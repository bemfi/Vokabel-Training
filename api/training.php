<?php
/**
 * Trainings-API (erfordert Login)
 *
 * GET  ?action=next&dataset_id=N[&directions=1>2,2>1]  Nächste zufällige Frage
 * POST ?action=answer                                  Antwort prüfen + Fortschritt speichern
 * GET  ?action=stats&dataset_id=N                      Lernstatistik des Datensatzes
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$userId = require_login();
$action = $_GET['action'] ?? '';
$body = request_body();

/** Alle Abfragerichtungen eines Datensatzes, z.B. ['1>2','2>1',...]. */
function dataset_directions(array $dataset): array
{
    $langs = empty($dataset['lang3']) ? [1, 2] : [1, 2, 3];
    $dirs = [];
    foreach ($langs as $from) {
        foreach ($langs as $to) {
            if ($from !== $to) {
                $dirs[] = "$from>$to";
            }
        }
    }
    return $dirs;
}

switch ($action) {

    // Nächste Frage: zufällige, noch nicht gemeisterte Vokabel/Richtung
    case 'next':
        $datasetId = (int) ($_GET['dataset_id'] ?? 0);
        $dataset = require_dataset_owner($datasetId, $userId);
        $required = (int) $dataset['required_correct'];
        $directions = dataset_directions($dataset);

        // Optionale Richtungsfilter aus dem Frontend (z.B. nur '1>2')
        $filter = isset($_GET['directions']) ? array_intersect(explode(',', $_GET['directions']), $directions) : $directions;
        if (count($filter) === 0) {
            $filter = $directions;
        }

        $stmt = db()->prepare('SELECT * FROM vocab WHERE dataset_id = ?');
        $stmt->execute([$datasetId]);
        $vocabRows = $stmt->fetchAll();
        if (count($vocabRows) === 0) {
            json_response(['done' => true, 'reason' => 'empty']);
        }

        $ids = array_column($vocabRows, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare(
            "SELECT vocab_id, direction, correct_count, wrong_count
             FROM progress WHERE vocab_id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $progress = [];
        foreach ($stmt->fetchAll() as $p) {
            $progress[$p['vocab_id']][$p['direction']] = $p;
        }

        // Offene Kombinationen (Vokabel + Richtung) sammeln
        $open = [];
        foreach ($vocabRows as $v) {
            foreach ($filter as $dir) {
                // Richtung nur, wenn beide Wörter existieren (word3 kann leer sein)
                [$from, $to] = explode('>', $dir);
                if (($v["word$from"] ?? null) === null || ($v["word$to"] ?? null) === null) {
                    continue;
                }
                $correct = (int) ($progress[$v['id']][$dir]['correct_count'] ?? 0);
                if ($correct < $required) {
                    $open[] = ['vocab' => $v, 'direction' => $dir, 'correct' => $correct];
                }
            }
        }

        if (count($open) === 0) {
            json_response(['done' => true, 'reason' => 'mastered']);
        }

        $pick = $open[random_int(0, count($open) - 1)];
        [$from, $to] = explode('>', $pick['direction']);
        $langNames = [1 => $dataset['lang1'], 2 => $dataset['lang2'], 3 => $dataset['lang3']];

        json_response([
            'done' => false,
            'vocab_id' => (int) $pick['vocab']['id'],
            'direction' => $pick['direction'],
            'question' => $pick['vocab']["word$from"],
            'question_lang' => $langNames[(int) $from],
            'answer_lang' => $langNames[(int) $to],
            'note' => $pick['vocab']['note'],
            'correct_count' => $pick['correct'],
            'required_correct' => $required,
            'open_total' => count($open),
        ]);

    // Antwort prüfen und Fortschritt speichern
    case 'answer':
        $vocabId = (int) ($body['vocab_id'] ?? 0);
        $direction = (string) ($body['direction'] ?? '');
        $answer = trim((string) ($body['answer'] ?? ''));

        if (!preg_match('/^[123]>[123]$/', $direction)) {
            json_error('Ungültige Richtung.');
        }

        $stmt = db()->prepare(
            'SELECT v.*, d.required_correct, d.reset_on_wrong
             FROM vocab v JOIN datasets d ON d.id = v.dataset_id
             WHERE v.id = ? AND d.user_id = ?'
        );
        $stmt->execute([$vocabId, $userId]);
        $vocab = $stmt->fetch();
        if (!$vocab) {
            json_error('Vokabel nicht gefunden.', 404);
        }

        [, $to] = explode('>', $direction);
        $expected = (string) ($vocab["word$to"] ?? '');

        // Vergleich: Groß/Klein ignorieren, mehrere Lösungen per ; oder , erlaubt
        $normalize = fn(string $s): string => mb_strtolower(trim(preg_replace('/\s+/', ' ', $s)));
        $accepted = array_map($normalize, preg_split('/[;,\/]/', $expected));
        $isCorrect = in_array($normalize($answer), $accepted, true);

        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO progress (vocab_id, direction, correct_count, wrong_count, last_seen)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                correct_count = ' . ($isCorrect
                    ? 'correct_count + 1'
                    : ((int) $vocab['reset_on_wrong'] === 1 ? '0' : 'correct_count')) . ',
                wrong_count = wrong_count + ' . ($isCorrect ? '0' : '1') . ',
                last_seen = NOW()'
        );
        $stmt->execute([$vocabId, $direction, $isCorrect ? 1 : 0, $isCorrect ? 0 : 1]);

        $stmt = $pdo->prepare('SELECT correct_count FROM progress WHERE vocab_id = ? AND direction = ?');
        $stmt->execute([$vocabId, $direction]);
        $newCount = (int) $stmt->fetchColumn();

        json_response([
            'correct' => $isCorrect,
            'expected' => $expected,
            'correct_count' => $newCount,
            'required_correct' => (int) $vocab['required_correct'],
            'mastered_direction' => $newCount >= (int) $vocab['required_correct'],
        ]);

    // Statistik pro Datensatz
    case 'stats':
        $datasetId = (int) ($_GET['dataset_id'] ?? 0);
        $dataset = require_dataset_owner($datasetId, $userId);
        $required = (int) $dataset['required_correct'];
        $directions = dataset_directions($dataset);

        $stmt = db()->prepare('SELECT * FROM vocab WHERE dataset_id = ?');
        $stmt->execute([$datasetId]);
        $vocabRows = $stmt->fetchAll();

        $totalCombos = 0;
        $masteredCombos = 0;
        $masteredWords = 0;

        if (count($vocabRows) > 0) {
            $ids = array_column($vocabRows, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = db()->prepare(
                "SELECT vocab_id, direction, correct_count FROM progress WHERE vocab_id IN ($placeholders)"
            );
            $stmt->execute($ids);
            $progress = [];
            foreach ($stmt->fetchAll() as $p) {
                $progress[$p['vocab_id']][$p['direction']] = (int) $p['correct_count'];
            }

            foreach ($vocabRows as $v) {
                $wordMastered = true;
                $wordHasCombo = false;
                foreach ($directions as $dir) {
                    [$from, $to] = explode('>', $dir);
                    if (($v["word$from"] ?? null) === null || ($v["word$to"] ?? null) === null) {
                        continue;
                    }
                    $wordHasCombo = true;
                    $totalCombos++;
                    if (($progress[$v['id']][$dir] ?? 0) >= $required) {
                        $masteredCombos++;
                    } else {
                        $wordMastered = false;
                    }
                }
                if ($wordHasCombo && $wordMastered) {
                    $masteredWords++;
                }
            }
        }

        json_response([
            'vocab_total' => count($vocabRows),
            'mastered_words' => $masteredWords,
            'combos_total' => $totalCombos,
            'combos_mastered' => $masteredCombos,
            'directions' => $directions,
        ]);

    default:
        json_error('Unbekannte Aktion.', 404);
}
