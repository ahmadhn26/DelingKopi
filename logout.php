<?php
session_start();

//Clear user cart from database if logged in
if (isset($_SESSION['user_id'])) {
    require_once 'config/db.php';
    
    try {
        $stmt = $pdo->prepare("DELETE FROM user_carts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // Log error but continue with logout
        error_log("Error clearing user cart: " . $e->getMessage());
    }
}

session_destroy();

// Clear cart when logging out
echo '<script>
    localStorage.removeItem("cart");
    window.location.href = "index.php";
</script>';
exit;
?>
