<?php
session_start();
// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../index.php");
  exit;
}

// Tambahkan logika untuk menampilkan nama user jika perlu, misalnya:
// $admin_name = $_SESSION['nama'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Damkar Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --damkar-red: #d62828;
            --damkar-dark: #212529;
            --text-light: #f8f9fa;
        }

        body {
            background-color: #e9ecef; /* Abu-abu terang untuk kontras */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* NAVBAR STYLING */
        .navbar {
            background-color: var(--damkar-red) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4); /* Bayangan kuat */
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: 1px;
            font-size: 1.6rem;
            color: var(--text-light) !important;
        }

        .btn-logout {
            background-color: var(--damkar-dark) !important;
            color: var(--text-light) !important;
            border: 2px solid var(--text-light);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background-color: var(--text-light) !important;
            color: var(--damkar-red) !important;
            border-color: var(--damkar-red);
        }

        /* DASHBOARD CONTENT STYLING */
        .dashboard-container {
            padding-top: 30px;
        }

        .dashboard-container h3 {
            color: var(--damkar-dark);
            font-weight: 700;
            border-bottom: 3px solid var(--damkar-red);
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        /* Menu Utama */
        .menu-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap; /* Memastikan responsif */
            gap: 20px;
        }
        
        .menu-item {
            flex: 0 0 calc(33.333% - 20px); /* 3 kolom per baris di desktop */
            min-width: 250px;
        }

        .menu-link {
            display: block;
            text-decoration: none;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-left: 5px solid var(--damkar-red); /* Garis merah penanda */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            color: var(--damkar-dark);
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .menu-link:hover {
            border-left-color: var(--damkar-dark-red);
            background-color: #fff3f3; /* Sedikit merah muda saat hover */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px); /* Efek angkat */
        }
        
        /* Media Query untuk Responsif */
        @media (max-width: 992px) {
            .menu-item {
                flex: 0 0 calc(50% - 20px); /* 2 kolom di tablet */
            }
        }

        @media (max-width: 576px) {
            .menu-item {
                flex: 0 0 100%; /* 1 kolom di mobile */
            }
        }

    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-danger">
        <div class="container-fluid container">
            <a class="navbar-brand" href="dashboard.php">Damkar Scheduler</a>
            <a href="../index.php" class="btn btn-light btn-sm btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container dashboard-container">
        <h3>Dashboard Admin</h3>
        
        <ul class="menu-list">
            <li class="menu-item"><a href="pegawai.php" class="menu-link">👨‍🚒 CRUD Pegawai</a></li>
            <li class="menu-item"><a href="peleton.php" class="menu-link">🚩 CRUD Peleton</a></li>
            <li class="menu-item"><a href="regu.php" class="menu-link">👥 CRUD Regu</a></li>
            <li class="menu-item"><a href="pos.php" class="menu-link">🏠 CRUD Pos Jaga</a></li>
            <li class="menu-item"><a href="jadwal.php" class="menu-link">📅 Jadwal Piket</a></li>
            <li class="menu-item"><a href="verifikasi.php" class="menu-link">📄 Verifikasi Izin</a></li>
            <li class="menu-item"><a href="laporan.php" class="menu-link">📊 Rekap Laporan</a></li>
            </ul>
        
        <div class="mt-5 p-4 bg-white rounded shadow-sm">
            <h4>Selamat Datang, Admin!</h4>
            <p class="text-secondary">Gunakan menu di atas untuk mengatur data master, jadwal, dan proses verifikasi di sistem Damkar Scheduler.</p>
        </div>
        
    </div>
    
    <footer class="bg-dark text-white text-center py-3 mt-5">
        &copy; <?= date("Y") ?> Damkar Scheduler. All rights reserved.
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>