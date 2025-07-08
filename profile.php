<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            
            // Validate input
            if (empty($username) || empty($email)) {
                $error = 'Username dan email harus diisi';
            } else {
                // Check if email is already used by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    $error = 'Email sudah digunakan oleh pengguna lain';
                } else {
                    // Update profile
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    if ($stmt->execute([$username, $email, $phone, $address, $_SESSION['user_id']])) {
                        $_SESSION['username'] = $username;
                        $message = 'Profile berhasil diperbarui';
                    } else {
                        $error = 'Gagal memperbarui profile';
                    }
                }
            }
        } elseif ($_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate input
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'Semua field password harus diisi';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Password baru dan konfirmasi password tidak cocok';
            } elseif (strlen($new_password) < 6) {
                $error = 'Password baru minimal 6 karakter';
            } else {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'Password saat ini salah';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                        $message = 'Password berhasil diubah';
                    } else {
                        $error = 'Gagal mengubah password';
                    }
                }
            }
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Kopi Kenangan Senja</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .profile-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            padding: 2rem 0;
        }
        
        .profile-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .back-link {
            margin-bottom: 2rem;
        }
        
        .back-link a {
            color: #d97706;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            color: #b45309;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .card-header h2 {
            margin: 0;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-content {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #d97706;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .tabs {
            display: flex;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            background: #d97706;
            color: white;
        }
        
        .tab:first-child {
            border-radius: 8px 0 0 8px;
        }
        
        .tab:last-child {
            border-radius: 0 8px 8px 0;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-content">
            <div class="back-link">
                <a href="index.php">← Kembali ke beranda</a>
            </div>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab active" onclick="switchTab('profile')">Profile</button>
                <button class="tab" onclick="switchTab('password')">Ubah Password</button>
            </div>
            
            <!-- Profile Tab -->
            <div class="tab-content active" id="profile">
                <div class="profile-card">
                    <div class="card-header">
                        <h2>
                            <i data-feather="user"></i>
                            Informasi Profile
                        </h2>
                    </div>
                    <div class="card-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nomor HP</label>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Role</label>
                                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" readonly style="background: #f3f4f6;">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Alamat</label>
                                <textarea name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Password Tab -->
            <div class="tab-content" id="password">
                <div class="profile-card">
                    <div class="card-header">
                        <h2>
                            <i data-feather="lock"></i>
                            Ubah Password
                        </h2>
                    </div>
                    <div class="card-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label>Password Saat Ini</label>
                                <input type="password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="new_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="key"></i> Ubah Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to selected tab
            event.target.classList.add('active');
        }
        
        feather.replace();
    </script>
</body>
</html>
