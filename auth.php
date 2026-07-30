<?php
/**
 * Session, contrôle d'accès et échappement.
 *
 * L'ancienne version stockait tantôt $_SESSION['email'], tantôt ['id'],
 * tantôt ['user_id'] selon les pages — si bien que la messagerie ne
 * retrouvait jamais l'utilisateur connecté. Une seule forme désormais :
 *   $_SESSION['utilisateur'] = ['id' => int, 'role' => 'parent'|'nounou', 'email' => string]
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,                                  // inaccessible au JavaScript
        'samesite' => 'Lax',                                 // limite les requêtes inter-sites
        'secure'   => !empty($_SERVER['HTTPS']),             // HTTPS uniquement quand disponible
    ]);
    session_start();
}

/**
 * Échappe une valeur avant affichage HTML.
 *
 * À appeler sur TOUTE donnée venant de la base ou d'un formulaire : sans elle,
 * un message contenant <script> s'exécute chez celui qui le lit.
 */
function e(?string $valeur): string
{
    return htmlspecialchars($valeur ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function utilisateur_connecte(): ?array
{
    return $_SESSION['utilisateur'] ?? null;
}

function connecter(int $id, string $role, string $email): void
{
    // Empêche la fixation de session : l'identifiant de session change au
    // moment où l'utilisateur gagne des droits.
    session_regenerate_id(true);
    $_SESSION['utilisateur'] = ['id' => $id, 'role' => $role, 'email' => $email];
}

/** Interrompt la page si personne n'est connecté (ou si le rôle ne convient pas). */
function exiger_connexion(?string $role = null): array
{
    $u = utilisateur_connecte();
    if ($u === null) {
        header('Location: login.php');
        exit;
    }
    if ($role !== null && $u['role'] !== $role) {
        http_response_code(403);
        exit('Accès refusé.');
    }
    return $u;
}

/**
 * Jeton anti-CSRF.
 *
 * Sans lui, un formulaire hébergé sur un autre site peut poster vers ce site
 * en réutilisant le cookie de session du visiteur : ajout d'enfant, envoi de
 * message ou modification de fiche à son insu.
 */
function jeton_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function champ_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(jeton_csrf()) . '">';
}

function verifier_csrf(): void
{
    $recu = $_POST['csrf'] ?? '';
    if (!is_string($recu) || !hash_equals($_SESSION['csrf'] ?? '', $recu)) {
        http_response_code(400);
        exit('Requête invalide (jeton de sécurité absent ou expiré).');
    }
}
