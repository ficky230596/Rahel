<?php
session_start();
require '../config/db.php'; // Pastikan path koneksi database benar

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}



$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// GANTI URL INI: Ini adalah URL HTTP/HTTPS ke file send_whatsapp.php Anda.
// Contoh jika Anda menggunakan localhost:
// const WHATSAPP_API_URL = 'http://localhost/damkar_scheduler/admin/send_whatsapp.php';
// Contoh jika domain Anda adalah scheduler.com:
const WHATSAPP_API_URL = 'http://localhost/damkar_scheduler_2/admin/send_whatsapp.php'; 

// --- UTILITY FUNGSI UNTUK PENGIRIMAN WHATSAPP INTERNAL ---

/**
 * Mengirim notifikasi dengan memanggil skrip internal send_whatsapp.php.
 */
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


// --- FUNGSI KIRIM NOTIFIKASI DARURAT ---

function sendToAllPegawai($pdo, $message) {
    // Ambil semua Pegawai dengan kolom no_hp yang valid
    $stmt = $pdo->query("SELECT id, nama, no_hp FROM pegawai WHERE role='petugas' AND no_hp IS NOT NULL AND TRIM(no_hp) != ''");
    $pegawais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $success_count = 0;
    foreach ($pegawais as $p) {
        $success = sendNotification($p['id'], $p['no_hp'], $message);

        if ($success) {
            $success_count++;
            // Log ke tabel notification_log (Tipe: emergency)
            $log_stmt = $pdo->prepare("INSERT INTO notification_log (pegawai_id, notification_type, message_sent) VALUES (?, ?, ?)");
            $log_stmt->execute([$p['id'], 'emergency', $message]);
        }
    }
    return $success_count;
}

