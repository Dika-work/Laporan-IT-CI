<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class UserController extends ResourceController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function getUsernameByHash()
    {
        $usernameHash = $this->request->getVar('username_hash');

        if (!$usernameHash) {
            return $this->respond([
                'status' => 400,
                'message' => 'username_hash is required'
            ], 400);
        }

        log_message('info', 'Received username_hash: ' . $usernameHash);

        // Query yang diperbaiki
        $user = $this->userModel
            ->select('username, divisi') // Pilih kolom username dan divisi
            ->where('username_hash', $usernameHash) // Filter berdasarkan username_hash
            ->first();

        if (!$user) {
            log_message('error', 'User not found for hash: ' . $usernameHash);
            return $this->respond([
                'status' => 404,
                'message' => 'User not found'
            ], 404);
        }

        log_message('info', 'User found: ' . $user['username']);

        return $this->respond([
            'status' => 200,
            'message' => 'Username found',
            'data' => [
                'username' => $user['username'],
                'divisi' => $user['divisi']
            ]
        ], 200);
    }


    public function login()
    {
        $json = $this->request->getJSON();

        if (!isset($json->username) || !isset($json->password)) {
            return $this->respond([
                'status' => 400,
                'message' => 'Username dan password harus diisi.'
            ], 400);
        }

        $user = $this->userModel->where('username', $json->username)->first();

        if (!$user) {
            return $this->respond([
                'status' => 404,
                'message' => 'Username tidak ditemukan.'
            ], 404);
        }

        if (!password_verify($json->password, $user['password'])) {
            return $this->respond([
                'status' => 401,
                'message' => 'Password salah.'
            ], 401);
        }

        return $this->respond([
            'status' => 200,
            'message' => 'Login berhasil.',
            'data' => [
                'username_hash' => $user['username_hash'],
                'type_user' => $user['type_user'],
                'foto_user' => $user['foto_user']
            ]
        ], 200);
    }


    // Read: Menampilkan semua user (username dan type_user saja)
    public function index()
    {
        $users = $this->userModel->select('username_hash, type_user')->findAll();

        if (empty($users)) {
            return $this->respond([
                'status' => 404,
                'message' => 'Tidak ada data user'
            ], 404);
        }

        return $this->respond([
            'status' => 200,
            'message' => 'Data users berhasil diambil',
            'data' => $users
        ]);
    }



    // Create: Menambah user baru
    public function create()
    {
        $username = $this->request->getPost('username');
        $type_user = $this->request->getPost('type_user');
        $password = $this->request->getPost('password');
        $divisi = $this->request->getPost('divisi');
        $file = $this->request->getFile('foto_user');

        $fotoPath = null;
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads', $newName);
            $fotoPath = 'uploads/' . $newName;
        }

        $usernameHash = hash('sha256', $username);
        $data = [
            'username' => $username,
            'username_hash' => $usernameHash,
            'type_user' => $type_user,
            'divisi' => $divisi,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'foto_user' => $fotoPath,
        ];

        try {
            $this->userModel->insert($data);

            // Hapus 'username' dari data sebelum mengembalikan respons
            unset($data['username']);

            return $this->respondCreated([
                'status' => 201,
                'message' => 'User berhasil ditambahkan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('Terjadi kesalahan pada server.');
        }
    }


    // Update: Memperbarui user berdasarkan username_hash
    public function update($hashedUsername = null)
    {
        if (!$hashedUsername) {
            return $this->respond([
                'status' => 400,
                'message' => 'Username is required'
            ], 400);
        }

        // Cari user berdasarkan username_hash
        $user = $this->userModel->where('username_hash', $hashedUsername)->first();

        if (!$user) {
            return $this->respond([
                'status' => 404,
                'message' => 'User tidak ditemukan.'
            ], 404);
        }

        $json = $this->request->getJSON();
        $data = [];

        // Update hanya field yang diberikan
        if (!empty($json->type_user)) {
            $data['type_user'] = $json->type_user;
        }

        if (!empty($json->password)) {
            $data['password'] = password_hash($json->password, PASSWORD_BCRYPT);
        }

        if (!empty($data)) {
            $this->userModel->where('username_hash', $hashedUsername)->set($data)->update();

            return $this->respond([
                'status' => 200,
                'message' => 'User berhasil diperbarui',
                'data' => $data
            ]);
        }

        return $this->respond([
            'status' => 400,
            'message' => 'Tidak ada data yang diperbarui.'
        ], 400);
    }



    // Delete: Menghapus user berdasarkan username_hash
    public function delete($hashedUsername = null)
    {
        if (!$hashedUsername) {
            return $this->respond([
                'status' => 400,
                'message' => 'Username is required'
            ], 400);
        }

        // Cari user berdasarkan username_hash
        $user = $this->userModel->where('username_hash', $hashedUsername)->first();

        if (!$user) {
            return $this->respond([
                'status' => 404,
                'message' => "User dengan hash $hashedUsername tidak ditemukan."
            ], 404);
        }

        // Hapus user
        $this->userModel->where('username_hash', $hashedUsername)->delete();

        return $this->respondDeleted([
            'status' => 200,
            'message' => "User dengan hash $hashedUsername berhasil dihapus."
        ]);
    }
}
