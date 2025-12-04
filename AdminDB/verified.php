<?php
// Ensure admin pages use a dedicated session name to avoid collisions with regular user sessions
session_name('ADMINSESSID');
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../matching_engine.php';
require_once __DIR__ . '/../match_workflow_helpers.php';
require_once __DIR__ . '/../matching_service.php';
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
$matchSummary = [
  'lost_reports' => [],
  'found_reports' => [],
  'matches' => [],
  'lost_lookup' => [],
  'found_lookup' => [],
];
$placeholderImg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><rect width='100%' height='100%' fill='%23FFF9F2'/><circle cx='32' cy='32' r='20' fill='%23FFDCE0'/></svg>";

if (function_exists('ensure_match_results_table')) {
  try {
    ensure_match_results_table($pdo);
  } catch (Throwable $e) {
    error_log('Unable to ensure match_results table: ' . $e->getMessage());
  }
}
if (function_exists('warm_claim_matches')) {
  try {
    warm_claim_matches($pdo, 10);
  } catch (Throwable $e) {
    error_log('Unable to warm claim matches: ' . $e->getMessage());
  }
}

function normalizeImage(?string $path): string {
  if (!$path) return '';
  $trim = trim($path);
  if (str_starts_with($trim, '/')) return $trim;
  return '/BA-3104/' . ltrim($trim, '/');
}

function formatEventDate(?string $value): string {
  if (!$value) {
    return '—';
  }
  try {
    $date = new DateTimeImmutable($value);
    return $date->format('M d, Y');
  } catch (Throwable $e) {
    return $value;
  }
}

function claimStatusLabel(?string $status): string {
  $normalized = strtolower(trim((string)$status));
  return match ($normalized) {
    'approved' => 'Claimable',
    'resolved' => 'Claimed',
    'rejected' => "Doesn't match",
    default => 'Pending review',
  };
}

