<?php
/**
 * Dashboard Main Page
 * Requires authentication - redirects to login if not logged in
 */

require_once '../auth_check.php';

// Get user's first name for welcome message
$fullname = $_SESSION['fullname'] ?? $_SESSION['user_name'] ?? 'User';
$nameParts = explode(' ', trim($fullname));
$firstName = $nameParts[0] ?? 'User';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BA-3104/Dashboard/dashboard.css?v=1">
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
    
    <a href="dashboard.php" class="nav-item nav-item--active">
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
        <!-- greeting & subtitle -->
        <section class="welcome">
          <div>
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($firstName); ?>!</h1>
            <p class="welcome-sub">Here's what's happening with your reports.</p>
          </div>
        </section>

        <!-- Stats cards -->
        <section class="stats-grid" aria-label="Summary">
          <article class="stat-card">
            <div class="stat-icon stat-icon--blue" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
            </div>
            <div class="stat-body">
              <div class="stat-num">5</div>
              <div class="stat-label">Total Reports</div>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-icon stat-icon--green" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
            </div>
            <div class="stat-body">
              <div class="stat-num">2</div>
              <div class="stat-label">Verified</div>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-icon stat-icon--yellow" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5l3 3"></path><path d="M12 22C6 22 2 17.5 2 12.5S6 3 12 3s10 4.5 10 9.5S18 22 12 22z"></path></svg>
            </div>
            <div class="stat-body">
              <div class="stat-num">2</div>
              <div class="stat-label">Pending</div>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-icon stat-icon--red" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
            </div>
            <div class="stat-body">
              <div class="stat-num">1</div>
              <div class="stat-label">Claimed</div>
            </div>
          </article>
        </section>

 <!-- SEARCH / FILTERS + TABS -->
        <section class="search-panel">
          <div class="search-row">
            <div class="search-box">
              <span class="search-ic">🔍</span>
              <input id="searchInput" placeholder="Search items..." />
            </div>

            <select id="filterLocation" class="select">
              <option>All</option>
              <option>Library</option>
              <option>Gym</option>
              <option>Main Building</option>
            </select>

            <select id="filterType" class="select">
              <option>All types</option>
              <option>Lost</option>
              <option>Found</option>
            </select>

            <button id="applyBtn" class="btn primary">Apply</button>
          </div>

          <div class="tabs">
            <button class="tab active" data-tab="approved">Approved</button>
            <button class="tab" data-tab="rejected">Rejected</button>
          </div>
        </section>

        <!-- RESULTS / EMPTY CARD -->
        <section class="results-card">
          <div class="results-inner" id="resultsInner">
            <div class="empty-state" id="emptyState">
              No approved/rejected Lost and Found Items
            </div>

            <!-- Example list area (hidden if empty) -->
            <div id="listArea" class="list-area hidden">
              <!-- real items would be injected here -->
            </div>
          </div>
        </section>

        </section>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="../avatar_dropdown.js"></script>
  <script src="dashboard.js"></script>
</body>
</html>

