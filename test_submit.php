<?php
/**
 * Debug test for form submission
 * Shows exactly what's being received
 */

session_start();

echo "<h2>Session Info:</h2>";
echo "<pre>";
echo "Logged in: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "User Type: " . ($_SESSION['user_type'] ?? 'not set') . "\n";
}
echo "</pre>";

echo "<h2>POST Data:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>FILES Data:</h2>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

echo "<h2>Database Connection Test:</h2>";
try {
    require_once 'db_config.php';
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'lost_reports'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p style='color: green;'>✅ Table 'lost_reports' exists!</p>";
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) FROM lost_reports");
        $count = $stmt->fetchColumn();
        echo "<p>Current records in table: $count</p>";
    } else {
        echo "<p style='color: red;'>❌ Table 'lost_reports' does NOT exist!</p>";
        echo "<p>Please run: <a href='create_lost_reports_table.php'>create_lost_reports_table.php</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
