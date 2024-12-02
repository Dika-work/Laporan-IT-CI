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
            $allBug = $this->laporanBug
                ->select('username_hash, divisi, apk, lampiran, foto_user, tgl_diproses, status_kerja, priority')
                ->orderBy('status_kerja', 'ASC')
                ->orderBy('priority', 'DESC')
                ->findAll();

            return $this->respond($allBug, 200);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function createLaporan()
    {
        $username = $this->request->getPost('username');
        $lampiran = $this->request->getPost('lampiran');
        $apk = $this->request->getPost('apk');
        $priority = $this->request->getPost('priority');
        $tgl_diproses = $this->request->getPost('tgl_diproses');
        $status_kerja = $this->request->getPost('status_kerja');

        if (!$username) {
            return $this->fail('Username is required', 400);
        }

        if (!in_array($priority, ['1', '2', '3', '4'])) {
            return $this->fail('Invalid priority value', 400);
        }

        if (!in_array($status_kerja, ['0', '1', '2'])) {
            return $this->fail('Invalid status_kerja value', 400);
        }

        $user = $this->userModel->select('username_hash, divisi, foto_user')->where('username', $username)->first();

        if (!$user) {
            return $this->fail('User not found', 404);
        }

        $data = [
            'username_hash' => $user['username_hash'],
            'divisi' => $user['divisi'],
            'lampiran' => $lampiran,
            'foto_user' => $user['foto_user'],
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
                'data' => $data
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError('Terjadi kesalahan pada server');
        }
    }


    public function updateLaporan($usernameHash = null)
    {
        if (!$usernameHash) {
            return $this->respond([
                'status' => 400,
                'message' => 'username_hash is required'
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
        $data = [];

        if (!empty($json->lampiran)) {
            $data['lampiran'] = $json->lampiran;
        }

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
