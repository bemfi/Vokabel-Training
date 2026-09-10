<?php
/**
 * Vokabel-API (erfordert Login)
 *
 * GET  ?action=list&dataset_id=N  Vokabeln eines Datensatzes inkl. Lernstatus
 * POST ?action=add                Einzelne Vokabel anlegen
 * POST ?action=update             Vokabel bearbeiten
 * POST ?action=delete             Vokabel löschen
 * POST ?action=import             Bulk-Import (JSON-Zeilen, vom Frontend geparst)
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$userId = require_login();
$action = $_GET['action'] ?? '';
$body = request_body();

switch ($action) {

    case 'list':
        $datasetId = (int) ($_GET['dataset_id'] ?? 0);
        require_dataset_owner($datasetId, $userId);

        $stmt = db()->prepare(
            'SELECT v.*,
                    COALESCE(SUM(p.correct_count), 0) AS total_correct,
                    COALESCE(SUM(p.wrong_count), 0) AS total_wrong,
                    COUNT(p.id) AS directions_tracked,
                    COALESCE(MIN(p.correct_count), 0) AS min_correct
             FROM vocab v
             LEFT JOIN progress p ON p.vocab_id = v.id
             WHERE v.dataset_id = ?
             GROUP BY v.id
             ORDER BY v.id'
        );
        $stmt->execute([$datasetId]);
        json_response(['vocab' => $stmt->fetchAll()]);

    case 'add':
        $datasetId = (int) ($body['dataset_id'] ?? 0);
        $dataset = require_dataset_owner($datasetId, $userId);

        $word1 = trim($body['word1'] ?? '');
        $word2 = trim($body['word2'] ?? '');
        $word3 = trim($body['word3'] ?? '');
        $note = trim($body['note'] ?? '');

        if ($word1 === '' || $word2 === '') {
            json_error('Wort 1 und Wort 2 sind Pflichtfelder.', 400, 'vocab_fields_missing');
        }
        if ($word3 !== '' && empty($dataset['lang3'])) {
            json_error('Dieser Datensatz hat keine dritte Sprache.', 400, 'no_third_language');
        }

        $stmt = db()->prepare(
            'INSERT INTO vocab (dataset_id, word1, word2, word3, note) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$datasetId, $word1, $word2, $word3 !== '' ? $word3 : null, $note !== '' ? $note : null]);
        json_response(['ok' => true, 'id' => (int) db()->lastInsertId()]);

    case 'update':
        $id = (int) ($body['id'] ?? 0);
        $stmt = db()->prepare(
            'SELECT v.* FROM vocab v JOIN datasets d ON d.id = v.dataset_id
             WHERE v.id = ? AND d.user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        if (!$stmt->fetch()) {
            json_error('Vokabel nicht gefunden.', 404, 'vocab_not_found');
        }

        $word1 = trim($body['word1'] ?? '');
        $word2 = trim($body['word2'] ?? '');
        $word3 = trim($body['word3'] ?? '');
        $note = trim($body['note'] ?? '');

        if ($word1 === '' || $word2 === '') {
            json_error('Wort 1 und Wort 2 sind Pflichtfelder.', 400, 'vocab_fields_missing');
        }

        $stmt = db()->prepare(
            'UPDATE vocab SET word1 = ?, word2 = ?, word3 = ?, note = ? WHERE id = ?'
        );
        $stmt->execute([$word1, $word2, $word3 !== '' ? $word3 : null, $note !== '' ? $note : null, $id]);
        json_response(['ok' => true]);

    case 'delete':
        $id = (int) ($body['id'] ?? 0);
        $stmt = db()->prepare(
            'DELETE v FROM vocab v JOIN datasets d ON d.id = v.dataset_id
             WHERE v.id = ? AND d.user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        json_response(['ok' => true]);

    // Bulk-Import: Frontend parst Excel/CSV (SheetJS) und sendet JSON-Zeilen.
    case 'import':
        $datasetId = (int) ($body['dataset_id'] ?? 0);
        require_dataset_owner($datasetId, $userId);

        $rows = $body['rows'] ?? [];
        if (!is_array($rows) || count($rows) === 0) {
            json_error('Keine Zeilen zum Importieren.', 400, 'import_empty');
        }
        if (count($rows) > 5000) {
            json_error('Maximal 5000 Zeilen pro Import.', 400, 'import_too_large');
        }

        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO vocab (dataset_id, word1, word2, word3, note) VALUES (?, ?, ?, ?, ?)'
        );
        $imported = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $w1 = trim((string) ($row['word1'] ?? ''));
            $w2 = trim((string) ($row['word2'] ?? ''));
            $w3 = trim((string) ($row['word3'] ?? ''));
            $note = trim((string) ($row['note'] ?? ''));
            if ($w1 === '' || $w2 === '') {
                $skipped++;
                continue;
            }
            $stmt->execute([
                $datasetId,
                mb_substr($w1, 0, 255),
                mb_substr($w2, 0, 255),
                $w3 !== '' ? mb_substr($w3, 0, 255) : null,
                $note !== '' ? mb_substr($note, 0, 255) : null,
            ]);
            $imported++;
        }
        $pdo->commit();
        json_response(['ok' => true, 'imported' => $imported, 'skipped' => $skipped]);

    default:
        json_error('Unbekannte Aktion.', 404, 'unknown_action');
}
