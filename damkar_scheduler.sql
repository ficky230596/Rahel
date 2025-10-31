-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Okt 2025 pada 19.02
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
  `jenis` enum('izin','sakit') DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `alasan` text DEFAULT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `status` enum('pending','diterima','ditolak') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `izin`
--

INSERT INTO `izin` (`id`, `pegawai_id`, `jenis`, `tanggal_mulai`, `tanggal_selesai`, `alasan`, `lampiran`, `status`, `created_at`) VALUES
(1, 5, '', '2025-10-23', '2025-10-24', 'istri hamil', NULL, 'diterima', '2025-10-21 03:16:00'),
(2, 5, '', '2025-10-21', '2025-10-28', 'healling', NULL, 'ditolak', '2025-10-21 06:52:23'),
(3, 6, '', '2025-10-25', '2025-10-26', 'belum makan', 'uploads/izin/izin_68fb30b5c2aab_gunnn.jpeg', 'diterima', '2025-10-24 07:54:29'),
(4, 6, '', '2025-10-25', '2025-10-26', 'demam', 'uploads/izin/izin_68fb3c4a1c397_17807331_396376427400869_8321405002576538803_o.jpg', 'diterima', '2025-10-24 08:43:54'),
(5, 6, 'sakit', '2025-10-25', '2025-10-20', 'muntah', 'uploads/izin/izin_68fb3d55e5502_17807331_396376427400869_8321405002576538803_o.jpg', 'ditolak', '2025-10-24 08:48:21'),
(6, 6, '', '2025-10-23', '2025-10-26', 'makan minum ', NULL, 'ditolak', '2025-10-24 09:20:25'),
(7, 6, 'sakit', '2025-10-25', '2025-10-27', 'uvuvbub', NULL, 'pending', '2025-10-24 09:22:03'),
(8, 6, '', '2025-10-25', '2025-10-28', 'izin', 'uploads/izin/izin_68fb4568de4f3_delgio1.png', 'pending', '2025-10-24 09:22:48'),
(9, 6, '', '2025-10-25', '2025-10-28', 'kkkkk', NULL, 'pending', '2025-10-24 09:24:41'),
(10, 6, '', '2025-10-28', '2025-10-31', 'HRTGERTG', NULL, 'pending', '2025-10-24 09:26:15'),
(11, 6, '', '2025-10-25', '2025-10-29', 'fefefegf', NULL, 'pending', '2025-10-24 09:29:06'),
(12, 6, '', '2025-10-25', '2025-10-29', 'fefefef', NULL, 'diterima', '2025-10-24 09:29:24'),
(13, 6, 'sakit', '2025-10-30', '2025-10-27', 'hgh65h ', NULL, 'pending', '2025-10-24 09:29:49'),
(14, 6, 'izin', '2025-10-27', '2025-10-29', 'last', NULL, 'ditolak', '2025-10-24 09:30:24'),
(15, 9, 'izin', '2025-10-25', '2025-10-26', 'Patah Hati', 'uploads/izin/izin_68fb640c88477_logo.PNG', 'diterima', '2025-10-24 11:33:32');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `apel_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `tanggal`, `slot`, `pegawai_id`, `regu_id`, `peleton_id`, `pos_id`, `status`, `created_at`, `apel_time`) VALUES
(317, '2025-10-24', 'pagi', 9, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(318, '2025-10-24', 'pagi', 10, 2, 2, 2, 'izin', '2025-10-24 15:28:11', '00:00:00'),
(319, '2025-10-25', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(320, '2025-10-25', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(321, '2025-10-25', 'pagi', 7, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(322, '2025-10-25', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(323, '2025-10-26', 'pagi', 6, 1, 5, 2, 'aktif', '2025-10-24 15:28:11', '01:56:00'),
(324, '2025-10-27', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(325, '2025-10-28', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(326, '2025-10-28', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(327, '2025-10-28', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(328, '2025-10-29', 'pagi', 6, 1, 5, 2, 'aktif', '2025-10-24 15:28:11', '01:54:00'),
(329, '2025-10-30', 'pagi', 9, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(330, '2025-10-30', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(331, '2025-10-31', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(332, '2025-10-31', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(333, '2025-10-31', 'pagi', 7, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(334, '2025-10-31', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(335, '2025-11-01', 'pagi', 6, 1, 5, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(336, '2025-11-02', 'pagi', 9, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(337, '2025-11-02', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(338, '2025-11-03', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(339, '2025-11-03', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(340, '2025-11-03', 'pagi', 7, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(341, '2025-11-03', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(342, '2025-11-04', 'pagi', 6, 1, 5, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(343, '2025-11-05', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(344, '2025-11-06', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(345, '2025-11-06', 'pagi', 7, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(346, '2025-11-06', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(347, '2025-11-08', 'pagi', 9, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(348, '2025-11-08', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(349, '2025-11-09', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(350, '2025-11-09', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(351, '2025-11-09', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(352, '2025-11-11', 'pagi', 9, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(353, '2025-11-11', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(354, '2025-11-12', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(355, '2025-11-12', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(356, '2025-11-12', 'pagi', 7, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(357, '2025-11-12', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(358, '2025-11-13', 'pagi', 6, 1, 5, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(359, '2025-11-14', 'pagi', 9, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(360, '2025-11-14', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(361, '2025-11-15', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(362, '2025-11-15', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(363, '2025-11-15', 'pagi', 7, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(364, '2025-11-15', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(365, '2025-11-17', 'pagi', 9, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(366, '2025-11-17', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(367, '2025-11-18', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(368, '2025-11-18', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(369, '2025-11-18', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(370, '2025-11-19', 'pagi', 6, 1, 5, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(371, '2025-11-20', 'pagi', 9, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(372, '2025-11-20', 'pagi', 10, 2, 2, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(373, '2025-11-21', 'pagi', 8, 3, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(374, '2025-11-21', 'pagi', 8, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(375, '2025-11-21', 'pagi', 5, 4, 3, 1, 'aktif', '2025-10-24 15:28:11', '00:00:00'),
(376, '2025-11-22', 'pagi', 6, 1, 5, 2, 'aktif', '2025-10-24 15:28:11', '00:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notification_log`
--

