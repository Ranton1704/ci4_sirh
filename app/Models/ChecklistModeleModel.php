<?php

namespace App\Models;

use CodeIgniter\Model;

class ChecklistModeleModel extends Model
{
    protected $table            = 'checklist_modele';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'titre',
        'description',
        'categorie',
        'responsable_id',
        'delai_jours',
        'obligatoire',
        'ordre',
        'actif',
    ];
    protected array $casts = [
        'id'             => 'int',
        'responsable_id' => '?int',
        'delai_jours'    => 'int',
        'ordre'          => 'int',
        'obligatoire'    => 'boolean',
        'actif'          => 'boolean',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'date_creation';
    protected $updatedField  = 'date_modification';

    /**
     * Récupérer les items du modèle de checklist (triés)
     */
    public function getAll(bool $actifsSeulement = false): array
    {
        if ($actifsSeulement) {
            $this->where('actif', true);
        }

        return $this->orderBy('ordre', 'ASC')->orderBy('id', 'ASC')->findAll();
    }
}
