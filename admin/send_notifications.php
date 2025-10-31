<?php
// Hanya dijalankan via CLI
if (php_sapi_name() !== 'cli') {
    die("Akses ditolak.\n");
}

require __DIR__ . '/../config/db.php';
require __DIR__ . '/send_whatsapp.php'; // pastikan fungsi sendWA($to, $message) ada

function formatMessage($template, $data)
{
    $map = [];
    foreach ($data as $k => $v) {
        $map[strtoupper($k)] = $v;
    }

    $message = preg_replace_callback('/(\[([^\]]+)\]|\{([^}]+)\}|%([^%]+)%)/', function ($m) use ($map) {
        $key = '';
        if (!empty($m[2])) $key = $m[2];
        elseif (!empty($m[3])) $key = $m[3];
        elseif (!empty($m[4])) $key = $m[4];

        $keyUp = strtoupper($key);
        return array_key_exists($keyUp, $map) ? $map[$keyUp] : '';
    }, $template);

    return $message;
}

function debug_log($text)
{
    $file = __DIR__ . '/notify_debug.log';
    file_put_contents($file, date('Y-m-d H:i:s') . " - " . $text . PHP_EOL, FILE_APPEND);
}

function sendAutomaticNotifications($pdo)
{
    $settings = $pdo->query("SELECT * FROM notification_settings WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($settings as $setting) {
        $key = $setting['key_name'];
        $offset = (int)$setting['time_offset'];
        $template = $setting['message_template'];

        if ($key === 'before_duty') {
            // Ambil j.*, plus p.nama & p.no_hp & p.tugas sebagai fallback pegawai_tugas
            $stmt = $pdo->prepare("
                SELECT j.*, p.nama as pegawai_nama, p.no_hp as target_number, p.tugas as pegawai_tugas,
                       r.nama as regu_nama, pel.nama as peleton_nama, pos.nama as pos_nama
                FROM jadwal j
                JOIN pegawai p ON j.pegawai_id = p.id
                LEFT JOIN regu r ON j.regu_id = r.id
                LEFT JOIN peleton pel ON j.peleton_id = pel.id
                LEFT JOIN pos ON j.pos_id = pos.id
                WHERE DATE(j.tanggal) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                  AND j.status = 'aktif'
                  AND j.id NOT IN (SELECT jadwal_id FROM notification_log WHERE notification_type = ?)
            ");
            $stmt->execute([$key]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                // Ambil tugas dari jadwal dulu, jika kosong gunakan pegawai_tugas
                $tugas_val = '';
                if (array_key_exists('tugas', $row) && $row['tugas'] !== null && trim($row['tugas']) !== '') {
                    $tugas_val = $row['tugas'];
                } elseif (array_key_exists('pegawai_tugas', $row) && $row['pegawai_tugas'] !== null) {
                    $tugas_val = $row['pegawai_tugas'];
                }

                $message_data = [
                    'NAMA' => $row['pegawai_nama'] ?? '',
                    'PELETON' => $row['peleton_nama'] ?? '',
                    'REGU' => $row['regu_nama'] ?? '',
                    'POS' => $row['pos_nama'] ?? '',
                    'TUGAS' => $tugas_val ?? '',
                    'TANGGAL' => isset($row['tanggal']) ? date('d-m-Y', strtotime($row['tanggal'])) : ''
                ];

                $message = formatMessage($template, $message_data);

                debug_log("before_duty: jadwal_id={$row['id']}, pegawai_id={$row['pegawai_id']}, jadwal.tugas=" . (isset($row['tugas']) ? ($row['tugas'] === '' ? '[EMPTY]' : $row['tugas']) : '[NO-COL]') . ", pegawai.tugas=" . (isset($row['pegawai_tugas']) ? ($row['pegawai_tugas'] === '' ? '[EMPTY]' : $row['pegawai_tugas']) : '[NO-COL]'));
                debug_log("message: " . $message);

                $sent = sendWA($row['target_number'], $message);

                if ($sent) {
                    $log = $pdo->prepare("INSERT INTO notification_log (jadwal_id, pegawai_id, notification_type, message_sent) VALUES (?, ?, ?, ?)");
                    $log->execute([$row['id'], $row['pegawai_id'], $key, $message]);
                    debug_log("sent: jadwal_id={$row['id']} logged");
                } else {
                    debug_log("failed to send: jadwal_id={$row['id']} to {$row['target_number']}");
                }
            }
        }

        if ($key === 'before_call') {
            $stmt = $pdo->prepare("
                SELECT j.*, p.nama as pegawai_nama, p.no_hp as target_number, p.tugas as pegawai_tugas,
                       r.nama as regu_nama, pel.nama as peleton_nama, pos.nama as pos_nama
                FROM jadwal j
                JOIN pegawai p ON j.pegawai_id = p.id
                LEFT JOIN regu r ON j.regu_id = r.id
                LEFT JOIN peleton pel ON j.peleton_id = pel.id
                LEFT JOIN pos ON j.pos_id = pos.id
                WHERE j.apel_time IS NOT NULL
                  AND TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(j.tanggal, j.apel_time)) BETWEEN 0 AND 30
                  AND j.id NOT IN (SELECT jadwal_id FROM notification_log WHERE notification_type = ?)
            ");
            $stmt->execute([$key]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $tugas_val = '';
                if (array_key_exists('tugas', $row) && $row['tugas'] !== null && trim($row['tugas']) !== '') {
                    $tugas_val = $row['tugas'];
                } elseif (array_key_exists('pegawai_tugas', $row) && $row['pegawai_tugas'] !== null) {
                    $tugas_val = $row['pegawai_tugas'];
                }

                $message_data = [
                    'NAMA' => $row['pegawai_nama'] ?? '',
                    'PELETON' => $row['peleton_nama'] ?? '',
                    'REGU' => $row['regu_nama'] ?? '',
                    'POS' => $row['pos_nama'] ?? '',
                    'MENIT' => $offset ?: 30,
                    'TUGAS' => $tugas_val ?? ''
                ];

                $message = formatMessage($template, $message_data);

                debug_log("before_call: jadwal_id={$row['id']}, pegawai_id={$row['pegawai_id']}, jadwal.tugas=" . (isset($row['tugas']) ? ($row['tugas'] === '' ? '[EMPTY]' : $row['tugas']) : '[NO-COL]') . ", pegawai.tugas=" . (isset($row['pegawai_tugas']) ? ($row['pegawai_tugas'] === '' ? '[EMPTY]' : $row['pegawai_tugas']) : '[NO-COL]'));
                debug_log("message: " . $message);

                $sent = sendWA($row['target_number'], $message);

                if ($sent) {
                    $log = $pdo->prepare("INSERT INTO notification_log (jadwal_id, pegawai_id, notification_type, message_sent) VALUES (?, ?, ?, ?)");
                    $log->execute([$row['id'], $row['pegawai_id'], $key, $message]);
                    debug_log("sent: jadwal_id={$row['id']} logged");
                } else {
                    debug_log("failed to send: jadwal_id={$row['id']} to {$row['target_number']}");
                }
            }
        }
    }
}

sendAutomaticNotifications($pdo);
echo "Automatic notification check completed at " . date('Y-m-d H:i:s') . "\n";
