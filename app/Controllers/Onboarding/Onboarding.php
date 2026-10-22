<?php

namespace App\Controllers\Onboarding;

use CodeIgniter\RESTful\ResourceController;
use InvalidArgumentException;

/**
 * Liaison checklist ↔ employé, suivi, tableau de bord et historique
 */
class Onboarding extends ResourceController
{
    use OnboardingApiTrait;

    protected $format = 'json';

    /**
     * Lier la checklist à un employé. Corps optionnel : { "date_debut": "2026-11-01" }
     *
     * @param int|string $employeId
     */
    public function link($employeId)
    {
        $data = $this->payload();
        if (! $this->validateData($data, ['date_debut' => 'permit_empty|valid_date[Y-m-d]'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $resultat = $this->service()->linkToEmploye((int) $employeId, $data['date_debut'] ?? null, $this->utilisateurId($data));
        } catch (InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        }

        return $this->respondCreated($resultat);
    }

    /**
     * Suivi de tous les employés, ou d'un seul
     *
     * @param int|string|null $employeId
     */
    public function tracking($employeId = null)
    {
        $suivi = $this->service()->getTracking($employeId === null ? null : (int) $employeId);

        if ($employeId === null) {
            return $this->respond($suivi);
        }

        return $this->respond($suivi[0] ?? [
            'employe_id'  => (int) $employeId,
            'progression' => $this->service()->calculateProgress([]),
            'items'       => [],
        ]);
    }

    public function dashboard()
    {
        return $this->respond($this->service()->getDashboard());
    }

    public function history()
    {
        $filters = [
            'employe_id' => $this->request->getGet('employe_id'),
            'item_id'    => $this->request->getGet('item_id'),
            'action'     => $this->request->getGet('action'),
        ];
        $limit = (int) ($this->request->getGet('limit') ?? 100);

        return $this->respond($this->service()->getHistory($filters, max(1, min($limit, 500))));
    }
}
