<?php
session_start();
session_unset();
session_destroy();

// Still protect against caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

echo json_encode(["success" => true, "msg" => "Logged out successfully"]);
exit;
