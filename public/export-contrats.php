<?php
/**
 * Export CSV des contrats de l'utilisateur connecté — parent ou nounou.
 * Comme partout ailleurs, la liste vient de contrats_parent()/contrats_nounou(),
 * déjà filtrées par l'identifiant de session : impossible d'exporter les
 * contrats de quelqu'un d'autre en manipulant l'URL.
 */
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$moi = exiger_connexion();

if ($moi['role'] === 'parent') {
    $parent   = parent_par_utilisateur($bdd, (int) $moi['id']);
    $contrats = $parent !== null ? contrats_parent($bdd, (int) $parent['id']) : [];
    $colonnes = ['titre', 'nounou_prenom', 'nounou_nom', 'montant', 'statut', 'date_signature'];
    $entetes  = ['Titre', 'Nounou (prénom)', 'Nounou (nom)', 'Montant (FCFA)', 'Statut', 'Date de signature'];
} else {
    $nounou   = nounou_par_utilisateur($bdd, (int) $moi['id']);
    $contrats = $nounou !== null ? contrats_nounou($bdd, (int) $nounou['id']) : [];
    $colonnes = ['titre', 'parent_prenom', 'parent_nom', 'montant', 'statut', 'date_signature'];
    $entetes  = ['Titre', 'Parent (prénom)', 'Parent (nom)', 'Montant (FCFA)', 'Statut', 'Date de signature'];
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="mes-contrats-' . date('Y-m-d') . '.csv"');
header('X-Content-Type-Options: nosniff');

$sortie = fopen('php://output', 'w');
// BOM UTF-8 : sans lui, Excel (le tableur le plus répandu ici) affiche les
// accents corrompus à l'ouverture du fichier.
fwrite($sortie, "\xEF\xBB\xBF");
fputcsv($sortie, $entetes, ';');

foreach ($contrats as $c) {
    fputcsv($sortie, array_map(static fn ($col) => (string) ($c[$col] ?? ''), $colonnes), ';');
}
fclose($sortie);
exit;
