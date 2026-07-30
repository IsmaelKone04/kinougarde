<?php
/**
 * Connexion à la base de données et réglages généraux.
 *
 * Chargé par `bootstrap.php`, jamais directement : les pages utilisent la
 * variable $bdd qu'il expose. Auparavant, les identifiants MySQL étaient
 * recopiés en dur dans une dizaine de fichiers ; il suffisait d'en oublier un
 * pour publier un mot de passe de production.
 */

declare(strict_types=1);

/** Charge un fichier .env dans $_ENV (format CLE=valeur, # pour un commentaire). */
function charger_env(string $chemin): void
{
    if (!is_readable($chemin)) {
        return;
    }
    foreach (file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne) {
        $ligne = trim($ligne);
        if ($ligne === '' || $ligne[0] === '#' || !str_contains($ligne, '=')) {
            continue;
        }
        [$cle, $valeur] = explode('=', $ligne, 2);
        $_ENV[trim($cle)] = trim($valeur);
    }
}

// Le .env est à la racine du projet, un cran au-dessus de src/ — et donc
// deux crans au-dessus de la racine web : il n'est servable par aucune URL.
charger_env(dirname(__DIR__) . '/.env');

function env(string $cle, string $defaut = ''): string
{
    if (isset($_ENV[$cle]) && $_ENV[$cle] !== '') {
        return $_ENV[$cle];
    }
    $valeur = getenv($cle);
    return ($valeur === false || $valeur === '') ? $defaut : $valeur;
}

const APP_NOM = 'kiNouGarde';

$debug = strtolower(env('APP_DEBUG', 'false')) === 'true';

// En production, une erreur SQL affichée à l'écran révèle la structure de la
// base et parfois le contenu des requêtes. On la journalise sans la montrer.
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    env('DB_HOST', 'localhost'),
    env('DB_NAME', 'kinougarde')
);

try {
    $bdd = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Sans cette option, PDO émule les requêtes préparées : les paramètres
        // sont réinjectés dans la chaîne SQL côté PHP. On veut de vraies
        // requêtes préparées, préparées par MySQL.
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('Connexion base impossible : ' . $e->getMessage());
    http_response_code(500);
    exit($debug ? 'Connexion base impossible : ' . $e->getMessage() : 'Service momentanément indisponible.');
}
