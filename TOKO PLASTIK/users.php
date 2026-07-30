<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_user') {
            try {
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $nama_lengkap = trim($_POST['nama_lengkap']);
                $role = $_POST['role'];
                
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $message = 'Username sudah digunakan!';
                    $message_type = 'danger';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $hashed_password, $nama_lengkap, $role]);
                    
                    $message = 'User berhasil ditambahkan!';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Gagal menambahkan user: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'edit_user') {
            try {
                $id = $_POST['id'];
                $username = trim($_POST['username']);
                $nama_lengkap = trim($_POST['nama_lengkap']);
                $role = $_POST['role'];
                $password = $_POST['password'];
                
                // Check if username already exists (excluding current user)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetch()) {
                    $message = 'Username sudah digunakan!';
                    $message_type = 'danger';
                } else {
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $hashed_password, $nama_lengkap, $role, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $nama_lengkap, $role, $id]);
                    }
                    
                    $message = 'User berhasil diperbarui!';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Gagal memperbarui user: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'delete_user') {
            try {
                $id = $_POST['id'];
                
                // Prevent deleting self
                if ($id == $_SESSION['user_id']) {
                    $message = 'Tidak dapat menghapus user yang sedang login!';
                    $message_type = 'danger';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    $message = 'User berhasil dihapus!';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Gagal menghapus user: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll();

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - PLASTIFY</title>
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
                <li><a href="laporan.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
            </ul>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                <span class="badge badge-info"><?php echo ucfirst($_SESSION['role']); ?></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 style="margin-bottom: 30px; color: var(--primary-orange);">Kelola Pengguna Sistem</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-<?php echo $edit_user ? 'edit' : 'plus'; ?>"></i> <?php echo $edit_user ? 'Edit User' : 'Tambah User Baru'; ?></h2>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit_user' : 'add_user'; ?>">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password <?php echo $edit_user ? '(Biarkan kosong jika tidak ingin mengubah)' : ''; ?></label>
                        <input type="password" name="password" class="form-control" <?php echo $edit_user ? '' : 'required'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" value="<?php echo $edit_user ? htmlspecialchars($edit_user['nama_lengkap']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="">Pilih Role</option>
                            <option value="admin" <?php echo $edit_user && $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="kasir" <?php echo $edit_user && $edit_user['role'] == 'kasir' ? 'selected' : ''; ?>>Kasir</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?php echo $edit_user ? 'save' : 'plus'; ?>"></i> <?php echo $edit_user ? 'Simpan Perubahan' : 'Tambah User'; ?>
                    </button>
                    <?php if ($edit_user): ?>
                        <a href="users.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-list"></i> Daftar Pengguna</h2>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Role</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'info' : 'success'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