function encodeMatchPayload(?array $match): string {
  if (!$match) {
    return '';
  }
  $payload = [
    'score' => $match['score'] ?? 0,
    'label' => $match['label'] ?? '',
    'reasons' => $match['reasons'] ?? [],
    'lost' => $match['lost_report'] ?? [],
    'found' => $match['found_report'] ?? [],
    'has_proof' => false,
  ];
  return htmlspecialchars(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
}

function encodeClaimMatchPayload(array $claim): string {
  $inline = calculate_inline_match($claim);
  $score = $inline['score'] ?? 0;
  $label = $score >= 80 ? 'Excellent Match' : ($score >= 60 ? 'Good Match' : ($score >= 40 ? 'Possible Match' : 'Weak Match'));
  $lost = [
    'report_id' => $claim['request_code'] ?? '',
    'item_name' => $claim['found_item'] ?? 'Item',
    'category' => $claim['item_type'] ?? 'Found',
    'description' => $claim['details'] ?? '',
    'location' => $claim['last_seen_location'] ?? '',
    'event_date' => $claim['created_at'] ?? '',
  ];
  $found = [
    'report_id' => $claim['report_id'] ?? '',
    'item_name' => $claim['found_item'] ?? 'Item',
    'category' => $claim['item_type'] ?? 'Found',
    'description' => $claim['found_description'] ?? '',
    'location' => $claim['found_location'] ?? '',
    'event_date' => $claim['found_date'] ?? '',
  ];
  $descPct = $inline['desc_pct'] ?? 0;
  $locPct = $inline['loc_pct'] ?? 0;
  $reasons = [
    'Description overlap: ' . $descPct . '%',
    'Last seen vs found location: ' . $locPct . '%',
  ];
  $payload = [
    'score' => $score,
    'label' => $label,
    'reasons' => $reasons,
    'lost' => $lost,
    'found' => $found,
    'last_seen' => $claim['last_seen_location'] ?? '',
    'id_photo' => $claim['id_photo_url'] ?? '',
    'has_proof' => true,
  ];
  return htmlspecialchars(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
}

try {
  $sql = "SELECT cr.request_code, cr.report_id, cr.item_type, cr.contact_info, cr.details, cr.status,
                  cr.created_at, cr.last_seen_location, cr.id_photo_path,
                  fr.item_name AS found_item, fr.location_found AS found_location, fr.date_found AS found_date,
                  fr.description AS found_description,
                  fr.photo_path AS found_photo,
                  best_match.confidence AS match_confidence,
                  best_match.classification AS match_classification,
                  best_match.status AS match_status,
                  best_match.breakdown AS match_breakdown,
                  fr_match.report_id AS match_report_code,
                  fr_match.item_name AS match_item_name,
                  fr_match.location_found AS match_location,
                  fr_match.photo_path AS match_photo,
                  CONCAT(u.first_name, ' ', u.last_name) AS requester_name
            FROM claim_requests cr
            LEFT JOIN found_reports fr ON cr.report_id = fr.report_id
            LEFT JOIN users u ON cr.user_id = u.id
            LEFT JOIN match_results best_match ON best_match.id = (
              SELECT mr2.id
              FROM match_results mr2
              WHERE mr2.claim_request_id = cr.id
              ORDER BY (mr2.classification = 'probable') DESC, mr2.confidence DESC, mr2.updated_at DESC, mr2.id DESC
              LIMIT 1
            )
            LEFT JOIN found_reports fr_match ON fr_match.id = best_match.found_report_id
            WHERE cr.status <> 'Resolved'
            ORDER BY cr.created_at DESC";
  $stmt = $pdo->query($sql);
  $claims = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $claims = [];
}

try {
  $matchSummary = compute_verified_matches($pdo);
} catch (Throwable $e) {
  $matchSummary = [
    'lost_reports' => [],
    'found_reports' => [],
    'matches' => [],
    'lost_lookup' => [],
    'found_lookup' => [],
  ];
}
$workflowMap = [];
try {
  $workflowMap = fetch_match_workflow_map($pdo);
} catch (Throwable $e) {
  $workflowMap = [];
}
$lostVerified = $matchSummary['lost_reports'];
$foundVerified = $matchSummary['found_reports'];
$lostLookup = $matchSummary['lost_lookup'];
$foundLookup = $matchSummary['found_lookup'];
$lostCount = count($lostVerified);
$foundCount = count($foundVerified);
$claimCount = count($claims);

function calculate_inline_match(array $claim): array {
  $details = strtolower(trim((string)($claim['details'] ?? '')));
  $lastSeen = strtolower(trim((string)($claim['last_seen_location'] ?? '')));
  $foundDesc = strtolower(trim((string)($claim['found_description'] ?? '')));
  $foundName = strtolower(trim((string)($claim['found_item'] ?? '')));
  $foundLoc = strtolower(trim((string)($claim['found_location'] ?? '')));

  $descSource = $details;
  $descTarget = $foundDesc ?: ($foundName ?: '');
  $descSim = jaccard_inline($descSource, $descTarget);
  $locSource = $lastSeen ?: $details;
  $locSim = jaccard_inline($locSource, $foundLoc);

  $score = (int)round(min(1.0, $descSim) * 60 + min(1.0, $locSim) * 40);

  return [
    'score' => max(0, min(100, $score)),
    'desc_pct' => (int)round($descSim * 100),
    'loc_pct' => (int)round($locSim * 100),
  ];
}

function inline_claim_score(array $claim): ?int {
  $result = calculate_inline_match($claim);
  return $result['score'];
}

function jaccard_inline(string $a, string $b): float {
  $ta = tokenize_inline($a);
  $tb = tokenize_inline($b);
  if (!$ta || !$tb) return 0.0;
  $inter = count(array_intersect($ta, $tb));
  $union = count(array_unique(array_merge($ta, $tb)));
  return $union ? ($inter / $union) : 0.0;
}

function tokenize_inline(string $v): array {
  $v = strtolower($v);
  $v = preg_replace('/[^a-z0-9\s]+/', ' ', $v);
  $v = preg_replace('/\s+/', ' ', $v);
  $v = trim($v);
  if ($v === '') return [];
  return array_values(array_filter(explode(' ', $v)));
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
      <div class="content">
        <section class="section-header">
          <div>
            <h2>Manage Items</h2>
            <p class="muted">Review verified reports, compare them with automatic matches, and resolve claim requests.</p>
          </div>
        </section>

        <div class="management-stats" role="region" aria-label="Summary counts">
          <article class="stat-chip" role="button" tabindex="0" aria-pressed="false" aria-controls="panel-lost" data-filter-target="lost">
            <div class="stat-chip__label">Verified Lost</div>
            <div class="stat-chip__value"><?= $lostCount ?></div>
            <div class="stat-chip__hint">Awaiting owner confirmation</div>
          </article>
          <article class="stat-chip" role="button" tabindex="0" aria-pressed="false" aria-controls="panel-found" data-filter-target="found">
            <div class="stat-chip__label">Verified Found</div>
            <div class="stat-chip__value"><?= $foundCount ?></div>
            <div class="stat-chip__hint">Ready for release</div>
          </article>
          <article class="stat-chip" role="button" tabindex="0" aria-pressed="false" aria-controls="panel-claims" data-filter-target="claims">
            <div class="stat-chip__label">Claim Requests</div>
            <div class="stat-chip__value"><?= $claimCount ?></div>
            <div class="stat-chip__hint">Need admin action</div>
          </article>
          <article class="stat-chip stat-chip--reset" role="button" tabindex="0" aria-pressed="false" data-filter-target="all">
            <div class="stat-chip__label">Show All Panels</div>
            <div class="stat-chip__value">&infin;</div>
            <div class="stat-chip__hint">Reset current filter</div>
          </article>
        </div>

        <div class="management-grid" data-panel-filter="manage-items">
          <section class="management-panel" id="panel-lost" data-panel-type="lost" aria-labelledby="lostHeading">
            <div class="panel-header">
              <div>
                <h2 id="lostHeading">Verified Lost Reports</h2>
                <p class="muted">Confirmed lost reports waiting for a found match.</p>
              </div>
              <span class="panel-count"><?= $lostCount ?> item(s)</span>
            </div>
            <?php if (empty($lostVerified)): ?>
              <div class="admin-empty admin-empty--panel">No verified lost reports yet.</div>
            <?php else: ?>
              <div class="panel-scroll">
                <div class="report-grid">
                  <?php foreach ($lostVerified as $lost):
                    $bestMatch = summarize_best_match($lostLookup, $lost['report_id']);
                    $img = $lost['photo_url'] ?: $placeholderImg;
                    $matchPayload = $bestMatch ? encodeMatchPayload($bestMatch) : '';
                    $description = trim((string)($lost['description'] ?? '')) !== '' ? $lost['description'] : 'No description provided.';
                    $pairKey = $bestMatch ? (($lost['report_id'] ?? '') . '|' . ($bestMatch['found_report']['report_id'] ?? '')) : '';
                    $workflowStatus = $pairKey && isset($workflowMap[$pairKey]) ? $workflowMap[$pairKey] : 'pending';
                  ?>
                  <article class="report-tile" data-report="<?= htmlspecialchars($lost['report_id'] ?? '') ?>">
                    <div class="report-tile__thumb">
                      <?php if ($img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Photo of <?= htmlspecialchars($lost['item_name'] ?? 'item') ?>">
                      <?php else: ?>
                        <div class="no-photo">No Photo</div>
                      <?php endif; ?>
                    </div>
                    <div class="report-tile__body">
                      <div class="report-tile__row">
                        <h3><?= htmlspecialchars($lost['item_name'] ?? 'Item') ?></h3>
                        <div class="badges">
                          <span class="pill pill-lost">Lost</span>
                          <span class="pill pill-status pill-status--verified">Verified</span>
                        </div>
                      </div>
                      <dl class="report-tile__meta">
                        <div><span>ID</span><?= htmlspecialchars($lost['report_id'] ?? '—') ?></div>
                        <div><span>Category</span><?= htmlspecialchars($lost['category'] ?? '—') ?></div>
                        <div><span>Last seen</span><?= htmlspecialchars($lost['location'] ?? '—') ?></div>
                        <div><span>Date lost</span><?= htmlspecialchars(formatEventDate($lost['event_date'] ?? null)) ?></div>
                      </dl>
                      <p class="report-tile__desc"><?= nl2br(htmlspecialchars($description)) ?></p>
                      <?php if ($bestMatch): ?>
                        <div class="report-tile__note">
                          <strong>Suggested match:</strong> <?= htmlspecialchars($bestMatch['label']) ?> (<?= htmlspecialchars((string)$bestMatch['score']) ?>%) with <?= htmlspecialchars($bestMatch['found_report']['item_name'] ?? 'found report') ?>.
                        </div>
                        <div class="report-tile__actions">
                          <button type="button" class="btn btn-primary" data-match="<?= $matchPayload ?>">View Match Details</button>
                        </div>
                        <div class="match-workflow" data-workflow data-lost-id="<?= htmlspecialchars($lost['report_id'] ?? '') ?>" data-found-id="<?= htmlspecialchars($bestMatch['found_report']['report_id'] ?? '') ?>" data-status="<?= htmlspecialchars($workflowStatus) ?>">
                          <div class="workflow-head">
                            <div class="workflow-status">
                              <span class="workflow-label">Match workflow</span>
                              <span class="workflow-pill workflow-pill--<?= htmlspecialchars($workflowStatus) ?>">
                                <?= htmlspecialchars(match_workflow_label($workflowStatus)) ?>
                              </span>
                            </div>
                            <span class="workflow-ref">Lost <?= htmlspecialchars($lost['report_id'] ?? 'LR-?') ?> · Found <?= htmlspecialchars($bestMatch['found_report']['report_id'] ?? 'FR-?') ?></span>
                          </div>
                          <p class="workflow-hint" data-workflow-hint>Review the details before choosing an action.</p>
                          <div class="workflow-buttons">
                            <button type="button" class="workflow-btn workflow-btn--primary" data-workflow-action="claimable">Mark as Claimable</button>
                            <button type="button" class="workflow-btn workflow-btn--accent" data-workflow-action="claimed">Mark as Claimed</button>
                            <button type="button" class="workflow-btn workflow-btn--danger" data-workflow-action="reject">Doesn't Match</button>
                          </div>
                        </div>
                      <?php else: ?>
                        <p class="muted report-tile__note">No automatic match yet.</p>
                      <?php endif; ?>
                    </div>
                  </article>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </section>

          <section class="management-panel" id="panel-found" data-panel-type="found" aria-labelledby="foundHeading">
            <div class="panel-header">
              <div>
                <h2 id="foundHeading">Verified Found Reports</h2>
                <p class="muted">Found items ready for release once ownership is confirmed.</p>
              </div>
              <span class="panel-count"><?= $foundCount ?> item(s)</span>
            </div>
            <?php if (empty($foundVerified)): ?>
              <div class="admin-empty admin-empty--panel">No verified found reports yet.</div>
            <?php else: ?>
              <div class="panel-scroll">
                <div class="report-grid">
                  <?php foreach ($foundVerified as $found):
                    $bestMatch = summarize_best_match($foundLookup, $found['report_id']);
                    $img = $found['photo_url'] ?: $placeholderImg;
                    $matchPayload = $bestMatch ? encodeMatchPayload($bestMatch) : '';
                    $description = trim((string)($found['description'] ?? '')) !== '' ? $found['description'] : 'No description provided.';
                    $pairKey = $bestMatch ? (($bestMatch['lost_report']['report_id'] ?? '') . '|' . ($found['report_id'] ?? '')) : '';
                    $workflowStatus = $pairKey && isset($workflowMap[$pairKey]) ? $workflowMap[$pairKey] : 'pending';
                  ?>
                  <article class="report-tile" data-report="<?= htmlspecialchars($found['report_id'] ?? '') ?>">
                    <div class="report-tile__thumb">
                      <?php if ($img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="Photo of <?= htmlspecialchars($found['item_name'] ?? 'item') ?>">
                      <?php else: ?>
                        <div class="no-photo">No Photo</div>
                      <?php endif; ?>
                    </div>
                    <div class="report-tile__body">
                      <div class="report-tile__row">
                        <h3><?= htmlspecialchars($found['item_name'] ?? 'Item') ?></h3>
                        <div class="badges">
                          <span class="pill pill-found">Found</span>
                          <span class="pill pill-status pill-status--verified">Verified</span>
                        </div>
                      </div>
                      <dl class="report-tile__meta">
                        <div><span>ID</span><?= htmlspecialchars($found['report_id'] ?? '—') ?></div>
                        <div><span>Category</span><?= htmlspecialchars($found['category'] ?? '—') ?></div>
                        <div><span>Found at</span><?= htmlspecialchars($found['location'] ?? '—') ?></div>
                        <div><span>Date found</span><?= htmlspecialchars(formatEventDate($found['event_date'] ?? null)) ?></div>
                      </dl>
                      <p class="report-tile__desc"><?= nl2br(htmlspecialchars($description)) ?></p>
                      <?php if ($bestMatch): ?>
                        <div class="report-tile__note">
                          <strong>Suggested owner:</strong> <?= htmlspecialchars($bestMatch['label']) ?> (<?= htmlspecialchars((string)$bestMatch['score']) ?>%) with <?= htmlspecialchars($bestMatch['lost_report']['item_name'] ?? 'lost report') ?>.
                        </div>
                        <div class="report-tile__actions">
                          <button type="button" class="btn btn-primary" data-match="<?= $matchPayload ?>">View Match Details</button>
                        </div>
                        <div class="match-workflow" data-workflow data-lost-id="<?= htmlspecialchars($bestMatch['lost_report']['report_id'] ?? '') ?>" data-found-id="<?= htmlspecialchars($found['report_id'] ?? '') ?>" data-status="<?= htmlspecialchars($workflowStatus) ?>">
                          <div class="workflow-head">
                            <div class="workflow-status">
                              <span class="workflow-label">Match workflow</span>
                              <span class="workflow-pill workflow-pill--<?= htmlspecialchars($workflowStatus) ?>">
                                <?= htmlspecialchars(match_workflow_label($workflowStatus)) ?>
                              </span>
                            </div>
                            <span class="workflow-ref">Lost <?= htmlspecialchars($bestMatch['lost_report']['report_id'] ?? 'LR-?') ?> · Found <?= htmlspecialchars($found['report_id'] ?? 'FR-?') ?></span>
                          </div>
                          <p class="workflow-hint" data-workflow-hint>Review the details before choosing an action.</p>
                          <div class="workflow-buttons">
                            <button type="button" class="workflow-btn workflow-btn--primary" data-workflow-action="claimable">Mark as Claimable</button>
                            <button type="button" class="workflow-btn workflow-btn--accent" data-workflow-action="claimed">Mark as Claimed</button>
                            <button type="button" class="workflow-btn workflow-btn--danger" data-workflow-action="reject">Doesn't Match</button>
                          </div>
                        </div>
                      <?php else: ?>
                        <p class="muted report-tile__note">No automatic match yet.</p>
                      <?php endif; ?>
                    </div>
                  </article>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </section>

          <section class="management-panel management-panel--wide" id="panel-claims" data-panel-type="claims" aria-labelledby="claimHeading">
            <div class="panel-header">
              <div>
                <h2 id="claimHeading">Claim Requests</h2>
                <p class="muted">Resolve outstanding ownership claims submitted by students.</p>
              </div>
              <span class="panel-count"><?= $claimCount ?> request(s)</span>
            </div>
            <div class="panel-scroll panel-scroll--table">
              <div class="table-wrap">
                <table class="reports-table" aria-label="Claim requests">
                  <thead><tr><th>Image</th><th>User</th><th>Type</th><th>Item</th><th>Location</th><th>Match</th><th>Date</th><th>Actions</th></tr></thead>
                  <tbody id="reportsTbody">
                    <?php if (empty($claims)): ?>
                      <tr data-empty-row="true">
                        <td colspan="8" class="empty-row">No claim requests submitted yet.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($claims as $claim):
                        $itemName = $claim['found_item'] ?? 'Item';
                        $location = $claim['found_location'] ?? '—';
                        $date = $claim['found_date'] ?? '—';
                        $userName = $claim['requester_name'] ?? 'Unknown User';
                        $badgeClass = strtolower($claim['item_type'] ?? '') === 'lost' ? 'badge-lost' : 'badge-found';
                        $imgPath = normalizeImage($claim['found_photo'] ?? '') ?: $placeholderImg;
                        $idPhoto = normalizeImage($claim['id_photo_path'] ?? '');
                        $requestCode = (string)($claim['request_code'] ?? '');
                        $rawStatus = $claim['status'] ?? '';
                        $statusMap = [
                          'approved' => 'Approved',
                          'resolved' => 'Resolved',
                          'rejected' => 'Rejected',
                        ];
                        $normalizedKey = strtolower(trim((string)$rawStatus));
                        $statusValue = $statusMap[$normalizedKey] ?? 'Pending';
                        $statusLabel = claimStatusLabel($rawStatus);
                        $statusSlug = $statusMap[$normalizedKey] ?? 'Pending';
                        $statusSlug = strtolower(str_replace(' ', '-', $statusSlug));
                        if ($statusSlug === '') {
                          $statusSlug = 'pending';
                        }
                        $matchConfidence = $claim['match_confidence'] ?? null;
                        if ($matchConfidence === null) {
                          $matchConfidence = inline_claim_score($claim);
                          $claim['match_classification'] = ($matchConfidence >= 75) ? 'probable' : (($matchConfidence >= 45) ? 'possible' : '');
                        }
                        $matchClass = $claim['match_classification'] ?? '';
                        $matchLabel = $matchConfidence !== null
                          ? (($matchClass ? ucfirst($matchClass) . ' · ' : '') . $matchConfidence . '%')
                          : 'No signals yet';
                        $matchState = $matchConfidence !== null ? 'match-pill--active' : 'match-pill--muted';
                        // enhance claim with found description if available for modal
                        $claim['found_description'] = $claim['found_description'] ?? '';
                        $claim['id_photo_url'] = $idPhoto;
                        $matchPayload = encodeClaimMatchPayload($claim);
                      ?>
                      <tr>
                        <td class="td-thumb"><img src="<?= htmlspecialchars($imgPath) ?>" alt="Photo of <?= htmlspecialchars($itemName) ?>"></td>
                        <td class="td-user"><?= htmlspecialchars($userName) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($claim['item_type'] ?? 'Found') ?></span></td>
                        <td><?= htmlspecialchars($itemName) ?></td>
                        <td><?= htmlspecialchars($location) ?></td>
                        <td><span class="match-pill <?= $matchState ?>"><?= htmlspecialchars($matchLabel) ?></span></td>
                        <td><?= htmlspecialchars($date) ?></td>
                        <td>
                          <div class="claim-actions">
                            <div class="claim-status-block">
                              <span class="claim-status-label">Status</span>
                              <span class="claim-status-pill claim-status-pill--<?= htmlspecialchars($statusSlug) ?>" data-claim-status-label><?= htmlspecialchars($statusLabel) ?></span>
                            </div>
                            <div class="claim-action-group" data-request="<?= htmlspecialchars($requestCode) ?>" data-status="<?= htmlspecialchars($statusValue) ?>">
                              <button type="button" class="claim-pill-btn claim-pill-btn--ghost" data-claim-view data-match='<?= $matchPayload ?>'>View Match Details</button>
                              <button type="button" class="claim-pill-btn claim-pill-btn--primary" data-claim-action="Approved">Mark as Claimable</button>
                              <button type="button" class="claim-pill-btn claim-pill-btn--accent" data-claim-action="Claimed">Mark as Claimed</button>
                              <button type="button" class="claim-pill-btn claim-pill-btn--danger" data-claim-action="Rejected">Doesn't Match</button>
                            </div>
                          </div>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>
  <div id="matchModal" class="match-modal" aria-hidden="true">
    <div class="match-modal__panel" role="dialog" aria-modal="true" aria-labelledby="matchModalTitle">
      <button type="button" class="match-modal__close" data-match-close>&times;</button>
      <header class="match-modal__header">
        <p class="match-modal__eyebrow">Auto Match Suggestion</p>
        <h2 id="matchModalTitle">Match Details</h2>
        <p class="match-modal__subtitle">Review reasons before confirming ownership or contacting claimants.</p>
      </header>
      <div class="match-modal__body">
        <div class="match-modal__score-row">
          <div class="match-modal__score" id="matchModalScore">0%</div>
          <div class="match-modal__score-meta">
            <div class="match-modal__label" id="matchModalLabel">—</div>
            <p class="match-modal__note">Automatic signal score based on categories, descriptions, and locations.</p>
          </div>
        </div>
        <div class="match-modal__columns">
          <div class="match-modal__column">
            <h3>Lost Report</h3>
            <div class="match-modal__item" id="matchModalLost">—</div>
          </div>
          <div class="match-modal__column">
            <h3>Found Report</h3>
            <div class="match-modal__item" id="matchModalFound">—</div>
          </div>
        </div>
        <div class="match-modal__reasons">
          <h3>Match Signals</h3>
          <ul id="matchModalReasons" class="match-modal__reason-list"></ul>
        </div>
        <div class="match-modal__proof" id="matchModalProof" style="display:none;">
          <h3>Claim Proof</h3>
          <p class="match-modal__proof-row">
            <strong>School ID:</strong>
            <a href="#" id="matchModalIdLink" target="_blank" rel="noopener">View Upload</a>
            <span id="matchModalIdMissing" class="match-modal__proof-missing">Not uploaded</span>
          </p>
          <div class="match-modal__id-block" id="matchModalIdBlock">
            <p class="match-modal__id-label">Submitted School ID for Verification</p>
            <div class="match-modal__id-preview">
              <img src="" alt="Submitted School ID" id="matchModalIdImage">
            </div>
          </div>
        </div>
      </div>
      <footer class="match-modal__footer">
        <button type="button" class="btn" data-match-close>Close</button>
      </footer>
    </div>
  </div>
  <script>
    window.CLAIM_UPDATE_ENDPOINT = '/BA-3104/AdminDB/update_claim_status.php';
    window.MATCH_WORKFLOW_ENDPOINT = '/BA-3104/AdminDB/match_workflow.php';
  </script>
  <script src="admin.js"></script>
  <script>
    (function(){
      const modal = document.getElementById('matchModal');
      if (!modal) return;
      const scoreEl = document.getElementById('matchModalScore');
      const labelEl = document.getElementById('matchModalLabel');
      const lostEl = document.getElementById('matchModalLost');
      const foundEl = document.getElementById('matchModalFound');
      const reasonsEl = document.getElementById('matchModalReasons');
      const proofEl = document.getElementById('matchModalProof');
      const idLinkEl = document.getElementById('matchModalIdLink');
      const idMissingEl = document.getElementById('matchModalIdMissing');
      const idImageEl = document.getElementById('matchModalIdImage');
      const idBlockEl = document.getElementById('matchModalIdBlock');
      const dateFormatter = new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

      const formatDateForModal = (raw) => {
        if (!raw) return '—';
        const parsed = new Date(raw);
        return Number.isNaN(parsed.getTime()) ? raw : dateFormatter.format(parsed);
      };

      const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

      const formatItemBlock = (data = {}, type = 'lost') => {
        const locationLabel = type === 'lost' ? 'Last seen' : 'Found at';
        const dateLabel = type === 'lost' ? 'Date lost' : 'Date found';
        const rawDate = data.event_date || data.date_found || data.date_lost || data.created_at || '';
        const descSource = (data.description && String(data.description).trim().length)
          ? data.description
          : 'No description provided.';
        const safeDesc = escapeHtml(descSource).replace(/\n/g, '<br>');
        return `
          <h4>${escapeHtml(data.item_name || 'Item')}</h4>
          <div class="match-modal__item-meta">
            <div><span>Report ID</span>${escapeHtml(data.report_id || '—')}</div>
            <div><span>Category</span>${escapeHtml(data.category || '—')}</div>
            <div><span>${locationLabel}</span>${escapeHtml(data.location || '—')}</div>
            <div><span>${dateLabel}</span>${escapeHtml(formatDateForModal(rawDate))}</div>
          </div>
          <p class="match-modal__item-desc">${safeDesc}</p>
        `;
      };

      function clearModal(){
        if (scoreEl) scoreEl.textContent = '0%';
        if (labelEl) labelEl.textContent = '—';
        if (lostEl) lostEl.innerHTML = '—';
        if (foundEl) foundEl.innerHTML = '—';
        if (reasonsEl) reasonsEl.innerHTML = '';
        if (proofEl) proofEl.style.display = 'none';
        if (idLinkEl) idLinkEl.style.display = 'none';
        if (idMissingEl) idMissingEl.style.display = 'inline';
        if (idImageEl) {
          idImageEl.src = '';
          idImageEl.style.display = 'none';
        }
        if (idBlockEl) idBlockEl.style.display = 'none';
      }

      function closeModal(){
        modal.setAttribute('aria-hidden','true');
        modal.classList.remove('is-visible');
        document.body.classList.remove('modal-open');
      }

      function openModal(payload){
        if (scoreEl) scoreEl.textContent = (payload.score ?? 0) + '%';
        if (labelEl) labelEl.textContent = payload.label || 'Match';
        if (lostEl) {
          const lost = payload.lost || {};
          lostEl.innerHTML = formatItemBlock(lost, 'lost');
        }
        if (foundEl) {
          const found = payload.found || {};
          foundEl.innerHTML = formatItemBlock(found, 'found');
        }
        if (reasonsEl) {
          reasonsEl.innerHTML = '';
          const reasonList = (payload.reasons && payload.reasons.length)
            ? payload.reasons
            : ['No detailed signals were generated for this pair.'];
          reasonList.forEach(reason => {
            const li = document.createElement('li');
            li.className = 'match-modal__reason';
            li.textContent = reason;
            reasonsEl.appendChild(li);
          });
        }
        const showProof = Boolean(payload.has_proof);
        if (proofEl) {
          proofEl.style.display = showProof ? 'flex' : 'none';
        }
        const hasIdPhoto = showProof && Boolean(payload.id_photo && payload.id_photo.trim().length);
        if (idLinkEl) {
          if (hasIdPhoto) {
            idLinkEl.href = payload.id_photo;
            idLinkEl.style.display = 'inline';
          } else {
            idLinkEl.removeAttribute('href');
            idLinkEl.style.display = 'none';
          }
        }
        if (idMissingEl) {
          idMissingEl.style.display = showProof && !hasIdPhoto ? 'inline' : 'none';
        }
        if (idImageEl) {
          if (hasIdPhoto) {
            idImageEl.src = payload.id_photo;
            idImageEl.style.display = 'block';
          } else {
            idImageEl.src = '';
            idImageEl.style.display = 'none';
          }
        }
        if (idBlockEl) {
          idBlockEl.style.display = hasIdPhoto ? 'block' : 'none';
        }
        modal.setAttribute('aria-hidden','false');
        modal.classList.add('is-visible');
        document.body.classList.add('modal-open');
      }

      document.querySelectorAll('[data-match]').forEach(btn => {
        btn.addEventListener('click', () => {
          const json = btn.getAttribute('data-match');
          if (!json) return;
          try {
            const payload = JSON.parse(json);
            openModal(payload);
          } catch (err) {
            console.error('Unable to parse match payload', err);
          }
        });
      });

      modal.querySelectorAll('[data-match-close]').forEach(btn => btn.addEventListener('click', () => {
        closeModal();
        clearModal();
      }));

      modal.addEventListener('click', event => {
        if (event.target === modal) {
          closeModal();
          clearModal();
        }
      });
      document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
          closeModal();
          clearModal();
        }
      });
    })();
  </script>
</body>
</html>
