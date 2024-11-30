<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanBug extends Model
{
    protected $table = 'laporanbugs';
    protected $primaryKey = 'id';
    protected $allowedFields    = ['username_hash', 'divisi', 'lampiran', 'foto_user'];
    protected $useTimestamps = true;
}
