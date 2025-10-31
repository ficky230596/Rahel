<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require '../config/db.php'; // pastikan koneksi $pdo tersedia
include 'header.php';
include 'sidebar.php';

// Hitung jumlah data dari setiap tabel
$total_pegawai = $pdo->query("SELECT COUNT(*) FROM pegawai")->fetchColumn();
$total_peleton = $pdo->query("SELECT COUNT(*) FROM peleton")->fetchColumn();
$total_regu = $pdo->query("SELECT COUNT(*) FROM regu")->fetchColumn();
$total_pos = $pdo->query("SELECT COUNT(*) FROM pos")->fetchColumn();
?>
<link rel="stylesheet" href="../assets/css/admin/dashboard.css">
<div class="container-fluid mt-4 dashboard-container">
    <h3 class="fw-bold mb-4" style="color: white;">Dashboard Admin</h3>

    <!-- 📊 Kartu Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <a href="pegawai.php" class="text-decoration-none d-block">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pegawai</h5>
                        <p class="card-text display-6 fw-bold"><?= $total_pegawai ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Peleton</h5>
                    <p class="card-text display-6 fw-bold"><?= $total_peleton ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Regu</h5>
                    <p class="card-text display-6 fw-bold"><?= $total_regu ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Pos Jaga</h5>
                    <p class="card-text display-6 fw-bold"><?= $total_pos ?></p>
                </div>
            </div>
        </div>
    </div>

</div>