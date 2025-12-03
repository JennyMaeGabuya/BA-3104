<?php

declare(strict_types=1);

/**
 * Lost & Found matching utilities for BatStateU FindIt.
 * Contains helper functions to fetch verified reports, compute match scores,
 * and build lookup datasets for admin dashboards and claim reviews.
 */

const MATCH_STOP_WORDS = [
	'the', 'a', 'an', 'of', 'for', 'and', 'with', 'is', 'it', 'this', 'that',
	'has', 'have', 'color', 'brand', 'item', 'lost', 'found', 'bag', 'wallet'
];

function match_normalize(string $value): string {
	$lower = strtolower($value);
	$sanitized = preg_replace('/[^a-z0-9\s]+/', ' ', $lower ?? '');
	return trim(preg_replace('/\s+/', ' ', $sanitized ?? '') ?? '');
}

function match_tokenize(string $value): array {
	$normalized = match_normalize($value);
	if ($normalized === '') {
		return [];
	}
	$parts = preg_split('/\s+/', $normalized) ?: [];
	$filtered = array_values(array_filter($parts, static function (string $token): bool {
		return $token !== '' && !in_array($token, MATCH_STOP_WORDS, true);
	}));
	return $filtered;
}

function match_jaccard(array $a, array $b): float {
	if (!$a || !$b) {
		return 0.0;
	}
	$setA = array_unique($a);
	$setB = array_unique($b);
	$intersection = count(array_intersect($setA, $setB));
	$union = count(array_unique(array_merge($setA, $setB)));
	if ($union === 0) {
		return 0.0;
	}
	return $intersection / $union;
}

function match_quality_label(int $score): string {
	if ($score >= 80) {
		return 'Excellent Match';
	}
	if ($score >= 65) {
		return 'Good Match';
	}
	if ($score >= 50) {
		return 'Possible Match';
	}
	if ($score >= 40) {
		return 'Weak Match';
	}
	return 'Hidden';
}

function match_score_pair(array $lost, array $found): array {
	if (strcasecmp($lost['category'] ?? '', $found['category'] ?? '') !== 0) {
		return [0, ['Different categories']];
	}

	$score = 0;
	$reasons = [];

	$score += 30;
	$reasons[] = 'Same category: ' . ($lost['category'] ?? '');

	$locationLost = match_normalize((string)($lost['location'] ?? ''));
	$locationFound = match_normalize((string)($found['location'] ?? ''));
	if ($locationLost !== '' && $locationLost === $locationFound) {
		$score += 25;
		$reasons[] = 'Exact location match';
	} elseif ($locationLost !== '' && $locationFound !== '' && (str_contains($locationLost, $locationFound) || str_contains($locationFound, $locationLost))) {
		$score += 20;
		$reasons[] = 'Locations contain each other';
	} else {
		$locScore = (int)round(match_jaccard(match_tokenize($lost['location'] ?? ''), match_tokenize($found['location'] ?? '')) * 25 * 0.6);
		$score += $locScore;
		$reasons[] = 'Location overlap (+' . $locScore . ')';
	}

	$dateLost = $lost['event_date'] ?? null;
	$dateFound = $found['event_date'] ?? null;
	$datePoints = 0;
	if ($dateLost && $dateFound) {
		try {
			$lostDate = new DateTimeImmutable($dateLost);
			$foundDate = new DateTimeImmutable($dateFound);
			$delta = abs((int)$lostDate->diff($foundDate)->format('%a'));
			if ($delta === 0) {
				$datePoints = 20;
			} elseif ($delta <= 3) {
				$datePoints = 16;
			} elseif ($delta <= 7) {
				$datePoints = 12;
			} elseif ($delta <= 14) {
				$datePoints = 8;
			} elseif ($delta <= 30) {
				$datePoints = 4;
			}
			if ($datePoints > 0) {
				$reasons[] = 'Found within ' . $delta . ' day(s) (+' . $datePoints . ')';
			} else {
				$reasons[] = 'Dates far apart';
			}
			$score += $datePoints;
		} catch (Throwable $e) {
			$reasons[] = 'Invalid date information';
		}
	}

	$nameLost = match_normalize((string)($lost['item_name'] ?? ''));
	$nameFound = match_normalize((string)($found['item_name'] ?? ''));
	if ($nameLost !== '' && $nameLost === $nameFound) {
		$score += 15;
		$reasons[] = 'Exact item name match (+15)';
	} elseif ($nameLost !== '' && $nameFound !== '' && (str_contains($nameLost, $nameFound) || str_contains($nameFound, $nameLost))) {
		$score += 12;
		$reasons[] = 'Partial item name match (+12)';
	} else {
		$nameScore = (int)round(min(0.6, match_jaccard(match_tokenize($lost['item_name'] ?? ''), match_tokenize($found['item_name'] ?? ''))) * 15);
		$score += $nameScore;
		$reasons[] = 'Item name keyword overlap (+' . $nameScore . ')';
	}

	$descOverlap = array_intersect(match_tokenize($lost['description'] ?? ''), match_tokenize($found['description'] ?? ''));
	$descScore = min(10, count(array_unique($descOverlap)) * 2);
	$score += $descScore;
	$reasons[] = 'Description keyword overlap (+' . $descScore . ')';

	if (!empty($lost['photo_path']) && !empty($found['photo_path'])) {
		$score += 5;
		$reasons[] = 'Both reports include photos (+5)';
	}

	return [min(100, $score), $reasons];
}

