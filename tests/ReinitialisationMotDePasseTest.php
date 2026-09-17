<?php
declare(strict_types=1);

namespace Tests;

final class ReinitialisationMotDePasseTest extends TestCaseAvecBase
{
    public function test_un_jeton_valide_renvoie_le_bon_utilisateur(): void
    {
        [$utilisateur_id] = $this->creerParent();

        $jeton = demarrer_reinitialisation($this->bdd, $utilisateur_id);

        self::assertSame($utilisateur_id, verifier_jeton_reinitialisation($this->bdd, $jeton));
    }

    public function test_un_jeton_inconnu_est_refuse(): void
    {
        self::assertNull(verifier_jeton_reinitialisation($this->bdd, 'jeton-invente-au-hasard'));
    }

    public function test_le_jeton_stocke_en_base_nest_jamais_le_jeton_en_clair(): void
    {
        [$utilisateur_id] = $this->creerParent();
        $jeton = demarrer_reinitialisation($this->bdd, $utilisateur_id);

        $stocke = $this->bdd->query('SELECT jeton_hache FROM reinitialisations_mot_de_passe')->fetchColumn();

        self::assertNotSame($jeton, $stocke, 'le jeton en clair ne doit jamais être stocké tel quel');
        self::assertSame(hash('sha256', $jeton), $stocke);
    }

    public function test_un_jeton_ne_peut_servir_quune_fois(): void
    {
        [$utilisateur_id] = $this->creerParent();
        $jeton = demarrer_reinitialisation($this->bdd, $utilisateur_id);

        appliquer_reinitialisation($this->bdd, $utilisateur_id, 'NouveauMdp2026!');

        self::assertNull(
            verifier_jeton_reinitialisation($this->bdd, $jeton),
            'le jeton reste valide après avoir déjà servi'
        );
    }

    public function test_le_mot_de_passe_est_effectivement_change(): void
    {
        [$utilisateur_id] = $this->creerParent();
        $jeton = demarrer_reinitialisation($this->bdd, $utilisateur_id);

        appliquer_reinitialisation($this->bdd, $utilisateur_id, 'NouveauMdp2026!');

        $hash = $this->bdd->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
        $hash->execute([$utilisateur_id]);

        self::assertTrue(password_verify('NouveauMdp2026!', $hash->fetchColumn()));
    }

    public function test_demander_un_nouveau_jeton_invalide_lancien(): void
    {
        [$utilisateur_id] = $this->creerParent();

        $premier = demarrer_reinitialisation($this->bdd, $utilisateur_id);
        $second  = demarrer_reinitialisation($this->bdd, $utilisateur_id);

        self::assertNull(verifier_jeton_reinitialisation($this->bdd, $premier));
        self::assertSame($utilisateur_id, verifier_jeton_reinitialisation($this->bdd, $second));
    }
}
