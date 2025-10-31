<?php
session_start();
require '../config/db.php'; // Koneksi PDO = $pdo

// 🔒 Cek autentikasi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}


// Ambil pesan session
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// =======================================================
// 💾 HANDLE CRUD ACTIONS
// =======================================================

// --- CREATE ---
if (isset($_POST['create'])) {
  try {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama = $_POST['nama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $nip = $_POST['nip'];
    $status_kepegawaian = $_POST['status_kepegawaian'];
    $no_hp = $_POST['no_hp'];
    $gol = $_POST['golongan'];
    $ruang = $_POST['ruang'];
    $jabatan = $_POST['jabatan'];
    $tugas = $_POST['tugas'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare(
      'INSERT INTO pegawai 
   (username, password_hash, nama, jenis_kelamin, nip, status_kepegawaian, no_hp, golongan, ruang, jabatan, tugas, role)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$username, $password, $nama, $jenis_kelamin, $nip, $status_kepegawaian, $no_hp, $gol, $ruang, $jabatan, $tugas, $role]);

    $_SESSION['message'] = '✅ Pegawai baru berhasil ditambahkan.';
  } catch (PDOException $e) {
    if ($e->getCode() == '23000') {
      $_SESSION['message'] = '❌ Gagal menambahkan pegawai: Username sudah digunakan.';
    } else {
      $_SESSION['message'] = '❌ Error: ' . $e->getMessage();
    }
  }
  header('Location: pegawai.php');
  exit;
}

// --- UPDATE ---
if (isset($_POST['update'])) {
  $id = $_POST['id'];
  $nama = $_POST['nama'];
  $jenis_kelamin = $_POST['jenis_kelamin'];
  $nip = $_POST['nip'];
  $status_kepegawaian = $_POST['status_kepegawaian'];
  $no_hp = $_POST['no_hp'];
  $gol = $_POST['golongan'];
  $ruang = $_POST['ruang'];
  $jabatan = $_POST['jabatan'];
  $tugas = $_POST['tugas'];
  $role = $_POST['role'];
  $password = $_POST['password'];

  $sql = 'UPDATE pegawai 
     SET nama=?, jenis_kelamin=?, nip=?, status_kepegawaian=?, no_hp=?, golongan=?, ruang=?, jabatan=?, tugas=?, role=?';
  $params = [$nama, $jenis_kelamin, $nip, $status_kepegawaian, $no_hp, $gol, $ruang, $jabatan, $tugas, $role];

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

// --- DELETE ---
if (isset($_GET['delete_id'])) {
  $id = $_GET['delete_id'];

  if ($id == ($_SESSION['user_id'] ?? null) && ($_SESSION['role'] ?? '') == 'admin') {
    $_SESSION['message'] = '❌ Tidak dapat menghapus akun admin yang sedang login.';
  } else {
    $stmt = $pdo->prepare('DELETE FROM pegawai WHERE id = ?');
    $stmt->execute([$id]);
    $_SESSION['message'] = '🗑️ Pegawai ID ' . htmlspecialchars($id) . ' berhasil dihapus.';
  }

  header('Location: pegawai.php');
  exit;
}

// --- FETCH DATA ---
// Data diambil dan diurutkan berdasarkan ID terbaru (DESC)
$rows = $pdo->query('SELECT * FROM pegawai ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

// --- DATA EDIT ---
$edit_data = null;
if (isset($_GET['edit_id'])) {
  $stmt = $pdo->prepare('SELECT * FROM pegawai WHERE id = ?');
  $stmt->execute([$_GET['edit_id']]);
  $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
include 'header.php';
include 'sidebar.php';
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manajemen Pegawai</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="../assets/css/admin/pegawai.css">
</head>

<body>
  <div class="container py-4">
    <div class="pegawai-header d-flex justify-content-between align-items-center mb-3">
      <h3>Data Pegawai</h3>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#mAdd">➕ Tambah Pegawai</button>
    </div>

    <?php if (!empty($message)): ?>
      <script>
        Swal.fire({
          icon: 'info',
          title: 'Notifikasi',
          text: '<?= htmlspecialchars($message) ?>'
        });
      </script>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-sm table-striped table-hover">
        <thead class="table-dark">
          <tr>
            <th>No.</th>
            <th>Nama</th>
            <th>Jenis Kelamin</th>
            <th>No. HP (WA)</th>
            <th>Status Kepegawaian</th>
            <th>Jabatan</th>
            <th>Role</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // ⭐ INISIALISASI VARIABEL PENOMORAN
          $no = 1;
          foreach ($rows as $r):
          ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($r['nama']) ?></td>
              <td><?= htmlspecialchars($r['jenis_kelamin'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['no_hp'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['status_kepegawaian'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['jabatan'] ?? '-') ?></td>
              <td><span class="badge bg-<?= ($r['role'] == 'admin' ? 'danger' : 'primary') ?>"><?= ucfirst($r['role']) ?></span></td>
              <td>
                <!-- Tombol Detail -->
                <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#detailModal-<?= $r['id'] ?>">Detail</button>

                <a href="?edit_id=<?= $r['id'] ?>" class="btn btn-sm btn-info text-white">Edit</a>
                <?php if ($r['id'] != ($_SESSION['user_id'] ?? null) || $r['role'] != 'admin'): ?>
                  <a href="?delete_id=<?= $r['id'] ?>" class="btn btn-sm btn-danger"
                    onclick="return confirm('Yakin ingin menghapus pegawai <?= htmlspecialchars($r['nama']) ?>?')">Hapus</a>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Modal Detail untuk setiap baris -->
            <div class="modal fade" id="detailModal-<?= $r['id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel-<?= $r['id'] ?>" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="color: black;">
                  <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="detailModalLabel-<?= $r['id'] ?>">Detail Pegawai: <?= htmlspecialchars($r['nama']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                  </div>
                  <div class="modal-body">
                    <dl class="row">
                      <dt class="col-sm-4">ID</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['id']) ?></dd>

                      <dt class="col-sm-4">Username</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['username']) ?></dd>

                      <dt class="col-sm-4">Nama</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['nama']) ?></dd>

                      <dt class="col-sm-4">Jenis Kelamin</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['jenis_kelamin'] ?? '-') ?></dd>

                      <dt class="col-sm-4">NIP</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['nip'] ?? '-') ?></dd>

                      <dt class="col-sm-4">Status Kepegawaian</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['status_kepegawaian'] ?? '-') ?></dd>

                      <dt class="col-sm-4">No. HP (WA)</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['no_hp'] ?? '-') ?></dd>

                      <dt class="col-sm-4">Golongan</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['golongan'] ?? '-') ?></dd>

                      <dt class="col-sm-4">Ruang</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['ruang'] ?? '-') ?></dd>

                      <dt class="col-sm-4">Jabatan</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['jabatan'] ?? '-') ?></dd>

                      <dt class="col-sm-4">Tugas</dt>
                      <dd class="col-sm-8"><?= htmlspecialchars($r['tugas'] ?? '-') ?></dd>

                      <dt class="col-sm-4">Role</dt>
                      <dd class="col-sm-8"><?= ucfirst(htmlspecialchars($r['role'])) ?></dd>
                    </dl>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <a href="?edit_id=<?= $r['id'] ?>" class="btn btn-info text-white">Edit</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="mAdd" tabindex="-1" style="color: black;">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Tambah Pegawai Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2"><label>Username</label><input name="username" class="form-control" required></div>
            <div class="mb-2"><label>Password</label><input type="password" name="password" class="form-control" required></div>
            <hr>
            <div class="mb-2"><label>Nama Lengkap</label><input name="nama" class="form-control"></div>
            <div class="mb-2"><label>Jenis Kelamin</label>
              <select name="jenis_kelamin" class="form-select">
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
            </div>
            <div class="mb-2"><label>NIP</label><input name="nip" class="form-control"></div>
            <div class="mb-2">
              <label>Status Kepegawaian</label>
              <select name="status_kepegawaian" class="form-select" required>
                <option value="">-- Pilih Status --</option>
                <option value="PNS">PNS</option>
                <option value="PPPK">PPPK</option>
                <option value="PPPK PARUH WAKTU">PPPK PARUH WAKTU</option>
                <option value="TENAGA OUTSOURCING">TENAGA OUTSOURCING</option>
              </select>
            </div>
            <div class="mb-2"><label>No. HP (WA)</label><input name="no_hp" class="form-control" placeholder="628xxxxxxxx"></div>
            <div class="mb-2"><label>Golongan</label><input name="golongan" class="form-control"></div>
            <div class="mb-2"><label>Ruang</label><input name="ruang" class="form-control"></div>
            <div class="mb-2">
              <label>Jabatan</label>
              <select name="jabatan" class="form-select" required>
                <option value="">-- Pilih Jabatan --</option>
                <?php
                $jabatanList = [
                  "Kepala Dinas",
                  "Sekretaris",
                  "Kabid Pemadaman Dan Penyelamatan",
                  "Kabid Pencegahan, Peningkatan Kapasitas SDM dan Sarpras",
                  "Kasubeg Umum dan Kepegawaian",
                  "Kasie Operasi Pemadaman dan Investigasi",
                  "Kasie Pemberdayaan Masyarakat dan Pelatihan",
                  "Kasie Inspeksi Proteksi Kebakaran",
                  "Kasie Kesiapsiagaan dan Komunikasi",
                  "Kasie Sarana dan Prasarana",
                  "Kasie Evakuasi, Penyelamatan dan Perlindungan Hak Sipil",
                  "Kasubag Perencanaan Program dan Keuangan",
                  "Pelaksana",
                  "PETUGAS PEMADAM KEBAKARAN",
                  "PENATA LAYANAN OPERASIONAL",
                  "OPERATOR LAYANAN OPERASIONAL",
                  "PENGELOLA UMUM OPERASIONAL"
                ];
                foreach ($jabatanList as $jab) {
                  echo "<option value='" . htmlspecialchars($jab, ENT_QUOTES) . "'>$jab</option>";
                }
                ?>
              </select>
            </div>

            <div class="mb-2">
              <label>Tugas</label>
              <select name="tugas" class="form-select" required>
                <option value="">-- Pilih Tugas --</option>
                <?php
                $tugasList = [
                  "Perwira jaga",
                  "Pendamping",
                  "Danton",
                  "Danru",
                  "Danru/Nozzle",
                  "Operator",
                  "Selang",
                  "Listrik/Selang",
                  "Danru/Operator",
                  "Nozzle/Selang",
                  "Nozzle"
                ];
                foreach ($tugasList as $tg) {
                  echo "<option value='" . htmlspecialchars($tg, ENT_QUOTES) . "'>$tg</option>";
                }
                ?>
              </select>
            </div>

            <div class="mb-2"><label>Role</label>
              <select name="role" class="form-select">
                <option value="petugas">Petugas</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button name="create" class="btn btn-success">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade <?= $edit_data ? 'show' : '' ?>" id="mEdit" tabindex="-1"
    style="display: <?= $edit_data ? 'block' : 'none' ?>;" aria-modal="<?= $edit_data ? 'true' : 'false' ?>" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" style="color: black;">
          <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id'] ?? '') ?>">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">Edit Pegawai: <?= htmlspecialchars($edit_data['nama'] ?? '') ?></h5>
            <a href="pegawai.php" class="btn-close"></a>
          </div>
          <div class="modal-body">
            <p>Username: <b><?= htmlspecialchars($edit_data['username'] ?? '') ?></b></p>
            <div class="mb-2">
              <label>Ganti Password (Kosongkan jika tidak diubah)</label>
              <input type="password" name="password" class="form-control">
            </div>
            <hr>
            <div class="mb-2">
              <label>Nama Lengkap</label>
              <input name="nama" class="form-control" value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label>Jenis Kelamin</label>
              <select name="jenis_kelamin" class="form-select">
                <option value="Laki-laki" <?= (($edit_data['jenis_kelamin'] ?? '') == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                <option value="Perempuan" <?= (($edit_data['jenis_kelamin'] ?? '') == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
              </select>
            </div>
            <div class="mb-2">
              <label>NIP</label>
              <input name="nip" class="form-control" value="<?= htmlspecialchars($edit_data['nip'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label>Status Kepegawaian</label>
              <select name="status_kepegawaian" class="form-select" required>
                <option value="">-- Pilih Status --</option>
                <option value="PNS" <?= (($edit_data['status_kepegawaian'] ?? '') == 'PNS') ? 'selected' : '' ?>>PNS</option>
                <option value="PPPK" <?= (($edit_data['status_kepegawaian'] ?? '') == 'PPPK') ? 'selected' : '' ?>>PPPK</option>
                <option value="PPPK PARUH WAKTU" <?= (($edit_data['status_kepegawaian'] ?? '') == 'PPPK PARUH WAKTU') ? 'selected' : '' ?>>PPPK PARUH WAKTU</option>
                <option value="TENAGA OUTSOURCING" <?= (($edit_data['status_kepegawaian'] ?? '') == 'TENAGA OUTSOURCING') ? 'selected' : '' ?>>TENAGA OUTSOURCING</option>
              </select>
            </div>

            <div class="mb-2">
              <label>No. HP (WA)</label>
              <input name="no_hp" class="form-control" value="<?= htmlspecialchars($edit_data['no_hp'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label>Golongan</label>
              <input name="golongan" class="form-control" value="<?= htmlspecialchars($edit_data['golongan'] ?? '') ?>">
            </div>
            <div class="mb-2">
              <label>Ruang</label>
              <input name="ruang" class="form-control" value="<?= htmlspecialchars($edit_data['ruang'] ?? '') ?>">
            </div>

            <!-- 🔹 Dropdown Jabatan -->
            <div class="mb-2">
              <label>Jabatan</label>
              <select name="jabatan" class="form-select" required>
                <option value="">-- Pilih Jabatan --</option>
                <?php
                $jabatanList = [
                  "Kepala Dinas",
                  "Sekretaris",
                  "Kabid Pemadaman Dan Penyelamatan",
                  "Kabid Pencegahan, Peningkatan Kapasitas SDM dan Sarpras",
                  "Kasubeg Umum dan Kepegawaian",
                  "Kasie Operasi Pemadaman dan Investigasi",
                  "Kasie Pemberdayaan Masyarakat dan Pelatihan",
                  "Kasie Inspeksi Proteksi Kebakaran",
                  "Kasie Kesiapsiagaan dan Komunikasi",
                  "Kasie Sarana dan Prasarana",
                  "Kasie Evakuasi, Penyelamatan dan Perlindungan Hak Sipil",
                  "Kasubag Perencanaan Program dan Keuangan",
                  "Pelaksana",
                  "PETUGAS PEMADAM KEBAKARAN",
                  "PENATA LAYANAN OPERASIONAL",
                  "OPERATOR LAYANAN OPERASIONAL",
                  "PENGELOLA UMUM OPERASIONAL"
                ];
                foreach ($jabatanList as $jab) {
                  $selected = (($edit_data['jabatan'] ?? '') == $jab) ? 'selected' : '';
                  echo "<option value='" . htmlspecialchars($jab, ENT_QUOTES) . "' $selected>$jab</option>";
                }
                ?>
              </select>
            </div>

            <!-- 🔹 Dropdown Tugas -->
            <div class="mb-2">
              <label>Tugas</label>
              <select name="tugas" class="form-select" required>
                <option value="">-- Pilih Tugas --</option>
                <?php
                $tugasList = [
                  "Perwira jaga",
                  "Pendamping",
                  "Danton",
                  "Danru",
                  "Danru/Nozzle",
                  "Operator",
                  "Selang",
                  "Listrik/Selang",
                  "Danru/Operator",
                  "Nozzle/Selang",
                  "Nozzle"
                ];
                foreach ($tugasList as $tg) {
                  $selected = (($edit_data['tugas'] ?? '') == $tg) ? 'selected' : '';
                  echo "<option value='" . htmlspecialchars($tg, ENT_QUOTES) . "' $selected>$tg</option>";
                }
                ?>
              </select>
            </div>

            <div class="mb-2">
              <label>Role</label>
              <select name="role" class="form-select">
                <option value="petugas" <?= (($edit_data['role'] ?? '') == 'petugas') ? 'selected' : '' ?>>Petugas</option>
                <option value="admin" <?= (($edit_data['role'] ?? '') == 'admin') ? 'selected' : '' ?>>Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <a href="pegawai.php" class="btn btn-secondary">Batal</a>
            <button name="update" class="btn btn-info text-white">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if ($edit_data): ?><div class="modal-backdrop fade show"></div><?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>