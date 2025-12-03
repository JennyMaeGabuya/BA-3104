<?php
/**
 * Found Items Page - Converted from user_found.html
 * Requires authentication - redirects to login if not logged in
 */

require_once 'auth_check.php';
require_once __DIR__ . '/db_config.php';

$foundItems = [];
$loadError = false;
$placeholderImg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='640' height='420'><rect width='100%' height='100%' fill='%23f1f5f9'/><text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle' fill='%2394a3b8' font-family='Inter,Arial' font-size='20'>No Photo</text></svg>";

function normalizePhotoPath(?string $path): string {
  if (!$path) {
    return '';
  }
  $trim = trim($path);
  if ($trim === '') {
    return '';
  }
  if (str_starts_with($trim, 'Image/')) {
    $trim = 'Dashboard/' . $trim;
  }
  if (preg_match('/^https?:\/\//', $trim) || str_starts_with($trim, '/')) {
    return $trim;
  }
  return '/BA-3104/' . ltrim($trim, '/');
}

function statusLabel(string $status): string {
  return match ($status) {
    'Verified' => 'Available',
    'Claimed' => 'Claimed',
    'Pending' => 'Pending Review',
    'Rejected' => 'Rejected',
    default => ucfirst(strtolower($status)),
  };
}

function statusClass(string $status): string {
  return match ($status) {
    'Verified' => 'status-pill--available',
    'Claimed' => 'status-pill--claimed',
    'Rejected' => 'status-pill--rejected',
    default => 'status-pill--pending',
  };
}

function excerpt(string $text, int $limit = 160): string {
  $trim = trim($text);
  if ($trim === '') {
    return $trim;
  }
  $lenFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
  $subFn = function_exists('mb_substr') ? 'mb_substr' : 'substr';
  if ($lenFn($trim) <= $limit) {
    return $trim;
  }
  return rtrim($subFn($trim, 0, $limit - 1)) . '…';
}

try {
  $stmt = $pdo->prepare("SELECT report_id, item_name, category, description, location_found, date_found, time_found, photo_path, status FROM found_reports WHERE status = 'Verified' ORDER BY COALESCE(date_found, created_at) DESC, created_at DESC");
  $stmt->execute();
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $photoUrl = normalizePhotoPath($row['photo_path'] ?? '') ?: $placeholderImg;
    $foundItems[] = [
      'report_id' => $row['report_id'] ?? '',
      'item_name' => $row['item_name'] ?? 'Unnamed Item',
      'category' => $row['category'] ?? 'Others',
      'description' => $row['description'] ?? '',
      'location_found' => $row['location_found'] ?? 'Unknown Location',
      'date_found' => $row['date_found'] ?? '',
      'time_found' => $row['time_found'] ?? '',
      'photo_url' => $photoUrl,
      'status' => $row['status'] ?? 'Pending',
    ];
  }
} catch (Throwable $e) {
  $loadError = true;
}
$itemsCount = count($foundItems);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — Found Items</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BA-3104/user_home.css">
</head>
<body>

  <!-- Header -->
<header class="site-header">
  <div class="header-inner">

    <!-- LEFT: Logo + Text -->
    <div class="brand">
      <div class="logo" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
          <path d="M12 3v18"></path>
        </svg>
      </div>

      <div class="brand-text">
        <div class="site-title">FindIt@BatStateU</div>
        <div class="site-sub">Malvar Campus</div>
      </div>
    </div>

    <!-- RIGHT: NAVIGATION + BELL + PROFILE -->
    <div class="header-right">

      <nav class="nav" role="navigation" aria-label="Main">
        <a href="user_home.php">Home</a>
        <a href="user_report.php">Report An Item</a>
        <a href="user_found.php" class="active">Found Items</a>
        <a href="user_about.php">About</a>
        <a class="btn-dashboard" href="Dashboard/dashboard.php">Dashboard</a>
      </nav>

      <!-- Notification Icon with Dropdown -->
      <div class="notification-container">
        <div class="notif" id="notifBtn">
          <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2z"></path>
            <path d="M18 16v-5a6 6 0 1 0-12 0v5l-2 2v1h16v-1z"></path>
          </svg>
          <span class="notif-dot" id="notifDot"></span>
        </div>

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
            <a href="Dashboard/notification.php" class="view-all-link">View all notifications</a>
          </div>
        </div>
      </div>

      <!-- Profile Avatar - Dynamic with PHP Session -->
      <?php include 'avatar_component.php'; ?>

    </div>

  </div>
</header>


<!-- Hero for Found Items Gallery -->
<section class="hero hero-found">
  <div class="hero-inner">
    <h1>Found Items Gallery</h1>
    <p class="hero-sub">Browse through items found on campus and claim what's yours</p>

    <form id="searchForm" class="search-bar" onsubmit="return false;">
      <label class="search-icon" for="searchInput" aria-hidden="true">🔍</label>
      <input id="searchInput" type="search" placeholder="Search by item name or description..." />
      <button id="searchBtn" class="btn-hero" type="button">Search</button>
    </form>
  </div>
