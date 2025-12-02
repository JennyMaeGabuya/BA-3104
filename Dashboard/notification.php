<?php
/**
 * Notifications Page
 * Requires authentication - redirects to login if not logged in
 */

require_once '../auth_check.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — Notifications</title>
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

    <a href="notification.php" class="nav-item nav-item--active">
        <span class="nav-icon" aria-hidden="true">🔔</span>
        <span class="nav-label">Notifications</span>
        <span class="nav-badge" id="sidebarBadge">4</span>
    </a>

    <a href="settings.php" class="nav-item">
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
              <span class="topbar-badge" id="topbarBadge">4</span>
            </button>

            <!-- Notification Dropdown -->
            <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
              <div class="notification-dropdown-header">
                <h3>Notifications</h3>
              </div>
              <div class="notification-dropdown-list">
                <div class="notification-dropdown-item">
                  <div class="notification-dropdown-content">
                    <div class="notification-dropdown-title">Your report #LR-003 is pending admin approval (includes photo)</div>
                    <div class="notification-dropdown-time">30 minutes ago</div>
                  </div>
                </div>
                
                <div class="notification-dropdown-item">
                  <div class="notification-dropdown-content">
                    <div class="notification-dropdown-title">Your lost item report #LR-001 has been verified</div>
                    <div class="notification-dropdown-time">2 hours ago</div>
                  </div>
                </div>
                
                <div class="notification-dropdown-item">
                  <div class="notification-dropdown-content">
                    <div class="notification-dropdown-title">A matching item was found for your report #LR-001</div>
                    <div class="notification-dropdown-time">5 hours ago</div>
                  </div>
                </div>
                
                <div class="notification-dropdown-item">
                  <div class="notification-dropdown-content">
                    <div class="notification-dropdown-title">Please claim your item within 7 days</div>
                    <div class="notification-dropdown-time">1 day ago</div>
                  </div>
                </div>
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

      <!-- PAGE BODY -->
      <div class="page-body">
        <section class="card">
          <h2 class="page-heading">Notifications</h2>
          
          <div class="notifications-page-list">
            <div class="notification-page-item">
              <div class="notification-page-icon notification-page-icon--blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h11z"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              </div>
              <div class="notification-page-content">
                <div class="notification-page-title">Your report #LR-003 is pending admin approval (includes photo)</div>
                <div class="notification-page-time">30 minutes ago</div>
              </div>
            </div>
            
            <div class="notification-page-item">
              <div class="notification-page-icon notification-page-icon--green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h11z"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              </div>
              <div class="notification-page-content">
                <div class="notification-page-title">Your lost item report #LR-001 has been verified</div>
                <div class="notification-page-time">2 hours ago</div>
              </div>
            </div>
            
            <div class="notification-page-item">
              <div class="notification-page-icon notification-page-icon--blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h11z"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              </div>
              <div class="notification-page-content">
                <div class="notification-page-title">A matching item was found for your report #LR-001</div>
                <div class="notification-page-time">5 hours ago</div>
              </div>
            </div>
            
            <div class="notification-page-item">
              <div class="notification-page-icon notification-page-icon--yellow">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h11z"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              </div>
              <div class="notification-page-content">
                <div class="notification-page-title">Please claim your item within 7 days</div>
                <div class="notification-page-time">1 day ago</div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <script src="../avatar_dropdown.js"></script>
  <script src="dashboard.js"></script>
</body>
</html>

