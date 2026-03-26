<?php
session_start();

// 🧹 delete all session data
session_unset();
session_destroy();

// 👉 redirect to home
header("Location: index.php");
exit();
?>