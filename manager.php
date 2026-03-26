<?php
session_start();
include 'db.php';

// 🔒 1. Mutekano: Niba atari Manager, musubize kuri Login
if(!isset($_SESSION['user'])){
    header("Location: index.php?page=login");
    exit();
}

$stmt = $conn->prepare("SELECT role FROM users WHERE email=?");
$stmt->execute([$_SESSION['user']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user['role'] != 'manager'){
    echo "<div class='alert alert-danger'>Access denied! You are not a manager.</div>";
    exit();
}

// 📊 Stats (Byakuwe muri SQL)
$totalFoods = $conn->query("SELECT COUNT(*) FROM foods")->fetchColumn();
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// ✅ 2. UPDATE ORDER STATUS (Uburenganzira bwonyine Manager afite)
if(isset($_POST['update_status'])){
    $status = $_POST['status'];
    $id = $_POST['id'];

    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);

    header("Location: manager.php?msg=Status Updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard | FoodieExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .card-stats { border: none; border-radius: 15px; transition: 0.3s; }
        .card-stats:hover { transform: translateY(-5px); }
        .table-container { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .status-select { border-radius: 8px; font-size: 0.9rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand fw-bold">🍔 MANAGER <span class="text-warning">PANEL</span></span>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 small"><?php echo $_SESSION['user']; ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <div class="row g-4 mb-5 text-center">
        <div class="col-md-6">
            <div class="card card-stats p-4 shadow-sm bg-primary text-white">
                <h2 class="fw-bold mb-0"><?php echo $totalFoods; ?></h2>
                <p class="mb-0">Available Foods on Menu</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-stats p-4 shadow-sm bg-success text-white">
                <h2 class="fw-bold mb-0"><?php echo $totalOrders; ?></h2>
                <p class="mb-0">Total Orders Received</p>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h4 class="fw-bold mb-4 text-dark">🛒 Manage Customer Orders</h4>
        
        <?php if(isset($_GET['msg'])) echo "<div class='alert alert-success py-2'>".htmlspecialchars($_GET['msg'])."</div>"; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Food Item</th>
                        <th>Quantity</th>
                        <th>Status Control</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orders = $conn->query("
                        SELECT orders.id, orders.customer_name, foods.name AS food, orders.quantity, orders.status
                        FROM orders
                        JOIN foods ON orders.food_id = foods.id
                        ORDER BY orders.id DESC
                    ");

                    while($o = $orders->fetch()):
                    ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                            <td class="fw-bold"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($o['food']); ?></td>
                            <td><span class="badge bg-secondary rounded-pill"><?php echo $o['quantity']; ?></span></td>
                            <td>
                                <select name="status" class="form-select status-select">
                                    <option value="pending" <?php if($o['status']=='pending') echo 'selected'; ?>>🟡 Pending</option>
                                    <option value="approved" <?php if($o['status']=='approved') echo 'selected'; ?>>🔵 Approved</option>
                                    <option value="delivered" <?php if($o['status']=='delivered') echo 'selected'; ?>>🟢 Delivered</option>
                                    <option value="cancelled" <?php if($o['status']=='cancelled') echo 'selected'; ?>>🔴 Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <button name="update_status" class="btn btn-dark btn-sm rounded-3 shadow-sm px-3">Update</button>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 mb-5 p-4 border rounded bg-light">
        <h5 class="fw-bold mb-3"><i class="fas fa-info-circle"></i> Current Menu (View Only)</h5>
        <p class="text-muted small">Manager can only view foods. Contact Admin to add or edit the menu.</p>
        <div class="row g-2">
            <?php
            $foods = $conn->query("SELECT * FROM foods");
            while($row = $foods->fetch()){
                echo "<div class='col-md-4'><div class='p-2 border bg-white rounded small'>• {$row['name']} - <b>{$row['price']} RWF</b></div></div>";
            }
            ?>
        </div>
    </div>

</div>

<footer class="text-center py-4 text-muted small">&copy; 2026 FoodieExpress Manager Dashboard</footer>

</body>
</html>