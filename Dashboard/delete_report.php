<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../db_config.php';

function redirect_back($ok = true, $msg = ''): void {
    $q = $ok ? 'deleted=1' : 'deleted=0';
    if ($msg !== '') {
        $q .= '&msg=' . urlencode($msg);
    }
    header('Location: /BA-3104/Dashboard/my_report.php?' . $q);
    exit;
}

try {
    if (!isset($_GET['id'])) {
        redirect_back(false, 'Missing report id');
    }

    $reportId = trim((string)$_GET['id']);
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        header('Location: /BA-3104/login.php');
        exit;
    }

    $isFound = str_starts_with($reportId, 'FR-');
    $table = $isFound ? 'found_reports' : 'lost_reports';

    $stmt = $pdo->prepare("DELETE FROM $table WHERE report_id = :rid AND user_id = :uid");
    $stmt->execute([':rid' => $reportId, ':uid' => $userId]);

    if ($stmt->rowCount() === 1) {
        redirect_back(true);
    } else {
        redirect_back(false, 'Report not found or not yours');
    }
} catch (Throwable $e) {
    error_log('delete_report error: ' . $e->getMessage());
    redirect_back(false, 'Server error');
}
