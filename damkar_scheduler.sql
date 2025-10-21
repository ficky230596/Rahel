-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Okt 2025 pada 20.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `damkar_scheduler`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `izin`
--

CREATE TABLE `izin` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `jenis` enum('khusus_h1','umum_cuti','sakit') DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `alasan` text DEFAULT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `status` enum('pending','diterima','ditolak') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `slot` varchar(50) DEFAULT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `regu_id` int(11) DEFAULT NULL,
  `peleton_id` int(11) DEFAULT NULL,
  `pos_id` int(11) DEFAULT NULL,
  `status` enum('aktif','diganti','izin','cuti') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `pangkat` varchar(50) DEFAULT NULL,
  `golongan` varchar(50) DEFAULT NULL,
  `ruang` varchar(50) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `tugas` text DEFAULT NULL,
  `role` enum('admin','petugas') DEFAULT 'petugas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pegawai`
--

INSERT INTO `pegawai` (`id`, `username`, `password_hash`, `nama`, `nip`, `pangkat`, `golongan`, `ruang`, `jabatan`, `tugas`, `role`, `created_at`) VALUES
(2, 'admin', '$2y$10$M.6lRqN6tlYHOaQDgJ7BHOoXKhry7Q3nGQSUyPZdS1.xY3l5Vjh0S', 'Administrator Utama', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', '2025-10-20 16:52:28'),
(3, 'user', '$2y$10$A7Adj4cTtt8c.x6AMiHyL.hdr7xILbZHoTgKyuB7YeO4l8qRCak6a', 'Basilius Mario Vikranta Rahanubun', '1234567890', '3C', 'Penata Muda 3', 'A', '', 'Danru', 'petugas', '2025-10-20 17:10:10'),
(4, 'user2', '$2y$10$YLcVEgSNJm4GE2.5JyUuW.cN25zKEnl3OMILCAuYkHFDpM8LYCf8G', 'marko', '124124', 'r1re12', 'r141', '4', '4141', '4124', 'petugas', '2025-10-20 18:14:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai_regu`
--

CREATE TABLE `pegawai_regu` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `regu_id` int(11) DEFAULT NULL,
  `pos_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pegawai_regu`
--

INSERT INTO `pegawai_regu` (`id`, `pegawai_id`, `regu_id`, `pos_id`) VALUES
(1, 3, 1, NULL),
(2, 4, 3, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `peleton`
--

CREATE TABLE `peleton` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peleton`
--

INSERT INTO `peleton` (`id`, `nama`) VALUES
(2, 'Brama'),
(3, 'Jaya'),
(1, 'Yudah');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pos`
--

CREATE TABLE `pos` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pos`
--

INSERT INTO `pos` (`id`, `nama`, `alamat`) VALUES
(1, 'Kawangkowan', 'Tondano 1');

-- --------------------------------------------------------

--
-- Struktur dari tabel `regu`
--

CREATE TABLE `regu` (
  `id` int(11) NOT NULL,
  `peleton_id` int(11) DEFAULT NULL,
  `nama` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `regu`
--

INSERT INTO `regu` (`id`, `peleton_id`, `nama`) VALUES
(1, 1, '10'),
(2, 2, '10'),
(3, 3, '10');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `izin`
--
ALTER TABLE `izin`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `pegawai_regu`
--
ALTER TABLE `pegawai_regu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `regu_id` (`regu_id`),
  ADD KEY `pos_id` (`pos_id`);

--
-- Indeks untuk tabel `peleton`
--
ALTER TABLE `peleton`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indeks untuk tabel `pos`
--
ALTER TABLE `pos`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `regu`
--
ALTER TABLE `regu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peleton_id` (`peleton_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `izin`
--
ALTER TABLE `izin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pegawai_regu`
--
ALTER TABLE `pegawai_regu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `peleton`
--
ALTER TABLE `peleton`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pos`
--
ALTER TABLE `pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `regu`
--
ALTER TABLE `regu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pegawai_regu`
--
ALTER TABLE `pegawai_regu`
  ADD CONSTRAINT `pegawai_regu_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pegawai_regu_ibfk_2` FOREIGN KEY (`regu_id`) REFERENCES `regu` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pegawai_regu_ibfk_3` FOREIGN KEY (`pos_id`) REFERENCES `pos` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `regu`
--
ALTER TABLE `regu`
  ADD CONSTRAINT `regu_ibfk_1` FOREIGN KEY (`peleton_id`) REFERENCES `peleton` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