// --- HANDLE UPDATE PENGATURAN ---
if (isset($_POST['update_settings'])) {
    $settings = [
        'before_duty' => [
            'offset' => intval($_POST['offset_duty']) * 60, // Jam ke menit
            'template' => $_POST['template_duty'],
            'active' => isset($_POST['active_duty']) ? 1 : 0
        ],
        'before_call' => [
            'offset' => intval($_POST['offset_call']), // Sudah dalam menit
            'template' => $_POST['template_call'],
            'active' => isset($_POST['active_call']) ? 1 : 0
        ]
    ];

    foreach ($settings as $key => $data) {
        $stmt = $pdo->prepare("
            INSERT INTO notification_settings (key_name, time_offset, message_template, is_active) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                time_offset = VALUES(time_offset), 
                message_template = VALUES(message_template), 
                is_active = VALUES(is_active)
        ");
        $stmt->execute([$key, $data['offset'], $data['template'], $data['active']]);
    }

    $_SESSION['message'] = '✅ Pengaturan notifikasi berhasil diperbarui!';
    header('Location: notification_settings.php');
    exit;
}

// --- HANDLE PESAN DARURAT ---
if (isset($_POST['send_emergency'])) {
    $emergency_msg = trim($_POST['emergency_message']);
    if (empty($emergency_msg)) {
         $_SESSION['message'] = '❌ Pesan darurat tidak boleh kosong.';
    } else {
        $sent_count = sendToAllPegawai($pdo, $emergency_msg);
        $_SESSION['message'] = "📣 Pesan darurat berhasil dikirim ke $sent_count petugas!";
    }
    header('Location: notification_settings.php');
    exit;
}

// --- AMBIL DATA SETTING SAAT INI ---
// Hapus PDO::FETCH_KEY_PAIR. Gunakan PDO::FETCH_GROUP agar hasilnya dikelompokkan oleh 'key_name'.
$settings_data = $pdo->query("SELECT key_name, time_offset, message_template, is_active FROM notification_settings")->fetchAll(PDO::FETCH_GROUP);

function getSetting($key, $field, $default) {
    global $settings_data;
    return $settings_data[$key][0][$field] ?? $default;
}

$offset_duty_hours = round(getSetting('before_duty', 'time_offset', 1440) / 60); 
$offset_call_minutes = getSetting('before_call', 'time_offset', 30);      


include 'header.php';
include 'sidebar.php';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pengaturan Notifikasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/admin/notification_settings.css">
</head>
<body>
    <div class="container py-4">
        <h3>Pengaturan Notifikasi & Pesan Darurat</h3>
        
        <?php if (!empty($message)): ?>
           <script>
           Swal.fire({ icon: 'info', title: 'Notifikasi!', html: '<?= htmlspecialchars($message) ?>' });
           </script>
        <?php endif; ?>
        
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4 border-info">
                        <div class="card-header bg-info text-white">🔔 Notifikasi Tugas Harian</div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="active_duty" name="active_duty" <?= getSetting('before_duty', 'is_active', 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="active_duty" style="color:white;">Aktifkan Notifikasi</label>
                            </div>
                            <div class="mb-3">
                                <label for="offset_duty" class="form-label" style="color:white;">Waktu Pengiriman (Jam Sebelum Tugas)</label>
                                <input type="number" id="offset_duty" name="offset_duty" class="form-control" value="<?= $offset_duty_hours ?>" min="1" required>
                                <div class="form-text">Contoh: Isi **24** untuk dikirim 1 hari sebelum tugas dimulai.</div>
                            </div>
                            <div class="mb-3">
                                <label for="template_duty" class="form-label">Template Pesan</label>
                                <textarea id="template_duty" name="template_duty" class="form-control" rows="5" required><?= htmlspecialchars(getSetting('before_duty', 'message_template', "Selamat Pagi, [NAMA]!\n\nAnda memiliki tugas besok:\n- Peleton: [PELETON]\n- Regu: [REGU]\n- Pos: [POS]\n- Tugas: [TUGAS]\n- Tanggal Tugas: [TANGGAL]\n\nHarap siapkan diri!")) ?></textarea>
                                <div class="form-text">Gunakan placeholder: **[NAMA], [PELETON], [REGU], [POS], [TUGAS], [TANGGAL]**</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-warning " style="color:white; ">⏰ Notifikasi Apel/Siaga</div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="active_call" name="active_call" <?= getSetting('before_call', 'is_active', 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="active_call" style="color:white;">Aktifkan Notifikasi</label>
                            </div>
                            <div class="mb-3">
                                <label for="offset_call" class="form-label" style="color:white;">Waktu Pengiriman (Menit Sebelum Apel)</label>
                                <input type="number" id="offset_call" name="offset_call" class="form-control" value="<?= $offset_call_minutes ?>" min="1" required>
                                <div class="form-text">Contoh: Isi **30** untuk dikirim 30 menit sebelum apel.</div>
                            </div>
                            <div class="mb-3">
                                <label for="template_call" class="form-label" style="color:white;">Template Pesan</label>
                                <textarea id="template_call" name="template_call" class="form-control" rows="5" required><?= htmlspecialchars(getSetting('before_call', 'message_template', "Peringatan: Apel akan segera dimulai dalam [MENIT] menit!\n\n[NAMA] dari Peleton [PELETON], segera merapat ke [POS]!")) ?></textarea>
                                <div class="form-text">Gunakan placeholder: **[NAMA], [PELETON], [POS], [MENIT]**</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button name="update_settings" type="submit" class="btn btn-primary w-100">Simpan Pengaturan Notifikasi Otomatis</button>
        </form>

        <hr class="my-5">

        <div class="card border-danger">
            <div class="card-header bg-danger text-white" style="color:white;">🚨 Kirim Pesan Darurat ke Semua Pegawai</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="emergency_message" class="form-label">Isi Pesan Darurat</label>
                        <textarea id="emergency_message" name="emergency_message" class="form-control" rows="4" required placeholder="Contoh: SEMUA PETUGAS HARAP KEMBALI KE MARKAS SAAT INI JUGA. KODE MERAH."></textarea>
                    </div>
                    <button name="send_emergency" type="submit" class="btn btn-danger w-100" onclick="return confirm('Yakin ingin mengirim pesan darurat ini ke SEMUA petugas? Pastikan pesannya akurat.')">KIRIM SEKARANG</button>
                </form>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>