-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2025 at 07:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hidrosmart`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `admin_id`, `session_id`, `ip_address`, `user_agent`, `last_activity`) VALUES
(3505, 1, 't50cndovflo35k5q9v0o8gr23v', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-28 05:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `id_saran` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `pesan` text DEFAULT NULL,
  `tanggal_submit` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`id_saran`, `id_pengguna`, `subject`, `pesan`, `tanggal_submit`, `read_at`) VALUES
(1, 2, 'Pertanyaan tentang Fitur HidroSmart Tumbler', 'Saya tertarik dengan produk HidroSmart Tumbler. Bisakah Anda menjelaskan lebih detail tentang fitur pengingat minum? Apakah bisa disesuaikan dengan jadwal pribadi saya?', '2025-06-28 10:28:42', '2025-06-29 23:05:48'),
(4, 2, 'Informasi Spesifikasi HidroSmart Tumbler', 'Saya tertarik dengan produk HidroSmart Tumbler edisi terbaru. Bisakah Anda berikan detail tentang:\r\n\r\n1. Kapasitas baterai dan lama pengisian daya\r\n2. Material yang digunakan untuk bagian dalam tumbler\r\n\r\nTerima kasih,', '2025-07-04 10:42:18', '2025-07-04 10:46:30'),
(9, 3, 'Apresiasi untuk Customer Service', 'Saya ingin berterima kasih kepada tim CS atas bantuan menyelesaikan masalah garansi saya kemarin. Responnya sangat cepat dan solutif. Khususnya untuk Bpk/Ibu [nama CS jika diketahui] yang sangat profesional.', '2025-07-07 04:58:31', '2025-07-07 06:05:11'),
(31, 2, 'asdasdasdasd', 'asdasdasdasdasd', '2025-07-13 15:24:27', NULL),
(32, 2, 'Syarat Distributor', 'Apa persyaratan menjadi distributor resmi?', '2025-07-27 14:14:29', NULL),
(33, 2, 'Syarat Distributor', 'Apa persyaratan menjadi distributor resmi?', '2025-07-27 14:16:01', NULL);

--
-- Triggers `contact`
--
DELIMITER $$
CREATE TRIGGER `after_contact_insert` AFTER INSERT ON `contact` FOR EACH ROW BEGIN
    DECLARE user_name VARCHAR(255);
    SELECT name INTO user_name FROM pengguna WHERE id_pengguna = NEW.id_pengguna;
    
    INSERT INTO notifikasi (tipe_aktivitas, pesan, id_pengguna, id_referensi) 
    VALUES ('contact', CONCAT('Pesan baru dari ', COALESCE(user_name, 'Unknown'), ': ', NEW.subject), NEW.id_pengguna, NEW.id_saran);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `new_email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `id_pengguna`, `new_email`, `token`, `expires_at`) VALUES
(1, 3, 'niiju.nana@gmail.com', '8be981209bcabe447c523d7c8d94c97c1e54cc7fd628e61622d4b8754ae28048', '2025-07-07 20:09:45'),
(2, 3, 'niiju.nana@gmail.com', '33f173da2316953c1406efea4375cd7ffd3fb6604869ca8313f7bc4e48178cb2', '2025-07-07 20:09:53'),
(3, 3, 'niiju.nana@gmail.com', '6e05ad093d84ed72d0aedeb5d56d57a33fb9d87bf30e25b4f0c26d893b1edc10', '2025-07-07 20:10:35'),
(4, 3, 'niiju.nana@gmail.com', '8193c231a13caf7c4b2b9b590b9e64e367e4cf1949d1e77a804c3acdcc1b1503', '2025-07-07 20:12:38'),
(5, 3, 'niiju.nana@gmail.com', 'b96f1eb1d925aa09aa5c3bd9076493866080d3238075e959e039c01c078634e8', '2025-07-07 20:13:59'),
(6, 3, 'niiju.nana@gmail.com', 'f870d4ce281b31251f21b7ce0020cf0c9c348e05715b18572b7b0948420b39d5', '2025-07-07 20:18:52'),
(7, 3, 'niiju.nana@gmail.com', 'dde7360dff7f3d3c10714cac4322a86c6d8a55befa2c3ff75a6621b538394013', '2025-07-08 03:39:59'),
(8, 3, 'niiju.nana@gmail.com', '88a7931dbfc29a0c28ba39998d72cb66318bbc6011aea2ca2f0d4e693bf5171f', '2025-07-08 03:42:57');

