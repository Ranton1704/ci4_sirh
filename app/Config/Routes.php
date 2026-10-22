<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Route principale
$routes->get('/', 'Home::index');

// ============================================================================
// API REST - Routes par module
// ============================================================================

// Module Recrutement (Personne 2)
$routes->group('api', ['namespace' => 'App\Controllers\Recrutement'], function ($routes) {
    // À remplir par la Personne 2
    // Exemple : $routes->get('offres', 'OffreEmploi::index');
    // Exemple : $routes->post('offres', 'OffreEmploi::create');
});

// Module Négociation (Personne 3)
$routes->group('api', ['namespace' => 'App\Controllers\Negociation'], function ($routes) {
    // À remplir par la Personne 3
    // Exemple : $routes->get('propositions', 'PropositionEmbauche::index');
    // Exemple : $routes->post('propositions', 'PropositionEmbauche::create');
});

// Module Salarié (Personne 4)
$routes->group('api', ['namespace' => 'App\Controllers\Salarie'], function ($routes) {
    // À remplir par la Personne 4
    // Exemple : $routes->get('employes', 'Employe::index');
    // Exemple : $routes->post('employes', 'Employe::create');
});

// Module Onboarding (Personne 5)
$routes->group('api', ['namespace' => 'App\Controllers\Onboarding'], function ($routes) {
    // Modèle de checklist
    $routes->get('onboarding/checklist', 'ChecklistModele::index');
    $routes->post('onboarding/checklist', 'ChecklistModele::store');
    $routes->put('onboarding/checklist/(:num)', 'ChecklistModele::update/$1');
    $routes->delete('onboarding/checklist/(:num)', 'ChecklistModele::delete/$1');

    // Items de checklist par employé
    $routes->get('onboarding/employes/(:num)/items', 'ItemOnboarding::index/$1');
    $routes->get('onboarding/items', 'ItemOnboarding::index');
    $routes->post('onboarding/items', 'ItemOnboarding::store');
    $routes->get('onboarding/items/(:num)', 'ItemOnboarding::show/$1');
    $routes->match(['put', 'patch'], 'onboarding/items/(:num)', 'ItemOnboarding::update/$1');
    $routes->delete('onboarding/items/(:num)', 'ItemOnboarding::delete/$1');
    $routes->post('onboarding/items/(:num)/valider', 'ItemOnboarding::valider/$1');
    $routes->put('onboarding/items/(:num)/responsable', 'ItemOnboarding::assign/$1');
    $routes->post('onboarding/items/(:num)/notifier', 'ItemOnboarding::notify/$1');

    // Liaison, suivi, tableau de bord, historique
    $routes->post('onboarding/employes/(:num)/checklist', 'Onboarding::link/$1');
    $routes->get('onboarding/employes/(:num)/suivi', 'Onboarding::tracking/$1');
    $routes->get('onboarding/suivi', 'Onboarding::tracking');
    $routes->get('onboarding/dashboard', 'Onboarding::dashboard');
    $routes->get('onboarding/historique', 'Onboarding::history');
});

// Routes de référence (communes)
$routes->group('api', ['namespace' => 'App\Controllers'], function ($routes) {
    $routes->get('utilisateurs', 'Utilisateur::index');
    $routes->get('departements', 'Departement::index');
    $routes->get('postes', 'Poste::index');
});
