<?php
// Ganti 'password_rahasia_admin' dengan password yang Anda inginkan
$passwordAdmin = 'admin123'; 

// Membuat hash yang aman dari password di atas
$hashedPassword = password_hash($passwordAdmin, PASSWORD_DEFAULT);

// Tampilkan hash-nya
echo "Password Asli: " . $passwordAdmin . "<br>";
echo "Password Hash (salin ini ke database): <br>";
echo "<textarea rows='4' cols='80'>" . htmlspecialchars($hashedPassword) . "</textarea>";

/*
 * Contoh output hash yang akan Anda dapatkan (setiap kali dijalankan akan berbeda):
 * $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
 */
?>