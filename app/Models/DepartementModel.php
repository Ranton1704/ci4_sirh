<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartementModel extends Model
{
    protected $table = 'departement';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'nom',
        'code',
        'description',
        'responsable_id',
        'actif',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'date_creation';

    /**
     * Récupérer tous les départements
     */
    public function getAll(): array
    {
        return $this->where('actif', true)->findAll();
    }

    /**
     * Créer un département
     */
    public function create(array $data): int
    {
        return $this->insert($data);
    }
}
