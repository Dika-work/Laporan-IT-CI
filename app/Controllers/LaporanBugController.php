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
        // Menerima data JSON dari request
        $input = $this->request->getJSON();

        // Log semua data yang diterima untuk pemeriksaan
        log_message('info', 'Data yang diterima dari frontend: ' . json_encode($input));

        $hashId = $input->hash_id ?? null;
        $lampiran = $input->lampiran ?? null;
        $apk = $input->apk ?? null;
        $priority = $input->priority ?? null;
        $foto_user = $input->foto_user ?? null;

        // Cek dan log nilai yang diterima
        log_message('info', 'hash_id: ' . $hashId);
        log_message('info', 'lampiran: ' . $lampiran);
        log_message('info', 'apk: ' . $apk);
        log_message('info', 'priority: ' . $priority);
        log_message('info', 'foto_user: ' . $foto_user);

        // Mencari laporan berdasarkan hash_id
        $laporan = $this->laporanBug->where('SHA2(id, 256)', $hashId)->first();

        if (!$laporan) {
            return $this->failNotFound('Laporan tidak ditemukan.');
        }

        // Data yang akan diupdate
        $data = [
            'lampiran' => $lampiran ?? $laporan['lampiran'],
            'apk'      => $apk ?? $laporan['apk'],
            'priority' => $priority ?? $laporan['priority'],
        ];

        try {
            // Update laporan tanpa menyentuh foto_user jika tidak ada perubahan
            $this->laporanBug->update($laporan['id'], $data);

            // Jika ada foto_user (base64 image), proses foto
            if ($foto_user) {
                // Log gambar base64
                log_message('info', 'Foto user (base64): ' . $foto_user);

                // Decode gambar dari base64
                $imageData = base64_decode($foto_user);
                $newName = uniqid() . '.jpg';

                // Simpan gambar baru
                file_put_contents(FCPATH . 'uploads/laporan/' . $newName, $imageData);

                // Update foto_user dengan foto baru
                $this->laporanBug->update($laporan['id'], ['foto_user' => 'uploads/laporan/' . $newName]);
            }

            return $this->respond([
                'status'  => 200,
                'message' => 'Laporan berhasil diperbarui',
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
