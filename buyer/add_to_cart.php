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
        echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
        exit();
    }
    
    // Check if product exists and is in stock
    $product_query = "SELECT * FROM products WHERE id = ? AND status = 'active'";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or inactive']);
        exit();
    }
    
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit();
    }
    
    // Check if product is already in cart
    $cart_check = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($cart_check);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $existing_item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existing_item) {
        // Update existing cart item
        $new_quantity = $existing_item['quantity'] + $quantity;
        if ($new_quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit();
        }
        
        $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cart updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating cart']);
        }
        $stmt->close();
    } else {
        // Add new item to cart
        $insert_query = "INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $product['price']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product added to cart successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
        }
        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
