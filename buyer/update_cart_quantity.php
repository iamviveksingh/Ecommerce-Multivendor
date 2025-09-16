<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    echo json_encode(['success' => false, 'message' => 'Please login as a buyer']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    // Validate quantity
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
        exit();
    }
    
    // Check product stock
    $product_query = "SELECT stock FROM products WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
        exit();
    }
    
    if ($quantity > $product['stock']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit();
    }
    
    // Update cart quantity
    $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iii", $quantity, $user_id, $product_id);
    
    if ($stmt->execute()) {
        // Get updated cart item for response
        $cart_query = "SELECT c.*, p.name, p.price, p.stock FROM cart c 
                      JOIN products p ON c.product_id = p.id 
                      WHERE c.user_id = ? AND c.product_id = ?";
        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $cart_item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($cart_item) {
            $total = $cart_item['price'] * $quantity;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Quantity updated successfully!',
                'quantity' => $quantity,
                'total' => number_format($total, 2)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating quantity: ' . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
