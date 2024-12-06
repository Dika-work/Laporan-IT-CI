<?php

namespace App\Controllers;

use App\Models\LaporanBug;
use App\Models\UserModel;
use App\Models\LaporanGambar;
use CodeIgniter\RESTful\ResourceController;

class LaporanBugController extends ResourceController
{
    protected $laporanBug;
    protected $userModel;
    protected $laporanGambar;

    public function __construct()
    {
        $this->laporanBug = new LaporanBug();
        $this->userModel = new UserModel();
        $this->laporanGambar = new LaporanGambar();
    }

    public function index()
    {
        try {
            $usernameHash = $this->request->getVar('username_hash');

            if (!$usernameHash) {
                return $this->fail('username is required', 400);
            }

            $filteredBug = $this->laporanBug
                ->select('laporanbugs.id, users.username AS username, users.foto_user AS user_foto_user, users.divisi, laporanbugs.apk, laporanbugs.lampiran, laporanbugs.tgl_diproses, laporanbugs.status_kerja, laporanbugs.priority')
                ->join('users', 'users.username_hash = laporanbugs.username_hash')
                ->where('laporanbugs.username_hash', $usernameHash)
                ->orderBy('laporanbugs.status_kerja', 'ASC')
                ->orderBy('laporanbugs.priority', 'DESC')
                ->findAll();

            foreach ($filteredBug as &$bug) {
                // Ambil semua gambar terkait dari tabel laporan_gambar
                $bug['images'] = $this->laporanGambar->where('laporan_id', $bug['id'])->findAll();
            }

            return $this->respond($filteredBug, 200);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function getAllLaporan()
    {
        try {
            $allBug = $this->laporanBug
                ->select('
                laporanbugs.*, 
                users.username AS username, 
                users.divisi, 
                users.foto_user AS user_foto_user
            ')
                ->join('users', 'users.username_hash = laporanbugs.username_hash')
                ->orderBy('laporanbugs.status_kerja', 'ASC')
                ->orderBy('laporanbugs.priority', 'DESC')
                ->findAll();

            foreach ($allBug as &$bug) {
                // Ambil semua gambar untuk laporan ini
                $bug['images'] = $this->laporanGambar->where('laporan_id', $bug['id'])->findAll();
            }

            return $this->respond($allBug, 200);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function createLaporan()
    {
        // Log untuk debug
        log_message('info', 'Request Data: ' . json_encode($this->request->getPost()));
        log_message('info', 'Files Received: ' . json_encode($_FILES['foto_user'] ?? null));

        // Validasi input
        $validationRules = [
            'username'     => 'required',
            'priority'     => 'required|in_list[1,2,3,4]',
            'status_kerja' => 'required|in_list[0,1,2]',
            'lampiran'     => 'required',
        ];

        if (!$this->validate($validationRules)) {
            log_message('error', 'Validation Errors: ' . json_encode($this->validator->getErrors()));
            return $this->fail($this->validator->getErrors(), 400);
        }

        $username     = $this->request->getPost('username');
        $lampiran     = $this->request->getPost('lampiran');
        $apk          = $this->request->getPost('apk');
        $priority     = $this->request->getPost('priority');
        $tgl_diproses = $this->request->getPost('tgl_diproses');
        $status_kerja = $this->request->getPost('status_kerja');

        // Ambil data user
        $user = $this->userModel->select('username_hash, divisi')->where('username', $username)->first();
        if (!$user) {
            return $this->fail('User not found', 404);
        }

        $dataLaporan = [
            'username_hash' => $user['username_hash'],
            'divisi'        => $user['divisi'],
            'lampiran'      => $lampiran,
            'apk'           => $apk,
            'priority'      => $priority,
            'tgl_diproses'  => $tgl_diproses,
            'status_kerja'  => $status_kerja,
        ];

        try {
            // Simpan laporan
            $laporanId = $this->laporanBug->insert($dataLaporan, true);
            log_message('info', "Laporan ID {$laporanId} berhasil dibuat.");

            // Simpan gambar
            $gambarFiles = $this->request->getFileMultiple('foto_user');
            if ($gambarFiles && is_array($gambarFiles)) {
                foreach ($gambarFiles as $file) {
                    if ($file->isValid() && !$file->hasMoved()) {
                        $newName = $file->getRandomName();
                        $file->move(FCPATH . 'uploads/laporan', $newName);

                        // Simpan path gambar di table laporan_gambar
                        $this->laporanGambar->insert([
                            'laporan_id' => $laporanId,
                            'path'       => 'uploads/laporan/' . $newName,
                        ]);
                        log_message('info', "Gambar disimpan: uploads/laporan/{$newName}");
                    }
                }
            }

            // Update kolom foto_user di tabel laporanbugs
            $this->laporanBug->update($laporanId, ['foto_user' => $laporanId]);
            log_message('info', "Kolom foto_user diperbarui dengan laporan ID: {$laporanId}");

            return $this->respondCreated([
                'status'  => 201,
                'message' => 'Berhasil menambahkan laporan',
            ]);
        } catch (\Throwable $th) {
            log_message('error', '[ERROR] ' . $th->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server');
        }
    }


    public function updateLaporan()
    {
        $hashId = $this->request->getVar('hash_id');
        $laporan = $this->laporanBug->where('SHA2(id, 256)', $hashId)->first();

        if (!$laporan) {
            return $this->failNotFound('Laporan tidak ditemukan.');
        }

        $json = $this->request->getJSON();
        $files = $this->request->getFiles('foto_user');

        $data = [
            'lampiran' => $json->lampiran ?? $laporan['lampiran'],
            'apk'      => $json->apk ?? $laporan['apk'],
            'priority' => $json->priority ?? $laporan['priority'],
        ];

        try {
            $this->laporanBug->update($laporan['id'], $data);

            // Tambahkan gambar baru
            if ($files) {
                foreach ($files as $file) {
                    if ($file->isValid() && !$file->hasMoved()) {
                        $newName = $file->getRandomName();
                        $file->move(FCPATH . 'uploads/laporan', $newName);

                        $this->laporanGambar->insert([
                            'laporan_id' => $laporan['id'],
                            'path'       => 'uploads/laporan/' . $newName,
                        ]);
                    }
                }
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Laporan berhasil diperbarui',
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError('Terjadi kesalahan pada server');
        }
    }

    public function deleteLaporan()
    {
        try {
            $hashId = $this->request->getVar('hash_id');
            $laporan = $this->laporanBug->where('SHA2(id, 256)', $hashId)->first();

            if (!$laporan) {
                return $this->failNotFound('Laporan tidak ditemukan.');
            }

            // Ambil semua gambar yang terkait dengan laporan
            $images = $this->laporanGambar->where('laporan_id', $laporan['id'])->findAll();

            // Hapus setiap gambar dari server dan database
            foreach ($images as $image) {
                $filePath = FCPATH . $image['path'];
                if (file_exists($filePath)) {
                    unlink($filePath); // Hapus file dari server
                }
                $this->laporanGambar->delete($image['id']); // Hapus entri dari database
            }

            // Hapus laporan dari database
            $this->laporanBug->delete($laporan['id']);

            return $this->respondDeleted(['message' => 'Laporan dan semua gambar terkait berhasil dihapus.']);
        } catch (\Throwable $th) {
            log_message('error', '[ERROR] ' . $th->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server.');
        }
    }


    // public function deleteImage($imageId)
    // {
    //     try {
    //         $image = $this->laporanGambar->find($imageId);

    //         if (!$image) {
    //             return $this->failNotFound('Gambar tidak ditemukan.');
    //         }

    //         $filePath = FCPATH . $image['path'];

    //         // Hapus file dari server
    //         if (file_exists($filePath)) {
    //             unlink($filePath);
    //         }

    //         // Hapus dari database
    //         $this->laporanGambar->delete($imageId);

    //         return $this->respondDeleted(['message' => 'Gambar berhasil dihapus.']);
    //     } catch (\Throwable $th) {
    //         return $this->failServerError('Terjadi kesalahan pada server.');
    //     }
    // }
}
