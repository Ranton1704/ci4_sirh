<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Module Onboarding (Personne 5)
 *
 * - checklist_modele      : modèle de checklist (items types appliqués à chaque nouvel employé)
 * - item_onboarding       : items de checklist liés à un employé
 * - historique_onboarding : journal des actions du module
 */
class CreateOnboardingTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'titre'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'categorie'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'],
            'responsable_id'    => ['type' => 'INT', 'null' => true],
            'delai_jours'       => ['type' => 'INT', 'default' => 7],
            'obligatoire'       => ['type' => 'BOOLEAN', 'default' => new RawSql('TRUE')],
            'ordre'             => ['type' => 'INT', 'default' => 0],
            'actif'             => ['type' => 'BOOLEAN', 'default' => new RawSql('TRUE')],
            'date_creation'     => ['type' => 'TIMESTAMP', 'null' => true],
            'date_modification' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('checklist_modele', true);

        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employe_id'        => ['type' => 'INT'],
            'modele_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'titre'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'categorie'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'],
            'responsable_id'    => ['type' => 'INT', 'null' => true],
            'statut'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'a_faire'],
            'obligatoire'       => ['type' => 'BOOLEAN', 'default' => new RawSql('TRUE')],
            'date_echeance'     => ['type' => 'DATE', 'null' => true],
            'date_realisation'  => ['type' => 'TIMESTAMP', 'null' => true],
            'date_validation'   => ['type' => 'TIMESTAMP', 'null' => true],
            'valide_par'        => ['type' => 'INT', 'null' => true],
            'commentaire'       => ['type' => 'TEXT', 'null' => true],
            'date_creation'     => ['type' => 'TIMESTAMP', 'null' => true],
            'date_modification' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('employe_id');
        $this->forge->addKey('statut');
        $this->forge->addForeignKey('modele_id', 'checklist_modele', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('item_onboarding', true);

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employe_id'     => ['type' => 'INT', 'null' => true],
            'item_id'        => ['type' => 'INT', 'null' => true],
            'action'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'details'        => ['type' => 'TEXT', 'null' => true],
            'utilisateur_id' => ['type' => 'INT', 'null' => true],
            'date_action'    => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('employe_id');
        $this->forge->addKey('item_id');
        $this->forge->createTable('historique_onboarding', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('historique_onboarding', true);
        $this->forge->dropTable('item_onboarding', true);
        $this->forge->dropTable('checklist_modele', true);
    }
}
