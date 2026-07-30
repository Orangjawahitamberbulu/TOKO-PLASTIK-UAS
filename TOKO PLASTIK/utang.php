<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_customer') {
            try {
                $nama_pelanggan = trim($_POST['nama_pelanggan']);
                $no_telepon = trim($_POST['no_telepon']);
                $alamat = trim($_POST['alamat']);
                
                $stmt = $pdo->prepare("INSERT INTO pelanggan (nama_pelanggan, no_telepon, alamat) VALUES (?, ?, ?)");
                $stmt->execute([$nama_pelanggan, $no_telepon, $alamat]);
                
                $message = 'Pelanggan berhasil ditambahkan!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Gagal menambahkan pelanggan: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'pay_debt') {
            try {
                $pdo->beginTransaction();
                
                $utang_id = $_POST['utang_id'];
                $jumlah_bayar = $_POST['jumlah_bayar'];
                $metode_pembayaran = $_POST['metode_pembayaran'];
                $catatan = $_POST['catatan'] ?? '';
                $tanggal_pembayaran = date('Y-m-d H:i:s');
                
                // Get current debt
                $stmt = $pdo->prepare("SELECT * FROM utang_pelanggan WHERE id = ?");
                $stmt->execute([$utang_id]);
                $utang = $stmt->fetch();
                
                if (!$utang) {
                    throw new Exception('Data utang tidak ditemukan');
                }
                
                // Insert payment record
                $stmt = $pdo->prepare("INSERT INTO pembayaran_utang (utang_id, jumlah_bayar, tanggal_pembayaran, metode_pembayaran, catatan) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$utang_id, $jumlah_bayar, $tanggal_pembayaran, $metode_pembayaran, $catatan]);
                
                // Update debt
                $sisa_baru = $utang['sisa_utang'] - $jumlah_bayar;
                $status_baru = ($sisa_baru <= 0) ? 'lunas' : ($sisa_baru < $utang['jumlah_utang'] ? 'sebagian' : 'belum_lunas');
                
                $stmt = $pdo->prepare("UPDATE utang_pelanggan SET sisa_utang = ?, status_pembayaran = ?, tanggal_lunas = ? WHERE id = ?");
                $stmt->execute([$sisa_baru > 0 ? $sisa_baru : 0, $status_baru, $status_baru == 'lunas' ? $tanggal_pembayaran : null, $utang_id]);
                
                // Update transaction status if fully paid
                if ($status_baru == 'lunas') {
                    $stmt = $pdo->prepare("UPDATE transaksi SET status_utang = 'lunas' WHERE id = ?");
                    $stmt->execute([$utang['transaksi_id']]);
                }
                
                $pdo->commit();
                
                $message = 'Pembayaran utang berhasil dicatat!';
                $message_type = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Gagal mencatat pembayaran: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Get all debts with customer info
$stmt = $pdo->query("
    SELECT u.*, p.nama_pelanggan, p.no_telepon, t.no_transaksi, t.tanggal_transaksi 
    FROM utang_pelanggan u 
    JOIN pelanggan p ON u.pelanggan_id = p.id 
    JOIN transaksi t ON u.transaksi_id = t.id 
    WHERE u.status_pembayaran != 'lunas' 
    ORDER BY u.tanggal_utang DESC
");
$utang_list = $stmt->fetchAll();

// Get all customers
$pelanggan = $pdo->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Utang - PLASTIFY</title>
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
                <li><a href="utang.php" class="active"><i class="fas fa-credit-card"></i> Utang</a></li>
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
        <h1 style="margin-bottom: 30px; color: var(--primary-orange);">Kelola Utang Pelanggan</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-user-plus"></i> Tambah Pelanggan</h2>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_customer">
                        
                        <div class="form-group">
                            <label class="form-label">Nama Pelanggan</label>
                            <input type="text" name="nama_pelanggan" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="no_telepon" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-plus"></i> Tambah Pelanggan
                        </button>
                    </form>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-users"></i> Daftar Pelanggan</h2>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Telepon</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pelanggan as $p): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($p['nama_pelanggan']); ?></td>
                                        <td><?php echo htmlspecialchars($p['no_telepon'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-credit-card"></i> Daftar Utang Belum Lunas</h2>
                    </div>
                    
                    <?php if (empty($utang_list)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">Tidak ada utang yang belum lunas</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Pelanggan</th>
                                        <th>Tanggal</th>
                                        <th>Jumlah Utang</th>
                                        <th>Sisa Utang</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($utang_list as $u): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u['no_transaksi']); ?></td>
                                            <td><?php echo htmlspecialchars($u['nama_pelanggan']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($u['tanggal_utang'])); ?></td>
                                            <td>Rp <?php echo number_format($u['jumlah_utang'], 0, ',', '.'); ?></td>
                                            <td style="color: var(--danger); font-weight: bold;">Rp <?php echo number_format($u['sisa_utang'], 0, ',', '.'); ?></td>
                                            <td>
                                                <?php if ($u['status_pembayaran'] == 'belum_lunas'): ?>
                                                    <span class="badge badge-danger">Belum Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Sebagian</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button onclick="showPaymentModal(<?php echo $u['id']; ?>, <?php echo $u['sisa_utang']; ?>)" class="btn btn-success btn-sm">
                                                    <i class="fas fa-money-bill"></i> Bayar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div class="modal" id="paymentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-money-bill"></i> Bayar Utang</h3>
                <button class="modal-close" onclick="closePaymentModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="pay_debt">
                <input type="hidden" name="utang_id" id="utang_id">
                
                <div class="form-group">
                    <label class="form-label">Sisa Utang</label>
                    <input type="text" class="form-control" id="sisa_utang_display" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jumlah Bayar (Rp)</label>
                    <input type="number" name="jumlah_bayar" class="form-control" id="jumlah_bayar" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Metode Pembayaran</label>
                    <select name="metode_pembayaran" class="form-control" required>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Catatan (Opsional)</label>
                    <textarea name="catatan" class="form-control" rows="2"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-check"></i> Proses Pembayaran
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function showPaymentModal(utangId, sisaUtang) {
            document.getElementById('utang_id').value = utangId;
            document.getElementById('sisa_utang_display').value = 'Rp ' + sisaUtang.toLocaleString('id-ID');
            document.getElementById('jumlah_bayar').value = sisaUtang;
            document.getElementById('jumlah_bayar').max = sisaUtang;
            document.getElementById('paymentModal').classList.add('active');
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>
</body>
</html>