CREATE TABLE `notification_log` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) DEFAULT NULL,
  `pegawai_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `message_sent` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notification_log`
--

INSERT INTO `notification_log` (`id`, `jadwal_id`, `pegawai_id`, `notification_type`, `message_sent`, `sent_at`) VALUES
(2, 328, 6, 'before_call', 'Peringatan: Apel akan segera dimulai dalam 30 menit!\n\nFriska Regina Maralantang dari Peleton YUDAH, segera merapat ke MANDOLANG!', '2025-10-25 01:31:33'),
(3, 323, 6, 'before_duty', 'Selamat Pagi, Friska Regina Maralantang!\r\n\r\nAnda memiliki tugas besok:\r\n- Peleton: YUDAH\r\n- Regu: 10\r\n- Pos: MANDOLANG\r\n- Tugas: Tugas Regu\r\n- Tanggal Tugas: 26-10-2025\r\n\r\nHarap siapkan diri!', '2025-10-25 01:56:01'),
(4, 323, 6, 'before_call', 'Peringatan: Apel akan segera dimulai dalam 30 menit!\r\n\r\nFriska Regina Maralantang dari Peleton YUDAH, segera merapat ke MANDOLANG!', '2025-10-25 01:56:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notification_settings`
--

CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(50) NOT NULL,
  `time_offset` int(11) NOT NULL,
  `message_template` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `key_name`, `time_offset`, `message_template`, `is_active`, `last_updated`) VALUES
(1, 'before_duty', 1440, 'Selamat Pagi, [NAMA]!\r\n\r\nAnda memiliki tugas besok:\r\n- Peleton: [PELETON]\r\n- Regu: [REGU]\r\n- Pos: [POS]\r\n- Tugas: [TUGAS]\r\n- Tanggal Tugas: [TANGGAL]\r\n\r\nHarap siapkan diri!', 1, '2025-10-24 17:15:37'),
(2, 'before_call', 30, 'Peringatan: Apel akan segera dimulai dalam [MENIT] menit!\r\n\r\n[NAMA] dari Peleton [PELETON], segera merapat ke [POS]!', 1, '2025-10-21 04:25:22');

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
-- Struktur dari tabel `notifikasi_log`
--

CREATE TABLE `notifikasi_log` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `tanggal_kirim` date DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `status_kirim` varchar(50) DEFAULT NULL,
  `respons_api` text DEFAULT NULL,
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
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `pangkat` varchar(50) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `telegram_id` varchar(50) DEFAULT NULL,
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

