<?php
session_start();
require '../config/db.php'; // Pastikan path ke db.php sudah benar

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Tambah Regu
if (isset($_POST['create'])) {
    $peleton_id = $_POST['peleton_id'];
    $pos_id = $_POST['pos_id']; // <-- BARU: Ambil ID Pos
    $nama = $_POST['nama'];
    
    // UPDATE QUERY: Tambahkan pos_id
    $stmt = $pdo->prepare("INSERT INTO regu (peleton_id, pos_id, nama) VALUES (?, ?, ?)");
    $stmt->execute([$peleton_id, $pos_id, $nama]);
    
    header('Location: regu.php');
    exit;
}

// Edit Regu
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $peleton_id = $_POST['peleton_id'];
    $pos_id = $_POST['pos_id']; // <-- BARU: Ambil ID Pos
    $nama = $_POST['nama'];
    
    // UPDATE QUERY: Tambahkan pos_id
    $stmt = $pdo->prepare("UPDATE regu SET peleton_id=?, pos_id=?, nama=? WHERE id=?");
    $stmt->execute([$peleton_id, $pos_id, $nama, $id]);
    
    header('Location: regu.php');
    exit;
}

// Hapus Regu
if (isset($_GET['hapus_regu'])) {
    $id = $_GET['hapus_regu'];
    $pdo->prepare("DELETE FROM regu WHERE id=?")->execute([$id]);
    header('Location: regu.php');
    exit;
}

// Tambah Anggota Regu
if (isset($_POST['tambah_anggota'])) {
    $regu_id = $_POST['regu_id'];
    $pegawai_id = $_POST['pegawai_id'];
    $stmt = $pdo->prepare("INSERT INTO pegawai_regu (regu_id, pegawai_id) VALUES (?, ?)");
    $stmt->execute([$regu_id, $pegawai_id]);
    header('Location: regu.php');
    exit;
}

// Hapus Anggota
if (isset($_GET['hapus_anggota'])) {
    $id = $_GET['hapus_anggota'];
    $pdo->prepare("DELETE FROM pegawai_regu WHERE id=?")->execute([$id]);
    header('Location: regu.php');
    exit;
}

// Ambil data Pos (ASUMSI NAMA TABEL: pos)
$pos = $pdo->query("SELECT id, nama FROM pos ORDER BY nama ASC")->fetchAll();

// Ambil data peleton
$peletons = $pdo->query("SELECT id, nama FROM peleton ORDER BY id DESC")->fetchAll();

// Ambil data regu (UPDATE: JOIN dengan tabel 'pos')
$rows = $pdo->query("SELECT r.*, p.nama as peleton_nama, ps.nama as pos_nama 
                     FROM regu r 
                     LEFT JOIN peleton p ON r.peleton_id=p.id 
                     LEFT JOIN pos ps ON r.pos_id=ps.id /* <-- BARU: JOIN POS */
                     ORDER BY r.id DESC")->fetchAll();
                     
$pegawai = $pdo->query("SELECT id, nama FROM pegawai ORDER BY nama ASC")->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>CRUD Regu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Gaya kustom untuk memastikan tampilan badge anggota lebih rapi */
        .anggota-badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 5px;
        }
        .badge {
            font-size: 0.8em;
        }
        .text-white.ms-1 {
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .text-white.ms-1:hover {
            opacity: 1;
        }
    </style>
</head>

<body>
    <?php include 'inc_nav.php'; // Asumsikan ini adalah file navigasi Anda ?>
    <div class="container py-4">
        <h3>Manajemen Regu</h3>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#mAdd">Tambah Regu</button>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Regu</th>
                        <th>Peleton</th>
                        <th>Pos</th> <th>Anggota</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <td><?= htmlspecialchars($r['peleton_nama']) ?></td>
                            <td><?= htmlspecialchars($r['pos_nama'] ?? 'N/A') ?></td> <td>
                                <div class="anggota-badge-container">
                                    <?php
                                    $anggota = $pdo->prepare("SELECT ar.id as anggota_id, pg.nama 
                                                             FROM pegawai_regu ar 
                                                             JOIN pegawai pg ON ar.pegawai_id = pg.id 
                                                             WHERE ar.regu_id=?");
                                    $anggota->execute([$r['id']]);
                                    foreach ($anggota as $a) {
                                        echo "<span class='badge bg-primary'>" . htmlspecialchars($a['nama']) .
                                            " <a href='?hapus_anggota=" . $a['anggota_id'] . "' class='text-white ms-1' style='text-decoration:none;'>&times;</a></span>";
                                    }
                                    ?>
                                </div>
                                
                                <form method="post" class="d-flex mt-2">
                                    <input type="hidden" name="regu_id" value="<?= $r['id'] ?>">
                                    <select name="pegawai_id" class="form-select form-select-sm me-2" required>
                                        <option value="">-- Pilih Pegawai --</option>
                                        <?php foreach ($pegawai as $pg): ?>
                                            <option value="<?= $pg['id'] ?>"><?= htmlspecialchars($pg['nama']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-success" name="tambah_anggota">Tambah</button>
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#mEdit<?= $r['id'] ?>">Edit</button>
                                <a href="?hapus_regu=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus regu ini?')">Hapus</a>
                            </td>
                        </tr>
                        
                        <div class="modal fade" id="mEdit<?= $r['id'] ?>">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header">
                                            <h5>Edit Regu</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <div class="mb-2"><label>Nama Regu</label>
                                                <input name="nama" value="<?= htmlspecialchars($r['nama']) ?>" class="form-control" required>
                                            </div>
                                            <div class="mb-2"><label>Peleton</label>
                                                <select name="peleton_id" class="form-control" required>
                                                    <?php foreach ($peletons as $p): ?>
                                                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $r['peleton_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($p['nama']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-2"><label>Pos</label>
                                                <select name="pos_id" class="form-control" required>
                                                    <option value="">-- Pilih Pos --</option>
                                                    <?php foreach ($pos as $ps): ?>
                                                        <option value="<?= $ps['id'] ?>" <?= $ps['id'] == $r['pos_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($ps['nama']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            <button class="btn btn-primary" name="edit">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="modal fade" id="mAdd" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Regu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2"><label>Peleton</label>
                            <select name="peleton_id" class="form-control" required>
                                <option value="">-- Pilih Peleton --</option>
                                <?php foreach ($peletons as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-2"><label>Pos</label>
                            <select name="pos_id" class="form-control" required>
                                <option value="">-- Pilih Pos --</option>
                                <?php foreach ($pos as $ps): ?>
                                    <option value="<?= $ps['id'] ?>"><?= htmlspecialchars($ps['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label>Nama Regu</label><input name="nama" class="form-control" required></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button name="create" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>