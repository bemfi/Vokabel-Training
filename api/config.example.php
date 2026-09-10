<?php
// Kopie dieser Datei als config.php anlegen und Zugangsdaten eintragen.
// Bei All-Inkl findest du die MySQL-Daten im KAS unter "Datenbanken".
return [
    'db_host' => 'localhost',
    'db_name' => 'd0XXXXXX',      // z.B. d0123456
    'db_user' => 'd0XXXXXX',      // meist identisch mit dem DB-Namen
    'db_pass' => 'GEHEIM',

    // Basis-URL der App für Passwort-Reset-Links (ohne Slash am Ende).
    // Leer lassen = automatisch aus dem Request ermitteln.
    'app_url' => '',              // z.B. 'https://deine-domain.de/vokabeltrainer'

    // Absender für Reset-E-Mails (sollte zur Domain passen, sonst Spam-Gefahr)
    'mail_from' => '',            // z.B. 'no-reply@deine-domain.de'
];
