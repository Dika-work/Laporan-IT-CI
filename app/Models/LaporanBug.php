<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanBug extends Model
{
    protected $table = 'laporanbugs';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'username_hash',
        'divisi',
        'apk',
        'status_kerja',
        'priority',
        'tgl_diproses',
        'tgl_acc',
        'lampiran',
        'foto_user',
        'created_at',
        'updated_at'
    ];
}
