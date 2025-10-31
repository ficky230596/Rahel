<?php
session_start();
require '../config/db.php';



// Cek autentikasi dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

// CRUD
if (isset($_POST['create'])) {
  $stmt = $pdo->prepare('INSERT INTO pos (nama, alamat) VALUES (?, ?)');
  $stmt->execute([$_POST['nama'], $_POST['alamat']]);
  header('Location: pos.php');
  exit;
}

if (isset($_POST['update'])) {
  $stmt = $pdo->prepare('UPDATE pos SET nama=?, alamat=? WHERE id=?');
  $stmt->execute([$_POST['nama'], $_POST['alamat'], $_POST['id']]);
  header('Location: pos.php');
  exit;
}

if (isset($_POST['delete'])) {
  $stmt = $pdo->prepare('DELETE FROM pos WHERE id=?');
  $stmt->execute([$_POST['id']]);
  header('Location: pos.php');
  exit;
}

$rows = $pdo->query('SELECT * FROM pos ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'sidebar.php';
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Data Pos Jaga | Damkar Scheduler</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin/pos.css">
</head>

<body>

  <main class="damkar-main">
    <div class="container py-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h3 class="fw-bold text-light mb-2">🔥 Data Pos Jaga</h3>
        <input type="text" id="searchInput" class="form-control search-box" placeholder="Cari pos...">
      </div>

      <div class="card damkar-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold text-white">Daftar Pos</span>
          <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#mAdd">
            <i class="bi bi-plus-circle"></i> Tambah Pos
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0 text-center" id="posTable">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Pos</th>
                <th>Alamat</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1;
              foreach ($rows as $r): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars($r['nama']) ?></td>
                  <td><?= htmlspecialchars($r['alamat']) ?></td>
                  <td>
                    <button class="btn btn-sm btn-warning me-1 btn-edit"
                      data-bs-toggle="modal" data-bs-target="#mEdit"
                      data-id="<?= $r['id'] ?>"
                      data-nama="<?= htmlspecialchars($r['nama']) ?>"
                      data-alamat="<?= htmlspecialchars($r['alamat']) ?>">
                      Edit
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete"
                      data-bs-toggle="modal" data-bs-target="#mDelete"
                      data-id="<?= $r['id'] ?>"
                      data-nama="<?= htmlspecialchars($r['nama']) ?>">
                      Hapus
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal Tambah -->
  <div class="modal fade" id="mAdd" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" style="color: black;">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Tambah Pos Baru</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nama Pos</label>
              <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Alamat</label>
              <textarea name="alamat" class="form-control" required></textarea>
            </div>
          </div>
          <div class="modal-footer" style="gap: 10px;">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="submit" name="create" class="btn btn-danger">Simpan</button>
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
          <div class="modal-header bg-warning">
            <h5 class="modal-title">Edit Pos</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="edit-id">
            <div class="mb-3">
              <label class="form-label">Nama Pos</label>
              <input type="text" name="nama" id="edit-nama" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Alamat</label>
              <textarea name="alamat" id="edit-alamat" class="form-control" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" name="update" class="btn btn-warning">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Delete -->
  <div class="modal fade" id="mDelete" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Konfirmasi Hapus</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="delete-id">
            <p>Apakah Anda yakin ingin menghapus pos <strong><span id="delete-nama-span"></span></strong>?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" name="delete" class="btn btn-danger">Hapus</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-nama').value = this.dataset.nama;
        document.getElementById('edit-alamat').value = this.dataset.alamat;
      });
    });
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('delete-id').value = this.dataset.id;
        document.getElementById('delete-nama-span').textContent = this.dataset.nama;
      });
    });
    document.getElementById('searchInput').addEventListener('input', function() {
      const keyword = this.value.toLowerCase();
      document.querySelectorAll('#posTable tbody tr').forEach(row => {
        const nama = row.cells[1].textContent.toLowerCase();
        const alamat = row.cells[2].textContent.toLowerCase();
        row.style.display = (nama.includes(keyword) || alamat.includes(keyword)) ? '' : 'none';
      });
    });
  </script>
</body>

</html>