function match_format_photo(?string $path): string {
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

function fetch_verified_reports(PDO $pdo, string $type): array {
	if ($type === 'lost') {
		$sql = "SELECT id, report_id, item_name, category, description, location, date_lost AS event_date, photo_path, status, created_at, updated_at
				FROM lost_reports WHERE status = 'Verified' ORDER BY updated_at DESC LIMIT 200";
	} else {
		$sql = "SELECT id, report_id, item_name, category, description, location_found AS location, date_found AS event_date, photo_path, status, created_at, updated_at
				FROM found_reports WHERE status = 'Verified' ORDER BY updated_at DESC LIMIT 200";
	}
	$stmt = $pdo->query($sql);
	$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
	foreach ($rows as &$row) {
		$row['report_type'] = $type;
		$row['photo_url'] = match_format_photo($row['photo_path'] ?? '');
	}
	unset($row);
	return $rows;
}

function compute_verified_matches(PDO $pdo): array {
	$lostReports = fetch_verified_reports($pdo, 'lost');
	$foundReports = fetch_verified_reports($pdo, 'found');
	$matches = [];

	foreach ($lostReports as $lost) {
		foreach ($foundReports as $found) {
			[$score, $reasons] = match_score_pair($lost, $found);
			if ($score >= 40) {
				$matches[] = [
					'score' => $score,
					'label' => match_quality_label($score),
					'lost_report' => $lost,
					'found_report' => $found,
					'reasons' => $reasons,
				];
			}
		}
	}

	usort($matches, static function (array $a, array $b): int {
		return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
	});

	$lostLookup = [];
	$foundLookup = [];
	foreach ($matches as $match) {
		$lostId = $match['lost_report']['report_id'] ?? '';
		$foundId = $match['found_report']['report_id'] ?? '';
		if ($lostId !== '') {
			$lostLookup[$lostId][] = $match;
		}
		if ($foundId !== '') {
			$foundLookup[$foundId][] = $match;
		}
	}

	foreach ($lostLookup as &$list) {
		usort($list, static function (array $a, array $b): int {
			return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
		});
	}
	unset($list);
	foreach ($foundLookup as &$list) {
		usort($list, static function (array $a, array $b): int {
			return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
		});
	}
	unset($list);

	return [
		'lost_reports' => $lostReports,
		'found_reports' => $foundReports,
		'matches' => $matches,
		'lost_lookup' => $lostLookup,
		'found_lookup' => $foundLookup,
	];
}

function summarize_best_match(array $lookup, string $reportId): ?array {
	if (!isset($lookup[$reportId]) || !$lookup[$reportId]) {
		return null;
	}
	return $lookup[$reportId][0];
}

