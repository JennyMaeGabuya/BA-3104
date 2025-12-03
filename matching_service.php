<?php

/**
 * Matching service for Claim Requests vs Found Reports.
 *
 * Usage:
 *   require_once __DIR__ . '/matching_service.php';
 *   run_claim_matching($pdo, $claimId);
 */

require_once __DIR__ . '/db_config.php';

const MATCH_DEFAULT_THRESHOLD = 75;
const MATCH_MAX_CANDIDATES = 25;

/**
 * Ensure match_results table exists before attempting to write signals.
 */
function ensure_match_results_table(PDO $pdo): void {
  static $ensured = false;
  if ($ensured) {
    return;
  }
  $sql = "CREATE TABLE IF NOT EXISTS match_results (
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
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
  $pdo->exec($sql);
  $ensured = true;
}

/**
 * Entry point used by claim submission flow.
 *
 * @return array{results: array, threshold: int}
 */
function run_claim_matching(PDO $pdo, int $claimId, array $options = []): array {
  ensure_match_results_table($pdo);

  $claim = fetch_claim_request($pdo, $claimId);
  if (!$claim) {
    return ['results' => [], 'threshold' => MATCH_DEFAULT_THRESHOLD];
  }

  $threshold = (int)($options['threshold'] ?? get_config_value($pdo, 'match_confidence_threshold', MATCH_DEFAULT_THRESHOLD));
  $candidates = fetch_candidate_found_reports($pdo, $claim);
  $results = [];

  foreach ($candidates as $found) {
    $scoreData = score_claim_against_found($claim, $found);
    if ($scoreData['score'] <= 0) {
      continue;
    }
    $classification = $scoreData['score'] >= $threshold ? 'probable' : 'possible';
    $resultRow = upsert_match_result($pdo, $claim, $found, $scoreData, $classification);
    if ($resultRow) {
      $results[] = $resultRow;
    }
  }

  record_audit_event($pdo, [
    'action_type' => 'match_run',
    'actor_id' => $claim['user_id'] ?? null,
    'actor_role' => 'user',
    'subject_type' => 'claim_request',
    'subject_id' => (string)$claimId,
    'payload' => [
      'threshold' => $threshold,
      'candidate_count' => count($candidates),
      'match_count' => count($results),
    ],
  ]);

  return ['results' => $results, 'threshold' => $threshold];
}

function fetch_claim_request(PDO $pdo, int $claimId): ?array {
  $sql = "SELECT cr.*, fr.id AS found_row_id, fr.report_id AS found_report_code, fr.item_name AS found_item_name,
                 fr.category AS found_category, fr.description AS found_description, fr.location_found AS found_location,
                 fr.date_found AS found_date, fr.photo_path AS found_photo_path
          FROM claim_requests cr
          LEFT JOIN found_reports fr ON fr.report_id = cr.report_id
          WHERE cr.id = :id LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id' => $claimId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function fetch_candidate_found_reports(PDO $pdo, array $claim): array {
  $category = $claim['found_category'] ?? $claim['category'] ?? '';
  $primaryReport = $claim['report_id'] ?? '';
  $claimDate = $claim['created_at'] ?? date('Y-m-d');

  $sql = "SELECT fr.*,
                 (fr.report_id = :primary) AS is_primary,
                 ABS(DATEDIFF(fr.date_found, :claimDate)) AS date_gap
          FROM found_reports fr
          WHERE (fr.status = 'Verified' OR fr.report_id = :primary)
            AND (:category = '' OR fr.category = :category)
          ORDER BY is_primary DESC, date_gap ASC, fr.updated_at DESC
          LIMIT " . MATCH_MAX_CANDIDATES;
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':category' => $category,
    ':primary' => $primaryReport,
    ':claimDate' => $claimDate,
  ]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function score_claim_against_found(array $claim, array $found): array {
  $signals = [];
  $totalScore = 0;

  $totalScore += $signals[] = referenced_report_signal($claim, $found);
  $totalScore += $signals[] = category_signal($claim, $found);
  $totalScore += $signals[] = item_name_signal($claim, $found);
  $totalScore += $signals[] = description_signal($claim, $found);
  $totalScore += $signals[] = unique_identifier_signal($claim, $found);
  $totalScore += $signals[] = date_signal($claim, $found);
  $totalScore += $signals[] = location_signal($claim, $found);
  $totalScore += $signals[] = image_signal($claim, $found);

  $score = array_sum(array_column($signals, 'score'));
  $score = max(0, min(100, $score));

  return ['score' => (int)round($score), 'signals' => $signals];
}

