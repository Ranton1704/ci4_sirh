# Module Onboarding (Personne 5)

Gère la checklist d'intégration des nouveaux salariés : modèle de checklist, liaison à un employé, suivi de la progression, validation des items, assignation des responsables, notifications email et historique des actions.

## Architecture

| Couche | Fichiers |
| --- | --- |
| Migration | `app/Database/Migrations/2026-10-22-000001_CreateOnboardingTables.php` |
| Modèles | `app/Models/ChecklistModeleModel.php`, `ItemOnboardingModel.php`, `HistoriqueOnboardingModel.php` |
| Service | `app/Services/OnboardingService.php` (accessible via `service('onboarding')`) |
| Contrôleurs | `app/Controllers/Onboarding/ItemOnboarding.php`, `ChecklistModele.php`, `Onboarding.php` |
| Routes | `app/Config/Routes.php` (groupe `api`, namespace `App\Controllers\Onboarding`) |
| Email | `app/Config/Email.php`, gabarit `app/Views/emails/onboarding_notification.php` |
| Frontend | `frontend/src/views/onboarding/*`, `frontend/src/components/onboarding/*` |
| Tests | `tests/unit/Onboarding/OnboardingServiceTest.php`, `tests/E2E/OnboardingFlowTest.php`, `frontend/tests/onboarding.spec.js` |

### Tables

- **checklist_modele** : items types (`titre`, `description`, `categorie`, `responsable_id`, `delai_jours`, `obligatoire`, `ordre`, `actif`).
- **item_onboarding** : items liés à un employé (`employe_id`, `modele_id`, `titre`, `categorie`, `responsable_id`, `statut`, `date_echeance`, `date_realisation`, `date_validation`, `valide_par`, `commentaire`).
- **historique_onboarding** : journal (`employe_id`, `item_id`, `action`, `details`, `utilisateur_id`, `date_action`).

`employe_id` fait référence au module Salarié (Personne 4) et `responsable_id` / `valide_par` / `utilisateur_id` à la table `utilisateur` du socle. Aucune clé étrangère n'est posée vers ces tables, car elles appartiennent à d'autres modules.

### Statuts d'un item

`a_faire` → `en_cours` → `termine` → `valide`

- Passer à `termine` renseigne `date_realisation`.
- Seul un item `termine` peut être validé (`valide`, avec `date_validation` et `valide_par`) ou rejeté (retour à `en_cours`).
- Le statut `valide` ne peut pas être positionné avec `PUT /items/{id}` : il faut passer par `/valider`.

**Progression** = (items `termine` + `valide`) / total × 100, arrondi. Un item est **en retard** s'il n'est pas terminé et que sa `date_echeance` est dépassée.

## Endpoints

Base : `/api/onboarding`. Corps et réponses en JSON. L'utilisateur à l'origine d'une action, enregistré dans l'historique, est transmis par l'en-tête `X-Utilisateur-Id` (ou par le champ `utilisateur_id`).

Erreurs : `400` (validation, avec `{"messages": {...}}`) et `404` (ressource introuvable).

### Checklist type

| Méthode | URL | Description |
| --- | --- | --- |
| GET | `/checklist?actifs=1` | Liste des items du modèle (triés par `ordre`) |
| POST | `/checklist` | Créer : `titre`* , `description`, `categorie`, `responsable_id`, `delai_jours` (défaut 7), `obligatoire`, `ordre` |
| PUT | `/checklist/{id}` | Modifier (mêmes champs + `actif`) |
| DELETE | `/checklist/{id}` | Supprimer (les items déjà liés aux employés sont conservés) |

### Liaison et items par employé — `ItemOnboarding`

| Méthode | URL | Description |
| --- | --- | --- |
| POST | `/employes/{employeId}/checklist` | `linkToEmploye` : crée un item pour chaque élément actif du modèle (sans doublon). Corps optionnel `{"date_debut": "2026-11-02"}`. Échéance = date_debut + `delai_jours`. Notifie les responsables. |
| GET | `/employes/{employeId}/items` | `index($employeId)` : `{employe_id, items, progression}` |
| GET | `/items?statut=&employe_id=&responsable_id=` | Recherche d'items |
| GET | `/items/{id}` | Détail |
| POST | `/items` | `store()` : `employe_id`*, `titre`*, `description`, `categorie`, `responsable_id`, `statut`, `date_echeance` (Y-m-d), `obligatoire` |
| PUT/PATCH | `/items/{id}` | `update($id)` : mêmes champs sauf `employe_id` ; `statut` ∈ `a_faire`, `en_cours`, `termine` |
| DELETE | `/items/{id}` | Supprimer |
| POST | `/items/{id}/valider` | `{"approuve": true|false, "commentaire": "..."}` |
| PUT | `/items/{id}/responsable` | `{"responsable_id": 3}` : assigne et notifie |
| POST | `/items/{id}/notifier` | `notifyResponsables` : renvoie `{"notifies": n}` |

