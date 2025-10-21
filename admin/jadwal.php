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

// --- INISIALISASI DATA MASTER ---
$peletons = $pdo->query('SELECT * FROM peleton ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$regus = $pdo->query('SELECT r.*, p.nama as peleton_nama, ps.nama as pos_nama FROM regu r LEFT JOIN peleton p ON r.peleton_id=p.id LEFT JOIN pos ps ON r.pos_id=ps.id')->fetchAll(PDO::FETCH_ASSOC);
$poses = $pdo->query('SELECT * FROM pos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$pegawais_all = $pdo->query("SELECT id, nama FROM pegawai WHERE role='petugas' ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);

// Buat array mapping untuk Lookup Cepat
$regu_to_peleton = array_column($regus, 'peleton_id', 'id');
$pegawai_regu_data = $pdo->query('SELECT pegawai_id, regu_id FROM pegawai_regu')->fetchAll(PDO::FETCH_ASSOC);
$pegawai_regu_map = []; // Map: pegawai_id => regu_id
foreach ($pegawai_regu_data as $row) {
    $pegawai_regu_map[$row['pegawai_id']] = $row['regu_id'];
}

// Filter Pegawai: Hanya yang sudah punya regu yang dipertimbangkan GA
$pegawais = array_filter($pegawais_all, function ($p) use ($pegawai_regu_map) {
    return isset($pegawai_regu_map[$p['id']]);
});
// Pastikan $pegawais memiliki indeks numerik ulang
$pegawais = array_values($pegawais);


// =======================================================
// 🧬 FUNGSI UTAMA GENETIC ALGORITHM (GA) 🧬
// =======================================================

/**
 * 1. Membuat Populasi Awal secara acak (Inisialisasi)
 */
function initializePopulation(int $populationSize, array $allAvailableAssignments): array
{
    // Jika tidak ada assignments, kembalikan array kosong
    if (empty($allAvailableAssignments)) return [];

    $population = [];
    $totalAssignments = count($allAvailableAssignments);
    // Target schedule size = Jumlah Hari Unik * Jumlah Pos Unik yang bertugas
    $targetScheduleSize = count(array_unique(array_column($allAvailableAssignments, 'tanggal'))) * count(array_unique(array_column($allAvailableAssignments, 'pos_id')));

    for ($i = 0; $i < $populationSize; $i++) {
        // Pilih subset acak, dibatasi oleh total assignments yang tersedia
        $randomKeys = array_rand($allAvailableAssignments, min($targetScheduleSize, $totalAssignments));
        $chromosome = [];

        foreach ((array)$randomKeys as $key) {
            $chromosome[] = $allAvailableAssignments[$key];
        }

        // Hapus duplikat Gen (jika ada)
        $chromosome = array_unique($chromosome, SORT_REGULAR);

        $population[] = array_values($chromosome);
    }
    return $population;
}

/**
 * 2. Menghitung Fitness (Kebugaran) setiap Kromosom.
 * Memperhatikan Rotasi Peleton dan Keterisian Pos.
 */
function calculateFitness(array $chromosome, array $pegawais, array $regus, int $days): float
{
    // Jika kromosom kosong, kembalikan skor minimum
    if (empty($chromosome)) return 1.0;

    $score = 1000.0; // Skor awal
    $pegawaiWorkload = array_fill_keys(array_column($pegawais, 'id'), 0);
    $dailyAssignments = []; // Melacak double shift harian (Pegawai + Hari)
    $posOccupancy = []; // Melacak Pos yang sudah terisi di tanggal tertentu (Pos + Hari)

    // Hitung jumlah Pos unik yang terlibat dalam Regu (target keterisian)
    $target_pos_ids = array_unique(array_column($regus, 'pos_id'));

    // Hard Constraint Pelanggaran (Penalti Berat)
    $penalty_double_shift = 0;
    $penalty_double_pos = 0;

    foreach ($chromosome as $assignment) {
        $date = $assignment['tanggal'];
        $pegawai_id = $assignment['pegawai_id'];
        $pos_id = $assignment['pos_id'];

        $posDayKey = $date . '-' . $pos_id;
        $dailyKey = $date . '-' . $pegawai_id;

        // HC 1: Pegawai TIDAK boleh double shift (di hari yang sama)
        if (isset($dailyAssignments[$dailyKey])) {
            $penalty_double_shift += 100;
        }
        $dailyAssignments[$dailyKey] = true;
        $pegawaiWorkload[$pegawai_id]++;

        // HC 2: Pos TIDAK boleh diisi lebih dari satu pegawai (di hari yang sama)
        if (isset($posOccupancy[$posDayKey])) {
            $penalty_double_pos += 100;
        }
        $posOccupancy[$posDayKey] = true;
    }

    // Terapkan penalti Hard Constraint
    $score -= $penalty_double_shift;
    $score -= $penalty_double_pos;

    // HC 3: Keterisian Pos (Pos yang bertugas harus terisi SEMUA)
    $expectedFilledCount = $days * count($target_pos_ids);
    $actualFilledCount = count($posOccupancy);

    $missingAssignments = $expectedFilledCount - $actualFilledCount;
    if ($missingAssignments > 0) {
        // Penalti sangat berat jika ada pos yang kosong, memastikan GA fokus pada keterisian
        $score -= $missingAssignments * 500;
    }


    // --- SOFT CONSTRAINT: Pemerataan Beban Kerja ---
    if (!empty($pegawais)) {
        $totalWorkDays = array_sum($pegawaiWorkload);
        $count_pegawais = count($pegawais);
        if ($count_pegawais > 0) {
            $averageWorkload = $totalWorkDays / $count_pegawais;

            foreach ($pegawaiWorkload as $load) {
                $deviation = abs($load - $averageWorkload);
                $score -= $deviation * 2; // PENALTI RINGAN
            }
        }
    }

    return max(1.0, $score);
}

/**
 * 3. Melakukan Seleksi (Memilih Induk Terbaik) - Metode Elitisme
 */
function selection(array $population, array $fitnessScores): array
{
    arsort($fitnessScores);
    $bestKeys = array_slice(array_keys($fitnessScores), 0, 2);
    return [
        'parent1' => $population[$bestKeys[0]],
        'parent2' => $population[$bestKeys[1] ?? $bestKeys[0]]
    ];
}

/**
 * 4. Melakukan Crossover (Menyilangkan Induk) - One-Point Crossover pada Gen
 */
function crossover(array $parent1, array $parent2): array
{
    if (empty($parent1) || empty($parent2)) return array_merge($parent1, $parent2);

    $crossoverPoint = rand(1, min(count($parent1), count($parent2)) - 1);

    $child = array_merge(
        array_slice($parent1, 0, $crossoverPoint),
        array_slice($parent2, $crossoverPoint)
    );

    // Hapus duplikat Gen (penugasan) berdasarkan nilai isinya
    $child = array_unique($child, SORT_REGULAR);

    return array_values($child);
}

/**
 * 5. Melakukan Mutasi (Perubahan Acak)
 */
function mutation(array $chromosome, array $allAvailableAssignments): array
{
    // Jika tidak ada assignments, tidak bisa mutasi
    if (empty($allAvailableAssignments)) return $chromosome;

    if (rand(1, 100) <= 10 && count($chromosome) > 0) { // 10% kemungkinan mutasi
        $randomIndexToMutate = array_rand($chromosome);
        $newRandomAssignment = $allAvailableAssignments[array_rand($allAvailableAssignments)];

        $chromosome[$randomIndexToMutate] = $newRandomAssignment;
    }
    return $chromosome;
}

// =======================================================
// 💾 LOGIKA APLIKASI (CRUD/DB HANDLING)
// =======================================================

// --- HANDLE ACTION: RESET JADWAL ---
// --- HANDLE ACTION: RESET JADWAL ---
if (isset($_POST['reset_jadwal'])) {
    try {
        // Nonaktifkan foreign key sementara
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Hapus tabel yang terkait terlebih dahulu
        $pdo->exec("TRUNCATE TABLE notification_log");
        $pdo->exec("TRUNCATE TABLE jadwal");

        // Aktifkan kembali foreign key
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        $_SESSION['message'] = '🚨 Semua data jadwal dan log notifikasi berhasil direset.';
    } catch (PDOException $e) {
        $_SESSION['message'] = '❌ Gagal mereset data: ' . $e->getMessage();
    }

    header('Location: jadwal.php');
    exit;
}



// --- HANDLE ACTION: GENERATE JADWAL (MENGGUNAKAN GA) ---
if (isset($_POST['generate'])) {
    $start = $_POST['start_date'] ?? date('Y-m-d');
    $days = intval($_POST['days'] ?? 30);
    $peleton_start_id = intval($_POST['peleton_start']); // ID Peleton awal

    // Pastikan Pos dan Pegawai tersedia
    if (empty($poses) || empty($pegawais)) {
        $_SESSION['message'] = '❌ Error: Data Pos atau Pegawai (yang terikat Regu) belum lengkap!';
        header('Location: jadwal.php');
        exit;
    }

    // 1. Siapkan Data Input GA dengan ROTASI PELETON
    $allPossibleAssignments = [];

    // Tentukan ID Peleton dan urutannya
    $peleton_ids = array_column($peletons, 'id');
    $startIndex = array_search($peleton_start_id, $peleton_ids);

    // Atur ulang $peleton_ids agar rotasi dimulai dari $peleton_start_id
    if ($startIndex !== false) {
        $peleton_ids = array_merge(
            array_slice($peleton_ids, $startIndex),
            array_slice($peleton_ids, 0, $startIndex)
        );
    }
    $num_peletons = count($peleton_ids);

    // Map Regu ke Peleton
    $regu_per_peleton = [];
    foreach ($regus as $r) {
        $regu_per_peleton[$r['peleton_id']][] = $r;
    }

    // Map Pegawai ke Regu
    $pegawai_per_regu_raw = $pdo->query('SELECT pegawai_id, regu_id FROM pegawai_regu')->fetchAll(PDO::FETCH_ASSOC);
    $pegawai_per_regu = [];
    foreach ($pegawai_per_regu_raw as $pr) {
        $pegawai_per_regu[$pr['regu_id']][] = $pr['pegawai_id'];
    }

    // --- LOOP HARIAN DENGAN ROTASI PELETON ---
    for ($d = 0; $d < $days; $d++) {
        $date = date('Y-m-d', strtotime("$start +$d days"));

        // Tentukan Peleton yang bertugas di hari ini
        $peleton_id_hari_ini = $peleton_ids[$d % $num_peletons];

        if (!isset($regu_per_peleton[$peleton_id_hari_ini])) continue;

        $regu_hari_ini = $regu_per_peleton[$peleton_id_hari_ini];

        // Loop melalui Regu yang bertugas
        foreach ($regu_hari_ini as $regu) {
            $regu_id = $regu['id'];
            $pos_id = $regu['pos_id'];

            if (!$pos_id || !isset($pegawai_per_regu[$regu_id])) continue;

            // Loop melalui Pegawai Regu yang bersangkutan
            foreach ($pegawai_per_regu[$regu_id] as $pegawai_id) {

                // Tambahkan Gen (Penugasan) untuk Pos/Hari ini
                $allPossibleAssignments[] = [
                    'tanggal' => $date,
                    'pegawai_id' => $pegawai_id,
                    'pos_id' => $pos_id,
                    'regu_id' => $regu_id,
                    'peleton_id' => $peleton_id_hari_ini,
                    'slot' => 'pagi'
                ];
            }
        }
    }

    // Jalankan GA
    $populationSize = 50;
    $maxGenerations = 100;
    $bestFitness = 0;
    $bestSchedule = [];

    // 2. Inisialisasi Populasi
    $population = initializePopulation($populationSize, $allPossibleAssignments);

    // Jika Populasi gagal diinisialisasi (misal $allPossibleAssignments kosong)
    if (empty($population)) {
        $_SESSION['message'] = '❌ Error: Gagal membuat jadwal. Pastikan Peleton, Regu, dan Pos sudah terisi dengan benar.';
        header('Location: jadwal.php');
        exit;
    }

    // 3. Loop Generasi (Evolusi Solusi)
    // Gunakan $regus yang sudah di-fetch di awal untuk Fitness
    for ($generation = 0; $generation < $maxGenerations; $generation++) {
        $fitnessScores = [];

        foreach ($population as $index => $chromosome) {
            $fitnessScores[$index] = calculateFitness($chromosome, $pegawais, $regus, $days);
        }

        $currentBestFitness = max($fitnessScores);
        $currentBestIndex = array_keys($fitnessScores, $currentBestFitness)[0];

        if ($currentBestFitness > $bestFitness) {
            $bestFitness = $currentBestFitness;
            $bestSchedule = $population[$currentBestIndex];
        }

        if ($bestFitness >= 950) break; // Kriteria Berhenti Cepat

        // Seleksi dan Reproduksi
        $newPopulation = [];
        while (count($newPopulation) < $populationSize) {
            $parents = selection($population, $fitnessScores);
            $child = crossover($parents['parent1'], $parents['parent2']);
            $child = mutation($child, $allPossibleAssignments);
            $newPopulation[] = $child;
        }
        $population = $newPopulation;
    }

    // 4. Simpan Solusi Terbaik ke Database
    $rows_inserted = 0;

    // Hapus jadwal lama dalam rentang tanggal ini
    $pdo->prepare('DELETE FROM jadwal WHERE tanggal BETWEEN ? AND ?')->execute([$start, date('Y-m-d', strtotime("$start +$days days"))]);

    foreach ($bestSchedule as $assignment) {
        $pegawai_id = $assignment['pegawai_id'];
        $regu_id = $assignment['regu_id'];
        $peleton_id = $assignment['peleton_id'];

        // Cek lagi untuk keamanan, meskipun data sudah disiapkan dengan baik
        if ($pegawai_id && $regu_id && $peleton_id) {
            $ins = $pdo->prepare('INSERT INTO jadwal (tanggal, slot, pegawai_id, regu_id, peleton_id, pos_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $ins->execute([$assignment['tanggal'], $assignment['slot'], $pegawai_id, $regu_id, $peleton_id, $assignment['pos_id'], 'aktif']);
            $rows_inserted++;
        }
    }

    $_SESSION['message'] = "✅ Jadwal berhasil digenerate menggunakan Genetic Algorithm! $rows_inserted entri baru dibuat dengan Fitness Score **" . round($bestFitness, 2) . "**.";
    header('Location: jadwal.php');
    exit;
}

// === HANDLE ACTION: DELETE JADWAL ===
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $del = $pdo->prepare('DELETE FROM jadwal WHERE id = ?');
    $del->execute([$id]);

    $_SESSION['message'] = '🗑️ Jadwal dengan ID ' . htmlspecialchars($id) . ' berhasil dihapus.';
    header('Location: jadwal.php');
    exit;
}

// === HANDLE ACTION: EDIT JADWAL (Update) ===
if (isset($_POST['update_jadwal'])) {
    $id = $_POST['id'];
    $pegawai_id = $_POST['pegawai_id'] ?: null;
    $status = $_POST['status'];

    $upd = $pdo->prepare('UPDATE jadwal SET pegawai_id=?, status=? WHERE id=?');
    $upd->execute([$pegawai_id, $status, $id]);

    $_SESSION['message'] = '📝 Jadwal ID ' . htmlspecialchars($id) . ' berhasil diperbarui.';
    header('Location: jadwal.php');
    exit;
}

// --- FETCH DATA UNTUK TABEL & MODAL EDIT ---

$jadwals = $pdo->query(
    '
 SELECT 
  j.*, 
  p.nama as pegawai, 
  pos.nama as posnama, 
  r.nama as regu, 
  pel.nama as peleton 
 FROM jadwal j 
 LEFT JOIN pegawai p ON j.pegawai_id=p.id 
 LEFT JOIN pos ON j.pos_id=pos.id 
 LEFT JOIN regu r ON j.regu_id=r.id 
 LEFT JOIN peleton pel ON j.peleton_id=pel.id 
 ORDER BY tanggal DESC, peleton, regu 
 LIMIT 1000'
)->fetchAll(PDO::FETCH_ASSOC);

// Fetch data untuk Modal Edit
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare('
  SELECT j.*, pos.nama as posnama FROM jadwal j 
  LEFT JOIN pos ON j.pos_id=pos.id 
  WHERE j.id = ?');
    $stmt->execute([$_GET['edit_id']]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Jadwal (Genetic Algorithm)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Hapus semua style terkait drag and drop karena tidak lagi digunakan */
    </style>
</head>

<body>
    <?php include 'inc_nav.php'; ?>
    <div class="container py-4">
        <h3>Penjadwalan Otomatis (Genetic Algorithm)</h3>

        <?php if (!empty($message)): ?>
            <script>
                Swal.fire({
                    icon: 'info',
                    title: 'Notifikasi!',
                    html: '<?= htmlspecialchars($message) ?>'
                });
            </script>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Generate Jadwal (GA)</div>
            <div class="card-body">
                <form method="post" id="generateForm" class="row g-3 align-items-end mb-3">

                    <div class="col-md-3">
                        <label for="peleton_start" class="form-label">Peleton Mulai</label>
                        <select id="peleton_start" name="peleton_start" class="form-control" required>
                            <option value="">-- Pilih Peleton Awal --</option>
                            <?php foreach ($peletons as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Mulai Tanggal</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="days" class="form-label">Jumlah Hari</label>
                        <input type="number" id="days" name="days" class="form-control" value="30" min="1" required>
                    </div>

                    <div class="col-md-3">
                        <button name="generate" class="btn btn-danger w-100">🔥 Jalankan Genetic Algorithm</button>
                    </div>
                </form>

                <form method="post" class="mt-3">
                    <button name="reset_jadwal" type="submit" class="btn btn-outline-secondary w-100"
                        onclick="return confirm('⚠️ PERINGATAN: Yakin ingin menghapus SEMUA jadwal yang sudah ada? Tindakan ini tidak dapat dibatalkan.')">
                        ❌ Reset Semua Jadwal
                    </button>
                </form>

            </div>
        </div>

        <hr>

        <h5>Jadwal Terbaru (<?= count($jadwals) ?> Entri)</h5>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;">No.</th>
                        <th>Tanggal</th>
                        <th>Peleton</th>
                        <th>Regu</th>
                        <th>Pos</th>
                        <th>Pegawai Bertugas</th>
                        <th>Status</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1; // Inisialisasi variabel penomoran
                    foreach ($jadwals as $j): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $j['tanggal'] ?></td>
                            <td><?= htmlspecialchars($j['peleton']) ?></td>
                            <td><?= htmlspecialchars($j['regu']) ?></td>
                            <td><?= htmlspecialchars($j['posnama']) ?></td>
                            <td><?= htmlspecialchars($j['pegawai'] ?? '---') ?></td>
                            <td><span class="badge bg-<?= ($j['status'] == 'aktif' ? 'success' : ($j['status'] == 'diganti' ? 'warning' : 'info')) ?>"><?= ucfirst($j['status']) ?></span></td>
                            <td>
                                <a href="?edit_id=<?= $j['id'] ?>" class="btn btn-sm btn-info text-white me-2">Edit</a>
                                <a href="?delete_id=<?= $j['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus jadwal ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade <?= $edit_data ? 'show' : '' ?>" id="mEdit" tabindex="-1" aria-labelledby="mEditLabel" aria-hidden="true" style="display: <?= $edit_data ? 'block' : 'none' ?>;">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id'] ?? '') ?>">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="mEditLabel">Edit Jadwal (ID: <?= htmlspecialchars($edit_data['id'] ?? '') ?>)</h5>
                        <a href="jadwal.php" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">
                        <p>Tanggal: <b><?= htmlspecialchars($edit_data['tanggal'] ?? '') ?></b> | Pos: <b><?= htmlspecialchars($edit_data['posnama'] ?? '') ?></b></p>

                        <div class="mb-3">
                            <label for="pegawai_id" class="form-label">Pegawai Bertugas</label>
                            <select name="pegawai_id" id="pegawai_id" class="form-control">
                                <option value="">--- Pilih Pegawai ---</option>
                                <?php
                                // Menggunakan $pegawais_all karena di Modal Edit, semua petugas dapat dipilih
                                foreach ($pegawais_all as $peg): ?>
                                    <option value="<?= $peg['id'] ?>" <?= (isset($edit_data) && $edit_data['pegawai_id'] == $peg['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($peg['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-control">
                                <?php $statuses = ['aktif', 'diganti', 'izin', 'cuti']; ?>
                                <?php foreach ($statuses as $stat): ?>
                                    <option value="<?= $stat ?>" <?= (isset($edit_data) && $edit_data['status'] == $stat) ? 'selected' : '' ?>>
                                        <?= ucfirst($stat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="jadwal.php" class="btn btn-secondary">Batal</a>
                        <button name="update_jadwal" class="btn btn-info text-white">Simpan Perubahan</button>
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