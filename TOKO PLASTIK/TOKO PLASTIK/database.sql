-- Database: plastify_db
-- Sistem Kasir Toko Plastik PLASTIFY

CREATE DATABASE IF NOT EXISTS plastify_db;
USE plastify_db;

-- Table: users (untuk login dan hak akses)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: kategori_barang
CREATE TABLE kategori_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: barang
CREATE TABLE barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(50) NOT NULL UNIQUE,
    nama_barang VARCHAR(200) NOT NULL,
    kategori_id INT NOT NULL,
    harga_beli DECIMAL(10, 2) NOT NULL,
    margin_keuntungan DECIMAL(5, 2) DEFAULT 20.00,
    harga_jual DECIMAL(10, 2) NOT NULL,
    stok INT DEFAULT 0,
    gambar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_barang(id) ON DELETE RESTRICT
);

-- Table: pelanggan
CREATE TABLE pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: transaksi
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) NOT NULL UNIQUE,
    tanggal_transaksi DATETIME NOT NULL,
    pelanggan_id INT,
    total_belanja DECIMAL(10, 2) NOT NULL,
    total_bayar DECIMAL(10, 2) NOT NULL,
    kembalian DECIMAL(10, 2) NOT NULL,
    metode_pembayaran ENUM('cash', 'utang') NOT NULL,
    status_utang ENUM('lunas', 'belum_lunas') DEFAULT 'lunas',
    kasir_id INT NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE SET NULL,
    FOREIGN KEY (kasir_id) REFERENCES users(id)
);

-- Table: detail_transaksi
CREATE TABLE detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    barang_id INT NOT NULL,
    jumlah_barang INT NOT NULL,
    harga_satuan DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id)
);

-- Table: utang_pelanggan
CREATE TABLE utang_pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id INT NOT NULL,
    transaksi_id INT NOT NULL UNIQUE,
    jumlah_utang DECIMAL(10, 2) NOT NULL,
    sisa_utang DECIMAL(10, 2) NOT NULL,
    status_pembayaran ENUM('belum_lunas', 'sebagian', 'lunas') DEFAULT 'belum_lunas',
    tanggal_utang DATETIME NOT NULL,
    tanggal_lunas DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE
);

-- Table: pembayaran_utang
CREATE TABLE pembayaran_utang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utang_id INT NOT NULL,
    jumlah_bayar DECIMAL(10, 2) NOT NULL,
    tanggal_pembayaran DATETIME NOT NULL,
    metode_pembayaran ENUM('cash') NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utang_id) REFERENCES utang_pelanggan(id) ON DELETE CASCADE
);

-- Insert default users (password: admin123 / kasir123)
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Toko', 'kasir');

-- Insert default categories
INSERT INTO kategori_barang (nama_kategori) VALUES
('Plastik Makanan'),
('Plastik Minuman'),
('Plastik Sampah'),
('Plastik Kemasan'),
('Plastik Rumah Tangga'),
('Lain-lain');

-- Insert sample products
INSERT INTO barang (kode_barang, nama_barang, kategori_id, harga_beli, margin_keuntungan, harga_jual, stok) VALUES
('PL001', 'Plastik Kresek Kecil', 3, 5000, 30, 6500, 100),
('PL002', 'Plastik Kresek Sedang', 3, 7000, 30, 9100, 80),
('PL003', 'Plastik Kresek Besar', 3, 10000, 30, 13000, 60),
('PL004', 'Plastik Makanan PP', 1, 15000, 25, 18750, 50),
('PL005', 'Plastik Minuman Cup', 2, 8000, 35, 10800, 120),
('PL006', 'Plastik Kemasan Vacuum', 4, 25000, 20, 30000, 30),
('PL007', 'Plastik Rapat Jumbo', 3, 12000, 30, 15600, 45),
('PL008', 'Plastik Kemasan Ziplock', 4, 18000, 25, 22500, 40);
