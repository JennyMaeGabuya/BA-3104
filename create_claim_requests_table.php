<?php
require_once __DIR__ . '/db_config.php';

$sql = "CREATE TABLE IF NOT EXISTS claim_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_code VARCHAR(20) NOT NULL UNIQUE,
  report_id VARCHAR(32) NOT NULL,
  item_type ENUM('Found','Lost') DEFAULT 'Found',
  user_id INT NOT NULL,
  details TEXT NOT NULL,
  last_seen_location VARCHAR(255) DEFAULT NULL,
  contact_info VARCHAR(255) NOT NULL,
  id_photo_path VARCHAR(500) NOT NULL,
  status ENUM('Pending','Approved','Rejected','Resolved') DEFAULT 'Pending',
  admin_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_report_claim (report_id),
  INDEX idx_status_claim (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
  $pdo->exec($sql);
  echo "claim_requests table ensured.\n";
} catch (Throwable $e) {
  echo "Failed to create claim_requests table: " . $e->getMessage();
}

// Add column if an old table exists without last_seen_location
try {
  $colCheck = $pdo->query("SHOW COLUMNS FROM claim_requests LIKE 'last_seen_location'");
  $hasCol = $colCheck && $colCheck->fetch(PDO::FETCH_ASSOC);
  if (!$hasCol) {
    $pdo->exec("ALTER TABLE claim_requests ADD COLUMN last_seen_location VARCHAR(255) DEFAULT NULL AFTER details");
    echo "Added last_seen_location column to claim_requests.\n";
  }
} catch (Throwable $e) {
  echo "Column check/alter failed: " . $e->getMessage() . "\n";
}

// Ensure match_results exists to store signals
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS match_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_request_id INT NOT NULL,
    found_report_id INT NOT NULL,
    confidence TINYINT UNSIGNED NOT NULL,
    classification ENUM('probable','possible') NOT NULL DEFAULT 'possible',
    breakdown JSON NOT NULL,
    status ENUM('pending_review','need_proof','authorized','rejected','claimed') NOT NULL DEFAULT 'pending_review',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_request_id) REFERENCES claim_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (found_report_id) REFERENCES found_reports(id) ON DELETE CASCADE,
    INDEX idx_claim_match (claim_request_id),
    INDEX idx_found_matchresult (found_report_id),
    INDEX idx_status_matchresult (status),
    UNIQUE KEY uq_claim_found (claim_request_id, found_report_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  echo "match_results table ensured.\n";
} catch (Throwable $e) {
  echo "Failed to ensure match_results: " . $e->getMessage() . "\n";
}
