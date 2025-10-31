<?php
// Pastikan hanya bisa dijalankan via CLI (cron job)
if (php_sapi_name() !== 'cli') {
    die("Akses ditolak.\n");
}

require __DIR__ . '/../config/db.php'; // koneksi database
require __DIR__ . '/send_whatsapp.php'; // fungsi sendWA()

/**
 * Fungsi untuk format pesan dengan placeholder
 */
function formatMessage($template, $data)
{
    $message = $template;
    foreach ($data as $key => $value) {
        $message = str_replace("[$key]", $value, $message);
    }
    return $message;
}

/**
 * Kirim notifikasi otomatis berdasarkan jadwal
 */
function sendAutomaticNotifications($pdo)
{
    // Ambil pengaturan aktif
    $settings = $pdo->query("SELECT * FROM notification_settings WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($settings as $setting) {
        $key = $setting['key_name'];
        $offset = $setting['time_offset']; // menit
        $template = $setting['message_template'];

        if ($key === 'before_duty') {
            // 1 hari sebelum tugas
            $target_date = date('Y-m-d', strtotime("+$offset minutes"));
            $stmt = $pdo->prepare("
                SELECT j.*, p.nama as pegawai_nama, p.no_hp as target_number, 
                       r.nama as regu_nama, pel.nama as peleton_nama, pos.nama as pos_nama
                FROM jadwal j
                JOIN pegawai p ON j.pegawai_id = p.id
                LEFT JOIN regu r ON j.regu_id = r.id
                LEFT JOIN peleton pel ON j.peleton_id = pel.id
                LEFT JOIN pos ON j.pos_id = pos.id
                WHERE j.tanggal = CURDATE() + INTERVAL 1 DAY
                  AND j.status = 'aktif'
                  AND j.id NOT IN (SELECT jadwal_id FROM notification_log WHERE notification_type = ?)
            ");
            $stmt->execute([$key]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $message_data = [
                    'NAMA' => $row['pegawai_nama'],
                    'PELETON' => $row['peleton_nama'] ?? '',
                    'REGU' => $row['regu_nama'] ?? '',
                    'POS' => $row['pos_nama'] ?? '',
                    'TUGAS' => $row['tugas'] ?? 'Tugas Regu',
                    'TANGGAL' => date('d-m-Y', strtotime($row['tanggal']))
                ];

                $message = formatMessage($template, $message_data);
                $sent = sendWA($row['target_number'], $message);

                if ($sent) {
                    $log = $pdo->prepare("INSERT INTO notification_log (jadwal_id, pegawai_id, notification_type, message_sent) VALUES (?, ?, ?, ?)");
                    $log->execute([$row['id'], $row['pegawai_id'], $key, $message]);
                }
            }
        }

        if ($key === 'before_call') {
            // 30 menit sebelum apel
            $stmt = $pdo->prepare("
                SELECT j.*, p.nama as pegawai_nama, p.no_hp as target_number, 
                       r.nama as regu_nama, pel.nama as peleton_nama, pos.nama as pos_nama
                FROM jadwal j
                JOIN pegawai p ON j.pegawai_id = p.id
                LEFT JOIN regu r ON j.regu_id = r.id
                LEFT JOIN peleton pel ON j.peleton_id = pel.id
                LEFT JOIN pos ON j.pos_id = pos.id
                WHERE j.apel_time IS NOT NULL
                  AND TIMESTAMPDIFF(MINUTE, NOW(), j.apel_time) BETWEEN 0 AND 5
                  AND j.id NOT IN (SELECT jadwal_id FROM notification_log WHERE notification_type = ?)
            ");
            $stmt->execute([$key]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $message_data = [
                    'NAMA' => $row['pegawai_nama'],
                    'PELETON' => $row['peleton_nama'] ?? '',
                    'REGU' => $row['regu_nama'] ?? '',
                    'POS' => $row['pos_nama'] ?? '',
                    'MENIT' => 30
                ];

                $message = formatMessage($template, $message_data);
                $sent = sendWA($row['target_number'], $message);

                if ($sent) {
                    $log = $pdo->prepare("INSERT INTO notification_log (jadwal_id, pegawai_id, notification_type, message_sent) VALUES (?, ?, ?, ?)");
                    $log->execute([$row['id'], $row['pegawai_id'], $key, $message]);
                }
            }
        }
    }
}

sendAutomaticNotifications($pdo);
echo "Automatic notification check completed at " . date('Y-m-d H:i:s') . "\n";
