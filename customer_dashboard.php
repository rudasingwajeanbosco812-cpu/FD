<?php
include 'db.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard</title>
    <style>
        body { font-family: Arial; background:#f4f4f4; }
        .container { width:90%; margin:auto; }
        .card {
            display:inline-block;
            width:250px;
            padding:15px;
            margin:10px;
            background:white;
            border-radius:10px;
            box-shadow:0 0 10px #ccc;
            text-align:center;
        }
        button {
            padding:10px;
            margin-top:10px;
            cursor:pointer;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🍔 Food Menu</h1>

    <?php
    $foods = $conn->query("SELECT * FROM foods");

    while($f = $foods->fetch()){
    ?>
        <div class="card">
            <h3><?php echo $f['name']; ?></h3>
            <p>Price: <?php echo $f['price']; ?></p>

            <form method="POST">
                <input type="hidden" name="food_id" value="<?php echo $f['id']; ?>">
                
                <input type="text" name="customer_name" placeholder="Your Name" required><br><br>
                <input type="text" name="phone" placeholder="Phone" required><br><br>
                <input type="number" name="quantity" placeholder="Qty" required><br><br>

                <button name="order">Order</button>
            </form>
        </div>
    <?php } ?>

    <?php
    // PLACE ORDER
    if(isset($_POST['order'])){
        $food_id = $_POST['food_id'];
        $name = $_POST['customer_name'];
        $phone = $_POST['phone'];
        $quantity = $_POST['quantity'];

        $stmt = $conn->prepare("
            INSERT INTO orders (food_id, quantity, customer_name, customer_phone)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$food_id, $quantity, $name, $phone]);

        echo "<p>✅ Order placed successfully!</p>";
    }
    ?>

</div>

</body>
</html>