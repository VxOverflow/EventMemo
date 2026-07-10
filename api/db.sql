CREATE DATABASE IF NOT EXISTS Memora
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE Memora;

CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prix DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    nb_evenements_max INT UNSIGNED NULL,
    nb_souvenirs_max_par_event INT UNSIGNED NULL,
    espace_max_mo INT UNSIGNED NULL,
    description TEXT NULL,
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20) NOT NULL UNIQUE,
    telephone_verifie BOOLEAN NOT NULL DEFAULT FALSE,
    avatar VARCHAR(255) NULL,
    plan_id INT UNSIGNED NULL,
    plan_expire_le DATETIME NULL,
    suspendu BOOLEAN NOT NULL DEFAULT FALSE,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_plan
        FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT NULL,
    date_evenement DATE NOT NULL,
    lieu VARCHAR(150) NULL,
    token_acces VARCHAR(64) NOT NULL UNIQUE,
    statut ENUM('actif', 'clos') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS guests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_guests_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_guest (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    guest_id INT UNSIGNED NOT NULL,
    date_participation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_guest (event_id, guest_id),
    CONSTRAINT fk_event_guest_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_event_guest_guest
        FOREIGN KEY (guest_id) REFERENCES guests(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS souvenirs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    guest_id INT UNSIGNED NOT NULL,
    contenu TEXT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_souvenirs_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_souvenirs_guest
        FOREIGN KEY (guest_id) REFERENCES guests(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS souvenir_media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    souvenir_id INT UNSIGNED NOT NULL,
    type ENUM('photo', 'audio', 'video') NOT NULL,
    url VARCHAR(255) NOT NULL,
    duree_secondes INT UNSIGNED NULL,
    taille_octets BIGINT UNSIGNED NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_souvenir_media_type (type),
    CONSTRAINT fk_souvenir_media_souvenir
        FOREIGN KEY (souvenir_id) REFERENCES souvenirs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS otp_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    code VARCHAR(6) NOT NULL,
    type ENUM('inscription', 'reset_password') NOT NULL,
    expire_a DATETIME NOT NULL,
    utilise BOOLEAN NOT NULL DEFAULT FALSE,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_otp_codes_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    moyen_paiement VARCHAR(50) NULL,
    reference_externe VARCHAR(100) NULL,
    statut ENUM('en_attente', 'reussi', 'echoue') NOT NULL DEFAULT 'en_attente',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_transactions_plan
        FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT IGNORE INTO plans (id, nom, prix, description)
VALUES (1, 'Gratuit', 0.00, 'Plan gratuit par défaut.');
