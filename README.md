# Vokabeltrainer – Web-App im IT-Hummel-Design

Mehrbenutzerfähiger Vokabeltrainer mit bis zu **3 Sprachen pro Datensatz** (z.B. Deutsch · Italienisch · Englisch), Excel-Import und konfigurierbarem Lernziel. Design und Branding angelehnt an [ithummel.com](https://ithummel.com).

Läuft auf klassischem PHP-Webhosting (**All-Inkl**-kompatibel) – **kein Framework, kein Build-Schritt, kein Composer**. Hochladen per FTP genügt.

---

## 🌐 Funktionen

### Nutzer & Konten
- 👥 **Mehrere Nutzer** mit E-Mail-Registrierung/Login (Passwörter bcrypt-gehasht)
- 🔑 **Passwort-Reset per E-Mail** (Token-Link, 1 Stunde gültig, Einmalverwendung)

### Lernen
- 🗂️ **Beliebig viele Datensätze** pro Nutzer (z.B. "Italienisch Lektion 1") mit Fortschrittsbalken
- 🌍 **2 oder 3 Sprachen** pro Datensatz – alle Richtungen werden abgefragt (bei 3 Sprachen: 6 Richtungen)
- 📥 **Excel/CSV-Import** direkt im Browser (Spalte A/B/C = Sprachen, Spalte D = Notiz)
- 🔀 **Zufällige Abfragereihenfolge** über alle offenen Vokabel/Richtungs-Kombinationen
- ⚙️ **Einstellbares Lernziel**: Wort gilt als "gekonnt", wenn es z.B. 3× in *jeder* Richtung richtig war (1–20 einstellbar)
- 🔄 Optional: Zähler bei falscher Antwort auf 0 zurücksetzen
- ☑️ Abfragerichtungen einzeln wählbar (z.B. nur Deutsch → Italienisch)
- 📊 Fortschrittsanzeige pro Datensatz, Fortschritt jederzeit zurücksetzbar

### Oberfläche
- 🎨 **IT-Hummel-Branding**: offizielle Markenfarben (Navy, Brand-Blau, Orange), Logo und Favicon von ithummel.com
- 🌓 **Dark & Light Mode** (folgt Systemeinstellung, manuell umschaltbar, ohne Aufblitzen beim Laden)
- 🌐 **Oberfläche in Deutsch und Englisch** umschaltbar (automatische Browsersprach-Erkennung)
- 📱 Responsive – nutzbar auf Desktop, Tablet und Smartphone

### Rechtliches
- ⚖️ **Footer mit Impressum und Datenschutzerklärung** (zweisprachig DE/EN)
- 🗑️ **Konto-Löschen-Funktion** (Art. 17 DSGVO – entfernt alle Nutzerdaten unwiderruflich)
- 🚫 **Keine Drittanbieter**: keine CDNs, externen Fonts, Tracker oder Analyse-Tools – alles wird lokal ausgeliefert

---

## 🏗️ Architektur

**Klassische 3-Schichten-Anwendung ohne Framework** – bewusst einfach gehalten, damit sie auf Shared Hosting läuft und langfristig wartbar bleibt:

```
Browser (SPA)                PHP-API                    MySQL/MariaDB
┌──────────────┐  JSON/HTTPS  ┌──────────────┐  PDO       ┌────────────┐
│ index.html  │ ───────────▶ │ api/*.php    │ ───────▶ │ 6 Tabellen │
│ js/app.js   │ ◀─────────── │ (bootstrap)  │ ◀─────── │ (schema.sql)│
│ css/style.css│              └──────────────┘           └────────────┘
└──────────────┘
```

- **Frontend**: Vanilla JS als Single-Page-App. Excel-Parsing läuft komplett im Browser (SheetJS, lokal gehostet – kein CDN) – der Server bekommt nur JSON.
- **Backend**: Jede PHP-Datei ist ein Endpunkt mit `?action=`-Routing. `bootstrap.php` bündelt alles Gemeinsame (Session, DB, CSRF, Rate-Limit).
- **Datenbank**: Fortschritt wird pro **Vokabel + Richtung** gespeichert (`progress`-Tabelle, Richtung als `'1>2'` codiert).

---

## 📁 Projektstruktur

```
Vokabel-Training/
├── index.html            # Single-Page-Frontend (alle Views, i18n-Attribute)
├── favicon.ico           # Favicon (von ithummel.com)
├── css/
│   └── style.css         # Design-Tokens (CSS-Variablen), Light/Dark Theme
├── js/
│   ├── app.js            # Gesamte Frontend-Logik (i18n, Auth, CRUD, Training)
│   └── xlsx.full.min.js  # SheetJS lokal gehostet (DSGVO: kein CDN)
├── img/
│   ├── hummel-logo.png   # Offizielles IT-Hummel-Logo
│   └── icon-192.png      # App-Icon / Apple-Touch-Icon
├── api/                  # PHP-Backend (JSON-API)
│   ├── bootstrap.php     # Gemeinsame Basis: Session, DB, CSRF, Rate-Limit
│   ├── auth.php          # Registrierung, Login, Logout, Passwort-Reset
│   ├── datasets.php      # Datensätze: CRUD + Fortschritt-Reset
│   ├── vocab.php         # Vokabeln: CRUD + Bulk-Import
│   ├── training.php      # Abfrage-Logik, Antwort-Prüfung, Statistik
│   ├── config.example.php# Vorlage – als config.php kopieren und ausfüllen
│   └── .htaccess         # Schützt config.php & bootstrap.php vor Direktzugriff
└── sql/
    └── schema.sql        # Datenbankschema (6 Tabellen, idempotent)
```

---

## 🔌 API-Übersicht

Alle Endpunkte antworten mit JSON. Schreibende Aktionen (POST) benötigen den `X-CSRF-Token`-Header (Token kommt von `auth.php?action=me`).

| Endpunkt | Aktionen |
|---|---|
| `api/auth.php` | `register`, `login`, `logout`, `me`, `request_reset`, `reset_password`, `delete_account` |
| `api/datasets.php` | `list`, `create`, `update`, `delete`, `reset_progress` |
| `api/vocab.php` | `list`, `add`, `update`, `delete`, `import` |
| `api/training.php` | `next`, `answer`, `stats` |

Jede PHP-Datei enthält im Kopf-Kommentar die genauen Parameter.

---

## 🛠️ Deployment auf All-Inkl – Schritt-für-Schritt-Anleitung

Diese Anleitung führt dich von Null bis zur laufenden App. Geplante Reihenfolge:

1. Subdomain anlegen
2. SSL-Zertifikat (HTTPS) aktivieren
3. MySQL-Datenbank anlegen
4. Datenbankschema importieren
5. E-Mail-Adresse für Reset-Mails anlegen
6. `config.php` vorbereiten
7. Dateien per FTP hochladen
8. Testen
9. (Optional) Updates später einspielen

> Alle Schritte finden im **KAS** statt, der All-Inkl-Verwaltungsoberfläche: <https://kas.all-inkl.com> – melde dich mit deinen KAS-Zugangsdaten an (z.B. `w0123456`).

---

### Schritt 1: Subdomain anlegen

Eine eigene Subdomain wie `vokabeln.deine-domain.de` ist übersichtlicher als ein Unterordner.

1. Im KAS links auf **Subdomain** klicken.
2. Button **Neue Subdomain anlegen** (bzw. „+") wählen.
3. Subdomain-Namen eingeben, z.B. `vokabeln`, und deine Domain auswählen → ergibt `vokabeln.deine-domain.de`.
4. Bei **Ziel/Pfad** ein neues Verzeichnis angeben, z.B. `/vokabeltrainer/` (KAS legt den Ordner automatisch an).
5. **PHP-Version**: mindestens **PHP 8.1** wählen (im KAS unter Subdomain → Bearbeiten → PHP-Version, falls nicht schon Standard).
6. Speichern. Die Subdomain ist meist nach wenigen Minuten aktiv (DNS kann bis zu 1 Stunde dauern).

> **Alternative ohne Subdomain:** Du kannst die App auch in einen Unterordner deiner Hauptdomain legen (z.B. `deine-domain.de/vokabeltrainer/`). Dann Schritt 1 überspringen und beim FTP-Upload (Schritt 7) einfach einen Unterordner im Web-Verzeichnis der Hauptdomain anlegen.

---

### Schritt 2: SSL-Zertifikat (HTTPS) aktivieren

**Ohne HTTPS keine sichere Anmeldung!** Bei All-Inkl ist Let's Encrypt kostenlos.

1. Im KAS → **Subdomain** → deine neue Subdomain → **Bearbeiten**.
2. Abschnitt **SSL-Schutz** → **Bearbeiten** (Schloss-Symbol).
3. **Let's Encrypt** auswählen und aktivieren.
4. Zusätzlich **„SSL erzwingen"** (HTTPS-Weiterleitung) aktivieren – dadurch werden alle HTTP-Aufrufe automatisch auf HTTPS umgeleitet.
5. Speichern. Die Zertifikats-Ausstellung dauert einige Minuten.

> **Wichtig:** Das Zertifikat kann erst ausgestellt werden, wenn die Subdomain per DNS erreichbar ist. Falls es fehlschlägt: 30–60 Minuten warten und erneut versuchen.

---

### Schritt 3: MySQL-Datenbank anlegen

1. Im KAS → **Datenbanken** → **Neue Datenbank anlegen**.
2. Einen **Kommentar** vergeben (z.B. „Vokabeltrainer"), damit du sie später wiederfindest.
3. Ein **sicheres Passwort** setzen (Generator nutzen) und notieren.
4. Nach dem Anlegen zeigt das KAS dir:
   - **Datenbankname** (z.B. `d0123456`)
   - **Benutzername** (identisch mit dem Datenbanknamen)
   - **Host**: `localhost`
5. Diese drei Werte + Passwort brauchst du in Schritt 6.

---

### Schritt 4: Datenbankschema importieren

1. Im KAS → **Datenbanken** → bei deiner Datenbank auf **phpMyAdmin** klicken (oder direkt <https://pma.kasserver.com> öffnen und mit den DB-Zugangsdaten anmelden).
2. Links deine Datenbank (z.B. `d0123456`) anklicken.
3. Oben Reiter **Importieren** wählen.
4. **Datei auswählen** → die Datei `sql/schema.sql` aus diesem Projekt wählen.
5. Format „SQL" belassen → unten **OK** / **Importieren** klicken.
6. Erfolgskontrolle: Links sollten jetzt 6 Tabellen erscheinen:
   `users`, `password_resets`, `login_attempts`, `datasets`, `vocab`, `progress`.

---

### Schritt 5: E-Mail-Adresse für Reset-Mails anlegen

Damit Passwort-Reset-Mails nicht im Spam landen, sollte der Absender zu deiner Domain gehören.

1. Im KAS → **E-Mail** → **Neue E-Mail-Adresse anlegen**.
2. Adresse anlegen, z.B. `no-reply@deine-domain.de`.
   - Ein Postfach ist nicht zwingend nötig – die App **versendet** nur. Ein kleines Postfach schadet aber nicht (Bounces landen dort).
3. Die Adresse notieren – sie kommt in Schritt 6 in die `config.php`.

> Der Mail-Versand läuft über die PHP-`mail()`-Funktion, die bei All-Inkl ohne weitere Konfiguration funktioniert.

---

### Schritt 6: config.php vorbereiten

1. **Lokal** auf deinem PC: die Datei `api/config.example.php` kopieren und die Kopie `config.php` nennen (im selben Ordner `api/`).
2. Werte aus Schritt 3 und 5 eintragen:

```php
<?php
return [
    'db_host' => 'localhost',
    'db_name' => 'd0123456',              // aus Schritt 3
    'db_user' => 'd0123456',              // aus Schritt 3 (= DB-Name)
    'db_pass' => 'dein-DB-passwort',      // aus Schritt 3

    // Basis-URL der App (ohne Slash am Ende) – für die Reset-Links
    'app_url' => 'https://vokabeln.deine-domain.de',

    // Absender der Reset-E-Mails (aus Schritt 5)
    'mail_from' => 'no-reply@deine-domain.de',
];
```

> ⚠️ Die `config.php` niemals in ein öffentliches Git-Repository committen – sie steht deshalb in der `.gitignore`.

---

### Schritt 7: Dateien per FTP hochladen

**FTP-Zugangsdaten** findest du im KAS unter **FTP** (oder du legst dort einen neuen FTP-Benutzer an). Als FTP-Programm eignet sich z.B. [FileZilla](https://filezilla-project.org/) (kostenlos).

1. In FileZilla verbinden:
   - **Server:** `w0123456.kasserver.com` (steht im KAS unter FTP)
   - **Benutzer:** dein FTP-Benutzer (z.B. `w0123456`)
   - **Passwort:** dein FTP-Passwort
   - **Port:** 21 (FTP über TLS wählen: „Explizites FTP über TLS erfordern")
2. Rechts (Server) in das Verzeichnis wechseln, das du in Schritt 1 als Subdomain-Ziel angegeben hast (z.B. `/vokabeltrainer/`).
3. Diese Dateien/Ordner aus dem Projekt hochladen:

```
index.html
favicon.ico
css/            (kompletter Ordner)
js/             (kompletter Ordner)
img/            (kompletter Ordner – Logo & App-Icon)
api/            (kompletter Ordner – inkl. deiner config.php und der .htaccess!)
```

   **Nicht nötig auf dem Server:** `sql/` (nur für den Import), `README.md`, `.gitignore`.

4. **Kontrolle:** Prüfe, dass `api/.htaccess` mit hochgeladen wurde (FileZilla zeigt versteckte Dateien an unter *Server → Erzwinge Anzeige versteckter Dateien*). Sie schützt deine `config.php` vor direktem Zugriff.

---

### Schritt 8: Testen

1. **App öffnen:** `https://vokabeln.deine-domain.de` → die Login-Seite sollte erscheinen.
2. **Schloss-Symbol** in der Adressleiste prüfen (gültiges SSL-Zertifikat).
3. **Config-Schutz testen:** `https://vokabeln.deine-domain.de/api/config.php` aufrufen → es muss **403 Forbidden** erscheinen (nicht der Inhalt!).
4. **Registrieren:** Konto mit deiner echten E-Mail anlegen (Passwort: min. 8 Zeichen mit Buchstabe + Zahl).
5. **Datensatz anlegen**, 2–3 Vokabeln eintragen, Training starten.
6. **Passwort-Reset testen:** Abmelden → „Passwort vergessen?" → E-Mail eingeben → Mail sollte innerhalb weniger Minuten ankommen (auch im Spam-Ordner nachsehen) → Link öffnen → neues Passwort setzen.

#### Wenn etwas nicht funktioniert

| Problem | Ursache / Lösung |
|---|---|
| Weiße Seite / Fehler 500 | PHP-Version prüfen (min. 8.1, KAS → Subdomain → PHP-Version). |
| „config.php fehlt" | `config.php` wurde nicht hochgeladen oder liegt im falschen Ordner (muss in `api/` liegen). |
| „Datenbankverbindung fehlgeschlagen" | Zugangsdaten in `config.php` prüfen; `db_host` muss `localhost` sein. |
| Login geht, aber Aktionen schlagen fehl (403) | Seite einmal neu laden (CSRF-Token). Cookies im Browser erlauben. |
| Reset-Mail kommt nicht an | Spam-Ordner prüfen; `mail_from` muss zu deiner Domain gehören; im KAS prüfen, ob die Absenderadresse existiert. |
| Reset-Link führt zur Login-Seite | `app_url` in `config.php` prüfen (exakte URL der Subdomain, ohne Slash am Ende). |
| 403 beim Aufruf der App selbst | `.htaccess` versehentlich ins Hauptverzeichnis geladen – sie gehört **nur** in `api/`. |

---

### Schritt 9: Updates später einspielen

Wenn du den Code aktualisierst (z.B. via Git):

1. Geänderte Dateien einfach erneut per FTP hochladen und überschreiben.
2. **`api/config.php` nicht überschreiben/löschen** – sie enthält deine Zugangsdaten.
3. Falls sich `sql/schema.sql` geändert hat: Änderungen in phpMyAdmin nachziehen. Bei neuen Tabellen reicht ein erneuter Import (bestehende Tabellen bleiben durch `CREATE TABLE IF NOT EXISTS` unberührt). **Vorher Backup machen:** phpMyAdmin → Datenbank → **Exportieren** → SQL.

---

### Checkliste (Kurzfassung)

- [ ] Subdomain angelegt, Ziel-Ordner vergeben, PHP ≥ 8.1
- [ ] Let's-Encrypt-SSL aktiviert + „SSL erzwingen"
- [ ] MySQL-Datenbank angelegt, Zugangsdaten notiert
- [ ] `sql/schema.sql` in phpMyAdmin importiert (6 Tabellen sichtbar)
- [ ] `no-reply@`-Adresse im KAS angelegt
- [ ] `config.php` aus Vorlage erstellt und ausgefüllt
- [ ] Alles per FTP hochgeladen (inkl. `api/.htaccess` und `api/config.php`)
- [ ] `api/config.php` im Browser → 403
- [ ] Registrierung, Training und Passwort-Reset getestet

---

## 📥 Excel-Import – Format

| Spalte A (Sprache 1) | Spalte B (Sprache 2) | Spalte C (Sprache 3, optional) | Spalte D (Notiz, optional) |
|---|---|---|---|
| Haus | casa | house | Substantiv |
| gehen | andare | to go | |

- Erste Zeile darf Überschriften enthalten (wird automatisch erkannt).
- Unterstützt `.xlsx`, `.xls` und `.csv`.
- Mehrere gültige Antworten pro Zelle mit `;` oder `,` trennen – jede zählt beim Abfragen als richtig.
- Maximal 5000 Zeilen pro Import.

---

## 🎯 Lernlogik

- Jede Vokabel wird in **allen aktivierten Richtungen** abgefragt (bei 3 Sprachen: `1→2, 2→1, 1→3, 3→1, 2→3, 3→2`).
- Eine Richtung gilt als **gemeistert**, sobald sie *n*-mal richtig beantwortet wurde (*n* = Einstellung im Datensatz, Standard 3).
- Eine Vokabel gilt als **gekonnt**, wenn alle Richtungen gemeistert sind.
- Die Reihenfolge der Abfrage ist zufällig über alle noch offenen Kombinationen.
- Groß-/Kleinschreibung wird beim Antwortvergleich ignoriert.

---

## 💻 Lokal testen (optional)

Voraussetzungen: PHP ≥ 8.1 und eine lokale MySQL/MariaDB (z.B. via XAMPP).

```bash
# 1. Datenbank anlegen und Schema importieren
mysql -u root -p -e "CREATE DATABASE vokabeltrainer CHARACTER SET utf8mb4"
mysql -u root -p vokabeltrainer < sql/schema.sql

# 2. api/config.example.php nach api/config.php kopieren und ausfüllen
#    (db_name = vokabeltrainer, db_user/db_pass = lokale MySQL-Zugangsdaten)

# 3. PHP-Entwicklungsserver im Projektordner starten
php -S localhost:8000
```

Browser: `http://localhost:8000`

> Hinweis: Der Passwort-Reset-Mailversand funktioniert lokal meist nicht (PHP `mail()` braucht einen konfigurierten Mailserver) – alles andere ist voll testbar.

---

## 🔧 Wartung & Erweiterung

Hinweise für spätere Anpassungen – wo was zu ändern ist:

| Ich möchte … | Datei / Ort |
|---|---|
| Texte/Übersetzungen ändern oder Sprache ergänzen | `js/app.js` → `I18N`-Objekt (plus `data-i18n`-Attribute in `index.html`) |
| Farben/Design anpassen | `css/style.css` → CSS-Variablen in `:root` und `[data-theme="dark"]` |
| Neuen API-Endpunkt hinzufügen | Neue `case`-Zeile in passender `api/*.php`; gemeinsame Helfer in `bootstrap.php` |
| Passwort-Regeln ändern | `api/auth.php` → `validate_password()` |
| Rate-Limits anpassen | `api/bootstrap.php` → `check_rate_limit()`-Defaults bzw. Aufrufe in `auth.php` |
| Reset-Mail-Text ändern | `api/auth.php` → `send_reset_mail()` |
| Datenbankfeld ergänzen | `sql/schema.sql` + betroffene `api/*.php` + Formular in `index.html`/`app.js` |
| Import-Limit (5000 Zeilen) ändern | `api/vocab.php` → `case 'import'` |

**Konventionen im Projekt:**
- PHP: `declare(strict_types=1)`, Prepared Statements überall, `snake_case` für DB/API-Felder
- JS: `camelCase`, alle DOM-Zugriffe über den `$()`-Helfer, alle Nutzereingaben durch `escapeHtml()`
- Jede API-Datei dokumentiert ihre Aktionen im Kopf-Kommentar
- Keine Build-Tools: was im Repo liegt, läuft 1:1 auf dem Server

---

## 🔒 Sicherheit

- Passwörter mit `password_hash()` (bcrypt) gespeichert, automatisches Rehashing bei Algorithmus-Updates
- Passwort-Richtlinie: min. 8 Zeichen mit Buchstabe und Zahl
- Alle SQL-Abfragen mit Prepared Statements (kein SQL-Injection-Risiko)
- **CSRF-Schutz**: alle schreibenden Anfragen benötigen ein Session-gebundenes Token (`X-CSRF-Token`-Header)
- **Brute-Force-Schutz**: max. 8 Login-Versuche pro 15 Minuten (pro E-Mail und IP), max. 3 Reset-Anfragen pro Stunde
- **Passwort-Reset**: kryptografisch sicheres Token (256 Bit), nur als SHA-256-Hash gespeichert, 1 Stunde gültig, Einmalverwendung; keine User-Enumeration (Antwort immer gleich)
- Session-Cookies: `HttpOnly`, `SameSite=Strict`, `Secure` (bei HTTPS), `session_regenerate_id` bei Login
- Security-Header: `X-Content-Type-Options`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Cache-Control: no-store`
- `config.php` per `.htaccess` vor direktem Zugriff geschützt
- Ausgaben im Frontend HTML-escaped (kein XSS)

> **Wichtig:** Die Seite muss über **HTTPS** laufen (bei All-Inkl kostenlos per Let's Encrypt im KAS aktivierbar).

---

## ⚖️ Rechtliches (Impressum & DSGVO)

Die App bringt die rechtlich notwendigen Bausteine für den Betrieb in Deutschland mit:

| Baustein | Status | Wo |
|---|---|---|
| **Impressum** (§ 5 DDG) | ⚠️ Platzhalter – vor Veröffentlichung ausfüllen! | `index.html` → `view-imprint` |
| **Datenschutzerklärung** (Art. 13 DSGVO) | ✅ Vorformuliert (DE + EN) | `js/app.js` → `PRIVACY_HTML` |
| **Recht auf Löschung** (Art. 17 DSGVO) | ✅ Selbstbedienung: „Konto löschen“-Button | Datensatz-Übersicht |
| **Datenminimierung** | ✅ Nur E-Mail, Benutzername, Passwort-Hash + Inhalte | – |
| **Kein Cookie-Banner nötig** | ✅ Nur technisch notwendiges Session-Cookie, kein Tracking | – |
| **Keine Drittanbieter** | ✅ SheetJS lokal gehostet, keine externen Fonts/CDNs | `js/xlsx.full.min.js` |
| **Login-Versuche** | ✅ IP-Speicherung nur 24h (Missbrauchsschutz, Art. 6 Abs. 1 lit. f) | `login_attempts`-Tabelle |

**Vor der Veröffentlichung noch zu tun:**

1. **Impressum vervollständigen**: In `index.html` (Abschnitt `view-imprint`) die Platzhalter `[Straße und Hausnummer]` und `[PLZ]` durch die echte Anschrift ersetzen – am einfachsten identisch zum Impressum auf ithummel.com.
2. **Datenschutzerklärung prüfen**: Die vorformulierten Texte in `js/app.js` (Konstante `PRIVACY_HTML`) decken die tatsächliche Datenverarbeitung der App ab – bitte einmal gegenlesen und ggf. an eure Gegebenheiten anpassen.

> **Hinweis:** Diese Vorlagen sind sorgfältig auf die tatsächliche Funktionsweise der App abgestimmt, ersetzen aber keine Rechtsberatung. Im Zweifel kurz anwaltlich prüfen lassen.
