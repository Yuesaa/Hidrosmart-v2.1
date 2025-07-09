-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2025 at 10:34 AM
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
(1191, 1, '48ji0p1qpgvjg662hiqbrv7j7u', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-07-06 08:32:51');

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
(6, 4, 'cara kerja sensor', 'bagaimana cara kerja sensor dehidrasi pada tumbler hidrosmart', '2025-07-04 13:04:20', '2025-07-05 07:13:07'),
(7, 5, 'Penawaran Kerjasama Reseller', 'Saya pemilik toko perlengkapan olahraga di Bandung dan tertarik menjadi reseller produk HidroSmart. Mohon informasi:\r\n\r\n1. Minimum order quantity\r\n2. Harga khusus reseller\r\n3. Syarat dan ketentuan kerjasama', '2025-07-05 01:50:41', '2025-07-06 01:59:00'),
(8, 5, 'Apresiasi untuk Customer Service', 'Saya ingin berterima kasih kepada tim CS atas bantuan menyelesaikan masalah garansi saya kemarin. Responnya sangat cepat dan solutif. Khususnya untuk Bpk/Ibu [nama CS jika diketahui] yang sangat profesional.', '2025-07-05 03:25:41', '2025-07-05 15:04:35');

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
('GR202507048673', 4, 'HISM0417BF', 'bagian samping tumbler pecah', 'HISM0417BF_1751638486.jpg', 'disetujui', '2025-07-04 14:14:46', '2025-07-04 14:15:35', 'Klaim garansi disetujui'),
('GR202507061711', 5, 'HISM054A14', 'Sensor tidak bisa berfungsi', 'HISM054A14_1751776252.jpg', 'disetujui', '2025-07-06 04:30:52', '2025-07-06 04:34:27', 'Klaim garansi disetujui'),
('GR202507065707', 2, 'HISM02DB33', 'Baterai tidak bisa menyimpan daya, harus di-charge setiap hari padahal sebelumnya bisa tahan 1 minggu. Indikator baterai di aplikasi juga tidak akurat', 'HISM02DB33_1751778990.jpg', 'ditolak', '2025-07-06 05:16:30', '2025-07-06 05:19:01', 'gambar dan issu yang anda jelaskan tidak match'),
('GR202507068566', 2, 'HISM02F5DF', 'saat sampai ke rumah ada kerusakan pada badan tumbler', 'HISM02F5DF_1751778137.jpg', 'ditolak', '2025-07-06 05:02:17', '2025-07-06 05:04:41', 'foto anda tidak sesuai dengan color product');

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
(2, 'guarantee', 'Klaim garansi baru dari Laneoooo untuk order #HISM054A14', 5, 0, '2025-07-06 04:30:52', 0),
(3, 'guarantee', 'Klaim garansi baru dari yuesa untuk order #HISM02F5DF', 2, 0, '2025-07-06 05:02:17', 0),
(4, 'order', 'Pesanan baru #HISM0221A0 dari yuesa', 2, 0, '2025-07-06 05:10:41', 0),
(5, 'guarantee', 'Klaim garansi baru dari yuesa untuk order #HISM02DB33', 2, 0, '2025-07-06 05:16:30', 0);

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
(11, 4, 'order_initiated', '{\"order_id\":\"HISM0413F4B9\",\"color\":\"gray\",\"quantity\":\"1\",\"total\":314000}', '2025-07-04 13:15:00'),
(12, 4, 'order_initiated', '{\"order_id\":\"HISM04E93979\",\"color\":\"gray\",\"quantity\":\"1\",\"total\":314000}', '2025-07-04 13:25:53'),
(13, 4, 'order_initiated', '{\"order_id\":\"HISM04B6270B\",\"color\":\"white\",\"quantity\":\"1\",\"total\":314000}', '2025-07-04 13:30:54'),
(14, 4, 'order_initiated', '{\"order_id\":\"HISM0417BFFD\",\"color\":\"blue\",\"quantity\":\"1\",\"total\":314000}', '2025-07-04 13:33:10'),
(15, 5, 'order_initiated', '{\"order_id\":\"HISM054A1411\",\"color\":\"blue\",\"quantity\":\"2\",\"total\":613000}', '2025-07-05 04:11:57'),
(16, 5, 'order_initiated', '{\"order_id\":\"HISM059550BA\",\"color\":\"black\",\"quantity\":\"1\",\"total\":314000}', '2025-07-05 06:23:44'),
(17, 5, 'order_initiated', '{\"order_id\":\"HISM0546503B\",\"color\":\"white\",\"quantity\":\"1\",\"total\":314000}', '2025-07-05 06:37:45'),
(18, 2, 'order_initiated', '{\"order_id\":\"HISM02F5DFB8\",\"color\":\"gray\",\"quantity\":\"1\",\"total\":314000}', '2025-07-05 12:20:25'),
(19, 2, 'order_initiated', '{\"order_id\":\"HISM0233FECB\",\"color\":\"white\",\"quantity\":\"2\",\"total\":613000}', '2025-07-05 12:28:15'),
(20, 2, 'order_initiated', '{\"order_id\":\"HISM02DB3312\",\"color\":\"blue\",\"quantity\":\"1\",\"total\":314000}', '2025-07-05 13:01:50'),
(21, 2, 'order_initiated', '{\"order_id\":\"HISM02FCD600\",\"color\":\"white\",\"quantity\":\"2\",\"total\":613000}', '2025-07-06 01:40:12'),
(22, 2, 'order_initiated', '{\"order_id\":\"HISM0221A029\",\"color\":\"white\",\"quantity\":\"2\",\"total\":613000}', '2025-07-06 05:09:49');

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
(10, 'HISM04E939', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-04 13:27:39'),
(12, 'HISM0417BF', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-04 13:34:05'),
(13, 'HISM04E939', 'Sedang Dikemas', 'Product anda sedang dikemas', '2025-07-04 13:36:39'),
(14, 'HISM04E939', 'Diterima Customer', 'pesanan anda berhasil diterima customer', '2025-07-04 13:42:59'),
(15, 'HISM0417BF', 'Sedang Dalam Perjalanan', 'sedang dalam perjalanan', '2025-07-04 14:03:28'),
(16, 'HISM0417BF', 'Diterima Customer', 'product telah sampai pada customer', '2025-07-04 14:03:49'),
(17, 'HISM054A14', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-05 04:12:08'),
(18, 'HISM059550', 'Pembayaran Dikonfirmasi', 'Bukti transfer telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.', '2025-07-05 06:24:48'),
(20, 'HISM02F5DF', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-05 12:20:31'),
(22, 'HISM02DB33', 'Pesanan Dibuat', 'Pesanan Anda telah dikonfirmasi dengan metode COD (Cash on Delivery). Produk akan segera diproses dan dikirim ke alamat tujuan.', '2025-07-05 13:01:54'),
(23, 'HISM02FCD6', 'Pembayaran Dikonfirmasi', 'Bukti pembayaran e-wallet telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.', '2025-07-06 01:41:01'),
(24, 'HISM054A14', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-06 03:46:09'),
(25, 'HISM054A14', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-06 03:46:51'),
(26, 'HISM054A14', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-06 03:47:21'),
(27, 'HISM02F5DF', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-06 04:54:21'),
(28, 'HISM02F5DF', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-06 05:00:01'),
(29, 'HISM02F5DF', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-06 05:00:31'),
(30, 'HISM0221A0', 'Pembayaran Dikonfirmasi', 'Bukti pembayaran e-wallet telah diterima dan sedang dalam proses verifikasi oleh tim kami. Kami akan mengkonfirmasi pembayaran dalam 1x24 jam.', '2025-07-06 05:10:41'),
(31, 'HISM02DB33', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-06 05:11:09'),
(32, 'HISM02DB33', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-06 05:11:14'),
(33, 'HISM02DB33', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-06 05:11:19'),
(34, 'HISM02D5E0', 'Sedang Dikemas', 'Pesanan sedang dikemas di gudang', '2025-07-06 05:43:59'),
(35, 'HISM02D5E0', 'Sedang Dalam Perjalanan', 'Pesanan sedang dalam perjalanan ke alamat tujuan', '2025-07-06 05:44:16'),
(36, 'HISM02D5E0', 'Diterima Customer', 'Pesanan telah diterima oleh customer. Terima kasih!', '2025-07-06 05:44:21');

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
('HISM0417BF', 4, 1, 'blue', 'cod', 15000, 299000, 314000, '2025-07-04 13:34:05', 'Diterima Customer', NULL),
('HISM04E939', 4, 1, 'gray', 'cod', 15000, 299000, 314000, '2025-07-04 13:27:39', 'Diterima Customer', NULL),
('HISM054A14', 5, 2, 'blue', 'cod', 15000, 598000, 613000, '2025-07-05 04:12:08', 'Diterima Customer', NULL),
('HISM059550', 5, 1, 'black', 'bank_transfer', 15000, 299000, 314000, '2025-07-05 06:24:48', 'Pembayaran Dikonfirmasi', 'HISM059550BA_1751696688.jpeg'),
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
  `verification_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `name`, `password`, `email`, `role`, `phone`, `alamat`, `avatar`, `email_verified`, `verification_token`) VALUES
(1, 'admin', '$2y$10$vVJmoPrlSMc..xMCslt7IOpE.eks2KUehOUGE1CBcR9ttbol001oe', 'admin@hidrosmart.com', 2, NULL, NULL, NULL, 0, NULL),
(2, 'yuesa', '$2y$10$TfWgr5GbeHlk0NR5tZrcJufiRBc0DWvT7mxY59rfN.IoGmpEOoeIi', 'yuesa.saka@gmail.com', 1, '+6285421129871', 'Jl. Kenanga No. 12, Bandung 55642', 'avatar_2_1751183858.jpg', 0, NULL),
(4, 'nanda', '$2y$10$mu7hrQJAC3YS20Eobm7VTew5k5vKITg.FEOtQrT8uYbIOT1ip4MVe', 'nanda123@gmail.com', 1, '+6285168186318', 'Jl. Imogiri Gunung Kidul Desa Pertruk RT 06 RW 05', 'avatar_4_1751634307.jpeg', 0, NULL),
(5, 'Laneoooo', '$2y$10$No9dKIMwctCtrYW.LdE4geKiuStt3YWCq7bo6o9dokx2ENlKTLPg.', 'laneo12@gmail.com', 1, '+6289652429667', 'Popongan RT 24 RW 30 55787 No. 231 Sindudi, Mlati, Sleman, Yogyakarta', 'avatar_5_1751682950.jpeg', 0, NULL),
(6, 'ananta', '$2y$10$D/ZhhVn7lKpGmgsAhQ8F2O7ZF.tZDR5bC7/LZsAbvmjku8xC6MscO', 'ananta.mailme@gmail.com', 1, NULL, NULL, NULL, 0, 'be6bf57e91ba58938f677edeec72c87df8949fa98da967fc9f3b479102859b81');

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
(5, 4, 'HISM04E939', 4, 'Productnya bagus, ukurannya cocok dengan yang saya inginkan\r\n\r\nTerimakasih Admin!!', '2025-07-04 13:48:06');

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
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id_order`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1230;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `id_saran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_logs`
--
ALTER TABLE `order_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1051;

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
