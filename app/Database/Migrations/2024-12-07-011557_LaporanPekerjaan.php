<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LaporanPekerjaan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'    => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'apk' => [
                'type' => 'VARCHAR',
                'constraint' => '64'
            ],
            'problem' => [
                'type' => 'LONGTEXT'
            ],
            'pekerjaan' => [
                'type' => 'LONGTEXT'
            ],
            'tgl' => [
                'type' => 'DATETIME'
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 100
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['1', '2'],
                'default' => '1',
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
        $this->forge->createTable('laporan_pekerjaan');
    }

    public function down()
    {
        $this->forge->dropTable('laporan_pekerjaan');
    }
}
