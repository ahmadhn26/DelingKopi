<?php
session_start();
require_once 'config/db.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Database setup - add stock columns if they don't exist
try {
    // Check and add stock column to products table
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('stock', $columns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 AFTER price");
        $pdo->exec("UPDATE products SET stock = 50 WHERE stock = 0");
    }
    
    // Check and add stock column to menukami table
    $stmt = $pdo->query("DESCRIBE menukami");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('stock', $columns)) {
        $pdo->exec("ALTER TABLE menukami ADD COLUMN stock INT DEFAULT 0 AFTER price");
        $pdo->exec("UPDATE menukami SET stock = 50 WHERE stock = 0");
    }
} catch (PDOException $e) {
    // Silently handle if columns already exist
}

$message = '';
$activeTab = $_GET['tab'] ?? 'dashboard';

// Handle file upload
function handleFileUpload($file, $type) {
    $uploadDir = "img/{$type}/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $uploadPath;
    }
    
    return false;
}

// Handle operations (existing code for products, menu, transactions)
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_product') {
        $name = trim($_POST['name']);
        $price = (int)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $description = trim($_POST['description']);
        
        $image = 'img/products/default-product.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedImage = handleFileUpload($_FILES['image'], 'products');
            if ($uploadedImage) {
                $image = $uploadedImage;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, description, image) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $price, $stock, $description, $image])) {
            $message = 'Produk berhasil ditambahkan';
        }
    }
    
    elseif ($action === 'edit_product') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $price = (int)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $description = trim($_POST['description']);
        
        // Get current image
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        $image = $currentProduct['image'];
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedImage = handleFileUpload($_FILES['image'], 'products');
            if ($uploadedImage) {
                // Delete old image if it's not default
                if ($image && $image !== 'img/products/default-product.jpg' && file_exists($image)) {
                    unlink($image);
                }
                $image = $uploadedImage;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?, description = ?, image = ? WHERE id = ?");
        if ($stmt->execute([$name, $price, $stock, $description, $image, $id])) {
            $message = 'Produk berhasil diupdate';
        }
    }
    
    elseif ($action === 'delete_product') {
        $id = (int)$_POST['id'];
        
        // Get image path before deleting
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$id])) {
            // Delete image file if it's not default
            if ($product['image'] && $product['image'] !== 'img/products/default-product.jpg' && file_exists($product['image'])) {
                unlink($product['image']);
            }
            $message = 'Produk berhasil dihapus';
        }
    }
    
    elseif ($action === 'add_menu') {
        $name = trim($_POST['name']);
        $price = (int)$_POST['price'];
        $stock = (int)$_POST['stock'];
        
        $image = 'img/menu/default-coffee.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedImage = handleFileUpload($_FILES['image'], 'menu');
            if ($uploadedImage) {
                $image = $uploadedImage;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO menukami (name, price, stock, image) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $price, $stock, $image])) {
            $message = 'Menu berhasil ditambahkan';
        }
    }
    
    elseif ($action === 'edit_menu') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $price = (int)$_POST['price'];
        $stock = (int)$_POST['stock'];
        
        // Get current image
        $stmt = $pdo->prepare("SELECT image FROM menukami WHERE id = ?");
        $stmt->execute([$id]);
        $currentMenu = $stmt->fetch(PDO::FETCH_ASSOC);
        $image = $currentMenu['image'];
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedImage = handleFileUpload($_FILES['image'], 'menu');
            if ($uploadedImage) {
                // Delete old image if it's not default
                if ($image && $image !== 'img/menu/default-coffee.jpg' && file_exists($image)) {
                    unlink($image);
                }
                $image = $uploadedImage;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE menukami SET name = ?, price = ?, stock = ?, image = ? WHERE id = ?");
        if ($stmt->execute([$name, $price, $stock, $image, $id])) {
            $message = 'Menu berhasil diupdate';
        }
    }
    
    elseif ($action === 'delete_menu') {
        $id = (int)$_POST['id'];
        
        // Get image path before deleting
        $stmt = $pdo->prepare("SELECT image FROM menukami WHERE id = ?");
        $stmt->execute([$id]);
        $menu = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("DELETE FROM menukami WHERE id = ?");
        if ($stmt->execute([$id])) {
            // Delete image file if it's not default
            if ($menu['image'] && $menu['image'] !== 'img/menu/default-coffee.jpg' && file_exists($menu['image'])) {
                unlink($menu['image']);
            }
            $message = 'Menu berhasil dihapus';
        }
    }
    
    elseif ($action === 'complete_transaction') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'Transaksi berhasil diselesaikan';
        }
    }
    
    elseif ($action === 'delete_transaction') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'Transaksi berhasil dihapus';
        }
    }
    
    // User management operations
    elseif ($action === 'edit_user') {
        $id = (int)$_POST['id'];
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $role = $_POST['role'];
        
        // Check if username or email already exists for other users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Username atau email sudah digunakan oleh pengguna lain';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, address = ?, role = ? WHERE id = ?");
            if ($stmt->execute([$username, $full_name, $email, $phone, $address, $role, $id])) {
                $message = 'Data pengguna berhasil diupdate';
            }
        }
    }
    
    elseif ($action === 'delete_user') {
        $id = (int)$_POST['id'];
        
        // Prevent deleting current admin
        if ($id == $_SESSION['user_id']) {
            $message = 'Tidak dapat menghapus akun admin yang sedang login';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = 'Pengguna berhasil dihapus';
            }
        }
    }
    
    elseif ($action === 'reset_password') {
        $id = (int)$_POST['id'];
        $new_password = 'newpassword123'; // Default password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $id])) {
            $message = "Password berhasil direset ke: $new_password";
        }
    }
}

