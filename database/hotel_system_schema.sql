-- ════════════════════════════════════════════════════════
-- HospitOS — Hospitality Operating System v2.0
-- Schéma de base de données modulaire & marque blanche
-- ════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `hotel_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `hotel_system`;

-- ────────────────────────────────────────────────────────
-- TABLE : users
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`             CHAR(36)        NOT NULL DEFAULT (UUID()),
  `nom`            VARCHAR(100)    NOT NULL,
  `prenom`         VARCHAR(100)    NOT NULL,
  `email`          VARCHAR(191)    NOT NULL,
  `code_client`    VARCHAR(20)     NOT NULL COMMENT 'Code identification client unique (ex: CLI-2026-XXXX)',
  `telephone`      VARCHAR(20)     NULL,
  `pays`           VARCHAR(80)     NULL,
  `ville`          VARCHAR(100)    NULL,
  `adresse`        VARCHAR(255)    NULL,
  `role`           ENUM('client','admin','super_admin') NOT NULL DEFAULT 'client',
  `email_verified` TINYINT(1)      NOT NULL DEFAULT 0,
  `otp_code`       VARCHAR(10)     NULL,
  `otp_expires_at` DATETIME        NULL,
  `otp_attempts`   INT             NOT NULL DEFAULT 0,
  `temp_new_email` VARCHAR(191)    NULL,
  `temp_new_code`  VARCHAR(20)     NULL,
  `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`     DATETIME        NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_code_client` (`code_client`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : admin_permissions
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_permissions` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `user_id` CHAR(36) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `can_view` TINYINT(1) NOT NULL DEFAULT 1,
  `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_module` (`user_id`, `module`),
  CONSTRAINT `fk_perm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : chambres
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `chambres` (
  `id`                CHAR(36)         NOT NULL DEFAULT (UUID()),
  `nom`               VARCHAR(150)     NOT NULL,
  `type`              ENUM('standard','superieure','suite','villa') NOT NULL,
  `superficie_m2`     TINYINT UNSIGNED NOT NULL,
  `prix_nuit`         DECIMAL(10,2)    NOT NULL COMMENT 'Prix dans la devise de l établissement',
  `capacite_max`      TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `capacite_enfants`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `description`       TEXT             NULL,
  `amenities`         JSON             NULL,
  `image_principale`  VARCHAR(255)     NULL,
  `disponible`        TINYINT(1)       NOT NULL DEFAULT 1,
  `statut_menage`     ENUM('propre','a_nettoyer','en_cours','maintenance') NOT NULL DEFAULT 'propre',
  `etage`             TINYINT          NOT NULL DEFAULT 0,
  `numero`            INT              NOT NULL DEFAULT 0,
  `created_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_disponible` (`disponible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : options
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `options` (
  `id`          CHAR(36)      NOT NULL DEFAULT (UUID()),
  `nom`         VARCHAR(100)  NOT NULL,
  `description` TEXT          NULL,
  `prix`        DECIMAL(10,2) NOT NULL,
  `unite`       VARCHAR(50)   NOT NULL DEFAULT 'par séjour',
  `actif`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : codes_promo
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `codes_promo` (
  `id`              CHAR(36) NOT NULL DEFAULT (UUID()),
  `code`            VARCHAR(50) NOT NULL,
  `description`     VARCHAR(255) NULL,
  `type_reduction`  ENUM('pourcentage', 'montant_fixe') NOT NULL DEFAULT 'pourcentage',
  `valeur`          DECIMAL(10,2) NOT NULL,
  `montant_min`     DECIMAL(10,2) NOT NULL DEFAULT 0,
  `date_debut`      DATETIME NULL,
  `date_fin`        DATETIME NULL,
  `utilisations_max` INT NULL,
  `utilisations_actuelles` INT NOT NULL DEFAULT 0,
  `actif`           TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : reservations
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reservations` (
  `id`                 CHAR(36)        NOT NULL DEFAULT (UUID()),
  `reference`          VARCHAR(20)     NOT NULL COMMENT 'Format : HTL-AAAA-XXXX',
  `user_id`            CHAR(36)        NOT NULL,
  `chambre_id`         CHAR(36)        NOT NULL,
  `date_arrivee`       DATE            NOT NULL,
  `date_depart`        DATE            NOT NULL,
  `nb_adultes`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `nb_enfants`         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `prix_total`         DECIMAL(10,2)   NOT NULL,
  `prix_nuit`          DECIMAL(10,2)   NOT NULL,
  `prix_options`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `montant_reduction`  DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `code_promo_id`      CHAR(36)        NULL,
  `demandes_speciales` TEXT            NULL,
  `statut`             ENUM('en_cours','validee','en_sejour','terminee','annulee','modifiee') NOT NULL DEFAULT 'en_cours',
  `mode_paiement`      VARCHAR(50)     NULL,
  `statut_paiement`    ENUM('en_attente','partiel','paye','rembourse') NOT NULL DEFAULT 'en_attente',
  `note_admin`         TEXT            NULL,
  `valide_par`         CHAR(36)        NULL,
  `valide_at`          DATETIME        NULL,
  `checkin_at`         DATETIME        NULL,
  `checkout_at`        DATETIME        NULL,
  `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reference` (`reference`),
  INDEX `idx_statut` (`statut`),
  INDEX `idx_dates` (`date_arrivee`, `date_depart`),
  CONSTRAINT `fk_res_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_chambre` FOREIGN KEY (`chambre_id`) REFERENCES `chambres` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : reservation_options
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reservation_options` (
  `reservation_id` CHAR(36) NOT NULL,
  `option_id`      CHAR(36) NOT NULL,
  `quantite`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `prix_unitaire`  DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`reservation_id`, `option_id`),
  CONSTRAINT `fk_ro_res`    FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ro_option` FOREIGN KEY (`option_id`)      REFERENCES `options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : avis_clients
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `avis_clients` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `reservation_id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `note` TINYINT UNSIGNED NOT NULL COMMENT 'Note sur 5 étoiles',
  `titre` VARCHAR(150) NULL,
  `commentaire` TEXT NOT NULL,
  `reponse_hotel` TEXT NULL,
  `statut` ENUM('en_attente', 'publie', 'refuse') NOT NULL DEFAULT 'en_attente',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `publie_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reservation_avis` (`reservation_id`),
  CONSTRAINT `fk_avis_res` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : devis_evenements
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `devis_evenements` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `reference` VARCHAR(20) NOT NULL,
  `nom_contact` VARCHAR(150) NOT NULL,
  `entreprise` VARCHAR(150) NULL,
  `email` VARCHAR(191) NOT NULL,
  `telephone` VARCHAR(30) NOT NULL,
  `type_evenement` VARCHAR(100) NOT NULL,
  `espace_souhaite` VARCHAR(100) NULL,
  `date_evenement` DATE NOT NULL,
  `date_fin` DATE NULL,
  `nb_participants` INT NOT NULL DEFAULT 10,
  `services_souhaites` JSON NULL,
  `budget_estime` VARCHAR(100) NULL,
  `message` TEXT NULL,
  `statut` ENUM('nouveau', 'en_etude', 'traite', 'refuse') NOT NULL DEFAULT 'nouveau',
  `note_interne` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_devis_ref` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : room_service_commandes
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `room_service_commandes` (
  `id` CHAR(36) NOT NULL DEFAULT (UUID()),
  `reference` VARCHAR(20) NOT NULL,
  `user_id` CHAR(36) NULL,
  `chambre_numero` VARCHAR(50) NOT NULL,
  `client_nom` VARCHAR(150) NOT NULL,
  `client_telephone` VARCHAR(30) NULL,
  `client_email` VARCHAR(191) NULL,
  `elements_commande` JSON NOT NULL,
  `total_estime` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `instructions` TEXT NULL,
  `statut` ENUM('recue', 'en_preparation', 'en_livraison', 'livree', 'annulee') NOT NULL DEFAULT 'recue',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rs_ref` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────
-- TABLE : indisponibilites
-- ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `indisponibilites` (
  `id`          CHAR(36)     NOT NULL DEFAULT (UUID()),
  `chambre_id`  CHAR(36)     NOT NULL,
  `date_debut`  DATE         NOT NULL,
  `date_fin`    DATE         NOT NULL,
  `motif`       VARCHAR(255) NOT NULL DEFAULT 'Maintenance',
  `cree_par`    CHAR(36)     NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_indisp_chambre` FOREIGN KEY (`chambre_id`) REFERENCES `chambres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
