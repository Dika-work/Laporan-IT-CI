<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LaporanBug extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'    => [
                'type' =>  'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'username_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'unique'     => true,
            ],
            'divisi' => [
                'type' => 'VARCHAR',
                'constraint' => '64'
            ],
            'lampiran' => [
                'type' => 'LONGTEXT'
            ],
            'foto_user' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'created_at'   => [
                'type'       => 'DATETIME',
                'null' => TRUE,
            ],
            'updated_at'    => [
                'type'       => 'DATETIME',
                'null' => TRUE,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('laporanbugs');
    }

    public function down()
    {
        $this->forge->dropTable('laporanbugs');
    }
}
