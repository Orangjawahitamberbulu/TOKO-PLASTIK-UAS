<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get transaction ID
$transaksi_id = isset($_GET['id']) ? $_GET['id'] : 0;

try {
    // Get transaction details
    $stmt = $pdo->prepare("SELECT t.*, p.nama_pelanggan, u.nama_lengkap as nama_kasir FROM transaksi t LEFT JOIN pelanggan p ON t.pelanggan_id = p.id LEFT JOIN users u ON t.kasir_id = u.id WHERE t.id = ?");
    $stmt->execute([$transaksi_id]);
    $transaksi = $stmt->fetch();
    
    if (!$transaksi) {
        die('Transaksi tidak ditemukan');
    }
    
    // Get transaction items
    $stmt = $pdo->prepare("SELECT dt.*, b.nama_barang FROM detail_transaksi dt LEFT JOIN barang b ON dt.barang_id = b.id WHERE dt.transaksi_id = ?");
    $stmt->execute([$transaksi_id]);
    $detail_transaksi = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Nota - PLASTIFY</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="transaksi.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Transaksi
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Cetak Nota
            </button>
        </div>
        
        <div class="receipt" id="receipt">
            <div class="receipt-header">
                <h2><i class="fas fa-shopping-bag"></i> PLASTIFY</h2>
                <p>Sistem Kasir Toko Plastik</p>
                <p style="margin-top: 10px; font-size: 12px;">No. Transaksi: <?php echo htmlspecialchars($transaksi['no_transaksi']); ?></p>
                <p style="font-size: 12px;">Tanggal: <?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal_transaksi'])); ?></p>
                <p style="font-size: 12px;">Kasir: <?php echo htmlspecialchars($transaksi['nama_kasir']); ?></p>
                <?php if ($transaksi['nama_pelanggan']): ?>
                    <p style="font-size: 12px;">Pelanggan: <?php echo htmlspecialchars($transaksi['nama_pelanggan']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="receipt-items">
                <?php foreach ($detail_transaksi as $item): ?>
                    <div class="receipt-item">
                        <div>
                            <strong><?php echo htmlspecialchars($item['nama_barang']); ?></strong><br>
                            <small><?php echo $item['jumlah_barang']; ?> x Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></small>
                        </div>
                        <div>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="receipt-total">
                <div class="receipt-total-row">
                    <span>Total Belanja:</span>
                    <span>Rp <?php echo number_format($transaksi['total_belanja'], 0, ',', '.'); ?></span>
                </div>
                <div class="receipt-total-row">
                    <span>Metode Pembayaran:</span>
                    <span><?php echo ucfirst($transaksi['metode_pembayaran']); ?></span>
                </div>
                <?php if ($transaksi['metode_pembayaran'] != 'utang'): ?>
                    <div class="receipt-total-row">
                        <span>Total Bayar:</span>
                        <span>Rp <?php echo number_format($transaksi['total_bayar'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="receipt-total-row final">
                        <span>Kembalian:</span>
                        <span>Rp <?php echo number_format($transaksi['kembalian'], 0, ',', '.'); ?></span>
                    </div>
                <?php else: ?>
                    <div class="receipt-total-row final" style="color: var(--danger);">
                        <span>Status:</span>
                        <span>BELUM LUNAS</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($transaksi['catatan']): ?>
                <div style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px; font-size: 12px;">
                    <strong>Catatan:</strong> <?php echo htmlspecialchars($transaksi['catatan']); ?>
                </div>
            <?php endif; ?>
            
            <div class="receipt-footer">
                <p>Terima kasih telah berbelanja di PLASTIFY!</p>
                <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
                <p style="margin-top: 10px;"><?php echo date('d/m/Y H:i'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
