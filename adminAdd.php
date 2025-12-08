<?php
session_start();
// Security check: Ensure user is logged in
if (empty($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

require_once 'src/config.php';

$message = '';
$message_type = ''; // 'success' or 'error'

// Initialize variables
$first_name = '';
$last_name = '';
$full_name = '';
$user_type = '';
$srcode = '';
$contact = '';
$purpose = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect data directly from the POST request
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $full_name = $first_name . ' ' . $last_name; // Combine first and last name
    $user_type = trim($_POST['user_type'] ?? '');
    $srcode = trim($_POST['srcode'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($user_type) || empty($purpose)) {
        $message = "First Name, Last Name, Type, and Purpose are required fields.";
        $message_type = 'error';
    } 
    // Check if Student needs Sr-Code
    elseif ($user_type === 'Student' && empty($srcode)) {
        $message = "Sr-Code is required for students.";
        $message_type = 'error';
    }
    else {
        try {
            $pdo = connect_db();
            
            // Handle optional SR-Code: if user_type is Student, use provided srcode, otherwise NULL
            $final_srcode = ($user_type === 'Student' && !empty($srcode)) ? $srcode : NULL;
            
            // Note: time_out is NULL because the admin is checking the visitor IN now.
            $sql = "INSERT INTO visitors (full_name, user_type, srcode, contact, purpose, time_in, time_out) 
                    VALUES (?, ?, ?, ?, ?, NOW(), NULL)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $full_name,
                $user_type,
                $final_srcode,
                $contact,
                $purpose
            ]);

            $message = "Visitor $full_name successfully checked in.";
            $message_type = 'success';
            
            // Keep full_name for modal display, but clear form variables
            $temp_full_name = $full_name;
            $temp_user_type = $user_type;
            
            // Clear variables so form loads empty after success
            $first_name = $last_name = $srcode = $contact = $purpose = ''; 
            $user_type = '';

        } catch (Exception $e) {
            $message = "Error checking in visitor: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Add Visitor</title>
  <link rel="stylesheet" href="css/styles.css">
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
        <h2>Admin Visitor Form</h2>
        <nav class="main-nav">
            <a href="admin.php" class="nav-btn">Home</a>
            <a href="adminEditLog.php" class="nav-btn">Edit Log Book</a>
        </nav>
    </div>
    
    <?php if ($message && $message_type === 'error'): ?>
        <p class="status-message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="adminAdd.php" class="visitor-form">
        
        <label for="first_name">First Name *</label>
        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($first_name); ?>">

        <label for="last_name">Last Name *</label>
        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($last_name); ?>">
        
        <label for="user_type">Visitor Type *</label>
        <select id="user_type" name="user_type" required>
            <option value="">-- Select Type --</option>
            <option value="Student" <?php if ($user_type === 'Student') echo 'selected'; ?>>Student</option>
            <option value="Visitor" <?php if ($user_type === 'Visitor') echo 'selected'; ?>>Visitor</option>
        </select>
        
        <div id="srCodeField" style="display: none;">
            <label for="srcode">SR-Code *</label>
<input type="text" id="srcode" name="srcode" pattern="[0-9]{2}-[0-9]{5}" title="Please enter SR-Code in format: 10-10001" placeholder="e.g., 10-10001" value="<?php echo htmlspecialchars($srcode); ?>">
        </div>

        <label for="contact">Contact Number</label>
        <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($contact); ?>">

        <label for="purpose">Purpose of Visit *</label>
        <textarea id="purpose" name="purpose" required><?php echo htmlspecialchars($purpose); ?></textarea>

        <button type="submit" class="submit-btn">Check In Visitor</button>
    </form>
  </div>
  
  <!-- Receipt Modal -->
  <div id="adminReceiptModal" class="modal">
      <div class="modal-content">
          <h2>✓ Visitor Checked In!</h2>
          <div style="text-align: left; margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px;">
              <p><strong>Name:</strong> <span id="adminReceiptName"></span></p>
              <p><strong>Type:</strong> <span id="adminReceiptType"></span></p>
              <p><strong>Time:</strong> <span id="adminReceiptTime"></span></p>
          </div>
          <p style="color: #d32f2f; font-weight: 600;">Visitor successfully checked in!</p>
          <button class="close-btn" onclick="closeAdminModal()">Close</button>
      </div>
  </div>

  <script>
    const userType = document.getElementById('user_type');
    const srCodeField = document.getElementById('srCodeField');
    const srCodeInput = document.getElementById('srcode');

    function toggleSrCode() {
        if (userType.value === 'Student') {
            srCodeField.style.display = 'block';
            srCodeInput.required = true;
        } else {
            srCodeField.style.display = 'none';
            srCodeInput.required = false;
        }
    }

    userType.addEventListener('change', toggleSrCode);
    toggleSrCode();

    // Show modal on successful submission
    <?php if ($message_type === 'success' && isset($temp_full_name)): ?>
        document.getElementById('adminReceiptName').textContent = '<?php echo htmlspecialchars($temp_full_name); ?>';
        document.getElementById('adminReceiptType').textContent = '<?php echo htmlspecialchars($temp_user_type); ?>';
        document.getElementById('adminReceiptTime').textContent = new Date().toLocaleString();
        document.getElementById('adminReceiptModal').style.display = 'block';
    <?php endif; ?>

    function closeAdminModal() {
        document.getElementById('adminReceiptModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('adminReceiptModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
  </script>
</body>
</html>