### Suivi, tableau de bord, historique

| Méthode | URL | Description |
| --- | --- | --- |
| GET | `/suivi` | `[{employe_id, progression, items}]` pour chaque employé |
| GET | `/employes/{employeId}/suivi` | Suivi d'un employé |
| GET | `/dashboard` | `nb_employes`, `nb_employes_termine`, `nb_items`, `items_par_statut`, `items_en_retard`, `progression_moyenne`, `employes[]`, `historique_recent[]` |
| GET | `/historique?employe_id=&item_id=&action=&limit=100` | Journal (du plus récent au plus ancien, `limit` ≤ 500) |

Actions journalisées : `liaison`, `creation`, `modification`, `suppression`, `validation`, `rejet`, `assignation`, `notification`, `notification_echec`, `modele_creation`, `modele_modification`, `modele_suppression`.

### Exemple

```bash
curl -X POST localhost:8080/api/onboarding/checklist -H 'Content-Type: application/json' \
     -d '{"titre":"Badge d accès","categorie":"acces","delai_jours":1}'
curl -X POST localhost:8080/api/onboarding/employes/12/checklist -H 'Content-Type: application/json' \
     -H 'X-Utilisateur-Id: 2' -d '{"date_debut":"2026-11-02"}'
curl -X PUT  localhost:8080/api/onboarding/items/1 -H 'Content-Type: application/json' -d '{"statut":"termine"}'
curl -X POST localhost:8080/api/onboarding/items/1/valider -H 'Content-Type: application/json' -d '{"approuve":true}'
```

## Notifications email (`app/Config/Email.php`)

Une notification est envoyée au responsable de l'item (email de la table `utilisateur`) et aux destinataires RH en copie :

- lors de la liaison de la checklist à un employé (pour chaque item qui a un responsable) ;
- lors de la création d'un item qui a un responsable ;
- lors de l'assignation d'un responsable ;
- à la demande (`POST /items/{id}/notifier`).

Un échec d'envoi ne bloque pas l'action : il est journalisé (`notification_echec`) et consigné dans `writable/logs`.

Par défaut : SMTP `localhost:1025` (par exemple Mailpit ou MailHog en développement), au format HTML. Pour configurer, utiliser `.env` :

```ini
email.fromEmail = 'no-reply@entreprise.mg'
email.fromName = 'SIRH - Onboarding'
email.protocol = 'smtp'
email.SMTPHost = 'smtp.entreprise.mg'
email.SMTPUser = 'user'
email.SMTPPass = 'secret'
email.SMTPPort = 587
email.SMTPCrypto = 'tls'
email.onboardingNotifications = true        ; false pour désactiver
email.onboardingRecipients = 'rh@entreprise.mg,drh@entreprise.mg'
```

## Frontend (Vue 3 + Vite)

```bash
cd frontend
npm install
npm run dev      # http://localhost:5173 ; /api est redirigé vers http://localhost:8080 (VITE_API_PROXY)
npm test         # tests Vitest
npm run build
```

| Route | Vue | Fonction principale |
| --- | --- | --- |
| `/onboarding` | `OnboardingDashboard.vue` | `fetchDashboard()` |
| `/onboarding/checklist` | `Checklist.vue` | `fetchChecklist()`, liaison à un employé |
| `/onboarding/suivi/:employeId?` | `EmployeTracking.vue` | `fetchTracking()`, changement de statut, assignation |
| `/onboarding/items/nouveau` | `ItemCreate.vue` | `handleSubmit()` (checklist type ou employé précis) |
| `/onboarding/validation` | `ItemValidate.vue` | `validateItem()` |
| `/onboarding/historique` | `ActionHistory.vue` | `fetchHistory()` |

Composants : `ProgressBar.vue`, `StatusBadge.vue`, `ProgressDisplay.vue` (`calculateProgress()`) et `ResponsableAssign.vue` (`assign()`).

Si `localStorage.utilisateur_id` est défini, il est envoyé dans l'en-tête `X-Utilisateur-Id`.

## Installation et tests backend

```bash
composer install
php spark migrate                 # crée les 3 tables (PostgreSQL, voir app/Config/Database.php)
php spark serve                   # http://localhost:8080
vendor/bin/phpunit tests/unit/Onboarding tests/E2E   # SQLite en mémoire, aucune base requise
```
