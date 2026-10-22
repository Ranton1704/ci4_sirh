<?php

namespace App\Controllers\Onboarding;

use App\Models\ItemOnboardingModel;
use CodeIgniter\RESTful\ResourceController;
use InvalidArgumentException;

/**
 * Contrôleur des items de checklist d'onboarding (P5-012)
 */
class ItemOnboarding extends ResourceController
{
    use OnboardingApiTrait;

    protected $modelName = ItemOnboardingModel::class;
    protected $format    = 'json';

    /**
     * Liste des items d'un employé (ou de tous, filtrables par ?statut=&responsable_id=&employe_id=)
     *
     * @param int|string|null $employeId
     */
    public function index($employeId = null)
    {
        $filters = [
            'employe_id'     => $employeId ?? $this->request->getGet('employe_id'),
            'statut'         => $this->request->getGet('statut'),
            'responsable_id' => $this->request->getGet('responsable_id'),
        ];

        $items = $this->model->search($filters);

        if ($employeId === null) {
            return $this->respond($items);
        }

        return $this->respond([
            'employe_id'  => (int) $employeId,
            'items'       => $items,
            'progression' => $this->service()->calculateProgress($items),
        ]);
    }

    /**
     * @param int|string|null $id
     */
    public function show($id = null)
    {
        $item = $this->model->find($id);

        return $item === null ? $this->failNotFound("Item {$id} introuvable.") : $this->respond($item);
    }

    /**
     * Créer un item
     */
    public function store()
    {
        $data  = $this->payload();
        $rules = [
            'employe_id'     => 'required|is_natural_no_zero',
            'titre'          => 'required|max_length[150]',
            'categorie'      => 'permit_empty|max_length[50]',
            'responsable_id' => 'permit_empty|is_natural_no_zero',
            'statut'         => 'permit_empty|in_list[' . implode(',', ItemOnboardingModel::STATUTS) . ']',
            'date_echeance'  => 'permit_empty|valid_date[Y-m-d]',
        ];
        if (! $this->validateData($data, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $item = $this->service()->createItem($this->nettoyer($data), $this->utilisateurId($data));
        } catch (InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        }

        return $this->respondCreated($item);
    }

    /**
     * Modifier un item
     *
     * @param int|string|null $id
     */
    public function update($id = null)
    {
        $data  = $this->payload();
        $rules = [
            'titre'          => 'permit_empty|max_length[150]',
            'categorie'      => 'permit_empty|max_length[50]',
            'responsable_id' => 'permit_empty|is_natural_no_zero',
            'statut'         => 'permit_empty|in_list[' . implode(',', ItemOnboardingModel::STATUTS) . ']',
            'date_echeance'  => 'permit_empty|valid_date[Y-m-d]',
        ];
        if (! $this->validateData($data, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            return $this->respond($this->service()->updateItem((int) $id, $this->nettoyer($data), $this->utilisateurId($data)));
        } catch (InvalidArgumentException $e) {
            return $this->model->find($id) === null
                ? $this->failNotFound($e->getMessage())
                : $this->failValidationErrors($e->getMessage());
        }
    }

    /**
     * @param int|string|null $id
     */
    public function delete($id = null)
    {
        try {
            $this->service()->deleteItem((int) $id, $this->utilisateurId());
        } catch (InvalidArgumentException $e) {
            return $this->failNotFound($e->getMessage());
        }

        return $this->respondDeleted(['id' => (int) $id]);
    }

    /**
     * Valider / rejeter un item terminé. Corps : { "approuve": true, "commentaire": "..." }
     *
     * @param int|string $id
     */
    public function valider($id)
    {
        $data     = $this->payload();
        $approuve = filter_var($data['approuve'] ?? true, FILTER_VALIDATE_BOOLEAN);

        try {
            return $this->respond($this->service()->validateItem((int) $id, $approuve, $data['commentaire'] ?? null, $this->utilisateurId($data)));
        } catch (InvalidArgumentException $e) {
            return $this->model->find($id) === null
                ? $this->failNotFound($e->getMessage())
                : $this->failValidationErrors($e->getMessage());
        }
    }

    /**
     * Assigner un responsable. Corps : { "responsable_id": 3 }
     *
     * @param int|string $id
     */
    public function assign($id)
    {
        $data = $this->payload();
        if (! $this->validateData($data, ['responsable_id' => 'required|is_natural_no_zero'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            return $this->respond($this->service()->assignResponsable((int) $id, (int) $data['responsable_id'], $this->utilisateurId($data)));
        } catch (InvalidArgumentException $e) {
            return $this->model->find($id) === null
                ? $this->failNotFound($e->getMessage())
                : $this->failValidationErrors($e->getMessage());
        }
    }

    /**
     * Renvoyer la notification aux responsables
     *
     * @param int|string $id
     */
    public function notify($id)
    {
        try {
            return $this->respond(['notifies' => $this->service()->notifyResponsables((int) $id)]);
        } catch (InvalidArgumentException $e) {
            return $this->failNotFound($e->getMessage());
        }
    }

    private function nettoyer(array $data): array
    {
        foreach (['responsable_id', 'date_echeance', 'description', 'categorie', 'statut'] as $champ) {
            if (array_key_exists($champ, $data) && $data[$champ] === '') {
                $data[$champ] = null;
            }
        }
        if (array_key_exists('categorie', $data) && $data['categorie'] === null) {
            unset($data['categorie']);
        }
        if (array_key_exists('statut', $data) && $data['statut'] === null) {
            unset($data['statut']);
        }
        if (array_key_exists('obligatoire', $data)) {
            $data['obligatoire'] = filter_var($data['obligatoire'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }
}
