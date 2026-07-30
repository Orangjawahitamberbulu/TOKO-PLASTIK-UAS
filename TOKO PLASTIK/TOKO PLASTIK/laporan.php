<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'today';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Set date range based on period
switch ($period) {
    case 'today':
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d');
        break;
    case 'week':
        $date_from = date('Y-m-d', strtotime('monday this week'));
        $date_to = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-t');
        break;
    case 'custom':
        $date_from = $start_date;
        $date_to = $end_date;
        break;
    default:
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d');
}

try {
    // Get sales summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transaksi,
            COALESCE(SUM(total_belanja), 0) as total_pendapatan,
            COALESCE(AVG(total_belanja), 0) as rata_rata_transaksi
        FROM transaksi 
        WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $summary = $stmt->fetch();
    
    // Get sales by payment method
    $stmt = $pdo->prepare("
        SELECT 
            metode_pembayaran,
            COUNT(*) as jumlah,
            COALESCE(SUM(total_belanja), 0) as total
        FROM transaksi 
        WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
        GROUP BY metode_pembayaran
    ");
    $stmt->execute([$date_from, $date_to]);
    $by_payment = $stmt->fetchAll();
    
    // Get transaction list
    $stmt = $pdo->prepare("
        SELECT t.*, p.nama_pelanggan, u.nama_lengkap as nama_kasir
        FROM transaksi t
        LEFT JOIN pelanggan p ON t.pelanggan_id = p.id
        LEFT JOIN users u ON t.kasir_id = u.id
        WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
        ORDER BY t.tanggal_transaksi DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $transactions = $stmt->fetchAll();
    
    // Get top selling products
    $stmt = $pdo->prepare("
        SELECT b.nama_barang, SUM(dt.jumlah_barang) as total_jual, SUM(dt.subtotal) as total_pendapatan
        FROM detail_transaksi dt
        JOIN barang b ON dt.barang_id = b.id
        JOIN transaksi t ON dt.transaksi_id = t.id
        WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
        GROUP BY b.id, b.nama_barang
        ORDER BY total_jual DESC
        LIMIT 10
    ");
    $stmt->execute([$date_from, $date_to]);
    $top_products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - PLASTIFY</title>
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="transaksi.php"><i class="fas fa-cash-register"></i> Transaksi</a></li>
                <li><a href="barang.php"><i class="fas fa-box"></i> Barang</a></li>
                <li><a href="utang.php"><i class="fas fa-credit-card"></i> Utang</a></li>
                <li><a href="laporan.php" class="active"><i class="fas fa-chart-bar"></i> Laporan</a></li>
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
        <h1 style="margin-bottom: 30px; color: var(--primary-orange);">Laporan Penjualan</h1>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-filter"></i> Filter Periode</h2>
            </div>
            
            <form method="GET" action="">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Periode</label>
                        <select name="period" class="form-control" onchange="toggleDateFields()">
                            <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>Hari Ini</option>
                            <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>Minggu Ini</option>
                            <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Bulan Ini</option>
                            <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;" id="start-date-group" style="display: <?php echo $period == 'custom' ? 'block' : 'none'; ?>;">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" <?php echo $period != 'custom' ? 'disabled' : ''; ?>>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;" id="end-date-group" style="display: <?php echo $period == 'custom' ? 'block' : 'none'; ?>;">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" <?php echo $period != 'custom' ? 'disabled' : ''; ?>>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($summary['total_transaksi']); ?></h3>
                    <p>Total Transaksi</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Rp <?php echo number_format($summary['total_pendapatan'], 0, ',', '.'); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>Rp <?php echo number_format($summary['rata_rata_transaksi'], 0, ',', '.'); ?></h3>
                    <p>Rata-rata Transaksi</p>
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-credit-card"></i> Berdasarkan Metode Pembayaran</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Metode</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($by_payment as $item): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-primary"><?php echo ucfirst($item['metode_pembayaran']); ?></span>
                                    </td>
                                    <td><?php echo number_format($item['jumlah']); ?></td>
                                    <td>Rp <?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-trophy"></i> Produk Terlaris</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Terjual</th>
                                <th>Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                    <td><?php echo number_format($item['total_jual']); ?></td>
                                    <td>Rp <?php echo number_format($item['total_pendapatan'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-list"></i> Riwayat Transaksi</h2>
                <div style="font-size: 14px; color: #666;">
                    Periode: <?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['no_transaksi']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($t['tanggal_transaksi'])); ?></td>
                                <td><?php echo $t['nama_pelanggan'] ? htmlspecialchars($t['nama_pelanggan']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($t['nama_kasir']); ?></td>
                                <td>Rp <?php echo number_format($t['total_belanja'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge badge-primary"><?php echo ucfirst($t['metode_pembayaran']); ?></span>
                                </td>
                                <td>
                                    <?php if ($t['status_utang'] == 'lunas'): ?>
                                        <span class="badge badge-success">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Belum Lunas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="cetak_nota.php?id=<?php echo $t['id']; ?>" class="btn btn-info btn-sm" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function toggleDateFields() {
            const period = document.querySelector('select[name="period"]').value;
            const startGroup = document.getElementById('start-date-group');
            const endGroup = document.getElementById('end-date-group');
            const startDate = document.querySelector('input[name="start_date"]');
            const endDate = document.querySelector('input[name="end_date"]');
            
            if (period === 'custom') {
                startGroup.style.display = 'block';
                endGroup.style.display = 'block';
                startDate.disabled = false;
                endDate.disabled = false;
            } else {
                startGroup.style.display = 'none';
                endGroup.style.display = 'none';
                startDate.disabled = true;
                endDate.disabled = true;
            }
        }
        
        // Initialize on page load
        toggleDateFields();
    </script>
</body>
</html>
