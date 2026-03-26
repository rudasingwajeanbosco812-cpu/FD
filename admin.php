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

// --- 2. RESTRICT ACCESS (Only Admin) ---
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'admin'){
    header("Location: index.php?page=login");
    exit();
}

// --- 3. ALL ACTIONS ---
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- A. NEW USER REGISTRATION (Icyo wongereyeho) ---
    if(isset($_POST['add_user'])){
        $name = $_POST['user_name'];
        $email = $_POST['user_email'];
        $pass = password_hash($_POST['user_password'], PASSWORD_DEFAULT);
        $role = $_POST['user_role'];

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        if($stmt->execute([$name, $email, $pass, $role])) {
            $msg = "New user account created!";
        }
    }

    // B. ORDER MANAGEMENT
    if(isset($_POST['update_status'])){
        $conn->prepare("UPDATE orders SET status=? WHERE id=?")
             ->execute([$_POST['status'], $_POST['order_id']]);
        $msg = "Order status updated!";
    }

    // C. FOOD MENU MANAGEMENT
    if(isset($_POST['add_food'])){
        $conn->prepare("INSERT INTO foods (name, price) VALUES (?, ?)")
             ->execute([$_POST['name'], $_POST['price']]);
        $msg = "New food added!";
    }
    if(isset($_POST['delete_food'])){
        $conn->prepare("DELETE FROM foods WHERE id=?")->execute([$_POST['food_id']]);
        $msg = "Food item removed!";
    }

    // D. USER MANAGEMENT
    if(isset($_POST['update_user_role'])){
        $conn->prepare("UPDATE users SET role=? WHERE id=?")
             ->execute([$_POST['role'], $_POST['user_id']]);
        $msg = "User role updated!";
    }
    if(isset($_POST['delete_user'])){
        $conn->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['user_id']]);
        $msg = "User account deleted!";
    }
}

// --- 4. FETCH ALL DATA ---
$orders = $conn->query("SELECT orders.*, foods.name as food_name FROM orders JOIN foods ON orders.food_id = foods.id ORDER BY orders.id DESC");
$users = $conn->query("SELECT * FROM users");
$foods = $conn->query("SELECT * FROM foods");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | Rudasingwa's</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        .sidebar { height: 100vh; width: 250px; position: fixed; background: #0f172a; color: white; padding: 20px; }
        .content { margin-left: 250px; padding: 30px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .nav-link { color: #94a3b8; margin-bottom: 10px; border-radius: 10px; }
        .nav-link:hover, .nav-link.active { background: #3b82f6; color: white; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 class="fw-bold text-info mb-4">ADMIN PRO</h3>
    <nav class="nav flex-column">
        <a class="nav-link active" href="#"><i class="fas fa-home me-2"></i> Overview</a>
        <a class="nav-link" href="#orders"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
        <a class="nav-link" href="#menu"><i class="fas fa-utensils me-2"></i> Food Menu</a>
        <a class="nav-link" href="#users"><i class="fas fa-user-shield me-2"></i> Staff/Users</a>
        <a class="nav-link text-danger mt-5" href="logout.php"><i class="fas fa-power-off me-2"></i> Logout</a>
    </nav>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">System Dashboard</h2>
        <?php if($msg) echo "<div class='alert alert-success py-1 px-3 shadow-sm'>$msg</div>"; ?>
    </div>

    <div class="card" id="orders">
        <div class="card-header bg-white fw-bold">Recent Customer Orders</div>
        <div class="card-body">
            <table class="table align-middle">
                <thead>
                    <tr><th>Customer</th><th>Phone</th><th>Order</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while($o = $orders->fetch()): ?>
                    <tr>
                        <form method="POST">
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td><?= htmlspecialchars($o['phone']) ?></td>
                            <td><?= $o['quantity'] ?>x <?= htmlspecialchars($o['food_name']) ?></td>
                            <td>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="pending" <?= $o['status']=='pending'?'selected':'' ?>>Pending</option>
                                    <option value="approved" <?= $o['status']=='approved'?'selected':'' ?>>Approved</option>
                                    <option value="delivered" <?= $o['status']=='delivered'?'selected':'' ?>>Delivered</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="users">
        <div class="card-header bg-white fw-bold">User Management</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 border-end">
                    <h6 class="fw-bold mb-3">Add New User</h6>
                    <form method="POST" class="p-3 bg-light rounded shadow-sm">
                        <input type="text" name="user_name" class="form-control mb-2" placeholder="Full Name" required>
                        <input type="email" name="user_email" class="form-control mb-2" placeholder="Email" required>
                        <input type="password" name="user_password" class="form-control mb-2" placeholder="Password" required>
                        <select name="user_role" class="form-select mb-3">
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button type="submit" name="add_user" class="btn btn-dark w-100 btn-sm">Create User</button>
                    </form>
                </div>
                
                <div class="col-md-8 ps-4">
                    <h6 class="fw-bold mb-3">Staff List</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr class="small text-muted"><th>NAME</th><th>ROLE</th><th>ACTION</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Re-fetch users for the table
                            $users_list = $conn->query("SELECT * FROM users");
                            while($u = $users_list->fetch()): ?>
                            <tr>
                                <form method="POST">
                                    <td><?= htmlspecialchars($u['name']) ?></td>
                                    <td>
                                        <select name="role" class="form-select form-select-sm d-inline w-auto">
                                            <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                                            <option value="manager" <?= $u['role']=='manager'?'selected':'' ?>>Manager</option>
                                            <option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" name="update_user_role" class="btn btn-sm btn-info text-white">Save</button>
                                        <button type="submit" name="delete_user" class="btn btn-sm text-danger" onclick="return confirm('Delete user?')"><i class="fas fa-trash"></i></button>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="menu">
        <div class="col-md-4">
            <div class="card p-3">
                <h5 class="fw-bold mb-3">Add New Food</h5>
                <form method="POST">
                    <input type="text" name="name" class="form-control mb-2" placeholder="Food Name" required>
                    <input type="number" name="price" class="form-control mb-2" placeholder="Price" required>
                    <button type="submit" name="add_food" class="btn btn-success w-100">Add Food</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-3">
                <h5 class="fw-bold mb-3">Menu List</h5>
                <table class="table table-sm">
                    <?php while($f = $foods->fetch()): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['name']) ?></td>
                        <td><?= number_format($f['price']) ?> RWF</td>
                        <td class="text-end">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="food_id" value="<?= $f['id'] ?>">
                                <button type="submit" name="delete_food" class="btn btn-sm text-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>