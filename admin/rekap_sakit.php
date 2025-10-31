<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

// Ambil filter dari query
$filter_pegawai = $_GET['pegawai'] ?? '';
$filter_jenis   = $_GET['jenis'] ?? '';
$filter_status  = $_GET['status'] ?? '';
$filter_mulai   = $_GET['tanggal_mulai'] ?? '';
$filter_selesai = $_GET['tanggal_selesai'] ?? '';

$sql = "SELECT i.*, p.nama FROM izin i LEFT JOIN pegawai p ON i.pegawai_id=p.id WHERE 1=1";
$params = [];

// Filter pegawai
if ($filter_pegawai) {
  $sql .= " AND p.nama LIKE ?";
  $params[] = "%$filter_pegawai%";
}
// Filter jenis
if ($filter_jenis) {
  $sql .= " AND i.jenis = ?";
  $params[] = $filter_jenis;
}
// Filter status
if ($filter_status) {
  $sql .= " AND i.status = ?";
  $params[] = $filter_status;
}
// Filter tanggal mulai
if ($filter_mulai) {
  $sql .= " AND i.tanggal_mulai >= ?";
  $params[] = $filter_mulai;
}
// Filter tanggal selesai
if ($filter_selesai) {
  $sql .= " AND i.tanggal_selesai <= ?";
  $params[] = $filter_selesai;
}

$sql .= " ORDER BY i.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="rekap_izin.csv"');
  $output = fopen('php://output', 'w');
  fputcsv($output, ['No', 'Pegawai', 'Jenis', 'Periode', 'Alasan', 'Lampiran', 'Status']);
  $no = 1;
  foreach ($rows as $r) {
    $lampiran = $r['lampiran'] ? "uploads/izin/" . basename($r['lampiran']) : "-";
    fputcsv($output, [
      $no++,
      $r['nama'],
      $r['jenis'],
      $r['tanggal_mulai'] . ' - ' . $r['tanggal_selesai'],
      $r['alasan'],
      $lampiran,
      $r['status']
    ]);
  }
  fclose($output);
  exit;
}

include 'header.php';
include 'sidebar.php';
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rekap Izin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin/rekap.css">
</head>

<body>
  <div class="container py-4">
    <h3>Rekap Izin/Cuti</h3>

    <!-- Tombol untuk menampilkan/menyembunyikan filter -->
    <button id="toggleFilter" class="btn btn-secondary mb-3">Tampilkan Filter</button>

    <!-- Filter collapsible -->
    <form method="get" class="row g-2 mb-3" id="filterForm" style="display:none;">
      <div class="col-md-3">
        <label class="form-label">Filter Pegawai</label>
        <input type="text" name="pegawai" placeholder="Cari pegawai..." value="<?= htmlspecialchars($filter_pegawai) ?>" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Filter Jenis</label>
        <select name="jenis" class="form-control">
          <option value="">Semua Jenis</option>
          <option value="izin" <?= $filter_jenis === 'izin' ? 'selected' : '' ?>>Izin</option>
          <option value="sakit" <?= $filter_jenis === 'sakit' ? 'selected' : '' ?>>Sakit</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Filter Status</label>
        <select name="status" class="form-control">
          <option value="">Semua Status</option>
          <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Menunggu</option>
          <option value="diterima" <?= $filter_status === 'diterima' ? 'selected' : '' ?>>Diterima</option>
          <option value="ditolak" <?= $filter_status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Tanggal Mulai</label>
        <input type="date" name="tanggal_mulai" value="<?= $filter_mulai ?>" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">Tanggal Selesai</label>
        <input type="date" name="tanggal_selesai" value="<?= $filter_selesai ?>" class="form-control">
      </div>
      <div class="col-md-1 d-grid align-self-end">
        <button class="btn btn-primary">Filter</button>
      </div>
      <div class="col-md-2 d-grid align-self-end">
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success">Download CSV</a>
      </div>
      <div class="col-md-1 d-grid align-self-end">
        <a href="rekap_sakit.php" class="btn btn-warning">Reset</a>
      </div>
    </form>

    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th>No</th>
          <th>Pegawai</th>
          <th>Jenis</th>
          <th>Periode</th>
          <th>Alasan</th>
          <th>Lampiran</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1;
        foreach ($rows as $r): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['jenis']) ?></td>
            <td><?= $r['tanggal_mulai'] ?> - <?= $r['tanggal_selesai'] ?></td>
            <td><?= htmlspecialchars($r['alasan']) ?></td>
            <td>
              <?php if (!empty($r['lampiran'])): ?>
                <a href="../<?= $r['lampiran'] ?>" download="<?= basename($r['lampiran']) ?>">Download</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= ucfirst($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script>
    // Toggle filter
    document.getElementById('toggleFilter').addEventListener('click', function() {
      const form = document.getElementById('filterForm');
      form.style.display = form.style.display === 'none' ? 'flex' : 'none';
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>