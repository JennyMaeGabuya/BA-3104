<?php
session_start();
if (empty($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}
require_once 'src/config.php';

$message = '';
$messageType = '';

/**
 * Helper: normalize a datetime string to MySQL DATETIME or return null if invalid/empty.
 */
function normalize_datetime_or_null($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return null;
    // try exact format first
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $raw);
    if ($dt !== false) return $dt->format('Y-m-d H:i:s');
    // fallback to strtotime parsing
    $ts = strtotime($raw);
    if ($ts === false) return null;
    return date('Y-m-d H:i:s', $ts);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml_file'])) {
    try {
        $file = $_FILES['xml_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error. Code: ' . $file['error']);
        }

        // Quick extension check
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'xml') {
            throw new Exception('Please upload an XML file (.xml).');
        }

        // Parse XML
        $xmlContent = file_get_contents($file['tmp_name']);
        $xml = @simplexml_load_string($xmlContent);
        if ($xml === false) {
            throw new Exception('Failed to parse XML. Ensure it is well-formed.');
        }

        $pdo = connect_db();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepared statements: insert and update
        $insertSql = "INSERT INTO visitors (full_name, user_type, srcode, contact, purpose, time_in, time_out)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertSql);

        $updateSql = "UPDATE visitors SET full_name = ?, user_type = ?, srcode = ?, contact = ?, purpose = ?, time_in = ?, time_out = ?
                      WHERE visitor_id = ?";
        $updateStmt = $pdo->prepare($updateSql);

        // Optional: check existence by visitor_id
        $existsStmt = $pdo->prepare("SELECT visitor_id FROM visitors WHERE visitor_id = ? LIMIT 1");

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        // Accept both <VisitorLog><Visitor> and <visitors><visitor>
        if (isset($xml->Visitor) && count($xml->Visitor) > 0) {
            $nodes = $xml->Visitor;
        } elseif (isset($xml->visitor) && count($xml->visitor) > 0) {
            $nodes = $xml->visitor;
        } else {
            // try to detect children generically
            $nodes = $xml->children();
        }

        // Ensure nodes is iterable
        if (empty($nodes) || count($nodes) === 0) {
            throw new Exception('No visitor nodes found in the XML.');
        }

        $pdo->beginTransaction();

        // Safe numeric counter to avoid string+int errors
        $rowCounter = 0;

        foreach ($nodes as $idx => $node) {
            $rowCounter++;
            $rowNum = $rowCounter; // always numeric and safe

            // Accept different tag names used in your sample:
            // visitorid, timein, fullname, usertype, srcode, contact, purpose, timeout
            $visitorid = isset($node->visitorid) ? trim((string)$node->visitorid) : null;
            $time_in_raw = isset($node->timein) ? (string)$node->timein : (isset($node->time_in) ? (string)$node->time_in : '');
            $full_name = isset($node->fullname) ? trim((string)$node->fullname) : (isset($node->full_name) ? trim((string)$node->full_name) : '');
            $user_type = isset($node->usertype) ? trim((string)$node->usertype) : (isset($node->user_type) ? trim((string)$node->user_type) : '');
            $srcode_raw = isset($node->srcode) ? (string)$node->srcode : '';
            $contact_raw = isset($node->contact) ? (string)$node->contact : '';
            $purpose_raw = isset($node->purpose) ? (string)$node->purpose : '';
            $time_out_raw = isset($node->timeout) ? (string)$node->timeout : (isset($node->time_out) ? (string)$node->time_out : '');

            $srcode = trim($srcode_raw) === '' ? null : trim($srcode_raw);
            $contact = trim($contact_raw) === '' ? null : trim($contact_raw);
            $purpose = trim($purpose_raw) === '' ? null : trim($purpose_raw);

            // Validate required fields (as your importer required: full_name, user_type, time_in)
            if ($full_name === '' || $user_type === '' || trim($time_in_raw) === '') {
                $skipped++;
                $errors[] = "Row $rowNum skipped: missing required field (fullname/usertype/timein).";
                continue;
            }

            // Normalize datetimes
            $time_in = normalize_datetime_or_null($time_in_raw);
            if ($time_in === null) {
                $skipped++;
                $errors[] = "Row $rowNum skipped: invalid timein format ('{$time_in_raw}').";
                continue;
            }
            $time_out = normalize_datetime_or_null($time_out_raw); // can be null

            // If visitorid is provided and numeric, try update path
            if ($visitorid !== null && $visitorid !== '' && is_numeric($visitorid)) {
                $vid = (int)$visitorid;
                // check existence
                $existsStmt->execute([$vid]);
                $found = $existsStmt->fetchColumn();
                if ($found) {
                    // update
                    try {
                        $updateStmt->execute([$full_name, $user_type, $srcode, $contact, $purpose, $time_in, $time_out, $vid]);
                        $updated += $updateStmt->rowCount() ?: 1; // treat as updated if execute ok
                    } catch (Exception $e) {
                        $skipped++;
                        $errors[] = "Row $rowNum (visitorid=$vid) update failed: " . $e->getMessage();
                        continue;
                    }
                    continue;
                }
                // if not found, fallthrough to insert (import as new)
            }

            // Insert new record
            try {
                $insertStmt->execute([$full_name, $user_type, $srcode, $contact, $purpose, $time_in, $time_out]);
                $imported++;
            } catch (Exception $e) {
                $skipped++;
                $errors[] = "Row $rowNum insert failed: " . $e->getMessage();
                continue;
            }
        }

        $pdo->commit();

        $message = "Import finished. Inserted: $imported. Updated: $updated. Skipped: $skipped.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(' | ', array_slice($errors, 0, 20));
            if (count($errors) > 20) $message .= " (and " . (count($errors)-20) . " more)";
        }
        $messageType = 'success';

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $message = "Fatal import error: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Import XML - Admin</title>
  <link rel="stylesheet" href="css/styles.css?v=3">
</head>
<body class="admin-page">
  <div class="admin-wrap">
    <div class="admin-top">
      <h1>Import Visitors from XML</h1>
      <div class="user-info-bar">
        <span class="signed-in-msg">
          Signed in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        </span>
        <a href="src/logout.php" class="logout-btn">Logout</a>
      </div>
    </div>

    <div class="import-container">
      <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageType; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <h2>Upload XML File</h2>
      <p>Select an XML file containing visitor records to import into the system.</p>
      
      <form method="POST" enctype="multipart/form-data">
        <div class="file-input-wrapper">
          <input type="file" name="xml_file" accept=".xml" required>
        </div>
        
        <div class="button-group">
          <button type="submit" class="upload-btn">Upload & Import</button>
          <a href="admin.php" class="back-btn">Back to Dashboard</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>