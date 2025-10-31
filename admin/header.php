<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Damkar Scheduler</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS Utama -->
    <link rel="stylesheet" href="../assets/css/admin/header.css">
    <link rel="stylesheet" href="../assets/css/admin/sidebar.css">
    <link rel="stylesheet" href="../assets/css/admin/footer.css">




</head>

<body>
    <!-- 🔥 Navbar Fixed -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/img/logo.png" alt="Logo Damkar">
                Damkar Minahasa 
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto d-lg-none">
                    <li class="nav-item"><a class="nav-link" href="pegawai.php">Pegawai</a></li>
                    <li class="nav-item"><a class="nav-link" href="peleton.php">Peleton</a></li>
                    <li class="nav-item"><a class="nav-link" href="regu.php">Regu</a></li>
                    <li class="nav-item"><a class="nav-link" href="pos.php">Pos</a></li>
                    <li class="nav-item"><a class="nav-link" href="jadwal.php">Jadwal</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link logout-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </nav>

    <!-- 🔽 Tambahkan spacer agar konten tidak tertutup navbar -->
    <div class="header-spacer"></div>