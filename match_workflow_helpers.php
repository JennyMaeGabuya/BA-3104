<?php

/**
 * Shared helpers for match workflow tracking.
 */
function ensure_match_workflow_table(PDO $pdo): void {
  $sql = "CREATE TABLE IF NOT EXISTS match_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lost_report_id VARCHAR(32) NOT NULL,
    found_report_id VARCHAR(32) NOT NULL,
    status ENUM('pending','claimable','claimed','rejected') NOT NULL DEFAULT 'pending',
    admin_id INT DEFAULT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_match_pair (lost_report_id, found_report_id),
    INDEX idx_status (status),
    INDEX idx_lost (lost_report_id),
    INDEX idx_found (found_report_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
  $pdo->exec($sql);
}

function fetch_match_workflow_map(PDO $pdo): array {
  ensure_match_workflow_table($pdo);
  $map = [];
  try {
    $stmt = $pdo->query("SELECT lost_report_id, found_report_id, status FROM match_workflows");
    if ($stmt) {
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = ($row['lost_report_id'] ?? '') . '|' . ($row['found_report_id'] ?? '');
        if (trim($key, '|') === '') {
          continue;
        }
        $map[$key] = $row['status'] ?? 'pending';
      }
    }
  } catch (Throwable $e) {
    return [];
  }
  return $map;
}

function match_workflow_label(string $status): string {
  return match ($status) {
    'claimable' => 'Ready for pickup',
    'claimed' => 'Claim completed',
    'rejected' => 'Match dismissed',
    default => 'Pending review',
  };
}
