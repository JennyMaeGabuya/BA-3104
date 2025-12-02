<?php
require_once __DIR__ . '/db_config.php';

$sql = "CREATE TABLE IF NOT EXISTS claim_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_code VARCHAR(20) NOT NULL UNIQUE,
  report_id VARCHAR(32) NOT NULL,
  item_type ENUM('Found','Lost') DEFAULT 'Found',
  user_id INT NOT NULL,
  details TEXT NOT NULL,
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
