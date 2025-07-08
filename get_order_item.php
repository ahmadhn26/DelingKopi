<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$orderId = $_GET['order_id'] ?? 0;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID required']);
    exit;
}

// Verify that this order belongs to the logged-in user
$stmt = $pdo->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);

if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("SELECT * FROM orders WHERE transaction_id = ? ORDER BY id");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['items' => $items]);
?>