-- --------------------------------------------------------

--
-- Table structure for table `guarantee`
--

CREATE TABLE `guarantee` (
  `id_guarantee` varchar(50) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `id_order` varchar(10) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `bukti_gambar` varchar(255) DEFAULT NULL,
  `status_klaim` enum('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
  `tanggal_klaim` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_respon` timestamp NULL DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guarantee`
--

INSERT INTO `guarantee` (`id_guarantee`, `id_pengguna`, `id_order`, `deskripsi`, `bukti_gambar`, `status_klaim`, `tanggal_klaim`, `tanggal_respon`, `catatan_admin`) VALUES
('GR202506293328', 2, 'HISM200001', 'terdapat keretakan waktu datang ke rumah', 'HISM200001_1751181082.jpg', 'disetujui', '2025-06-29 07:11:22', '2025-06-30 00:23:43', 'Klaim garansi disetujui'),
('GR202507065707', 2, 'HISM02DB33', 'Baterai tidak bisa menyimpan daya, harus di-charge setiap hari padahal sebelumnya bisa tahan 1 minggu. Indikator baterai di aplikasi juga tidak akurat', 'HISM02DB33_1751778990.jpg', 'ditolak', '2025-07-06 05:16:30', '2025-07-06 05:19:01', 'gambar dan issu yang anda jelaskan tidak match'),
('GR202507074446', 3, 'HISM03C711', 'terjadi keterakan pada tumbler', 'HISM03C711_1751865384.jpg', 'ditolak', '2025-07-07 05:16:24', '2025-07-07 05:38:10', 'gambar yang anda uploud tidak sama dengan product yang anda pesan'),
('GR202507078568', 3, 'HISM032299', 'Sensor Dehidrasi tidak berfungsi akibat jatuh dari mobil', 'HISM032299_1751868079.jpg', 'disetujui', '2025-07-07 06:01:19', '2025-07-07 06:03:37', 'Kami menyetujui klaim garansi Anda. Tim kami akan segera memproses penggantian unit baru dan akan menghubungi Anda dalam 1-2 hari kerja untuk konfirmasi pengiriman. Terima kasih atas kepercayaan Anda pada HidroSmart'),
('GR202507079225', 3, 'HISM031CDF', 'Terjadi retakan pada tumbler saat menaiki sepeda motor', 'HISM031CDF_1751874870.jpg', 'disetujui', '2025-07-07 07:54:30', '2025-07-07 07:55:54', 'Klaim garansi kita terima, akan kami hubungi dalam waktu 2x24 jam, terima kasih'),
('GR202507286684', 2, 'HISM02F5DF', 'tumbler bocor setelah jatuh dari lantai 5', 'HISM02F5DF_1753664001.jpg', 'menunggu', '2025-07-28 00:53:21', NULL, NULL);

--
-- Triggers `guarantee`
--
DELIMITER $$
CREATE TRIGGER `after_guarantee_insert` AFTER INSERT ON `guarantee` FOR EACH ROW BEGIN
    DECLARE user_name VARCHAR(255);
    SELECT name INTO user_name FROM pengguna WHERE id_pengguna = NEW.id_pengguna;
    
    INSERT INTO notifikasi (tipe_aktivitas, pesan, id_pengguna, id_referensi) 
    VALUES ('guarantee', CONCAT('Klaim garansi baru dari ', COALESCE(user_name, 'Unknown'), ' untuk order #', NEW.id_order), NEW.id_pengguna, NEW.id_guarantee);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `tipe_aktivitas` enum('contact','guarantee','order','review','suggestion') NOT NULL,
  `pesan` text NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `id_referensi` int(11) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp(),
  `dibaca` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `tipe_aktivitas`, `pesan`, `id_pengguna`, `id_referensi`, `waktu`, `dibaca`) VALUES
(1, 'order', 'Pesanan baru #HISM02FCD6 dari yuesa', 2, 0, '2025-07-06 01:41:01', 0),
(3, 'guarantee', 'Klaim garansi baru dari yuesa untuk order #HISM02F5DF', 2, 0, '2025-07-06 05:02:17', 0),
(4, 'order', 'Pesanan baru #HISM0221A0 dari yuesa', 2, 0, '2025-07-06 05:10:41', 0),
(5, 'guarantee', 'Klaim garansi baru dari yuesa untuk order #HISM02DB33', 2, 0, '2025-07-06 05:16:30', 0),
(6, 'order', 'Pesanan baru #HISM032299 dari ananta', 3, 0, '2025-07-06 09:50:32', 0),
(7, 'order', 'Pesanan baru #HISM03C711 dari ananta', 3, 0, '2025-07-06 09:56:56', 0),
(8, 'order', 'Pesanan baru #HISM036C5D dari ananta', 3, 0, '2025-07-06 10:11:14', 0),
(9, 'review', 'Ulasan baru dari yuesa untuk order #HISM02DB33', 2, 6, '2025-07-06 15:46:27', 0),
(10, 'contact', 'Pesan baru dari Anantaa: Apresiasi untuk Customer Service', 3, 9, '2025-07-07 04:58:31', 0),
(11, 'guarantee', 'Klaim garansi baru dari Anantaa untuk order #HISM03C711', 3, 0, '2025-07-07 05:16:24', 0),
(12, 'order', 'Pesanan baru #HISM033BD0 dari Anantaa', 3, 0, '2025-07-07 05:18:52', 0),
(13, 'guarantee', 'Klaim garansi baru dari Anantaa untuk order #HISM032299', 3, 0, '2025-07-07 06:01:19', 0),
(14, 'contact', 'Pesan baru dari Anantaa: apresiasi', 3, 10, '2025-07-07 06:30:32', 0),
(15, 'contact', 'Pesan baru dari Anantaa: Apresiasi untuk Customer', 3, 11, '2025-07-07 06:33:13', 0),
(16, 'order', 'Pesanan baru #HISM031CDF dari Anantaa', 3, 0, '2025-07-07 06:34:34', 0),
(17, 'order', 'Pesanan baru #HISM060D63 dari Nadia Puji Saputri', 6, 0, '2025-07-07 07:05:53', 0),
(20, 'guarantee', 'Klaim garansi baru dari Anantaa untuk order #HISM031CDF', 3, 0, '2025-07-07 07:54:30', 0),
(21, 'review', 'Ulasan baru dari Anantaa untuk order #HISM031CDF', 3, 7, '2025-07-07 07:57:33', 0),
(22, 'order', 'Pesanan baru #HISM03FBF4 dari Anantaa', 3, 0, '2025-07-07 08:01:49', 0),
(23, 'contact', 'Pesan baru dari yuesa: Bekerja Sama', 2, 13, '2025-07-13 04:44:51', 0),
(24, 'contact', 'Pesan baru dari yuesa: Konsultasi Pemeliharaan Rutin', 2, 14, '2025-07-13 06:50:56', 0),
(25, 'contact', 'Pesan baru dari yuesa: Konsultasi Pemeliharaan Rutin', 2, 15, '2025-07-13 10:23:08', 0),
(26, 'contact', 'Pesan baru dari yuesa: Konsultasi Pemeliharaan Rutin', 2, 16, '2025-07-13 10:39:22', 0),
(27, 'contact', 'Pesan baru dari yuesa: Konsultasi Pemeliharaan Rutin', 2, 17, '2025-07-13 10:39:54', 0),
(28, 'contact', 'Pesan baru dari yuesa: Konsultasi Pemeliharaan Rutin', 2, 18, '2025-07-13 10:54:16', 0),
(29, 'contact', 'Pesan baru dari yuesa: Penawaran Kerjasama Distribusi', 2, 19, '2025-07-13 11:36:27', 0),
(30, 'contact', 'Pesan baru dari yuesa: Penawaran Kerjasama Distribusi', 2, 20, '2025-07-13 11:38:59', 0),
(31, 'contact', 'Pesan baru dari yuesa: Konsultasi Pemeliharaan Rutin', 2, 21, '2025-07-13 11:53:26', 0),
(32, 'contact', 'Pesan baru dari yuesa: sasasasasa', 2, 22, '2025-07-13 12:00:17', 0),
(33, 'contact', 'Pesan baru dari yuesa: asasasasasa', 2, 23, '2025-07-13 12:01:10', 0),
(34, 'contact', 'Pesan baru dari yuesa: ajajnsanasca', 2, 24, '2025-07-13 13:05:36', 0),
(35, 'contact', 'Pesan baru dari yuesa: asdasdsadasdasda', 2, 25, '2025-07-13 13:07:31', 0),
(36, 'contact', 'Pesan baru dari yuesa: asasxasxasxa', 2, 26, '2025-07-13 13:20:35', 0),
(37, 'contact', 'Pesan baru dari yuesa: asdasdasd', 2, 27, '2025-07-13 13:25:03', 0),
(38, 'contact', 'Pesan baru dari yuesa: sdasdasdasd', 2, 28, '2025-07-13 14:27:08', 0),
(39, 'contact', 'Pesan baru dari yuesa: asdasdasdass', 2, 29, '2025-07-13 14:45:19', 0),
(40, 'contact', 'Pesan baru dari yuesa: sasasasasas', 2, 30, '2025-07-13 14:58:28', 0),
(41, 'contact', 'Pesan baru dari yuesa: asdasdasdasd', 2, 31, '2025-07-13 15:24:27', 0),
(42, 'order', 'Pesanan baru #HISM024193 dari yuesa', 2, 0, '2025-07-27 11:24:13', 0),
(43, 'contact', 'Pesan baru dari yuesa: Syarat Distributor', 2, 32, '2025-07-27 14:14:29', 0),
(44, 'contact', 'Pesan baru dari yuesa: Syarat Distributor', 2, 33, '2025-07-27 14:16:01', 0),
(45, 'guarantee', 'Klaim garansi baru dari yuesa untuk order #HISM02F5DF', 2, 0, '2025-07-28 00:53:21', 0),
(46, 'order', 'Pesanan baru #HISM022B90 dari yuesa', 2, 0, '2025-07-28 01:37:10', 0),
(47, 'order', 'Pesanan baru #HISM026C3F dari yuesa', 2, 0, '2025-07-28 01:39:01', 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_logs`
--

CREATE TABLE `order_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_logs`
--

INSERT INTO `order_logs` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(5, 2, 'order_created', '{\"order_id\":\"HISM02439D72\",\"color\":\"white\",\"quantity\":\"2\",\"total\":613000}', '2025-06-29 07:14:46'),
(6, 2, 'order_created', '{\"order_id\":\"HISM02D5063E\",\"color\":\"gray\",\"quantity\":\"3\",\"total\":912000}', '2025-06-29 07:54:27'),
(8, 2, 'order_created', '{\"order_id\":\"HISM02F7E5C7\",\"color\":\"black\",\"quantity\":\"2\",\"total\":613000}', '2025-06-30 05:42:28'),
(9, 2, 'order_initiated', '{\"order_id\":\"HISM02D5E0AB\",\"color\":\"black\",\"quantity\":\"1\",\"total\":314000}', '2025-06-30 11:16:46'),
(18, 2, 'order_initiated', '{\"order_id\":\"HISM02F5DFB8\",\"color\":\"gray\",\"quantity\":\"1\",\"total\":314000}', '2025-07-05 12:20:25'),
(19, 2, 'order_initiated', '{\"order_id\":\"HISM0233FECB\",\"color\":\"white\",\"quantity\":\"2\",\"total\":613000}', '2025-07-05 12:28:15'),
(20, 2, 'order_initiated', '{\"order_id\":\"HISM02DB3312\",\"color\":\"blue\",\"quantity\":\"1\",\"total\":314000}', '2025-07-05 13:01:50'),
(21, 2, 'order_initiated', '{\"order_id\":\"HISM02FCD600\",\"color\":\"white\",\"quantity\":\"2\",\"total\":613000}', '2025-07-06 01:40:12'),
(22, 2, 'order_initiated', '{\"order_id\":\"HISM0221A029\",\"color\":\"white\",\"quantity\":\"2\",\"total\":613000}', '2025-07-06 05:09:49'),
(23, 3, 'order_initiated', '{\"order_id\":\"HISM03AF5806\",\"color\":\"white\",\"quantity\":\"2\",\"total\":613000}', '2025-07-06 09:35:58'),
(24, 3, 'order_initiated', '{\"order_id\":\"HISM03229919\",\"color\":\"white\",\"quantity\":\"1\",\"total\":314000}', '2025-07-06 09:46:27'),
(25, 3, 'order_initiated', '{\"order_id\":\"HISM03C711CF\",\"color\":\"blue\",\"quantity\":\"1\",\"total\":314000}', '2025-07-06 09:53:10'),
(26, 3, 'order_initiated', '{\"order_id\":\"HISM036C5D8C\",\"color\":\"gray\",\"quantity\":\"1\",\"total\":314000}', '2025-07-06 10:07:57'),
(27, 3, 'order_initiated', '{\"order_id\":\"HISM033BD0DF\",\"color\":\"black\",\"quantity\":\"2\",\"total\":613000}', '2025-07-07 05:16:57'),
(28, 3, 'order_initiated', '{\"order_id\":\"HISM031CDFB6\",\"color\":\"black\",\"quantity\":\"1\",\"total\":314000}', '2025-07-07 06:34:14'),
(29, 6, 'order_initiated', '{\"order_id\":\"HISM060D6309\",\"color\":\"gray\",\"quantity\":\"1\",\"total\":314000}', '2025-07-07 07:03:41'),
(31, 3, 'order_initiated', '{\"order_id\":\"HISM03FBF4D8\",\"color\":\"black\",\"quantity\":\"2\",\"total\":613000}', '2025-07-07 08:00:58'),
(32, 2, 'order_initiated', '{\"order_id\":\"HISM022DDA6B\",\"color\":\"black\",\"quantity\":\"1\",\"total\":314000}', '2025-07-13 03:10:20'),
(33, 2, 'order_initiated', '{\"order_id\":\"HISM024193CC\",\"color\":\"white\",\"quantity\":\"1\",\"total\":314000}', '2025-07-27 11:20:23'),
(34, 2, 'order_initiated', '{\"order_id\":\"HISM0234E789\",\"color\":\"white\",\"quantity\":\"1\",\"total\":314000}', '2025-07-27 11:25:29'),
(35, 2, 'order_initiated', '{\"order_id\":\"HISM02392A22\",\"color\":\"black\",\"quantity\":\"1\",\"total\":314000}', '2025-07-28 01:11:08'),
(36, 2, 'order_initiated', '{\"order_id\":\"HISM022B90B8\",\"color\":\"black\",\"quantity\":\"2\",\"total\":613000}', '2025-07-28 01:11:59'),
(37, 2, 'order_initiated', '{\"order_id\":\"HISM026C3F40\",\"color\":\"black\",\"quantity\":\"2\",\"total\":613000}', '2025-07-28 01:38:52'),
(38, 2, 'order_initiated', '{\"order_id\":\"HISM0277643B\",\"color\":\"black\",\"quantity\":\"2\",\"total\":613000}', '2025-07-28 02:12:22');

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `status` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_tracking`
--

INSERT INTO `order_tracking` (`id`, `order_id`, `status`, `description`, `created_at`) VALUES
(8, 'HISM02D5E0', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-06-30 11:17:26'),
(20, 'HISM02F5DF', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-05 12:20:31'),
(22, 'HISM02DB33', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-05 13:01:54'),
(23, 'HISM02FCD6', 'Pembayaran Dikonfirmasi', 'Bukti pembayaran e-wallet telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.', '2025-07-06 01:41:01'),
(27, 'HISM02F5DF', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-06 04:54:21'),
(28, 'HISM02F5DF', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-06 05:00:01'),
(29, 'HISM02F5DF', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-06 05:00:31'),
(30, 'HISM0221A0', 'Pembayaran Dikonfirmasi', 'Bukti pembayaran e-wallet telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.', '2025-07-06 05:10:41'),
(31, 'HISM02DB33', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-06 05:11:09'),
(32, 'HISM02DB33', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-06 05:11:14'),
(33, 'HISM02DB33', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-06 05:11:19'),
(34, 'HISM02D5E0', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-06 05:43:59'),
(35, 'HISM02D5E0', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-06 05:44:16'),
(36, 'HISM02D5E0', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-06 05:44:21'),
(37, 'HISM032299', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-06 09:50:32'),
(38, 'HISM03C711', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-06 09:56:56'),
(44, 'HISM03C711', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-07 04:51:07'),
(45, 'HISM03C711', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-07 04:52:10'),
(46, 'HISM03C711', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-07 04:52:50'),
(47, 'HISM033BD0', 'Pembayaran Dikonfirmasi', 'Bukti transfer telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.', '2025-07-07 05:18:52'),
(48, 'HISM032299', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-07 05:44:01'),
(49, 'HISM032299', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-07 05:44:40'),
(50, 'HISM032299', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-07 05:45:18'),
(51, 'HISM031CDF', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-07 06:34:34'),
(52, 'HISM033BD0', 'Pesanan Dibuat', 'Pesanan berhasil dibuat dan menunggu pembayaran', '2025-07-07 06:37:04'),
(53, 'HISM031CDF', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-07 06:38:55'),
(54, 'HISM031CDF', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-07 06:41:43'),
(55, 'HISM060D63', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-07 07:05:53'),
(57, 'HISM031CDF', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-07 07:52:52'),
(58, 'HISM03FBF4', 'Pembayaran Dikonfirmasi', 'Bukti transfer telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.', '2025-07-07 08:01:49'),
(59, 'HISM03FBF4', 'Pesanan Dibuat', 'Pesanan berhasil dibuat dan menunggu pembayaran', '2025-07-07 08:10:12'),
(60, 'HISM03FBF4', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-07 08:10:31');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(6, 'yuesa.saka@gmail.com', 'a38873ed077aee8b6a606a1551035b74be895bbe8057c9962efa0cc90a7937dd', '2025-07-06 17:05:35', '2025-07-06 14:05:35'),
(26, 'zayd.alkhalifii@gmail.com', '08590983dc6686f26e4c1b850ac86f908655282243f0379ebae7e646b9260e5d', '2025-07-07 11:05:17', '2025-07-07 08:05:17');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id_order` varchar(10) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `kuantitas` int(11) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `metode_pembayaran` varchar(20) NOT NULL,
  `ongkir` int(11) DEFAULT NULL,
  `subtotal_harga` int(11) DEFAULT NULL,
  `total_harga` int(11) DEFAULT NULL,
  `tanggal_transaksi` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pesanan Dibuat','Pembayaran Dikonfirmasi','Sedang Dikemas','Sedang Dalam Perjalanan','Diterima Customer') DEFAULT 'Pesanan Dibuat',
  `bukti_transfer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`id_order`, `id_pengguna`, `kuantitas`, `color`, `metode_pembayaran`, `ongkir`, `subtotal_harga`, `total_harga`, `tanggal_transaksi`, `status`, `bukti_transfer`) VALUES
('HISM0221A0', 2, 2, 'white', 'ewallet', 15000, 598000, 613000, '2025-07-06 05:10:41', 'Pembayaran Dikonfirmasi', 'HISM0221A029_1751778641.jpg'),
('HISM02D5E0', 2, 1, 'black', 'cod', 15000, 299000, 314000, '2025-06-30 11:17:26', 'Diterima Customer', NULL),
('HISM02DB33', 2, 1, 'blue', 'cod', 15000, 299000, 314000, '2025-07-05 13:01:54', 'Diterima Customer', NULL),
('HISM02F5DF', 2, 1, 'gray', 'cod', 15000, 299000, 314000, '2025-07-05 12:20:31', 'Diterima Customer', NULL),
('HISM02FCD6', 2, 2, 'white', 'ewallet', 15000, 598000, 613000, '2025-07-06 01:41:01', 'Pembayaran Dikonfirmasi', 'HISM02FCD600_1751766061.jpeg'),
('HISM031CDF', 3, 1, 'black', 'cod', 15000, 299000, 314000, '2025-07-07 06:34:34', 'Diterima Customer', NULL),
('HISM032299', 3, 1, 'white', 'cod', 15000, 299000, 314000, '2025-07-06 09:50:32', 'Diterima Customer', NULL),
('HISM033BD0', 3, 2, 'black', 'bank_transfer', 15000, 598000, 613000, '2025-07-07 05:18:52', 'Pesanan Dibuat', 'HISM033BD0DF_1751865532.jpg'),
('HISM03C711', 3, 1, 'blue', 'cod', 15000, 299000, 314000, '2025-07-06 09:56:56', 'Diterima Customer', NULL),
('HISM03FBF4', 3, 2, 'black', 'bank_transfer', 15000, 598000, 613000, '2025-07-07 08:01:49', 'Sedang Dikemas', 'HISM03FBF4D8_1751875309.jpg'),
('HISM060D63', 6, 1, 'gray', 'cod', 15000, 299000, 314000, '2025-07-07 07:05:53', 'Pesanan Dibuat', NULL),
('HISM200001', 2, 1, 'black', 'bank', 15000, 299000, 314000, '2025-06-28 17:00:00', 'Diterima Customer', NULL);

--
-- Triggers `payment`
--
DELIMITER $$
CREATE TRIGGER `after_order_insert` AFTER INSERT ON `payment` FOR EACH ROW BEGIN
    DECLARE user_name VARCHAR(255);
    SELECT name INTO user_name FROM pengguna WHERE id_pengguna = NEW.id_pengguna;
    
    INSERT INTO notifikasi (tipe_aktivitas, pesan, id_pengguna, id_referensi) 
    VALUES ('order', CONCAT('Pesanan baru #', NEW.id_order, ' dari ', COALESCE(user_name, 'Unknown')), NEW.id_pengguna, NEW.id_order);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `email` char(50) DEFAULT NULL,
  `role` tinyint(4) NOT NULL DEFAULT 1,
  `phone` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `session_token` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `name`, `password`, `email`, `role`, `phone`, `alamat`, `avatar`, `email_verified`, `verification_token`, `session_token`) VALUES
(1, 'admin', '$2y$10$vVJmoPrlSMc..xMCslt7IOpE.eks2KUehOUGE1CBcR9ttbol001oe', 'admin@hidrosmart.com', 2, NULL, NULL, NULL, 0, NULL, 't50cndovflo35k5q9v0o8gr23v'),
(2, 'yuesa', '$2y$10$TfWgr5GbeHlk0NR5tZrcJufiRBc0DWvT7mxY59rfN.IoGmpEOoeIi', 'yuesa.saka@gmail.com', 1, '+6281326143063', 'Jl. Kenanga No. 12, Bandung 55642', 'avatar_2_1751817837.jpeg', 1, NULL, '7kbs160vcm0cn87n7grvfl5heq'),
(3, 'Anantaa', '$2y$10$QYl2GBzKPoOEqkET7phDc.jSyWHiMOwk0ImwCtyWIKI.GzvRr/YTu', 'niiju.nana@gmail.com', 1, '+628725412671', 'Popongan RT 14 RW 20 55278 No. 129 Sinduadi, Mlati, Sleman', 'avatar_3_1751817980.jpeg', 1, NULL, NULL),
(6, 'Nadia Puji Saputri', NULL, 'nadia.puji@students.amikom.ac.id', 1, '+628132614306', 'ngaglik minomartani', 'avatar_6_1751871661.jpg', 1, NULL, NULL),
(8, 'alkhalifi', '$2y$10$YML4lXue0/TD9u9z379ZYeO1oDe/Wyexb0kySvDoAp2Ud5K8OGnQi', 'zayd.alkhalifii@gmail.com', 1, NULL, NULL, 'avatar_8_1752321641.jpg', 1, NULL, 'cf2o7nm5n7k35fmscls6albrdu'),
(10, 'Nanta', '$2y$10$Q3BmKyNiZ1AYLctx9fmy4.pOQpWPLVEDmCLznkd5pQ1ZxUTHLnTUG', 'ananta.mailme@gmail.com', 1, NULL, NULL, NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `user_id`, `order_id`, `rating`, `review_text`, `created_at`) VALUES
(4, 2, 'HISM200001', 5, 'mantaplah produk ini bisa melihat traker dehidrasi saya!!', '2025-06-29 07:09:38'),
(6, 2, 'HISM02DB33', 4, 'Sensor dehidrasi sangat memukau!!!!\r\n\r\nmembantuku untuk menjaga kadar air di tubuh, terimakasih hidrosmart!!!', '2025-07-06 15:46:27'),
(7, 3, 'HISM031CDF', 5, 'Produk sangat bagus, membatu untuk meliahat tingkat dehidrasi dalam tubuh saya', '2025-07-07 07:57:33');

--
-- Triggers `product_reviews`
--
DELIMITER $$
CREATE TRIGGER `after_review_insert` AFTER INSERT ON `product_reviews` FOR EACH ROW BEGIN
    DECLARE user_name VARCHAR(255);
    SELECT name INTO user_name FROM pengguna WHERE id_pengguna = NEW.user_id;
    
    INSERT INTO notifikasi (tipe_aktivitas, pesan, id_pengguna, id_referensi) 
    VALUES ('review', CONCAT('Ulasan baru dari ', COALESCE(user_name, 'Unknown'), ' untuk order #', NEW.order_id), NEW.user_id, NEW.id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `last_activity`) VALUES
(1956, 2, '7kbs160vcm0cn87n7grvfl5heq', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0', '2025-07-28 04:19:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_session` (`admin_id`,`session_id`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`id_saran`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `token_2` (`token`),
  ADD KEY `fk_email_verifications_user` (`id_pengguna`);

--
-- Indexes for table `guarantee`
--
ALTER TABLE `guarantee`
  ADD PRIMARY KEY (`id_guarantee`),
  ADD KEY `id_pengguna` (`id_pengguna`),
  ADD KEY `id_order` (`id_order`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `order_logs`
--
ALTER TABLE `order_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id_order`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_session` (`user_id`,`session_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3609;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `id_saran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `order_logs`
--
ALTER TABLE `order_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1959;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE;

--
-- Constraints for table `contact`
--
ALTER TABLE `contact`
  ADD CONSTRAINT `contact_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE;

--
-- Constraints for table `guarantee`
--
ALTER TABLE `guarantee`
  ADD CONSTRAINT `guarantee_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE,
  ADD CONSTRAINT `guarantee_ibfk_2` FOREIGN KEY (`id_order`) REFERENCES `payment` (`id_order`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE;

--
-- Constraints for table `order_logs`
--
ALTER TABLE `order_logs`
  ADD CONSTRAINT `order_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `fk_tracking_order` FOREIGN KEY (`order_id`) REFERENCES `payment` (`id_order`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`email`) REFERENCES `pengguna` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `payment` (`id_order`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
