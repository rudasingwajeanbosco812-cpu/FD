<?php
$orderMessage = "";

if(isset($_POST['order'])){

    $food_id = $_POST['food_id'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $quantity = $_POST['quantity'];

    // ✅ validation
    if(empty($name) || empty($phone) || empty($quantity)){
        $orderMessage = "<div class='alert alert-danger'>❌ All fields are required!</div>";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO orders (food_id, quantity, customer_name, customer_phone)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$food_id, $quantity, $name, $phone]);

        $orderMessage = "<div class='alert alert-success'>✅ Order placed successfully!</div>";
    }
}
?>