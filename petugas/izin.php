<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}
$id = $_SESSION['user_id'];
if (isset($_POST['submit'])) {
  $jenis = $_POST['jenis'];
  $mulai = $_POST['mulai'];
  $selesai = $_POST['selesai'];
  $alasan = $_POST['alasan'];
  $pdo->prepare('INSERT INTO izin (pegawai_id,jenis,tanggal_mulai,tanggal_selesai,alasan) VALUES (?,?,?,?,?)')->execute([$id, $jenis, $mulai, $selesai, $alasan]);
  $msg = 'Izin dikirim. Menunggu verifikasi.';
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

<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid"><a class="navbar-brand" href="dashboard.php">Petugas</a>
      <div class="collapse navbar-collapse"></div><a class="nav-link text-white" href="../index.php">Logout</a>
    </div>
  </nav>
  <div class="container py-4">
    <h3>Ajukan Izin / Cuti</h3>
    <?php if (!empty($msg)): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post" class="row g-2">
      <div class="col-md-4"><label>Jenis</label><select name="jenis" class="form-control">
          <option value="khusus_h1">Izin Khusus (H-1)</option>
          <option value="umum_cuti">Izin Umum / Cuti</option>
          <option value="sakit">Sakit</option>
        </select></div>
      <div class="col-md-3"><label>Tanggal Mulai</label><input type="date" name="mulai" class="form-control"></div>
      <div class="col-md-3"><label>Tanggal Selesai</label><input type="date" name="selesai" class="form-control"></div>
      <div class="col-md-12"><label>Alasan</label><textarea name="alasan" class="form-control"></textarea></div>
      <div class="col-md-12"><button name="submit" class="btn btn-primary mt-2">Kirim</button></div>
    </form>
  </div>
</body>

</html>