<?php

namespace App\Models;

use CodeIgniter\Model;

class ApkCategory extends Model
{
    protected $table = 'apk_category';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'id_apk',
        'title',
        'subtitle',
        'created_at',
        'updated_at'
    ];
}
