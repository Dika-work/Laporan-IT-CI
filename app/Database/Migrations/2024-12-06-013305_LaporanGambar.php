<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LaporanGambar extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'laporan_id'  => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'path'        => [
                'type'           => 'VARCHAR',
                'constraint'     => 255,
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

        // Set primary key
        $this->forge->addKey('id', true);

        // Set foreign key untuk relasi ke tabel `laporanbugs`
        $this->forge->addForeignKey('laporan_id', 'laporanbugs', 'id', 'CASCADE', 'CASCADE');

        // Buat tabel
        $this->forge->createTable('laporan_gambar');
    }

    public function down()
    {
        $this->forge->dropTable('laporan_gambar');
    }
}
