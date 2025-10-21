<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

if (isset($_POST['set_status'])) {
  $id = $_POST['id'];
  $status = $_POST['status'];
  $pdo->prepare('UPDATE izin SET status=? WHERE id=?')->execute([$status, $id]);
  header('Location: verifikasi.php');
  exit;
}

$rows = $pdo->query('SELECT i.*, p.nama FROM izin i LEFT JOIN pegawai p ON i.pegawai_id=p.id ORDER BY i.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verifikasi Izin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <?php include 'inc_nav.php'; ?>
  <div class="container py-4">
    <h3>Verifikasi Izin/Cuti</h3>
    <table class="table table-sm">
      <thead>
        <tr>
          <th>ID</th>
          <th>Pegawai</th>
          <th>Jenis</th>
          <th>Periode</th>
          <th>Alasan</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= $r['jenis'] ?></td>
            <td><?= $r['tanggal_mulai'] ?> - <?= $r['tanggal_selesai'] ?></td>
            <td><?= htmlspecialchars($r['alasan']) ?></td>
            <td><?= $r['status'] ?></td>
            <td>
              <form method="post" style="display:inline"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button name="set_status" value="diterima" class="btn btn-sm btn-success">Terima</button></form>
              <form method="post" style="display:inline"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button name="set_status" value="ditolak" class="btn btn-sm btn-danger">Tolak</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>