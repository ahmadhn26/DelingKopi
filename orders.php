<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build date filter condition
$dateCondition = '';
$dateParams = [];

switch ($filter) {
    case 'this_month':
        $dateCondition = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case '1_month_ago':
        $dateCondition = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND t.created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case '5_months_ago':
        $dateCondition = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND t.created_at < DATE_SUB(NOW(), INTERVAL 5 MONTH)";
        break;
    case '1_year_ago':
        $dateCondition = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 2 YEAR) AND t.created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    case 'all':
    default:
        // No date filter
        break;
}

// Build search condition
$searchCondition = '';
$searchParams = [];
if ($search) {
    $searchCondition = " AND (t.customer_name LIKE ? OR o.item_name LIKE ?)";
    $searchParams = ['%' . $search . '%', '%' . $search . '%'];
}

// Fetch user's orders with search and filter
$query = "
    SELECT t.*, GROUP_CONCAT(CONCAT(o.item_name, ' (', o.quantity, 'x)') SEPARATOR ', ') as items
    FROM transactions t
    LEFT JOIN orders o ON t.id = o.transaction_id
    WHERE t.user_id = ?" . $dateCondition . $searchCondition . "
    GROUP BY t.id
    ORDER BY t.created_at DESC
";

$params = array_merge([$_SESSION['user_id']], $searchParams);
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Kopi Kenangan Senja</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .orders-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            padding: 2rem 0;
        }
        
        .orders-content {
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
        
        .orders-card {
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
        
        .card-header h1 {
            margin: 0;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filters-section {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: end;
        }
        
        .search-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-group input {
            flex: 1;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .search-group input:focus {
            border-color: #d97706;
        }
        
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            outline: none;
            background: white;
            min-width: 150px;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        .order-item {
            border: 1px solid #e5e7eb;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: white;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .order-info h3 {
            margin: 0 0 0.5rem 0;
            color: #1f2937;
            font-size: 1.125rem;
        }
        
        .order-info p {
            margin: 0.25rem 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .order-total {
            font-size: 1.25rem;
            font-weight: 600;
            color: #d97706;
        }
        
        .order-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .status-processing {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .order-items {
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .order-items h4 {
            margin: 0 0 0.5rem 0;
            color: #374151;
        }
        
        .order-items p {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .empty-orders {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-orders i {
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #1f2937;
        }
        
        .close-modal {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .detail-section {
            margin-bottom: 1.5rem;
        }
        
        .detail-section h4 {
            margin: 0 0 0.5rem 0;
            color: #374151;
            font-size: 1rem;
        }
        
        .detail-section p {
            margin: 0.25rem 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .detail-items {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item-info h5 {
            margin: 0;
            font-size: 0.875rem;
            color: #1f2937;
        }
        
        .detail-item-info p {
            margin: 0;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .detail-item-price {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.875rem;
        }
        
        .detail-total {
            border-top: 2px solid #e5e7eb;
            padding-top: 1rem;
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.125rem;
            font-weight: 600;
            color: #d97706;
        }
        
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .search-group {
                flex-direction: column;
            }
            
            .order-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .order-actions {
                align-self: stretch;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="orders-container">
        <div class="orders-content">
            <div class="back-link">
                <a href="index.php">← Kembali ke beranda</a>
            </div>
            
            <div class="orders-card">
                <div class="card-header">
                    <h1>
                        <i data-feather="package"></i>
                        Riwayat Pesanan
                    </h1>
                </div>
                
                <div class="filters-section">
                    <form method="GET" class="filters-grid">
                        <div class="search-group">
                            <input type="text" name="search" placeholder="Cari pesanan atau item..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                        
                        <div class="filter-group">
                            <select name="filter" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Semua Transaksi</option>
                                <option value="this_month" <?php echo $filter === 'this_month' ? 'selected' : ''; ?>>Bulan Ini</option>
                                <option value="1_month_ago" <?php echo $filter === '1_month_ago' ? 'selected' : ''; ?>>1 Bulan Lalu</option>
                                <option value="5_months_ago" <?php echo $filter === '5_months_ago' ? 'selected' : ''; ?>>5 Bulan Lalu</option>
                                <option value="1_year_ago" <?php echo $filter === '1_year_ago' ? 'selected' : ''; ?>>1 Tahun Lalu</option>
                            </select>
                        </div>
                        
                        <?php if ($search || $filter !== 'all'): ?>
                            <a href="orders.php" class="btn btn-outline">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="card-content">
                    <?php if (empty($orders)): ?>
                        <div class="empty-orders">
                            <i data-feather="shopping-bag" size="64"></i>
                            <h3>Tidak Ada Pesanan</h3>
                            <p>
                                <?php if ($search || $filter !== 'all'): ?>
                                    Tidak ditemukan pesanan dengan kriteria pencarian tersebut.
                                <?php else: ?>
                                    Anda belum memiliki riwayat pesanan. Mulai berbelanja sekarang!
                                <?php endif; ?>
                            </p>
                            <a href="index.php#menu" class="btn btn-primary" style="margin-top: 1rem;">Lihat Menu</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-item">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h3>Pesanan #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></h3>
                                        <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                        <p><strong>Status:</strong> 
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <i data-feather="<?php echo $order['status'] === 'completed' ? 'check-circle' : 'clock'; ?>"></i>
                                                <?php echo $order['status'] === 'completed' ? 'Selesai' : 'Diproses'; ?>
                                            </span>
                                        </p>
                                        <p class="order-total">Total: Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                                    </div>
                                    <div class="order-actions">
                                        <button class="btn btn-outline btn-small" onclick="viewOrderDetail(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                            <i data-feather="eye"></i> Detail
                                        </button>
                                    </div>
                                </div>
                                
                                <?php if ($order['items']): ?>
                                    <div class="order-items">
                                        <h4>Item Pesanan:</h4>
                                        <p><?php echo htmlspecialchars($order['items']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Detail Modal -->
    <div class="modal" id="order-detail-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-order-title">Detail Pesanan</h3>
                <button class="close-modal" onclick="closeOrderDetail()">
                    <i data-feather="x"></i>
                </button>
            </div>
            
            <div class="detail-section">
                <h4>Informasi Pesanan</h4>
                <p><strong>Nomor Pesanan:</strong> <span id="detail-order-id"></span></p>
                <p><strong>Tanggal:</strong> <span id="detail-order-date"></span></p>
                <p><strong>Status:</strong> <span id="detail-order-status"></span></p>
            </div>
            
            <div class="detail-section">
                <h4>Informasi Pengiriman</h4>
                <p><strong>Nama:</strong> <span id="detail-customer-name"></span></p>
                <p><strong>Email:</strong> <span id="detail-customer-email"></span></p>
                <p><strong>HP:</strong> <span id="detail-customer-phone"></span></p>
                <p><strong>Alamat:</strong> <span id="detail-customer-address"></span></p>
            </div>
            
            <div class="detail-section">
                <h4>Detail Pesanan</h4>
                <div class="detail-items" id="detail-items-container">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <button class="btn btn-outline" onclick="closeOrderDetail()">Tutup</button>
            </div>
        </div>
    </div>
    
    <script>
        function viewOrderDetail(order) {
            // Populate modal with order data
            document.getElementById('modal-order-title').textContent = `Detail Pesanan #${String(order.id).padStart(4, '0')}`;
            document.getElementById('detail-order-id').textContent = `#${String(order.id).padStart(4, '0')}`;
            document.getElementById('detail-order-date').textContent = new Date(order.created_at).toLocaleString('id-ID');
            
            const statusBadge = document.getElementById('detail-order-status');
            const statusIcon = order.status === 'completed' ? 'check-circle' : 'clock';
            const statusText = order.status === 'completed' ? 'Selesai' : 'Diproses';
            statusBadge.innerHTML = `<span class="status-badge status-${order.status}"><i data-feather="${statusIcon}"></i> ${statusText}</span>`;
            
            document.getElementById('detail-customer-name').textContent = order.customer_name;
            document.getElementById('detail-customer-email').textContent = order.customer_email;
            document.getElementById('detail-customer-phone').textContent = order.customer_phone;
            document.getElementById('detail-customer-address').textContent = order.customer_address;
            
            // Fetch detailed order items
            fetchOrderItems(order.id);
            
            document.getElementById('order-detail-modal').classList.add('active');
            feather.replace();
        }
        
        function fetchOrderItems(orderId) {
            fetch(`get_order_items.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    let itemsHTML = '';
                    let total = 0;
                    
                    data.items.forEach(item => {
                        const itemTotal = item.price * item.quantity;
                        total += itemTotal;
                        
                        itemsHTML += `
                            <div class="detail-item">
                                <div class="detail-item-info">
                                    <h5>${item.item_name}</h5>
                                    <p>Rp ${parseInt(item.price).toLocaleString('id-ID')} x ${item.quantity}</p>
                                </div>
                                <div class="detail-item-price">
                                    Rp ${itemTotal.toLocaleString('id-ID')}
                                </div>
                            </div>
                        `;
                    });
                    
                    itemsHTML += `
                        <div class="detail-total">
                            <span>Total Pembayaran:</span>
                            <span>Rp ${total.toLocaleString('id-ID')}</span>
                        </div>
                    `;
                    
                    document.getElementById('detail-items-container').innerHTML = itemsHTML;
                })
                .catch(error => {
                    console.error('Error fetching order items:', error);
                    document.getElementById('detail-items-container').innerHTML = '<p>Gagal memuat detail pesanan</p>';
                });
        }
        
        function closeOrderDetail() {
            document.getElementById('order-detail-modal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('order-detail-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderDetail();
            }
        });
        
        feather.replace();
    </script>
</body>
</html>
