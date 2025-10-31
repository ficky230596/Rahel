<?php
session_start();
require '../config/db.php';
require 'send_whatsapp.php'; // fungsi sendWA()

/**
 * Pegawai kirim izin/sakit
 */
if (isset($_POST['request_leave'])) {
    $pegawai_id = $_SESSION['user_id'];
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis']; // izin / sakit
    $alasan = $_POST['alasan'];

    $stmt = $pdo->prepare("INSERT INTO notifikasi (pegawai_id, jenis, tanggal, alasan, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$pegawai_id, $jenis, $tanggal, $alasan]);

    // Kirim notifikasi ke admin
    $admins = $pdo->query("SELECT nama, no_hp FROM pegawai WHERE role='admin' AND no_hp IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($admins as $admin) {
        $message = "Notifikasi $jenis dari pegawai: " . $_SESSION['user_nama'] . "\nTanggal: $tanggal\nAlasan: $alasan";
        sendWA($admin['no_hp'], $message);
    }

    $_SESSION['message'] = "Permintaan $jenis berhasil dikirim ke admin.";
    header("Location: dashboard.php");
    exit;
}

/**
 * Admin konfirmasi izin/sakit
 */
if (isset($_POST['confirm_leave'])) {
    $notif_id = $_POST['notif_id'];
    $status = $_POST['status']; // approve / reject
    $stmt = $pdo->prepare("UPDATE notifikasi SET status = ? WHERE id = ?");
    $stmt->execute([$status, $notif_id]);

    // Ambil info pegawai
    $notif = $pdo->prepare("SELECT n.*, p.nama as pegawai_nama, p.no_hp as target_number FROM notifikasi n JOIN pegawai p ON n.pegawai_id = p.id WHERE n.id = ?");
    $notif->execute([$notif_id]);
    $row = $notif->fetch(PDO::FETCH_ASSOC);

    // Kirim balasan ke pegawai
    $message = "Permintaan " . $row['jenis'] . " Anda untuk tanggal " . $row['tanggal'] . " telah " . ($status == 'approve' ? 'disetujui' : 'ditolak');
    sendWA($row['target_number'], $message);

    $_SESSION['message'] = "Balasan dikirim ke pegawai.";
    header("Location: admin_notifications.php");
    exit;
}

/**
 * Admin kirim pesan darurat ke semua pegawai
 */
if (isset($_POST['send_emergency'])) {
    $emergency_msg = $_POST['emergency_message'];
    $pegawais = $pdo->query("SELECT nama, no_hp FROM pegawai WHERE role='petugas' AND no_hp IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($pegawais as $p) {
        sendWA($p['no_hp'], $emergency_msg);
    }
    $_SESSION['message'] = "Pesan darurat berhasil dikirim ke semua pegawai.";
    header("Location: admin_notifications.php");
    exit;
}
