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

// --- 2. QUERY TO GET ALL FOODS ---
// Menya neza ko column ya 'image' irimo muri SELECT
$stmt = $conn->query("SELECT * FROM foods ORDER BY id DESC");
$foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rudasingwa's Restaurant | Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=2070');
            background-size: cover;
            background-position: center;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 40px;
        }
        .food-card {
            background: white;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            height: 100%;
        }
        .food-card:hover { transform: translateY(-10px); }
        .food-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .price-tag {
            color: #2563eb;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .btn-order {
            background: #2563eb;
            color: white;
            border-radius: 8px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="fas fa-utensils text-primary me-2"></i>RUDASINGWA'S
        </a>
        <div class="ms-auto">
            <?php if(isset($_SESSION['user'])): ?>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="hero-section text-center">
    <div>
        <h1 class="display-4 fw-bold">Delicious Meals Delivered</h1>
        <p class="lead">Experience the best taste at Rudasingwa's Restaurant</p>
    </div>
</div>

<div class="container mb-5">
    <h3 class="fw-bold mb-4"><i class="fas fa-fire text-danger me-2"></i> Our Menu</h3>
    
    <div class="row g-4">
        <?php if(count($foods) > 0): ?>
            <?php foreach($foods as $food): ?>
                <div class="col-md-3">
                    <div class="food-card">
                        <?php 
                            // Reba niba ifoto iri muri database, niba idahari koresha default
                            $imagePath = !empty($food['image']) ? "uploads/" . $food['image'] : "https://via.placeholder.com/300x200?text=No+Image";
                        ?>
                        <img src="<?= $imagePath ?>" class="food-img" alt="<?= htmlspecialchars($food['name']) ?>" 
                             onerror="this.src='https://via.placeholder.com/300x200?text=Food+Image';">
                        
                        <div class="p-3 text-center">
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($food['name']) ?></h5>
                            <p class="price-tag mb-3"><?= number_format($food['price']) ?> RWF</p>
                            <a href="order.php?id=<?= $food['id'] ?>" class="btn btn-order w-100">Order Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                <h4>No food items available yet.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container text-center">
        <p class="mb-0">© 2026 Rudasingwa's Restaurant. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
