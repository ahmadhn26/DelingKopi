<?php
session_start();
require_once 'config/db.php';

// Get search query
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch menu items (limit to 9 for carousel)
$menuQuery = "SELECT * FROM menukami";
if ($search) {
    $menuQuery .= " WHERE name LIKE :search";
}
$menuQuery .= " ORDER BY created_at DESC";
$menuStmt = $pdo->prepare($menuQuery);
if ($search) {
    $menuStmt->bindValue(':search', '%' . $search . '%');
}
$menuStmt->execute();
$menuItems = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products
$productQuery = "SELECT * FROM products";
if ($search) {
    $productQuery .= " WHERE name LIKE :search OR description LIKE :search";
}
$productStmt = $pdo->prepare($productQuery);
if ($search) {
    $productStmt->bindValue(':search', '%' . $search . '%');
}
$productStmt->execute();
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopi Kenangan Senja - Cita Rasa Autentik Kopi Mandailing</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/minimal-live-search.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?Playfair+Display:wght@400;700&family=Poppins:ital,wght@0,100;0,300;0,400;0,700;1,700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <!-- Hidden input for user ID -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <input type="hidden" id="user-id" value="<?php echo $_SESSION['user_id']; ?>">
    <?php endif; ?>

    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php"><img src="img/logo.png" alt="Logo Website"></a>
            </div>

            <div class="nav-menu" id="nav-menu">
                <!-- Auth buttons for mobile and tablet (shown only when not logged in) -->
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="auth-buttons mobile-auth-buttons">
                        <a href="login.php" class="btn btn-outline">Login</a>
                        <a href="register.php" class="btn btn-primary">Daftar</a>
                    </div>
                <?php endif; ?>
                
                <a href="#home" class="nav-link">Home</a>
                <a href="#about" class="nav-link">About Us</a>
                <a href="#menu" class="nav-link">Menu</a>
                <a href="#products" class="nav-link">Produk</a>
                <a href="#contact" class="nav-link">Kontak</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="nav-link admin-link">Admin</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="nav-link mobile-logout-link">Logout</a>
                <?php endif; ?>
            </div>

            <div class="nav-actions">
                <div class="action-container search-container">
                    <button class="action-btn search-toggle" id="search-toggle">
                        <i data-feather="search"></i>
                    </button>
                </div>

                <div class="action-container cart-container">
                    <button class="action-btn cart-btn" id="cart-btn">
                        <i data-feather="shopping-cart"></i>
                        <span class="cart-count" id="cart-count">0</span>
                    </button>
                </div>

                <div class="desktop-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        $userLinks = [
                            ['href' => 'profile.php', 'icon' => 'user', 'label' => 'Profile'],
                            ['href' => 'orders.php', 'icon' => 'package', 'label' => 'Pesanan'],
                            ['href' => 'logout.php', 'icon' => 'log-out', 'label' => 'Logout', 'class' => 'logout-link']
                        ];
                        ?>
                        <?php foreach ($userLinks as $link): ?>
                            <?php
                            $classes = 'action-btn';
                            if ($link['icon'] === 'log-out') {
                                $classes .= ' desktop-logout-link';
                            }
                            ?>
                            <a href="<?php echo $link['href']; ?>" class="<?php echo $classes; ?>" title="<?php echo $link['label']; ?>">
                                <i data-feather="<?php echo $link['icon']; ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="auth-buttons desktop-auth-buttons">
                            <a href="login.php" class="btn btn-outline">Login</a>
                            <a href="register.php" class="btn btn-primary">Daftar</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="hamburger" id="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>

        <div class="search-bar" id="search-bar">
            <div class="search-container">
                <input type="text" 
                       id="search-input" 
                       placeholder="Temukan kopi dan produk favorit Anda..." 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       autocomplete="off"
                       spellcheck="false">
            </div>
        </div>
    </nav>

    <!-- Rest of the HTML remains unchanged -->
    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Secangkir Kopi, <span id="typing-text" class="typing-cursor"></span></h1>
            <p>Rasakan kehangatan dan kekayaan cita rasa otentik dari dataran tinggi Mandailing.</p>
            <a href="#menu" class="btn btn-primary btn-large">Jelajahi Menu</a>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>100+</h3>
                    <p>Pelanggan Puas</p>
                </div>
                <div class="stat-item">
                    <h3>5★</h3>
                    <p>Rating Kualitas</p>
                </div>
                <div class="stat-item">
                    <h3>10+</h3>
                    <p>Varian Menu</p>
                </div>
                <div class="stat-item">
                    <h3>3</h3>
                    <p>Tahun Pengalaman</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <h2><span>Tentang</span> Kopi Mandailing</h2>
            <div class="about-content">
                <div class="about-text">
                    <p>Kopi Mandailing berasal dari dataran tinggi Sumatera Utara yang terkenal dengan karakteristik rasa yang kuat, body yang penuh, dan aroma yang menggoda. Kami menghadirkan kopi premium dengan proses pengolahan tradisional yang telah diwariskan turun-temurun.</p>
                    <p>Setiap cangkir kopi kami adalah hasil dari dedikasi petani lokal dan komitmen kami untuk memberikan pengalaman kopi terbaik bagi setiap pelanggan.</p>
                    
                    <div class="feature-grid">
                        <div class="feature-card">
                            <i data-feather="coffee" size="48"></i>
                            <h3>Kopi Premium</h3>
                            <p>Biji kopi pilihan langsung dari petani Mandailing dengan kualitas terbaik</p>
                        </div>
                        <div class="feature-card">
                            <i data-feather="award" size="48"></i>
                            <h3>Proses Tradisional</h3>
                            <p>Menggunakan metode pengolahan tradisional yang telah teruji selama bertahun-tahun</p>
                        </div>
                    </div>
                </div>
                <div class="about-image slide-from-right">
                    <img src="img/Tentang2.jpg" alt="Kopi Mandailing">
                </div>
            </div>
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="menu">
        <div class="container">
            <div class="section-header">
                <h2><span>Menu</span> Kami</h2>
                <p>Pilihan menu kopi terbaik dengan cita rasa yang tak terlupakan</p>
            </div>

            <!-- Menu Warning Banner -->
            <div class="menu-warning-banner">
                <h3>
                    <i data-feather="map-pin"></i>
                    INFORMASI PENTING MENU KOPI
                </h3>
                <p>Menu kopi kami hanya dapat dipesan untuk dinikmati langsung di kafe. Menu ini TIDAK tersedia untuk pengiriman jarak jauh. Silakan kunjungi kafe kami untuk menikmati cita rasa autentik kopi Mandailing!</p>
            </div>

            <!-- Menu Carousel -->
            <div class="menu-carousel-container">
                <button class="carousel-btn prev" id="menu-prev">
                    <i data-feather="chevron-left"></i>
                </button>
                
                <div class="menu-carousel" id="menu-carousel">
                    <?php foreach ($menuItems as $item): ?>
                        <div class="menu-card <?php echo ($item['stock'] ?? 0) <= 0 ? 'out-of-stock' : ''; ?>" data-id="<?php echo $item['id']; ?>" data-type="menu">
                            <div class="menu-image">
                                <?php if (($item['stock'] ?? 0) <= 0): ?>
                                    <div class="menu-badge stock-out">Habis</div>
                                <?php else: ?>
                                    <div class="menu-badge">Hanya di Kafe</div>
                                <?php endif; ?>
                                <img src="<?php echo $item['image'] ?: 'img/menu/default-coffee.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="menu-overlay">
                                    <button class="btn btn-white btn-small add-to-cart" 
                                            data-id="<?php echo $item['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                            data-price="<?php echo $item['price']; ?>" 
                                            data-stock="<?php echo $item['stock'] ?? 0; ?>"
                                            data-type="menu"
                                            <?php echo ($item['stock'] ?? 0) <= 0 ? 'disabled' : ''; ?>>
                                        <i data-feather="<?php echo ($item['stock'] ?? 0) <= 0 ? 'x' : 'plus'; ?>"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="menu-content">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                                <p class="stock-info">
                                    <i data-feather="package"></i>
                                    Stok: <?php echo $item['stock'] ?? 0; ?>
                                </p>
                                <p class="menu-location">
                                    <i data-feather="map-pin"></i>
                                    Dinikmati di kafe
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="carousel-btn next" id="menu-next">
                    <i data-feather="chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="products">
        <div class="container">
            <div class="section-header">
                <h2><span>Produk Unggulan</span> Kami</h2>
                <p>Bawa pulang cita rasa kopi Mandailing terbaik - Tersedia untuk pengiriman!</p>
            </div>

            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card <?php echo ($product['stock'] ?? 0) <= 0 ? 'out-of-stock' : ''; ?>" data-id="<?php echo $product['id']; ?>" data-type="product">
                        <div class="product-image">
                            <?php if (($product['stock'] ?? 0) <= 0): ?>
                                <div class="product-badge stock-out">Habis</div>
                            <?php else: ?>
                                <div class="product-badge">Bisa Dikirim</div>
                            <?php endif; ?>
                            <img src="<?php echo $product['image'] ?: 'img/products/default-product.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="product-overlay">
                                <button class="btn btn-white btn-small product-detail" 
                                        data-id="<?php echo $product['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-price="<?php echo $product['price']; ?>"
                                        data-stock="<?php echo $product['stock'] ?? 0; ?>"
                                        data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                        data-image="<?php echo $product['image'] ?: 'img/products/default-product.jpg'; ?>">
                                    <i data-feather="eye"></i>
                                </button>
                                <button class="btn btn-white btn-small add-to-cart" 
                                        data-id="<?php echo $product['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                        data-price="<?php echo $product['price']; ?>" 
                                        data-stock="<?php echo $product['stock'] ?? 0; ?>"
                                        data-type="product"
                                        <?php echo ($product['stock'] ?? 0) <= 0 ? 'disabled' : ''; ?>>
                                    <i data-feather="<?php echo ($product['stock'] ?? 0) <= 0 ? 'x' : 'plus'; ?>"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-content">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                            <p class="stock-info">
                                <i data-feather="package"></i>
                                Stok: <?php echo $product['stock'] ?? 0; ?>
                            </p>
                            <p class="product-shipping">
                                <i data-feather="truck"></i>
                                Tersedia pengiriman
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonial-section">
        <div class="container">
            <div class="section-header">
                <h2>Apa Kata Pelanggan <span>Kami</span></h2>
                <p>Testimoni dari para pecinta kopi yang telah merasakan cita rasa Mandailing</p>
            </div>
            
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">Kopi Mandailing di sini benar-benar autentik! Rasanya kuat dan aromanya menggoda. Tempat yang nyaman untuk menikmati kopi berkualitas.</p>
                    <p class="testimonial-author">- Ahmad Rizki, Pelanggan Setia</p>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">Saya suka sekali dengan produk kopi bubuknya. Bisa dibawa pulang dan rasanya tetap nikmat seperti di kafe. Pelayanannya juga ramah!</p>
                    <p class="testimonial-author">- Siti Nurhaliza, Ibu Rumah Tangga</p>
                </div>
                <div class="testimonial-card">
                    <p class="testimonial-text">Sebagai pecinta kopi, saya sangat merekomendasikan tempat ini. Kualitas biji kopinya premium dan harganya terjangkau.</p>
                    <p class="testimonial-author">- Budi Santoso, Coffee Enthusiast</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Siap Merasakan Cita Rasa Autentik?</h2>
            <p>Kunjungi kafe kami atau pesan produk pilihan untuk dibawa pulang</p>
            <div class="cta-buttons">
                <a href="#menu" class="btn btn-white">Lihat Menu</a>
                <a href="#products" class="btn btn-outline">Beli Produk</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2>Hubungi <span>Kami</span></h2>
                <p>Kami siap melayani Anda dengan sepenuh hati</p>
            </div>

            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-item">
                        <i data-feather="map-pin"></i>
                        <div>
                            <h3>Alamat</h3>
                            <p>Jl. Mandailing Raya No. 123<br>Panyabungan, Mandailing Natal<br>Sumatera Utara 22973</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i data-feather="phone"></i>
                        <div>
                            <h3>Telepon</h3>
                            <p>+62 812-3456-7890</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i data-feather="mail"></i>
                        <div>
                            <h3>Email</h3>
                            <p>info@kopikenangansenja.com</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i data-feather="clock"></i>
                        <div>
                            <h3>Jam Buka</h3>
                            <p>Senin - Jumat: 07:00 - 22:00<br>Sabtu - Minggu: 08:00 - 23:00</p>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <form id="contact-form" method="POST">
                        <input type="text" name="name" placeholder="Nama Anda" required>
                        <input type="email" name="email" placeholder="Email Anda" required>
                        <textarea name="message" rows="4" placeholder="Pesan Anda" required></textarea>
                        <button type="submit" class="btn btn-primary">Kirim Pesan</button>
                    </form>
                </div>
            </div>
            
            <!-- Map Section -->
            <div class="map-section">
                <div class="section-header">
                    <h2>Lokasi <span>Kami</span></h2>
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d179788.14439069518!2d106.73111856587569!3d-6.442202193959052!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69c3efc63ecc09%3A0xc74c10a339f311d!2sCafe%20and%20Restaurant%20Mandailing!5e0!3m2!1sid!2sid!4v1749912211761!5m2!1sid!2sid" 
                                width="100%" 
                                height="450" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Deling Kopi</h3>
                    <p>Menghadirkan cita rasa autentik kopi Mandailing dengan kualitas terbaik untuk setiap momen berharga Anda.</p>
                </div>
                <div class="footer-section">
                    <h4>Menu</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">Tentang Kami</a></li>
                        <li><a href="#menu">Menu</a></li>
                        <li><a href="#products">Produk</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Kontak</h4>
                    <ul>
                        <li>+62 822-1043-2168</li>
                        <li>info@delingkopi.com</li>
                        <li>Mandailing Natal, Sumut</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 Deling Kopi. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h2>Keranjang Belanja</h2>
            <button class="close-cart" id="close-cart">
                <i data-feather="x"></i>
            </button>
        </div>
        <div class="cart-content" id="cart-content">
            <p class="empty-cart">Keranjang kosong</p>
        </div>
        <div class="cart-footer" id="cart-footer" style="display: none;">
            <div class="cart-total">
                <span>Total: Rp <span id="cart-total">0</span></span>
            </div>
            <a href="checkout.php" class="btn btn-primary btn-full">Checkout</a>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div class="modal" id="product-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title"></h2>
                <button class="close-modal">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-image">
                    <img id="modal-image" src="/placeholder.svg" alt="">
                </div>
                <div class="modal-info">
                    <p id="modal-description"></p>
                    <p class="modal-price">Rp <span id="modal-price"></span></p>
                    <button class="btn btn-primary btn-full" id="modal-add-cart">
                        <i data-feather="plus"></i> Tambah ke Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Overlay -->
    <div class="cart-overlay" id="cart-overlay"></div>

    <script src="js/script.js"></script>
    <script src="js/minimal-live-search.js"></script>
    <script>
        // Initialize feather icons
        feather.replace();
        
        // Re-replace icons after dynamic content loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                feather.replace();
            }, 100);
        });
    </script>
</body>
</html>
