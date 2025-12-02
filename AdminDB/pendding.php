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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt Admin — Pending Approval</title>

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
            <p class="muted">Review and verify reports with photos before posting</p>
          </div>
        </section>

        <?php
        // CSRF token setup and report aggregation for tabs
        if (empty($_SESSION['admin_csrf'])) {
          $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
        }
        $csrf = $_SESSION['admin_csrf'];

        $statusLabels = [
          'pending' => 'Pending',
          'verified' => 'Verified',
          'rejected' => 'Rejected',
        ];
        $statusCounts = array_fill_keys(array_keys($statusLabels), 0);
        $reports = [];

        try {
          $placeholders = implode(',', array_fill(0, count($statusLabels), '?'));
          $sql = "SELECT 'Lost' AS type, lr.report_id, lr.item_name, lr.category, lr.description, lr.location AS location, lr.date_lost AS date_event,
                         lr.photo_path, lr.created_at, lr.status, u.email AS reporter_email
                    FROM lost_reports lr
                    JOIN users u ON lr.user_id = u.id
                    WHERE lr.status IN ($placeholders)
                  UNION ALL
                  SELECT 'Found' AS type, fr.report_id, fr.item_name, fr.category, fr.description, fr.location_found AS location, fr.date_found AS date_event,
                         fr.photo_path, fr.created_at, fr.status, u.email AS reporter_email
                    FROM found_reports fr
                    JOIN users u ON fr.user_id = u.id
                    WHERE fr.status IN ($placeholders)
                  ORDER BY created_at DESC";
          $params = array_merge(array_values($statusLabels), array_values($statusLabels));
          $stmt = $pdo->prepare($sql);
          $stmt->execute($params);
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = strtolower($row['status'] ?? 'pending');
            if (!isset($statusCounts[$key])) {
              $statusCounts[$key] = 0;
            }
            $statusCounts[$key] += 1;
            $row['status_key'] = $key;
            $reports[] = $row;
          }
        } catch (Throwable $e) {
          echo '<div class="card"><div class="card-inner" style="color:#ef4444">Failed to load reports.</div></div>';
        }

        $initialTab = 'pending';
        foreach (array_keys($statusLabels) as $candidate) {
          if (($statusCounts[$candidate] ?? 0) > 0) {
            $initialTab = $candidate;
            break;
          }
        }
        $hasReports = count($reports) > 0;
        ?>

        <!-- tabs -->
        <div class="tabs-wrap">
          <div class="tabs" role="tablist" aria-label="Report states">
            <button type="button" class="tab <?= $initialTab === 'pending' ? 'tab--active' : '' ?>" data-tab="pending" aria-controls="adminStatusList" aria-pressed="<?= $initialTab === 'pending' ? 'true' : 'false' ?>">
              Pending (<?= htmlspecialchars($statusCounts['pending'] ?? 0) ?>)
            </button>
            <button type="button" class="tab <?= $initialTab === 'verified' ? 'tab--active' : '' ?>" data-tab="verified" aria-controls="adminStatusList" aria-pressed="<?= $initialTab === 'verified' ? 'true' : 'false' ?>">
              Verified (<?= htmlspecialchars($statusCounts['verified'] ?? 0) ?>)
            </button>
            <button type="button" class="tab <?= $initialTab === 'rejected' ? 'tab--active' : '' ?>" data-tab="rejected" aria-controls="adminStatusList" aria-pressed="<?= $initialTab === 'rejected' ? 'true' : 'false' ?>">
              Rejected (<?= htmlspecialchars($statusCounts['rejected'] ?? 0) ?>)
            </button>
          </div>
        </div>

        <div id="adminEmptyState" class="admin-empty <?= $hasReports ? 'hidden' : '' ?>">
          No reports to review yet.
        </div>

        <div class="status-scroll <?= $hasReports ? '' : 'hidden' ?>">
        <div id="adminStatusList" class="report-list <?= $hasReports ? '' : 'hidden' ?>" data-default-tab="<?= htmlspecialchars($initialTab) ?>">
          <?php foreach ($reports as $rep):
            $imgPath = normalizeImagePath($rep['photo_path'] ?? '');
            $statusKey = $rep['status_key'] ?? 'pending';
            $visibleClass = ($statusKey === $initialTab) ? '' : ' hidden';
            $pillClass = ($rep['type'] === 'Found') ? 'pill-found' : 'pill-lost';
            $statusPill = 'pill-status pill-status--' . $statusKey;
            $description = trim($rep['description'] ?? '') ?: 'No description provided.';
          ?>
          <section class="card report-card<?= $visibleClass ?>" data-status-card data-status="<?= htmlspecialchars($statusKey) ?>">
            <div class="card-inner">
              <div class="report-image">
                <?php if ($imgPath): ?>
                  <img src="<?= htmlspecialchars($imgPath) ?>" alt="Photo of <?= htmlspecialchars($rep['item_name']) ?>" />
                <?php else: ?>
                  <div class="no-photo">No Photo</div>
                <?php endif; ?>
              </div>
              <div class="report-details">
                <div class="report-head">
                  <h3 class="item-title"><?= htmlspecialchars($rep['item_name']) ?></h3>
                  <div class="badges">
                    <span class="pill <?= $pillClass ?>"><?= htmlspecialchars($rep['type']) ?></span>
                    <span class="pill <?= $statusPill ?>"><?= htmlspecialchars($statusLabels[$statusKey] ?? ucfirst($statusKey)) ?></span>
                  </div>
                </div>

                <div class="meta-grid">
                  <div class="meta-column meta-column--left">
                    <div class="meta meta-report-id">
                      <div class="meta-label">Report ID</div>
                      <div class="meta-value meta-id"><?= htmlspecialchars($rep['report_id']) ?></div>
                    </div>
                    <div class="meta meta-email">
                      <div class="meta-label">User Email</div>
                      <div class="meta-value"><?= htmlspecialchars($rep['reporter_email'] ?? '—') ?></div>
                    </div>
                    <p class="desc"><?= nl2br(htmlspecialchars($description)) ?></p>
                  </div>
                  <div class="meta-column meta-column--right">
                    <div class="meta meta-category">
                      <div class="meta-label">Category</div>
                      <div class="meta-value"><?= htmlspecialchars($rep['category']) ?></div>
                    </div>
                    <div class="meta meta-location">
                      <div class="meta-label">Location</div>
                      <div class="meta-value"><?= htmlspecialchars($rep['location']) ?></div>
                    </div>
                    <div class="meta meta-date">
                      <div class="meta-label">Date</div>
                      <div class="meta-value"><?= htmlspecialchars($rep['date_event']) ?></div>
                    </div>
                  </div>
                </div>


                <?php if ($statusKey === 'pending'): ?>
                  <div class="actions">
                    <form method="post" action="update_report_status.php" style="display:inline-block">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="report_id" value="<?= htmlspecialchars($rep['report_id']) ?>">
                      <input type="hidden" name="action" value="approve">
                      <button class="btn btn-approve" onclick="return confirm('Verify this report?')">Verify</button>
                    </form>
                    <form method="post" action="update_report_status.php" style="display:inline-block">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="report_id" value="<?= htmlspecialchars($rep['report_id']) ?>">
                      <input type="hidden" name="action" value="reject">
                      <button class="btn btn-reject" onclick="return confirm('Reject this report?')">Reject</button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </section>
          <?php endforeach; ?>
        </div>
        </div>

        <!-- placeholder for multiple cards / empty states -->
        <div id="afterActionMsg" class="after-action hidden" aria-live="polite"></div>

      </div><!-- /.content -->
    </main>
  </div>


 <script src="admin.js"></script>

</body>
</html>
