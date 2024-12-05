<?php

namespace App\Controllers;

use App\Models\LaporanBug;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class LaporanBugController extends ResourceController
{
    protected $laporanBug;
    protected $userModel;

    public function __construct()
    {
        $this->laporanBug = new LaporanBug();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        try {
            $usernameHash = $this->request->getVar('username_hash');

            if (!$usernameHash) {
                return $this->fail('username is required', 400);
            }

            $filteredBug = $this->laporanBug
                ->select('laporanbugs.id, users.username AS username, users.foto_user AS user_foto_user, users.divisi, laporanbugs.apk, laporanbugs.lampiran, laporanbugs.foto_user AS laporan_foto_user, laporanbugs.tgl_diproses, laporanbugs.status_kerja, laporanbugs.priority')
                ->join('users', 'users.username_hash = laporanbugs.username_hash')
                ->where('laporanbugs.username_hash', $usernameHash)
                ->orderBy('laporanbugs.status_kerja', 'ASC')
                ->orderBy('laporanbugs.priority', 'DESC')
                ->findAll();

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
                laporanbugs.id, 
                users.username AS username, 
                users.divisi, 
                laporanbugs.apk, 
                laporanbugs.lampiran, 
                laporanbugs.foto_user AS laporan_foto_user,
                users.foto_user AS user_foto_user,
                laporanbugs.tgl_diproses, 
                laporanbugs.status_kerja, 
                laporanbugs.priority
            ')
                ->join('users', 'users.username_hash = laporanbugs.username_hash')
                ->orderBy('laporanbugs.status_kerja', 'ASC')
                ->orderBy('laporanbugs.priority', 'DESC')
                ->findAll();

            return $this->respond($allBug, 200);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }



    public function createLaporan()
    {
        $validationRules = [
            'username' => 'required',
            'priority' => 'required|in_list[1,2,3,4]',
            'status_kerja' => 'required|in_list[0,1,2]',
        ];

        if (!$this->validate($validationRules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $username = $this->request->getPost('username');
        $lampiran = $this->request->getPost('lampiran');
        $apk = $this->request->getPost('apk');
        $priority = $this->request->getPost('priority');
        $tgl_diproses = $this->request->getPost('tgl_diproses');
        $status_kerja = $this->request->getPost('status_kerja');

        $fotoPath = null;
        $fotoUser = $this->request->getFile('foto_user');
        if ($fotoUser && $fotoUser->isValid() && !$fotoUser->hasMoved()) {
            $newName = $fotoUser->getRandomName();
            $fotoUser->move(WRITEPATH . 'uploads/laporan', $newName);
            $fotoPath = 'uploads/laporan/' . $newName;
        }

        $user = $this->userModel->select('username_hash, divisi')->where('username', $username)->first();
        if (!$user) {
            return $this->fail('User not found', 404);
        }

        // Debug data yang akan dimasukkan
        log_message('debug', 'Data yang akan dimasukkan: ' . json_encode([
            'username_hash' => $user['username_hash'],
            'divisi' => $user['divisi'],
            'lampiran' => $lampiran,
            'foto_user' => $fotoPath,
            'apk' => $apk,
            'priority' => $priority,
            'tgl_diproses' => $tgl_diproses,
            'status_kerja' => $status_kerja,
        ]));

        $data = [
            'username_hash' => $user['username_hash'],
            'divisi' => $user['divisi'],
            'lampiran' => $lampiran,
            'foto_user' => $fotoPath,
            'apk' => $apk,
            'priority' => $priority,
            'tgl_diproses' => $tgl_diproses,
            'status_kerja' => $status_kerja,
        ];

        try {
            $this->laporanBug->insert($data);

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Berhasil menambahkan laporan',
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            log_message('error', '[ERROR] ' . $th->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server');
        }
    }



    public function updateLaporan($usernameHash = null)
    {
        if (!$usernameHash) {
            return $this->respond([
                'status' => 400,
                'message' => 'username is required'
            ], 400);
        }

        $laporan = $this->laporanBug->where('username_hash', $usernameHash)->first();

        if (!$laporan) {
            return $this->respond([
                'status' => 404,
                'message' => 'Laporan tidak ditemukan.'
            ], 404);
        }

        $json = $this->request->getJSON();
        $file = $this->request->getFile('foto_user');
        $data = [];

        // Update lampiran jika tersedia
        if (!empty($json->lampiran)) {
            $data['lampiran'] = $json->lampiran;
        }

        // Update apk jika tersedia
        if (!empty($json->apk)) {
            $data['apk'] = $json->apk;
        }

        // Update priority jika tersedia
        if (!empty($json->priority)) {
            $data['priority'] = $json->priority;
        }

        // Update foto_user jika file diunggah
        if ($file && $file->isValid() && !$file->hasMoved()) {
            if (!empty($laporan['foto_user']) && file_exists(WRITEPATH . $laporan['foto_user'])) {
                unlink(WRITEPATH . $laporan['foto_user']);
            }

            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/laporan', $newName);
            $data['foto_user'] = 'uploads/laporan/' . $newName;
        }

        // Lakukan pembaruan jika ada data yang diubah
        if (!empty($data)) {
            $this->laporanBug->where('username_hash', $usernameHash)->set($data)->update();

            return $this->respond([
                'status' => 200,
                'message' => 'Laporan berhasil diperbarui',
                'data' => $data
            ]);
        }

        return $this->respond([
            'status' => 400,
            'message' => 'Tidak ada data yang diperbarui.'
        ], 400);
    }

    public function deleteLaporan()
    {
        try {
            // Ambil hash ID dari request
            $hashId = $this->request->getVar('hash_id');
            log_message('debug', 'Hash ID diterima dari Flutter: ' . $hashId);

            // Cari laporan berdasarkan hash ID
            $laporan = $this->laporanBug
                ->select('id, foto_user') // Tambahkan foto_user
                ->where('SHA2(id, 256)', $hashId)
                ->first();
            log_message('debug', 'Hasil query laporan: ' . json_encode($laporan));

            // Jika laporan tidak ditemukan
            if (!$laporan) {
                log_message('debug', 'Laporan tidak ditemukan untuk hash ID: ' . $hashId);
                return $this->failNotFound('Laporan tidak ditemukan.');
            }

            // Hapus file foto_user jika ada
            if (!empty($laporan['foto_user'])) {
                $filePath = FCPATH . 'uploads/laporan/' . basename($laporan['foto_user']);
                if (file_exists($filePath)) {
                    unlink($filePath); // Hapus file
                    log_message('info', 'File foto_user berhasil dihapus: ' . $filePath);
                } else {
                    log_message('info', 'File foto_user tidak ditemukan: ' . $filePath);
                }
            }

            // Hapus laporan berdasarkan ID
            $delete = $this->laporanBug->delete($laporan['id']);
            log_message('debug', 'Hapus laporan status: ' . ($delete ? 'Berhasil' : 'Gagal'));

            // Kembalikan respons sukses
            return $this->respondDeleted([
                'status' => 200,
                'message' => 'Laporan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            // Log jika terjadi error
            log_message('error', 'Error saat menghapus laporan: ' . $e->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server.');
        }
    }
}
