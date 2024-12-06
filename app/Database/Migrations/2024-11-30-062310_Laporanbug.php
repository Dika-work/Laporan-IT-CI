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
            ],
            'divisi' => [
                'type' => 'VARCHAR',
                'constraint' => '64'
            ],
            'apk' => [
                'type' => 'VARCHAR',
                'constraint' => '64'
            ],
            'status_kerja' => [
                'type' => 'ENUM',
                'constraint' => ['0', '1', '2'],
                'default' => '0',
            ],
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['1', '2', '3', '4'],
                'default' => '1',
            ],
            'tgl_diproses' => [
                'type' => 'DATETIME',
            ],
            'lampiran' => [
                'type' => 'LONGTEXT'
            ],
            'foto_user' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
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

        $this->forge->addKey('id', true);
        $this->forge->createTable('laporanbugs');
    }

    public function down()
    {
        $this->forge->dropTable('laporanbugs');
    }
}
