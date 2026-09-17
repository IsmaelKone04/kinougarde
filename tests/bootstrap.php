<?php
/**
 * Amorçage des tests.
 *
 * Volontairement séparé de src/bootstrap.php : celui-ci charge config.php
 * (connexion à la base) et auth.php (session, en-têtes HTTP), deux choses
 * qu'un test ne doit jamais déclencher en se contentant de démarrer. Les
 * tests ouvrent leur propre connexion vers une base dédiée (voir
 * tests/ConnexionTest.php et TestCaseAvecBase), jamais celle de production.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// e() vient normalement de src/auth.php, mais ce fichier démarre une session
// et envoie des en-têtes HTTP — rien qu'un test ne doit faire. Réimplémentée
// à l'identique ici plutôt que d'appeler auth.php pour un simple échappement.
if (!function_exists('e')) {
    function e(?string $valeur): string
    {
        return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
