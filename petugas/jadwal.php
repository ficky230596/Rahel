<?php
session_start();
require '../config/db.php';

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'petugas') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data jadwal petugas
$stmt = $pdo->prepare("
    SELECT j.id, j.tanggal, j.status,
           p.nama AS peleton, r.nama AS regu, ps.nama AS pos
    FROM jadwal j
    LEFT JOIN peleton p ON j.peleton_id = p.id
    LEFT JOIN regu r ON j.regu_id = r.id
    LEFT JOIN pos ps ON j.pos_id = ps.id
    WHERE j.pegawai_id = ?
    ORDER BY j.tanggal DESC
");
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Piket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/jadwal.css">
</head>
<style>
    /* === JADWAL PAGE THEME (DAMKAR STYLE) === */
    body {
        background: #0f172a;
        color: #f8fafc;
        font-family: 'Inter', sans-serif;
        overflow-x: hidden;
    }

    .jadwal-container {
        margin-left: 250px;
        transition: margin-left 0.3s ease;
    }

    /* Jika sidebar disembunyikan di mobile */
    @media (max-width: 991px) {
        .jadwal-container {
            margin-left: 0;
        }
    }

    /* HEADER */
    .page-title {
        font-weight: 700;
        color: #f1f5f9;
        border-left: 5px solid #ef4444;
        padding-left: 10px;
    }

    .search-box {
        max-width: 300px;
        margin-top: 10px;
        background: #1e293b;
        border: 1px solid #334155;
        color: #f8fafc;
        border-radius: 10px;
        transition: 0.3s;
    }

    .search-box:focus {
        border-color: #ef4444;
        box-shadow: 0 0 5px rgba(239, 68, 68, 0.4);
    }

    /* TABLE */
    .table {
        background: rgba(30, 41, 59, 0.7);
        border-radius: 12px;
        overflow: hidden;
    }

    .table th {
        background-color: #b30000;
        color: #fff;
        font-weight: 600;
    }

    .table td {
        color: #e2e8f0;
    }

    .table-hover tbody tr:hover {
        background: rgba(239, 68, 68, 0.2);
        transition: 0.2s;
    }

    /* BADGES */
    .badge {
        font-size: 0.85rem;
        padding: 6px 10px;
        border-radius: 8px;
    }

    /* CONTENT HEADER */
    .content-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
    }

    /* FOOTER */
    footer {
        background: #1e293b;
        text-align: center;
        color: #94a3b8;
        padding: 10px;
        font-size: 0.9rem;
        margin-top: 30px;
        border-top: 1px solid #334155;
    }
</style>

<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="container-fluid py-4 jadwal-container">
        <div class="content-header">
            <h3 class="page-title">📅 Jadwal Piket Anda</h3>
            <input type="text" id="searchInput" class="form-control search-box" placeholder="Cari tanggal, peleton, atau pos...">
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-dark table-striped table-hover align-middle text-center">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Peleton</th>
                        <th>Regu</th>
                        <th>Pos Jaga</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="jadwalTable">
                    <?php
                    $no = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($r['peleton'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['regu'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['pos'] ?? '-') ?></td>
                            <td>
                                <?php if ($r['status'] == 'aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php elseif ($r['status'] == 'selesai'): ?>
                                    <span class="badge bg-secondary">Selesai</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?= htmlspecialchars($r['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // 🔍 Fitur pencarian real-time
        $(document).ready(function() {
            $("#searchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#jadwalTable tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
    </script>
</body>

</html>