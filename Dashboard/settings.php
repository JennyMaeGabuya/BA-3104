<?php
/**
 * Settings Page
 * Requires authentication - redirects to login if not logged in
 */

require_once '../auth_check.php';
require_once '../db_config.php';
require_once __DIR__ . '/notification_context.php';

// Get user data from session (with safe fallbacks)
$fullname = $_SESSION['fullname'] ?? $_SESSION['user_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$first_name = $_SESSION['first_name'] ?? $_SESSION['fname'] ?? '';
$last_name = $_SESSION['last_name'] ?? $_SESSION['lname'] ?? '';
$phone = $_SESSION['phone'] ?? $_SESSION['phone_number'] ?? $_SESSION['tel'] ?? '';

// If first/last not separately saved, try to split fullname
if (empty($first_name) && !empty($fullname)) {
  $parts = preg_split('/\s+/', trim($fullname));
  $first_name = $parts[0] ?? '';
  if (count($parts) > 1) {
    $last_name = implode(' ', array_slice($parts, 1));
  }
}

$notificationContext = build_notification_context($pdo, $_SESSION['user_id'] ?? null, 10);
$notificationDropdown = $notificationContext['dropdownNotifications'];
$notificationTotal = $notificationContext['totalCount'];
$notificationUnread = $notificationContext['unreadCount'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — Settings</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BA-3104/Dashboard/dashboard.css">
</head>
<body>
  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-top">
       <a href="../user_home.php" class="brand">
        <div class="logo" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <path d="M12 3v18"></path>
          </svg>
        </div>
          <div class="brand-text">
            <div class="brand-title">FindIt</div>
            <div class="brand-sub">@BatStateU</div>
          </div>
        </a>

       <nav class="nav" aria-label="Primary">
    
    <a href="dashboard.php" class="nav-item">
        <span class="nav-icon" aria-hidden="true">🏠</span>
        <span class="nav-label">Dashboard</span>
    </a>

    <a href="my_report.php" class="nav-item">
        <span class="nav-icon" aria-hidden="true">📄</span>
        <span class="nav-label">My Reports</span>
    </a>

    <a href="../user_found.php" class="nav-item">
        <span class="nav-icon" aria-hidden="true">🔍</span>
        <span class="nav-label">Found Items</span>
    </a>

    <a href="notification.php" class="nav-item">
      <span class="nav-icon" aria-hidden="true">🔔</span>
      <span class="nav-label">Notifications</span>
      <span class="nav-badge" id="sidebarBadge" <?= $notificationTotal === 0 ? 'style="display:none;"' : '' ?>><?= $notificationTotal ?></span>
    </a>

    <a href="settings.php" class="nav-item nav-item--active">
        <span class="nav-icon" aria-hidden="true">⚙️</span>
        <span class="nav-label">Settings</span>
    </a>

</nav>

      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main">
      <!-- HEADER -->
      <header class="topbar">
        <div class="topbar-left">
          <button id="btnMenu" class="menu-btn" aria-label="Toggle menu">☰</button>
          <div class="topbar-title">Lost and Found Management System</div>
        </div>

        <div class="topbar-right">
          <div class="notification-container">
            <button class="icon-btn" id="notifBtn" aria-label="Notifications">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h11z"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              <span class="topbar-badge" id="topbarBadge" <?= $notificationUnread === 0 ? 'style="display:none;"' : '' ?>><?= $notificationUnread > 0 ? $notificationUnread : '' ?></span>
            </button>

            <!-- Notification Dropdown -->
            <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
              <div class="notification-dropdown-header">
                <h3>Notifications</h3>
              </div>
              <div class="notification-dropdown-list">
                <?php if (empty($notificationDropdown)): ?>
                  <div class="notification-dropdown-item">
                    <div class="notification-dropdown-content">
                      <div class="notification-dropdown-title">No recent notifications.</div>
                    </div>
                  </div>
                <?php else: ?>
                  <?php foreach ($notificationDropdown as $notification): ?>
                    <div class="notification-dropdown-item">
                      <div class="notification-dropdown-content">
                        <div class="notification-dropdown-title"><?= htmlspecialchars($notification['message']) ?></div>
                        <div class="notification-dropdown-time"><?= htmlspecialchars(format_relative_time($notification['created_at'] ?? null)) ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <div class="notification-dropdown-footer">
                <a href="notification.php" class="view-all-link">View all notifications</a>
              </div>
            </div>
          </div>

      <!-- Profile Avatar -->
      <?php include '../avatar_component.php'; ?>

        </div>
      </header>
  <div class="page">
    <header class="page-header">
      <h1>Settings</h1>
    </header>

    <main class="page-content">
      <section class="card profile-card" aria-labelledby="profile-title">
        <h2 id="profile-title" class="card-title">Profile Information</h2>

        <form id="profileForm" class="form-grid" novalidate method="post" action="save_profile.php">
          <div class="form-row">
            <label class="field">
              <span class="field-label">First Name</span>
              <input id="firstName" name="firstName" type="text" value="<?php echo htmlspecialchars($first_name); ?>" placeholder="First Name">
            </label>

            <label class="field">
              <span class="field-label">Last Name</span>
              <input id="lastName" name="lastName" type="text" value="<?php echo htmlspecialchars($last_name); ?>" placeholder="Last Name">
            </label>
          </div>

          <label class="field">
            <span class="field-label">Email</span>
            <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="your@batstate-u.edu.ph">
          </label>

          <label class="field">
            <span class="field-label">Phone Number</span>
            <input id="phone" name="phone" type="tel" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Your phone number">
          </label>

          <div class="form-actions">
            <button type="submit" id="btnSave" class="btn btn-save">Save Changes</button>
          </div>
        </form>
      </section>

      <section class="card profile-card" aria-labelledby="security-title">
        <h2 id="security-title" class="card-title">Security</h2>
        <form id="securityForm" class="form-grid" novalidate>
          <div class="form-row">
            <label class="field">
              <span class="field-label">Current password</span>
              <input id="currentPassword" name="currentPassword" type="password" placeholder="Current password" required>
            </label>

            <label class="field">
              <span class="field-label">New password</span>
              <input id="newPassword" name="newPassword" type="password" placeholder="New password (min 8 chars)" required>
            </label>
          </div>

          <label class="field">
            <span class="field-label">Confirm new password</span>
            <input id="confirmPassword" name="confirmPassword" type="password" placeholder="Confirm new password" required>
          </label>

          <div class="form-actions">
            <button type="submit" id="changePasswordBtn" class="btn btn-save">Change Password</button>
          </div>
        </form>
      </section>
    </main>
  </div>

  <!-- Toast -->
  <div id="toast" class="toast" role="status" aria-live="polite" hidden>
    <div id="toastMessage">Saved</div>
  </div>

  <?php if (!empty($_SESSION['toast_message'])): ?>
    <script>
      // show simple toast via small inline script
      (function(){
        const t = document.getElementById('toast');
        const m = document.getElementById('toastMessage');
        m.textContent = <?php echo json_encode($_SESSION['toast_message']); ?>;
        t.hidden = false;
        setTimeout(()=> t.hidden = true, 2500);
      })();
    </script>
  <?php unset($_SESSION['toast_message']); endif; ?>

  <script src="../avatar_dropdown.js"></script>
  <script src="dashboard.js"></script>
</body>
</html>

