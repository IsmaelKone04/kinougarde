<?php
/**
 * Amorçage de l'application.
 *
 * Unique point d'entrée du code : chaque page de `public/` commence par
 *
 *     require_once __DIR__ . '/../src/bootstrap.php';
 *
 * et dispose alors de la connexion $bdd, de la session, et des fonctions
 * métier. Les pages n'ont plus à savoir quel fichier contient quoi — et,
 * surtout, ces trois fichiers sont hors de la racine web : même si le serveur
 * cessait d'interpréter le PHP, personne ne pourrait lire les identifiants de
 * la base en appelant leur URL, puisqu'ils n'en ont pas.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
