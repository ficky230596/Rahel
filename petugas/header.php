<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ASUMSI file db.php mendefinisikan koneksi PDO sebagai $pdo
include "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// 1. INISIALISASI VARIABEL 
$nama_user = 'Pengguna DAMKAR';
$user_id = $_SESSION['user_id'];

// 2. LOGIKA PENGAMBILAN DATA MENGGUNAKAN PDO
// Pastikan variabel koneksi Anda adalah $pdo
if (isset($pdo) && $user_id) {
    try {
        // Query menggunakan PDO (mengambil nama dari tabel 'pegawai' berdasarkan 'id')
        $stmt = $pdo->prepare("SELECT nama FROM pegawai WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 3. SETELAH BERHASIL, TIMPA NILAI DEFAULT
            $nama_user = htmlspecialchars($user['nama']);
        }
    } catch (PDOException $e) {
        // PENTING: Untuk lingkungan PRODUKSI, hapus baris ini
        // echo "Gagal mengambil data user: " . $e->getMessage();
    }
} else {
    // PENTING: Untuk lingkungan PRODUKSI, hapus baris ini
    // echo "ERROR: Koneksi PDO ($pdo) tidak tersedia atau User ID ($user_id) kosong.";
}
?>

<!DOCTYPE html>

<!DOCTYPE html>
<html lang="id">
<!DOCTYPE html>
<html lang="id">
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard DAMKAR'; ?></title>

    <!-- Bootstrap & jQuery -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom DAMKAR Theme -->
    <style>
        body {
            background: #0f172a;
            color: #fff;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        .navbar {
            background: linear-gradient(90deg, #b30000, #dc2626);
        }

        .navbar-brand {
            font-weight: bold;
            color: #fff !important;
            letter-spacing: 0.5px;
            position: relative;
            left: 60px;
        }

        .navbar .btn-logout {
            background: #ef4444ff;
            color: #ffffffff;
            font-weight: 600;
            border: 10px;
            border: 3px solid #ffffffff;
        }

        .navbar .btn-logout:hover {
            background: #41dc26ff;
        }

        .content-wrapper {
            flex-grow: 1;
            padding: 20px;
            overflow-x: hidden;
        }

        .card {
            border: none;
            border-radius: 16px;
            background: linear-gradient(145deg, #1e293b, #0f172a);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            color: #fff;
            text-align: center;
            padding: 20px;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .card h5 {
            margin-bottom: 10px;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .card.peleton {
            border-top: 5px solid #ef4444;
        }

        .card.regu {
            border-top: 5px solid #f59e0b;
        }

        .card.pos {
            border-top: 5px solid #10b981;
        }

        .no-schedule {
            text-align: center;
            font-size: 1.2rem;
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <!-- Brand dengan logo -->
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../assets/img/logo.png" alt="Logo DAMKAR"
                    style="height: 38px; width: auto; margin-right: 10px; border-radius: 6px;">
                <span class="fw-bold">DAMKAR Dashboard</span>
            </a>

            <!-- Tombol Logout -->
            <div class="d-flex align-items-center">
                <span class="me-3 d-none d-sm-inline" style="color: #fff; font-weight: 500;">
                    Halo, <?= $nama_user; ?>
                </span>

                <a href="../logout.php"
                    class="btn btn-logout btn-sm"
                    onclick="return confirm('Yakin ingin logout?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>


    <div class="content-wrapper mt-5">