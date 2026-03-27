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

// --- 2. AUTHENTICATION & ROLE CHECK ---
if(!isset($_SESSION['user'])){
    header("Location: index.php?page=login");
    exit();
}

$stmt = $conn->prepare("SELECT role FROM users WHERE email=?");
$stmt->execute([$_SESSION['user']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user || $user['role'] != 'admin'){
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h1 style='color:red;'>🚫 Access Denied</h1>
            <p>Admin privileges required.</p>
            <a href='index.php'>Return to Home</a>
          </div>";
    exit();
}

// --- 3. FORM PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- KONGERA USER ---
    if(isset($_POST['add_user'])){
        $name = $_POST['user_name'];
        $email = $_POST['user_email'];
        $pass = password_hash($_POST['user_password'], PASSWORD_DEFAULT);
        $role = $_POST['user_role'];
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $pass, $role]);
    }

    // --- KONGERA FOOD (Ikiryo) IFITE IFOTO ---
    if(isset($_POST['add_food'])){
        $food_name = $_POST['name'];
        $price = $_POST['price'];
        
        $image_name = $_FILES['food_image']['name'];
        $tmp_name = $_FILES['food_image']['tmp_name'];
        $folder = "uploads/";

        // Reba niba folder ihari, niba idahari uyikore
        if (!is_dir($folder)) { mkdir($folder, 0777, true); }

        if (!empty($image_name)) {
            $new_image_name = time() . "_" . basename($image_name);
            $target_file = $folder . $new_image_name;

            if (move_uploaded_file($tmp_name, $target_file)) {
                $stmt = $conn->prepare("INSERT INTO foods (name, price, image) VALUES (?, ?, ?)");
                $stmt->execute([$food_name, $price, $new_image_name]);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO foods (name, price) VALUES (?, ?)");
            $stmt->execute([$food_name, $price]);
        }
    }

    // --- DELETE FOOD ---
    if(isset($_POST['delete_food'])){
        $conn->prepare("DELETE FROM foods WHERE id=?")->execute([$_POST['food_id']]);
    }

    // --- DELETE USER ---
    if(isset($_POST['delete_user'])){
        $conn->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['user_id']]);
    }

    header("Location: admin.php"); 
    exit();
}

// STATS
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalFoods = $conn->query("SELECT COUNT(*) FROM foods")->fetchColumn();
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Pro Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #1e293b; --main-bg: #f8fafc; --accent: #3b82f6; }
        body { font-family: 'Inter', sans-serif; background-color: var(--main-bg); display: flex; }
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; position: fixed; height: 100vh; padding: 2rem 1.5rem; }
        .main-content { margin-left: 260px; width: 100%; padding: 2.5rem; }
        .section-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-top: 20px; }
        .nav-link { color: #94a3b8; padding: 0.8rem; border-radius: 8px; text-decoration: none; display: block; }
        .nav-link.active { background: var(--accent); color: white; }
        .food-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2 class="fw-bold text-info mb-4">👑 Admin Pro</h2>
    <nav class="nav flex-column">
        <a class="nav-link active" href="#">📊 Dashboard</a>
        <a class="nav-link" href="#users">👥 Manage Users</a>
        <a class="nav-link" href="#food_list">🍔 Food Menu</a>
        <div class="mt-5"><a href="logout.php" class="btn btn-danger btn-sm w-100">Logout</a></div>
    </nav>
</div>

<div class="main-content">
    <h4 class="fw-bold mb-4">System Management</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="section-card text-center"><h6>Users</h6><h3><?= $totalUsers ?></h3></div></div>
        <div class="col-md-4"><div class="section-card text-center text-success"><h6>Foods</h6><h3><?= $totalFoods ?></h3></div></div>
        <div class="col-md-4"><div class="section-card text-center text-warning"><h6>Orders</h6><h3><?= $totalOrders ?></h3></div></div>
    </div>

    <div class="section-card" id="add_food">
        <h5 class="fw-bold text-success mb-3">🍔 Add New Food Menu</h5>
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-4">
                <label class="small fw-bold">Food Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="small fw-bold">Price (RWF)</label>
                <input type="number" name="price" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold">Food Image (Browser)</label>
                <input type="file" name="food_image" class="form-control" accept="image/*">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="add_food" class="btn btn-success w-100 fw-bold">Add Item</button>
            </div>
        </form>
    </div>

    <div class="section-card" id="food_list">
        <h5 class="fw-bold mb-3">Menu List</h5>
        <table class="table align-middle">
            <thead><tr class="small text-muted"><th>IMAGE</th><th>FOOD NAME</th><th>PRICE</th><th class="text-end">ACTION</th></tr></thead>
            <tbody>
                <?php $foods = $conn->query("SELECT * FROM foods ORDER BY id DESC"); while($f = $foods->fetch()): ?>
                <tr>
                    <td>
                        <?php if(!empty($f['image'])): ?>
                            <img src="uploads/<?= $f['image'] ?>" class="food-img">
                        <?php else: ?>
                            <div class="bg-light text-center food-img pt-2 small">No Img</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= $f['name'] ?></strong></td>
                    <td><?= number_format($f['price']) ?> RWF</td>
                    <td class="text-end">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="food_id" value="<?= $f['id'] ?>">
                            <button name="delete_food" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this food?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section-card" id="users">
        <h5 class="fw-bold text-primary mb-3">System Staff</h5>
        <div class="row">
            <div class="col-md-4 border-end">
                <form method="POST" class="p-3 bg-light rounded">
                    <h6>New User</h6>
                    <input type="text" name="user_name" class="form-control form-control-sm mb-2" placeholder="Full Name" required>
                    <input type="email" name="user_email" class="form-control form-control-sm mb-2" placeholder="Email" required>
                    <input type="password" name="user_password" class="form-control form-control-sm mb-2" placeholder="Password" required>
                    <select name="user_role" class="form-select form-select-sm mb-3">
                        <option value="manager">Manager</option>
                        <option value="admin">Administrator</option>
                    </select>
                    <button type="submit" name="add_user" class="btn btn-primary btn-sm w-100">Create</button>
                </form>
            </div>
            <div class="col-md-8">
                <table class="table table-sm">
                    <thead><tr class="small text-muted"><th>NAME</th><th>ROLE</th><th class="text-end">ACTION</th></tr></thead>
                    <tbody>
                        <?php $users = $conn->query("SELECT * FROM users"); while($u = $users->fetch()): ?>
                        <tr>
                            <td><strong><?= $u['name'] ?></strong></td>
                            <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                            <td class="text-end">
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button name="delete_user" class="btn btn-link text-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
