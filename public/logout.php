<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$_SESSION = [];

// Supprime aussi le cookie de session côté navigateur : sans cela, l'identifiant
// de session reste valide et peut être rejoué.
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

// L'ancienne version renvoyait vers login_parents.php, un fichier qui n'a
// jamais existé : se déconnecter menait à une page 404.
header('Location: login.php');
exit;
