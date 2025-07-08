<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the intended action in session
    $_SESSION['redirect_after_login'] = 'checkout';
    header('Location: login.php');
    exit;
}

$message = '';
$success = false;

// Handle order submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $customerName = trim($_POST['customer_name']);
    $customerEmail = trim($_POST['customer_email']);
    $customerPhone = trim($_POST['customer_phone']);
    $customerAddress = trim($_POST['customer_address']);
    $cartData = json_decode($_POST['cart_data'], true);
    
    // Calculate total
    $total = 0;
    foreach ($cartData as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Validate form
    if (empty($customerName) || empty($customerEmail) || empty($customerPhone) || empty($customerAddress)) {
        $message = 'Semua field harus diisi';
    } elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid';
    } elseif (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Bukti transfer harus diupload';
    } elseif (empty($cartData)) {
        $message = 'Keranjang kosong';
    } else {
        // Handle file upload
        $uploadDir = 'uploads/payment_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['payment_proof']['type'], $allowedTypes)) {
            $message = 'Format file tidak didukung. Gunakan JPG, PNG, atau WebP';
        } else {
            $extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $uploadPath)) {
                try {
                    $pdo->beginTransaction();
                    
                    // Insert transaction
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, customer_name, customer_email, customer_phone, customer_address, total_amount, payment_proof, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'processing')");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $customerName,
                        $customerEmail,
                        $customerPhone,
                        $customerAddress,
                        $total,
                        $uploadPath
                    ]);
                    
                    $transactionId = $pdo->lastInsertId();
                    
                    // Insert order details and reduce stock
                    $stmt = $pdo->prepare("INSERT INTO orders (transaction_id, item_name, item_type, quantity, price) VALUES (?, ?, ?, ?, ?)");
                    
                    foreach ($cartData as $item) {
                        // Check stock availability before processing
                        $table = $item['type'] === 'menu' ? 'menukami' : 'products';
                        $stockStmt = $pdo->prepare("SELECT stock FROM $table WHERE id = ?");
                        $stockStmt->execute([$item['id']]);
                        $stockData = $stockStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$stockData || ($stockData['stock'] ?? 0) < $item['quantity']) {
                            throw new Exception("Stok tidak mencukupi untuk {$item['name']}. Stok tersedia: " . ($stockData['stock'] ?? 0));
                        }
                        
                        $stmt->execute([
                            $transactionId,
                            $item['name'],
                            $item['type'],
                            $item['quantity'],
                            $item['price']
                        ]);
                        
                        // Reduce stock
                        $updateStockStmt = $pdo->prepare("UPDATE $table SET stock = stock - ? WHERE id = ?");
                        $updateStockStmt->execute([$item['quantity'], $item['id']]);
                    }
                    
                    $pdo->commit();
                    
                    $success = true;
                    $message = 'Pesanan berhasil dikirim! Kami akan memproses pesanan Anda dalam 1x24 jam.';
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = 'Terjadi kesalahan saat memproses pesanan: ' . $e->getMessage();
                }
            } else {
                $message = 'Gagal mengupload bukti transfer';
            }
        }
    }
}

