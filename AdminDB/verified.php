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

$claims = [];
$placeholderImg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><rect width='100%' height='100%' fill='%23FFF9F2'/><circle cx='32' cy='32' r='20' fill='%23FFDCE0'/></svg>";

function normalizeImage(?string $path): string {
  if (!$path) return '';
  $trim = trim($path);
  if (str_starts_with($trim, '/')) return $trim;
  return '/BA-3104/' . ltrim($trim, '/');
}

try {
  $sql = "SELECT cr.request_code, cr.report_id, cr.item_type, cr.contact_info, cr.details, cr.status,
                  cr.created_at,
                  fr.item_name AS found_item, fr.location_found AS found_location, fr.date_found AS found_date,
                  fr.photo_path AS found_photo,
                  CONCAT(u.first_name, ' ', u.last_name) AS requester_name
            FROM claim_requests cr
            LEFT JOIN found_reports fr ON cr.report_id = fr.report_id
            LEFT JOIN users u ON cr.user_id = u.id
            ORDER BY cr.created_at DESC";
  $stmt = $pdo->query($sql);
  $claims = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $claims = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt Admin — Manage Items</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BA-3104/AdminDB/admin.css">
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
        <a href="verified.php" class="nav-item nav-item--active" data-section="verified">
          <span class="nav-ico"></span>
          <span class="nav-label">Manage Items</span>
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
      <main class="page-body">
        <section class="table-card">
          <div class="table-header"><div class="table-title">Manage Items</div></div>
          <div class="table-wrap">
            <table class="reports-table" aria-label="Manage items">
              <thead><tr><th>Image</th><th>User</th><th>Type</th><th>Item</th><th>Location</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody id="reportsTbody">
                <?php if (empty($claims)): ?>
                  <tr>
                    <td colspan="7" class="empty-row">No claim requests submitted yet.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($claims as $claim):
                    $itemName = $claim['found_item'] ?? 'Item';
                    $location = $claim['found_location'] ?? '—';
                    $date = $claim['found_date'] ?? '—';
                    $userName = $claim['requester_name'] ?? 'Unknown User';
                    $badgeClass = strtolower($claim['item_type'] ?? '') === 'lost' ? 'badge-lost' : 'badge-found';
                    $imgPath = normalizeImage($claim['found_photo'] ?? '') ?: $placeholderImg;
                    $statusLabel = $claim['status'] ?? 'Pending';
                  ?>
                  <tr>
                    <td class="td-thumb"><img src="<?= htmlspecialchars($imgPath) ?>" alt="Photo of <?= htmlspecialchars($itemName) ?>"></td>
                    <td class="td-user"><?= htmlspecialchars($userName) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($claim['item_type'] ?? 'Found') ?></span></td>
                    <td><?= htmlspecialchars($itemName) ?></td>
                    <td><?= htmlspecialchars($location) ?></td>
                    <td><?= htmlspecialchars($date) ?></td>
                    <td>
                      <div class="actions-col" data-request="<?= htmlspecialchars($claim['request_code']) ?>">
                        <button type="button" class="action-btn view" data-claim='<?= json_encode($claim, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>'>View</button>
                        <button type="button" class="action-btn claim" data-action="claimed">Mark as Claim</button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>
  </div>
  <script>
    window.CLAIM_UPDATE_ENDPOINT = '/BA-3104/AdminDB/update_claim_status.php';
  </script>
  <script src="admin.js"></script>
</body>
</html>
