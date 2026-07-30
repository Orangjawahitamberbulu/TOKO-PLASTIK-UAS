# PLASTIFY - Sistem Kasir Toko Plastik

Sistem kasir terkomputerisasi untuk toko plastik dengan tampilan modern ala Shopee Food.

## Fitur Utama

### 1. Manajemen Barang (Admin)
- Tambah, edit, dan hapus data barang
- Kategorisasi barang
- Pencarian barang berdasarkan nama atau kategori
- Perhitungan harga jual otomatis berdasarkan margin keuntungan
- Monitoring stok barang dengan alert stok rendah

### 2. Transaksi Penjualan (Kasir)
- Pemilihan barang dengan tampilan grid yang menarik
- Filter barang berdasarkan kategori
- Pencarian barang cepat
- Keranjang belanja interaktif
- Perhitungan total, bayar, dan kembalian otomatis
- Metode pembayaran: Cash, Transfer, E-Wallet, Utang
- Pencetakan nota transaksi otomatis
- Update stok barang otomatis setelah transaksi

### 3. Manajemen Utang Pelanggan
- Pencatatan transaksi utang
- Daftar pelanggan dengan utang belum lunas
- Pembayaran utang sebagian atau lunas
- Tracking status pembayaran utang

### 4. Laporan Penjualan (Admin)
- Laporan harian, mingguan, bulanan
- Filter periode custom
- Statistik total transaksi dan pendapatan
- Laporan berdasarkan metode pembayaran
- Produk terlaris
- Riwayat transaksi lengkap

### 5. Manajemen Pengguna (Admin)
- Tambah, edit, hapus user
- Role-based access control (Admin/Kasir)
- Keamanan dengan password hashing

## Persyaratan Sistem

- **Web Server**: XAMPP, WAMP, atau server lain dengan PHP
- **PHP Version**: PHP 7.0 atau lebih tinggi
- **Database**: MySQL 5.7 atau lebih tinggi
- **Browser**: Chrome, Firefox, Edge (modern browser)
- **Printer**: Untuk mencetak nota (opsional)

## Instalasi

### 1. Ekstrak File
Letakkan folder "TOKO PLASTIK" di dalam:
- XAMPP: `C:\xampp\htdocs\`
- WAMP: `C:\wamp64\www\`

### 2. Buat Database
1. Buka phpMyAdmin (http://localhost/phpmyadmin)
2. Klik tab "SQL"
3. Copy dan paste isi file `database.sql`
4. Klik "Go" untuk menjalankan query

Atau import file `database.sql` melalui:
1. Klik tab "Import"
2. Pilih file `database.sql`
3. Klik "Go"

### 3. Konfigurasi Database
Edit file `config/database.php` sesuai dengan konfigurasi database Anda:

```php
$host = 'localhost';        // Host database
$dbname = 'plastify_db';    // Nama database
$username = 'root';         // Username database
$password = '';             // Password database
```

### 4. Akses Aplikasi
Buka browser dan akses:
```
http://localhost/TOKO%20PLASTIK/login.php
```

## Login Default

### Admin
- **Username**: admin
- **Password**: admin123

### Kasir
- **Username**: kasir
- **Password**: kasir123

> ⚠️ **PENTING**: Ganti password default setelah login pertama untuk keamanan!

## Struktur Folder

```
TOKO PLASTIK/
├── assets/
│   └── css/
│       └── style.css          # File styling utama
├── config/
│   └── database.php          # Koneksi database
├── barang.php                 # Manajemen barang
├── dashboard.php              # Halaman dashboard
├── laporan.php                # Laporan penjualan
├── login.php                  # Halaman login
├── logout.php                 # Logout
├── transaksi.php              # Halaman transaksi
├── utang.php                  # Manajemen utang
├── users.php                  # Manajemen user (admin)
├── cetak_nota.php             # Cetak nota transaksi
├── database.sql               # Schema database
└── README.md                  # Dokumentasi ini
```

## Panduan Penggunaan

### Untuk Admin

1. **Login sebagai Admin**
   - Gunakan username dan password admin

2. **Kelola Barang**
   - Masuk ke menu "Barang"
   - Klik "Tambah Barang Baru"
   - Isi data barang (kode, nama, kategori, harga beli, margin, stok)
   - Sistem akan menghitung harga jual otomatis

3. **Kelola User**
   - Masuk ke menu "Users"
   - Tambah user baru dengan role admin atau kasir
   - Edit atau hapus user yang sudah tidak diperlukan

4. **Lihat Laporan**
   - Masuk ke menu "Laporan"
   - Pilih periode (Hari Ini, Minggu Ini, Bulan Ini, atau Custom)
   - Lihat statistik dan riwayat transaksi

### Untuk Kasir

1. **Login sebagai Kasir**
   - Gunakan username dan password kasir

2. **Lakukan Transaksi**
   - Masuk ke menu "Transaksi"
   - Pilih barang dengan mengklik kartu produk
   - Atau gunakan pencarian/filter kategori
   - Barang akan masuk ke keranjang
   - Atur jumlah barang di keranjang
   - Pilih pelanggan (opsional)
   - Pilih metode pembayaran
   - Masukkan jumlah bayar (untuk non-utang)
   - Klik "Proses Transaksi"

3. **Cetak Nota**
   - Setelah transaksi berhasil, nota akan otomatis ditampilkan
   - Klik "Cetak Nota" untuk mencetak
   - Atau kembali ke transaksi baru

4. **Kelola Utang**
   - Masuk ke menu "Utang"
   - Lihat daftar pelanggan dengan utang
   - Klik "Bayar" untuk mencatat pembayaran
   - Pilih metode dan jumlah pembayaran

## Fitur Keamanan

- Password hashing dengan bcrypt
- Session-based authentication
- Role-based access control
- SQL injection prevention dengan PDO prepared statements
- Input validation

## Troubleshooting

### Tidak bisa login
- Pastikan database sudah dibuat dengan benar
- Cek konfigurasi database di `config/database.php`
- Pastikan username dan password benar

### Error koneksi database
- Pastikan MySQL server sudah berjalan
- Cek username dan password database di `config/database.php`
- Pastikan nama database sudah benar

### Tampilan tidak berfungsi
- Pastikan file `assets/css/style.css` ada
- Clear cache browser
- Pastikan menggunakan browser modern

## Customization

### Mengubah Warna Tema
Edit file `assets/css/style.css` dan ubah variabel CSS di bagian `:root`:

```css
:root {
    --primary-orange: #ee4d2d;
    --primary-red: #d32f2f;
    --secondary-orange: #ff6b35;
    /* ... */
}
```

### Menambah Kategori Baru
Tambahkan kategori melalui phpMyAdmin di tabel `kategori_barang`

### Mengubah Password Default
Login sebagai admin, masuk ke menu "Users", edit user yang ingin diubah passwordnya

## Support

Untuk pertanyaan atau masalah, silakan hubungi tim pengembang.

---

**PLASTIFY** - Sistem Kasir Toko Plastik
Versi 1.0
