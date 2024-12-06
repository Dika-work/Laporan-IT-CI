<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ApkCategory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_apk' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'subtitle' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

        ]);

        $this->forge->addPrimaryKey('id_apk');
        $this->forge->createTable('apk_category');
    }

    public function down()
    {
        $this->forge->dropTable('apk_category');
    }
}
