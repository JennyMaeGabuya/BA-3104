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
// validate adminsessions
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
  <title>FindIt Admin — Settings</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <div class="app">
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
        <a href="pendding.php" class="nav-item" data-section="pending">
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
        <a href="settings.php" class="nav-item nav-item--active" data-section="settings">
          <span class="nav-ico"></span>
          <span class="nav-label">Settings</span>
        </a>
      </nav>
    </aside>
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
      <main class="content">
        <h2 class="page-heading">Admin Settings</h2>
        <section class="settings-card card">
          <form id="settingsForm">
            <fieldset class="fieldset">
              <legend class="legend">System Configuration</legend>
              <label class="label" for="autoVerify">Auto-verification threshold (days)</label>
              <input id="autoVerify" name="autoVerify" type="number" min="0" step="1" value="3" class="input">
              <label class="label" for="claimDeadline">Claim deadline (days)</label>
              <input id="claimDeadline" name="claimDeadline" type="number" min="0" step="1" value="30" class="input">
            </fieldset>
            <fieldset class="fieldset">
              <legend class="legend">Notification Settings</legend>
              <label class="checkbox">
                <input id="emailReports" name="emailReports" type="checkbox" checked>
                <span>Send email on new reports</span>
              </label>
              <label class="checkbox">
                <input id="dailySummary" name="dailySummary" type="checkbox" checked>
                <span>Send daily summary</span>
              </label>
            </fieldset>
            <div class="actions">
              <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
          </form>
        </section>
      </main>
    </div>
  </div>
  <script src="admin.js"></script>
</body>
</html>