function referenced_report_signal(array $claim, array $found): array {
  $claimCode = strtolower(trim($claim['report_id'] ?? ''));
  $foundCode = strtolower(trim($found['report_id'] ?? ''));
  $score = ($claimCode !== '' && $claimCode === $foundCode) ? 55 : 0;
  return ['label' => 'Report reference match', 'score' => $score, 'details' => [$claimCode, $foundCode]];
}

function category_signal(array $claim, array $found): array {
  $claimCategory = strtolower(trim($claim['found_category'] ?? $claim['category'] ?? ''));
  $foundCategory = strtolower(trim($found['category'] ?? ''));
  $score = 0;
  if ($claimCategory && $foundCategory) {
    $score = $claimCategory === $foundCategory ? 25 : (jaccard_similarity($claimCategory, $foundCategory) >= 0.5 ? 15 : 0);
  }
  return ['label' => 'Category match', 'score' => $score, 'details' => [$claimCategory, $foundCategory]];
}

function item_name_signal(array $claim, array $found): array {
  $claimName = $claim['item_name'] ?? $claim['details'] ?? '';
  $foundName = $found['item_name'] ?? '';
  $similarity = token_similarity($claimName, $foundName);
  $score = (int)round($similarity * 20);
  return ['label' => 'Item name similarity', 'score' => $score, 'details' => ['similarity' => $similarity]];
}

function description_signal(array $claim, array $found): array {
  $claimDesc = $claim['details'] ?? '';
  $foundDesc = $found['description'] ?? '';
  $similarity = token_similarity($claimDesc, $foundDesc);
  $score = (int)round($similarity * 20);
  return ['label' => 'Description similarity', 'score' => $score, 'details' => ['similarity' => $similarity]];
}

function unique_identifier_signal(array $claim, array $found): array {
  $identifiers = extract_identifiers($claim['details'] ?? '');
  $foundBlob = strtolower(($found['description'] ?? '') . ' ' . ($found['item_name'] ?? ''));
  $foundIdentifiers = array_filter($identifiers, function ($id) use ($foundBlob) {
    return $id !== '' && str_contains($foundBlob, strtolower($id));
  });
  $score = $foundIdentifiers ? 25 : 0;
  return ['label' => 'Unique identifiers', 'score' => $score, 'details' => $foundIdentifiers ?: ['match' => false]];
}

function date_signal(array $claim, array $found): array {
  $claimDate = $claim['created_at'] ?? null;
  $foundDate = $found['date_found'] ?? null;
  $score = 0;
  $diff = null;
  if ($claimDate && $foundDate) {
    $diff = abs((int)((new DateTime($claimDate))->diff(new DateTime($foundDate)))->format('%a'));
    if ($diff === 0) $score = 5;
    elseif ($diff <= 3) $score = 4;
    elseif ($diff <= 7) $score = 3;
    elseif ($diff <= 14) $score = 2;
  }
  return ['label' => 'Date proximity', 'score' => $score, 'details' => ['days_apart' => $diff]];
}

function location_signal(array $claim, array $found): array {
  $claimLocation = strtolower(trim($claim['last_seen_location'] ?? ''));
  $descriptionHints = strtolower(trim($claim['details'] ?? ''));
  $foundLocation = strtolower(trim($found['location_found'] ?? ''));
  $score = 0;
  $similarity = 0.0;
  if ($foundLocation) {
    $source = $claimLocation ?: $descriptionHints;
    if ($source) {
      $similarity = token_similarity($source, $foundLocation);
      if ($similarity >= 0.75) $score = 5;
      elseif ($similarity >= 0.5) $score = 4;
      elseif ($similarity >= 0.35) $score = 3;
      elseif ($similarity >= 0.25) $score = 1;
    }
  }
  return ['label' => 'Location hints', 'score' => $score, 'details' => [
    'last_seen' => $claimLocation,
    'found_location' => $foundLocation,
    'similarity' => $similarity,
  ]];
}

function image_signal(array $claim, array $found): array {
  $claimPhoto = $claim['item_photo_path'] ?? '';
  $foundPhoto = $found['photo_path'] ?? '';
  if (!$claimPhoto || !$foundPhoto) {
    return ['label' => 'Image similarity', 'score' => 0, 'details' => ['available' => false]];
  }
  $hashA = safe_hash_file($claimPhoto);
  $hashB = safe_hash_file($foundPhoto);
  if (!$hashA || !$hashB) {
    return ['label' => 'Image similarity', 'score' => 0, 'details' => ['available' => false]];
  }
  $distance = hamming_distance($hashA, $hashB);
  $score = max(0, 25 - $distance);
  return ['label' => 'Image similarity', 'score' => $score, 'details' => ['hash_distance' => $distance]];
}

