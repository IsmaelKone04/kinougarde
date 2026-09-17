<?php
declare(strict_types=1);

namespace Tests;

/**
 * Cycle de vie d'un contrat : proposé par un parent, accepté ou refusé par
 * la nounou concernée, puis marqué terminé par le parent — qui peut alors
 * laisser un avis. Avant septembre 2026, `contrats_emploi` n'avait pas de
 * statut : une ligne était forcément déjà signée, sans aucun moyen pour
 * l'application d'en créer une.
 */
final class ContratsEtAvisTest extends TestCaseAvecBase
{
    public function test_une_proposition_est_en_attente(): void
    {
        [, $parentId] = $this->creerParent();
        [, $nounouId] = $this->creerNounou();

        $id = proposer_contrat($this->bdd, $parentId, $nounouId, 'Garde du soir', 50000);

        $contrats = contrats_parent($this->bdd, $parentId);
        self::assertCount(1, $contrats);
        self::assertSame('en_attente', $contrats[0]['statut']);
        self::assertSame($id, (int) $contrats[0]['id']);
    }

    public function test_une_nounou_ne_peut_pas_repondre_a_la_proposition_dune_autre(): void
    {
        [, $parentId]  = $this->creerParent();
        [, $nounouA]   = $this->creerNounou('nounouA@test.local');
        [, $nounouB]   = $this->creerNounou('nounouB@test.local');

        $id = proposer_contrat($this->bdd, $parentId, $nounouA, 'Garde du soir', 50000);

        $resultat = repondre_contrat($this->bdd, $id, $nounouB, true);

        self::assertFalse($resultat, "une nounou a pu répondre à la proposition d'une autre");

        $contrats = contrats_parent($this->bdd, $parentId);
        self::assertSame('en_attente', $contrats[0]['statut'], 'le statut ne doit pas avoir changé');
    }

    public function test_accepter_puis_refuser_le_meme_contrat_est_impossible(): void
    {
        [, $parentId] = $this->creerParent();
        [, $nounouId] = $this->creerNounou();
        $id = proposer_contrat($this->bdd, $parentId, $nounouId, 'Garde du soir', 50000);

        self::assertTrue(repondre_contrat($this->bdd, $id, $nounouId, true));
        // Une fois accepté, un second appel (même pour refuser) ne doit rien
        // changer : le WHERE filtre sur statut = 'en_attente'.
        self::assertFalse(repondre_contrat($this->bdd, $id, $nounouId, false));

        $contrats = contrats_parent($this->bdd, $parentId);
        self::assertSame('acceptee', $contrats[0]['statut']);
    }

    public function test_on_ne_peut_pas_terminer_un_contrat_encore_en_attente(): void
    {
        [, $parentId] = $this->creerParent();
        [, $nounouId] = $this->creerNounou();
        $id = proposer_contrat($this->bdd, $parentId, $nounouId, 'Garde du soir', 50000);

        self::assertFalse(marquer_contrat_termine($this->bdd, $id, $parentId));
    }

    public function test_on_ne_peut_pas_noter_un_contrat_qui_nest_pas_termine(): void
    {
        [, $parentId] = $this->creerParent();
        [, $nounouId] = $this->creerNounou();
        $id = proposer_contrat($this->bdd, $parentId, $nounouId, 'Garde du soir', 50000);
        repondre_contrat($this->bdd, $id, $nounouId, true);

        self::assertNull(contrat_notable($this->bdd, $id, $parentId));
    }

    public function test_le_cycle_complet_permet_de_laisser_un_avis(): void
    {
        [, $parentId] = $this->creerParent();
        [, $nounouId] = $this->creerNounou();
        $id = proposer_contrat($this->bdd, $parentId, $nounouId, 'Garde du soir', 50000);

        repondre_contrat($this->bdd, $id, $nounouId, true);
        self::assertTrue(marquer_contrat_termine($this->bdd, $id, $parentId));

        $notable = contrat_notable($this->bdd, $id, $parentId);
        self::assertSame($nounouId, $notable);

        laisser_avis($this->bdd, $id, $parentId, $nounouId, 5, 'Parfait');

        $avis = avis_nounou($this->bdd, $nounouId);
        self::assertCount(1, $avis);
        self::assertSame(5, (int) $avis[0]['note']);
    }

    public function test_un_contrat_deja_note_ne_peut_pas_letre_deux_fois(): void
    {
        [, $parentId] = $this->creerParent();
        [, $nounouId] = $this->creerNounou();
        $id = proposer_contrat($this->bdd, $parentId, $nounouId, 'Garde du soir', 50000);
        repondre_contrat($this->bdd, $id, $nounouId, true);
        marquer_contrat_termine($this->bdd, $id, $parentId);
        laisser_avis($this->bdd, $id, $parentId, $nounouId, 4, null);

        self::assertNull(contrat_notable($this->bdd, $id, $parentId));
    }

    public function test_un_autre_parent_ne_peut_pas_terminer_le_contrat_dun_autre(): void
    {
        [, $parentA] = $this->creerParent('parentA@test.local');
        [, $parentB] = $this->creerParent('parentB@test.local');
        [, $nounouId] = $this->creerNounou();
        $id = proposer_contrat($this->bdd, $parentA, $nounouId, 'Garde du soir', 50000);
        repondre_contrat($this->bdd, $id, $nounouId, true);

        self::assertFalse(marquer_contrat_termine($this->bdd, $id, $parentB));
    }
}
