<?php
// FILE: setup_database.php - Skrip untuk inisialisasi Database dan Tabel.
// SUPER ROBUST VERSION: Cek keberadaan kolom/FK sebelum ALTER TABLE.

// 1. Konfigurasi Koneksi Database (HARUS SESUAI dengan config/db.php Anda)
$servername = "localhost";
$username = "root"; // Ganti dengan username MySQL Anda
$password = ""; // Ganti dengan password MySQL Anda
$dbname = "damkar_scheduler";

// Konfigurasi Akun Admin Awal
$admin_username = "admin";
$admin_password_raw = "1234"; // Ganti dengan password kuat Anda
$admin_password_hash = password_hash($admin_password_raw, PASSWORD_DEFAULT);

// 2. Query SQL untuk Membuat Database dan Tabel Dasar (Gunakan IF NOT EXISTS)
$sql_create_commands = [
    // 1. Membuat Database (jika belum ada)
    "CREATE DATABASE IF NOT EXISTS $dbname;",
    "USE $dbname;",

    // 2. Membuat Tabel pegawai
    "CREATE TABLE IF NOT EXISTS pegawai (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) UNIQUE,
password_hash VARCHAR(255),
nama VARCHAR(150),
nip VARCHAR(50),
pangkat VARCHAR(50),
golongan VARCHAR(50),
ruang VARCHAR(50),
jabatan VARCHAR(100),
no_hp VARCHAR(20) NULL,
telegram_id VARCHAR(50) NULL,
tugas TEXT,
role ENUM('admin','petugas') DEFAULT 'petugas',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);",

    // 3. Membuat Tabel peleton
    "CREATE TABLE IF NOT EXISTS peleton (
id INT AUTO_INCREMENT PRIMARY KEY,
nama VARCHAR(50) UNIQUE
);",

    // 4. Membuat Tabel pos
    "CREATE TABLE IF NOT EXISTS pos (
id INT AUTO_INCREMENT PRIMARY KEY,
nama VARCHAR(100),
alamat TEXT
);",

    // 5. Membuat Tabel regu
    "CREATE TABLE IF NOT EXISTS regu (
id INT AUTO_INCREMENT PRIMARY KEY,
peleton_id INT,
pos_id INT NULL,
nama VARCHAR(50),
FOREIGN KEY (peleton_id) REFERENCES peleton(id) ON DELETE SET NULL,
FOREIGN KEY (pos_id) REFERENCES pos(id) ON DELETE SET NULL
);",

    // 6. Membuat Tabel pegawai_regu
    "CREATE TABLE IF NOT EXISTS pegawai_regu (
id INT AUTO_INCREMENT PRIMARY KEY,
pegawai_id INT,
regu_id INT,
pos_id INT,
FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE,
FOREIGN KEY (regu_id) REFERENCES regu(id) ON DELETE SET NULL,
FOREIGN KEY (pos_id) REFERENCES pos(id) ON DELETE SET NULL
);",

    // 7. Membuat Tabel jadwal
    "CREATE TABLE IF NOT EXISTS jadwal (
id INT AUTO_INCREMENT PRIMARY KEY,
tanggal DATE,
slot VARCHAR(50),
pegawai_id INT,
regu_id INT,
peleton_id INT,
pos_id INT,
status ENUM('aktif','diganti','izin','cuti') DEFAULT 'aktif',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE SET NULL
);",

    // 8. Membuat Tabel izin
    "CREATE TABLE IF NOT EXISTS izin (
id INT AUTO_INCREMENT PRIMARY KEY,
pegawai_id INT,
jenis ENUM('khusus_h1','umum_cuti','sakit'),
tanggal_mulai DATE,
tanggal_selesai DATE,
alasan TEXT,
lampiran VARCHAR(255),
status ENUM('pending','diterima','ditolak') DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);",

    // 9. Membuat Tabel notifikasi (Notifikasi Internal Dashboard)
    "CREATE TABLE IF NOT EXISTS notifikasi (
id INT AUTO_INCREMENT PRIMARY KEY,
pegawai_id INT NULL,
message TEXT,
type VARCHAR(50),
is_read TINYINT(1) DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);",

    // 10. Tabel Pengaturan Notifikasi
    "CREATE TABLE IF NOT EXISTS notification_settings (
id INT AUTO_INCREMENT PRIMARY KEY,
key_name VARCHAR(50) UNIQUE NOT NULL, 
time_offset INT NOT NULL, /* Dalam MENIT */
message_template TEXT NOT NULL,
is_active BOOLEAN DEFAULT 1,
last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);",

    // 11. Tabel Log Notifikasi
    "CREATE TABLE IF NOT EXISTS notification_log (
id INT AUTO_INCREMENT PRIMARY KEY,
jadwal_id INT NULL, /* Referensi ke jadwal yang di-notif */
pegawai_id INT NOT NULL, /* Pegawai penerima */
notification_type VARCHAR(50) NOT NULL, 
message_sent TEXT NULL, 
sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE,
FOREIGN KEY (jadwal_id) REFERENCES jadwal(id) ON DELETE SET NULL
);",
];

// 3. Proses Eksekusi Query Dasar
echo "<h1>Setup Database Damkar Scheduler</h1>";

