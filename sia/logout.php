<?php
session_start();
include('db.php');

// Log activity before destroying session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, timestamp) VALUES (?, 'logged out', NOW())");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login with JS to clear localStorage
echo '<script>
        localStorage.removeItem("activeSection"); 
        window.location.href="login.php";
      </script>';
exit;
?>