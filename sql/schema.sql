-- kiNouGarde — structure de la base
--
-- Différences avec le schéma d'origine (dump phpMyAdmin d'avril 2024) :
--   * la table `messsages` (avec trois s) faisait doublon avec `messages` :
--     supprimée, une seule table de messages ;
--   * `messages.sender` / `receiver` étaient des VARCHAR(250) contenant
--     tantôt un identifiant numérique, tantôt le texte « Parent ». Ce sont
--     désormais des clés étrangères entières vers `utilisateurs` ;
--   * `contrats_emploi.montant` était un VARCHAR : passé en DECIMAL ;
--   * `utilisateurs.email` n'avait aucune contrainte d'unicité — deux comptes
--     pouvaient partager la même adresse, ce qui rendait la connexion ambiguë ;
--   * le mot de passe est stocké une seule fois, sur `utilisateurs`. Il était
--     auparavant dupliqué sur `parents` et `nounous`, chacune avec sa propre
--     politique (l'une hachée, l'autre en clair) ;
--   * jeu de caractères utf8mb4 (utf8 de MySQL ne couvre pas tout l'Unicode).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `contrats_emploi`;
DROP TABLE IF EXISTS `enfants`;
DROP TABLE IF EXISTS `nounous`;
DROP TABLE IF EXISTS `parents`;
DROP TABLE IF EXISTS `utilisateurs`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Identité : un compte, un rôle, un mot de passe haché.
-- --------------------------------------------------------
CREATE TABLE `utilisateurs` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `nom`          VARCHAR(100) NOT NULL,
  `prenom`       VARCHAR(100) NOT NULL,
  `telephone`    VARCHAR(20)  DEFAULT NULL,
  `email`        VARCHAR(190) NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL COMMENT 'empreinte password_hash(), jamais le mot de passe',
  `role`         ENUM('parent','nounou') NOT NULL,
  `cree_le`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_utilisateurs_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Profils : les champs propres à chaque rôle.
-- --------------------------------------------------------
CREATE TABLE `parents` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `utilisateur_id`  INT NOT NULL,
  `ville`           VARCHAR(50)  NOT NULL,
  `profession`      VARCHAR(100) DEFAULT NULL,
  `nb_enfants`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uk_parents_utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_parents_utilisateur` FOREIGN KEY (`utilisateur_id`)
    REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nounous` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `utilisateur_id`  INT NOT NULL,
  `sexe`            ENUM('femme','homme','autre') NOT NULL,
  `ville`           VARCHAR(50)  NOT NULL,
  `type_service`    VARCHAR(100) NOT NULL COMMENT 'garde, ménage, aide aux devoirs…',
  `horaires`        VARCHAR(100) NOT NULL,
  `montant`         DECIMAL(10,2) NOT NULL,
  `paiement`        ENUM('heure','jour','mois') NOT NULL DEFAULT 'mois',
  `photo_profil`    VARCHAR(250) DEFAULT NULL,
  `verifiee`        TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Identité vérifiée manuellement — pas encore de back-office admin, voir README',
  UNIQUE KEY `uk_nounous_utilisateur` (`utilisateur_id`),
  KEY `idx_nounous_ville` (`ville`),
  CONSTRAINT `fk_nounous_utilisateur` FOREIGN KEY (`utilisateur_id`)
    REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Enfants : données sensibles (allergies, besoins spécifiques).
-- Toute lecture doit être filtrée par parent_id — voir modifier-enfant.php.
-- --------------------------------------------------------
CREATE TABLE `enfants` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id`         INT NOT NULL,
  `nom`               VARCHAR(50) NOT NULL,
  `prenom`            VARCHAR(50) NOT NULL,
  `age`               TINYINT UNSIGNED NOT NULL,
  `allergie`          VARCHAR(100) DEFAULT NULL,
  `besoin_specifique` VARCHAR(250) DEFAULT NULL,
  KEY `idx_enfants_parent` (`parent_id`),
  CONSTRAINT `fk_enfants_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `parents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- `statut` : jusqu'ici absent, un contrat était forcément déjà signé. Sans
-- lui, aucun vrai flux de proposition/acceptation n'était possible — voir
-- profil-nounou.php (proposer) et dashboard-nounou.php (accepter/refuser).
-- --------------------------------------------------------
CREATE TABLE `contrats_emploi` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `parent_id`      INT NOT NULL,
  `nounou_id`      INT NOT NULL,
  `titre`          VARCHAR(150) NOT NULL,
  `montant`        DECIMAL(10,2) NOT NULL,
  `statut`         ENUM('en_attente', 'acceptee', 'refusee', 'terminee') NOT NULL DEFAULT 'en_attente',
  `date_signature` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_contrats_parent` (`parent_id`),
  KEY `idx_contrats_nounou` (`nounou_id`),
  CONSTRAINT `fk_contrats_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `parents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contrats_nounou` FOREIGN KEY (`nounou_id`)
    REFERENCES `nounous` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Avis : un seul par contrat (pas par parent en général), et seulement une
-- fois le contrat marqué `terminee` — voir dashboard-parent.php. Empêche un
-- avis sans lien réel avec un accompagnement effectif.
-- --------------------------------------------------------
CREATE TABLE `avis` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `contrat_id`  INT NOT NULL,
  `parent_id`   INT NOT NULL,
  `nounou_id`   INT NOT NULL,
  `note`        TINYINT UNSIGNED NOT NULL,
  `commentaire` VARCHAR(500) DEFAULT NULL,
  `cree_le`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_avis_contrat` (`contrat_id`),
  KEY `idx_avis_nounou` (`nounou_id`),
  CONSTRAINT `fk_avis_contrat` FOREIGN KEY (`contrat_id`)
    REFERENCES `contrats_emploi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `parents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_nounou` FOREIGN KEY (`nounou_id`)
    REFERENCES `nounous` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_avis_note` CHECK (`note` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Messagerie : expéditeur et destinataire sont des comptes, pas du texte.
-- --------------------------------------------------------
CREATE TABLE `messages` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `expediteur_id` INT NOT NULL,
  `destinataire_id` INT NOT NULL,
  `contenu`      TEXT NOT NULL,
  `lu`           TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'sert le badge de non lus, faute de notification par e-mail',
  `envoye_le`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_messages_conversation` (`expediteur_id`, `destinataire_id`, `envoye_le`),
  KEY `idx_messages_non_lus` (`destinataire_id`, `lu`),
  CONSTRAINT `fk_messages_expediteur` FOREIGN KEY (`expediteur_id`)
    REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_destinataire` FOREIGN KEY (`destinataire_id`)
    REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Anti-brute-force : la connexion n'avait aucune limite de tentatives.
-- Une ligne par échec ; purgée en continu par tentative_connexion_echouee().
-- --------------------------------------------------------
CREATE TABLE `tentatives_connexion` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(190) NOT NULL,
  `adresse_ip` VARCHAR(45)  NOT NULL,
  `tentee_le`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tentatives_email` (`email`, `tentee_le`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Réinitialisation de mot de passe : aucune récupération n'était possible
-- avant septembre 2026 (limite déjà documentée dans le README).
--
-- Le jeton lui-même n'est jamais stocké, seulement son empreinte SHA-256 —
-- même principe que le mot de passe : une fuite de la base ne doit pas
-- suffire à réutiliser un jeton en circulation.
-- --------------------------------------------------------
CREATE TABLE `reinitialisations_mot_de_passe` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `utilisateur_id`  INT NOT NULL,
  `jeton_hache`     CHAR(64) NOT NULL COMMENT 'sha256() du jeton envoyé, jamais le jeton lui-même',
  `expire_le`       TIMESTAMP NOT NULL,
  `utilise_le`      TIMESTAMP NULL DEFAULT NULL,
  `cree_le`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_reinit_utilisateur` (`utilisateur_id`),
  KEY `idx_reinit_jeton` (`jeton_hache`),
  CONSTRAINT `fk_reinit_utilisateur` FOREIGN KEY (`utilisateur_id`)
    REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
