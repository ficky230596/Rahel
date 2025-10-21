<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}
$rows = $pdo->query('SELECT i.*, p.nama FROM izin i LEFT JOIN pegawai p ON i.pegawai_id=p.id ORDER BY i.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rekap Izin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <?php include 'inc_nav.php'; ?>
  <div class="container py-4">
    <h3>Rekap Izin/Cuti</h3>
    <table class="table table-sm">
      <thead>
        <tr>
          <th>ID</th>
          <th>Pegawai</th>
          <th>Jenis</th>
          <th>Periode</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= $r['jenis'] ?></td>
            <td><?= $r['tanggal_mulai'] ?> - <?= $r['tanggal_selesai'] ?></td>
            <td><?= $r['status'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="mt-3">Gunakan PHPSpreadsheet atau TCPDF untuk export (contoh library tidak termasuk).</p>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>