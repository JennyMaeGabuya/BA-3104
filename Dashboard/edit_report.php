<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../db_config.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  header('Location: /BA-3104/login.php');
  exit;
}

function load_report(PDO $pdo, int $userId, string $reportId): array {
  $isFound = str_starts_with($reportId, 'FR-');
  if ($isFound) {
    $sql = "SELECT 'Found' as type, report_id, item_name, category, description, location_found AS location, date_found AS date_event, time_found AS time_event, photo_path, pickup_location, contact_email, contact_phone FROM found_reports WHERE report_id = :rid AND user_id = :uid LIMIT 1";
  } else {
    $sql = "SELECT 'Lost' as type, report_id, item_name, category, description, location AS location, date_lost AS date_event, time_lost AS time_event, photo_path, NULL AS pickup_location, contact_email, contact_phone FROM lost_reports WHERE report_id = :rid AND user_id = :uid LIMIT 1";
  }
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':rid' => $reportId, ':uid' => $userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) { throw new RuntimeException('Report not found'); }
  return $row;
}

function handle_upload(): ?string {
  if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
  }

  $err = (int)$_FILES['photo']['error'];
  if ($err !== UPLOAD_ERR_OK) {
    throw new RuntimeException('Upload error code: ' . $err);
  }

  $tmp = $_FILES['photo']['tmp_name'];
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp) ?: '';
  $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
  if (!isset($allowed[$mime])) {
    throw new RuntimeException('Invalid image type');
  }
  $ext = $allowed[$mime];

  $primary = __DIR__ . '/Image';
  $fallback = __DIR__ . '/../ImageUploads';
  foreach ([$primary, $fallback] as $dir) {
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    if (!is_writable($dir)) { @chmod($dir, 0777); }
  }
  $targetDir = is_writable($primary) ? $primary : $fallback;
  $name = 'upd_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $targetDir . '/' . $name;

  if (!is_uploaded_file($tmp)) { throw new RuntimeException('Invalid upload'); }
  if (!@move_uploaded_file($tmp, $dest)) {
    if (!@rename($tmp, $dest)) {
      throw new RuntimeException('Failed to save uploaded file');
    }
  }

  if ($targetDir === $primary) {
    return 'Dashboard/Image/' . $name;
  }
  return 'ImageUploads/' . $name;
}

$reportId = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if ($reportId === '') {
  http_response_code(400);
  echo 'Missing report id';
  exit;
}

try {
  $report = load_report($pdo, (int)$userId, $reportId);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date_event = trim($_POST['date_event'] ?? '');
    $time_event = trim($_POST['time_event'] ?? '');
    $email = trim($_POST['contact_email'] ?? '');
    $phone = trim($_POST['contact_phone'] ?? '');
    $pickup = isset($_POST['pickup_location']) ? trim($_POST['pickup_location']) : null;

    if ($item_name === '' || $category === '' || $description === '' || $location === '' || $date_event === '') {
      throw new RuntimeException('Please fill all required fields');
    }

    $newPhoto = handle_upload();

    if ($report['type'] === 'Found') {
      $sql = "UPDATE found_reports SET item_name=:n, category=:c, description=:d, location_found=:loc, date_found=:dt, time_found=:tm, contact_email=:em, contact_phone=:ph, pickup_location=:pk" . ($newPhoto ? ", photo_path=:pp" : "") . " WHERE report_id=:rid AND user_id=:uid";
      $params = [':n'=>$item_name, ':c'=>$category, ':d'=>$description, ':loc'=>$location, ':dt'=>$date_event, ':tm'=>($time_event !== '' ? $time_event : null), ':em'=>$email, ':ph'=>$phone, ':pk'=>$pickup, ':rid'=>$reportId, ':uid'=>$userId];
    } else {
      $sql = "UPDATE lost_reports SET item_name=:n, category=:c, description=:d, location=:loc, date_lost=:dt, time_lost=:tm, contact_email=:em, contact_phone=:ph" . ($newPhoto ? ", photo_path=:pp" : "") . " WHERE report_id=:rid AND user_id=:uid";
      $params = [':n'=>$item_name, ':c'=>$category, ':d'=>$description, ':loc'=>$location, ':dt'=>$date_event, ':tm'=>($time_event !== '' ? $time_event : null), ':em'=>$email, ':ph'=>$phone, ':rid'=>$reportId, ':uid'=>$userId];
    }
    if ($newPhoto) { $params[':pp'] = $newPhoto; }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Location: /BA-3104/Dashboard/my_report.php?edited=1');
    exit;
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Error: ' . htmlspecialchars($e->getMessage());
  exit;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Edit Report - FindIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BA-3104/Dashboard/dashboard.css?v=1">
</head>
<body>
  <div class="app">
    <div class="main">
      <header class="topbar">
        <div class="topbar-left"><a href="/BA-3104/Dashboard/my_report.php" class="btn">← Back</a><div class="topbar-title">Edit <?= htmlspecialchars($report['type']) ?> Report</div></div>
      </header>
      <div class="page-body">
        <section class="card">
          <form method="post" enctype="multipart/form-data" class="form-grid">
            <div class="form-row">
              <label>Item Name
                <input type="text" name="item_name" value="<?= htmlspecialchars($report['item_name']) ?>" required>
              </label>
              <label>Category
                <input type="text" name="category" value="<?= htmlspecialchars($report['category']) ?>" required>
              </label>
            </div>
            <div class="form-row">
              <label>Description
                <textarea name="description" rows="4" required><?= htmlspecialchars($report['description']) ?></textarea>
              </label>
            </div>
            <div class="form-row">
              <label>Location
                <input type="text" name="location" value="<?= htmlspecialchars($report['location']) ?>" required>
              </label>
              <label>Date
                <input type="date" name="date_event" value="<?= htmlspecialchars($report['date_event']) ?>" required>
              </label>
              <label>Time
                <input type="time" name="time_event" value="<?= htmlspecialchars((string)$report['time_event']) ?>">
              </label>
            </div>
            <?php if ($report['type'] === 'Found'): ?>
            <div class="form-row">
              <label>Pickup Location
                <input type="text" name="pickup_location" value="<?= htmlspecialchars((string)($report['pickup_location'] ?? '')) ?>">
              </label>
            </div>
            <?php endif; ?>
            <div class="form-row">
              <label>Contact Email
                <input type="email" name="contact_email" value="<?= htmlspecialchars($report['contact_email']) ?>" required>
              </label>
              <label>Contact Phone
                <input type="tel" name="contact_phone" value="<?= htmlspecialchars($report['contact_phone']) ?>" required>
              </label>
            </div>
            <div class="form-row">
              <label>Replace Photo (optional)
                <input type="file" name="photo" accept="image/*">
              </label>
            </div>
            <div class="form-row">
              <button type="submit" class="btn btn-cta">Save Changes</button>
              <a class="btn btn-outline" href="/BA-3104/Dashboard/my_report.php">Cancel</a>
            </div>
          </form>
        </section>
      </div>
    </div>
  </div>
</body>
</html>
