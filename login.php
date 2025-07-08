<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Check if user was trying to checkout
            if (isset($_SESSION['redirect_after_login']) && $_SESSION['redirect_after_login'] === 'checkout') {
                unset($_SESSION['redirect_after_login']);
                header('Location: checkout.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Deling Kopi</title>
    <link rel="icon" href="img/logo1.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary-brown:  #92400e; /* Warna coklat tua dari desain */
            --light-gray: #f0f0f0;
            --text-color: #92400e;
            --white: #ffffff;
            --error-bg: #fee2e2;
            --error-text: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
        }

        .auth-container {
            min-height: 100vh;
            /* Background yang Anda inginkan (gradient gelap + gambar) */
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.4)),
                        url("https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=1920&h=1080&fit=crop") center / cover;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-card {
            display: flex;
            width: 100%;
            max-width: 900px;
            min-height: 600px;
            background: var(--white);
            border-radius: 25px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden; /* Penting agar panel dalam mengikuti border-radius */
        }

        /* Panel Kiri (Branding) */
        .auth-promo-panel {
            flex-basis: 45%;
            background-color: var(--primary-brown);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-promo-panel img {
            max-width: 100%;
            height: auto;
        }

        /* Panel Kanan (Form) */
        .auth-form-panel {
            flex-basis: 55%;
            padding: 3rem 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
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

        .auth-form-panel p {
            color: #6b7280;
            margin-bottom: 2rem;
            margin-top:1em;
            text-align:center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-group input:focus {
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

        .error {
            background: var(--error-bg);
            color: var(--error-text);
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 920px) {
            .auth-promo-panel {
                display: none; /* Sembunyikan panel kiri di layar kecil */
            }
            .auth-form-panel {
                flex-basis: 100%;
                padding: 2rem;
            }
            .auth-card {
                flex-direction: column;
                min-height: auto;
                max-width: 450px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-promo-panel">
                <img src="img/logo1.png" alt="Deling Kopi Logo">
            </div>

            <div class="auth-form-panel">
                <div class="auth-header">
                    <img src="img/logo1.png" alt="Logo Kecil">
                    <h1>Deling Kopi</h1>
                </div>
                <p>Masuk ke akun Anda untuk melanjutkan</p>
                
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Masukkan username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i data-feather="eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Masuk</button>
                </form>
                
                <div class="auth-links">
                    <span>Belum punya akun? <a href="register.php">Daftar di sini</a></span>
                </div>
                
                <div class="back-link">
                    <a href="index.php">← Kembali ke beranda</a>
                </div>
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