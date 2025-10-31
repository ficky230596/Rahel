<?php
include "../config/db.php";

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

if (!$type || !$id || !$tanggal) {
    echo "<div class='text-danger text-center'>Parameter tidak lengkap.</div>";
    exit;
}

$col = match ($type) {
    'peleton' => 'peleton_id',
    'regu'    => 'regu_id',
    'pos'     => 'pos_id',
    default   => null
};

if (!$col) {
    echo "<div class='text-danger text-center'>Tipe tidak valid.</div>";
    exit;
}

$stmt = $pdo->prepare("
    SELECT pg.nama, pg.nip, pg.jabatan, pg.no_hp
    FROM jadwal j
    JOIN pegawai pg ON j.pegawai_id = pg.id
    WHERE j.$col = ? AND j.tanggal = ? AND j.status = 'aktif'
    ORDER BY pg.nama ASC
");
$stmt->execute([$id, $tanggal]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$data) {
    echo "<div class='text-center text-muted'>Tidak ada anggota lain di jadwal ini.</div>";
    exit;
}

echo "<table class='table table-dark table-striped table-bordered mb-0'>";
echo "<thead><tr><th>Nama</th><th>NIP</th><th>Jabatan</th><th>No. HP</th></tr></thead><tbody>";
foreach ($data as $row) {
    echo "<tr>
        <td>" . htmlspecialchars($row['nama']) . "</td>
        <td>" . htmlspecialchars($row['nip'] ?? '-') . "</td>
        <td>" . htmlspecialchars($row['jabatan'] ?? '-') . "</td>
        <td>" . htmlspecialchars($row['no_hp'] ?? '-') . "</td>
    </tr>";
}
echo "</tbody></table>";
