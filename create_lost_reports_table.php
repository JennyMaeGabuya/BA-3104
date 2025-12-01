<?php
/**
 * Database Setup Script
 * Run this file ONCE to create the lost_reports table
 * Access: http://localhost/SIA.html/create_lost_reports_table.php
 */

require_once 'db_config.php';

try {
    // Create lost_reports table
    $sql = "CREATE TABLE IF NOT EXISTS lost_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_id VARCHAR(20) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        location VARCHAR(255) NOT NULL,
        date_lost DATE NOT NULL,
        time_lost TIME,
        photo_path VARCHAR(500),
        contact_email VARCHAR(150) NOT NULL,
        contact_phone VARCHAR(30) NOT NULL,
        status ENUM('Pending', 'Verified', 'Claimed', 'Rejected') DEFAULT 'Pending',
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_report_id (report_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo "✅ SUCCESS! The 'lost_reports' table has been created.<br><br>";
    echo "You can now:<br>";
    echo "1. Submit lost item reports from user_report.php<br>";
    echo "2. View them in Dashboard/my_report.php<br><br>";
    echo "<a href='user_report.php'>Go to Report Lost Item</a> | ";
    echo "<a href='Dashboard/my_report.php'>Go to My Reports</a><br><br>";
    echo "<strong>Note:</strong> You can delete this file (create_lost_reports_table.php) after running it once.";
    
} catch (PDOException $e) {
    echo "❌ ERROR creating table: " . $e->getMessage() . "<br><br>";
    echo "Make sure your database connection in db_config.php is correct.";
}
?>
