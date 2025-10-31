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
$pegawais = array_values($pegawais);

// --- AMBIL DATA BULAN DAN TAHUN DARI TABEL JADWAL UNTUK FILTER DAN RESET ---
$available_periods = $pdo->query("SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') AS period, YEAR(tanggal) AS year, MONTH(tanggal) AS month FROM jadwal ORDER BY year DESC, month DESC")->fetchAll(PDO::FETCH_ASSOC);

// --- HANDLE FILTER ---
$filter_period = $_GET['filter_period'] ?? '';
$filter_peleton = $_GET['filter_peleton'] ?? '';
$filter_regu = $_GET['filter_regu'] ?? '';
$filter_pos = $_GET['filter_pos'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$where_clauses = [];
$params = [];

if ($filter_period && preg_match('/^\d{4}-\d{2}$/', $filter_period)) {
    $year = substr($filter_period, 0, 4);
    $month = substr($filter_period, 5, 2);
    $where_clauses[] = "DATE_FORMAT(j.tanggal, '%Y-%m') = ?";
    $params[] = $filter_period;
}
if ($filter_peleton) {
    $where_clauses[] = "j.peleton_id = ?";
    $params[] = $filter_peleton;
}
if ($filter_regu) {
    $where_clauses[] = "j.regu_id = ?";
    $params[] = $filter_regu;
}
if ($filter_pos) {
    $where_clauses[] = "j.pos_id = ?";
    $params[] = $filter_pos;
}
if ($filter_status) {
    $where_clauses[] = "j.status = ?";
    $params[] = $filter_status;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
$query = "
    SELECT 
        j.*, 
        p.nama as pegawai, 
        pos.nama as posnama, 
        r.nama as regu, 
        pel.nama as peleton 
    FROM jadwal j 
    LEFT JOIN pegawai p ON j.pegawai_id = p.id 
    LEFT JOIN pos ON j.pos_id = pos.id 
    LEFT JOIN regu r ON j.regu_id = r.id 
    LEFT JOIN peleton pel ON j.peleton_id = pel.id 
    $where_sql 
    ORDER BY j.tanggal ASC, pel.nama, r.nama 
    LIMIT 1000";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jadwals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================================================
// 🧬 FUNGSI UTAMA GENETIC ALGORITHM (GA) 🧬
// =======================================================

/**
 * 1. Membuat Populasi Awal secara acak (Inisialisasi)
 */
function initializePopulation(int $populationSize, array $allAvailableAssignments): array
{
    if (empty($allAvailableAssignments)) return [];

    $population = [];
    $totalAssignments = count($allAvailableAssignments);
    $targetScheduleSize = count(array_unique(array_column($allAvailableAssignments, 'tanggal'))) * count(array_unique(array_column($allAvailableAssignments, 'pos_id')));

    for ($i = 0; $i < $populationSize; $i++) {
        $randomKeys = array_rand($allAvailableAssignments, min($targetScheduleSize, $totalAssignments));
        $chromosome = [];

        foreach ((array)$randomKeys as $key) {
            $chromosome[] = $allAvailableAssignments[$key];
        }

        $chromosome = array_unique($chromosome, SORT_REGULAR);
        $population[] = array_values($chromosome);
    }
    return $population;
}

/**
 * 2. Menghitung Fitness (Kebugaran) setiap Kromosom.
 */
function calculateFitness(array $chromosome, array $pegawais, array $regus, int $days): float
{
    if (empty($chromosome)) return 1.0;

    $score = 1000.0;
    $pegawaiWorkload = array_fill_keys(array_column($pegawais, 'id'), 0);
    $dailyAssignments = [];
    $posOccupancy = [];
    $target_pos_ids = array_unique(array_column($regus, 'pos_id'));

    $penalty_double_shift = 0;
    $penalty_double_pos = 0;

    foreach ($chromosome as $assignment) {
        $date = $assignment['tanggal'];
        $pegawai_id = $assignment['pegawai_id'];
        $pos_id = $assignment['pos_id'];

        $posDayKey = $date . '-' . $pos_id;
        $dailyKey = $date . '-' . $pegawai_id;

        if (isset($dailyAssignments[$dailyKey])) {
            $penalty_double_shift += 100;
        }
        $dailyAssignments[$dailyKey] = true;
        $pegawaiWorkload[$pegawai_id]++;

        if (isset($posOccupancy[$posDayKey])) {
            $penalty_double_pos += 100;
        }
        $posOccupancy[$posDayKey] = true;
    }

    $score -= $penalty_double_shift;
    $score -= $penalty_double_pos;

    $expectedFilledCount = $days * count($target_pos_ids);
    $actualFilledCount = count($posOccupancy);
    $missingAssignments = $expectedFilledCount - $actualFilledCount;
    if ($missingAssignments > 0) {
        $score -= $missingAssignments * 500;
    }

    if (!empty($pegawais)) {
        $totalWorkDays = array_sum($pegawaiWorkload);
        $count_pegawais = count($pegawais);
        if ($count_pegawais > 0) {
            $averageWorkload = $totalWorkDays / $count_pegawais;
            foreach ($pegawaiWorkload as $load) {
                $deviation = abs($load - $averageWorkload);
                $score -= $deviation * 2;
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
 * 4. Melakukan Crossover (Menyilangkan Induk)
 */
function crossover(array $parent1, array $parent2): array
{
    if (empty($parent1) || empty($parent2)) return array_merge($parent1, $parent2);

    $crossoverPoint = rand(1, min(count($parent1), count($parent2)) - 1);
    $child = array_merge(
        array_slice($parent1, 0, $crossoverPoint),
        array_slice($parent2, $crossoverPoint)
    );
    $child = array_unique($child, SORT_REGULAR);
    return array_values($child);
}

/**
 * 5. Melakukan Mutasi (Perubahan Acak)
 */
function mutation(array $chromosome, array $allAvailableAssignments): array
{
    if (empty($allAvailableAssignments)) return $chromosome;

    if (rand(1, 100) <= 10 && count($chromosome) > 0) {
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
if (isset($_POST['reset_jadwal'])) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE notification_log");
        $pdo->exec("TRUNCATE TABLE jadwal");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $_SESSION['message'] = '🚨 Semua data jadwal dan log notifikasi berhasil direset.';
    } catch (PDOException $e) {
        $_SESSION['message'] = '❌ Gagal mereset data: ' . $e->getMessage();
    }
    header('Location: jadwal.php');
    exit;
}

// --- HANDLE ACTION: RESET JADWAL PER BULAN ---
if (isset($_POST['reset_perbulan'])) {
    $period = $_POST['reset_period'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
        $_SESSION['message'] = '❌ Error: Periode tidak valid!';
        header('Location: jadwal.php');
        exit;
    }

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $year = substr($period, 0, 4);
        $month = substr($period, 5, 2);
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        $stmt = $pdo->prepare("DELETE FROM jadwal WHERE tanggal BETWEEN ? AND ?");
        $stmt->execute([$startDate, $endDate]);
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $_SESSION['message'] = "🧹 Jadwal untuk bulan " . date('F', mktime(0, 0, 0, $month, 1)) . " $year berhasil dihapus!";
    } catch (PDOException $e) {
        $_SESSION['message'] = '❌ Gagal mereset jadwal bulanan: ' . $e->getMessage();
    }
    header('Location: jadwal.php');
    exit;
}

// --- HANDLE ACTION: GENERATE JADWAL (MENGGUNAKAN GA) ---
if (isset($_POST['generate'])) {
    $start = $_POST['start_date'] ?? date('Y-m-d');
    $days = intval($_POST['days'] ?? 30);
    $peleton_start_id = intval($_POST['peleton_start']);

    if (empty($poses) || empty($pegawais)) {
        $_SESSION['message'] = '❌ Error: Data Pos atau Pegawai (yang terikat Regu) belum lengkap!';
        header('Location: jadwal.php');
        exit;
    }

    $allPossibleAssignments = [];
    $peleton_ids = array_column($peletons, 'id');
    $startIndex = array_search($peleton_start_id, $peleton_ids);
    if ($startIndex !== false) {
        $peleton_ids = array_merge(
            array_slice($peleton_ids, $startIndex),
            array_slice($peleton_ids, 0, $startIndex)
        );
    }
    $num_peletons = count($peleton_ids);

    $regu_per_peleton = [];
    foreach ($regus as $r) {
        $regu_per_peleton[$r['peleton_id']][] = $r;
    }

    $pegawai_per_regu_raw = $pdo->query('SELECT pegawai_id, regu_id FROM pegawai_regu')->fetchAll(PDO::FETCH_ASSOC);
    $pegawai_per_regu = [];
    foreach ($pegawai_per_regu_raw as $pr) {
        $pegawai_per_regu[$pr['regu_id']][] = $pr['pegawai_id'];
    }

    for ($d = 0; $d < $days; $d++) {
        $date = date('Y-m-d', strtotime("$start +$d days"));
        $peleton_id_hari_ini = $peleton_ids[$d % $num_peletons];
        if (!isset($regu_per_peleton[$peleton_id_hari_ini])) continue;
        $regu_hari_ini = $regu_per_peleton[$peleton_id_hari_ini];

        foreach ($regu_hari_ini as $regu) {
            $regu_id = $regu['id'];
            $pos_id = $regu['pos_id'];
            if (!$pos_id || !isset($pegawai_per_regu[$regu_id])) continue;

            foreach ($pegawai_per_regu[$regu_id] as $pegawai_id) {
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

    $populationSize = 50;
    $maxGenerations = 100;
    $bestFitness = 0;
    $bestSchedule = [];

    $population = initializePopulation($populationSize, $allPossibleAssignments);
    if (empty($population)) {
        $_SESSION['message'] = '❌ Error: Gagal membuat jadwal. Pastikan Peleton, Regu, dan Pos sudah terisi dengan benar.';
        header('Location: jadwal.php');
        exit;
    }

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

        if ($bestFitness >= 950) break;

        $newPopulation = [];
        while (count($newPopulation) < $populationSize) {
            $parents = selection($population, $fitnessScores);
            $child = crossover($parents['parent1'], $parents['parent2']);
            $child = mutation($child, $allPossibleAssignments);
            $newPopulation[] = $child;
        }
        $population = $newPopulation;
    }

    $rows_inserted = 0;
    $pdo->prepare('DELETE FROM jadwal WHERE tanggal BETWEEN ? AND ?')->execute([$start, date('Y-m-d', strtotime("$start +$days days"))]);

    foreach ($bestSchedule as $assignment) {
        $pegawai_id = $assignment['pegawai_id'];
        $regu_id = $assignment['regu_id'];
        $peleton_id = $assignment['peleton_id'];

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

// Fetch data untuk Modal Edit
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare('SELECT j.*, pos.nama as posnama FROM jadwal j LEFT JOIN pos ON j.pos_id=pos.id WHERE j.id = ?');
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
    <title>Jadwal (Genetic Algorithm)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/admin/jadwal.css">
    <style>
        /* Custom styles for filter form */
        .filter-form .form-label {
            color: white;
        }
    </style>
</head>
<body>
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
                        <label for="peleton_start" class="form-label" style="color: white;">Peleton Mulai</label>
                        <select id="peleton_start" name="peleton_start" class="form-control" required>
                            <option value="">-- Pilih Peleton Awal --</option>
                            <?php foreach ($peletons as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label" style="color: white;">Mulai Tanggal</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="days" class="form-label" style="color: white;">Jumlah Hari</label>
                        <input type="number" id="days" name="days" class="form-control" value="30" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <button name="generate" class="btn btn-danger w-100">🔥 Jalankan Genetic Algorithm</button>
                    </div>
                </form>

                <!-- Form Reset Jadwal Per Bulan -->
                <form method="post" class="mt-3 row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="reset_period" class="form-label" style="color: white;">Pilih Periode</label>
                        <select name="reset_period" id="reset_period" class="form-control" required>
                            <option value="">-- Pilih Bulan dan Tahun --</option>
                            <?php if (empty($available_periods)): ?>
                                <option value="" disabled>Tidak ada data jadwal</option>
                            <?php else: ?>
                                <?php foreach ($available_periods as $period): ?>
                                    <option value="<?= $period['period'] ?>" <?= $period['period'] === $filter_period ? 'selected' : '' ?>>
                                        <?= date('F Y', strtotime($period['period'] . '-01')) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button name="reset_perbulan" type="submit" class="btn btn-warning w-100"
                            onclick="return confirm('Yakin ingin menghapus jadwal untuk periode yang dipilih?')">
                            🧹 Reset Jadwal per Bulan
                        </button>
                    </div>
                </form>

                <!-- Form Reset Semua Jadwal -->
                <form method="post" class="mt-3">
                    <button name="reset_jadwal" type="submit" class="btn btn-outline-secondary w-100"
                        onclick="return confirm('⚠️ PERINGATAN: Yakin ingin menghapus SEMUA jadwal yang sudah ada? Tindakan ini tidak dapat dibatalkan.')">
                        ❌ Reset Semua Jadwal
                    </button>
                </form>
            </div>
        </div>

        <hr>

        <!-- Form Filter Jadwal -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">Filter Jadwal</div>
            <div class="card-body">
                <form method="get" class="filter-form row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_period" class="form-label">Periode</label>
                        <select name="filter_period" id="filter_period" class="form-control">
                            <option value="">-- Semua Periode --</option>
                            <?php foreach ($available_periods as $period): ?>
                                <option value="<?= $period['period'] ?>" <?= $period['period'] === $filter_period ? 'selected' : '' ?>>
                                    <?= date('F Y', strtotime($period['period'] . '-01')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_peleton" class="form-label">Peleton</label>
                        <select name="filter_peleton" id="filter_peleton" class="form-control">
                            <option value="">-- Semua Peleton --</option>
                            <?php foreach ($peletons as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['id'] == $filter_peleton ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_regu" class="form-label">Regu</label>
                        <select name="filter_regu" id="filter_regu" class="form-control">
                            <option value="">-- Semua Regu --</option>
                            <?php foreach ($regus as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $r['id'] == $filter_regu ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_pos" class="form-label">Pos</label>
                        <select name="filter_pos" id="filter_pos" class="form-control">
                            <option value="">-- Semua Pos --</option>
                            <?php foreach ($poses as $pos): ?>
                                <option value="<?= $pos['id'] ?>" <?= $pos['id'] == $filter_pos ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pos['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_status" class="form-label">Status</label>
                        <select name="filter_status" id="filter_status" class="form-control">
                            <option value="">-- Semua Status --</option>
                            <?php $statuses = ['aktif', 'diganti', 'izin', 'cuti']; ?>
                            <?php foreach ($statuses as $stat): ?>
                                <option value="<?= $stat ?>" <?= $stat === $filter_status ? 'selected' : '' ?>>
                                    <?= ucfirst($stat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

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
                    $no = 1;
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
                    <?php if (empty($jadwals)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data jadwal yang sesuai dengan filter.</td>
                        </tr>
                    <?php endif; ?>
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
                                <?php foreach ($pegawais_all as $peg): ?>
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