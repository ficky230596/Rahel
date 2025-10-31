<?php
// ==========================
// 📱 Fonnte WhatsApp Sender
// ==========================
function sendWA($target, $message)
{
    $token = "5fUGg8xkha8rnr6EoM33"; // token dari Fonnte
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.fonnte.com/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'target' => $target,
            'message' => $message
        ],
        CURLOPT_HTTPHEADER => [
            "Authorization: $token"
        ]
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

// ========================================================
// Jalankan hanya jika file ini diakses langsung (POST API)
// ========================================================
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('Content-Type: application/json; charset=utf-8');

    $target = $_POST['target'] ?? null;
    $message = $_POST['message'] ?? null;

    if (!$target || !$message) {
        echo json_encode([
            "error" => true,
            "message" => "Target dan message wajib diisi"
        ]);
        http_response_code(400);
        exit;
    }

    $response_json = sendWA($target, $message);
    $response_data = json_decode($response_json, true);

    if (isset($response_data['status']) && $response_data['status'] === 'success') {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Pesan berhasil dikirim",
            "api_response" => $response_data
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "error" => true,
            "message" => "Gagal mengirim pesan",
            "api_response" => $response_data
        ]);
    }

    exit;
}
