<?php

namespace App\Models;

use CodeIgniter\Model;

class PosteModel extends Model
{
    protected $table = 'poste';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'intitule',
        'description',
        'departement_id',
        'niveau_hierarchique',
        'salaire_min',
        'salaire_max',
        'actif',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'date_creation';

    /**
     * Récupérer tous les postes
     */
    public function getAll(): array
    {
        return $this->where('actif', true)->findAll();
    }

    /**
     * Créer un poste
     */
    public function create(array $data): int
    {
        return $this->insert($data);
    }
}
