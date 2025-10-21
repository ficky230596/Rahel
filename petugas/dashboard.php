<?php
session_start();
require '../config/db.php';

// Cek autentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$id = $_SESSION['user_id'];

// Ambil data user saat ini untuk ditampilkan di sidebar
$user_stmt = $pdo->prepare('SELECT nama, role FROM pegawai WHERE id = ?');
$user_stmt->execute([$id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Menggunakan JOIN untuk mengambil nama Peleton, Regu, dan Pos
$rows = $pdo->prepare('
    SELECT 
        j.tanggal, 
        j.slot, 
        p.nama AS nama_peleton, 
        r.nama AS nama_regu, 
        pos.nama AS nama_pos 
    FROM jadwal j
    LEFT JOIN peleton p ON j.peleton_id = p.id
    LEFT JOIN regu r ON j.regu_id = r.id
    LEFT JOIN pos ON j.pos_id = pos.id
    WHERE j.pegawai_id = ? 
    ORDER BY j.tanggal ASC  /* <-- Pengurutan Tanggal Awal ke Akhir (ASCENDING) */
    LIMIT 10
');
$rows->execute([$id]);
$jadwals = $rows->fetchAll(PDO::FETCH_ASSOC);

// Tentukan halaman aktif untuk highlight sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Petugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: 0;
            /* Default visible in desktop */
            transition: margin .25s ease-out;
            background-color: #212529;
            width: 15rem;
        }

        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
        }

        #page-content-wrapper {
            min-width: 100vw;
            flex-grow: 1;
        }

        .wrapper {
            display: flex;
        }

        /* Style untuk menyembunyikan sidebar di mobile secara default */
        @media (max-width: 768px) {
            #sidebar-wrapper {
                margin-left: -15rem;
                position: fixed;
                z-index: 1000;
            }

            .wrapper.toggled #sidebar-wrapper {
                margin-left: 0;
            }

            #page-content-wrapper {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex wrapper" id="wrapper">

        <div class="border-right" id="sidebar-wrapper">
            <div class="sidebar-heading bg-primary text-white">
                <i class="fas fa-shield-alt"></i> PETUGAS DASH
            </div>
            <div class="list-group list-group-flush">

                <div class="list-group-item list-group-item-action bg-dark text-white-50">
                    <small>Halo,</small><br>
                    <?= htmlspecialchars($current_user['nama']) ?>
                </div>

                <a href="dashboard.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($current_page == 'dashboard.php' ? 'active' : '') ?>">
                    <i class="fas fa-calendar-alt me-2"></i> Jadwal Tugas
                </a>

                <a href="izin.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($current_page == 'izin.php' ? 'active' : '') ?>">
                    <i class="fas fa-file-signature me-2"></i> Pengajuan Izin
                </a>

                <a href="../index.php" class="list-group-item list-group-item-action bg-danger text-white mt-auto">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
        <div id="page-content-wrapper">

            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary d-block d-md-none" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="my-2 ms-auto me-3 d-none d-md-block">Dashboard Petugas</h5>
                </div>
            </nav>

            <div class="container-fluid py-4">

                <h3 class="mb-4">Jadwal Tugas Anda</h3>

                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5>Jadwal Mendatang (10 Entri Awal)</h5>
                    </div>
                    <div class="card-body">

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 50px;">No.</th>
                                        <th>Tanggal</th>
                                        <th>Slot</th>
                                        <th>Peleton</th>
                                        <th>Regu</th>
                                        <th>Pos Tugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($jadwals as $j): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $j['tanggal'] ?></td>
                                            <td><?= $j['slot'] ?></td>
                                            <td><?= htmlspecialchars($j['nama_peleton'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($j['nama_regu'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($j['nama_pos'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (empty($jadwals)): ?>
                            <div class="alert alert-info mt-4">
                                Anda belum memiliki jadwal tugas terbaru yang terdaftar.
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk Toggle Sidebar
        var el = document.getElementById("wrapper");
        var toggleButton = document.getElementById("menu-toggle");

        if (toggleButton) {
            toggleButton.onclick = function() {
                el.classList.toggle("toggled");
            };
        }
        // Atur sidebar agar defaultnya muncul di desktop
        if (window.innerWidth >= 768) {
            el.classList.add("toggled");
        }
    </script>
</body>

</html>