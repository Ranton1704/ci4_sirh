<?php

namespace Tests\Unit\Onboarding;

use App\Models\ChecklistModeleModel;
use App\Models\ItemOnboardingModel;
use App\Services\OnboardingService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use InvalidArgumentException;
use Tests\Support\Onboarding\OnboardingTestTrait;

/**
 * @internal
 */
final class OnboardingServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use OnboardingTestTrait;

    protected $namespace;

    private OnboardingService $service;
    private ChecklistModeleModel $modeles;
    private ItemOnboardingModel $items;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpOnboarding();

        $this->service = new OnboardingService();
        $this->modeles = model(ChecklistModeleModel::class, false);
        $this->items   = model(ItemOnboardingModel::class, false);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testLinkToEmployeCreeLesItemsActifsAvecEcheance(): void
    {
        $it = $this->utilisateurId('it@sirh.test');
        $this->modeles->insert(['titre' => 'Badge', 'delai_jours' => 1, 'ordre' => 1, 'responsable_id' => $it]);
        $this->modeles->insert(['titre' => 'Matériel', 'delai_jours' => 5, 'ordre' => 2]);
        $this->modeles->insert(['titre' => 'Ancien item', 'actif' => false]);

        $resultat = $this->service->linkToEmploye(42, '2026-11-02', $it);

        $this->assertSame(2, $resultat['crees']);
        $items = $this->items->getByEmploye(42);
        $this->assertCount(2, $items);
        $this->assertSame('Badge', $items[0]['titre']);
        $this->assertSame('2026-11-03', $items[0]['date_echeance']);
        $this->assertSame('2026-11-07', $items[1]['date_echeance']);
        $this->assertSame('a_faire', $items[0]['statut']);
        $this->seeInDatabase('historique_onboarding', ['employe_id' => 42, 'action' => 'liaison', 'utilisateur_id' => $it]);
    }

    public function testLinkToEmployeNeDupliquePas(): void
    {
        $this->modeles->insert(['titre' => 'Badge']);
        $this->service->linkToEmploye(7);
        $this->modeles->insert(['titre' => 'Formation sécurité']);

        $resultat = $this->service->linkToEmploye(7);

        $this->assertSame(1, $resultat['crees']);
        $this->assertSame('Formation sécurité', $resultat['items'][0]['titre']);
        $this->assertCount(2, $this->items->getByEmploye(7));
    }

    public function testLinkToEmployeRefuseUnIdentifiantInvalide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->linkToEmploye(0);
    }

    public function testNotifyResponsablesEnvoieAuResponsableEtAuxRH(): void
    {
        $item = $this->service->createItem(['employe_id' => 3, 'titre' => 'Compte applicatif']);
        $this->items->update($item['id'], ['responsable_id' => $this->utilisateurId('it@sirh.test')]);
        $this->emailsSent = [];

        $nb = $this->service->notifyResponsables((int) $item['id']);

        $this->assertSame(2, $nb);
        $this->assertCount(1, $this->emailsSent);
        $this->assertSame(['it@sirh.test', 'rh@sirh.test'], $this->emailsSent[0]['recipients']);
        $this->assertStringContainsString('Compte applicatif', $this->emailsSent[0]['subject']);
        $this->seeInDatabase('historique_onboarding', ['item_id' => $item['id'], 'action' => 'notification']);
    }

    public function testNotifyResponsablesDesactive(): void
    {
        $item = $this->service->createItem(['employe_id' => 3, 'titre' => 'Badge']);
        config('Email')->onboardingNotifications = false;

        $this->assertSame(0, $this->service->notifyResponsables((int) $item['id']));
        $this->assertSame([], $this->emailsSent);
    }

    public function testNotifyResponsablesEchecEnvoiJournalise(): void
    {
        $item = $this->service->createItem(['employe_id' => 3, 'titre' => 'Badge']);
        $this->email->returnValue = false;

        $this->assertSame(0, $this->service->notifyResponsables((int) $item['id']));
        $this->seeInDatabase('historique_onboarding', ['item_id' => $item['id'], 'action' => 'notification_echec']);
    }

    public function testNotifyResponsablesItemInconnu(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->notifyResponsables(999);
    }

    public function testCycleDeVieStatutEtValidation(): void
    {
        $manager = $this->utilisateurId('manager@sirh.test');
        $item    = $this->service->createItem(['employe_id' => 5, 'titre' => 'Inscription paie']);

        $item = $this->service->updateItem((int) $item['id'], ['statut' => 'termine']);
        $this->assertSame('termine', $item['statut']);
        $this->assertNotNull($item['date_realisation']);

        $item = $this->service->validateItem((int) $item['id'], false, 'RIB manquant', $manager);
        $this->assertSame('en_cours', $item['statut']);
        $this->assertNull($item['date_realisation']);
        $this->assertSame('RIB manquant', $item['commentaire']);

        $this->service->updateItem((int) $item['id'], ['statut' => 'termine']);
        $item = $this->service->validateItem((int) $item['id'], true, null, $manager);
        $this->assertSame('valide', $item['statut']);
        $this->assertSame($manager, (int) $item['valide_par']);
        $this->assertNotNull($item['date_validation']);
        $this->seeInDatabase('historique_onboarding', ['item_id' => $item['id'], 'action' => 'rejet']);
        $this->seeInDatabase('historique_onboarding', ['item_id' => $item['id'], 'action' => 'validation']);
    }

    public function testValidationRefuseeSiNonTermine(): void
    {
        $item = $this->service->createItem(['employe_id' => 5, 'titre' => 'Badge']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->validateItem((int) $item['id']);
    }

    public function testUpdateInterditLePassageDirectAValide(): void
    {
        $item = $this->service->createItem(['employe_id' => 5, 'titre' => 'Badge']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->updateItem((int) $item['id'], ['statut' => 'valide']);
    }

    public function testCreateItemRefuseUnStatutInvalide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createItem(['employe_id' => 5, 'titre' => 'Badge', 'statut' => 'inconnu']);
    }

    public function testAssignResponsableNotifie(): void
    {
        $item = $this->service->createItem(['employe_id' => 8, 'titre' => 'Matériel']);
        $it   = $this->utilisateurId('it@sirh.test');

        $item = $this->service->assignResponsable((int) $item['id'], $it);

        $this->assertSame($it, (int) $item['responsable_id']);
        $this->assertContains('it@sirh.test', end($this->emailsSent)['recipients']);
        $this->seeInDatabase('historique_onboarding', ['item_id' => $item['id'], 'action' => 'assignation']);
    }

    public function testAssignResponsableInconnu(): void
    {
        $item = $this->service->createItem(['employe_id' => 8, 'titre' => 'Matériel']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->assignResponsable((int) $item['id'], 999);
    }

    public function testCalculateProgress(): void
    {
        Time::setTestNow('2026-11-10');

        $progression = $this->service->calculateProgress([
            ['statut' => 'valide', 'date_echeance' => '2026-11-01'],
            ['statut' => 'termine', 'date_echeance' => '2026-11-01'],
            ['statut' => 'en_cours', 'date_echeance' => '2026-11-05'],
            ['statut' => 'a_faire', 'date_echeance' => '2026-11-20'],
        ]);

        $this->assertSame(['total' => 4, 'termines' => 2, 'valides' => 1, 'en_retard' => 1, 'pourcentage' => 50], $progression);
        $this->assertSame(0, $this->service->calculateProgress([])['pourcentage']);
    }

    public function testDashboardEtSuivi(): void
    {
        $this->modeles->insert(['titre' => 'Badge']);
        $this->modeles->insert(['titre' => 'Matériel']);
        $this->service->linkToEmploye(1);
        $this->service->linkToEmploye(2);
        foreach ($this->items->getByEmploye(1) as $item) {
            $this->service->updateItem((int) $item['id'], ['statut' => 'termine']);
        }

        $suivi = $this->service->getTracking(1);
        $this->assertCount(1, $suivi);
        $this->assertSame(100, $suivi[0]['progression']['pourcentage']);

        $dashboard = $this->service->getDashboard();
        $this->assertSame(2, $dashboard['nb_employes']);
        $this->assertSame(1, $dashboard['nb_employes_termine']);
        $this->assertSame(4, $dashboard['nb_items']);
        $this->assertSame(['a_faire' => 2, 'en_cours' => 0, 'termine' => 2, 'valide' => 0], $dashboard['items_par_statut']);
        $this->assertSame(50, $dashboard['progression_moyenne']);
        $this->assertNotEmpty($dashboard['historique_recent']);
    }
}
