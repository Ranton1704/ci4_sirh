<?php

namespace App\Models;

use CodeIgniter\Model;

class UtilisateurModel extends Model
{
    protected $table = 'utilisateur';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'personne_id',
        'email',
        'mot_de_passe',
        'role',
        'actif',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'date_creation';
    protected $updatedField = 'date_modification';

    /**
     * Récupérer tous les utilisateurs
     */
    public function getAll(): array
    {
        return $this->findAll();
    }

    /**
     * Créer un utilisateur
     */
    public function create(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUtilisateur(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
}
