-- MySQL schema for Damkar Scheduler (demo)
CREATE DATABASE IF NOT EXISTS damkar_scheduler;
USE damkar_scheduler;

-- pegawai
CREATE TABLE IF NOT EXISTS pegawai (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password_hash VARCHAR(255),
  nama VARCHAR(150),
  nip VARCHAR(50),
  pangkat VARCHAR(50),
  golongan VARCHAR(50),
  ruang VARCHAR(50),
  jabatan VARCHAR(100),
  tugas TEXT,
  role ENUM('admin','petugas') DEFAULT 'petugas',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- peleton
CREATE TABLE IF NOT EXISTS peleton (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(50) UNIQUE
);

-- regu
CREATE TABLE IF NOT EXISTS regu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  peleton_id INT,
  nama VARCHAR(50),
  FOREIGN KEY (peleton_id) REFERENCES peleton(id) ON DELETE SET NULL
);

-- pos
CREATE TABLE IF NOT EXISTS pos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100),
  alamat TEXT
);

-- pegawai_regu mapping
CREATE TABLE IF NOT EXISTS pegawai_regu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pegawai_id INT,
  regu_id INT,
  pos_id INT,
  FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE,
  FOREIGN KEY (regu_id) REFERENCES regu(id) ON DELETE SET NULL,
  FOREIGN KEY (pos_id) REFERENCES pos(id) ON DELETE SET NULL
);

-- jadwal
CREATE TABLE IF NOT EXISTS jadwal (
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
);

-- izin
CREATE TABLE IF NOT EXISTS izin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pegawai_id INT,
  jenis ENUM('khusus_h1','umum_cuti','sakit'),
  tanggal_mulai DATE,
  tanggal_selesai DATE,
  alasan TEXT,
  lampiran VARCHAR(255),
  status ENUM('pending','diterima','ditolak') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- notifikasi (log)
CREATE TABLE IF NOT EXISTS notifikasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pegawai_id INT NULL,
  message TEXT,
  type VARCHAR(50),
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);