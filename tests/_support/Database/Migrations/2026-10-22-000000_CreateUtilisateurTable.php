<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Table de référence « utilisateur » (créée par le socle en production),
 * nécessaire aux tests du module Onboarding.
 */
class CreateUtilisateurTable extends Migration
{
    protected $DBGroup = 'tests';

    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'personne_id'       => ['type' => 'INT', 'null' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'mot_de_passe'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'role'              => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'actif'             => ['type' => 'BOOLEAN', 'default' => true],
            'date_creation'     => ['type' => 'DATETIME', 'null' => true],
            'date_modification' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('utilisateur', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('utilisateur', true);
    }
}