INSERT INTO `pegawai` (`id`, `username`, `password_hash`, `nama`, `jenis_kelamin`, `nip`, `pangkat`, `no_hp`, `telegram_id`, `golongan`, `ruang`, `jabatan`, `tugas`, `role`, `created_at`) VALUES
(2, 'admin', '$2y$10$M.6lRqN6tlYHOaQDgJ7BHOoXKhry7Q3nGQSUyPZdS1.xY3l5Vjh0S', 'Administrator Utama', 'Laki-laki', NULL, NULL, '6282248139051', NULL, NULL, NULL, NULL, NULL, 'admin', '2025-10-20 16:52:28'),
(5, 'Petugas1', '$2y$10$4HyjpjtLE.YoeEF3x.qHION15Z79xEbm5G9.35/j29AYX0SO9pQLe', 'budi', 'Laki-laki', '1234567890', '3C', '6282248139051', NULL, 'III', 'A', 'Kepala Dinas', 'Jaga', 'petugas', '2025-10-21 03:05:48'),
(6, 'Petugas2', '$2y$10$9DIN4lmnB92UcdKE0RoZSu3s4S0pl6n1aMIRozO09aibR0EE6b7ja', 'Friska Regina Maralantang', 'Perempuan', '0987654321', '5', '6282248139051', NULL, 'Penata muda', 'Lapangan', '', 'Selang', 'petugas', '2025-10-21 03:14:13'),
(7, 'Petugas3', '$2y$10$iCWBqvO/e.uQcnMd6kt9PuAAigbjrnaWqqncDGtvLbyWQFj7Uhd1y', 'ing', 'Laki-laki', '09876543215', '3C', '6282197928207', NULL, 'Penata Muda 3', 'A', 'Anggota', 'Selang', 'petugas', '2025-10-21 03:52:10'),
(8, 'Petugas4', '$2y$10$gavFAlvJd/SqSdtLtW9Tge6ZR46EmCM35fSW8lE.iPXJmmDs2gKJu', 'Marko', 'Perempuan', '35359309', 'Listrik', '62858248502844', NULL, 'II', 'C', '', 'Selang', 'petugas', '2025-10-21 12:17:22'),
(9, 'user23', '$2y$10$wtClVT0iVubFONbf1/9Uo.3w0C0v.DJir4abwixjsn57gCuTDxNtG', 'Mario', 'Laki-laki', '3423ffefef', '4C', '6289531012296', NULL, 'Penata Muda 3', 'A', 'Guru', 'Selang', 'petugas', '2025-10-23 17:51:52'),
(10, 'user2', '$2y$10$ug1LvS3hSY6rd2anBuTl/uPO/R6mpgtVG6pYWSpbisatEmVxHeBe2', 'TPA Suwung', 'Laki-laki', '43452353rt', 'staf', '6289531012296', NULL, 'Penata Muda 3', 'A', 'Kepala Sekolah', 'Selang', 'petugas', '2025-10-23 18:03:59');

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
(14, 9, 2, NULL),
(15, 8, 3, NULL),
(16, 8, 4, NULL),
(19, 7, 4, NULL),
(20, 10, 2, NULL),
(21, 5, 4, NULL),
(23, 6, 1, NULL);

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
(2, 'BRAMA'),
(3, 'JAYA'),
(5, 'YUDAH');

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
(1, 'KAWANGKOWAN', 'Kawangkowan'),
(2, 'MANDOLANG', 'Mandolang'),
(3, 'KANTOR', 'TONDANO'),
(4, 'LANGOWAN', 'Langowan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `regu`
--

CREATE TABLE `regu` (
  `id` int(11) NOT NULL,
  `peleton_id` int(11) DEFAULT NULL,
  `pos_id` int(11) DEFAULT NULL,
  `nama` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `regu`
--

INSERT INTO `regu` (`id`, `peleton_id`, `pos_id`, `nama`) VALUES
(1, 5, 2, '10'),
(2, 2, 2, '10'),
(3, 3, 1, '10'),
(4, 3, 1, '70'),
(5, 5, 3, '70');

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
-- Indeks untuk tabel `notification_log`
--
ALTER TABLE `notification_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `jadwal_id` (`jadwal_id`);

--
-- Indeks untuk tabel `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifikasi_log`
--
ALTER TABLE `notifikasi_log`
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
  ADD KEY `peleton_id` (`peleton_id`),
  ADD KEY `fk_regu_pos` (`pos_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `izin`
--
ALTER TABLE `izin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=377;

--
-- AUTO_INCREMENT untuk tabel `notification_log`
--
ALTER TABLE `notification_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `notifikasi_log`
--
ALTER TABLE `notifikasi_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `pegawai_regu`
--
ALTER TABLE `pegawai_regu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `peleton`
--
ALTER TABLE `peleton`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pos`
--
ALTER TABLE `pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `regu`
--
ALTER TABLE `regu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `notification_log`
--
ALTER TABLE `notification_log`
  ADD CONSTRAINT `notification_log_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_log_ibfk_2` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_regu_pos` FOREIGN KEY (`pos_id`) REFERENCES `pos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `regu_ibfk_1` FOREIGN KEY (`peleton_id`) REFERENCES `peleton` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
