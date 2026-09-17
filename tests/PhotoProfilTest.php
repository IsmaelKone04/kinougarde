<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * enregistrer_photo_profil() valide les fichiers envoyés via inscription-nounou.php.
 *
 * Un vrai fichier "réussi" ne peut être testé qu'au travers d'une requête
 * HTTP réelle : is_uploaded_file() renvoie toujours faux hors de ce contexte,
 * par conception de PHP (voir la faille historique qu'il neutralise : un
 * $_FILES fabriqué à la main pointant vers un fichier arbitraire du serveur).
 * Ce test vérifie donc les cas d'erreur, et au passage que ce garde-fou est
 * bien en place — ce qui est justement le plus important à vérifier.
 */
final class PhotoProfilTest extends TestCase
{
    public function test_aucun_fichier_renvoie_null(): void
    {
        self::assertNull(enregistrer_photo_profil([]));
        self::assertNull(enregistrer_photo_profil(['error' => UPLOAD_ERR_NO_FILE]));
    }

    public function test_une_erreur_de_televersement_leve_une_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('téléversement de la photo a échoué');

        enregistrer_photo_profil(['error' => UPLOAD_ERR_INI_SIZE, 'tmp_name' => '', 'size' => 0]);
    }

    public function test_un_fichier_non_televerse_par_http_est_refuse(): void
    {
        // is_uploaded_file() renvoie systématiquement faux pour un fichier qui
        // n'est pas passé par le mécanisme d'upload de PHP — exactement le cas
        // d'un $_FILES fabriqué de toutes pièces pour lire un fichier arbitraire
        // du serveur. C'est ce garde-fou qui est vérifié ici.
        $fichierLocal = tempnam(sys_get_temp_dir(), 'kg_test_');
        file_put_contents($fichierLocal, 'contenu quelconque');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Fichier de photo invalide');

            enregistrer_photo_profil([
                'error'    => UPLOAD_ERR_OK,
                'tmp_name' => $fichierLocal,
                'size'     => filesize($fichierLocal),
            ]);
        } finally {
            @unlink($fichierLocal);
        }
    }
}
