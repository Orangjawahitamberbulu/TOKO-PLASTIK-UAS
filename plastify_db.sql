-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 30, 2026 at 02:06 PM
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
-- Database: `plastify_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `cek_status_utang` ()   BEGIN
    DECLARE selesai INT DEFAULT 0;
    DECLARE vNama VARCHAR(100);
    DECLARE vSisa DECIMAL(10,2);
    DECLARE cur CURSOR FOR
        SELECT p.nama_pelanggan, u.sisa_utang FROM utang_pelanggan u 
        JOIN pelanggan p ON u.pelanggan_id=p.id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET selesai=1;
    OPEN cur;
    ulang:
    LOOP
        FETCH cur INTO vNama,vSisa;
        IF selesai=1 THEN
            LEAVE ulang;
        END IF;
        IF vSisa=0 THEN
            SELECT CONCAT(vNama,' : Lunas') AS Status;
        ELSE
            SELECT CONCAT(vNama,' : Belum Lunas') AS Status;
        END IF;
    END LOOP;
    CLOSE cur;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `tampil_transaksi` ()   BEGIN
    SELECT
        t.no_transaksi,
        t.tanggal_transaksi,
        p.nama_pelanggan,
        u.nama_lengkap AS kasir,
        t.total_belanja,
        t.metode_pembayaran,
        t.status_utang
    FROM transaksi t
    JOIN pelanggan p
        ON t.pelanggan_id = p.id
    JOIN users u
        ON t.kasir_id = u.id
    ORDER BY t.tanggal_transaksi DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `total_utang_pelanggan` (IN `p_pelanggan` INT, OUT `total` DECIMAL(10,2))   BEGIN
    SELECT SUM(sisa_utang)
    INTO total
    FROM utang_pelanggan
    WHERE pelanggan_id = p_pelanggan;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `hitung_subtotal` (`jumlah` INT, `harga` DECIMAL(10,2)) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    RETURN jumlah * harga;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `total_pelanggan_berutang` () RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE jumlah INT;

    SELECT COUNT(*)
    INTO jumlah
    FROM utang_pelanggan
    WHERE status_pembayaran <> 'lunas';

    RETURN jumlah;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int(11) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `nama_barang` varchar(200) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `harga_beli` decimal(10,2) NOT NULL,
  `margin_keuntungan` decimal(5,2) DEFAULT 20.00,
  `harga_jual` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `kode_barang`, `nama_barang`, `kategori_id`, `harga_beli`, `margin_keuntungan`, `harga_jual`, `stok`, `gambar`, `created_at`, `updated_at`) VALUES
(1, 'PL001', 'Plastik Apollo 3kg', 5, 11000.00, 20.00, 13000.00, 95, 'assets/img/barang/brg_6a621bd2313f1.jpg', '2026-07-23 13:49:06', '2026-07-23 15:17:11'),
(2, 'PL002', 'Plastik Apollo 2kg', 5, 7000.00, 20.00, 9000.00, 87, 'assets/img/barang/brg_6a621c8db141a.jpg', '2026-07-23 13:52:13', '2026-07-27 16:24:57'),
(3, 'PL003', 'Plastik Apollo 1kg', 5, 4500.00, 20.00, 5000.00, 81, 'assets/img/barang/brg_6a621cb05d3bc.jpg', '2026-07-23 13:52:48', '2026-07-27 11:08:32'),
(4, 'PL004', 'Plastik Apollo 1/2kg', 5, 2500.00, 20.00, 3000.00, 90, 'assets/img/barang/brg_6a621cd1dd8c4.jpg', '2026-07-23 13:53:21', '2026-07-23 15:17:11'),
(5, 'PL005', 'Plastik Apollo 1/4kg', 5, 2000.00, 20.00, 2500.00, 90, 'assets/img/barang/brg_6a621d19d02cb.jpg', '2026-07-23 13:54:33', '2026-07-27 11:09:43'),
(6, 'PL006', 'Plastik Apollo 1 Ons', 5, 1500.00, 20.00, 2000.00, 100, 'assets/img/barang/brg_6a621d4ca43ce.jpg', '2026-07-23 13:55:24', '2026-07-23 14:44:24'),
(7, 'PL007', 'Plastik Boyo 2kg', 5, 6500.00, 20.00, 7000.00, 100, 'assets/img/barang/brg_6a621dbe49a51.jpg', '2026-07-23 13:57:18', '2026-07-23 14:44:24'),
(8, 'PL008', 'Plastik Boyo 1kg', 5, 4500.00, 20.00, 5000.00, 92, 'assets/img/barang/brg_6a621e18f0173.jpg', '2026-07-23 13:58:48', '2026-07-27 11:11:54'),
(9, 'PL009', 'Plastik Boyo 1/2kg', 5, 2500.00, 20.00, 3000.00, 88, 'assets/img/barang/brg_6a621e413dcea.jpg', '2026-07-23 13:59:29', '2026-07-27 11:08:32'),
(10, 'PL010', 'Plastik Boyo 1/4kg', 5, 2000.00, 20.00, 2500.00, 99, 'assets/img/barang/brg_6a621e66c733a.jpg', '2026-07-23 14:00:06', '2026-07-23 15:14:51'),
(11, 'PL011', 'Plastik Boyo 1 1/2kg', 5, 6000.00, 20.00, 6500.00, 94, 'assets/img/barang/brg_6a621f16a3dca.jpg', '2026-07-23 14:03:02', '2026-07-27 11:07:36'),
(12, 'PL012', 'Plastik Es Maestro', 5, 7000.00, 20.00, 8500.00, 49, 'assets/img/barang/brg_6a621f8dc5894.jpg', '2026-07-23 14:05:01', '2026-07-23 15:14:09'),
(13, 'PL013', 'Plastik Cup Topaz', 5, 5000.00, 20.00, 6000.00, 44, 'assets/img/barang/brg_6a6222ce033dc.jpg', '2026-07-23 14:18:54', '2026-07-27 11:11:54'),
(14, 'PL014', 'Kresek Apollo Hitam Kecil', 5, 3500.00, 20.00, 4000.00, 145, 'assets/img/barang/brg_6a62233489964.jpg', '2026-07-23 14:20:36', '2026-07-27 11:10:14'),
(15, 'PL015', 'Kresek Apollo Hitam Tanggung', 5, 4000.00, 20.00, 5000.00, 148, 'assets/img/barang/brg_6a6223713fc6f.jpg', '2026-07-23 14:21:37', '2026-07-23 15:13:28'),
(16, 'PL016', 'Kresek Apollo Hitam Besar', 5, 6000.00, 20.00, 7000.00, 148, 'assets/img/barang/brg_6a6223973c485.jpg', '2026-07-23 14:22:15', '2026-07-27 11:07:36'),
(17, 'PL017', 'Kresek Lorek', 5, 10000.00, 20.00, 12000.00, 98, 'assets/img/barang/brg_6a622428727e5.jpg', '2026-07-23 14:24:40', '2026-07-23 15:17:11'),
(18, 'PL018', 'Kresek Merah Piala', 5, 15000.00, 20.00, 18000.00, 99, 'assets/img/barang/brg_6a62253ce333f.jpg', '2026-07-23 14:29:16', '2026-07-23 15:12:52'),
(19, 'PL019', 'Kertas Minyak 909 (250)', 7, 31000.00, 20.00, 33000.00, 99, 'assets/img/barang/brg_6a622612c44fc.jpg', '2026-07-23 14:32:50', '2026-07-23 15:59:18'),
(20, 'PL020', 'Kertas Minyak 909 (100)', 7, 12000.00, 20.00, 13000.00, 98, 'assets/img/barang/brg_6a62264c37350.jpg', '2026-07-23 14:33:48', '2026-07-26 03:03:48'),
(21, 'PL021', 'Kertas Minyak Fitri (250)', 7, 31000.00, 20.00, 33000.00, 95, 'assets/img/barang/brg_6a62269ca3a20.jpg', '2026-07-23 14:35:08', '2026-07-27 11:10:47'),
(22, 'PL022', 'Kertas Minyak Fitri (100)', 7, 12000.00, 20.00, 13000.00, 98, 'assets/img/barang/brg_6a6226c008be3.jpg', '2026-07-23 14:35:44', '2026-07-27 11:10:14'),
(23, 'PL023', 'Kertas Minyak Fitri (40)', 7, 4500.00, 20.00, 5000.00, 100, 'assets/img/barang/brg_6a6226dc3a117.jpg', '2026-07-23 14:36:12', '2026-07-23 15:59:59'),
(26, 'PL024', 'Cup Merak 10 oz', 9, 10000.00, 20.00, 13000.00, 48, 'assets/img/barang/brg_6a623498cc86c.jpg', '2026-07-23 15:34:48', '2026-07-27 11:11:29'),
(27, 'PL025', 'Cup Merak 12 oz', 9, 12000.00, 20.00, 13000.00, 43, 'assets/img/barang/brg_6a6234c35ed9f.jpg', '2026-07-23 15:35:31', '2026-07-27 11:09:43'),
(28, 'PL026', 'Cup Merak 16 oz', 9, 12000.00, 20.00, 13000.00, 49, 'assets/img/barang/brg_6a6234e761d82.jpg', '2026-07-23 15:36:07', '2026-07-26 03:01:36'),
(29, 'PL027', 'Cup Merak 18 oz', 9, 15000.00, 20.00, 17000.00, 50, 'assets/img/barang/brg_6a62350ae0c11.jpg', '2026-07-23 15:36:42', '2026-07-23 15:36:42'),
(30, 'PL028', 'Cup Merak 22 oz', 9, 18000.00, 20.00, 19000.00, 50, 'assets/img/barang/brg_6a6236c14dfb2.jpg', '2026-07-23 15:44:01', '2026-07-23 15:44:01'),
(31, 'PL029', 'Tisu Nice', 10, 6000.00, 20.00, 7000.00, 50, 'assets/img/barang/brg_6a6236f42b6a4.jpg', '2026-07-23 15:44:52', '2026-07-23 15:44:52'),
(32, 'PL030', 'Tisu Jolly Kecil', 10, 2000.00, 20.00, 3000.00, 100, 'assets/img/barang/brg_6a62371ac2613.jpg', '2026-07-23 15:45:30', '2026-07-23 15:46:29'),
(33, 'PL031', 'Tisu Jolly Besar', 10, 7000.00, 20.00, 8500.00, 50, 'assets/img/barang/brg_6a623749c152a.jpg', '2026-07-23 15:46:17', '2026-07-23 15:46:17'),
(34, 'PL032', 'Tisu Paseo Kecil', 10, 2500.00, 20.00, 3000.00, 100, 'assets/img/barang/brg_6a62378007005.jpg', '2026-07-23 15:47:12', '2026-07-23 15:47:12'),
(35, 'PL033', 'Tisu Paseo Sedang', 10, 7000.00, 20.00, 8500.00, 50, 'assets/img/barang/brg_6a6237b0cd6c7.jpg', '2026-07-23 15:48:00', '2026-07-23 15:48:00'),
(36, 'PL034', 'Tisu Paseo Besar', 10, 10000.00, 20.00, 10500.00, 49, 'assets/img/barang/brg_6a6237d28a511.jpg', '2026-07-23 15:48:34', '2026-07-27 11:08:48'),
(37, 'PL035', 'Sendok Plastik Panjang', 13, 5000.00, 20.00, 6000.00, 100, 'assets/img/barang/brg_6a623891cd27a.jpg', '2026-07-23 15:51:45', '2026-07-23 15:51:45'),
(38, 'PL036', 'Garpu Plastik Panjang', 13, 5000.00, 20.00, 6000.00, 100, 'assets/img/barang/brg_6a6238bb08b14.jpg', '2026-07-23 15:52:27', '2026-07-23 15:52:27'),
(39, 'PL037', 'Sendok Bebek Warna', 13, 3000.00, 20.00, 4000.00, 99, 'assets/img/barang/brg_6a6238e8d4f20.jpg', '2026-07-23 15:53:12', '2026-07-27 11:11:29'),
(40, 'PL038', 'Sendok Bebek Putih', 13, 4000.00, 20.00, 5000.00, 100, 'assets/img/barang/brg_6a62391926e67.jpg', '2026-07-23 15:54:01', '2026-07-23 15:54:01'),
(41, 'PL039', 'Sendok Bebek Hijau', 13, 4000.00, 20.00, 4500.00, 100, 'assets/img/barang/brg_6a62393c3c5f0.jpg', '2026-07-23 15:54:36', '2026-07-23 15:54:36'),
(42, 'PL040', 'Cup Agar Kecil', 8, 9000.00, 20.00, 10000.00, 99, 'assets/img/barang/brg_6a6239e646e94.jpg', '2026-07-23 15:57:26', '2026-07-26 03:01:36'),
(43, 'PL041', 'Cup Agar Ulir', 8, 10000.00, 20.00, 12000.00, 97, 'assets/img/barang/brg_6a623a032efc2.jpg', '2026-07-23 15:57:55', '2026-07-27 11:08:32'),
(44, 'PL042', 'Cup Agar Sambung', 8, 12000.00, 20.00, 14000.00, 99, 'assets/img/barang/brg_6a623a2c3b550.jpg', '2026-07-23 15:58:36', '2026-07-26 03:01:36'),
(45, 'PL043', 'Kardus Nasi 16', 12, 600.00, 20.00, 800.00, 400, 'assets/img/barang/brg_6a644518a4271.jpg', '2026-07-25 05:09:44', '2026-07-25 05:09:44'),
(46, 'PL044', 'Kardus Nasi 18', 12, 800.00, 20.00, 900.00, 349, 'assets/img/barang/brg_6a64453eb8de0.jpg', '2026-07-25 05:10:22', '2026-07-27 10:20:47'),
(47, 'PL045', 'Kardus Nasi 20', 12, 900.00, 20.00, 1000.00, 400, 'assets/img/barang/brg_6a64455e9cba6.jpg', '2026-07-25 05:10:54', '2026-07-25 05:10:54'),
(48, 'PL046', 'Kardus Nasi 22', 12, 1000.00, 20.00, 1100.00, 400, 'assets/img/barang/brg_6a64457f93be5.jpg', '2026-07-25 05:11:27', '2026-07-25 05:11:27'),
(50, 'PL047', 'Kardus Nasi 24', 12, 1200.00, 20.00, 1300.00, 400, 'assets/img/barang/brg_6a6445c07e349.jpg', '2026-07-25 05:12:32', '2026-07-25 05:12:32'),
(51, 'PL048', 'OPP 8x8', 2, 2000.00, 20.00, 2500.00, 50, 'assets/img/barang/brg_6a6446ba86777.jpg', '2026-07-25 05:16:42', '2026-07-25 05:16:42'),
(52, 'PL049', 'OPP 9x9', 2, 3000.00, 20.00, 3500.00, 50, 'assets/img/barang/brg_6a6446dc71bca.jpg', '2026-07-25 05:17:16', '2026-07-25 05:17:16'),
(53, 'PL050', 'OPP 10x10', 2, 3500.00, 20.00, 4000.00, 47, 'assets/img/barang/brg_6a644702ae722.jpg', '2026-07-25 05:17:54', '2026-07-27 11:08:32'),
(54, 'PL051', 'OPP 11x11', 2, 4000.00, 20.00, 4500.00, 50, 'assets/img/barang/brg_6a64472c38ebb.jpg', '2026-07-25 05:18:36', '2026-07-25 05:18:36'),
(55, 'BRG999', 'Plastik Sampel', NULL, 0.00, 20.00, 15000.00, 20, NULL, '2026-07-29 13:16:04', '2026-07-29 13:16:04');

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `jumlah_barang` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id`, `transaksi_id`, `barang_id`, `jumlah_barang`, `harga_satuan`, `subtotal`) VALUES
(5, 4, 19, 1, 33000.00, 33000.00),
(6, 4, 5, 5, 2500.00, 12500.00),
(7, 5, 3, 2, 5000.00, 10000.00),
(8, 5, 2, 1, 9000.00, 9000.00),
(9, 6, 21, 1, 33000.00, 33000.00),
(10, 6, 14, 1, 4000.00, 4000.00),
(11, 6, 15, 1, 5000.00, 5000.00),
(12, 7, 18, 1, 18000.00, 18000.00),
(13, 8, 11, 1, 6500.00, 6500.00),
(14, 8, 15, 1, 5000.00, 5000.00),
(15, 8, 21, 1, 33000.00, 33000.00),
(16, 9, 9, 5, 3000.00, 15000.00),
(17, 9, 12, 1, 8500.00, 8500.00),
(18, 10, 20, 1, 13000.00, 13000.00),
(19, 10, 9, 2, 3000.00, 6000.00),
(20, 10, 10, 1, 2500.00, 2500.00),
(21, 11, 17, 2, 12000.00, 24000.00),
(22, 11, 4, 10, 3000.00, 30000.00),
(23, 11, 3, 10, 5000.00, 50000.00),
(24, 11, 2, 5, 9000.00, 45000.00),
(25, 11, 1, 5, 13000.00, 65000.00),
(26, 11, 8, 5, 5000.00, 25000.00),
(27, 11, 13, 5, 6000.00, 30000.00),
(28, 12, 43, 1, 12000.00, 12000.00),
(29, 12, 28, 1, 13000.00, 13000.00),
(30, 12, 44, 1, 14000.00, 14000.00),
(31, 12, 42, 1, 10000.00, 10000.00),
(32, 12, 26, 1, 13000.00, 13000.00),
(33, 12, 27, 1, 13000.00, 13000.00),
(34, 13, 46, 1, 900.00, 900.00),
(35, 13, 20, 1, 13000.00, 13000.00),
(36, 13, 22, 1, 13000.00, 13000.00),
(37, 13, 16, 1, 7000.00, 7000.00),
(38, 14, 27, 5, 13000.00, 65000.00),
(39, 14, 46, 50, 900.00, 45000.00),
(40, 14, 21, 2, 33000.00, 66000.00),
(41, 15, 16, 1, 7000.00, 7000.00),
(42, 15, 14, 1, 4000.00, 4000.00),
(43, 15, 3, 5, 5000.00, 25000.00),
(44, 15, 2, 2, 9000.00, 18000.00),
(45, 15, 11, 5, 6500.00, 32500.00),
(46, 16, 43, 2, 12000.00, 24000.00),
(47, 16, 53, 3, 4000.00, 12000.00),
(48, 16, 3, 2, 5000.00, 10000.00),
(49, 16, 9, 5, 3000.00, 15000.00),
(50, 17, 36, 1, 10500.00, 10500.00),
(51, 18, 14, 1, 4000.00, 4000.00),
(52, 19, 27, 1, 13000.00, 13000.00),
(53, 19, 14, 1, 4000.00, 4000.00),
(54, 19, 5, 5, 2500.00, 12500.00),
(55, 20, 22, 1, 13000.00, 13000.00),
(56, 20, 14, 1, 4000.00, 4000.00),
(57, 21, 21, 1, 33000.00, 33000.00),
(58, 21, 8, 2, 5000.00, 10000.00),
(59, 22, 26, 1, 13000.00, 13000.00),
(60, 22, 39, 1, 4000.00, 4000.00),
(61, 23, 8, 1, 5000.00, 5000.00),
(62, 23, 13, 1, 6000.00, 6000.00),
(63, 1, 2, 5, 12000.00, 60000.00);

