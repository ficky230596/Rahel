<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$id = $_SESSION['user_id'];
$rows = $pdo->prepare('SELECT j.*, pos.nama as posnama FROM jadwal j LEFT JOIN pos ON j.pos_id=pos.id WHERE j.pegawai_id=? ORDER BY tanggal DESC');
$rows->execute([$id]);
$jadwals = $rows->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Jadwal Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid"><a class="navbar-brand" href="dashboard.php">Petugas</a>
            <div class="collapse navbar-collapse"></div><a class="nav-link text-white" href="../index.php">Logout</a>
        </div>
    </nav>
    <div class="container py-4">
        <h3>Jadwal Saya</h3>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Slot</th>
                    <th>Pos</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jadwals as $j): ?><tr>
                        <td><?= $j['tanggal'] ?></td>
                        <td><?= $j['slot'] ?></td>
                        <td><?= htmlspecialchars($j['posnama']) ?></td>
                        <td><?= $j['status'] ?></td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>