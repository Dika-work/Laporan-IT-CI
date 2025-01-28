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

        foreach ($apk as &$item) {
            unset($item['id_apk']);
            unset($item['created_at']);
            unset($item['updated_at']);
        }

        return $this->respond([
            'status' => 200,
            'message' => 'Category aplikasi berhasil diambil',
            'data' => $apk
        ]);
    }

    public function createApkCategory()
    {
        // Mengambil input dari request
        $apk = $this->request->getPost('title');
        $subtitle = $this->request->getPost('subtitle');

        // Aturan validasi
        $validationRules = [
            'title' => 'required',
            'subtitle' => 'required'
        ];

        if (!$this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $add = [
            'title' => $apk,
            'subtitle' => $subtitle,
        ];

        try {
            $this->apkCategory->insert($add);

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Apk category berhasil ditambahkan',
                'data' => $add
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError('Terjadi kesalahan pada server.');
        }
    }


    public function deleteApkCategory()
    {
        try {
            $hashTitle = $this->request->getVar('hash_title');

            // Mencari aplikasi berdasarkan hash id yang diterima
            $apk = $this->apkCategory->where('SHA2(title, 256)', $hashTitle)->first();

            if (!$apk) {
                return $this->failNotFound('Aplikasi tidak ditemukan.');
            }

            // Menghapus data berdasarkan title
            $this->apkCategory->where('title', $apk['title'])->delete();

            return $this->respondDeleted([
                'status' => 200,
                'message' => "Aplikasi berhasil dihapus."
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError('Terjadi kesalahan pada server.');
        }
    }
}
