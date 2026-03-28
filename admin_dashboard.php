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

// --- 2. AUTHENTICATION (Admin Only) ---
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// --- 3. ADMIN ACTIONS (Status Update, Edit Food, Delete) ---

// A. Guhindura Status y'ikiryo (Icyo wifuje ubu)
if(isset($_POST['update_status'])){
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['new_status'], $_POST['order_id']]);
    header("Location: admin_dashboard.php#orders");
}

// B. Guhindura Ibiryo (Edit Food)
if(isset($_POST['edit_food'])){
    $stmt = $conn->prepare("UPDATE foods SET name = ?, price = ? WHERE id = ?");
    $stmt->execute([$_POST['food_name'], $_POST['food_price'], $_POST['food_id']]);
}

// C. Gusiba
if(isset($_GET['delete_user'])){
    $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$_GET['delete_user']]);
    header("Location: admin_dashboard.php");
}

// --- 4. DATA FETCHING ---
$users = $conn->query("SELECT * FROM users WHERE role = 'manager'")->fetchAll(PDO::FETCH_ASSOC);
$foods = $conn->query("SELECT * FROM foods ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$orders = $conn->query("SELECT orders.*, foods.name as f_name FROM orders LEFT JOIN foods ON orders.food_id = foods.id ORDER BY orders.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$total_foods = count($foods);
$total_orders = count($orders);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin SuperPanel | Status Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 250px; height: 100vh; background: #1a1c2e; color: white; position: fixed; padding: 20px; }
        .main-content { margin-left: 250px; padding: 30px; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: none; }
        .nav-link { color: #cbd5e0; border-radius: 8px; margin-bottom: 5px; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: white; }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-warning fw-bold mb-5"><i class="fas fa-user-shield"></i> SUPER ADMIN</h4>
    <nav class="nav flex-column">
        <a class="nav-link active" href="#overview"><i class="fas fa-chart-bar me-2"></i> Work Overview</a>
        <a class="nav-link" href="#orders"><i class="fas fa-shopping-cart me-2"></i> Incoming Orders</a>
        <a class="nav-link" href="#menu"><i class="fas fa-utensils me-2"></i> Current Menu</a>
        <a class="nav-link" href="#staff"><i class="fas fa-users-cog me-2"></i> Staff Management</a>
        <hr class="text-secondary">
        <a class="nav-link text-danger" href="logout.php"><i class="fas fa-power-off me-2"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    
    <div class="row g-4 mb-5" id="overview">
        <div class="col-md-6">
            <div class="stat-card d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3 text-primary"><i class="fas fa-hamburger fa-2x"></i></div>
                <div><h2 class="mb-0 fw-bold"><?= $total_foods ?></h2><p class="text-muted mb-0">Active Menu Items</p></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card d-flex align-items-center">
                <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3 text-success"><i class="fas fa-check-circle fa-2x"></i></div>
                <div><h2 class="mb-0 fw-bold"><?= $total_orders ?></h2><p class="text-muted mb-0">Total Orders Handled</p></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-5" id="orders">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4">Incoming Orders (Status Control)</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th>Customer</th>
                            <th>Food Name</th>
                            <th>Status Control</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $o): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($o['customer_name']) ?></strong><br>
                                <small class="text-muted"><?= $o['customer_phone'] ?></small>
                            </td>
                            <td><?= $o['f_name'] ?></td>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <td>
                                    <select name="new_status" class="form-select form-select-sm w-auto">
                                        <option value="Pending" <?= ($o['status']=='Pending')?'selected':'' ?>>Pending</option>
                                        <option value="Delivered" <?= ($o['status']=='Delivered')?'selected':'' ?>>Delivered</option>
                                        <option value="Cancelled" <?= ($o['status']=='Cancelled')?'selected':'' ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary px-3">Update</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-5" id="menu">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4">Current Menu (Read/Write)</h5>
            <div class="row g-3">
                <?php foreach($foods as $f): ?>
                <div class="col-md-4">
                    <div class="p-3 border rounded shadow-sm">
                        <form method="POST">
                            <input type="hidden" name="food_id" value="<?= $f['id'] ?>">
                            <input type="text" name="food_name" class="form-control form-control-sm mb-2 fw-bold" value="<?= $f['name'] ?>">
                            <input type="number" name="food_price" class="form-control form-control-sm mb-2 text-primary" value="<?= $f['price'] ?>">
                            <button type="submit" name="edit_food" class="btn btn-sm btn-outline-primary w-100 mb-2">Save Changes</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" id="staff">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4">Manage Managers</h5>
            <table class="table">
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td><?= $u['email'] ?></td>
                        <td class="text-end"><a href="?delete_user=<?= $u['id'] ?>" class="btn btn-sm btn-danger">Remove</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
