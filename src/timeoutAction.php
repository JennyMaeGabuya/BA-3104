<?php
session_start();
if (empty($_SESSION['userId'])) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_ids'])) {
    
    $visitorIds = $_POST['visitor_ids'];
    
    // Validate that visitor_ids is an array and not empty
    if (!is_array($visitorIds) || empty($visitorIds)) {
        header('Location: ../admin.php?error=no_visitors_selected');
        exit;
    }
    
    // Sanitize visitor IDs (ensure they're all integers)
    $visitorIds = array_filter($visitorIds, 'is_numeric');
    $visitorIds = array_map('intval', $visitorIds);
    
    if (empty($visitorIds)) {
        header('Location: ../admin.php?error=invalid_visitor_ids');
        exit;
    }
    
    $currentTime = date('Y-m-d H:i:s');

    try {
        $pdo = connect_db();
        
        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($visitorIds), '?'));
        
        // Prepare SQL to update multiple visitors at once
        $sql = "UPDATE visitors 
                SET time_out = ? 
                WHERE visitor_id IN ($placeholders) 
                AND time_out IS NULL";
        
        $stmt = $pdo->prepare($sql);
        
        // Merge current time with visitor IDs for execution
        $params = array_merge([$currentTime], $visitorIds);
        $stmt->execute($params);
        
        $rowsAffected = $stmt->rowCount();
        
        // Success: Redirect back to admin page with success message
        header("Location: ../admin.php?success=timeout_complete&count=$rowsAffected");
        exit;

    } catch (Exception $e) {
        // Log error and redirect with error message
        error_log("Bulk Time Out Error: " . $e->getMessage());
        header('Location: ../admin.php?error=db_update_failed');
        exit;
    }
} else {
    header('Location: ../admin.php');
    exit;
}
?>