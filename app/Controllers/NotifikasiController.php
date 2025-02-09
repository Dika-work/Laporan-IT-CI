<?php
namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Google\Client as Google_Client;

class NotifikasiController extends ResourceController
{

    private function getAccessToken()
    {
        $keyFilePath = WRITEPATH . 'firebase-service-account.json'; // Pastikan path benar
        $client      = new Google_Client();
        $client->setAuthConfig($keyFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    }

    public function sendNotification($token, $title, $body, $data = [])
    {
        $accessToken = $this->getAccessToken();
        $projectId   = 'laporan-kerja-71c97';
        $url         = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $message = [
            'message' => [
                'token'        => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data'         => $data, // Tambahkan data logout
                'android'      => ['priority' => 'high'],
                'apns'         => ['headers' => ['apns-priority' => '10']],
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_message('debug', "FCM Response: " . $result);

        return json_decode($result, true);
    }
}
