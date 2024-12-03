<?php

namespace App\Controllers;

use App\Models\ApkCategory;
use CodeIgniter\RESTful\ResourceController;

class ApkCategoryController extends ResourceController
{
    protected $apkCategory;

    public function __construct()
    {
        $this->apkCategory = new ApkCategory();
    }

    public function index()
    {
        $apk = $this->apkCategory->findAll();

        if (empty($apk)) {
            return $this->respond([
                'status' => 404,
                'message' => 'Tidak ada aplikasi apapun'
            ], 404);
        }

        return $this->respond([
            'status' => 200,
            'message' => 'Category aplikasi berhasil diambil',
            'data' => $apk
        ]);
    }
}
