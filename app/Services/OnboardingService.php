<?php

namespace App\Services;

use App\Models\ChecklistModeleModel;
use App\Models\HistoriqueOnboardingModel;
use App\Models\ItemOnboardingModel;
use App\Models\UtilisateurModel;
use CodeIgniter\I18n\Time;
use Config\Email as EmailConfig;
use InvalidArgumentException;
use Throwable;

/**
 * Logique métier du module Onboarding (Personne 5)
 */
class OnboardingService
{
    private const STATUTS_TERMINES = [
        ItemOnboardingModel::STATUT_TERMINE,
        ItemOnboardingModel::STATUT_VALIDE,
    ];

    protected ItemOnboardingModel $items;
    protected ChecklistModeleModel $modeles;
    protected HistoriqueOnboardingModel $historique;
    protected UtilisateurModel $utilisateurs;

    public function __construct(
        ?ItemOnboardingModel $items = null,
        ?ChecklistModeleModel $modeles = null,
        ?HistoriqueOnboardingModel $historique = null,
        ?UtilisateurModel $utilisateurs = null,
    ) {
        $this->items        = $items ?? model(ItemOnboardingModel::class, false);
        $this->modeles      = $modeles ?? model(ChecklistModeleModel::class, false);
        $this->historique   = $historique ?? model(HistoriqueOnboardingModel::class, false);
        $this->utilisateurs = $utilisateurs ?? model(UtilisateurModel::class, false);
    }

    /**
     * Lier la checklist (items actifs du modèle) à un employé.
     * Les items du modèle déjà liés à l'employé ne sont pas dupliqués.
     *
     * @return array{crees: int, items: list<array<string, mixed>>}
     */
    public function linkToEmploye(int $employeId, ?string $dateDebut = null, ?int $utilisateurId = null): array
    {
        if ($employeId <= 0) {
            throw new InvalidArgumentException('Identifiant employé invalide.');
        }

        $debut   = $dateDebut !== null && $dateDebut !== '' ? Time::parse($dateDebut) : Time::parse(Time::now()->toDateString());
        $dejaLie = $this->items->getModeleIdsLies($employeId);
        $crees   = [];

        foreach ($this->modeles->getAll(true) as $modele) {
            if (in_array((int) $modele['id'], $dejaLie, true)) {
                continue;
            }

            $id = $this->items->insert([
                'employe_id'     => $employeId,
                'modele_id'      => $modele['id'],
                'titre'          => $modele['titre'],
                'description'    => $modele['description'],
                'categorie'      => $modele['categorie'],
                'responsable_id' => $modele['responsable_id'],
                'obligatoire'    => $modele['obligatoire'],
                'statut'         => ItemOnboardingModel::STATUT_A_FAIRE,
                'date_echeance'  => $debut->addDays((int) $modele['delai_jours'])->toDateString(),
            ]);

            $crees[] = $this->items->find($id);
        }

        $this->log('liaison', $employeId, null, count($crees) . ' item(s) de checklist lié(s) à l\'employé', $utilisateurId);

        foreach ($crees as $item) {
            $this->notifyResponsables((int) $item['id']);
        }

        return ['crees' => count($crees), 'items' => $crees];
    }

    /**
     * Notifier par email les responsables d'un item (responsable assigné + destinataires RH configurés).
     *
     * @return int Nombre de destinataires notifiés
     */
    public function notifyResponsables(int $itemId): int
    {
        $item = $this->items->find($itemId);
        if ($item === null) {
            throw new InvalidArgumentException("Item {$itemId} introuvable.");
        }

        /** @var EmailConfig $config */
        $config = config('Email');
        if (! $config->onboardingNotifications) {
            return 0;
        }

        $destinataires = $this->getEmailsResponsables($item, $config);
        if ($destinataires === []) {
            return 0;
        }

        $email = service('email');
        $email->clear(true);
        $email->setFrom($config->fromEmail, $config->fromName);
        $email->setTo($destinataires);
        $email->setSubject('[Onboarding] ' . $item['titre'] . ' - employé #' . $item['employe_id']);
        $email->setMessage(view('emails/onboarding_notification', ['item' => $item]));

        try {
            $envoye = $email->send(false);
        } catch (Throwable $e) {
            log_message('error', 'Onboarding : échec notification item {id} : {msg}', ['id' => $itemId, 'msg' => $e->getMessage()]);
            $envoye = false;
        }

        if (! $envoye) {
            $this->log('notification_echec', (int) $item['employe_id'], $itemId, 'Échec d\'envoi à ' . implode(', ', $destinataires));

            return 0;
        }

        $this->log('notification', (int) $item['employe_id'], $itemId, 'Notifié : ' . implode(', ', $destinataires));

        return count($destinataires);
    }

