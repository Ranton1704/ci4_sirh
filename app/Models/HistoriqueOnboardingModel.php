<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueOnboardingModel extends Model
{
    protected $table            = 'historique_onboarding';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'employe_id',
        'item_id',
        'action',
        'details',
        'utilisateur_id',
    ];
    protected array $casts = [
        'id'             => 'int',
        'employe_id'     => '?int',
        'item_id'        => '?int',
        'utilisateur_id' => '?int',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'date_action';
    protected $updatedField  = '';

    /**
     * Historique filtré (employe_id, item_id, action), du plus récent au plus ancien
     */
    public function search(array $filters = [], int $limit = 100): array
    {
        foreach (['employe_id', 'item_id', 'action'] as $champ) {
            if (isset($filters[$champ]) && $filters[$champ] !== '') {
                $this->where($champ, $filters[$champ]);
            }
        }

        return $this->orderBy('date_action', 'DESC')->orderBy('id', 'DESC')->findAll($limit);
    }
}
