<?php
session_start();
require '../config/db.php';
require '../admin/send_whatsapp.php'; // fungsi sendWA()

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
  header('Location: ../index.php');
  exit;
}

include 'header.php';
include 'sidebar.php';

$id = $_SESSION['user_id'];
$msg = '';

if (isset($_POST['submit'])) {
  $jenis = $_POST['jenis']; // izin / sakit
  $mulai = $_POST['mulai'];
  $selesai = $_POST['selesai'];
  $alasan = $_POST['alasan'];

  // === PROSES UPLOAD FILE ===
  $file_path = null;
  if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = [
      'image/jpeg',
      'image/png',
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $file_type = $_FILES['lampiran']['type'];
    $file_name = basename($_FILES['lampiran']['name']);
    $file_tmp = $_FILES['lampiran']['tmp_name'];

    if (in_array($file_type, $allowed_types)) {
      $upload_dir = '../uploads/izin/';
      if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

      $new_name = uniqid('izin_') . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
      $target_path = $upload_dir . $new_name;

      if (move_uploaded_file($file_tmp, $target_path)) {
        $file_path = 'uploads/izin/' . $new_name;
      } else {
        $msg = 'Gagal mengunggah file.';
      }
    } else {
      $msg = 'Format file tidak diizinkan (Hanya JPG, PNG, PDF, DOC, DOCX).';
    }
  }

  // === SIMPAN KE DATABASE `izin` ===
  $stmt = $pdo->prepare("INSERT INTO izin (pegawai_id, jenis, tanggal_mulai, tanggal_selesai, alasan, lampiran, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
  $stmt->execute([$id, $jenis, $mulai, $selesai, $alasan, $file_path]);

  $msg = '✅ Permintaan Anda telah dikirim ke admin.';

  // === AMBIL NAMA PEGAWAI ===
  $user_stmt = $pdo->prepare("SELECT nama FROM pegawai WHERE id = ?");
  $user_stmt->execute([$id]);
  $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
  $nama_user = $user['nama'] ?? 'Pegawai';

  // === KIRIM NOTIFIKASI KE SEMUA ADMIN ===
  $admins = $pdo->query("SELECT nama, no_hp FROM pegawai WHERE role='admin' AND no_hp IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

  foreach ($admins as $admin) {
    $pesan = "📢 *PENGAJUAN $jenis*\n"
      . "👤 Pegawai: $nama_user\n"
      . "📅 Tanggal: $mulai s/d $selesai\n"
      . "📝 Alasan: $alasan\n"
      . ($file_path ? "📎 Lampiran: tersedia" : "📎 Lampiran: tidak ada");

    sendWA($admin['no_hp'], $pesan);
  }
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ajukan Izin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<style>
  body {
    background: #0f172a;
    color: #f1f5f9;
    font-family: "Inter", sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  h3 {
    color: #f8fafc;
    font-weight: 700;
    border-left: 5px solid #ef4444;
    padding-left: 10px;
    margin-bottom: 25px;
  }

  .container {
    margin-top: 80px;
    background: rgba(30, 41, 59, 0.7);
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
    max-width: 800px;
  }

  form label {
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 5px;
  }

  form .form-control,
  form select,
  form textarea {
    background: #1e293b;
    color: #f8fafc;
    border: 1px solid #334155;
    border-radius: 10px;
  }

  form .form-control:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.25);
  }

  button.btn-primary {
    background: linear-gradient(90deg, #b30000, #ef4444);
    border: none;
    border-radius: 10px;
    font-weight: 600;
    padding: 10px 20px;
    transition: 0.3s;
  }

  button.btn-primary:hover {
    background: linear-gradient(90deg, #ef4444, #b30000);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
  }

  .alert-success {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid #10b981;
    border-radius: 8px;
  }

  .alert-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid #f87171;
    border-radius: 8px;
  }

  @media (max-width: 768px) {
    .container {
      margin-top: 60px;
      padding: 20px;
    }

    button.btn-primary {
      width: 100%;
    }
  }
</style>

<body>
  <div class="container py-4">
    <h3>Ajukan Izin / Sakit</h3>
    <?php if (!empty($msg)): ?>
      <div class="alert <?= strpos($msg, 'Gagal') !== false ? 'alert-danger' : 'alert-success' ?>">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="row g-3">
      <div class="col-md-4">
        <label>Jenis</label>
        <select name="jenis" class="form-control" required>
          <option value="izin">Izin</option>
          <option value="sakit">Sakit</option>
        </select>
      </div>
      <div class="col-md-3">
        <label>Tanggal Mulai</label>
        <input type="date" name="mulai" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label>Tanggal Selesai</label>
        <input type="date" name="selesai" class="form-control" required>
      </div>
      <div class="col-md-12">
        <label>Alasan</label>
        <textarea name="alasan" class="form-control" rows="3" required></textarea>
      </div>
      <div class="col-md-12">
        <label>Lampiran (Opsional)</label>
        <input type="file" name="lampiran" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
      </div>
      <div class="col-md-12">
        <button name="submit" class="btn btn-primary mt-2">Kirim</button>
      </div>
    </form>
  </div>
</body>

</html>