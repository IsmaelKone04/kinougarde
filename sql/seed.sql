-- kiNouGarde — jeu de données de démonstration
--
-- ENTIÈREMENT FICTIF. Le dump d'origine contenait de vrais comptes de test :
-- adresses e-mail personnelles, numéros de téléphone réels, et des mots de
-- passe stockés en clair. Rien de tout cela n'est repris ici.
--
-- Les cinq comptes partagent le mot de passe : Demo1234!
-- Les empreintes sont produites par password_hash(..., PASSWORD_BCRYPT, cost 12).

SET NAMES utf8mb4;

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `telephone`, `email`, `mot_de_passe`, `role`) VALUES
(1, 'Traoré',  'Aïcha',   '+225 00 00 00 01', 'aicha.parent@example.com',    '$2y$12$J/eVNzPKtLnDfZpQjEWrKOz1EzMh2e2M2.Uwf9A10RIVdSZluduXK', 'parent'),
(2, 'Bamba',   'Serge',   '+225 00 00 00 02', 'serge.parent@example.com',    '$2y$12$1hUNVg.P3a9D0GRlxR9cNeE1D./VsIXCaqunDGn78DYlEk86iGbiS', 'parent'),
(3, 'Konan',   'Adjoua',  '+225 00 00 00 03', 'adjoua.nounou@example.com',   '$2y$12$2Eciehcqee7DjWtLi5bQ8emZOG0XfAuNlfDWldNg21xxSHPz.0bBu', 'nounou'),
(4, 'Ouattara','Fatou',   '+225 00 00 00 04', 'fatou.nounou@example.com',    '$2y$12$TLq8oSavOrb76PBiP2sUP.tu2DwKWvXGxzySUgLOihKUuEKUsK3yy', 'nounou'),
(5, 'Diomandé','Moussa',  '+225 00 00 00 05', 'moussa.nounou@example.com',   '$2y$12$/KahIoSvoG5YSoj9cVVuNuL2zfdOAM4caMeoXHVOkdKVph5QZtk9C', 'nounou');

INSERT INTO `parents` (`id`, `utilisateur_id`, `ville`, `profession`, `nb_enfants`) VALUES
(1, 1, 'Cocody',     'Comptable',  2),
(2, 2, 'Port-Bouët', 'Enseignant', 1);

INSERT INTO `nounous` (`id`, `utilisateur_id`, `sexe`, `ville`, `type_service`, `horaires`, `montant`, `paiement`, `photo_profil`) VALUES
(1, 3, 'femme', 'Cocody',     'Garde d''enfants',            'Lun-Ven, 7h-18h',  120000.00, 'mois',  NULL),
(2, 4, 'femme', 'Yopougon',   'Garde d''enfants et ménage',  'Lun-Sam, 8h-17h',   90000.00, 'mois',  NULL),
(3, 5, 'homme', 'Port-Bouët', 'Aide aux devoirs',            'Mer et Sam, 14h-18h', 3000.00, 'heure', NULL);

INSERT INTO `enfants` (`id`, `parent_id`, `nom`, `prenom`, `age`, `allergie`, `besoin_specifique`) VALUES
(1, 1, 'Traoré', 'Awa',    4, 'Arachide',  'Sieste obligatoire après le déjeuner'),
(2, 1, 'Traoré', 'Yao',    7, NULL,        'Accompagnement aux devoirs de lecture'),
(3, 2, 'Bamba',  'Mariam', 2, 'Lait de vache', 'Biberon toutes les trois heures');

INSERT INTO `contrats_emploi` (`id`, `parent_id`, `nounou_id`, `titre`, `montant`) VALUES
(1, 1, 1, 'Garde à domicile — année scolaire', 120000.00),
(2, 2, 3, 'Aide aux devoirs — trimestre',       48000.00);

INSERT INTO `messages` (`expediteur_id`, `destinataire_id`, `contenu`) VALUES
(1, 3, 'Bonjour, seriez-vous disponible dès lundi prochain ?'),
(3, 1, 'Bonjour, oui tout à fait. Souhaitez-vous que nous en parlions de vive voix ?'),
(2, 5, 'Bonsoir, ma fille a besoin d''un accompagnement en lecture le mercredi.');