    /**
     * Créer un item de checklist pour un employé
     */
    public function createItem(array $data, ?int $utilisateurId = null): array
    {
        $data['statut'] ??= ItemOnboardingModel::STATUT_A_FAIRE;
        $this->assertStatut($data['statut']);

        $id   = $this->items->insert($this->filtrer($data));
        $item = $this->items->find($id);

        $this->log('creation', (int) $item['employe_id'], (int) $id, 'Item créé : ' . $item['titre'], $utilisateurId);

        if (! empty($item['responsable_id'])) {
            $this->notifyResponsables((int) $id);
        }

        return $item;
    }

    /**
     * Modifier un item (gère les dates selon le statut)
     */
    public function updateItem(int $id, array $data, ?int $utilisateurId = null): array
    {
        $item = $this->getItemOrFail($id);
        $data = $this->filtrer($data);
        unset($data['employe_id'], $data['modele_id']);

        if (isset($data['statut'])) {
            $this->assertStatut($data['statut']);
            if ($data['statut'] === ItemOnboardingModel::STATUT_VALIDE) {
                throw new InvalidArgumentException('Utiliser la validation pour passer un item au statut "valide".');
            }
            if ($data['statut'] === ItemOnboardingModel::STATUT_TERMINE && $item['statut'] !== ItemOnboardingModel::STATUT_TERMINE) {
                $data['date_realisation'] = Time::now()->toDateTimeString();
            }
            if (in_array($data['statut'], [ItemOnboardingModel::STATUT_A_FAIRE, ItemOnboardingModel::STATUT_EN_COURS], true)) {
                $data['date_realisation'] = null;
                $data['date_validation']  = null;
                $data['valide_par']       = null;
            }
        }

        if ($data !== []) {
            $this->items->update($id, $data);
        }

        $details = isset($data['statut']) && $data['statut'] !== $item['statut']
            ? "Statut : {$item['statut']} → {$data['statut']}"
            : 'Item modifié';
        $this->log('modification', (int) $item['employe_id'], $id, $details, $utilisateurId);

        return $this->items->find($id);
    }

    /**
     * Valider (ou rejeter) un item terminé
     */
    public function validateItem(int $id, bool $approuve = true, ?string $commentaire = null, ?int $utilisateurId = null): array
    {
        $item = $this->getItemOrFail($id);

        if ($item['statut'] !== ItemOnboardingModel::STATUT_TERMINE) {
            throw new InvalidArgumentException('Seul un item au statut "termine" peut être validé ou rejeté.');
        }

        if ($approuve) {
            $data = [
                'statut'          => ItemOnboardingModel::STATUT_VALIDE,
                'date_validation' => Time::now()->toDateTimeString(),
                'valide_par'      => $utilisateurId,
            ];
        } else {
            $data = [
                'statut'           => ItemOnboardingModel::STATUT_EN_COURS,
                'date_realisation' => null,
            ];
        }
        if ($commentaire !== null && $commentaire !== '') {
            $data['commentaire'] = $commentaire;
        }

        $this->items->update($id, $data);
        $this->log(
            $approuve ? 'validation' : 'rejet',
            (int) $item['employe_id'],
            $id,
            ($approuve ? 'Item validé' : 'Item rejeté') . ($commentaire ? ' : ' . $commentaire : ''),
            $utilisateurId,
        );

        return $this->items->find($id);
    }

    /**
     * Assigner un responsable à un item et le notifier
     */
    public function assignResponsable(int $id, int $responsableId, ?int $utilisateurId = null): array
    {
        $item = $this->getItemOrFail($id);

        if ($this->utilisateurs->find($responsableId) === null) {
            throw new InvalidArgumentException("Utilisateur {$responsableId} introuvable.");
        }

        $this->items->update($id, ['responsable_id' => $responsableId]);
        $this->log('assignation', (int) $item['employe_id'], $id, "Responsable assigné : utilisateur #{$responsableId}", $utilisateurId);
        $this->notifyResponsables($id);

        return $this->items->find($id);
    }

    public function deleteItem(int $id, ?int $utilisateurId = null): void
    {
        $item = $this->getItemOrFail($id);
        $this->items->delete($id);
        $this->log('suppression', (int) $item['employe_id'], $id, 'Item supprimé : ' . $item['titre'], $utilisateurId);
    }

