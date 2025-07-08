<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'save_cart':
        $cartData = json_decode($_POST['cart_data'], true);
        
        if (!$cartData) {
            echo json_encode(['success' => false, 'error' => 'Invalid cart data']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Clear existing cart
            $stmt = $pdo->prepare("DELETE FROM user_carts WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Save new cart items with stock validation
            $stmt = $pdo->prepare("INSERT INTO user_carts (user_id, item_id, item_name, item_type, price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($cartData as $item) {
                // Check stock availability
                $table = $item['type'] === 'menu' ? 'menukami' : 'products';
                $stockStmt = $pdo->prepare("SELECT stock FROM $table WHERE id = ?");
                $stockStmt->execute([$item['id']]);
                $stockData = $stockStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$stockData || ($stockData['stock'] ?? 0) < $item['quantity']) {
                    throw new Exception("Stok tidak mencukupi untuk {$item['name']}. Stok tersedia: " . ($stockData['stock'] ?? 0));
                }
                
                $stmt->execute([
                    $userId,
                    $item['id'],
                    $item['name'],
                    $item['type'],
                    $item['price'],
                    $item['quantity']
                ]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'load_cart':
        try {
            $stmt = $pdo->prepare("SELECT item_id as id, item_name as name, item_type as type, price, quantity FROM user_carts WHERE user_id = ?");
            $stmt->execute([$userId]);
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'cart' => $cartItems]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'clear_cart':
        try {
            $stmt = $pdo->prepare("DELETE FROM user_carts WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
