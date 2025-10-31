<?php
session_start();
require '../config/db.php';
require '../admin/send_whatsapp.php'; // Fungsi sendWA()

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}



// Handle update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status']) && !empty($_POST['set_status'])) {
  $id = $_POST['id'];
  $status = $_POST['set_status'];

  // Ambil data pegawai terkait izin
  $stmt = $pdo->prepare("SELECT i.*, p.nama, p.no_hp, i.lampiran FROM izin i LEFT JOIN pegawai p ON i.pegawai_id=p.id WHERE i.id=?");
  $stmt->execute([$id]);
  $izin = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($izin) {
    $jenis_izin = $izin['jenis'] ?? '-';

    // Update status di database
    $pdo->prepare('UPDATE izin SET status=? WHERE id=?')->execute([$status, $id]);

    // Kirim notifikasi WA ke pegawai
    if (!empty($izin['no_hp'])) {
      $nama = $izin['nama'] ?? 'Pegawai';
      $pesan = "📢 Status Izin Anda\n"
        . "👤 Nama: $nama\n"
        . "📄 Jenis: $jenis_izin\n"
        . "📅 Periode: {$izin['tanggal_mulai']} - {$izin['tanggal_selesai']}\n"
        . "📝 Alasan: {$izin['alasan']}\n"
        . ($izin['lampiran'] ? "📎 Lampiran: tersedia" : "📎 Lampiran: tidak ada") . "\n"
        . "✅ Status: " . ($status === 'diterima' ? 'Diterima' : 'Ditolak');
      sendWA($izin['no_hp'], $pesan);
    }
  }

  header('Location: verifikasi.php');
  exit;
}

// Ambil data izin
$rows = $pdo->query('SELECT i.*, p.nama, p.no_hp FROM izin i LEFT JOIN pegawai p ON i.pegawai_id=p.id ORDER BY i.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'sidebar.php';
?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verifikasi Izin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin/verifikasi.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
  /* Style link lampiran */
  table td a {
    display: inline-block;
    padding: 4px 8px;
    background-color: #fafafaff;
    /* Biru cerah */
    color: #ff0000ff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: background-color 0.3s, transform 0.2s;
  }

  table td a:hover {
    background-color: #020506ff;
    /* Biru lebih gelap saat hover */
    transform: translateY(-2px);
  }

  /* Kolom lampiran rata tengah */
  table td:nth-child(6) {
    text-align: center;
  }

  /* Jika tidak ada lampiran */
  table td:empty::before {
    content: "-";
    color: #6b7280;
    /* abu-abu */
    font-style: italic;
  }
</style>

<body>
  <div class="container py-4">
    <div class="search-bar mb-3">
      <h3 style="color: aliceblue;">Verifikasi Izin/Cuti</h3>
      <input type="text" id="searchInput" placeholder="🔍 Cari pegawai..." class="form-control">
    </div>

    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th>No</th>
          <th>Pegawai</th>
          <th>Jenis</th>
          <th>Periode</th>
          <th>Alasan</th>
          <th>Lampiran</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        foreach ($rows as $r):
          $is_verified = in_array($r['status'], ['diterima', 'ditolak']);
        ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['jenis']) ?></td>
            <td><?= $r['tanggal_mulai'] ?> - <?= $r['tanggal_selesai'] ?></td>
            <td><?= htmlspecialchars($r['alasan']) ?></td>
            <td>
              <?php if (!empty($r['lampiran'])): ?>
                <a href="../<?= $r['lampiran'] ?>" target="_blank">Lihat</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['status'] == 'diterima'): ?>
                <span class="badge bg-success">Diterima</span>
              <?php elseif ($r['status'] == 'ditolak'): ?>
                <span class="badge bg-danger">Ditolak</span>
              <?php else: ?>
                <span class="badge bg-warning">Menunggu</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" class="status-form" style="display:inline">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="set_status" value="">
                <button type="button" class="btn btn-sm btn-success" data-status="diterima" <?= $is_verified ? 'disabled' : '' ?>>Terima</button>
              </form>
              <form method="post" class="status-form" style="display:inline">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="set_status" value="">
                <button type="button" class="btn btn-sm btn-danger" data-status="ditolak" <?= $is_verified ? 'disabled' : '' ?>>Tolak</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script>
    document.querySelectorAll('.status-form button').forEach(btn => {
      btn.addEventListener('click', function() {
        const form = btn.closest('form');
        const status = btn.dataset.status;

        Swal.fire({
          title: 'Konfirmasi',
          text: `Apakah Anda yakin ingin ${status === 'diterima' ? 'menerima' : 'menolak'} izin ini?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Ya',
          cancelButtonText: 'Batal'
        }).then((result) => {
          if (result.isConfirmed) {
            form.querySelector('input[name="set_status"]').value = status;
            form.submit();
          }
        });
      });
    });

    // Filter pencarian pegawai
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function() {
      const filter = searchInput.value.toLowerCase();
      document.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.cells[1].textContent.toLowerCase().includes(filter) ? '' : 'none';
      });
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>