// Get filter parameter for dashboard
$dashboardFilter = $_GET['dashboard_filter'] ?? 'all';

// Build dashboard date condition
$dashboardDateCondition = '';
switch ($dashboardFilter) {
    case '1_month':
        $dashboardDateCondition = " WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case '6_months':
        $dashboardDateCondition = " WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        break;
    case '1_year':
        $dashboardDateCondition = " WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    case 'all':
    default:
        $dashboardDateCondition = "";
        break;
}

// Fetch dashboard data with filter
$dashboardStats = [];

// Total transactions with filter
$stmt = $pdo->query("SELECT COUNT(*) as total_transactions, SUM(total_amount) as total_revenue FROM transactions" . $dashboardDateCondition);
$dashboardStats['transactions'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Total products and menu items (no filter needed)
$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$dashboardStats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

$stmt = $pdo->query("SELECT COUNT(*) as total_menu FROM menukami");
$dashboardStats['menu'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_menu'];

// Recent transactions with filter
$recentQuery = "SELECT * FROM transactions";
if ($dashboardDateCondition) {
    $recentQuery .= $dashboardDateCondition;
}
$recentQuery .= " ORDER BY created_at DESC LIMIT 5";
$stmt = $pdo->query($recentQuery);
$dashboardStats['recent_transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top selling products with filter
$topProductsQuery = "
    SELECT 
        o.item_name,
        o.item_type,
        SUM(o.quantity) as total_sold,
        SUM(o.quantity * o.price) as total_revenue,
        COUNT(DISTINCT o.transaction_id) as order_count
    FROM orders o
    JOIN transactions t ON o.transaction_id = t.id
    WHERE t.status = 'completed'";
if ($dashboardDateCondition) {
    $topProductsQuery .= " AND t." . substr($dashboardDateCondition, 7); // Remove "WHERE " prefix
}
$topProductsQuery .= "
    GROUP BY o.item_name, o.item_type
    ORDER BY total_sold DESC
    LIMIT 10
";
$stmt = $pdo->query($topProductsQuery);
$dashboardStats['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sales by month with filter
$salesByMonthQuery = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as transaction_count,
        SUM(total_amount) as revenue
    FROM transactions 
    WHERE status = 'completed'";
if ($dashboardFilter === '1_month') {
    $salesByMonthQuery .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($dashboardFilter === '6_months') {
    $salesByMonthQuery .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($dashboardFilter === '1_year') {
    $salesByMonthQuery .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}
$salesByMonthQuery .= "
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
";
$stmt = $pdo->query($salesByMonthQuery);
$dashboardStats['sales_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Product type analysis with filter
$productTypeQuery = "
    SELECT 
        o.item_type,
        COUNT(*) as item_count,
        SUM(o.quantity) as total_quantity,
        SUM(o.quantity * o.price) as total_revenue
    FROM orders o
    JOIN transactions t ON o.transaction_id = t.id
    WHERE t.status = 'completed'";
if ($dashboardDateCondition) {
    $productTypeQuery .= " AND t." . substr($dashboardDateCondition, 7); // Remove "WHERE " prefix
}
$productTypeQuery .= " GROUP BY o.item_type";
$stmt = $pdo->query($productTypeQuery);
$dashboardStats['product_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch other data
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$menuItems = $pdo->query("SELECT * FROM menukami ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$transactionsQuery = "
    SELECT t.*, GROUP_CONCAT(CONCAT(o.item_name, ' (', o.quantity, 'x)') SEPARATOR ', ') as items
    FROM transactions t
    LEFT JOIN orders o ON t.id = o.transaction_id
    GROUP BY t.id
    ORDER BY t.created_at DESC
";
$transactions = $pdo->query($transactionsQuery)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kopi Kenangan Senja</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <?php /*<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> */?>
    <style>
        .admin-container {
            min-height: 100vh;
            background: #f9fafb;
        }
        
        .admin-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        
        .admin-nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-nav h1 {
            color: #1f2937;
            font-size: 1.5rem;
        }
        
        .admin-actions {
            display: flex;
            gap: 1rem;
        }
        
        .admin-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
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
        
        .admin-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .admin-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .admin-card-header h2 {
            margin: 0;
            color: #1f2937;
        }
        
        .admin-card-content {
            padding: 1.5rem;
        }
        
        /* Dashboard specific styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #d97706;
        }
        
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .chart-container h3 {
            margin: 0 0 1rem 0;
            color: #1f2937;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        
        .top-products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .top-products-table th,
        .top-products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .top-products-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .product-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-menu {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-product {
            background: #d1fae5;
            color: #065f46;
        }
        
        .stock-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .stock-normal {
            background: #d1fae5;
            color: #065f46;
        }
        
        .stock-low {
            background: #fecaca;
            color: #991b1b;
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .role-admin {
            background: #fef3c7;
            color: #92400e;
        }
        
        .role-user {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .recent-transactions {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .transaction-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-info h4 {
            margin: 0 0 0.25rem 0;
            color: #1f2937;
            font-size: 0.875rem;
        }
        
        .transaction-info p {
            margin: 0;
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        .transaction-amount {
            font-weight: 600;
            color: #d97706;
        }
        
        /* Existing styles for other tabs */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #d97706;
        }
        
        .file-upload {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload:hover {
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
            font-size: 0.875rem;
        }
        
        .file-upload .file-name {
            color: #d97706;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .data-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items:center;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
            /* margin-top:1.8rem; */
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-processing {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;

        }
        
        .transaction-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #d97706;
        }
        
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .transaction-info h3 {
            margin: 0 0 0.5rem 0;
            color: #1f2937;
        }
        
        .transaction-info p {
            margin: 0.25rem 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .transaction-total {
            font-size: 1.25rem;
            font-weight: 600;
            color: #d97706;
        }
        
        .transaction-items {
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .transaction-items h4 {
            margin: 0 0 0.5rem 0;
            color: #374151;
        }
        
        .transaction-items p {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
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
            max-width: 500px;
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
        }
        
        .close-modal {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .admin-content {
                padding: 2rem 15px;
            }
            
            .chart-container {
                margin-bottom: 1rem;
            }
            
            /* Make charts stack vertically on smaller screens */
            .charts-row {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem 15px;
            }
            
            .admin-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                border-radius: 0 !important;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            /* Make dashboard components stack vertically on mobile */
            .dashboard-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }
            
            .charts-row {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }
            
            .bottom-section {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }
            
            .chart-container {
                margin-bottom: 1rem;
            }
            
            .chart-wrapper {
                height: 250px;
            }
            
            .top-products-table {
                font-size: 0.875rem;
            }
            
            .top-products-table th,
            .top-products-table td {
                padding: 0.5rem;
            }
            
            .recent-transactions {
                max-height: 300px;
            }
            
            .transaction-item {
                padding: 0.75rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .transaction-amount {
                align-self: flex-end;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                font-size: 0.875rem;
                overflow-x: auto;
            }
            
            .transaction-header {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .admin-content {
                padding: 1rem 10px;
            }
            
            .admin-card-content {
                padding: 1rem;
            }
            
            .chart-wrapper {
                height: 200px;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 1.5rem !important;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-nav">
                <h1>Admin Dashboard</h1>
                <div class="admin-actions">
                    <a href="index.php" class="btn btn-outline">Kembali ke Website</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                </div>
            </div>
        </div>
        
        <div class="admin-content">
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" onclick="switchTab('dashboard')">Dashboard</button>
                <button class="tab <?php echo $activeTab === 'products' ? 'active' : ''; ?>" onclick="switchTab('products')">Produk</button>
                <button class="tab <?php echo $activeTab === 'menu' ? 'active' : ''; ?>" onclick="switchTab('menu')">Menu</button>
                <button class="tab <?php echo $activeTab === 'transactions' ? 'active' : ''; ?>" onclick="switchTab('transactions')">Transaksi</button>
                <button class="tab <?php echo $activeTab === 'users' ? 'active' : ''; ?>" onclick="switchTab('users')">Kelola Pengguna</button>
            </div>
            
            <!-- Dashboard Tab -->
            <div class="tab-content <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" id="dashboard">
                <!-- Dashboard Filter -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Filter Dashboard</h2>
                    </div>
                    <div class="admin-card-content">
                        <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="tab" value="dashboard">
                            <select name="dashboard_filter" onchange="this.form.submit()" style="padding: 8px 12px; border: 2px solid #e5e7eb; border-radius: 6px;">
                                <option value="all" <?php echo $dashboardFilter === 'all' ? 'selected' : ''; ?>>Semua Data</option>
                                <option value="1_month" <?php echo $dashboardFilter === '1_month' ? 'selected' : ''; ?>>1 Bulan Terakhir</option>
                                <option value="6_months" <?php echo $dashboardFilter === '6_months' ? 'selected' : ''; ?>>6 Bulan Terakhir</option>
                                <option value="1_year" <?php echo $dashboardFilter === '1_year' ? 'selected' : ''; ?>>1 Tahun Terakhir</option>
                            </select>
                            <?php if ($dashboardFilter !== 'all'): ?>
                                <a href="?tab=dashboard" class="btn btn-outline btn-small">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Transaksi</h3>
                        <div class="stat-value"><?php echo $dashboardStats['transactions']['total_transactions'] ?: 0; ?></div>
                        <div class="stat-label">
                            <?php 
                            switch($dashboardFilter) {
                                case '1_month': echo '1 bulan terakhir'; break;
                                case '6_months': echo '6 bulan terakhir'; break;
                                case '1_year': echo '1 tahun terakhir'; break;
                                default: echo 'Semua transaksi'; break;
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Pendapatan</h3>
                        <div class="stat-value">Rp <?php echo number_format($dashboardStats['transactions']['total_revenue'] ?: 0, 0, ',', '.'); ?></div>
                        <div class="stat-label">
                            <?php 
                            switch($dashboardFilter) {
                                case '1_month': echo '1 bulan terakhir'; break;
                                case '6_months': echo '6 bulan terakhir'; break;
                                case '1_year': echo '1 tahun terakhir'; break;
                                default: echo 'Dari semua penjualan'; break;
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Produk</h3>
                        <div class="stat-value"><?php echo $dashboardStats['products']; ?></div>
                        <div class="stat-label">Produk tersedia</div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Menu</h3>
                        <div class="stat-value"><?php echo $dashboardStats['menu']; ?></div>
                        <div class="stat-label">Menu tersedia</div>
                    </div>
                </div>
                
                <?php /*
                <!-- Charts Row -->
                <div class="charts-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <!-- Sales Chart -->
                    <div class="chart-container">
                        <h3>Penjualan Bulanan</h3>
                        <div class="chart-wrapper">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Product Type Chart -->
                    <div class="chart-container">
                        <h3>Penjualan per Kategori</h3>
                        <div class="chart-wrapper">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                */?>
                
                <!-- Bottom Section -->
                <div class="bottom-section" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <!-- Top Products -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2>Produk Terlaris</h2>
                        </div>
                        <div class="admin-card-content">
                            <!-- Desktop Table View -->
                            <div class="data-table-container hide-mobile">
                                <table class="top-products-table">
                                    <thead>
                                        <tr>
                                            <th>Nama Produk</th>
                                            <th>Kategori</th>
                                            <th>Terjual</th>
                                            <th>Pendapatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dashboardStats['top_products'] as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['item_name']); ?></td>
                                                <td>
                                                    <span class="product-type-badge badge-<?php echo $product['item_type']; ?>">
                                                        <?php echo ucfirst($product['item_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $product['total_sold']; ?>x</td>
                                                <td>Rp <?php echo number_format($product['total_revenue'], 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Card View -->
                            <div class="mobile-card-layout show-mobile">
                                <?php foreach ($dashboardStats['top_products'] as $product): ?>
                                    <div class="mobile-card">
                                        <div class="mobile-card-header">
                                            <div class="mobile-card-title"><?php echo htmlspecialchars($product['item_name']); ?></div>
                                            <span class="product-type-badge badge-<?php echo $product['item_type']; ?>">
                                                <?php echo ucfirst($product['item_type']); ?>
                                            </span>
                                        </div>
                                        <div class="mobile-card-content">
                                            <div class="mobile-card-row">
                                                <span class="mobile-card-label">Terjual:</span>
                                                <span class="mobile-card-value"><?php echo $product['total_sold']; ?>x</span>
                                            </div>
                                            <div class="mobile-card-row">
                                                <span class="mobile-card-label">Pendapatan:</span>
                                                <span class="mobile-card-value">Rp <?php echo number_format($product['total_revenue'], 0, ',', '.'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h2>Transaksi Terbaru</h2>
                        </div>
                        <div class="admin-card-content">
                            <div class="recent-transactions">
                                <?php foreach ($dashboardStats['recent_transactions'] as $transaction): ?>
                                    <div class="transaction-item">
                                        <div class="transaction-info">
                                            <h4><?php echo htmlspecialchars($transaction['customer_name']); ?></h4>
                                            <p><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></p>
                                        </div>
                                        <div class="transaction-amount">
                                            Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products Tab -->
            <div class="tab-content <?php echo $activeTab === 'products' ? 'active' : ''; ?>" id="products">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Tambah Produk Baru</h2>
                    </div>
                    <div class="admin-card-content">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_product">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nama Produk</label>
                                    <input type="text" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label>Harga</label>
                                    <input type="number" name="price" required>
                                </div>
                                <div class="form-group">
                                    <label>Stok</label>
                                    <input type="number" name="stock" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Gambar Produk</label>
                                    <div class="file-upload" onclick="document.getElementById('add-product-image').click()">
                                        <input type="file" id="add-product-image" name="image" accept="image/*">
                                        <div class="file-upload-icon">
                                            <i data-feather="upload" size="24"></i>
                                        </div>
                                        <p>Klik untuk pilih gambar</p>
                                        <div class="file-name" id="add-product-file-name"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">
                                        <i data-feather="plus"></i> Tambah Produk
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="description" rows="3" required></textarea>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Daftar Produk</h2>
                    </div>
                    <div class="admin-card-content">
                        <!-- Desktop Table View -->
                        <div class="data-table-container hide-mobile">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Nama</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Deskripsi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            </td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="stock-badge <?php echo ($product['stock'] ?? 0) <= 5 ? 'stock-low' : 'stock-normal'; ?>">
                                                    <?php echo $product['stock'] ?? 0; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-outline btn-small" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                        <i data-feather="edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                                                        <input type="hidden" name="action" value="delete_product">
                                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-small">
                                                            <i data-feather="trash-2"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mobile Card View -->
                        <div class="mobile-card-layout show-mobile">
                            <?php foreach ($products as $product): ?>
                                <div class="mobile-card">
                                    <div class="mobile-card-header">
                                        <div class="mobile-card-title"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                    </div>
                                    <div class="mobile-card-content">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Harga:</span>
                                            <span class="mobile-card-value">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Stok:</span>
                                            <span class="mobile-card-value">
                                                <span class="stock-badge <?php echo ($product['stock'] ?? 0) <= 5 ? 'stock-low' : 'stock-normal'; ?>">
                                                    <?php echo $product['stock'] ?? 0; ?>
                                                </span>
                                            </span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Deskripsi:</span>
                                            <span class="mobile-card-value"><?php echo htmlspecialchars(substr($product['description'], 0, 30)) . '...'; ?></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <div class="action-buttons">
                                            <button class="btn btn-outline btn-small" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                <i data-feather="edit"></i> Edit
                                            </button>
                                            <form method="POST" style="display: inline; flex: 1;" onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small" style="width: 100%;">
                                                    <i data-feather="trash-2"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Menu Tab -->
            <div class="tab-content <?php echo $activeTab === 'menu' ? 'active' : ''; ?>" id="menu">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Tambah Menu Baru</h2>
                    </div>
                    <div class="admin-card-content">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_menu">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nama Menu</label>
                                    <input type="text" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label>Harga</label>
                                    <input type="number" name="price" required>
                                </div>
                                <div class="form-group">
                                    <label>Stok</label>
                                    <input type="number" name="stock" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label>Gambar Menu</label>
                                    <div class="file-upload" onclick="document.getElementById('add-menu-image').click()">
                                        <input type="file" id="add-menu-image" name="image" accept="image/*">
                                        <div class="file-upload-icon">
                                            <i data-feather="upload" size="24"></i>
                                        </div>
                                        <p>Klik untuk pilih gambar</p>
                                        <div class="file-name" id="add-menu-file-name"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">
                                        <i data-feather="plus"></i> Tambah Menu
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Daftar Menu</h2>
                    </div>
                    <div class="admin-card-content">
                        <!-- Desktop Table View -->
                        <div class="data-table-container hide-mobile">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Gambar</th>
                                        <th>Nama</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($menuItems as $menu): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($menu['image']); ?>" alt="<?php echo htmlspecialchars($menu['name']); ?>">
                                            </td>
                                            <td><?php echo htmlspecialchars($menu['name']); ?></td>
                                            <td>Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="stock-badge <?php echo ($menu['stock'] ?? 0) <= 5 ? 'stock-low' : 'stock-normal'; ?>">
                                                    <?php echo $menu['stock'] ?? 0; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-outline btn-small" onclick="editMenu(<?php echo htmlspecialchars(json_encode($menu)); ?>)">
                                                        <i data-feather="edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus menu ini?')">
                                                        <input type="hidden" name="action" value="delete_menu">
                                                        <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-small">
                                                            <i data-feather="trash-2"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mobile Card View -->
                        <div class="mobile-card-layout show-mobile">
                            <?php foreach ($menuItems as $menu): ?>
                                <div class="mobile-card">
                                    <div class="mobile-card-header">
                                        <div class="mobile-card-title"><?php echo htmlspecialchars($menu['name']); ?></div>
                                        <img src="<?php echo htmlspecialchars($menu['image']); ?>" alt="<?php echo htmlspecialchars($menu['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                    </div>
                                    <div class="mobile-card-content">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Harga:</span>
                                            <span class="mobile-card-value">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Stok:</span>
                                            <span class="mobile-card-value">
                                                <span class="stock-badge <?php echo ($menu['stock'] ?? 0) <= 5 ? 'stock-low' : 'stock-normal'; ?>">
                                                    <?php echo $menu['stock'] ?? 0; ?>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <div class="action-buttons">
                                            <button class="btn btn-outline btn-small" onclick="editMenu(<?php echo htmlspecialchars(json_encode($menu)); ?>)">
                                                <i data-feather="edit"></i> Edit
                                            </button>
                                            <form method="POST" style="display: inline; flex: 1;" onsubmit="return confirm('Yakin ingin menghapus menu ini?')">
                                                <input type="hidden" name="action" value="delete_menu">
                                                <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small" style="width: 100%;">
                                                    <i data-feather="trash-2"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transactions Tab -->
            <div class="tab-content <?php echo $activeTab === 'transactions' ? 'active' : ''; ?>" id="transactions">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Filter Transaksi</h2>
                    </div>
                    <div class="admin-card-content">
                        <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="tab" value="transactions">
                            <select name="transaction_filter" onchange="this.form.submit()" style="padding: 8px 12px; border: 2px solid #e5e7eb; border-radius: 6px;">
                                <option value="all" <?php echo ($_GET['transaction_filter'] ?? 'all') === 'all' ? 'selected' : ''; ?>>Semua Transaksi</option>
                                <option value="1_month" <?php echo ($_GET['transaction_filter'] ?? '') === '1_month' ? 'selected' : ''; ?>>1 Bulan Terakhir</option>
                                <option value="6_months" <?php echo ($_GET['transaction_filter'] ?? '') === '6_months' ? 'selected' : ''; ?>>6 Bulan Terakhir</option>
                                <option value="1_year" <?php echo ($_GET['transaction_filter'] ?? '') === '1_year' ? 'selected' : ''; ?>>1 Tahun Terakhir</option>
                            </select>
                            <?php if (($_GET['transaction_filter'] ?? 'all') !== 'all'): ?>
                                <a href="?tab=transactions" class="btn btn-outline btn-small">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Daftar Transaksi</h2>
                    </div>
                    <div class="admin-card-content">
                        <?php 
                        // Get transaction filter
                        $transactionFilter = $_GET['transaction_filter'] ?? 'all';
                        $transactionDateCondition = '';
                        
                        switch ($transactionFilter) {
                            case '1_month':
                                $transactionDateCondition = " WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                                break;
                            case '6_months':
                                $transactionDateCondition = " WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
                                break;
                            case '1_year':
                                $transactionDateCondition = " WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                                break;
                            case 'all':
                            default:
                                $transactionDateCondition = "";
                                break;
                        }
                        
                        $filteredTransactionsQuery = "
                            SELECT t.*, GROUP_CONCAT(CONCAT(o.item_name, ' (', o.quantity, 'x)') SEPARATOR ', ') as items
                            FROM transactions t
                            LEFT JOIN orders o ON t.id = o.transaction_id" . 
                            $transactionDateCondition . "
                            GROUP BY t.id
                            ORDER BY t.created_at DESC
                        ";
                        $filteredTransactions = $pdo->query($filteredTransactionsQuery)->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php foreach ($filteredTransactions as $transaction): ?>
                            <div class="transaction-card">
                                <div class="transaction-header">
                                    <div class="transaction-info">
                                        <h3><?php echo htmlspecialchars($transaction['customer_name']); ?></h3>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($transaction['customer_email']); ?></p>
                                        <p><strong>HP:</strong> <?php echo htmlspecialchars($transaction['customer_phone']); ?></p>
                                        <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></p>
                                        <p class="transaction-total">Total: Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></p>
                                    </div>
                                        <div class="action-buttons">
                                            <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                                <?php echo $transaction['status'] === 'completed' ? 'Selesai' : 'Diproses'; ?>
                                            </span>

                                            <?php if ($transaction['payment_proof']): ?>
                                                <button class="btn btn-outline btn-small" onclick="viewPaymentProof('<?php echo htmlspecialchars($transaction['payment_proof']); ?>')">
                                                    <i data-feather="image"></i> 
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($transaction['status'] === 'processing'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="complete_transaction">
                                                    <input type="hidden" name="id" value="<?php echo $transaction['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-small">
                                                        <i data-feather="check"></i> Selesai
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                <input type="hidden" name="action" value="delete_transaction">
                                                <input type="hidden" name="id" value="<?php echo $transaction['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
</div>
                                </div>
                                
                                <?php if ($transaction['items']): ?>
                                    <div class="transaction-items">
                                        <h4>Detail Pesanan:</h4>
                                        <p><?php echo htmlspecialchars($transaction['items']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Users Tab -->
            <div class="tab-content <?php echo $activeTab === 'users' ? 'active' : ''; ?>" id="users">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Kelola Pengguna</h2>
                    </div>
                    <div class="admin-card-content">
                        <!-- Desktop Table View -->
                        <div class="data-table-container hide-mobile">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Terdaftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-outline btn-small" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                        <i data-feather="edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Reset password pengguna ini?')">
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-outline btn-small">
                                                            <i data-feather="key"></i>
                                                        </button>
                                                    </form>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-small">
                                                                <i data-feather="trash-2"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mobile Card View -->
                        <div class="mobile-card-layout show-mobile">
                            <?php foreach ($users as $user): ?>
                                <div class="mobile-card">
                                    <div class="mobile-card-header">
                                        <div class="mobile-card-title"><?php echo htmlspecialchars($user['username']); ?></div>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </div>
                                    <div class="mobile-card-content">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Nama:</span>
                                            <span class="mobile-card-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Email:</span>
                                            <span class="mobile-card-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Terdaftar:</span>
                                            <span class="mobile-card-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <div class="action-buttons">
                                            <button class="btn btn-outline btn-small" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i data-feather="edit"></i> Edit
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Reset password pengguna ini?')">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-outline btn-small">
                                                    <i data-feather="key"></i> Reset
                                                </button>
                                            </form>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" style="display: inline; flex: 1;" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-small" style="width: 100%;">
                                                        <i data-feather="trash-2"></i> Hapus
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div class="modal" id="edit-product-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Produk</h3>
                <button class="close-modal" onclick="closeModal('edit-product-modal')">
                    <i data-feather="x"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="id" id="edit-product-id">
                <div class="form-group">
                    <label>Nama Produk</label>
                    <input type="text" name="name" id="edit-product-name" required>
                </div>
                <div class="form-group">
                    <label>Harga</label>
                    <input type="number" name="price" id="edit-product-price" required>
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stock" id="edit-product-stock" min="0" required>
                </div>
                <div class="form-group">
                    <label>Gambar Produk</label>
                    <div class="file-upload" onclick="document.getElementById('edit-product-image').click()">
                        <input type="file" id="edit-product-image" name="image" accept="image/*">
                        <div class="file-upload-icon">
                            <i data-feather="upload" size="24"></i>
                        </div>
                        <p>Klik untuk ganti gambar (opsional)</p>
                        <div class="file-name" id="edit-product-file-name"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" id="edit-product-description" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Menu Modal -->
    <div class="modal" id="edit-menu-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Menu</h3>
                <button class="close-modal" onclick="closeModal('edit-menu-modal')">
                    <i data-feather="x"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_menu">
                <input type="hidden" name="id" id="edit-menu-id">
                <div class="form-group">
                    <label>Nama Menu</label>
                    <input type="text" name="name" id="edit-menu-name" required>
                </div>
                <div class="form-group">
                    <label>Harga</label>
                    <input type="number" name="price" id="edit-menu-price" required>
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stock" id="edit-menu-stock" min="0" required>
                </div>
                <div class="form-group">
                    <label>Gambar Menu</label>
                    <div class="file-upload" onclick="document.getElementById('edit-menu-image').click()">
                        <input type="file" id="edit-menu-image" name="image" accept="image/*">
                        <div class="file-upload-icon">
                            <i data-feather="upload" size="24"></i>
                        </div>
                        <p>Klik untuk ganti gambar (opsional)</p>
                        <div class="file-name" id="edit-menu-file-name"></div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Payment Proof Modal -->
    <div class="modal" id="payment-proof-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Bukti Pembayaran</h3>
                <button class="close-modal" onclick="closeModal('payment-proof-modal')">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div style="text-align: center;">
                <img id="payment-proof-image" src="/placeholder.svg" alt="Bukti Pembayaran" style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal" id="edit-user-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Pengguna</h3>
                <button class="close-modal" onclick="closeModal('edit-user-modal')">
                    <i data-feather="x"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" id="edit-user-id">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit-user-username" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" id="edit-user-fullname" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit-user-email" required>
                </div>
                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="text" name="phone" id="edit-user-phone">
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="address" id="edit-user-address" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit-user-role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Initialize charts data from PHP
        const salesData = <?php echo json_encode($dashboardStats['sales_by_month']); ?>;
        const productTypeData = <?php echo json_encode($dashboardStats['product_types']); ?>;
        
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
            
            // Update URL
            window.history.pushState({}, '', `?tab=${tabName}`);
            
            // Initialize charts if dashboard tab is selected
            if (tabName === 'dashboard') {
                setTimeout(initializeCharts, 100);
            }
        }
        
        function initializeCharts() {
            // Sales Chart
            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: salesData.map(item => {
                            const date = new Date(item.month + '-01');
                            return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short' });
                        }).reverse(),
                        datasets: [{
                            label: 'Pendapatan',
                            data: salesData.map(item => item.revenue).reverse(),
                            borderColor: '#d97706',
                            backgroundColor: 'rgba(217, 119, 6, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Category Chart
            const categoryCtx = document.getElementById('categoryChart');
            if (categoryCtx) {
                new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: productTypeData.map(item => item.item_type === 'menu' ? 'Menu' : 'Produk'),
                        datasets: [{
                            data: productTypeData.map(item => item.total_revenue),
                            backgroundColor: ['#d97706', '#10b981']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }
        
        function editProduct(product) {
            document.getElementById('edit-product-id').value = product.id;
            document.getElementById('edit-product-name').value = product.name;
            document.getElementById('edit-product-price').value = product.price;
            document.getElementById('edit-product-stock').value = product.stock || 0;
            document.getElementById('edit-product-description').value = product.description;
            
            document.getElementById('edit-product-modal').classList.add('active');
        }
        
        function editMenu(menu) {
            document.getElementById('edit-menu-id').value = menu.id;
            document.getElementById('edit-menu-name').value = menu.name;
            document.getElementById('edit-menu-price').value = menu.price;
            document.getElementById('edit-menu-stock').value = menu.stock || 0;
            
            document.getElementById('edit-menu-modal').classList.add('active');
        }
        
        function editUser(user) {
            document.getElementById('edit-user-id').value = user.id;
            document.getElementById('edit-user-username').value = user.username;
            document.getElementById('edit-user-fullname').value = user.full_name;
            document.getElementById('edit-user-email').value = user.email;
            document.getElementById('edit-user-phone').value = user.phone || '';
            document.getElementById('edit-user-address').value = user.address || '';
            document.getElementById('edit-user-role').value = user.role;
            
            document.getElementById('edit-user-modal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function viewPaymentProof(imagePath) {
            document.getElementById('payment-proof-image').src = imagePath;
            document.getElementById('payment-proof-modal').classList.add('active');
        }
        
        // File upload handlers
        document.getElementById('add-product-image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            document.getElementById('add-product-file-name').textContent = fileName ? `File: ${fileName}` : '';
        });
        
        document.getElementById('add-menu-image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            document.getElementById('add-menu-file-name').textContent = fileName ? `File: ${fileName}` : '';
        });
        
        document.getElementById('edit-product-image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            document.getElementById('edit-product-file-name').textContent = fileName ? `File: ${fileName}` : '';
        });
        
        document.getElementById('edit-menu-image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            document.getElementById('edit-menu-file-name').textContent = fileName ? `File: ${fileName}` : '';
        });
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();
            
            // Initialize charts if dashboard is active
            if (document.getElementById('dashboard').classList.contains('active')) {
                setTimeout(initializeCharts, 100);
            }
        });
    </script>
</body>
</html>
