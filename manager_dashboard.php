<?php
session_start();
include 'db.php';

// 🔒 1. Mutekano
if(!isset($_SESSION['user'])){
    header("Location: index.php?page=login");
    exit();
}

$stmt = $conn->prepare("SELECT role FROM users WHERE email=?");
$stmt->execute([$_SESSION['user']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user['role'] != 'manager'){
    echo "<div style='color:red; text-align:center; padding:50px;'>Access denied! You are not a manager.</div>";
    exit();
}

// Stats
$totalFoods = $conn->query("SELECT COUNT(*) FROM foods")->fetchColumn();
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// ✅ 2. UPDATE ORDER STATUS
if(isset($_POST['update_status'])){
    $status = $_POST['status'];
    $id = $_POST['id'];
    $conn->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
    header("Location: manager.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | FoodieExpress</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #4361ee;
            --bg-color: #f8f9fa;
            --dark-card: #ffffff;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: #2b2d42;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #1a1c23;
            color: white;
            position: fixed;
            padding: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px;
        }

        /* --- CARDS --- */
        .stat-card {
            background: white;
            border: none;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
        }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }

        /* --- TABLES --- */
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-top: 30px;
        }

        .table {
            vertical-align: middle;
        }

        .status-select {
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 5px 10px;
        }

        .badge-pending { background: #fff3cd; color: #856404; border-radius: 50px; padding: 5px 12px; font-size: 11px; }
        .badge-approved { background: #d1e7dd; color: #0f5132; border-radius: 50px; padding: 5px 12px; font-size: 11px; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="sidebar d-flex flex-column">
    <h4 class="fw-bold mb-5"><i class="fas fa-hamburger me-2 text-warning"></i> Manager</h4>
    <nav class="nav flex-column gap-2">
        <a href="#" class="nav-link text-white active bg-primary rounded-3 mb-2"><i class="fas fa-th-large me-2"></i> Dashboard</a>
        <a href="index.php" class="nav-link text-white-50"><i class="fas fa-home me-2"></i> View Site</a>
        <hr class="text-white-50">
        <a href="logout.php" class="nav-link text-danger mt-auto"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0 text-dark">Work Overview</h3>
        <span class="text-muted small">Welcome, <strong><?php echo $_SESSION['user']; ?></strong></span>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h3 class="fw-bold m-0"><?php echo $totalFoods; ?></h3>
                    <p class="text-muted m-0 small">Active Menu Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card text-end">
                <div class="icon-box bg-success bg-opacity-10 text-success ms-auto order-last">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="me-3">
                    <h3 class="fw-bold m-0"><?php echo $totalOrders; ?></h3>
                    <p class="text-muted m-0 small">Total Orders Handled</p>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold m-0">🛒 Incoming Orders</h5>
            <?php if(isset($_GET['success'])) echo "<span class='badge bg-success py-2'>Updated Successfully!</span>"; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover border-0">
                <thead class="table-light">
                    <tr>
                        <th class="border-0">Customer</th>
                        <th class="border-0">Food Name</th>
                        <th class="border-0">Qty</th>
                        <th class="border-0 text-center">Status Control</th>
                        <th class="border-0 text-end">Action</th>
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
                    while($o = $orders->fetch()): ?>
                    <tr>
                        <form method="POST">
                            <td class="fw-600"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($o['food']); ?></td>
                            <td><span class="badge bg-light text-dark px-3"><?php echo $o['quantity']; ?></span></td>
                            <td style="min-width: 150px;">
                                <select name="status" class="form-select status-select <?php echo ($o['status']=='pending') ? 'border-warning' : 'border-success'; ?>">
                                    <option value="pending" <?php if($o['status']=='pending') echo 'selected'; ?>>🟡 Pending</option>
                                    <option value="approved" <?php if($o['status']=='approved') echo 'selected'; ?>>🟢 Approved</option>
                                    <option value="delivered" <?php if($o['status']=='delivered') echo 'selected'; ?>>📦 Delivered</option>
                                </select>
                            </td>
                            <td class="text-end">
                                <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                <button class="btn btn-dark btn-sm rounded-pill px-4" name="update_status">Save</button>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-container bg-light border">
        <h6 class="fw-bold mb-3"><i class="fas fa-list me-2"></i> Current Menu (Read-Only)</h6>
        <div class="row g-2">
            <?php
            $foods = $conn->query("SELECT * FROM foods");
            while($row = $foods->fetch()){
                echo "<div class='col-md-3'><div class='bg-white p-2 rounded shadow-sm border small text-center'><b>{$row['name']}</b><br><span class='text-primary'>{$row['price']} RWF</span></div></div>";
            }
            ?>
        </div>
    </div>

</div>

</body>
</html>