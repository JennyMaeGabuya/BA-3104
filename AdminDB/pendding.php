<?php
// Ensure admin pages use a dedicated session name to avoid collisions with regular user sessions
session_name('ADMINSESSID');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/../db_config.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['user_type'] ?? '') !== 'Admin')) {
  header('Location: ../login.php');
  exit;
}
// ensure this session id matches an active admin session
$session_id = session_id();
try {
  $check = $pdo->prepare('SELECT admin_id,is_active FROM adminsessions WHERE session_id = ? LIMIT 1');
  $check->execute([$session_id]);
  $row = $check->fetch();
  if (!$row || !$row['is_active'] || intval($row['admin_id']) !== intval($_SESSION['user_id'])) {
    header('Location: ../logout.php');
    exit;
  }
} catch (Exception $e) {
  header('Location: ../logout.php');
  exit;
}

$first = $_SESSION['first_name'] ?? '';
$last = $_SESSION['last_name'] ?? '';
$initials = strtoupper((strlen($first) ? $first[0] : '') . (strlen($last) ? $last[0] : ''));
$fullname = trim(($first ?: '') . ' ' . ($last ?: ''));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt Admin — Pending Approval</title>

  <!-- Inter font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body>

  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-top">
       <a href="#" class="brand">
        <div class="logo" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <path d="M12 3v18"></path>
          </svg>
        </div>
        <div class="brand-text">
          <div class="brand-title">FindIt Admin</div>
          <div class="brand-sub">@BatStateU</div>
        </div>
      </a>

      <nav class="nav" aria-label="Admin Navigation">
        <a href="admin.php" class="nav-item" data-section="overview">
          <span class="nav-ico"></span>
          <span class="nav-label">Overview</span>
        </a>

        <a href="pendding.php" class="nav-item nav-item--active" data-section="pending">
          <span class="nav-ico"></span>
          <span class="nav-label">Pending Approval</span>
        </a>

        <a href="verified.php" class="nav-item" data-section="verified">
          <span class="nav-ico"></span>
          <span class="nav-label">Verified Reports</span>
        </a>

        <a href="users.php" class="nav-item" data-section="users">
          <span class="nav-ico"></span>
          <span class="nav-label">Users</span>
        </a>

        <a href="settings.php" class="nav-item" data-section="settings">
          <span class="nav-ico"></span>
          <span class="nav-label">Settings</span>
        </a>
      </nav>
    </aside>

    <!-- MAIN -->
    <div class="main">
      <header class="topbar">
        <div class="topbar-left">
          <button id="menuToggle" class="menu-toggle" aria-label="Toggle menu">☰</button>
          <div class="page-titles">
            <h1 class="page-title">Admin Dashboard</h1>
            <p class="page-sub">Manage lost and found reports</p>
          </div>
        </div>

        <div class="topbar-right">
          <div class="avatar" id="adminAvatar" title="<?=htmlspecialchars($fullname, ENT_QUOTES)?>"><?=htmlspecialchars($initials)?></div>

          <div class="avatar-dropdown" id="avatarDropdown" aria-hidden="true">
            <div class="avatar-fullname"><?=htmlspecialchars($fullname)?></div>
            <div class="avatar-actions">
              <button id="themeToggle" class="btn">Toggle dark / light</button>
              <a href="settings.php" class="btn">Settings</a>
              <a href="../logout.php?scope=admin" class="btn">Logout</a>
            </div>
          </div>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <div class="content">

        <!-- Section heading + small description -->
        <section class="section-header">
          <div>
            <h2>Pending Approval</h2>
            <p class="muted">Review and approve reports with photos before posting</p>
          </div>
        </section>

        <!-- tabs -->
        <div class="tabs-wrap">
          <div class="tabs" role="tablist" aria-label="Report states">
            <button class="tab tab--active" data-tab="pending">Pending</button>
            <button class="tab" data-tab="approved">Approved</button>
            <button class="tab" data-tab="rejected">Rejected</button>
          </div>
        </div>

        <!-- Report card -->
        <section class="card report-card" id="reportCard">
          <div class="card-inner">
            <!-- left: image -->
            <div class="report-image">
              <!-- use uploaded image path as report photo -->
              <img src="/mnt/data/a90fc6b1-3e9b-450c-aaa7-48408adf3698.png" alt="Black backpack">
            </div>

            <!-- right: details -->
            <div class="report-details">
              <div class="report-head">
                <h3 class="item-title">Black Backpack with Laptop</h3>
                <div class="badges">
                  <span class="pill pill-lost">Lost</span>
                  <span class="pill pill-requires">Requires Approval</span>
                </div>
              </div>

              <div class="meta-grid">
                <div class="meta">
                  <div class="meta-label">Report ID</div>
                  <div class="meta-value meta-id">LR-006</div>
                </div>

                <div class="meta">
                  <div class="meta-label">Category</div>
                  <div class="meta-value">Bag</div>
                </div>

                <div class="meta">
                  <div class="meta-label">Reported by</div>
                  <div class="meta-value">Anna Reyes</div>
                </div>

                <div class="meta">
                  <div class="meta-label">Date</div>
                  <div class="meta-value">2025-11-13</div>
                </div>

                <div class="meta">
                  <div class="meta-label">Location</div>
                  <div class="meta-value">Cafeteria</div>
                </div>
              </div>

              <!-- admin notice -->
              <div class="admin-note" role="status">
                <strong>Admin Action Required:</strong> This report includes a photo and requires your approval before it can be posted publicly.
              </div>

              <!-- actions -->
              <div class="actions">
                <button class="btn btn-approve" id="approveBtn">Approve</button>
                <button class="btn btn-reject" id="rejectBtn">Reject</button>
              </div>
            </div>
          </div>
        </section>

        <!-- placeholder for multiple cards / empty states -->
        <div id="afterActionMsg" class="after-action hidden" aria-live="polite"></div>

      </div><!-- /.content -->
    </main>
  </div>


 <script src="admin.js"></script>

</body>
</html>
