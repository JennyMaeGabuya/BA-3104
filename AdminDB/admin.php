  <?php
// Ensure admin pages use a dedicated session name to avoid collisions with regular user sessions
session_name('ADMINSESSID');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
// Protect admin area: verify role and that current session id exists in adminsessions
require_once __DIR__ . '/../db_config.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['user_type'] ?? '') !== 'Admin')) {
  header('Location: ../login.php');
  exit;
}
// validate session against adminsessions table to avoid session overwrite issues
$session_id = session_id();
try {
  $check = $pdo->prepare('SELECT admin_id,is_active FROM adminsessions WHERE session_id = ? LIMIT 1');
  $check->execute([$session_id]);
  $row = $check->fetch();
  if (!$row || !$row['is_active'] || intval($row['admin_id']) !== intval($_SESSION['user_id'])) {
    // session does not match an active admin session — force logout
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

function normalizeImagePath(?string $path): string {
  if (!$path) return '';
  $trim = trim($path);
  if ($trim === '') return '';
  if (str_starts_with($trim, 'Image/')) {
    $trim = 'Dashboard/' . $trim;
  }
  if (preg_match('/^https?:\/\//', $trim) || str_starts_with($trim, '/')) {
    return $trim;
  }
  return '/BA-3104/' . ltrim($trim, '/');
}

function countReports(PDO $pdo, ?array $statuses = null): int {
  $where = '';
  $params = [];
  if (!empty($statuses)) {
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $where = "WHERE status IN ($placeholders)";
    $params = $statuses;
  }

  $lostStmt = $pdo->prepare("SELECT COUNT(*) FROM lost_reports $where");
  $lostStmt->execute($params);
  $lostCount = (int)$lostStmt->fetchColumn();

  $foundStmt = $pdo->prepare("SELECT COUNT(*) FROM found_reports $where");
  $foundStmt->execute($params);
  $foundCount = (int)$foundStmt->fetchColumn();

  return $lostCount + $foundCount;
}

function fetchRecentReports(PDO $pdo, int $limit = 6): array {
  $sql = "SELECT * FROM (
            SELECT lr.report_id, lr.item_name, lr.category, lr.location AS location, lr.date_lost AS date_event,
                   lr.status, lr.photo_path, CONCAT(u.first_name, ' ', u.last_name) AS reporter_name,
                   'Lost' AS report_type, lr.created_at
            FROM lost_reports lr
            JOIN users u ON lr.user_id = u.id
            UNION ALL
            SELECT fr.report_id, fr.item_name, fr.category, fr.location_found AS location, fr.date_found AS date_event,
                   fr.status, fr.photo_path, CONCAT(u.first_name, ' ', u.last_name) AS reporter_name,
                   'Found' AS report_type, fr.created_at
            FROM found_reports fr
            JOIN users u ON fr.user_id = u.id
          ) AS combined
          ORDER BY created_at DESC
          LIMIT :limit";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function statusClass(string $status): string {
  $map = [
    'Pending' => 'status-pending',
    'Verified' => 'status-verified',
    'Claimed' => 'status-claimed',
    'Rejected' => 'status-rejected',
  ];
  return $map[$status] ?? 'status-pending';
}

$activeReports = countReports($pdo);
$verifiedReports = countReports($pdo, ['Verified']);
$resolvedReports = countReports($pdo, ['Claimed']);
$pendingReports = countReports($pdo, ['Pending']);
$recentReports = fetchRecentReports($pdo, max(1, $activeReports));
$placeholderImg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><rect width='100%' height='100%' fill='%23F9FAFB'/><circle cx='32' cy='32' r='20' fill='%23E8F0FF'/></svg>";
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt Admin — Dashboard</title>

  <!-- Inter font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BA-3104/AdminDB/admin.css">
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
        <a href="admin.php" class="nav-item nav-item--active" data-section="overview">
          <span class="nav-ico"></span>
          <span class="nav-label">Overview</span>
        </a>

        <a href="pendding.php" class="nav-item" data-section="pending">
          <span class="nav-ico"></span>
          <span class="nav-label">Pending Approval</span>
        </a>

        <a href="verified.php" class="nav-item" data-section="verified">
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
              <a href="settings.html" class="btn">Settings</a>
              <a href="../logout.php?scope=admin" class="btn">Logout</a>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <!-- Metric cards -->
        <section class="metrics-grid" aria-label="Key metrics">
          <article class="metric-card">
            <div class="metric-icon metric-icon--doc" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>
            </div>
            <div class="metric-body">
              <div class="metric-value" id="activeReports"><?= htmlspecialchars($activeReports) ?></div>
              <div class="metric-label">Active Reports</div>
              <div class="metric-foot">+2% from last week</div>
            </div>
          </article>

          <article class="metric-card">
            <div class="metric-icon metric-icon--check" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>
            </div>
            <div class="metric-body">
              <div class="metric-value" id="verifiedItems"><?= htmlspecialchars($verifiedReports) ?></div>
              <div class="metric-label">Verified Items</div>
              <div class="metric-foot">+3% from last week</div>
            </div>
          </article>

          <article class="metric-card">
            <div class="metric-icon metric-icon--cube" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A78BFA" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="metric-body">
              <div class="metric-value" id="resolvedCases"><?= htmlspecialchars($resolvedReports) ?></div>
              <div class="metric-label">Resolved Cases</div>
              <div class="metric-foot">+0% from last week</div>
            </div>
          </article>

          <article class="metric-card">
            <div class="metric-icon metric-icon--warn" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.73 3h16.9a2 2 0 0 0 1.73-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            </div>
            <div class="metric-body">
              <div class="metric-value" id="pendingVerification"><?= htmlspecialchars($pendingReports) ?></div>
              <div class="metric-label">Pending Verification</div>
              <div class="metric-foot warning">Requires attention</div>
            </div>
          </article>
        </section>

<!-- Recent reports table -->
        <section class="table-card">
          <div class="table-header">
            <div class="table-title">Recent Reports</div>
          </div>

          <div class="table-wrap">
            <table class="reports-table" aria-label="Recent reports">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>User</th>
                  <th>Type</th>
                  <th>Item</th>
                  <th>Location</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>

              <tbody id="reportsTbody">
                <?php if (empty($recentReports)): ?>
                  <tr>
                    <td colspan="7" class="empty-row">No reports submitted yet.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentReports as $report):
                    $img = $report['photo_path'] ? normalizeImagePath($report['photo_path']) : $placeholderImg;
                    $typeClass = ($report['report_type'] === 'Found') ? 'badge-found' : 'badge-lost';
                    $status = $report['status'] ?? 'Pending';
                    $statusClass = statusClass($status);
                    $reportDate = $report['date_event'] ?? '';
                  ?>
                  <tr>
                    <td class="td-thumb">
                      <img src="<?= htmlspecialchars($img) ?>" alt="Photo of <?= htmlspecialchars($report['item_name']) ?>">
                    </td>
                    <td class="td-user"><?= htmlspecialchars($report['reporter_name'] ?? 'Unknown Reporter') ?></td>
                    <td><span class="badge <?= $typeClass ?>"><?= htmlspecialchars($report['report_type']) ?></span></td>
                    <td><?= htmlspecialchars($report['item_name']) ?></td>
                    <td><?= htmlspecialchars($report['location']) ?></td>
                    <td><?= htmlspecialchars($reportDate) ?></td>
                    <td><span class="status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

        </section>
      </div>
    </div>
  </div>

  <script src="admin.js"></script>
</body>
</html>
