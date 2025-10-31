<?php
session_start();
require '../config/db.php';

// Cek autentikasi dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

// Logika Tambah (Create)
if (isset($_POST['create'])) {
  $nama = $_POST['nama'];
  $stmt = $pdo->prepare('INSERT INTO peleton (nama) VALUES (?)');
  $stmt->execute([$nama]);
  header('Location: peleton.php');
  exit;
}

// Logika Edit (Update)
if (isset($_POST['update'])) {
  $id = $_POST['id'];
  $nama = $_POST['nama'];
  $stmt = $pdo->prepare('UPDATE peleton SET nama = ? WHERE id = ?');
  $stmt->execute([$nama, $id]);
  header('Location: peleton.php');
  exit;
}

// Logika Hapus (Delete)
if (isset($_POST['delete'])) {
  $id = $_POST['id'];
  $stmt = $pdo->prepare('DELETE FROM peleton WHERE id = ?');
  $stmt->execute([$id]);
  header('Location: peleton.php');
  exit;
}

// Ambil data untuk ditampilkan
$rows = $pdo->query('SELECT * FROM peleton ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Peleton - Manajemen Damkar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin/peleton.css">
</head>

<body>
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <div class="peleton-container container py-4 mt-4">
    <div class="peleton-header d-flex justify-content-between align-items-center mb-3">
      <h3 class="text-white fw-bold"><i class="bi bi-fire text-warning"></i> Data Peleton</h3>
      <button class="btn btn-danger fw-semibold shadow" data-bs-toggle="modal" data-bs-target="#mAdd">+ Tambah Peleton</button>
    </div>

    <div class="search-box mb-3">
      <input type="text" id="searchInput" class="form-control" placeholder="🔍 Cari Peleton...">
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle text-center">
        <thead class="table-danger text-white">
          <tr>
            <th style="color: white;">No</th>
            <th style="color: white;">Nama Peleton</th>
            <th style="color: white;">Aksi</th>
          </tr>
        </thead>
        <tbody  id="peletonTable">
          <?php $no = 1;
          foreach ($rows as $r): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= htmlspecialchars($r['nama']); ?></td>
              <td>
                <button class="btn btn-sm btn-warning me-2 btn-edit"
                  data-bs-toggle="modal"
                  data-bs-target="#mEdit"
                  data-id="<?= $r['id'] ?>"
                  data-nama="<?= htmlspecialchars($r['nama']) ?>">✏️ Edit</button>
                <button class="btn btn-sm btn-danger btn-delete"
                  data-bs-toggle="modal"
                  data-bs-target="#mDelete"
                  data-id="<?= $r['id'] ?>"
                  data-nama="<?= htmlspecialchars($r['nama']) ?>">🗑️ Hapus</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Tambah -->
  <div class="modal fade" id="mAdd" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Tambah Peleton</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <label class="form-label">Nama Peleton</label>
            <input name="nama" class="form-control" required>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button name="create" class="btn btn-danger">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal fade" id="mEdit" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title">Edit Peleton</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="edit-id">
            <label class="form-label">Nama Peleton</label>
            <input name="nama" id="edit-nama" class="form-control" required>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button name="update" class="btn btn-warning text-dark fw-bold">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Hapus -->
  <div class="modal fade" id="mDelete" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title">Konfirmasi Hapus</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="delete-id">
            <p>Apakah Anda yakin ingin menghapus Peleton <strong><span id="delete-nama-span"></span></strong>?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button name="delete" class="btn btn-danger">Hapus</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Edit Modal
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-nama').value = this.dataset.nama;
      });
    });

    // Delete Modal
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('delete-id').value = this.dataset.id;
        document.getElementById('delete-nama-span').textContent = this.dataset.nama;
      });
    });

    // Fitur Pencarian
    document.getElementById('searchInput').addEventListener('keyup', function() {
      let filter = this.value.toLowerCase();
      let rows = document.querySelectorAll('#peletonTable tr');
      rows.forEach(row => {
        let nama = row.cells[1].textContent.toLowerCase();
        row.style.display = nama.includes(filter) ? '' : 'none';
      });
    });
  </script>
</body>

</html>