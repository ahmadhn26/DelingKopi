<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['suggestions' => []]);
    exit;
}

try {
    $suggestions = [];
    
    // Search in menu items
    $menuStmt = $pdo->prepare("
        SELECT 'menu' as type, id, name, price, image 
        FROM menukami 
        WHERE name LIKE ? 
        ORDER BY name ASC 
        LIMIT 5
    ");
    $menuStmt->execute(['%' . $query . '%']);
    $menuResults = $menuStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search in products
// Search in products (Optimized)
    $productStmt = $pdo->prepare("
        SELECT 'product' as type, id, name, price, image
        FROM products 
        WHERE name LIKE ? 
        ORDER BY name ASC 
        LIMIT 5
    ");
    // Karena hanya mencari berdasarkan nama, kita hanya butuh satu parameter
    $productStmt->execute(['%' . $query . '%']);
    $productResults = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine results
    $allResults = array_merge($menuResults, $productResults);
    
    // Sort by relevance (exact matches first, then partial matches)
    usort($allResults, function($a, $b) use ($query) {
        $aExact = stripos($a['name'], $query) === 0 ? 1 : 0;
        $bExact = stripos($b['name'], $query) === 0 ? 1 : 0;
        
        if ($aExact !== $bExact) {
            return $bExact - $aExact; // Exact matches first
        }
        
        return strcasecmp($a['name'], $b['name']);
    });
    
    // Limit total results
    $suggestions = array_slice($allResults, 0, 8);
    
    echo json_encode([
        'suggestions' => $suggestions,
        'query' => $query
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'suggestions' => [],
        'error' => 'Search error occurred'
    ]);
}
?>