// Get user data for pre-filling form
$userData = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kopi Kenangan Senja</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .checkout-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            padding: 2rem 0;
        }
        
        .checkout-content {
            max-width: 1000px;
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
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .checkout-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
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
            padding: 1.5rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
            color: #1f2937;
        }
        
        .item-info p {
            margin: 0;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .item-price {
            font-weight: 600;
            color: #1f2937;
        }
        
        .order-total {
            border-top: 2px solid #e5e7eb;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.25rem;
            font-weight: 600;
            color: #d97706;
        }
        
        .payment-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .payment-info h3 {
            margin: 0 0 0.5rem 0;
            color: #92400e;
            font-size: 1rem;
        }
        
        .payment-info p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
            color: #92400e;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .file-upload {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload:hover {
            border-color: #d97706;
            background: #fef3c7;
        }
        
        .file-upload.dragover {
            border-color: #d97706;
            background: #fef3c7;
        }
        
        .file-upload input {
            display: none;
        }
        
        .file-upload-icon {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .file-upload p {
            margin: 0;
            color: #6b7280;
        }
        
        .file-upload .file-name {
            color: #d97706;
            font-weight: 500;
            margin-top: 0.5rem;
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
            color: #dc2626;
            border: 1px solid #fca5a5;
        }
        
        .success-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .success-modal.active {
            display: flex;
        }
        
        .success-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .success-icon {
            color: #10b981;
            margin-bottom: 1rem;
        }
        
        .confirmation-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .confirmation-modal.active {
            display: flex;
        }
        
        .confirmation-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .confirmation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .confirmation-header h3 {
            margin: 0;
            color: #1f2937;
        }
        
        .close-modal {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .confirmation-section {
            margin-bottom: 1.5rem;
        }
        
        .confirmation-section h4 {
            margin: 0 0 0.5rem 0;
            color: #374151;
            font-size: 1rem;
        }
        
        .confirmation-section p {
            margin: 0.25rem 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .confirmation-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .confirmation-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-cart i {
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .confirmation-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <input type="hidden" id="user-id" value="<?php echo $_SESSION['user_id']; ?>">
    <?php endif; ?>
    <div class="checkout-container">
        <div class="checkout-content">
            <div class="back-link">
                <a href="index.php">← Kembali ke beranda</a>
            </div>
            
            <?php if ($message && !$success): ?>
                <div class="message error"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <div id="checkout-content" style="display: none;">
                    <div class="checkout-grid">
                        <!-- Order Summary -->
                        <div class="checkout-card">
                            <div class="card-header">
                                <h2>
                                    <i data-feather="credit-card"></i>
                                    Ringkasan Pesanan
                                </h2>
                            </div>
                            <div class="card-content">
                                <div id="order-summary">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                                
                                <div class="payment-info">
                                    <h3>Informasi Pembayaran</h3>
                                    <p><strong>Bank:</strong> Mandiri</p>
                                    <p><strong>No. Rekening:</strong> 123-456-789</p>
                                    <p><strong>Atas Nama:</strong> Kopi Kenangan Senja</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Checkout Form -->
                        <div class="checkout-card">
                            <div class="card-header">
                                <h2>Detail Pengiriman</h2>
                            </div>
                            <div class="card-content">
                                <form id="checkout-form">
                                    <div class="form-group">
                                        <label>
                                            <i data-feather="user"></i>
                                            Nama Lengkap *
                                        </label>
                                        <input type="text" id="customer_name" name="customer_name" placeholder="Masukkan nama lengkap" value="<?php echo htmlspecialchars($userData['full_name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <i data-feather="map-pin"></i>
                                            Alamat Lengkap *
                                        </label>
                                        <textarea id="customer_address" name="customer_address" rows="3" placeholder="Masukkan alamat lengkap untuk pengiriman" required><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>
                                                <i data-feather="mail"></i>
                                                Email *
                                            </label>
                                            <input type="email" id="customer_email" name="customer_email" placeholder="contoh@email.com" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>
                                                <i data-feather="phone"></i>
                                                Nomor HP *
                                            </label>
                                            <input type="tel" id="customer_phone" name="customer_phone" placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <i data-feather="upload"></i>
                                            Bukti Transfer *
                                        </label>
                                        <div class="file-upload" onclick="document.getElementById('payment_proof').click()">
                                            <input type="file" id="payment_proof" name="payment_proof" accept="image/*" required>
                                            <div class="file-upload-icon">
                                                <i data-feather="upload" size="32"></i>
                                            </div>
                                            <p>Klik untuk upload bukti transfer</p>
                                            <p style="font-size: 0.75rem; margin-top: 0.5rem;">Format: JPG, PNG, WebP (Max 5MB)</p>
                                            <div class="file-name" id="file-name"></div>
                                        </div>
                                    </div>
                                    
                                    <button type="button" onclick="showConfirmation()" class="btn btn-primary btn-full" style="padding: 1rem; font-size: 1.125rem;">
                                        Konfirmasi Pesanan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Empty Cart Message -->
                <div id="empty-cart-message" style="display: none;">
                    <div class="checkout-card">
                        <div class="card-content">
                            <div class="empty-cart">
                                <i data-feather="shopping-cart" size="64"></i>
                                <h3>Keranjang Kosong</h3>
                                <p>Silakan tambahkan produk ke keranjang terlebih dahulu sebelum checkout.</p>
                                <a href="index.php#menu" class="btn btn-primary" style="margin-top: 1rem;">Lihat Menu</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="success-modal <?php echo $success ? 'active' : ''; ?>" id="success-modal">
        <div class="success-content">
            <div class="success-icon">
                <i data-feather="check-circle" size="64"></i>
            </div>
            <h2>Pesanan Berhasil!</h2>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;" onclick="clearCart()">Kembali ke Beranda</a>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="confirmation-modal" id="confirmation-modal">
        <div class="confirmation-content">
            <div class="confirmation-header">
                <h3>Konfirmasi Pesanan</h3>
                <button class="close-modal" onclick="closeConfirmation()">
                    <i data-feather="x"></i>
                </button>
            </div>
            
            <div class="confirmation-section">
                <h4>Data Pelanggan</h4>
                <p><strong>Nama:</strong> <span id="confirm-name"></span></p>
                <p><strong>Email:</strong> <span id="confirm-email"></span></p>
                <p><strong>HP:</strong> <span id="confirm-phone"></span></p>
                <p><strong>Alamat:</strong> <span id="confirm-address"></span></p>
            </div>
            
            <div class="confirmation-section">
                <h4>Pesanan</h4>
                <div id="confirm-items"></div>
                <p style="font-weight: 600; color: #d97706; font-size: 1.1rem; margin-top: 1rem;">
                    <strong>Total: Rp <span id="confirm-total"></span></strong>
                </p>
            </div>
            
            <div class="confirmation-section">
                <h4>Bukti Transfer</h4>
                <img id="confirm-image" src="/placeholder.svg" alt="Bukti Transfer" class="confirmation-image" style="display: none;">
                <p id="confirm-image-name"></p>
            </div>
            
            <div class="confirmation-buttons">
                <button type="button" onclick="closeConfirmation()" class="btn btn-outline btn-full">Batal</button>
                <button type="button" onclick="submitOrder()" class="btn btn-primary btn-full">Konfirmasi & Kirim</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentCart = [];
        
        // Load cart from localStorage and server
        async function loadCart() {
            let cart = [];
            
            // First try to get from localStorage (for immediate display)
            const localCart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // If user is logged in, also try to get from server
            const userIdElement = document.getElementById('user-id');
            const isLoggedIn = userIdElement !== null;
            
            if (isLoggedIn) {
                try {
                    const response = await fetch('cart_api.php?action=load_cart');
                    const data = await response.json();
                    
                    if (data.success && data.cart && data.cart.length > 0) {
                        cart = data.cart;
                    } else {
                        cart = localCart;
                    }
                } catch (error) {
                    console.error('Error loading cart from server:', error);
                    cart = localCart;
                }
            } else {
                cart = localCart;
            }
            
            currentCart = cart;
            
            if (cart.length === 0) {
                document.getElementById('checkout-content').style.display = 'none';
                document.getElementById('empty-cart-message').style.display = 'block';
                return;
            }
            
            document.getElementById('checkout-content').style.display = 'block';
            document.getElementById('empty-cart-message').style.display = 'none';
            
            let total = 0;
            let orderSummaryHTML = '';
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                orderSummaryHTML += `
                    <div class="order-item">
                        <div class="item-info">
                            <h3>${item.name}</h3>
                            <p>Qty: ${item.quantity}</p>
                            ${item.type === 'menu' ? '<p style="color: #f59e0b; font-size: 0.75rem;">📍 Hanya di kafe</p>' : ''}
                        </div>
                        <div class="item-price">
                            Rp ${itemTotal.toLocaleString('id-ID')}
                        </div>
                    </div>
                `;
            });
            
            orderSummaryHTML += `
                <div class="order-total">
                    <div class="total-row">
                        <span>Total:</span>
                        <span>Rp ${total.toLocaleString('id-ID')}</span>
                    </div>
                </div>
            `;
            
            document.getElementById('order-summary').innerHTML = orderSummaryHTML;
        }
        
        // File upload handling
        document.getElementById('payment_proof').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileNameDiv = document.getElementById('file-name');
            
            if (fileName) {
                fileNameDiv.textContent = `File terpilih: ${fileName}`;
            } else {
                fileNameDiv.textContent = '';
            }
        });
        
        // Drag and drop for file upload
        const fileUpload = document.querySelector('.file-upload');
        
        fileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        fileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        fileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('payment_proof').files = files;
                document.getElementById('file-name').textContent = `File terpilih: ${files[0].name}`;
            }
        });
        
        // Show confirmation modal
        function showConfirmation() {
            const form = document.getElementById('checkout-form');
            const formData = new FormData(form);
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Check if file is selected
            const fileInput = document.getElementById('payment_proof');
            if (!fileInput.files[0]) {
                alert('Silakan upload bukti transfer terlebih dahulu');
                return;
            }
            
            // Check for menu items and show additional warning
            const hasMenuItems = currentCart.some(item => item.type === 'menu');
            if (hasMenuItems) {
                const confirmProceed = confirm(
                    '⚠️ PERHATIAN PENTING!\n\n' +
                    'Pesanan Anda berisi menu kopi yang HANYA dapat dinikmati di kafe.\n\n' +
                    'Pastikan Anda akan datang ke kafe untuk menikmati pesanan Anda.\n\n' +
                    'Lanjutkan konfirmasi pesanan?'
                );
                
                if (!confirmProceed) {
                    return;
                }
            }
            
            // Populate confirmation data
            document.getElementById('confirm-name').textContent = formData.get('customer_name');
            document.getElementById('confirm-email').textContent = formData.get('customer_email');
            document.getElementById('confirm-phone').textContent = formData.get('customer_phone');
            document.getElementById('confirm-address').textContent = formData.get('customer_address');
            
            // Show cart items with menu warnings
            let total = 0;
            let itemsHTML = '';
            
            currentCart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                const menuWarning = item.type === 'menu' ? ' <span style="color: #f59e0b;">(Hanya di kafe)</span>' : '';
                itemsHTML += `<p>${item.name} x${item.quantity}${menuWarning} - Rp ${itemTotal.toLocaleString('id-ID')}</p>`;
            });
            
            document.getElementById('confirm-items').innerHTML = itemsHTML;
            document.getElementById('confirm-total').textContent = total.toLocaleString('id-ID');
            
            // Show payment proof image
            const file = fileInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('confirm-image');
                    img.src = e.target.result;
                    img.style.display = 'block';
                };
                reader.readAsDataURL(file);
                document.getElementById('confirm-image-name').textContent = `File: ${file.name}`;
            }
            
            document.getElementById('confirmation-modal').classList.add('active');
        }
        
        function closeConfirmation() {
            document.getElementById('confirmation-modal').classList.remove('active');
        }
        
        // Submit order
        function submitOrder() {
            const form = document.getElementById('checkout-form');
            const formData = new FormData(form);
            formData.append('action', 'place_order');
            formData.append('cart_data', JSON.stringify(currentCart));
            
            // Show loading
            const submitBtn = document.querySelector('.confirmation-buttons .btn-primary');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Memproses...';
            submitBtn.disabled = true;
            
            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Clear cart from localStorage
                localStorage.removeItem('cart');
                
                // Reload page to show success message
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }
        
        function clearCart() {
            localStorage.removeItem('cart');
        }
        
        // Load cart on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add small delay to ensure DOM is fully loaded
            setTimeout(() => {
                loadCart();
                feather.replace();
            }, 100);
        });
        
        feather.replace();
    </script>
</body>
</html>
