<?php
session_start();

// --- 1. DATABASE CONNECTION ---
$host = "localhost";
$dbname = "fd";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// --- 2. PROCESSING THE ORDER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $c_name = $_POST['customer_name'];
    $c_phone = $_POST['customer_phone'];
    $food_id = $_POST['food_id'];
    $payment = $_POST['payment_method'];
    $status = "Pending";

    $sql = "INSERT INTO orders (customer_name, customer_phone, food_id, payment_method, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$c_name, $c_phone, $food_id, $payment, $status])) {
        $success_msg = "Order yawe yakiriwe neza! Turakuvugisha vuba.";
    }
}

// --- 3. GET FOODS ---
$foods = $conn->query("SELECT * FROM foods ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rudasingwa's | Order Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .food-card { border: none; border-radius: 15px; transition: 0.3s; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: white; }
        .food-card:hover { transform: translateY(-8px); }
        .food-img { height: 200px; object-fit: cover; width: 100%; }
        .btn-order { background: #2563eb; color: white; border-radius: 10px; padding: 10px; font-weight: bold; border: none; }
        .bank-info { background: #e0f2fe; border-left: 5px solid #0369a1; padding: 10px; margin-top: 10px; border-radius: 5px; display: none; }
        /* Style nshya ya Login Dropdown */
        .login-btn { font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand fw-bold" href="#">🍴 RUDASINGWA'S FAST FOOD</a>
        
        <div class="dropdown">
            <button class="btn btn-warning btn-sm px-4 fw-bold dropdown-toggle login-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                LOGIN
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                <li><h6 class="dropdown-header">Login as:</h6></li>
                <li><a class="dropdown-item py-2" href="login.php?role=admin"><i class="fas fa-user-shield me-2 text-primary"></i> Admin</a></li>
                <li><a class="dropdown-item py-2" href="login.php?role=manager"><i class="fas fa-user-tie me-2 text-success"></i> Manager</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <?php if(isset($success_msg)): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 text-center fw-bold">✅ <?= $success_msg ?></div>
    <?php endif; ?>

    <h3 class="fw-bold mb-4 text-dark"><i class="fas fa-fire text-danger me-2"></i> Our Special Menu</h3>
    <div class="row g-4">
        <?php foreach($foods as $f): ?>
        <div class="col-md-3 col-sm-6">
            <div class="food-card h-100 p-2">
                <img src="uploads/<?= $f['image'] ?>" class="food-img rounded-3" onerror="this.src='https://via.placeholder.com/300x200?text=Food+Image'">
                <div class="p-3 text-center">
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($f['name']) ?></h5>
                    <p class="text-primary fw-bold mb-3"><?= number_format($f['price']) ?> RWF</p>
                    <button class="btn btn-order w-100" data-bs-toggle="modal" data-bs-target="#orderModal<?= $f['id'] ?>">
                        Order Now
                    </button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="orderModal<?= $f['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Complete Your Order</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body p-4">
                            <h6 class="mb-3">Item: <span class="text-primary fw-bold"><?= $f['name'] ?></span></h6>
                            <input type="hidden" name="food_id" value="<?= $f['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="small fw-bold">Your Full Name</label>
                                <input type="text" name="customer_name" class="form-control" placeholder="Names" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="small fw-bold">Phone Number</label>
                                <input type="text" name="customer_phone" class="form-control" placeholder="07..." required>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold">Payment Method</label>
                                <select name="payment_method" class="form-select" onchange="toggleBank(this, <?= $f['id'] ?>)" required>
                                    <option value="MoMo">MTN Mobile Money</option>
                                    <option value="BK Bank Transfer">BK Bank Transfer</option>
                                </select>
                            </div>

                            <div id="bankInfo<?= $f['id'] ?>" class="bank-info">
                                <small class="fw-bold text-dark"><i class="fas fa-university"></i> Bank of Kigali (BK)</small><br>
                                <small>Account Name: <strong>Rudasingwa Business</strong></small><br>
                                <small>Account Number: <strong class="text-danger">10014867555</strong></small>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="submit" name="place_order" class="btn btn-primary w-100 fw-bold">Confirm & Place Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function toggleBank(select, foodId) {
        var info = document.getElementById('bankInfo' + foodId);
        if (select.value === 'BK Bank Transfer') {
            info.style.display = 'block';
        } else {
            info.style.display = 'none';
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
