<?php
session_start();

if (empty($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}
require_once 'src/config.php'; 

$visitors = []; 
$errorMessage = null; 

// Filter/s
$filter_user_type = $_GET['user_type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['filter_date'] ?? ''; 
$search_term = $_GET['search'] ?? '';


$where_clauses = [];
$params = [];

// Filter by User Type
if ($filter_user_type !== 'all') {
    $where_clauses[] = "user_type = ?"; 
    $params[] = $filter_user_type;
}

// Filter by Status (Checked In vs. Checked Out)
if ($filter_status === 'checked_in') {
    $where_clauses[] = "time_out IS NULL";
} elseif ($filter_status === 'checked_out') {
    $where_clauses[] = "time_out IS NOT NULL";
}

// Filter by Date
if (!empty($filter_date)) {
    $where_clauses[] = "DATE(time_in) = ?";
    $params[] = $filter_date;
}

// Filter by Searching
if (!empty($search_term)) {
    $where_clauses[] = "(
        full_name LIKE ? OR 
        srcode LIKE ? OR 
        purpose LIKE ?
    )";
    
    $like_term = '%' . $search_term . '%';
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
}

// Build the final WHERE clause
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Initialize statistics
$stats = [
    'total' => 0,
    'checked_in' => 0,
    'checked_out' => 0
];

try {
    $pdo = connect_db(); 
    
    // Get the main visitor list
    $sql = "SELECT * FROM visitors" . $where_sql . " ORDER BY time_in DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics based on current filters
    $stats['total'] = count($visitors);
    
    // Count checked in and checked out from filtered results
    foreach ($visitors as $visitor) {
        if (empty($visitor['time_out'])) {
            $stats['checked_in']++;
        } else {
            $stats['checked_out']++;
        }
    }

} catch (Exception $e) {
    $errorMessage = "Database Error: Could not load visitor data. (Check config.php and table name)";
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
.admin-wrap {
    max-width: 95%; /* Wider container */
    margin: 30px auto; /* Centers the whole content */
}

.table-scroll-container {
    max-height: 400px;
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-top: 15px;
}

.table-scroll-container table {
    width: 100%;
    min-width: 1200px;
    border-collapse: collapse;
    table-layout: auto;
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
    white-space: nowrap;
    font-size: 14px;
}

/* Column Widths */
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
}

.table-scroll-container th:nth-child(4),
.table-scroll-container td:nth-child(4) { 
    width: 160px; 
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
}

.table-scroll-container th:nth-child(8),
.table-scroll-container td:nth-child(8) { 
    width: 160px; 
}

.table-scroll-container th:nth-child(9),
.table-scroll-container td:nth-child(9) { 
    width: 160px; 
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

    
    .status-badge {
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-checked-in {
        background-color: #e8f5e9;
        color: #2e7d32;
    }
    
    .status-checked-out {
        background-color: #fce4ec;
        color: #c2185b;
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

    <!-- Statistics Boxes -->
    <div class="stats-container">
      <div class="stat-box total">
        <div class="stat-label">Total Visitors</div>
        <div class="stat-number"><?php echo $stats['total']; ?></div>
      </div>

      <div class="stat-box checked-in">
        <div class="stat-label">Currently Checked In</div>
        <div class="stat-number"><?php echo $stats['checked_in']; ?></div>
      </div>

      <div class="stat-box checked-out">
        <div class="stat-label">Checked Out</div>
        <div class="stat-number"><?php echo $stats['checked_out']; ?></div>
      </div>
    </div>

    <div class="filter-controls-bar">
        <h2>Filter</h2>
        <div class="xml-actions">
            <a href="exportXml.php?user_type=<?php echo htmlspecialchars($filter_user_type); ?>&status=<?php echo htmlspecialchars($filter_status); ?>&filter_date=<?php echo htmlspecialchars($filter_date); ?>&search=<?php echo htmlspecialchars($search_term); ?>" class="export-btn" title="Export Current View to XML">Export to XML</a>
            <a href="importXml.php" class="import-btn" title="Import Visitors from XML">Import XML</a>
        </div>
    </div>

    <form method="GET" action="admin.php" class="filter-form">
    
        <input type="text" name="search" placeholder="Search Name, Sr-Code, or Purpose..." 
               value="<?php echo htmlspecialchars($search_term); ?>" class="search-input">    

        <label for="user_type_select">Type:</label>
        <select name="user_type" id="user_type_select">
            <option value="all" <?php if ($filter_user_type === 'all') echo 'selected'; ?>>All Types</option>
            <option value="Student" <?php if ($filter_user_type === 'Student') echo 'selected'; ?>>Student</option>
            <option value="Visitor" <?php if ($filter_user_type === 'Visitor') echo 'selected'; ?>>Visitor</option>
        </select>

        <label for="status_select">Status:</label>
        <select name="status" id="status_select">
            <option value="all" <?php if ($filter_status === 'all') echo 'selected'; ?>>All Statuses</option>
            <option value="checked_in" <?php if ($filter_status === 'checked_in') echo 'selected'; ?>>Checked In</option>
            <option value="checked_out" <?php if ($filter_status === 'checked_out') echo 'selected'; ?>>Checked Out</option>
        </select>
        
        <label for="filter_date">Date:</label>
        <input type="date" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
    
        <button type="submit" class="apply-btn">Apply Filter</button>
        <a href="admin.php" class="reset-btn">Reset</a>
        <a href="adminAdd.php" class="add-visitor-btn">Add Visitor</a>

    </form>
    <h2>Visitor Log</h2>

    <?php if (isset($errorMessage)): ?>
        <p style="color: red;"><?php echo $errorMessage; ?></p>
    <?php elseif (empty($visitors)): ?>
        <p>No visitors have checked in yet.</p>
    <?php else: ?>

        <form method="POST" action="src/timeoutAction.php" id="bulkTimeoutForm">
            <div style="margin-bottom: 15px;">
                <button type="submit" class="timeout-btn" id="bulkTimeoutBtn">
                    Time Out Selected (<span id="selectedCount">0</span>)
                </button>
            </div>

            <div class="table-scroll-container">
                <table>
                    <thead>
                        <tr>
                         <th>
                            <input type="checkbox" id="selectAll" title="Select All Checked In">
                         </th>
                         <th>ID</th>
                         <th>Checked In</th>
                         <th>Full Name</th>
                         <th>Type</th>
                         <th>Sr-Code</th>
                         <th>Contact</th>
                         <th>Purpose</th>
                         <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($visitors as $visitor): ?>
                        <tr>
                         <td>
                            <?php if (empty($visitor['time_out'])): ?>
                                <input type="checkbox" name="visitor_ids[]" value="<?php echo $visitor['visitor_id']; ?>" class="visitor-checkbox">
                            <?php else: ?>
                                <span style="color: #ccc;">✓</span>
                            <?php endif; ?>
                         </td>
                         <td><?php echo htmlspecialchars($visitor['visitor_id']); ?></td>
                         <td><?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($visitor['time_in']))); ?></td>
                         <td><?php echo htmlspecialchars($visitor['full_name']); ?></td>
                         <td><?php echo htmlspecialchars($visitor['user_type']); ?></td>
                         <td>
                            <?php 
                            if (empty($visitor['srcode']) || trim($visitor['srcode']) === '') {
                                echo '<span style="color: #999;">N/A</span>';
                            } else {
                                echo htmlspecialchars($visitor['srcode']);
                            }
                            ?>
                         </td>
                         <td><?php echo htmlspecialchars($visitor['contact']); ?></td>
                         <td><?php echo htmlspecialchars($visitor['purpose']); ?></td>
                         <td>
                            <?php if (empty($visitor['time_out'])): ?>
                               <span class="status-badge status-checked-in">Checked In</span>
                            <?php else: ?>
                               <span class="status-badge status-checked-out">Checked Out</span>
                               <br>
                               <small style="color: #666;"><?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($visitor['time_out']))); ?></small>
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

  <script>
    // Select all functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const visitorCheckboxes = document.querySelectorAll('.visitor-checkbox');
    const bulkTimeoutBtn = document.getElementById('bulkTimeoutBtn');
    const selectedCount = document.getElementById('selectedCount');
    const bulkTimeoutForm = document.getElementById('bulkTimeoutForm');

    function updateSelectedCount() {
        const checkedBoxes = document.querySelectorAll('.visitor-checkbox:checked');
        const count = checkedBoxes.length;
        selectedCount.textContent = count;
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            visitorCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }

    visitorCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
            
            // Update "select all" checkbox state
            const allChecked = Array.from(visitorCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(visitorCheckboxes).some(cb => cb.checked);
            
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });

    // Form submission confirmation
    if (bulkTimeoutForm) {
        bulkTimeoutForm.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.visitor-checkbox:checked');
            const count = checkedBoxes.length;
            
            if (count === 0) {
                e.preventDefault();
                alert('Please select at least one visitor to time out.');
                return false;
            }
            
            const confirmMsg = count === 1 
                ? 'Are you sure you want to time out 1 visitor?' 
                : `Are you sure you want to time out ${count} visitors?`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Initialize count on page load
    updateSelectedCount();
  </script>
</body>
</html>