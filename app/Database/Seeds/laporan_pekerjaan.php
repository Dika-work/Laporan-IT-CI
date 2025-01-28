<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LaporanPekerjaan extends Seeder
{
    public function run()
    {
        $data = [
            'apk' => 'MTC',
            'problem'    => '-',
            'pekerjaan'    => 'asfafasfasf',
            'tgl'    => '2024-12-09 10:15:41',
            'username'    => '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918',
            'status'    => '0',
        ];

        $this->db->query('INSERT INTO laporan_pekerjaan (apk, problem, pekerjaan, tgl, username, status) VALUES(:apk:, :problem:, :pekerjaan:, :tgl:, :username:, :status:)', $data);

        $this->db->table('laporan_pekerjaan')->insert($data);
    }
}
