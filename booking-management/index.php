<?php

$page = $_GET['page'] ?? 'landing_page';
$file = __DIR__ . "/pages/{$page}.php";

include_once __DIR__ . '/includes/header.php';

if (file_exists($file)) {
    include $file;
} else {
    include __DIR__ . '/pages/error.php';
}

include_once __DIR__ . '/includes/footer.php';
