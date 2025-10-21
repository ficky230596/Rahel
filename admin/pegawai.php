<?php
session_start();
require '../config/db.php'; // Pastikan file ini mendefinisikan koneksi PDO sebagai $pdo

// Cek autentikasi & role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

// Ambil dan hapus pesan notifikasi dari session
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// =======================================================
// 💾 HANDLE CRUD ACTIONS
// =======================================================

// --- HANDLE ACTION: CREATE ---
if (isset($_POST['create'])) {
  try {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama = $_POST['nama'];
    $nip = $_POST['nip'];
    $pangkat = $_POST['pangkat'];
    $no_hp = $_POST['no_hp']; // <<< KOLOM BARU (WhatsApp)
    $gol = $_POST['golongan'];
    $ruang = $_POST['ruang'];
    $jabatan = $_POST['jabatan'];
    $tugas = $_POST['tugas'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare('INSERT INTO pegawai (username, password_hash, nama, nip, pangkat, no_hp, golongan, ruang, jabatan, tugas, role) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$username, $password, $nama, $nip, $pangkat, $no_hp, $gol, $ruang, $jabatan, $tugas, $role]);

    $_SESSION['message'] = '✅ Pegawai baru berhasil ditambahkan.';
  } catch (PDOException $e) {
    // Error code 23000 = Unique constraint violation (biasanya username sudah ada)
    if ($e->getCode() == '23000') {
      $_SESSION['message'] = '❌ Gagal menambahkan pegawai: Username sudah terpakai.';
    } else {
      $_SESSION['message'] = '❌ Gagal menambahkan pegawai: ' . $e->getMessage();
    }
  }
  header('Location: pegawai.php');
  exit;
}

// --- HANDLE ACTION: UPDATE ---
if (isset($_POST['update'])) {
  $id = $_POST['id'];
  $nama = $_POST['nama'];
  $nip = $_POST['nip'];
  $pangkat = $_POST['pangkat'];
  $no_hp = $_POST['no_hp']; // <<< KOLOM BARU
  $gol = $_POST['golongan'];
  $ruang = $_POST['ruang'];
  $jabatan = $_POST['jabatan'];
  $tugas = $_POST['tugas'];
  $role = $_POST['role'];
  $password = $_POST['password'];

  $sql = 'UPDATE pegawai SET nama=?, nip=?, pangkat=?, no_hp=?, golongan=?, ruang=?, jabatan=?, tugas=?, role=?';
  $params = [$nama, $nip, $pangkat, $no_hp, $gol, $ruang, $jabatan, $tugas, $role];

  // Jika password diisi, update password hash
  if (!empty($password)) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql .= ', password_hash=?';
    $params[] = $password_hash;
  }

  $sql .= ' WHERE id=?';
  $params[] = $id;

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $_SESSION['message'] = '📝 Data pegawai ID ' . htmlspecialchars($id) . ' berhasil diperbarui.';
  header('Location: pegawai.php');
  exit;
}

// --- HANDLE ACTION: DELETE ---
if (isset($_GET['delete_id'])) {
  $id = $_GET['delete_id'];

  // Cek jika yang dihapus adalah admin yang sedang login (sebaiknya dicegah)
  if ($id == ($_SESSION['user_id'] ?? null) && ($_SESSION['role'] ?? '') == 'admin') {
    $_SESSION['message'] = '❌ Anda tidak dapat menghapus akun admin yang sedang login.';
  } else {
    $stmt = $pdo->prepare('DELETE FROM pegawai WHERE id = ?');
    $stmt->execute([$id]);
    $_SESSION['message'] = '🗑️ Pegawai ID ' . htmlspecialchars($id) . ' berhasil dihapus.';
  }

  header('Location: pegawai.php');
  exit;
}

// --- FETCH DATA ---
$rows = $pdo->query('SELECT * FROM pegawai ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

// Fetch data untuk Modal Edit
$edit_data = null;
if (isset($_GET['edit_id'])) {
  $stmt = $pdo->prepare('SELECT * FROM pegawai WHERE id = ?');
  $stmt->execute([$_GET['edit_id']]);
  $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manajemen Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  <?php include 'inc_nav.php'; ?>
  <div class="container py-4">
    <h3>Data Pegawai</h3>

    <?php if (!empty($message)): ?>
      <script>
        Swal.fire({
          icon: 'info',
          title: 'Notifikasi',
          text: '<?= htmlspecialchars($message) ?>'
        });
      </script>
    <?php endif; ?>

    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#mAdd">➕ Tambah Pegawai</button>

    <div class="table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>No. HP (WA)</th>
            <th>Pangkat</th>
            <th>Jabatan</th>
            <th>Role</th>
            <th style="width: 150px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= htmlspecialchars($r['nama']) ?></td>
              <td><?= htmlspecialchars($r['no_hp'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['pangkat'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['jabatan'] ?? '-') ?></td>
              <td><span class="badge bg-<?= ($r['role'] == 'admin' ? 'danger' : 'primary') ?>"><?= ucfirst($r['role']) ?></span></td>
              <td>
                <a href="?edit_id=<?= $r['id'] ?>" class="btn btn-sm btn-info text-white me-1">Edit</a>

                <?php
                // Pencegahan agar admin tidak menghapus dirinya sendiri
                if ($r['id'] != ($_SESSION['user_id'] ?? null) || $r['role'] != 'admin'):
                ?>
                  <a href="?delete_id=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus pegawai <?= htmlspecialchars($r['nama']) ?>?')">Hapus</a>
                <?php endif; ?>
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
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Tambah Pegawai Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2"><label>Username (Login)</label><input name="username" class="form-control" required></div>
            <div class="mb-2"><label>Password</label><input type="password" name="password" class="form-control" required></div>
            <hr>
            <div class="mb-2"><label>Nama Lengkap</label><input name="nama" class="form-control"></div>
            <div class="mb-2"><label>NIP</label><input name="nip" class="form-control"></div>
            <div class="mb-2"><label>Pangkat</label><input name="pangkat" class="form-control"></div>
            <div class="mb-2">
              <label>Nomor HP (WA)</label>
              <input name="no_hp" class="form-control" placeholder="628xxxxxxxx (Wajib format internasional)">
            </div>
            <div class="mb-2"><label>Golongan</label><input name="golongan" class="form-control"></div>
            <div class="mb-2"><label>Ruang</label><input name="ruang" class="form-control"></div>
            <div class="mb-2"><label>Jabatan</label><input name="jabatan" class="form-control"></div>
            <div class="mb-2"><label>Tugas</label><input name="tugas" class="form-control"></div>
            <div class="mb-2"><label>Role</label><select name="role" class="form-control">
                <option value="petugas">Petugas</option>
                <option value="admin">Admin</option>
              </select></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button name="create" class="btn btn-success">Simpan Pegawai</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade <?= $edit_data ? 'show' : '' ?>" id="mEdit" tabindex="-1" style="display: <?= $edit_data ? 'block' : 'none' ?>;" aria-modal="<?= $edit_data ? 'true' : 'false' ?>" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id'] ?? '') ?>">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">Edit Pegawai: <?= htmlspecialchars($edit_data['nama'] ?? '') ?></h5>
            <a href="pegawai.php" class="btn-close" aria-label="Close"></a>
          </div>
          <div class="modal-body">
            <p>Username: <b><?= htmlspecialchars($edit_data['username'] ?? '') ?></b></p>
            <div class="mb-2"><label>Ganti Password (Kosongkan jika tidak diubah)</label><input type="password" name="password" class="form-control"></div>
            <hr>
            <div class="mb-2"><label>Nama Lengkap</label><input name="nama" class="form-control" value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>"></div>
            <div class="mb-2"><label>NIP</label><input name="nip" class="form-control" value="<?= htmlspecialchars($edit_data['nip'] ?? '') ?>"></div>
            <div class="mb-2"><label>Pangkat</label><input name="pangkat" class="form-control" value="<?= htmlspecialchars($edit_data['pangkat'] ?? '') ?>"></div>
            <div class="mb-2">
              <label>Nomor HP (WA)</label>
              <input name="no_hp" class="form-control" value="<?= htmlspecialchars($edit_data['no_hp'] ?? '') ?>" placeholder="628xxxxxxxx (Wajib format internasional)">
            </div>
            <div class="mb-2"><label>Golongan</label><input name="golongan" class="form-control" value="<?= htmlspecialchars($edit_data['golongan'] ?? '') ?>"></div>
            <div class="mb-2"><label>Ruang</label><input name="ruang" class="form-control" value="<?= htmlspecialchars($edit_data['ruang'] ?? '') ?>"></div>
            <div class="mb-2"><label>Jabatan</label><input name="jabatan" class="form-control" value="<?= htmlspecialchars($edit_data['jabatan'] ?? '') ?>"></div>
            <div class="mb-2"><label>Tugas</label><input name="tugas" class="form-control" value="<?= htmlspecialchars($edit_data['tugas'] ?? '') ?>"></div>
            <div class="mb-2"><label>Role</label><select name="role" class="form-control">
                <option value="petugas" <?= (($edit_data['role'] ?? '') == 'petugas') ? 'selected' : '' ?>>Petugas</option>
                <option value="admin" <?= (($edit_data['role'] ?? '') == 'admin') ? 'selected' : '' ?>>Admin</option>
              </select></div>
          </div>
          <div class="modal-footer">
            <a href="pegawai.php" class="btn btn-secondary">Batal</a>
            <button name="update" class="btn btn-info text-white">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php if ($edit_data): ?>
    <div class="modal-backdrop fade show"></div>
  <?php endif; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>