function safe_hash_file(string $relativePath): ?string {
  $fullPath = __DIR__ . '/' . ltrim($relativePath, '/');
  if (!is_file($fullPath)) {
    return null;
  }
  return substr(sha1_file($fullPath), 0, 16);
}

function extract_identifiers(string $text): array {
  if ($text === '') return [];
  preg_match_all('/[A-Z0-9]{5,}/i', strtoupper($text), $matches);
  return array_unique($matches[0] ?? []);
}

function token_similarity(string $a, string $b): float {
  $tokensA = tokenize($a);
  $tokensB = tokenize($b);
  if (!$tokensA || !$tokensB) {
    return 0.0;
  }
  $intersection = count(array_intersect($tokensA, $tokensB));
  $union = count(array_unique(array_merge($tokensA, $tokensB)));
  return $union === 0 ? 0.0 : $intersection / $union;
}

function tokenize(string $value): array {
  $value = strtolower($value);
  $value = preg_replace('/[^a-z0-9\s]+/', ' ', $value);
  $value = preg_replace('/\s+/', ' ', $value);
  $value = trim($value);
  if ($value === '') {
    return [];
  }
  return array_values(array_filter(explode(' ', $value)));
}

function jaccard_similarity(string $a, string $b): float {
  $tokensA = tokenize($a);
  $tokensB = tokenize($b);
  if (!$tokensA || !$tokensB) {
    return 0.0;
  }
  $intersection = count(array_intersect($tokensA, $tokensB));
  $union = count(array_unique(array_merge($tokensA, $tokensB)));
  return $union === 0 ? 0.0 : $intersection / $union;
}

function hamming_distance(string $hashA, string $hashB): int {
  $distance = 0;
  $len = min(strlen($hashA), strlen($hashB));
  for ($i = 0; $i < $len; $i++) {
    if ($hashA[$i] !== $hashB[$i]) {
      $distance++;
    }
  }
  return $distance + abs(strlen($hashA) - strlen($hashB));
}

function upsert_match_result(PDO $pdo, array $claim, array $found, array $scoreData, string $classification): ?array {
  if (empty($found['id']) || empty($claim['id'])) {
    return null;
  }
  $sql = "INSERT INTO match_results (claim_request_id, found_report_id, confidence, classification, breakdown, status, created_at, updated_at)
          VALUES (:claimId, :foundId, :confidence, :classification, :breakdown, 'pending_review', NOW(), NOW())
          ON DUPLICATE KEY UPDATE confidence = VALUES(confidence), classification = VALUES(classification), breakdown = VALUES(breakdown), updated_at = NOW()";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':claimId' => $claim['id'],
    ':foundId' => $found['id'],
    ':confidence' => $scoreData['score'],
    ':classification' => $classification,
    ':breakdown' => json_encode($scoreData['signals'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
  ]);

  return [
    'claim_request_id' => $claim['id'],
    'found_report_id' => $found['id'],
    'confidence' => $scoreData['score'],
    'classification' => $classification,
    'status' => 'pending_review',
    'breakdown' => $scoreData['signals'],
  ];
}

function get_config_value(PDO $pdo, string $key, $default = null) {
  $stmt = $pdo->prepare('SELECT config_value FROM app_config WHERE config_key = :key LIMIT 1');
  $stmt->execute([':key' => $key]);
  $value = $stmt->fetchColumn();
  if ($value === false) {
    return $default;
  }
  return is_numeric($value) ? (int)$value : $value;
}

function record_audit_event(PDO $pdo, array $data): void {
  $stmt = $pdo->prepare('INSERT INTO audit_logs (action_type, actor_id, actor_role, subject_type, subject_id, payload, created_at)
                         VALUES (:action_type, :actor_id, :actor_role, :subject_type, :subject_id, :payload, NOW())');
  $stmt->execute([
    ':action_type' => $data['action_type'],
    ':actor_id' => $data['actor_id'],
    ':actor_role' => $data['actor_role'] ?? 'system',
    ':subject_type' => $data['subject_type'],
    ':subject_id' => $data['subject_id'],
    ':payload' => json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
  ]);
}

function warm_claim_matches(PDO $pdo, int $limit = 5): void {
  ensure_match_results_table($pdo);
  $sql = "SELECT cr.id FROM claim_requests cr
          WHERE NOT EXISTS (SELECT 1 FROM match_results mr WHERE mr.claim_request_id = cr.id)
          ORDER BY cr.created_at DESC
          LIMIT :limit";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
  $stmt->execute();
  $claimIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
  foreach ($claimIds as $claimId) {
    try {
      run_claim_matching($pdo, (int)$claimId);
    } catch (Throwable $e) {
      error_log('Background claim matching failed for ID ' . $claimId . ': ' . $e->getMessage());
    }
  }
}
