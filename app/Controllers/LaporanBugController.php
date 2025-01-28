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
                ->orderBy('laporanbugs.created_at', 'DESC')
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
                ->orderBy('laporanbugs.created_at', 'DESC')
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

    public function changeStatusKerja()
    {
        try {
            $hashId = $this->request->getVar('hash_id');
            $laporan = $this->laporanBug->where('SHA2(id, 256)', $hashId)->first();

            if (!$laporan) {
                return $this->failNotFound('Data laporan tidak ditemukan.');
            }

            $json = $this->request->getJSON();
            $statusKerja = $json->status_kerja;
            $tglAcc = $json->tgl_acc ?? null;

            // Validasi status_kerja
            if (!in_array($statusKerja, [0, 1, 2])) {
                return $this->failValidationErrors('status_kerja harus diisi dan memiliki nilai 0, 1, atau 2.');
            }

            // Validasi tgl_acc
            if (empty($tglAcc)) {
                return $this->failValidationErrors('tgl_acc tidak boleh kosong.');
            }

            if (!\DateTime::createFromFormat('Y-m-d H:i:s', $tglAcc)) {
                return $this->failValidationErrors('Format tgl_acc tidak valid. Gunakan format Y-m-d H:i:s.');
            }

            // Data untuk update
            $updateData = [
                'status_kerja' => $statusKerja,
                'tgl_acc' => $tglAcc
            ];

            // Update data
            $this->laporanBug->update($laporan['id'], $updateData);
            log_message('debug', 'Update Data: ' . json_encode($updateData));

            return $this->respond([
                'status'  => 200,
                'message' => 'Status kerja berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error: ' . $e->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server: ' . $e->getMessage());
        }
    }



    public function createLaporan()
    {
        log_message('info', 'Request Data: ' . json_encode($this->request->getPost()));
        log_message('info', 'Files Received: ' . json_encode($_FILES['foto_user'] ?? null));

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
            $laporanId = $this->laporanBug->insert($dataLaporan, true);
            log_message('info', "Laporan ID {$laporanId} berhasil dibuat.");

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

                $this->laporanBug->update($laporanId, ['foto_user' => $laporanId]);
                log_message('info', "Kolom foto_user diperbarui dengan laporan ID: {$laporanId}");
            } else {
                $this->laporanBug->update($laporanId, ['foto_user' => null]);
                log_message('info', "Kolom foto_user tidak diperbarui karena tidak ada gambar yang diupload.");
            }

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
        // Ambil data teks dari request
        $hashId   = $this->request->getPost('hash_id');
        $lampiran = $this->request->getPost('lampiran');
        $apk      = $this->request->getPost('apk');
        $priority = $this->request->getPost('priority');

        // Ambil daftar gambar lama yang ingin dipertahankan
        $existingImages = $this->request->getPost('existing_images') ?? [];
        if (!is_array($existingImages)) {
            $existingImages = [];
        }

        // **Normalisasi existing_images** untuk memastikan path relatif
        $existingImages = array_map(function ($image) {
            // Hilangkan protokol, domain, dan base URL
            $normalized = parse_url($image, PHP_URL_PATH); // Ambil bagian path
            return ltrim($normalized, '/'); // Hapus leading slash jika ada
        }, $existingImages);

        // Debugging: Log hasil existing_images yang sudah dinormalisasi
        log_message('info', 'Normalized Existing Images: ' . json_encode($existingImages));

        // Cari laporan berdasarkan hash_id
        $laporan = $this->laporanBug->where('SHA2(id, 256)', $hashId)->first();
        if (!$laporan) {
            return $this->failNotFound('Laporan tidak ditemukan.');
        }

        // Ambil semua gambar lama dari database
        $oldImages = $this->laporanGambar->where('laporan_id', $laporan['id'])->findAll();
        $oldImagePaths = array_column($oldImages, 'path');

        // Debugging: Log semua gambar lama
        log_message('info', 'Old Image Paths: ' . json_encode($oldImagePaths));

        // Hapus gambar lama yang tidak ada di existing_images
        foreach ($oldImagePaths as $oldPath) {
            if (!in_array($oldPath, $existingImages)) {
                // Hapus file dari direktori jika file ada
                if (file_exists(FCPATH . $oldPath)) {
                    unlink(FCPATH . $oldPath);
                    log_message('info', 'Deleted Image File: ' . $oldPath);
                }
                // Hapus record gambar dari database
                $this->laporanGambar->where(['laporan_id' => $laporan['id'], 'path' => $oldPath])->delete();
                log_message('info', 'Deleted Image Record: ' . $oldPath);
            }
        }

        // Ambil file gambar baru (jika ada)
        $uploadedFiles = $this->request->getFileMultiple('new_images');
        $newImagePaths = [];

        if ($uploadedFiles) {
            foreach ($uploadedFiles as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    // Simpan file baru ke direktori
                    $newFileName = $file->getRandomName();
                    $file->move(FCPATH . 'uploads/laporan/', $newFileName);
                    $newImagePaths[] = 'uploads/laporan/' . $newFileName;
                    log_message('info', 'New Image Uploaded: ' . $newFileName);
                }
            }
        }

        // Simpan informasi gambar baru ke database
        foreach ($newImagePaths as $path) {
            $this->laporanGambar->insert([
                'laporan_id' => $laporan['id'],
                'path'       => $path,
            ]);
        }

        // Data yang akan diperbarui
        $data = [
            'lampiran' => $lampiran ?? $laporan['lampiran'],
            'apk'      => $apk ?? $laporan['apk'],
            'priority' => $priority ?? $laporan['priority'],
        ];

        try {
            // Update data laporan
            $this->laporanBug->update($laporan['id'], $data);

            return $this->respond([
                'status'  => 200,
                'message' => 'Laporan berhasil diperbarui.',
            ]);
        } catch (\Throwable $th) {
            log_message('error', 'Terjadi kesalahan: ' . $th->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server: ' . $th->getMessage());
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
}
