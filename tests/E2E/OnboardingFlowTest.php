<?php

namespace Tests\E2E;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Onboarding\OnboardingTestTrait;

/**
 * Parcours complet du module Onboarding via l'API REST (P5-025)
 *
 * @internal
 */
final class OnboardingFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use OnboardingTestTrait;

    protected $namespace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpOnboarding();
    }

    public function testParcoursOnboardingComplet(): void
    {
        $it      = $this->utilisateurId('it@sirh.test');
        $manager = $this->utilisateurId('manager@sirh.test');

        // 1. Construire le modèle de checklist
        $modeles = [['Badge d\'accès', 'acces'], ['Compte applicatif', 'informatique'], ['Inscription paie', 'administratif']];
        foreach ($modeles as $i => [$titre, $categorie]) {
            $modele = $this->api('post', 'api/onboarding/checklist', ['titre' => $titre, 'categorie' => $categorie, 'delai_jours' => $i + 1, 'ordre' => $i], 201, $manager);
            $this->assertSame($titre, $modele['titre']);
        }
        $this->assertCount(3, $this->api('get', 'api/onboarding/checklist'));

        // 2. Lier la checklist au nouvel employé #100 + un item spécifique
        $lien = $this->api('post', 'api/onboarding/employes/100/checklist', ['date_debut' => '2026-11-02'], 201, $manager);
        $this->assertSame(3, $lien['crees']);
        $this->api('post', 'api/onboarding/items', ['employe_id' => 100, 'titre' => 'Présentation équipe', 'responsable_id' => $manager], 201, $manager);

        $liste = $this->api('get', 'api/onboarding/employes/100/items');
        $this->assertCount(4, $liste['items']);
        $this->assertSame(0, $liste['progression']['pourcentage']);
        $this->assertSame('2026-11-03', $this->trouver($liste['items'], 'Badge d\'accès')['date_echeance']);

        // 3. Assigner un responsable (notification email)
        $compte           = $this->trouver($liste['items'], 'Compte applicatif');
        $this->emailsSent = [];
        $assigne          = $this->api('put', "api/onboarding/items/{$compte['id']}/responsable", ['responsable_id' => $it]);
        $this->assertSame($it, (int) $assigne['responsable_id']);
        $this->assertCount(1, $this->emailsSent);
        $this->assertContains('it@sirh.test', $this->emailsSent[0]['recipients']);

        // 4. Réaliser les items puis les valider
        foreach ($liste['items'] as $item) {
            $this->assertSame('termine', $this->api('put', "api/onboarding/items/{$item['id']}", ['statut' => 'termine'])['statut']);
        }
        $this->assertCount(4, $this->api('get', 'api/onboarding/items?statut=termine'));

        $paie   = $this->trouver($liste['items'], 'Inscription paie');
        $rejete = $this->api('post', "api/onboarding/items/{$paie['id']}/valider", ['approuve' => false, 'commentaire' => 'RIB manquant'], 200, $manager);
        $this->assertSame('en_cours', $rejete['statut']);
        $this->assertSame(75, $this->api('get', 'api/onboarding/employes/100/suivi')['progression']['pourcentage']);

        $this->api('put', "api/onboarding/items/{$paie['id']}", ['statut' => 'termine']);
        foreach ($liste['items'] as $item) {
            $valide = $this->api('post', "api/onboarding/items/{$item['id']}/valider", ['approuve' => true], 200, $manager);
            $this->assertSame('valide', $valide['statut']);
            $this->assertSame($manager, (int) $valide['valide_par']);
        }

        // 5. Suivi, tableau de bord et historique
        $suivi = $this->api('get', 'api/onboarding/suivi');
        $this->assertSame(100, $suivi[0]['progression']['pourcentage']);
        $this->assertSame(4, $suivi[0]['progression']['valides']);

        $dashboard = $this->api('get', 'api/onboarding/dashboard');
        $this->assertSame(1, $dashboard['nb_employes']);
        $this->assertSame(1, $dashboard['nb_employes_termine']);
        $this->assertSame(4, $dashboard['items_par_statut']['valide']);
        $this->assertSame(100, $dashboard['progression_moyenne']);

        $actions = array_column($this->api('get', 'api/onboarding/historique?employe_id=100'), 'action');
        foreach (['liaison', 'creation', 'assignation', 'notification', 'modification', 'rejet', 'validation'] as $action) {
            $this->assertContains($action, $actions);
        }
    }

    public function testErreursDeValidationEtRessourcesInconnues(): void
    {
        $this->api('post', 'api/onboarding/items', ['titre' => 'Sans employé'], 400);
        $this->api('post', 'api/onboarding/checklist', ['description' => 'Sans titre'], 400);
        $this->api('put', 'api/onboarding/items/999', ['statut' => 'termine'], 404);
        $this->api('get', 'api/onboarding/items/999', [], 404);
        $this->api('post', 'api/onboarding/items/999/valider', ['approuve' => true], 404);

        $item = $this->api('post', 'api/onboarding/items', ['employe_id' => 1, 'titre' => 'Badge'], 201);
        $this->api('post', "api/onboarding/items/{$item['id']}/valider", ['approuve' => true], 400);
        $this->api('put', "api/onboarding/items/{$item['id']}", ['statut' => 'inconnu'], 400);
        $this->api('put', "api/onboarding/items/{$item['id']}/responsable", ['responsable_id' => 999], 400);
    }

    public function testModificationEtSuppressionDuModele(): void
    {
        $modele = $this->api('post', 'api/onboarding/checklist', ['titre' => 'Badge'], 201);

        $modifie = $this->api('put', "api/onboarding/checklist/{$modele['id']}", ['actif' => false, 'titre' => 'Badge v2']);
        $this->assertSame('Badge v2', $modifie['titre']);
        $this->assertSame([], $this->api('get', 'api/onboarding/checklist?actifs=1'));

        $this->api('delete', "api/onboarding/checklist/{$modele['id']}");
        $this->dontSeeInDatabase('checklist_modele', ['id' => $modele['id']]);
    }

    /**
     * Appelle l'API en JSON, vérifie le code HTTP et retourne le corps décodé
     */
    private function api(string $method, string $uri, array $body = [], int $status = 200, ?int $utilisateurId = null): array
    {
        $requete = $this->withBodyFormat('json');
        if ($utilisateurId !== null) {
            $requete = $requete->withHeaders(['X-Utilisateur-Id' => (string) $utilisateurId]);
        }

        /** @var TestResponse $response */
        $response = $method === 'get' ? $requete->get($uri) : $requete->{$method}($uri, $body);
        $response->assertStatus($status);

        return json_decode((string) $response->getJSON(), true) ?? [];
    }

    private function trouver(array $items, string $titre): array
    {
        foreach ($items as $item) {
            if ($item['titre'] === $titre) {
                return $item;
            }
        }
        $this->fail("Item « {$titre} » introuvable");
    }
}
