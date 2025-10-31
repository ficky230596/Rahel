<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil assignment user dari pegawai_regu (hanya regu_id dan pos_id)
$stmt = $pdo->prepare('SELECT DISTINCT regu_id, pos_id FROM pegawai_regu WHERE pegawai_id = ?');
$stmt->execute([$user_id]);
$user_assignment = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jika user tidak ada di regu
if (!$user_assignment) {
    echo '<div class="alert alert-warning">Anda belum memiliki regu/pos yang terdaftar.</div>';
    exit;
}

// Ambil data regu, pos, peleton berdasarkan assignment
// Ambil assignment user dari pegawai_regu
$stmt = $pdo->prepare('SELECT DISTINCT regu_id, pos_id FROM pegawai_regu WHERE pegawai_id = ?');
$stmt->execute([$user_id]);
$user_assignment = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil nama regu, pos, dan peleton
$cards = [];
foreach ($user_assignment as $ua) {
    $stmt2 = $pdo->prepare('
        SELECT 
            r.id AS regu_id,
            pos.id AS pos_id,
            r.nama AS nama_regu,
            pos.nama AS nama_pos,
            p.nama AS nama_peleton
        FROM regu r
        LEFT JOIN peleton p ON r.peleton_id = p.id
        LEFT JOIN pos ON r.pos_id = pos.id
        WHERE r.id = ?
    ');
    $stmt2->execute([$ua['regu_id']]);
    $cards[] = $stmt2->fetch(PDO::FETCH_ASSOC);
}

?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-hover {
            cursor: pointer;
            transition: 0.2s;
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <h3 class="mb-4">Regu & Pos Tugas Anda</h3>

        <div class="row">
            <?php foreach ($cards as $c): ?>
                <div class="col-md-4 mb-3">
                    <div class="card card-hover h-100"
                        data-bs-toggle="modal"
                        data-bs-target="#teamModal"
                        data-regu="<?= $c['regu_id'] ?>"
                        data-pos="<?= $c['pos_id'] ?>">
                        <div class="card-body">
                            <p><strong>Peleton:</strong> <?= htmlspecialchars($c['nama_peleton'] ?? 'N/A') ?></p>
                            <p><strong>Regu:</strong> <?= htmlspecialchars($c['nama_regu'] ?? 'N/A') ?></p>
                            <p><strong>Pos Tugas:</strong> <?= htmlspecialchars($c['nama_pos'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal untuk anggota regu/pos -->
    <div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rekan Regu/Pos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="teamModalBody">
                    <p>Memuat data...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            $('.card-hover').on('click', function() {
                var regu_id = $(this).data('regu');
                var pos_id = $(this).data('pos');
                $('#teamModalBody').html('<p>Memuat data...</p>');
                $.ajax({
                    url: 'get_team.php',
                    type: 'POST',
                    data: {
                        regu_id: regu_id,
                        pos_id: pos_id
                    },
                    success: function(data) {
                        $('#teamModalBody').html(data);
                    },
                    error: function() {
                        $('#teamModalBody').html('<p class="text-danger">Gagal memuat data.</p>');
                    }
                });
            });
        });
    </script>
</body>

</html>