<?php
function sendWA($target, $message)
{
    $token = "5fUGg8xkha8rnr6EoM33";
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

$target = $_POST['target'] ?? null;
$message = $_POST['message'] ?? null;

if (!$target || !$message) {
    http_response_code(400);
    exit;
}

$response_json = sendWA($target, $message);
$response_data = json_decode($response_json, true);

if (isset($response_data['status']) && $response_data['status'] === 'success') {
    http_response_code(200);
} else {
    http_response_code(500);
}

exit;

