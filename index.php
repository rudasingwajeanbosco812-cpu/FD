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

// --- 2. ORDER PROCESSING ---
$order_status = "";
if (isset($_POST['place_order'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO orders (customer_name, phone, food_id, quantity, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$_POST['customer_name'], $_POST['phone'], $_POST['food_id'], $_POST['quantity']]);
        $order_status = "success";
    } catch (PDOException $e) {
        $order_status = "error";
        $debug_msg = $e->getMessage(); 
    }
}

// --- 3. LOGIN LOGIC ---
$auth_error = "";
if (isset($_POST['login'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->execute([$_POST['email'], $_POST['role']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        header("Location: " . ($user['role'] == 'admin' ? "admin.php" : "manager.php"));
        exit();
    } else {
        $auth_error = "Invalid credentials or role selection.";
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rudasingwa's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --gold: #ffb703; --navy: #023047; }
        body { font-family: sans-serif; background: #f8f9fa; }
        .hero { 
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=1200'); 
            background-size: cover; 
            background-position: center;
            height: 40vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            border-bottom: 5px solid var(--gold); 
        }
        .food-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); background: white; padding: 20px; transition: 0.3s; height: 100%; }
        .food-card:hover { transform: translateY(-5px); }
        .food-img { width: 100%; height: 180px; object-fit: cover; border-radius: 10px; margin-bottom: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🍴 RUDASINGWA'S</a>
        <div class="ms-auto">
            <a href="index.php" class="btn btn-link text-dark text-decoration-none">Home</a>
            <a href="index.php?page=login" class="btn btn-outline-dark btn-sm rounded-pill px-3">Login</a>
        </div>
    </div>
</nav>

<?php if($page == 'home'): ?>
    <header class="hero text-center">
        <h1 class="display-4 fw-bold">WELCOME TO RUDASINGWA'S RESTAURANT</h1>
    </header>

    <div class="container my-5 text-center">
        <?php if($order_status == "success"): ?>
            <div class="alert alert-success shadow-sm">
                <i class="fas fa-check-circle me-2"></i> Order placed successfully! We will contact you soon.
            </div>
        <?php elseif($order_status == "error"): ?>
            <div class="alert alert-danger shadow-sm">Error: <?= $debug_msg ?></div>
        <?php endif; ?>

        <div class="row g-4 text-start">
            <?php
            $foods = $conn->query("SELECT * FROM foods");
            while($f = $foods->fetch()): 
                
                // Logic yo guhitamo ifoto bitewe n'izina ry'ibiryo
                $food_name = strtolower($f['name']);
                $img_url = "https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=500"; // Default image (Coffee-like)

                if (strpos($food_name, 'pizza') !== false) {
                    $img_url = "https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500";
                } elseif (strpos($food_name, 'burger') !== false) {
                    $img_url = "https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=500";
                } elseif (strpos($food_name, 'coffee') !== false || strpos($food_name, 'cafe') !== false) {
                    $img_url = "https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=500";
                } elseif (strpos($food_name, 'chips') !== false || strpos($food_name, 'fries') !== false) {
                    $img_url = "https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=500";
                } elseif (strpos($food_name, 'juice') !== false || strpos($food_name, 'drink') !== false) {
                    $img_url = "https://images.unsplash.com/photo-1536599018102-9f803c140fc1?w=500";
                }
            ?>
            <div class="col-md-3">
                <div class="food-card">
                    <img src="<?= $img_url ?>" class="food-img" alt="<?= htmlspecialchars($f['name']) ?>">
                    
                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($f['name']) ?></h6>
                    <p class="text-primary fw-bold mb-3"><?= number_format($f['price']) ?> RWF</p>
                    
                    <form method="POST" class="bg-light p-2 rounded shadow-sm">
                        <input type="hidden" name="food_id" value="<?= $f['id'] ?>">
                        <input type="text" name="customer_name" class="form-control form-control-sm mb-2" placeholder="Full Name" required>
                        <input type="text" name="phone" class="form-control form-control-sm mb-2" placeholder="Phone" required>
                        <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm mb-2">
                        <button type="submit" name="place_order" class="btn btn-dark btn-sm w-100">Order Now</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

<?php elseif($page == 'login'): ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-4 card p-4 shadow-sm border-0">
                <h3 class="text-center mb-4">Login</h3>
                <?php if($auth_error) echo "<div class='alert alert-danger'>$auth_error</div>"; ?>
                <form method="POST">
                    <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                    <select name="role" class="form-select mb-4">
                        <option value="admin">Administrator</option>
                        <option value="manager">Manager</option>
                    </select>
                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<footer class="text-center py-4 text-muted border-top mt-5 bg-white">
    <small>&copy; 2026 Rudasingwa's Restaurant. All rights reserved.</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>