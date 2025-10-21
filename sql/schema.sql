CREATE DATABASE IF NOT EXISTS damkar_scheduler;
USE damkar_scheduler;

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    nama VARCHAR(100)
);

CREATE TABLE pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    nama VARCHAR(100),
    nip VARCHAR(30),
    pangkat VARCHAR(50),
    golongan VARCHAR(20),
    ruang VARCHAR(20),
    jabatan VARCHAR(50),
    tugas VARCHAR(50),
    phone VARCHAR(20)
);

CREATE TABLE peleton (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50)
);

CREATE TABLE regu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_peleton INT,
    nama VARCHAR(50),
    FOREIGN KEY (id_peleton) REFERENCES peleton(id) ON DELETE CASCADE
);

CREATE TABLE pos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50)
);

CREATE TABLE jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pegawai INT,
    id_pos INT,
    tanggal DATE,
    shift VARCHAR(20),
    status ENUM('aktif','ijin','cuti') DEFAULT 'aktif',
    FOREIGN KEY (id_pegawai) REFERENCES pegawai(id),
    FOREIGN KEY (id_pos) REFERENCES pos(id)
);

CREATE TABLE cuti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pegawai INT,
    jenis ENUM('ijin','cuti'),
    alasan TEXT,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    status ENUM('pending','disetujui','ditolak') DEFAULT 'pending',
    FOREIGN KEY (id_pegawai) REFERENCES pegawai(id)
);