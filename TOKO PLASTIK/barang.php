<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if admin
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            try {
                $kode_barang = trim($_POST['kode_barang']);
                $nama_barang = trim($_POST['nama_barang']);
                $kategori_id = $_POST['kategori_id'];
                $harga_beli = $_POST['harga_beli'];
                $margin_keuntungan = $_POST['margin_keuntungan'];
                $harga_jual = $_POST['harga_jual'];
                $stok = $_POST['stok'];

                // Handle image upload
                $gambar = null;
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'assets/img/barang/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
                    $ftype = mime_content_type($_FILES['gambar']['tmp_name']);
                    if (!in_array($ftype, $allowed)) throw new Exception('Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.');
                    if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) throw new Exception('Ukuran gambar maksimal 2MB.');
                    $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                    $gambar = $upload_dir . uniqid('brg_') . '.' . strtolower($ext);
                    move_uploaded_file($_FILES['gambar']['tmp_name'], $gambar);
                }

                $stmt = $pdo->prepare("INSERT INTO barang (kode_barang, nama_barang, kategori_id, harga_beli, margin_keuntungan, harga_jual, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$kode_barang, $nama_barang, $kategori_id, $harga_beli, $margin_keuntungan, $harga_jual, $stok, $gambar]);
                
                $message = 'Barang berhasil ditambahkan!';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Gagal menambahkan barang: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'edit') {
            try {
                $id = $_POST['id'];
                $kode_barang = trim($_POST['kode_barang']);
                $nama_barang = trim($_POST['nama_barang']);
                $kategori_id = $_POST['kategori_id'];
                $harga_beli = $_POST['harga_beli'];
                $margin_keuntungan = $_POST['margin_keuntungan'];
                $harga_jual = $_POST['harga_jual'];
                $stok = $_POST['stok'];

                // Fetch existing image
                $stmt_old = $pdo->prepare("SELECT gambar FROM barang WHERE id = ?");
                $stmt_old->execute([$id]);
                $old = $stmt_old->fetch();
                $gambar = $old['gambar'];

                // Handle new image upload
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'assets/img/barang/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
                    $ftype = mime_content_type($_FILES['gambar']['tmp_name']);
                    if (!in_array($ftype, $allowed)) throw new Exception('Format gambar tidak didukung.');
                    if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) throw new Exception('Ukuran gambar maksimal 2MB.');
                    $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                    $new_gambar = $upload_dir . uniqid('brg_') . '.' . strtolower($ext);
                    move_uploaded_file($_FILES['gambar']['tmp_name'], $new_gambar);
                    // Delete old image
                    if ($gambar && file_exists($gambar)) unlink($gambar);
                    $gambar = $new_gambar;
                }
                
                $stmt = $pdo->prepare("UPDATE barang SET kode_barang = ?, nama_barang = ?, kategori_id = ?, harga_beli = ?, margin_keuntungan = ?, harga_jual = ?, stok = ?, gambar = ? WHERE id = ?");
                $stmt->execute([$kode_barang, $nama_barang, $kategori_id, $harga_beli, $margin_keuntungan, $harga_jual, $stok, $gambar, $id]);
                
                $message = 'Barang berhasil diperbarui!';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Gagal memperbarui barang: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'delete') {
            try {
                $id = $_POST['id'];
                // Delete image file first
                $stmt_img = $pdo->prepare("SELECT gambar FROM barang WHERE id = ?");
                $stmt_img->execute([$id]);
                $img_row = $stmt_img->fetch();
                if ($img_row && $img_row['gambar'] && file_exists($img_row['gambar'])) {
                    unlink($img_row['gambar']);
                }
                $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
                $stmt->execute([$id]);
                
                $message = 'Barang berhasil dihapus!';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Gagal menghapus barang: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get categories
$categories = $pdo->query("SELECT * FROM kategori_barang ORDER BY nama_kategori")->fetchAll();

// Get products
if ($search) {
    $stmt = $pdo->prepare("SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori_barang k ON b.kategori_id = k.id WHERE b.nama_barang LIKE ? OR b.kode_barang LIKE ? ORDER BY b.kode_barang");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori_barang k ON b.kategori_id = k.id ORDER BY b.kode_barang");
}
$barang = $stmt->fetchAll();

// Get product for editing
$edit_barang = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_barang = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang - PLASTIFY</title>
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
                <li><a href="barang.php" class="active"><i class="fas fa-box"></i> Barang</a></li>
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
        <h1 style="margin-bottom: 30px; color: var(--primary-orange);">Kelola Barang</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-<?php echo $edit_barang ? 'edit' : 'plus'; ?>"></i> <?php echo $edit_barang ? 'Edit Barang' : 'Tambah Barang Baru'; ?></h2>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $edit_barang ? 'edit' : 'add'; ?>">
                <?php if ($edit_barang): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_barang['id']; ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Kode Barang</label>
                        <input type="text" name="kode_barang" class="form-control" value="<?php echo $edit_barang ? htmlspecialchars($edit_barang['kode_barang']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" value="<?php echo $edit_barang ? htmlspecialchars($edit_barang['nama_barang']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="kategori_id" class="form-control" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $edit_barang && $edit_barang['kategori_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Rp)</label>
                        <input type="number" name="harga_beli" class="form-control" step="0.01" value="<?php echo $edit_barang ? $edit_barang['harga_beli'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Margin Keuntungan (%)</label>
                        <input type="number" name="margin_keuntungan" class="form-control" step="0.01" value="<?php echo $edit_barang ? $edit_barang['margin_keuntungan'] : '20.00'; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" class="form-control" step="0.01" value="<?php echo $edit_barang ? $edit_barang['harga_jual'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control" value="<?php echo $edit_barang ? $edit_barang['stok'] : '0'; ?>" required>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Gambar Barang</label>
                        <div style="border: 2px dashed var(--border-color, #ddd); border-radius: 10px; padding: 20px; text-align: center; background: rgba(0,0,0,0.02); cursor: pointer;" onclick="document.getElementById('gambar-input').click()">
                            <div id="image-preview-wrap">
                                <?php if ($edit_barang && !empty($edit_barang['gambar'])): ?>
                                    <img id="image-preview" src="<?php echo htmlspecialchars($edit_barang['gambar']); ?>" alt="Gambar" style="max-height:180px; max-width:100%; border-radius:8px; object-fit:contain; display:block; margin:0 auto 10px;">
                                    <p style="color:#666; font-size:13px; margin:0;">Klik untuk ganti gambar</p>
                                <?php else: ?>
                                    <img id="image-preview" src="" alt="" style="max-height:180px; max-width:100%; border-radius:8px; object-fit:contain; display:none; margin:0 auto 10px;">
                                    <div id="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt" style="font-size:36px; color:#aaa; display:block; margin-bottom:8px;"></i>
                                        <p style="color:#888; margin:0;">Klik untuk pilih gambar <span style="color:#aaa; font-size:12px;">(JPG, PNG, GIF, WebP &bull; Max 2MB)</span></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="file" id="gambar-input" name="gambar" accept="image/*" style="display:none;" onchange="previewImage(event)">
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?php echo $edit_barang ? 'save' : 'plus'; ?>"></i> <?php echo $edit_barang ? 'Simpan Perubahan' : 'Tambah Barang'; ?>
                    </button>
                    <?php if ($edit_barang): ?>
                        <a href="barang.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-list"></i> Daftar Barang</h2>
                <div class="search-box" style="margin-bottom: 0; width: 300px;">
                    <form method="GET" action="">
                        <input type="text" name="search" placeholder="Cari barang..." value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search"></i>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Harga Beli</th>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($barang as $item): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['gambar']) && file_exists($item['gambar'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['gambar']); ?>" alt="<?php echo htmlspecialchars($item['nama_barang']); ?>" style="width:55px; height:55px; object-fit:cover; border-radius:8px; border:1px solid #eee;">
                                    <?php else: ?>
                                        <div style="width:55px; height:55px; border-radius:8px; background:linear-gradient(135deg,#f0f0f0,#e0e0e0); display:flex; align-items:center; justify-content:center; border:1px solid #eee;">
                                            <i class="fas fa-image" style="color:#ccc; font-size:20px;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['kode_barang']); ?></td>
                                <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                <td><?php echo htmlspecialchars($item['nama_kategori'] ?? '-'); ?></td>
                                <td>Rp <?php echo number_format($item['harga_beli'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($item['harga_jual'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($item['stok'] < 10): ?>
                                        <span class="badge badge-danger"><?php echo $item['stok']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><?php echo $item['stok']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="barang.php?edit=<?php echo $item['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus barang ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('image-preview');
        const placeholder = document.getElementById('upload-placeholder');
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
}
</script>
</body>
</html>
