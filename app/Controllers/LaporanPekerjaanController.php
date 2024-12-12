<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\LaporanPekerjaan;

class LaporanPekerjaanController extends ResourceController
{
    protected $laporanPekerjaan;
    protected $userModel;

    public function __construct()
    {
        $this->laporanPekerjaan = new LaporanPekerjaan();
        $this->userModel = new UserModel();
    }

    // Ambil data laporan pekerjaan berdasarkan username_hash
    public function index()
    {
        try {
            $usernameHash = $this->request->getVar('username_hash');

            if (!$usernameHash) {
                return $this->fail('Username hash is required', 400);
            }

            // Mengambil laporan pekerjaan berdasarkan username_hash
            $getLaporPekerjaan = $this->laporanPekerjaan
                ->select('id, apk, problem, pekerjaan, tgl, status')
                ->where('username', $usernameHash)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            // Jika tidak ada data, kembalikan data kosong, bukan 404
            $filteredData = $getLaporPekerjaan ? array_map(function ($item) {
                unset($item['username']); // Hapus field username
                return $item;
            }, $getLaporPekerjaan) : [];

            return $this->respond([
                'status' => 200,
                'data' => $filteredData, // Mengembalikan data kosong jika tidak ditemukan
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }


    // Buat laporan pekerjaan baru
    public function createLaporanPekerjaan()
    {
        try {
            // Ambil data input, baik dari form-data maupun JSON
            $inputData = $this->request->getPost();

            if (empty($inputData)) {
                $json = $this->request->getJSON();
                if (!$json) {
                    return $this->fail('Field username is required.', 400);
                }
                $inputData = (array)$json; // Ubah JSON menjadi array
            }

            // Validasi input
            $validationRules = [
                'apk' => 'required|max_length[64]',
                'problem' => 'required',
                'pekerjaan' => 'required',
                'tgl' => 'required|valid_date[Y-m-d H:i:s]',
                'username' => 'required|max_length[100]',
                'status' => 'required|in_list[1,2]',
            ];

            if (!$this->validate($validationRules, $inputData)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            $username = $inputData['username'];

            // Cek apakah username ada di UserModel
            $user = $this->userModel->where('username', $username)->first();

            if (!$user) {
                return $this->failNotFound('Username tidak ditemukan di sistem.');
            }

            // Ambil username_hash
            $usernameHash = $user['username_hash'];

            if (empty($usernameHash)) {
                return $this->fail('Username hash is missing.', 500);
            }

            log_message('debug', 'UsernameHash: ' . $usernameHash);

            // Siapkan data untuk disimpan
            $dataToSave = [
                'apk' => $inputData['apk'],
                'problem' => $inputData['problem'],
                'pekerjaan' => $inputData['pekerjaan'],
                'tgl' => $inputData['tgl'],
                'username' => $usernameHash, // Simpan hash ke kolom username
                'status' => $inputData['status'],
            ];

            log_message('debug', 'Final Data to Save: ' . json_encode($dataToSave));

            // Simpan data ke tabel laporan_pekerjaan
            $insertResult = $this->laporanPekerjaan->insert($dataToSave);
            log_message('debug', 'Insert Result: ' . $insertResult);

            if (!$insertResult) {
                return $this->fail('Failed to save data to laporan_pekerjaan.', 500);
            }

            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Laporan pekerjaan berhasil disimpan.',
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    // Perbarui data laporan pekerjaan
    public function updatePekerjaan()
    {
        try {
            $hashId = $this->request->getVar('hash_id');
            $pekerjaan = $this->laporanPekerjaan->where('SHA2(id, 256)', $hashId)->first();

            if (!$pekerjaan) {
                return $this->failNotFound('Data tidak ditemukan');
            }

            // Ambil data input JSON
            $json = $this->request->getJSON();

            $data = [
                'apk' => $json->apk ?? $pekerjaan['apk'],
                'problem' => $json->problem ?? $pekerjaan['problem'],
                'pekerjaan' => $json->pekerjaan ?? $pekerjaan['pekerjaan'],
                'tgl' => $json->tgl ?? $pekerjaan['tgl'],
                'status' => $json->status ?? $pekerjaan['status'],
            ];

            $this->laporanPekerjaan->update($pekerjaan['id'], $data);

            $updatedData = $this->laporanPekerjaan->find($pekerjaan['id']);

            return $this->respond([
                'status' => 200,
                'message' => 'Pekerjaan berhasil diperbarui',
                'data' => $updatedData,
            ]);
        } catch (\Throwable $th) {
            log_message('error', '[ERROR] ' . $th->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server');
        }
    }



    // Hapus data laporan pekerjaan
    public function deletePekerjaan()
    {
        try {
            // Ambil hash ID dari request
            $idHash = $this->request->getVar('hash_id');
            $pekerjaan = $this->laporanPekerjaan->where('SHA2(id, 256)', $idHash)->first();

            if (!$pekerjaan) {
                return $this->failNotFound('Laporan pekerjaan tidak ditemukan');
            }

            // Hapus laporan
            $this->laporanPekerjaan->delete($pekerjaan['id']);

            return $this->respondDeleted([
                'status' => 'success',
                'message' => 'Laporan berhasil dihapus.'
            ]);
        } catch (\Throwable $th) {
            // Log error untuk debugging
            log_message('error', '[ERROR] ' . $th->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server.');
        }
    }
}