    /**
     * Calculer la progression d'une liste d'items
     *
     * @return array{total: int, termines: int, valides: int, en_retard: int, pourcentage: int}
     */
    public function calculateProgress(array $items): array
    {
        $total    = count($items);
        $termines = 0;
        $valides  = 0;
        $retard   = 0;
        $today    = Time::now()->toDateString();

        foreach ($items as $item) {
            if (in_array($item['statut'], self::STATUTS_TERMINES, true)) {
                $termines++;
            } elseif (! empty($item['date_echeance']) && $item['date_echeance'] < $today) {
                $retard++;
            }
            if ($item['statut'] === ItemOnboardingModel::STATUT_VALIDE) {
                $valides++;
            }
        }

        return [
            'total'       => $total,
            'termines'    => $termines,
            'valides'     => $valides,
            'en_retard'   => $retard,
            'pourcentage' => $total > 0 ? (int) round($termines * 100 / $total) : 0,
        ];
    }

    /**
     * Suivi par employé (progression de chaque employé ayant des items)
     */
    public function getTracking(?int $employeId = null): array
    {
        $parEmploye = [];
        foreach ($this->items->search(['employe_id' => $employeId]) as $item) {
            $parEmploye[(int) $item['employe_id']][] = $item;
        }

        $suivi = [];
        foreach ($parEmploye as $id => $items) {
            $suivi[] = ['employe_id' => $id, 'progression' => $this->calculateProgress($items), 'items' => $items];
        }

        return $suivi;
    }

    /**
     * Données du tableau de bord
     */
    public function getDashboard(): array
    {
        $items    = $this->items->search();
        $tracking = $this->getTracking();

        $parStatut = array_fill_keys(ItemOnboardingModel::STATUTS, 0);
        foreach ($items as $item) {
            $parStatut[$item['statut']] = ($parStatut[$item['statut']] ?? 0) + 1;
        }

        $progressions = array_map(static fn (array $s): int => $s['progression']['pourcentage'], $tracking);
        $global       = $this->calculateProgress($items);

        return [
            'nb_employes'         => count($tracking),
            'nb_employes_termine' => count(array_filter($progressions, static fn (int $p): bool => $p === 100)),
            'nb_items'            => $global['total'],
            'items_par_statut'    => $parStatut,
            'items_en_retard'     => $global['en_retard'],
            'progression_moyenne' => $progressions === [] ? 0 : (int) round(array_sum($progressions) / count($progressions)),
            'employes'            => array_map(static fn (array $s): array => [
                'employe_id'  => $s['employe_id'],
                'progression' => $s['progression'],
            ], $tracking),
            'historique_recent' => $this->historique->search([], 10),
        ];
    }

    public function getHistory(array $filters = [], int $limit = 100): array
    {
        return $this->historique->search($filters, $limit);
    }

    public function getItemOrFail(int $id): array
    {
        $item = $this->items->find($id);
        if ($item === null) {
            throw new InvalidArgumentException("Item {$id} introuvable.");
        }

        return $item;
    }

    public function log(string $action, ?int $employeId, ?int $itemId, ?string $details = null, ?int $utilisateurId = null): void
    {
        $this->historique->insert([
            'employe_id'     => $employeId,
            'item_id'        => $itemId,
            'action'         => $action,
            'details'        => $details,
            'utilisateur_id' => $utilisateurId,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function getEmailsResponsables(array $item, EmailConfig $config): array
    {
        $emails = [];

        if (! empty($item['responsable_id'])) {
            $responsable = $this->utilisateurs->find((int) $item['responsable_id']);
            if ($responsable !== null && ! empty($responsable['email'])) {
                $emails[] = $responsable['email'];
            }
        }

        foreach (explode(',', $config->onboardingRecipients) as $rh) {
            if (trim($rh) !== '') {
                $emails[] = trim($rh);
            }
        }

        return array_values(array_unique($emails));
    }

    protected function filtrer(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'employe_id', 'modele_id', 'titre', 'description', 'categorie', 'responsable_id',
            'statut', 'obligatoire', 'date_echeance', 'commentaire',
        ]));
    }

    protected function assertStatut(string $statut): void
    {
        if (! in_array($statut, ItemOnboardingModel::STATUTS, true)) {
            throw new InvalidArgumentException("Statut invalide : {$statut}");
        }
    }
}
