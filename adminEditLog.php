<?php
session_start();
if (empty($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

require_once 'src/config.php';

$message = '';
$message_type = '';

// Get search parameters
$search_name = $_GET['search_name'] ?? '';
$search_date = $_GET['search_date'] ?? '';

// Handle BULK DELETE
if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    $ids = $_POST['visitor_ids'] ?? [];
    
    if (!empty($ids) && is_array($ids)) {
        $ids = array_filter($ids, 'is_numeric');
        $ids = array_map('intval', $ids);
        
        if (!empty($ids)) {
            try {
                $pdo = connect_db();
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM visitors WHERE visitor_id IN ($placeholders)");
                $stmt->execute($ids);
                
                $count = $stmt->rowCount();
                $message = "$count record(s) deleted successfully.";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Error deleting records: " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Handle EDIT
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['visitor_id']);
    $full_name = trim($_POST['full_name']);
    $user_type = trim($_POST['user_type']);
    $srcode = trim($_POST['srcode']);
    $contact = trim($_POST['contact']);
    $purpose = trim($_POST['purpose']);
    
    try {
        $pdo = connect_db();
        $final_srcode = ($user_type === 'Student' && !empty($srcode)) ? $srcode : NULL;
        
        $stmt = $pdo->prepare("UPDATE visitors SET full_name=?, user_type=?, srcode=?, contact=?, purpose=? WHERE visitor_id=?");
        $stmt->execute([$full_name, $user_type, $final_srcode, $contact, $purpose, $id]);
        
        $message = "Record updated successfully.";
        $message_type = 'success';
    } catch (Exception $e) {
        $message = "Error updating record: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch visitors with search filters
try {
    $pdo = connect_db();
    
    $where_clauses = [];
    $params = [];
    
    // Search by name
    if (!empty($search_name)) {
        $where_clauses[] = "full_name LIKE ?";
        $params[] = '%' . $search_name . '%';
    }
    
    // Search by date
    if (!empty($search_date)) {
        $where_clauses[] = "DATE(time_in) = ?";
        $params[] = $search_date;
    }
    
    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
    }
    
    $sql = "SELECT * FROM visitors" . $where_sql . " ORDER BY time_in DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = "Error loading records: " . $e->getMessage();
    $message_type = 'error';
    $visitors = [];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Log Book</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    /* ==================== EDIT LOG BOOK STYLES ==================== */
/* Add these styles to your existing styles.css file */

/* Scrollable table with fixed height for 5 rows */
.table-scroll-container {
    max-height: 400px;
    overflow-y: auto;
    overflow-x: auto; /* Allow horizontal scroll */
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-top: 15px;
}

.admin-wrap {
    max-width: 95%;
    margin: 30px auto;
}

.table-scroll-container table {
    width: 100%;
    min-width: 1200px; /* Minimum width to prevent squeezing */
    border-collapse: collapse;
    table-layout: auto; /* Allow columns to size naturally */
}

.table-scroll-container thead {
    position: sticky;
    top: 0;
    background-color: #d32f2f;
    z-index: 10;
}

.table-scroll-container thead th {
    background-color: #d32f2f;
    color: white;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    white-space: nowrap; /* Prevent header text wrapping */
    font-size: 14px;
}

/* Column width definitions */
.table-scroll-container th:nth-child(1),
.table-scroll-container td:nth-child(1) { 
    width: 50px; 
    text-align: center;
}

.table-scroll-container th:nth-child(2),
.table-scroll-container td:nth-child(2) { 
    width: 60px; 
}

.table-scroll-container th:nth-child(3),
.table-scroll-container td:nth-child(3) { 
    width: 180px; 
    min-width: 150px;
}

.table-scroll-container th:nth-child(4),
.table-scroll-container td:nth-child(4) { 
    width: 100px; 
}

.table-scroll-container th:nth-child(5),
.table-scroll-container td:nth-child(5) { 
    width: 120px; 
}

.table-scroll-container th:nth-child(6),
.table-scroll-container td:nth-child(6) { 
    width: 130px; 
}

.table-scroll-container th:nth-child(7),
.table-scroll-container td:nth-child(7) { 
    width: 180px; 
    min-width: 150px;
}

.table-scroll-container th:nth-child(8),
.table-scroll-container td:nth-child(8) { 
    width: 160px; 
}

.table-scroll-container th:nth-child(9),
.table-scroll-container td:nth-child(9) { 
    width: 160px; 
}

.table-scroll-container th:nth-child(10),
.table-scroll-container td:nth-child(10) { 
    width: 120px; 
    min-width: 100px;
}

.table-scroll-container tbody td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    vertical-align: middle;
}

.table-scroll-container tbody tr:hover {
    background-color: #f5f5f5;
}

.editing-row {
    background-color: #fff3cd !important;
}

.editing input, .editing select, .editing textarea {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
    box-sizing: border-box;
}

.editing textarea {
    min-height: 60px;
    resize: vertical;
}

/* Button styling in actions column */
.edit-log-btn, .save-log-btn, .cancel-log-btn {
    padding: 6px 12px;
    margin: 2px;
    font-size: 13px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    white-space: nowrap;
}

.edit-log-btn {
    background: #2196F3;
    color: white;
}

.edit-log-btn:hover {
    background: #1976D2;
}

.save-log-btn {
    background: #4CAF50;
    color: white;
}

.save-log-btn:hover {
    background: #388E3C;
}

.cancel-log-btn {
    background: #ff9800;
    color: white;
}

.cancel-log-btn:hover {
    background: #f57c00;
}

/* Mobile responsiveness */
@media screen and (max-width: 768px) {
    .table-scroll-container {
        max-height: 500px; /* Taller on mobile */
    }
}

/* Scrollbar styling */
.table-scroll-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-scroll-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-scroll-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.search-filter-bar {
    background: #f5f5f5;  /* Change this color (currently light gray) */
}

.search-filter-bar input {
    padding: 8px 12px;           /* Size of padding inside input */
    border: 1px solid #ddd;      /* Border color */
    border-radius: 4px;          /* Rounded corners */
    font-size: 14px;             /* Text size */
    min-width: 150px;            /* Minimum width */
}

.search-filter-bar input[type="text"] {
    flex: 1;                     /* Makes it flexible */
    min-width: 200px;            /* Minimum width */
}

.search-filter-bar input[type="date"] {
    padding: 8px 12px;           /* Size of padding */
    border: 1px solid #ddd;      /* Border color */
    border-radius: 4px;          /* Rounded corners */
    font-size: 14px;             /* Text size */
    min-width: 150px;            /* Minimum width */
    cursor: pointer;             /* Mouse cursor style */
}

.search-filter-bar input[type="date"]:focus {
    border-color: #d32f2f;       /* Border color when focused (red) */
    box-shadow: 0 0 0 2px rgba(211, 47, 47, 0.1);  /* Glow effect */
}

.search-filter-bar button {
    padding: 8px 16px;           /* Button size */
    background: #d32f2f;         /* Button background (red) */
    color: white;                /* Text color */
    border-radius: 4px;          /* Rounded corners */
    font-weight: 600;            /* Text boldness */
}

.search-filter-bar button:hover {
    background: #b71c1c;         /* Darker red on hover */
}

.search-filter-bar .reset-search-btn {
    background: #666;            /* Button background (gray) */
    padding: 8px 16px;           /* Button size */
    color: white;                /* Text color */
}

.search-filter-bar .reset-search-btn:hover {
    background: #444;            /* Darker gray on hover */
}

.search-filter-bar {
    gap: 10px;                   /* Space between input/buttons */
    padding: 15px;               /* Space inside the bar */
    margin-bottom: 15px;         /* Space below the bar */
}
</style>
</head>
<body class="admin-page">
  <div class="admin-wrap">

    <div class="admin-top">
        <h1>Admin Dashboard</h1>
        <div class="user-info-bar">
            <span class="signed-in-msg">
                Signed in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            </span>
            <a href="src/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="header-nav-section">
        <h2>Edit Log Book</h2>
        <nav class="main-nav">
            <a href="admin.php" class="nav-btn">Home</a>
            <a href="adminAdd.php" class="nav-btn">Add Visitor</a>
        </nav>
    </div>
    
    <?php if ($message): ?>
        <div class="status-message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Search Filter Bar -->
    <form method="GET" action="adminEditLog.php" class="search-filter-bar">
        <input type="text" name="search_name" placeholder="Search by name..." 
               value="<?php echo htmlspecialchars($search_name); ?>" style="flex: 1;">
        
        <input type="date" name="search_date" 
               value="<?php echo htmlspecialchars($search_date); ?>">
        
        <button type="submit">Search</button>
        <a href="adminEditLog.php" class="reset-search-btn" style="padding: 8px 16px; text-decoration: none; display: inline-block; border-radius: 4px;">Reset</a>
    </form>

    <?php if (empty($visitors)): ?>
        <div style="text-align: center; padding: 40px; color: #ffffffff;">No visitor records found.</div>
    <?php else: ?>
        <form method="POST" id="bulkDeleteForm">
            <input type="hidden" name="action" value="bulk_delete">
            
            <div style="margin-bottom: 15px;">
                <button type="submit" class="delete-log-btn" id="bulkDeleteBtn">
                    Delete Selected (<span id="selectedCount">0</span>)
                </button>
            </div>

            <div class="table-scroll-container">
                <table>
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" title="Select All Checked Out">
                            </th>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Type</th>
                            <th>SR-Code</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitors as $v): ?>
                            <tr id="row-<?php echo $v['visitor_id']; ?>" data-id="<?php echo $v['visitor_id']; ?>">
                                <td>
                                    <?php if (!empty($v['time_out'])): ?>
                                        <input type="checkbox" name="visitor_ids[]" value="<?php echo $v['visitor_id']; ?>" class="visitor-checkbox">
                                    <?php else: ?>
                                        <span style="color: #ccc;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($v['visitor_id']); ?></td>
                                <td class="editable-cell" data-field="full_name"><?php echo htmlspecialchars($v['full_name']); ?></td>
                                <td class="editable-cell" data-field="user_type"><?php echo htmlspecialchars($v['user_type']); ?></td>
                                <td class="editable-cell" data-field="srcode">
                                    <?php 
                                    if (empty($v['srcode']) || trim($v['srcode']) === '') {
                                        echo '<span style="color: #999;">N/A</span>';
                                    } else {
                                        echo htmlspecialchars($v['srcode']);
                                    }
                                    ?>
                                </td>
                                <td class="editable-cell" data-field="contact"><?php echo htmlspecialchars($v['contact'] ?? 'N/A'); ?></td>
                                <td class="editable-cell" data-field="purpose"><?php echo htmlspecialchars($v['purpose']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($v['time_in']))); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($v['time_out'])) {
                                        echo htmlspecialchars(date('M d, Y g:i A', strtotime($v['time_out'])));
                                    } else {
                                        echo '<span style="color: #4CAF50; font-weight: 600;">Checked In</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($v['time_out'])): ?>
                                        <button type="button" class="edit-log-btn" onclick="enableEdit(<?php echo $v['visitor_id']; ?>)">Edit</button>
                                        <button type="button" class="save-log-btn" onclick="saveEdit(<?php echo $v['visitor_id']; ?>)" style="display:none;">Save</button>
                                        <button type="button" class="cancel-log-btn" onclick="cancelEdit(<?php echo $v['visitor_id']; ?>)" style="display:none;">Cancel</button>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">Still checked in</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php endif; ?>

  </div>

  <!-- Edit Form (hidden) -->
  <form method="POST" id="editForm" style="display:none;">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="visitor_id" id="edit_id">
    <input type="hidden" name="full_name" id="edit_full_name">
    <input type="hidden" name="user_type" id="edit_user_type">
    <input type="hidden" name="srcode" id="edit_srcode">
    <input type="hidden" name="contact" id="edit_contact">
    <input type="hidden" name="purpose" id="edit_purpose">
  </form>

  <script src="js/editlog.js"></script>
</body>
</html>