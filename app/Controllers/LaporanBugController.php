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

    public function pluckUsername()
    {
        $usernameHash = $this->request->getVar('username_hash');

        if (!$usernameHash) {
            return $this->fail('username_hash is required', 400);
        }

        $username = $this->userModel->getUsernameByHash($usernameHash);

        if (!$username) {
            return $this->fail('User not found', 404);
        }

        return $this->respond(['username' => $username], 200);
    }

    public function index()
    {
        $allBug = $this->laporanBug->select('username_hash, divisi, lampiran, foto_user')->findAll();

        if (empty($allBug)) {
            return $this->respond([], 200);
        }

        return $this->respond($allBug, 200);
    }

    public function createLaporan()
    {
        $username = $this->request->getPost('username');
        $lampiran = $this->request->getPost('lampiran');
        $foto_user = $this->request->getPost('foto_user');

        if (!$username) {
            return $this->fail('Username is required', 400);
        }

        $user = $this->userModel->select('username_hash, divisi')->where('username', $username)->first();

        if (!$user) {
            return $this->fail('User not found', 404);
        }

        $data = [
            'username_hash' => $user['username_hash'],
            'divisi' => $user['divisi'],
            'lampiran' => $lampiran,
            'foto_user' => $foto_user,
        ];

        try {
            $this->laporanBug->insert($data);

            return $this->respondCreated([
                'status' => 201,
                'message' => 'Berhasil menambahkan laporan',
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError('Terjadi kesalahan pada server');
        }
    }

    public function updateLaporan($usernameHash = null)
    {
        // Validasi jika username_hash tidak diberikan
        if (!$usernameHash) {
            return $this->respond([
                'status' => 400,
                'message' => 'username_hash is required'
            ], 400);
        }

        // Cari laporan berdasarkan username_hash
        $laporan = $this->laporanBug->where('username_hash', $usernameHash)->first();

        if (!$laporan) {
            return $this->respond([
                'status' => 404,
                'message' => 'Laporan tidak ditemukan.'
            ], 404);
        }

        $json = $this->request->getJSON();
        $data = [];

        // Perbarui lampiran jika ada
        if (!empty($json->lampiran)) {
            $data['lampiran'] = $json->lampiran;
        }

        // Jika ada data yang diperbarui, lakukan update
        if (!empty($data)) {
            $this->laporanBug->where('username_hash', $usernameHash)->set($data)->update();

            return $this->respond([
                'status' => 200,
                'message' => 'Laporan berhasil diperbarui',
                'data' => $data
            ]);
        }

        // Jika tidak ada data yang diberikan untuk diperbarui
        return $this->respond([
            'status' => 400,
            'message' => 'Tidak ada data yang diperbarui.'
        ], 400);
    }

    public function deleteLaporan($hashedUsername = null)
    {
        if (!$hashedUsername) {
            return $this->respond([
                'status' => 400,
                'message' => 'username_hash is required'
            ], 400);
        }

        $laporan = $this->laporanBug->where('username_hash', $hashedUsername)->first();

        if (!$laporan) {
            return $this->respond([
                'status' => 404,
                'message' => "Laporan dengan hash $hashedUsername tidak ditemukan."
            ], 404);
        }

        $this->laporanBug->where('username_hash', $hashedUsername)->delete();

        return $this->respondDeleted([
            'status' => 200,
            'message' => "Laporan dengan hash $hashedUsername berhasil dihapus."
        ]);
    }
}
