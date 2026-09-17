<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * trop_long() borne côté serveur ce que les formulaires ne faisaient
 * qu'espérer côté navigateur (voir inscription-nounou.php, mon-enfant.php…).
 * Un POST direct pouvait jusqu'ici envoyer une valeur plus longue que la
 * colonne VARCHAR visée, et l'insertion échouait silencieusement.
 */
final class LongueurTest extends TestCase
{
    public function test_une_valeur_plus_courte_passe(): void
    {
        self::assertFalse(trop_long('Abidjan', 50));
    }

    public function test_une_valeur_de_longueur_exacte_passe(): void
    {
        self::assertFalse(trop_long(str_repeat('a', 50), 50));
    }

    public function test_une_valeur_plus_longue_est_rejetee(): void
    {
        self::assertTrue(trop_long(str_repeat('a', 51), 50));
    }

    public function test_les_caracteres_multioctets_comptent_une_fois(): void
    {
        // strlen() compterait les octets UTF-8, pas les caractères : "é" ferait
        // 2 de trop. mb_strlen() est indispensable pour un nom comme "Awa Koné".
        $nom = str_repeat('é', 50);

        self::assertSame(50, mb_strlen($nom));
        self::assertFalse(trop_long($nom, 50));
    }

    public function test_chaine_vide_ne_depasse_jamais(): void
    {
        self::assertFalse(trop_long('', 0));
    }
}
