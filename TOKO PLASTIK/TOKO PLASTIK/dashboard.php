<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get statistics
try {
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang");
    $total_products = $stmt->fetch()['total'];
    
    // Total transactions today
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE()");
    $transactions_today = $stmt->fetch()['total'];
    
    // Total revenue today
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_belanja), 0) as total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE()");
    $revenue_today = $stmt->fetch()['total'];
    
    // Total debt
    $stmt = $pdo->query("SELECT COALESCE(SUM(sisa_utang), 0) as total FROM utang_pelanggan WHERE status_pembayaran != 'lunas'");
    $total_debt = $stmt->fetch()['total'];
    
    // Low stock products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE stok < 10");
    $low_stock = $stmt->fetch()['total'];
    
    // Recent transactions
    $stmt = $pdo->query("SELECT t.*, p.nama_pelanggan FROM transaksi t LEFT JOIN pelanggan p ON t.pelanggan_id = p.id ORDER BY t.tanggal_transaksi DESC LIMIT 5");
    $recent_transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLASTIFY</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-shopping-bag"></i> PLASTIFY
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="transaksi.php"><i class="fas fa-cash-register"></i> Transaksi</a></li>
                <li><a href="barang.php"><i class="fas fa-box"></i> Barang</a></li>
                <li><a href="utang.php"><i class="fas fa-credit-card"></i> Utang</a></li>
                <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <?php endif; ?>
            </ul>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                <span class="badge badge-info"><?php echo ucfirst($_SESSION['role']); ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 style="margin-bottom: 30px; color: var(--primary-orange);">Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_products); ?></h3>
                    <p>Total Barang</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($transactions_today); ?></h3>
                    <p>Transaksi Hari Ini</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Rp <?php echo number_format($revenue_today, 0, ',', '.'); ?></h3>
                    <p>Pendapatan Hari Ini</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-info">
                    <h3>Rp <?php echo number_format($total_debt, 0, ',', '.'); ?></h3>
                    <p>Total Utang Pelanggan</p>
                </div>
            </div>
        </div>
        
        <?php if ($low_stock > 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Ada <?php echo $low_stock; ?> barang dengan stok rendah (kurang dari 10)!
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-history"></i> Transaksi Terakhir</h2>
                <a href="laporan.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Semua</a>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $trans): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trans['no_transaksi']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($trans['tanggal_transaksi'])); ?></td>
                                <td><?php echo $trans['nama_pelanggan'] ? htmlspecialchars($trans['nama_pelanggan']) : '-'; ?></td>
                                <td>Rp <?php echo number_format($trans['total_belanja'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge badge-primary"><?php echo ucfirst($trans['metode_pembayaran']); ?></span>
                                </td>
                                <td>
                                    <?php if ($trans['status_utang'] == 'lunas'): ?>
                                        <span class="badge badge-success">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Belum Lunas</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-bolt"></i> Aksi Cepat</h2>
            </div>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="transaksi.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-cash-register"></i> Transaksi Baru
                </a>
                <a href="barang.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-plus"></i> Tambah Barang
                </a>
                <a href="utang.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-credit-card"></i> Kelola Utang
                </a>
                <a href="laporan.php" class="btn btn-info btn-lg">
                    <i class="fas fa-chart-bar"></i> Lihat Laporan
                </a>
            </div>
        </div>
    </div>
</body>
</html>
