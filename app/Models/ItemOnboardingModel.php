<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemOnboardingModel extends Model
{
    public const STATUT_A_FAIRE  = 'a_faire';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_TERMINE  = 'termine';
    public const STATUT_VALIDE   = 'valide';
    public const STATUTS         = [
        self::STATUT_A_FAIRE,
        self::STATUT_EN_COURS,
        self::STATUT_TERMINE,
        self::STATUT_VALIDE,
    ];

    protected $table            = 'item_onboarding';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'employe_id',
        'modele_id',
        'titre',
        'description',
        'categorie',
        'responsable_id',
        'statut',
        'obligatoire',
        'date_echeance',
        'date_realisation',
        'date_validation',
        'valide_par',
        'commentaire',
    ];
    protected array $casts = [
        'id'             => 'int',
        'employe_id'     => 'int',
        'modele_id'      => '?int',
        'responsable_id' => '?int',
        'valide_par'     => '?int',
        'obligatoire'    => 'boolean',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'date_creation';
    protected $updatedField  = 'date_modification';

    /**
     * Items d'un employé
     */
    public function getByEmploye(int $employeId): array
    {
        return $this->where('employe_id', $employeId)
            ->orderBy('date_echeance', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Recherche multi-critères (employe_id, statut, responsable_id)
     */
    public function search(array $filters = []): array
    {
        foreach (['employe_id', 'statut', 'responsable_id'] as $champ) {
            if (isset($filters[$champ]) && $filters[$champ] !== '') {
                $this->where($champ, $filters[$champ]);
            }
        }

        return $this->orderBy('employe_id', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Identifiants des modèles déjà liés à un employé
     */
    public function getModeleIdsLies(int $employeId): array
    {
        $rows = $this->select('modele_id')
            ->where('employe_id', $employeId)
            ->where('modele_id IS NOT NULL')
            ->findAll();

        return array_map('intval', array_column($rows, 'modele_id'));
    }
}
