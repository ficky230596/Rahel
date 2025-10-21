<?php
// Pastikan skrip ini hanya dapat diakses melalui CLI (Cron Job)
if (php_sapi_name() !== 'cli') {
    die('Akses ditolak.');
}

require __DIR__ . '/../config/db.php'; // Sesuaikan path koneksi database

// GANTI URL INI: Ini adalah URL HTTP/HTTPS ke file send_whatsapp.php Anda.
// KECUALI Anda memanggilnya dengan path file, maka gunakan: 
// const WHATSAPP_API_URL = __DIR__ . '/send_whatsapp.php';
const WHATSAPP_API_URL = 'http://URL_DOMAIN_ANDA/admin/send_whatsapp.php'; // GANTI INI!

// --- UTILITY FUNGSI UNTUK PENGIRIMAN WHATSAPP INTERNAL ---
function sendNotification($pegawai_id, $target_number, $message) {
    // 1. Format Nomor (ke 62...)
    $target_number = preg_replace('/[^0-9]/', '', $target_number);
    if (substr($target_number, 0, 1) === '0') {
        $target_number = '62' . substr($target_number, 1);
    } elseif (substr($target_number, 0, 2) !== '62' && strlen($target_number) > 8) {
        $target_number = '62' . $target_number;
    }

    if (empty($target_number) || strlen($target_number) < 10) {
        return false;
    }
    
    // 2. Siapkan Payload
    $payload = [
        'target' => $target_number,
        'message' => $message,
    ];
    
    // 3. Eksekusi cURL
    $ch = curl_init(WHATSAPP_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 4. Cek Hasil (Asumsi 2xx adalah sukses dari skrip internal)
    if ($http_code >= 200 && $http_code < 300) {
        return true;
    } else {
        return false;
    }
}
// --- END UTILITY FUNGSI WHATSAPP ---


// --- UTILITY FUNGSI LAIN ---
function formatMessage($template, $data) {
    $message = $template;
    $message = str_replace('[NAMA]', $data['pegawai_nama'], $message);
    $message = str_replace('[PELETON]', $data['peleton_nama'], $message);
    $message = str_replace('[REGU]', $data['regu_nama'], $message);
    $message = str_replace('[POS]', $data['pos_nama'], $message);
    $message = str_replace('[TUGAS]', $data['tugas'] ?? 'Tugas Regu', $message);
    $message = str_replace('[TANGGAL]', date('d-m-Y', strtotime($data['tanggal'])), $message);
    $message = str_replace('[MENIT]', $data['time_offset'], $message);
    return $message;
}
// --- END UTILITY FUNGSI LAIN ---


// 1. Ambil Pengaturan Notifikasi Aktif
$settings_stmt = $pdo->prepare("SELECT * FROM notification_settings WHERE is_active = 1");
$settings_stmt->execute();
$active_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR | PDO::FETCH_GROUP);

// Loop melalui semua pengaturan aktif
foreach ($active_settings as $key => $setting_group) {
    $setting = $setting_group[0];
    $time_offset_minutes = $setting['time_offset']; 
    $template = $setting['message_template'];

    // Waktu Target Kirim: Waktu Tugas (07:00:00) dikurangi Offset Waktu (dalam Menit)
    // Ubah '07:00:00' jika slot tugas Anda berbeda
    $target_time_sql = "DATE_SUB(STR_TO_DATE(CONCAT(j.tanggal, ' ', '07:00:00'), '%Y-%m-%d %H:%i:%s'), INTERVAL $time_offset_minutes MINUTE)";

    // 3. Ambil Jadwal yang Sesuai untuk Dinotifikasi
    $jadwal_stmt = $pdo->prepare("
        SELECT 
            j.*, p.nama as pegawai_nama, p.no_hp as target_number, p.tugas, 
            r.nama as regu_nama, pel.nama as peleton_nama, pos.nama as pos_nama
        FROM jadwal j
        JOIN pegawai p ON j.pegawai_id = p.id
        LEFT JOIN regu r ON j.regu_id = r.id
        LEFT JOIN peleton pel ON j.peleton_id = pel.id
        LEFT JOIN pos ON j.pos_id = pos.id
        WHERE 
            -- Cek apakah Waktu Target Kirim berada dalam Window 10 Menit dari Waktu Sekarang
            $target_time_sql 
            BETWEEN 
                DATE_SUB(NOW(), INTERVAL 5 MINUTE)  
            AND 
                DATE_ADD(NOW(), INTERVAL 5 MINUTE) 
            
            AND j.status = 'aktif'
            -- Cek Log: Belum pernah dikirim notifikasi jenis ini
            AND j.id NOT IN (SELECT jadwal_id FROM notification_log WHERE notification_type = ?)
    ");
    
    $jadwal_stmt->execute([$key]);
    $assignments_to_notify = $jadwal_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Proses Pengiriman
    foreach ($assignments_to_notify as $assignment) {
        $message_data = $assignment;
        $message_data['time_offset'] = $time_offset_minutes; 
        $target_number = $assignment['target_number'];
        
        $final_message = formatMessage($template, $message_data);
        
        $success = sendNotification($assignment['pegawai_id'], $target_number, $final_message);
        
        // 5. Log Notifikasi
        if ($success) {
            $log_stmt = $pdo->prepare("
                INSERT INTO notification_log (jadwal_id, pegawai_id, notification_type, message_sent) 
                VALUES (?, ?, ?, ?)
            ");
            $log_stmt->execute([$assignment['id'], $assignment['pegawai_id'], $key, $final_message]);
        }
    }
}

echo "Notification check completed at " . date('Y-m-d H:i:s') . "\n";
?>