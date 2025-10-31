<?php
session_start();
require '../config/db.php'; // koneksi PDO $pdo

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}



// Ambil data jadwal
$jadwals = $pdo->query("
    SELECT j.*, p.nama as pegawai, pos.nama as posnama, r.nama as regu, pel.nama as peleton
    FROM jadwal j
    LEFT JOIN pegawai p ON j.pegawai_id = p.id
    LEFT JOIN pos ON j.pos_id = pos.id
    LEFT JOIN regu r ON j.regu_id = r.id
    LEFT JOIN peleton pel ON j.peleton_id = pel.id
    ORDER BY j.tanggal DESC, peleton, regu
")->fetchAll(PDO::FETCH_ASSOC);

// Filter tanggal
$filter_date = $_GET['tanggal'] ?? '';
if ($filter_date) {
    $jadwals = array_filter($jadwals, function ($j) use ($filter_date) {
        return strpos($j['tanggal'], $filter_date) !== false;
    });
}

// Download CSV
if (isset($_GET['download'])) {
    $filename = "rekap_jadwal_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'Tanggal', 'Peleton', 'Regu', 'Pos', 'Pegawai', 'Status']);

    $no = 1;
    foreach ($jadwals as $j) {
        fputcsv($output, [
            $no++,
            $j['tanggal'],
            $j['peleton'],
            $j['regu'],
            $j['posnama'],
            $j['pegawai'] ?? '---',
            $j['status']
        ]);
    }
    fclose($output);
    exit;
}
include 'header.php';
include 'sidebar.php';
?>
<link rel="stylesheet" href="../assets/css/admin/rekap_jadwal.css">
<div class="container py-4">
    <h3>Rekap Jadwal</h3>

    <form class="row g-3 mb-3" method="get">
        <div class="col-md-3">
            <label for="tanggal" class="form-label">Filter Tanggal</label>
            <input type="month" name="tanggal" id="tanggal" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">Filter</button>
            <a href="rekap_jadwal.php" class="btn btn-secondary">Reset</a>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <a href="?<?= $filter_date ? "tanggal=$filter_date&" : "" ?>download=1" class="btn btn-success">Download CSV</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Peleton</th>
                    <th>Regu</th>
                    <th>Pos</th>
                    <th>Pegawai</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($jadwals as $j): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($j['tanggal']) ?></td>
                        <td><?= htmlspecialchars($j['peleton']) ?></td>
                        <td><?= htmlspecialchars($j['regu']) ?></td>
                        <td><?= htmlspecialchars($j['posnama']) ?></td>
                        <td><?= htmlspecialchars($j['pegawai'] ?? '---') ?></td>
                        <td><?= htmlspecialchars($j['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>