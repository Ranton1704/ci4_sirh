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
    // À remplir par la Personne 5
    // Exemple : $routes->get('checklist', 'ItemOnboarding::index');
    // Exemple : $routes->post('checklist', 'ItemOnboarding::create');
});

// Routes de référence (communes)
$routes->group('api', ['namespace' => 'App\Controllers'], function ($routes) {
    $routes->get('utilisateurs', 'Utilisateur::index');
    $routes->get('departements', 'Departement::index');
    $routes->get('postes', 'Poste::index');
});
