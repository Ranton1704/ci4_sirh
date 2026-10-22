<?php

namespace App\Controllers\Onboarding;

use App\Models\ChecklistModeleModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * Modèle de checklist d'onboarding (items types liés à chaque nouvel employé)
 */
class ChecklistModele extends ResourceController
{
    use OnboardingApiTrait;

    protected $modelName = ChecklistModeleModel::class;
    protected $format    = 'json';

    private const CHAMPS = ['titre', 'description', 'categorie', 'responsable_id', 'delai_jours', 'obligatoire', 'ordre', 'actif'];

    private const RULES = [
        'titre'          => 'required|max_length[150]',
        'categorie'      => 'permit_empty|max_length[50]',
        'responsable_id' => 'permit_empty|is_natural_no_zero',
        'delai_jours'    => 'permit_empty|is_natural',
        'ordre'          => 'permit_empty|integer',
    ];

    public function index()
    {
        $actifs = filter_var($this->request->getGet('actifs') ?? false, FILTER_VALIDATE_BOOLEAN);

        return $this->respond($this->model->getAll($actifs));
    }

    public function store()
    {
        $data = $this->payload();
        if (! $this->validateData($data, self::RULES)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $id = $this->model->insert($this->nettoyer($data));
        $this->service()->log('modele_creation', null, null, 'Item de checklist créé : ' . $data['titre'], $this->utilisateurId($data));

        return $this->respondCreated($this->model->find($id));
    }

    /**
     * @param int|string|null $id
     */
    public function update($id = null)
    {
        if ($this->model->find($id) === null) {
            return $this->failNotFound("Item de checklist {$id} introuvable.");
        }

        $data  = $this->payload();
        $rules = self::RULES;
        $rules['titre'] = 'permit_empty|max_length[150]';
        if (! $this->validateData($data, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = $this->nettoyer($data);
        if ($data !== []) {
            $this->model->update($id, $data);
        }
        $this->service()->log('modele_modification', null, null, "Item de checklist #{$id} modifié", $this->utilisateurId($data));

        return $this->respond($this->model->find($id));
    }

    /**
     * @param int|string|null $id
     */
    public function delete($id = null)
    {
        $modele = $this->model->find($id);
        if ($modele === null) {
            return $this->failNotFound("Item de checklist {$id} introuvable.");
        }

        $this->model->delete($id);
        $this->service()->log('modele_suppression', null, null, 'Item de checklist supprimé : ' . $modele['titre'], $this->utilisateurId());

        return $this->respondDeleted(['id' => (int) $id]);
    }

    private function nettoyer(array $data): array
    {
        $data = array_intersect_key($data, array_flip(self::CHAMPS));
        foreach (['responsable_id', 'description'] as $champ) {
            if (array_key_exists($champ, $data) && $data[$champ] === '') {
                $data[$champ] = null;
            }
        }
        foreach (['categorie', 'delai_jours', 'ordre'] as $champ) {
            if (array_key_exists($champ, $data) && $data[$champ] === '') {
                unset($data[$champ]);
            }
        }
        foreach (['obligatoire', 'actif'] as $champ) {
            if (array_key_exists($champ, $data)) {
                $data[$champ] = filter_var($data[$champ], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $data;
    }
}
