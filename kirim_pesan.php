<?php
header('Content-Type: application/json');

// Pastikan request adalah metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- KONFIGURASI ---
    // Ganti dengan alamat email Anda yang akan menerima pesan
    $penerima = "a.husein260204@gamil.com"; 
    $subjek = "Pesan Baru dari Website Kopi Anda";

    // Ambil data dari form dan bersihkan untuk keamanan
    $nama = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : 'Tidak diisi';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : 'Tidak diisi';
    $pesan = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : 'Tidak ada pesan';

    // Validasi dasar
    if (empty($nama) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($pesan)) {
        // Kirim response error jika ada data yang tidak valid
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau email tidak valid.']);
        exit;
    }

    // Format isi email yang akan Anda terima
    $isi_email = "Anda menerima pesan baru dari website:\n\n";
    $isi_email .= "Nama Pengirim: " . $nama . "\n";
    $isi_email .= "Email Pengirim: " . $email . "\n\n";
    $isi_email .= "Pesan:\n" . $pesan . "\n";

    // Header email
    $headers = "From: " . $nama . " <" . $email . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Kirim email menggunakan fungsi mail() PHP
    if (mail($penerima, $subjek, $isi_email, $headers)) {
        // Jika berhasil, kirim response success
        echo json_encode(['success' => true, 'message' => 'Pesan berhasil dikirim!']);
    } else {
        // Jika gagal, kirim response error
        echo json_encode(['success' => false, 'message' => 'Gagal mengirim pesan. Silakan coba lagi.']);
    }

} else {
    // Jika bukan metode POST
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
}
?>