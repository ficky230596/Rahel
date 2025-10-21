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
  $alamat = $_POST['alamat'];
  // Gunakan prepared statement untuk keamanan
  $stmt = $pdo->prepare('INSERT INTO pos (nama, alamat) VALUES (?, ?)');
  $stmt->execute([$nama, $alamat]);
  header('Location: pos.php');
  exit;
}

// Logika Edit (Update)
if (isset($_POST['update'])) {
  $id = $_POST['id'];
  $nama = $_POST['nama'];
  $alamat = $_POST['alamat'];
  // Gunakan prepared statement untuk keamanan
  $stmt = $pdo->prepare('UPDATE pos SET nama = ?, alamat = ? WHERE id = ?');
  $stmt->execute([$nama, $alamat, $id]);
  header('Location: pos.php');
  exit;
}

// Logika Hapus (Delete)
if (isset($_POST['delete'])) {
  $id = $_POST['id'];
  // Gunakan prepared statement untuk keamanan
  $stmt = $pdo->prepare('DELETE FROM pos WHERE id = ?');
  $stmt->execute([$id]);
  header('Location: pos.php');
  exit;
}

// Ambil data untuk ditampilkan
$rows = $pdo->query('SELECT * FROM pos ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
  <?php include 'inc_nav.php'; ?>
  <div class="container py-4">
    <h3>Pos Jaga</h3>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#mAdd">Tambah Pos</button>

    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Alamat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= htmlspecialchars($r['nama']) ?></td>
              <td><?= htmlspecialchars($r['alamat']) ?></td>
              <td>
                <button class="btn btn-sm btn-warning me-2 btn-edit"
                  data-bs-toggle="modal"
                  data-bs-target="#mEdit"
                  data-id="<?= $r['id'] ?>"
                  data-nama="<?= htmlspecialchars($r['nama']) ?>"
                  data-alamat="<?= htmlspecialchars($r['alamat']) ?>">
                  Edit
                </button>
                <button class="btn btn-sm btn-danger btn-delete"
                  data-bs-toggle="modal"
                  data-bs-target="#mDelete"
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

  <div class="modal fade" id="mAdd" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Pos</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2"><label class="form-label">Nama Pos</label><input name="nama" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" required></textarea></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button name="create" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="mEdit" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Edit Pos</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="edit-id">
            <div class="mb-2"><label class="form-label">Nama Pos</label><input name="nama" id="edit-nama" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">Alamat</label><textarea name="alamat" id="edit-alamat" class="form-control" required></textarea></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button name="update" class="btn btn-warning">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="mDelete" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Konfirmasi Hapus</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="delete-id">
            <p>Apakah Anda yakin ingin menghapus pos jaga **<span id="delete-nama-span"></span>**?</p>
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
    // Script untuk mengisi data pada Modal Edit
    document.querySelectorAll('.btn-edit').forEach(button => {
      button.addEventListener('click', function() {
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-nama').value = this.dataset.nama;
        document.getElementById('edit-alamat').value = this.dataset.alamat;
      });
    });

    // Script untuk mengisi data pada Modal Hapus
    document.querySelectorAll('.btn-delete').forEach(button => {
      button.addEventListener('click', function() {
        document.getElementById('delete-id').value = this.dataset.id;
        document.getElementById('delete-nama-span').textContent = this.dataset.nama;
      });
    });
  </script>
</body>

</html>