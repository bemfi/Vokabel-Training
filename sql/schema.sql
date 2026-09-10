-- Vokabeltrainer – Datenbankschema für MySQL/MariaDB (All-Inkl kompatibel)
-- In phpMyAdmin importieren oder per Kommandozeile ausführen.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Passwort-Reset: nur der SHA-256-Hash des Tokens wird gespeichert
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token_hash (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Brute-Force-Schutz: fehlgeschlagene Versuche pro IP/E-Mail
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(190) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier, attempted_at),
    INDEX idx_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS datasets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    lang1 VARCHAR(40) NOT NULL,
    lang2 VARCHAR(40) NOT NULL,
    lang3 VARCHAR(40) DEFAULT NULL,
    required_correct TINYINT UNSIGNED NOT NULL DEFAULT 3,
    reset_on_wrong TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vocab (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT UNSIGNED NOT NULL,
    word1 VARCHAR(255) NOT NULL,
    word2 VARCHAR(255) NOT NULL,
    word3 VARCHAR(255) DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fortschritt pro Vokabel und Abfragerichtung.
-- direction: z.B. '1>2' = Sprache 1 wird gezeigt, Sprache 2 muss eingegeben werden.
CREATE TABLE IF NOT EXISTS progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vocab_id INT UNSIGNED NOT NULL,
    direction CHAR(3) NOT NULL,
    correct_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    wrong_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_seen TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_vocab_direction (vocab_id, direction),
    FOREIGN KEY (vocab_id) REFERENCES vocab(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
