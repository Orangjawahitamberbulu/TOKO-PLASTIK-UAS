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

// Handle transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'process_transaction') {
        try {
            $pdo->beginTransaction();
            
            // Generate transaction number
            $no_transaksi = 'TRX' . date('YmdHis');
            $tanggal_transaksi = date('Y-m-d H:i:s');
            $pelanggan_id = !empty($_POST['pelanggan_id']) ? $_POST['pelanggan_id'] : null;
            $total_belanja = $_POST['total_belanja'];
            $total_bayar = $_POST['total_bayar'];
            $kembalian = $_POST['kembalian'];
            $metode_pembayaran = $_POST['metode_pembayaran'];
            $status_utang = ($metode_pembayaran == 'utang') ? 'belum_lunas' : 'lunas';
            $kasir_id = $_SESSION['user_id'];
            $catatan = $_POST['catatan'] ?? '';
            
            // Insert transaction
            $stmt = $pdo->prepare("INSERT INTO transaksi (no_transaksi, tanggal_transaksi, pelanggan_id, total_belanja, total_bayar, kembalian, metode_pembayaran, status_utang, kasir_id, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$no_transaksi, $tanggal_transaksi, $pelanggan_id, $total_belanja, $total_bayar, $kembalian, $metode_pembayaran, $status_utang, $kasir_id, $catatan]);
            $transaksi_id = $pdo->lastInsertId();
            
            // Insert transaction details and update stock
            $barang_ids = $_POST['barang_id'];
            $jumlahs = $_POST['jumlah'];
            $hargas = $_POST['harga'];
            
            foreach ($barang_ids as $index => $barang_id) {
                $jumlah = $jumlahs[$index];
                $harga = $hargas[$index];
                $subtotal = $jumlah * $harga;
                
                // Insert detail
                $stmt = $pdo->prepare("INSERT INTO detail_transaksi (transaksi_id, barang_id, jumlah_barang, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$transaksi_id, $barang_id, $jumlah, $harga, $subtotal]);
                
                // Update stock
                $stmt = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
                $stmt->execute([$jumlah, $barang_id]);
            }
            
            // Handle debt if payment method is utang
            if ($metode_pembayaran == 'utang' && $pelanggan_id) {
                $stmt = $pdo->prepare("INSERT INTO utang_pelanggan (pelanggan_id, transaksi_id, jumlah_utang, sisa_utang, status_pembayaran, tanggal_utang) VALUES (?, ?, ?, ?, 'belum_lunas', ?)");
                $stmt->execute([$pelanggan_id, $transaksi_id, $total_belanja, $total_belanja, $tanggal_transaksi]);
            }
            
            $pdo->commit();
            
            $_SESSION['last_transaction_id'] = $transaksi_id;
            header('Location: cetak_nota.php?id=' . $transaksi_id);
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = 'Gagal memproses transaksi: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM kategori_barang ORDER BY nama_kategori")->fetchAll();

// Get products
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE nama_barang LIKE ? OR kode_barang LIKE ? AND stok > 0 ORDER BY nama_barang");
    $stmt->execute(["%$search%", "%$search%"]);
} elseif ($kategori_filter) {
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE kategori_id = ? AND stok > 0 ORDER BY nama_barang");
    $stmt->execute([$kategori_filter]);
} else {
    $stmt = $pdo->query("SELECT * FROM barang WHERE stok > 0 ORDER BY nama_barang");
}
$barang = $stmt->fetchAll();

// Get customers
$pelanggan = $pdo->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - PLASTIFY</title>
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
                <li><a href="transaksi.php" class="active"><i class="fas fa-cash-register"></i> Transaksi</a></li>
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
        <h1 style="margin-bottom: 30px; color: var(--primary-orange);">Transaksi Penjualan</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-boxes"></i> Pilih Barang</h2>
                    </div>
                    
                    <div class="category-pills">
                        <a href="transaksi.php" class="category-pill <?php echo !$kategori_filter ? 'active' : ''; ?>">Semua</a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="transaksi.php?kategori=<?php echo $cat['id']; ?>" class="category-pill <?php echo $kategori_filter == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="search-box">
                        <form method="GET" action="">
                            <input type="text" name="search" placeholder="Cari barang..." value="<?php echo htmlspecialchars($search); ?>">
                            <i class="fas fa-search"></i>
                        </form>
                    </div>
                    
                    <div class="product-grid">
                        <?php foreach ($barang as $item): ?>
                            <div class="product-card" onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nama_barang']); ?>', <?php echo $item['harga_jual']; ?>, <?php echo $item['stok']; ?>)">
                                <div class="product-image" style="<?php echo (!empty($item['gambar']) && file_exists($item['gambar'])) ? 'padding:0; background:none;' : ''; ?>">
                                    <?php if (!empty($item['gambar']) && file_exists($item['gambar'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_barang']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:inherit; display:block;">
                                    <?php else: ?>
                                        <i class="fas fa-box"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['nama_barang']); ?></div>
                                    <div class="product-price">Rp <?php echo number_format($item['harga_jual'], 0, ',', '.'); ?></div>
                                    <div class="product-stock">Stok: <?php echo $item['stok']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="cart-section">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-shopping-cart"></i> Keranjang</h2>
                        <button onclick="clearCart()" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                    <div id="cart-items">
                        <p style="text-align: center; color: #999; padding: 20px;">Keranjang kosong</p>
                    </div>
                    
                    <div class="cart-total">
                        <div class="total-row">
                            <span>Total Belanja:</span>
                            <span id="cart-total">Rp 0</span>
                        </div>
                    </div>
                    
                    <form method="POST" action="" id="transaction-form" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="process_transaction">
                        <input type="hidden" name="total_belanja" id="total-belanja" value="0">
                        
                        <div class="form-group">
                            <label class="form-label">Pelanggan (Opsional)</label>
                            <select name="pelanggan_id" class="form-control">
                                <option value="">Pilih Pelanggan</option>
                                <?php foreach ($pelanggan as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nama_pelanggan']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-control" id="metode-pembayaran" onchange="togglePaymentFields()" required>
                                <option value="cash">Cash</option>
                                <option value="utang">Utang</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="bayar-field">
                            <label class="form-label">Jumlah Bayar (Rp)</label>
                            <input type="number" name="total_bayar" class="form-control" id="total-bayar" value="0" oninput="calculateChange()" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Kembalian (Rp)</label>
                            <input type="number" name="kembalian" class="form-control" id="kembalian" value="0" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                            <i class="fas fa-check"></i> Proses Transaksi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        
        function addToCart(id, nama, harga, stok) {
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                if (existingItem.jumlah < stok) {
                    existingItem.jumlah++;
                } else {
                    alert('Stok tidak mencukupi!');
                    return;
                }
            } else {
                cart.push({
                    id: id,
                    nama: nama,
                    harga: harga,
                    jumlah: 1,
                    stok: stok
                });
            }
            
            updateCartDisplay();
        }
        
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');
            const totalBelanja = document.getElementById('total-belanja');
            
            if (cart.length === 0) {
                cartItems.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Keranjang kosong</p>';
                cartTotal.textContent = 'Rp 0';
                totalBelanja.value = 0;
                return;
            }
            
            let html = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                const subtotal = item.harga * item.jumlah;
                total += subtotal;
                
                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.nama}</div>
                            <div class="cart-item-price">Rp ${item.harga.toLocaleString('id-ID')} x ${item.jumlah}</div>
                        </div>
                        <div class="cart-item-quantity">
                            <button type="button" class="quantity-btn" onclick="decreaseQuantity(${index})">-</button>
                            <span>${item.jumlah}</span>
                            <button type="button" class="quantity-btn" onclick="increaseQuantity(${index})">+</button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeFromCart(${index})" style="margin-left: 10px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            cartTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');
            totalBelanja.value = total;
            calculateChange();
        }
        
        function increaseQuantity(index) {
            const item = cart[index];
            if (item.jumlah < item.stok) {
                item.jumlah++;
                updateCartDisplay();
            } else {
                alert('Stok tidak mencukupi!');
            }
        }
        
        function decreaseQuantity(index) {
            const item = cart[index];
            if (item.jumlah > 1) {
                item.jumlah--;
                updateCartDisplay();
            } else {
                removeFromCart(index);
            }
        }
        
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }
        
        function clearCart() {
            cart = [];
            updateCartDisplay();
        }
        
        function calculateChange() {
            const totalBelanja = parseFloat(document.getElementById('total-belanja').value) || 0;
            const totalBayar = parseFloat(document.getElementById('total-bayar').value) || 0;
            const kembalian = totalBayar - totalBelanja;
            
            document.getElementById('kembalian').value = kembalian >= 0 ? kembalian : 0;
        }
        
        function togglePaymentFields() {
            const metode = document.getElementById('metode-pembayaran').value;
            const bayarField = document.getElementById('bayar-field');
            
            if (metode === 'utang') {
                bayarField.style.display = 'none';
                document.getElementById('total-bayar').value = 0;
                document.getElementById('kembalian').value = 0;
            } else {
                bayarField.style.display = 'block';
            }
        }
        
        // Add hidden inputs for cart items before form submission
        document.getElementById('transaction-form').addEventListener('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Keranjang masih kosong!');
                return;
            }
            
            const metode = document.getElementById('metode-pembayaran').value;
            if (metode !== 'utang') {
                const totalBelanja = parseFloat(document.getElementById('total-belanja').value) || 0;
                const totalBayar = parseFloat(document.getElementById('total-bayar').value) || 0;
                
                if (totalBayar < totalBelanja) {
                    e.preventDefault();
                    alert('Jumlah bayar kurang dari total belanja!');
                    return;
                }
            }
            
            // Add cart items to form
            cart.forEach(item => {
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'barang_id[]';
                inputId.value = item.id;
                this.appendChild(inputId);
                
                const inputJumlah = document.createElement('input');
                inputJumlah.type = 'hidden';
                inputJumlah.name = 'jumlah[]';
                inputJumlah.value = item.jumlah;
                this.appendChild(inputJumlah);
                
                const inputHarga = document.createElement('input');
                inputHarga.type = 'hidden';
                inputHarga.name = 'harga[]';
                inputHarga.value = item.harga;
                this.appendChild(inputHarga);
            });
        });
    </script>
</body>
</html>
