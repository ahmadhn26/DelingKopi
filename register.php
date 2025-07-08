<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

if ($_POST) {
    // Ambil semua data kecuali admin_code
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $fullName = trim($_POST['full_name']);
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // HAPUS BARIS INI:
    // $adminCode = trim($_POST['admin_code']);
    
    // Lakukan validasi seperti biasa
    if (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $error = 'Nomor HP harus 10-15 digit';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username atau email sudah digunakan';
        } else {
            // --- PERUBAHAN DI SINI ---
            // Langsung atur role menjadi 'user' karena kode admin sudah tidak ada
            $role = 'user'; 
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, gender, address, email, phone, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $hashedPassword, $fullName, $gender, $address, $email, $phone, $role])) {
                $success = 'Registrasi berhasil! Silakan login.';
                $_POST = [];
            } else {
                $error = 'Terjadi kesalahan saat registrasi';
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Deling Kopi</title>
    <link rel="icon" href="img/logo1.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary-brown:  #92400e;
            --white: #ffffff;
            --error-bg: #fee2e2;
            --error-text: #dc2626;
            --success-bg: #dcfce7;
            --success-text: #166534;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        .auth-container {
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.4)),
                        url("https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=1920&h=1080&fit=crop") center / cover;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        /* --- STYLE AUTH-CARD DIUBAH --- */
        .auth-card {
            width: 100%;
            max-width: 700px; /* Dibuat lebih lebar untuk form registrasi */
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 2.5rem 3rem; /* Padding ditambahkan langsung di sini */
        }
        .auth-header {
            display: flex;
            flex-direction: column; /* Membuat logo dan judul tersusun ke bawah */
            align-items: center;    /* Membuatnya rata tengah secara horizontal */
            gap: 0.5rem;            /* Mengurangi jarak karena sekarang vertikal */
            margin-bottom: 1rem;    /* Sedikit menambah jarak ke bawah */
        }
        
        .auth-header img {
            width: 50px;
            height: 50px;
            background-color: var(--primary-brown);
            border-radius : 25px;
        }

        .auth-header h1 {
            color: var(--text-color);
            font-size: 1.75rem;
            font-weight: 600;
        }
        .auth-card > p {
            color: #6b7280;
            margin-bottom: 1.5rem;
            text-align: center; /* Tambahkan baris ini */
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-brown);
            box-shadow: 0 0 0 3px rgba(106, 79, 75, 0.1);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            margin-top: 1rem;
            border: none;
            border-radius: 8px;
            background: var(--primary-brown);
            color: var(--white);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #523b37;
        }

        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }
        
        .auth-links a {
            color: var(--primary-brown);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }
        .back-link a:hover {
            color: var(--primary-brown);
        }

        .error, .success {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            text-align: center;
        }
        
        .error {
            background: var(--error-bg);
            color: var(--error-text);
        }
        
        .success {
            background: var(--success-bg);
            color: var(--success-text);
        }
        
        .help-text {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .auth-card {
                padding: 2rem;
                max-width: 500px;
            }
        }

        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
             .auth-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="img/logo1.png" alt="Logo Kecil">
                <h1>Buat Akun Baru</h1>
            </div>
            <p>Isi data di bawah untuk bergabung</p>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!$success): // Sembunyikan form jika registrasi sukses ?>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" placeholder="Minimal 3 karakter" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required minlength="3">
                    </div>
                    <div class="form-group">
                        <label for="full_name">Nama Lengkap *</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Masukkan nama lengkap" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i data-feather="eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" placeholder="contoh@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Nomor HP *</label>
                        <input type="tel" id="phone" name="phone" placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required pattern="[0-9]{10,15}">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                       <label for="gender">Jenis Kelamin *</label>
                        <select id="gender" name="gender" required>
                            <option value="" disabled selected>Pilih jenis kelamin</option>
                            <option value="male" <?php echo (($_POST['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="female" <?php echo (($_POST['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Alamat *</label>
                    <textarea id="address" name="address" rows="3" placeholder="Masukkan alamat lengkap" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">Daftar</button>
            </form>
            <?php endif; ?>
            
            <div class="auth-links">
                <span>Sudah punya akun? <a href="login.php">Masuk di sini</a></span>
            </div>
            
            <div class="back-link">
                <a href="index.php">← Kembali ke beranda</a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-feather', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-feather', 'eye');
            }
            feather.replace();
        }
        feather.replace();
    </script>
</body>
</html>