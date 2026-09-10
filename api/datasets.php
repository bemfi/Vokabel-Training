<?php
/**
 * Datensätze-API (erfordert Login)
 *
 * GET  ?action=list            Alle Datensätze des Nutzers inkl. Fortschritt
 * POST ?action=create          Neuen Datensatz anlegen
 * POST ?action=update          Datensatz bearbeiten
 * POST ?action=delete          Datensatz inkl. Vokabeln löschen
 * POST ?action=reset_progress  Lernfortschritt des Datensatzes zurücksetzen
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$userId = require_login();
$action = $_GET['action'] ?? '';
$body = request_body();

switch ($action) {

    case 'list':
        // vocab_count + mastered_count in einer Query (vermeidet N+1).
        // Eine Vokabel ist "gekonnt", wenn alle Richtungen (2 Sprachen = 2,
        // 3 Sprachen = 6) das Lernziel erreicht haben.
        $stmt = db()->prepare(
            'SELECT d.*,
                    COUNT(v.id) AS vocab_count,
                    COALESCE(SUM(
                        (SELECT COUNT(*) FROM progress p
                         WHERE p.vocab_id = v.id AND p.correct_count >= d.required_correct)
                        >= IF(d.lang3 IS NULL, 2, 6)
                    ), 0) AS mastered_count
             FROM datasets d
             LEFT JOIN vocab v ON v.dataset_id = d.id
             WHERE d.user_id = ?
             GROUP BY d.id
             ORDER BY d.created_at DESC'
        );
        $stmt->execute([$userId]);
        json_response(['datasets' => $stmt->fetchAll()]);

    case 'create':
        $name = trim($body['name'] ?? '');
        $lang1 = trim($body['lang1'] ?? '');
        $lang2 = trim($body['lang2'] ?? '');
        $lang3 = trim($body['lang3'] ?? '');
        $required = max(1, min(20, (int) ($body['required_correct'] ?? 3)));
        $resetOnWrong = !empty($body['reset_on_wrong']) ? 1 : 0;

        if ($name === '' || $lang1 === '' || $lang2 === '') {
            json_error('Name, Sprache 1 und Sprache 2 sind Pflichtfelder.', 400, 'dataset_fields_missing');
        }

        $stmt = db()->prepare(
            'INSERT INTO datasets (user_id, name, lang1, lang2, lang3, required_correct, reset_on_wrong)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $name, $lang1, $lang2, $lang3 !== '' ? $lang3 : null, $required, $resetOnWrong]);
        json_response(['ok' => true, 'id' => (int) db()->lastInsertId()]);

    case 'update':
        $id = (int) ($body['id'] ?? 0);
        require_dataset_owner($id, $userId);

        $name = trim($body['name'] ?? '');
        $lang1 = trim($body['lang1'] ?? '');
        $lang2 = trim($body['lang2'] ?? '');
        $lang3 = trim($body['lang3'] ?? '');
        $required = max(1, min(20, (int) ($body['required_correct'] ?? 3)));
        $resetOnWrong = !empty($body['reset_on_wrong']) ? 1 : 0;

        if ($name === '' || $lang1 === '' || $lang2 === '') {
            json_error('Name, Sprache 1 und Sprache 2 sind Pflichtfelder.', 400, 'dataset_fields_missing');
        }

        $stmt = db()->prepare(
            'UPDATE datasets SET name = ?, lang1 = ?, lang2 = ?, lang3 = ?, required_correct = ?, reset_on_wrong = ?
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$name, $lang1, $lang2, $lang3 !== '' ? $lang3 : null, $required, $resetOnWrong, $id, $userId]);
        json_response(['ok' => true]);

    case 'delete':
        $id = (int) ($body['id'] ?? 0);
        require_dataset_owner($id, $userId);
        $stmt = db()->prepare('DELETE FROM datasets WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        json_response(['ok' => true]);

    case 'reset_progress':
        $id = (int) ($body['id'] ?? 0);
        require_dataset_owner($id, $userId);
        $stmt = db()->prepare(
            'DELETE p FROM progress p
             JOIN vocab v ON v.id = p.vocab_id
             WHERE v.dataset_id = ?'
        );
        $stmt->execute([$id]);
        json_response(['ok' => true]);

    default:
        json_error('Unbekannte Aktion.', 404, 'unknown_action');
}