</section>


  <!-- Main content: filters + gallery -->
  <main class="page">
    <div class="page-inner">
      <!-- Filters Column -->
      <aside class="filters" aria-labelledby="filters-heading">
        <h2 id="filters-heading"><span class="filter-icon">⚲</span> Filters</h2>

        <div class="filter-field">
          <label for="categorySelect">Category</label>
          <select id="categorySelect">
            <option value="all">All Categories</option>
          </select>
        </div>

        <div class="filter-field">
          <label for="locationSelect">Location</label>
          <select id="locationSelect">
            <option value="all">All Locations</option>
          </select>
        </div>

        <button id="clearFilters" class="btn-clear">Clear Filters</button>

        <div class="help-card">
          <h3>Need Help?</h3>
          <p>Can't find your item? Report it as lost and we'll notify you if it's found.</p>
          <a href="user_report.php" class="btn-report">Report An Item</a>
        </div>
      </aside>

      <!-- Gallery Column -->
      <section class="gallery-area" aria-labelledby="gallery-heading">
        <div class="gallery-header">
          <div>
            <h2 id="gallery-heading">Found Items</h2>
            <p id="gallery-count" class="muted">
              <?php echo $itemsCount ? 'Showing ' . $itemsCount . ' of ' . $itemsCount . ' items' : 'Showing 0 of 0 items'; ?>
            </p>
          </div>
        </div>

        <div id="cardsGrid" class="cards-grid cards-grid--found" aria-live="polite">
          <?php if ($loadError): ?>
            <div class="error-state" style="color:#ef4444">Failed to load found items.</div>
          <?php elseif (!$itemsCount): ?>
            <div class="empty-state">No verified found items yet.</div>
          <?php else: ?>
            <?php foreach ($foundItems as $item):
              $name = htmlspecialchars($item['item_name']);
              $category = htmlspecialchars($item['category']);
              $desc = htmlspecialchars(excerpt($item['description']));
              $location = htmlspecialchars($item['location_found']);
              $date = htmlspecialchars($item['date_found']);
              $statusText = statusLabel($item['status']);
              $statusCls = statusClass($item['status']);
              $photo = htmlspecialchars($item['photo_url']);
            ?>
              <article class="found-card" data-report="<?php echo htmlspecialchars($item['report_id']); ?>">
                <div class="found-thumb">
                  <img src="<?php echo $photo; ?>" alt="Photo of <?php echo $name; ?>" loading="lazy">
                </div>
                <div class="found-body">
                  <div class="found-title-row">
                    <h3 class="found-title"><?php echo $name; ?></h3>
                    <span class="status-pill <?php echo $statusCls; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                  </div>
                  <div class="category-chip"><?php echo $category; ?></div>
                  <p class="found-desc">Description hidden for safety. Contact admin if you believe this is yours.</p>
                  <div class="found-meta">
                    <div class="found-meta-item">📍 <span>Exact location withheld</span></div>
                    <div class="found-meta-item">📅 <span><?php echo $date; ?></span></div>
                  </div>
                  <button class="btn-claim" data-id="<?php echo htmlspecialchars($item['report_id']); ?>">Claim This Item</button>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </main>

  <!-- Footer -->
  <footer class="site-inner">
    <div class="footer-footer">
      <p>© 2025 Batangas State University - Malvar Campus. All rights reserved.</p>
    </div>
  </footer>

 <!-- Claim Item Request Modal -->
<div id="claimModal" class="modal" aria-hidden="true">
  <div class="modal-panel claim-panel">

    <button class="modal-close" id="modalClose" aria-label="Close">&times;</button>

    <h3 class="claim-title">Claim Item Request</h3>
    <p class="claim-sub">Please provide details to verify that you are the owner of this item</p>

    <div id="claimItemBox" class="claim-item-box">
      <!-- JS will insert item preview -->
    </div>

    <div class="claim-form">

      <label class="label">Describe the item and provide identifying details *</label>
      <textarea id="claimDetails" class="input claim-textarea"
        placeholder="Please provide specific details about the item to verify ownership (e.g., brand, color, contents, distinguishing marks, etc.)"></textarea>

      <small class="helper">Be as specific as possible to help us verify your ownership</small>

      <label class="label">Where did you last see it? *</label>
      <input id="claimLastSeen" class="input" placeholder="e.g., CICS Building near the parking area" />
      <small class="helper">We compare this with where the item was found to score the match.</small>

      <label class="label">Contact Information *</label>
      <input id="claimContact" class="input"
        placeholder="Phone number or email for verification">

      <label class="label" for="claimIdUpload">Upload School ID *</label>
      <div class="file-upload">
        <input id="claimIdUpload" name="school_id" type="file" class="input file-input" required
          accept="image/*">
      </div>

      <div class="claim-info-box">
        <strong>Important:</strong> After submitting your claim request, an admin will review
        your information and contact you for verification. Be prepared to provide additional
        proof of ownership if needed.
      </div>

      <div class="modal-actions">
        <button id="cancelClaim" class="btn-cancel">Cancel</button>
        <button id="submitClaim" class="btn-primary">Submit Claim Request</button>
      </div>

    </div>

  </div>
</div>

  <!-- Include JavaScript files -->
  <script>
    window.FOUND_ITEMS = <?php echo json_encode($foundItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  </script>
  <script src="avatar_dropdown.js"></script>
  <script src="user_script.js"></script>
</body>
</html>

