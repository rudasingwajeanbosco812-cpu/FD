<?php include 'db.php'; ?>

<form method="POST">
    <input type="text" name="name" placeholder="Name" required><br>
    
    <input type="email" name="email" placeholder="Email" required><br>
    
    <input type="password" name="password" placeholder="Password" required><br>

    <!-- ROLE SELECTION -->
    <select name="role" required>
        <option value="">Select Role</option>
        <option value="admin">Admin</option>
        <option value="manager">Manager</option>
        <option value="customer">Customer</option>
    </select><br><br>

    <button name="register">Register</button>
</form>

<?php
if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // 🔐 Hash password
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // ✅ Insert with role
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    
    if($stmt->execute([$name, $email, $password, $role])){
        echo "✅ Registered successfully as $role!";
    } else {
        echo "❌ Error occurred!";
    }
}
?>