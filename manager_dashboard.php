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

// --- 2. AUTHENTICATION ---
if(!isset($_SESSION['user'])){
    header("Location: index.php?page=login");
    exit();
}

// --- 3. FORM PROCESSING (ADD FOOD & UPDATE STATUS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. ADD NEW FOOD
    if(isset($_POST['add_food'])){
        $name = $_POST['name'];
        $price = $_POST['price'];
        $image = $_FILES['food_image']['name'];
        $folder = "uploads/";

        if (!is_dir($folder)) { mkdir($folder, 0777, true); }

        if (!empty($image)) {
            $new_image_name = time() . "_" . basename($image);
            move_uploaded_file($_FILES['food_image']['tmp_name'], $folder . $new_image_name);
            $stmt = $conn->prepare("INSERT INTO foods (name, price, image) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $new_image_name]);
        } else {
            $stmt = $conn->prepare("INSERT INTO foods (name, price) VALUES (?, ?)");
            $stmt->execute([$name, $price]);
        }
    }

    // B. UPDATE STATUS
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
    }

    header("Location: manager_dashboard.php");
    exit();
}

// --- 4. DATA FETCHING ---
// Stats
$activeFoods = $conn->query("SELECT COUNT(*) FROM foods")->fetchColumn();
$totalOrdersCount = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Fetch ALL Orders (using LEFT JOIN so they appear even if food is missing)
$ordersQuery = "SELECT orders.*, foods.name AS food_name, foods.price AS food_price 
                FROM orders 
                LEFT JOIN foods ON orders.food_id = foods.id 
                ORDER BY orders.id DESC";
$orders = $conn->query($ordersQuery)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard | Fixed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 250px; height: 100vh; background: #1a1c2e; color: white; position: fixed; }
        .main-content { margin-left: 250px; padding: 2rem; }
        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; }
        .nav-link { color: rgba(255,255,255,0.7); }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-radius: 10px; }
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column p-3">
    <h3 class="mb-4 text-center fw-bold text-warning"><i class="fas fa-user-shield"></i> Manager</h3>
    <nav class="nav flex-column gap-2">
        <a href="#dashboard" class="nav-link active p-2"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="#add_food_sec" class="nav-link p-2"><i class="fas fa-plus-circle me-2"></i> Add Food</a>
        <a href="index.php" class="nav-link p-2"><i class="fas fa-eye me-2"></i> View Site</a>
    </nav>
    <div class="mt-auto">
        <a href="logout.php" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a>
    </div>
</div>

<div class="main-content">
    <h2 class="fw-bold mb-4">Manager Workspace</h2>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card-custom d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3 text-primary"><i class="fas fa-utensils fa-2x"></i></div>
                <div><h3 class="mb-0 fw-bold"><?= $activeFoods ?></h3><small class="text-muted">Active Menu Items</small></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-custom d-flex align-items-center">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3 text-success"><i class="fas fa-shopping-cart fa-2x"></i></div>
                <div><h3 class="mb-0 fw-bold"><?= $totalOrdersCount ?></h3><small class="text-muted">Total Orders</small></div>
            </div>
        </div>
    </div>

    <div class="card-custom" id="add_food_sec">
        <h5 class="fw-bold mb-3 text-success">Add New Food Item</h5>
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Food Name" required></div>
            <div class="col-md-3"><input type="number" name="price" class="form-control" placeholder="Price (RWF)" required></div>
            <div class="col-md-3"><input type="file" name="food_image" class="form-control" accept="image/*"></div>
            <div class="col-md-2"><button type="submit" name="add_food" class="btn btn-success w-100">Add Item</button></div>
        </form>
    </div>

    <div class="card-custom">
        <h5 class="fw-bold mb-4">Incoming Orders</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Order Details</th>
                        <th>Price</th>
                        <th>Status Control</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($orders) > 0): ?>
                        <?php foreach ($orders as $row): ?>
                        <tr>
                            <td><div class="fw-bold"><?= htmlspecialchars($row['customer_name']) ?></div></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($row['food_name'] ?? 'DELETED ITEM') ?>
                                </span>
                            </td>
                            <td class="fw-bold text-primary"><?= number_format($row['food_price'] ?? 0) ?> RWF</td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                    <select name="new_status" class="form-select form-select-sm" style="width: 130px;">
                                        <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Delivered" <?= $row['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="Cancelled" <?= $row['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                            </td>
                            <td class="text-end">
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary px-3">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">No orders in database.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
