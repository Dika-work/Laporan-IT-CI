<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['username', 'username_hash', 'type_user', 'password', 'divisi', 'foto_user', 'device_token'];
    protected $useTimestamps = true;

    // Tambahkan hooks untuk beforeInsert dan beforeUpdate
    protected $beforeInsert = ['hashUsername'];
    protected $beforeUpdate = ['hashUsername'];

    // Fungsi untuk hash username
    protected function hashUsername(array $data)
    {
        if (isset($data['data']['username'])) {
            $data['data']['username_hash'] = hash('sha256', $data['data']['username']);
        }
        return $data;
    }

    // pluck username_hash
    public function getUsernameByHash($usernameHash)
    {
        $user = $this->where('username_hash', $usernameHash)->first();
        return $user ? $user['username'] : null;
    }
}
