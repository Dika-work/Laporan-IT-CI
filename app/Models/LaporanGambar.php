<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanGambar extends Model
{
    protected $table      = 'laporan_gambar';
    protected $primaryKey = 'id';

    protected $allowedFields = ['laporan_id', 'path', 'created_at', 'updated_at'];

    protected $useTimestamps = true;
}
