<?php
session_start();
include 'db.php';

// message
$msg = "";

if(isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // check if empty
    if(empty($email) || empty($password)){
        $msg = "❌ Fill all fields!";
    } else {

        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user && password_verify($password, $user['password'])){

            // store session
            $_SESSION['user'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // redirect
            if($user['role'] == 'admin'){
                header("Location: admin_dashboard.php");
            } elseif($user['role'] == 'manager'){
                header("Location: manager_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();

        } else {
            // 🔥 redirect to clear form
            header("Location: login.php?error=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <style>
        body{
            font-family: Arial;
            background:#f4f4f4;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }
        .box{
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 0 10px #ccc;
            width:300px;
        }
        input, button{
            width:100%;
            padding:10px;
            margin:5px 0;
            box-sizing: border-box;
        }
        .error{
            color:red;
            text-align:center;
        }
    </style>
</head>
<body>

<div class="box">
    <h2 style="text-align:center;">Login</h2>

    <?php
    if(isset($_GET['error'])){
        echo "<p class='error'>❌ Invalid email or password!</p>";
    }
    ?>

    <form method="POST" autocomplete="off">
        <input type="email" name="email" placeholder="Email" required autocomplete="off">
        <input type="password" name="password" placeholder="Password" required autocomplete="new-password">
        <button name="login">Login</button>
    </form>
</div>

</body>
</html>