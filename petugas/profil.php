<?php
$page_title = "Profil Saya";
include 'header.php';
include 'sidebar.php';

$user_id = $_SESSION['user_id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM pegawai WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<style>
    .profile-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .card-profile {
        flex: 1 1 250px;
        display: flex;
        align-items: center;
        padding: 15px 20px;
        background: linear-gradient(145deg, #1e293b, #0f172a);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card-profile:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
    }

    .card-profile i {
        font-size: 24px;
        color: #ef4444;
        margin-right: 15px;
        min-width: 30px;
        text-align: center;
    }

    .card-text {
        font-size: 16px;
        color: #f8fafc;
        word-break: break-word;
    }
</style>
<div class="container mt-4">
    <h2 class="mb-4">Profil Saya</h2>
    <div class="row g-3">
        <?php
        // Field + ikon mapping
        $fields = [
            'username' => ['label' => 'Username', 'icon' => 'fa-user'],
            'nama' => ['label' => 'Nama', 'icon' => 'fa-id-card'],
            'jenis_kelamin' => ['label' => 'Jenis Kelamin', 'icon' => 'fa-venus-mars'],
            'nip' => ['label' => 'NIP', 'icon' => 'fa-id-badge'],
            'status_kepegawaian' => ['label' => 'Status Kepegawaian', 'icon' => 'fa-certificate'],
            'no_hp' => ['label' => 'No HP', 'icon' => 'fa-phone'],
            'golongan' => ['label' => 'Golongan', 'icon' => 'fa-layer-group'],
            'ruang' => ['label' => 'Ruang', 'icon' => 'fa-door-closed'],
            'jabatan' => ['label' => 'Jabatan', 'icon' => 'fa-briefcase'],
            'tugas' => ['label' => 'Tugas', 'icon' => 'fa-tasks'],
            'role' => ['label' => 'Role', 'icon' => 'fa-user-shield'],
        ];

        foreach ($fields as $key => $val):
        ?>
            <div class="col-md-4">
                <div class="card profile-card p-3 d-flex align-items-center">
                    <i class="fas <?= $val['icon'] ?> profile-icon me-3"></i>
                    <div>
                        <h6 class="mb-1"><?= $val['label'] ?></h6>
                        <p class="mb-0"><?= htmlspecialchars($user_data[$key] ?? '-') ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>