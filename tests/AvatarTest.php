<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * avatar_html() est le correctif direct de la plainte initiale sur ce
 * projet : « on ne voit jamais les photos de profil des nounous ». Sans
 * repli sur les initiales, l'absence de photo laissait un espace vide.
 */
final class AvatarTest extends TestCase
{
    public function test_sans_photo_affiche_les_initiales(): void
    {
        $html = avatar_html('Adjoua', 'Konan', null);

        self::assertStringContainsString('AK', $html);
        self::assertStringNotContainsString('<img', $html);
    }

    public function test_avec_photo_affiche_une_image(): void
    {
        $html = avatar_html('Adjoua', 'Konan', 'abc123.png');

        self::assertStringContainsString('<img', $html);
        self::assertStringContainsString('uploads/abc123.png', $html);
    }

    public function test_le_nom_de_fichier_est_echappe(): void
    {
        // basename() neutralise déjà un chemin, mais l'échappement HTML doit
        // rester systématique — ceinture et bretelles sur une valeur qui finit
        // dans un attribut src.
        $html = avatar_html('Test', 'Xss', '"><script>alert(1)</script>.png');

        self::assertStringNotContainsString('<script>', $html);
    }

    public function test_deux_noms_differents_donnent_des_couleurs_differentes(): void
    {
        $premier = avatar_html('Moussa', 'Diomandé', null);
        $second  = avatar_html('Fatou', 'Ouattara', null);

        self::assertNotSame($premier, $second);
    }

    public function test_la_meme_personne_donne_toujours_la_meme_couleur(): void
    {
        // La couleur doit être stable pour une même personne d'un rendu à
        // l'autre, sinon son avatar change d'apparence à chaque page.
        $premier = avatar_html('Adjoua', 'Konan', null);
        $second  = avatar_html('Adjoua', 'Konan', null);

        self::assertSame($premier, $second);
    }
}