--
-- Triggers `detail_transaksi`
--
DELIMITER $$
CREATE TRIGGER `trg_cek_stok` BEFORE INSERT ON `detail_transaksi` FOR EACH ROW BEGIN
    DECLARE stok_barang INT;
    SELECT stok
    INTO stok_barang
    FROM barang
    WHERE id=NEW.barang_id;
    IF NEW.jumlah_barang > stok_barang THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT='Stok barang tidak mencukupi';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_kurangi_stok` AFTER INSERT ON `detail_transaksi` FOR EACH ROW BEGIN

    UPDATE barang
    SET stok = stok - NEW.jumlah_barang
    WHERE id = NEW.barang_id;

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_barang`
--

CREATE TABLE `kategori_barang` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_barang`
--

INSERT INTO `kategori_barang` (`id`, `nama_kategori`, `created_at`) VALUES
(1, 'Lain-lain', '2026-06-10 02:04:51'),
(2, 'Plastik Makanan', '2026-06-10 02:04:51'),
(3, 'Plastik Minuman', '2026-06-10 02:04:51'),
(4, 'Plastik Sampah', '2026-06-10 02:04:51'),
(5, 'Plastik Kemasan', '2026-06-10 02:04:51'),
(6, 'Plastik Rumah Tangga', '2026-06-10 02:04:51'),
(7, 'Kertas Minyak', '2026-07-23 14:41:30'),
(8, 'Cup Agar', '2026-07-23 14:41:30'),
(9, 'Cup Gelas', '2026-07-23 14:41:30'),
(10, 'Tisu', '2026-07-23 14:41:30'),
(11, 'Kardus Snack', '2026-07-23 14:41:30'),
(12, 'Kardus Nasi', '2026-07-23 14:41:30'),
(13, 'Alat Makan', '2026-07-23 14:41:30');

-- --------------------------------------------------------

--
-- Table structure for table `log_transaksi`
--

CREATE TABLE `log_transaksi` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) DEFAULT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id`, `nama_pelanggan`, `no_telepon`, `alamat`, `created_at`) VALUES
(1, 'busri', '085643385475', 'Bantul', '2026-07-01 10:41:51'),
(2, 'bu endang', '081257349182', 'Bantul', '2026-07-23 15:00:10'),
(3, 'bu Jasiah', '081384612507', 'Bantul', '2026-07-23 15:00:31'),
(4, 'bu bakmi', '082173594618', 'Bantul', '2026-07-23 15:00:51'),
(5, 'bu upik', '082291473506', 'Bantul', '2026-07-23 15:01:26'),
(6, 'bu umi', '082356817249', 'Bantul', '2026-07-23 15:01:44'),
(7, 'bu wulan', '085162748135', 'Jogja', '2026-07-23 15:01:58'),
(8, 'bu zul', '085279134608', 'Bantul', '2026-07-23 15:02:13'),
(9, 'bu suli', '085348279156', 'Bantul', '2026-07-23 15:02:32'),
(10, 'bu ratinem', '085631985427', 'Bantul', '2026-07-23 15:02:48'),
(11, 'bu sudar', '085760427318', 'Sleman', '2026-07-23 15:03:14'),
(12, 'bu warni', '085893712654', 'Bantul', '2026-07-23 15:03:34'),
(13, 'bu win', '087741596823', 'Bantul', '2026-07-23 15:03:50'),
(14, 'bu dawet', '087852069147', 'Jogja', '2026-07-23 15:04:07'),
(15, 'bu jamu', '088176352409', 'Jogja', '2026-07-23 15:04:33'),
(16, 'bu tini', '088249176358', 'Jogja', '2026-07-23 15:04:51'),
(17, 'bu eva', '088325867149', 'Jogja', '2026-07-23 15:05:05'),
(18, 'pak garry', '089574183620', 'Jogja', '2026-07-23 15:05:24'),
(19, 'pak kawi ijo', '089632578146', 'Bantul', '2026-07-23 15:05:44'),
(20, 'bu isti', '089768042513', 'Bantul', '2026-07-23 15:06:09'),
(21, 'bu kentaki', '089851429376', 'Bantul', '2026-07-23 15:06:35'),
(22, 'bu sate', '081978463521', 'Sleman', '2026-07-23 15:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_utang`
--

CREATE TABLE `pembayaran_utang` (
  `id` int(11) NOT NULL,
  `utang_id` int(11) NOT NULL,
  `jumlah_bayar` decimal(10,2) NOT NULL,
  `tanggal_pembayaran` datetime NOT NULL,
  `metode_pembayaran` enum('cash') NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran_utang`
--

INSERT INTO `pembayaran_utang` (`id`, `utang_id`, `jumlah_bayar`, `tanggal_pembayaran`, `metode_pembayaran`, `catatan`, `created_at`) VALUES
(1, 1, 10000.00, '2026-07-01 12:42:35', 'cash', '', '2026-07-01 10:42:35'),
(2, 1, 20000.00, '2026-07-23 16:51:00', 'cash', '', '2026-07-23 14:51:00'),
(3, 2, 45500.00, '2026-07-23 17:17:54', 'cash', '', '2026-07-23 15:17:54');

--
-- Triggers `pembayaran_utang`
--
DELIMITER $$
CREATE TRIGGER `trg_bayar_utang` AFTER INSERT ON `pembayaran_utang` FOR EACH ROW BEGIN
    UPDATE utang_pelanggan
    SET
    sisa_utang = sisa_utang - NEW.jumlah_bayar
    WHERE id = NEW.utang_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `no_transaksi` varchar(50) NOT NULL,
  `tanggal_transaksi` datetime NOT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `total_belanja` decimal(10,2) NOT NULL,
  `total_bayar` decimal(10,2) NOT NULL,
  `kembalian` decimal(10,2) NOT NULL,
  `metode_pembayaran` enum('cash','utang') NOT NULL,
  `status_utang` enum('lunas','belum_lunas') DEFAULT 'lunas',
  `kasir_id` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `no_transaksi`, `tanggal_transaksi`, `pelanggan_id`, `total_belanja`, `total_bayar`, `kembalian`, `metode_pembayaran`, `status_utang`, `kasir_id`, `catatan`, `created_at`) VALUES
(1, 'TRX20260701121158', '2026-07-01 12:11:58', NULL, 31750.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-01 10:11:58'),
(2, 'TRX20260701124054', '2026-07-01 12:40:54', NULL, 26000.00, 100000.00, 74000.00, 'cash', 'lunas', 1, '', '2026-07-01 10:40:54'),
(3, 'TRX20260701124212', '2026-07-01 12:42:12', 1, 30000.00, 0.00, 0.00, 'utang', 'lunas', 1, '', '2026-07-01 10:42:12'),
(4, 'TRX20260723171047', '2026-07-23 17:10:47', 22, 45500.00, 0.00, 0.00, 'utang', 'lunas', 1, '', '2026-07-23 15:10:47'),
(5, 'TRX20260723171134', '2026-07-23 17:11:34', NULL, 19000.00, 20000.00, 1000.00, 'cash', 'lunas', 1, '', '2026-07-23 15:11:34'),
(6, 'TRX20260723171214', '2026-07-23 17:12:14', NULL, 42000.00, 100000.00, 58000.00, 'cash', 'lunas', 1, '', '2026-07-23 15:12:14'),
(7, 'TRX20260723171252', '2026-07-23 17:12:52', NULL, 18000.00, 18000.00, 0.00, 'cash', 'lunas', 1, '', '2026-07-23 15:12:52'),
(8, 'TRX20260723171328', '2026-07-23 17:13:28', NULL, 44500.00, 50000.00, 5500.00, 'cash', 'lunas', 1, '', '2026-07-23 15:13:28'),
(9, 'TRX20260723171409', '2026-07-23 17:14:09', 5, 23500.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-23 15:14:09'),
(10, 'TRX20260723171451', '2026-07-23 17:14:51', NULL, 21500.00, 22000.00, 500.00, 'cash', 'lunas', 1, '', '2026-07-23 15:14:51'),
(11, 'TRX20260723171711', '2026-07-23 17:17:11', NULL, 269000.00, 270000.00, 1000.00, 'cash', 'lunas', 1, '', '2026-07-23 15:17:11'),
(12, 'TRX20260726050136', '2026-07-26 05:01:36', NULL, 75000.00, 100000.00, 25000.00, 'cash', 'lunas', 1, '', '2026-07-26 03:01:36'),
(13, 'TRX20260726050348', '2026-07-26 05:03:48', NULL, 33900.00, 100000.00, 66100.00, 'cash', 'lunas', 1, '', '2026-07-26 03:03:48'),
(14, 'TRX20260727122047', '2026-07-27 12:20:47', NULL, 176000.00, 200000.00, 24000.00, 'cash', 'lunas', 1, '', '2026-07-27 10:20:47'),
(15, 'TRX20260727130736', '2026-07-27 13:07:36', 17, 86500.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:07:36'),
(16, 'TRX20260727130832', '2026-07-27 13:08:32', 6, 61000.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:08:32'),
(17, 'TRX20260727130848', '2026-07-27 13:08:48', 21, 10500.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:08:48'),
(18, 'TRX20260727130915', '2026-07-27 13:09:15', 19, 4000.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:09:15'),
(19, 'TRX20260727130943', '2026-07-27 13:09:43', 14, 29500.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:09:43'),
(20, 'TRX20260727131014', '2026-07-27 13:10:14', 22, 17000.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:10:14'),
(21, 'TRX20260727131047', '2026-07-27 13:10:47', 8, 43000.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:10:47'),
(22, 'TRX20260727131129', '2026-07-27 13:11:29', 9, 17000.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:11:29'),
(23, 'TRX20260727131154', '2026-07-27 13:11:54', 11, 11000.00, 0.00, 0.00, 'utang', 'belum_lunas', 1, '', '2026-07-27 11:11:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','kasir') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', '2026-06-10 02:04:51'),
(2, 'kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Toko', 'kasir', '2026-06-10 02:04:51');

-- --------------------------------------------------------

--
-- Table structure for table `utang_pelanggan`
--

CREATE TABLE `utang_pelanggan` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `jumlah_utang` decimal(10,2) NOT NULL,
  `sisa_utang` decimal(10,2) NOT NULL,
  `status_pembayaran` enum('belum_lunas','sebagian','lunas') DEFAULT 'belum_lunas',
  `tanggal_utang` datetime NOT NULL,
  `tanggal_lunas` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utang_pelanggan`
--

INSERT INTO `utang_pelanggan` (`id`, `pelanggan_id`, `transaksi_id`, `jumlah_utang`, `sisa_utang`, `status_pembayaran`, `tanggal_utang`, `tanggal_lunas`, `created_at`) VALUES
(1, 1, 3, 30000.00, 0.00, 'lunas', '2026-07-01 12:42:12', '2026-07-23 16:51:00', '2026-07-01 10:42:12'),
(2, 22, 4, 45500.00, 0.00, 'lunas', '2026-07-23 17:10:47', '2026-07-23 17:17:54', '2026-07-23 15:10:47'),
(3, 5, 9, 23500.00, 23500.00, 'belum_lunas', '2026-07-23 17:14:09', NULL, '2026-07-23 15:14:09'),
(4, 17, 15, 86500.00, 86500.00, 'belum_lunas', '2026-07-27 13:07:36', NULL, '2026-07-27 11:07:36'),
(5, 6, 16, 61000.00, 61000.00, 'belum_lunas', '2026-07-27 13:08:32', NULL, '2026-07-27 11:08:32'),
(6, 21, 17, 10500.00, 10500.00, 'belum_lunas', '2026-07-27 13:08:48', NULL, '2026-07-27 11:08:48'),
(7, 19, 18, 4000.00, 4000.00, 'belum_lunas', '2026-07-27 13:09:15', NULL, '2026-07-27 11:09:15'),
(8, 14, 19, 29500.00, 29500.00, 'belum_lunas', '2026-07-27 13:09:43', NULL, '2026-07-27 11:09:43'),
(9, 22, 20, 17000.00, 17000.00, 'belum_lunas', '2026-07-27 13:10:14', NULL, '2026-07-27 11:10:14'),
(10, 8, 21, 43000.00, 43000.00, 'belum_lunas', '2026-07-27 13:10:47', NULL, '2026-07-27 11:10:47'),
(11, 9, 22, 17000.00, 17000.00, 'belum_lunas', '2026-07-27 13:11:29', NULL, '2026-07-27 11:11:29'),
(12, 11, 23, 11000.00, 11000.00, 'belum_lunas', '2026-07-27 13:11:54', NULL, '2026-07-27 11:11:54');

--
-- Triggers `utang_pelanggan`
--
DELIMITER $$
CREATE TRIGGER `trg_status_utang` AFTER UPDATE ON `utang_pelanggan` FOR EACH ROW BEGIN
    IF NEW.sisa_utang = 0 THEN
        UPDATE transaksi
        SET status_utang='lunas'
        WHERE id=NEW.transaksi_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_barang_stok`
-- (See below for the actual view)
--
CREATE TABLE `vw_barang_stok` (
`kode_barang` varchar(50)
,`nama_barang` varchar(200)
,`harga_jual` decimal(10,2)
,`stok` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_data_barang`
-- (See below for the actual view)
--
CREATE TABLE `vw_data_barang` (
`kode_barang` varchar(50)
,`nama_barang` varchar(200)
,`harga_jual` decimal(10,2)
,`stok` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_transaksi_cash`
-- (See below for the actual view)
--
CREATE TABLE `vw_transaksi_cash` (
`id` int(11)
,`no_transaksi` varchar(50)
,`tanggal_transaksi` datetime
,`pelanggan_id` int(11)
,`total_belanja` decimal(10,2)
,`total_bayar` decimal(10,2)
,`kembalian` decimal(10,2)
,`metode_pembayaran` enum('cash','utang')
,`status_utang` enum('lunas','belum_lunas')
,`kasir_id` int(11)
,`catatan` text
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `vw_barang_stok`
--
DROP TABLE IF EXISTS `vw_barang_stok`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_barang_stok`  AS SELECT `vw_data_barang`.`kode_barang` AS `kode_barang`, `vw_data_barang`.`nama_barang` AS `nama_barang`, `vw_data_barang`.`harga_jual` AS `harga_jual`, `vw_data_barang`.`stok` AS `stok` FROM `vw_data_barang` WHERE `vw_data_barang`.`stok` > 0WITH CASCADEDCHECK OPTION  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_data_barang`
--
DROP TABLE IF EXISTS `vw_data_barang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_data_barang`  AS SELECT `barang`.`kode_barang` AS `kode_barang`, `barang`.`nama_barang` AS `nama_barang`, `barang`.`harga_jual` AS `harga_jual`, `barang`.`stok` AS `stok` FROM `barang` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_transaksi_cash`
--
DROP TABLE IF EXISTS `vw_transaksi_cash`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_transaksi_cash`  AS SELECT `transaksi`.`id` AS `id`, `transaksi`.`no_transaksi` AS `no_transaksi`, `transaksi`.`tanggal_transaksi` AS `tanggal_transaksi`, `transaksi`.`pelanggan_id` AS `pelanggan_id`, `transaksi`.`total_belanja` AS `total_belanja`, `transaksi`.`total_bayar` AS `total_bayar`, `transaksi`.`kembalian` AS `kembalian`, `transaksi`.`metode_pembayaran` AS `metode_pembayaran`, `transaksi`.`status_utang` AS `status_utang`, `transaksi`.`kasir_id` AS `kasir_id`, `transaksi`.`catatan` AS `catatan`, `transaksi`.`created_at` AS `created_at` FROM `transaksi` WHERE `transaksi`.`metode_pembayaran` = 'cash' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`),
  ADD KEY `idx_barang_kategori` (`kategori_id`,`nama_barang`);

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `kategori_barang`
--
ALTER TABLE `kategori_barang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_transaksi`
--
ALTER TABLE `log_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaksi_pelanggan` (`transaksi_id`,`pelanggan_id`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pembayaran_utang`
--
ALTER TABLE `pembayaran_utang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utang_id` (`utang_id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_transaksi` (`no_transaksi`),
  ADD KEY `pelanggan_id` (`pelanggan_id`),
  ADD KEY `kasir_id` (`kasir_id`),
  ADD KEY `idx_tanggal_kasir` (`tanggal_transaksi`,`kasir_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `utang_pelanggan`
--
ALTER TABLE `utang_pelanggan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_id` (`pelanggan_id`),
  ADD KEY `transaksi_id` (`transaksi_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `kategori_barang`
--
ALTER TABLE `kategori_barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `log_transaksi`
--
ALTER TABLE `log_transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pembayaran_utang`
--
ALTER TABLE `pembayaran_utang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `utang_pelanggan`
--
ALTER TABLE `utang_pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_barang` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`);

--
-- Constraints for table `pembayaran_utang`
--
ALTER TABLE `pembayaran_utang`
  ADD CONSTRAINT `pembayaran_utang_ibfk_1` FOREIGN KEY (`utang_id`) REFERENCES `utang_pelanggan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`kasir_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `utang_pelanggan`
--
ALTER TABLE `utang_pelanggan`
  ADD CONSTRAINT `utang_pelanggan_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `utang_pelanggan_ibfk_2` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