// Koneksi ke server MySQL (tanpa menentukan database)
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Eksekusi semua command SQL CREATE
foreach ($sql_create_commands as $command) {
    if ($conn->query($command) === TRUE) {
        if (strpos($command, "CREATE TABLE IF NOT EXISTS") !== false && $conn->affected_rows == 0) {
            echo "<p style='color: orange;'>⚠️ Sudah ada: " . htmlspecialchars(substr($command, 0, 50)) . "...</p>";
        } else {
            echo "<p style='color: green;'>✅ Berhasil dibuat/digunakan: " . htmlspecialchars(substr($command, 0, 50)) . "...</p>";
        }
    } else {
        if (strpos($command, "CREATE DATABASE") === false && strpos($command, "USE $dbname") === false) {
            echo "<p style='color: red;'>❌ Error pada query: " . $conn->error . " | Query: " . htmlspecialchars(substr($command, 0, 50)) . "...</p>";
        }
    }
}

// 4. Proses ALTER TABLE (Pengecekan Kolom/FK)

// Koneksi ke database spesifik
$conn_db = new mysqli($servername, $username, $password, $dbname);

if ($conn_db->connect_error) {
    die("Koneksi ke database '$dbname' gagal: " . $conn_db->connect_error);
}

function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function foreignKeyExists($conn, $table, $constraintName) {
    $query = "
        SELECT 
            COUNT(*)
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE 
            TABLE_SCHEMA = DATABASE() AND
            TABLE_NAME = '$table' AND
            CONSTRAINT_NAME = '$constraintName'
    ";
    $result = $conn->query($query);
    return $result && $result->fetch_array()[0] > 0;
}


// --- ALTER TABLE MAINTENANCE ---

// 4.1. Tabel Pegawai: Tambahkan kolom no_hp dan telegram_id
$pegawai_alters = [
    'no_hp' => "ALTER TABLE `pegawai` ADD COLUMN `no_hp` VARCHAR(20) NULL AFTER `jabatan`",
    'telegram_id' => "ALTER TABLE `pegawai` ADD COLUMN `telegram_id` VARCHAR(50) NULL AFTER `no_hp`",
];

foreach ($pegawai_alters as $col => $query) {
    if (!columnExists($conn_db, 'pegawai', $col)) {
        if ($conn_db->query($query)) {
            echo "<p style='color: blue;'>🔄 Berhasil diubah: Tabel Pegawai, ditambahkan kolom `$col`.</p>";
        } else {
            echo "<p style='color: red;'>❌ Error ALTER Pegawai `$col`: " . $conn_db->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Sudah ada: Tabel Pegawai sudah memiliki kolom `$col`.</p>";
    }
}


// 4.2. Tabel Regu: Tambahkan pos_id dan FK (Ini yang menyebabkan error Anda)
// Kolom pos_id sudah ada di CREATE TABLE, tapi ini untuk update skema lama.
if (!columnExists($conn_db, 'regu', 'pos_id')) {
    if ($conn_db->query("ALTER TABLE `regu` ADD COLUMN `pos_id` INT NULL AFTER `peleton_id`")) {
        echo "<p style='color: blue;'>🔄 Berhasil diubah: Tabel Regu, ditambahkan kolom `pos_id`.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error ALTER Regu `pos_id`: " . $conn_db->error . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Sudah ada: Tabel Regu sudah memiliki kolom `pos_id`.</p>";
}

// Tambahkan Foreign Key fk_regu_pos jika belum ada
if (!foreignKeyExists($conn_db, 'regu', 'fk_regu_pos')) {
    if ($conn_db->query("ALTER TABLE `regu` ADD CONSTRAINT `fk_regu_pos` FOREIGN KEY (`pos_id`) REFERENCES `pos` (`id`) ON DELETE SET NULL")) {
        echo "<p style='color: blue;'>🔄 Berhasil diubah: Tabel Regu, ditambahkan FK `fk_regu_pos`.</p>";
    } else {
        // FK sudah ada tapi namanya beda, atau error lain. Kita hanya tangani error duplikasi
        $error = $conn_db->error;
        if (strpos($error, "a foreign key constraint already exists") !== false) {
             echo "<p style='color: orange;'>⚠️ Sudah ada: Tabel Regu sudah memiliki Foreign Key untuk `pos_id`.</p>";
        } else {
             echo "<p style='color: red;'>❌ Error ALTER Regu FK: " . $error . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ Sudah ada: Tabel Regu sudah memiliki Foreign Key `fk_regu_pos`.</p>";
}


// 5. Menambahkan Akun Admin
// Cek apakah akun admin sudah ada
$check_sql = "SELECT id FROM pegawai WHERE username = ?";
$stmt_check = $conn_db->prepare($check_sql);
$stmt_check->bind_param("s", $admin_username);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    // Jika belum ada, lakukan INSERT
    $insert_sql = "INSERT INTO pegawai (username, password_hash, nama, role) VALUES (?, ?, ?, 'admin')";
    $stmt_insert = $conn_db->prepare($insert_sql);
    $nama_admin = "Administrator Utama";
    $stmt_insert->bind_param("sss", $admin_username, $admin_password_hash, $nama_admin);

    if ($stmt_insert->execute()) {
        echo "<p style='color: blue;'>⭐ Berhasil menambahkan akun Admin:</p>";
        echo "<ul><li>Username: <b>$admin_username</b></li><li>Password: <b>$admin_password_raw</b> (HARAP SEGERA GANTI!)</li></ul>";
    } else {
        echo "<p style='color: red;'>❌ Gagal menambahkan akun Admin: " . $conn_db->error . "</p>";
    }
    $stmt_insert->close();
} else {
    echo "<p style='color: orange;'>⚠️ Akun Admin ($admin_username) sudah ada dalam database. Tidak ada penambahan data.</p>";
}

$stmt_check->close();
$conn->close();
$conn_db->close();
echo "<h2>Proses Setup Selesai!</h2>";
?>