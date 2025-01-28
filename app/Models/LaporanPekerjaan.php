<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanPekerjaan extends Model
{
    protected $table = 'laporan_pekerjaan';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'apk',
        'problem',
        'pekerjaan',
        'tgl',
        'username',
        'status',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
}
