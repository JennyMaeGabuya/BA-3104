<?php
session_start();
// logged-in admins can export data
if (empty($_SESSION['userId'])) {
    // If not logged in, then Go away.....
    header('Location: login.php');
    exit;
}

require_once 'src/config.php';

// Replicate Filter Logic from admin.php
// Get filters from the URL (GET parameters)
$filter_user_type = $_GET['user_type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['filter_date'] ?? ''; 
$search_term = $_GET['search'] ?? '';

$where_clauses = [];
$params = [];

// Filter by User Type
if ($filter_user_type !== 'all') {
    $where_clauses[] = "user_type = ?"; 
    $params[] = $filter_user_type;
}

// Filter by Status
if ($filter_status === 'checked_in') {
    $where_clauses[] = "time_out IS NULL";
} elseif ($filter_status === 'checked_out') {
    $where_clauses[] = "time_out IS NOT NULL";
}

// Filter by Date
if (!empty($filter_date)) {
    $where_clauses[] = "DATE(time_in) = ?";
    $params[] = $filter_date;
}

// Filter by Search Term
if (!empty($search_term)) {
    $where_clauses[] = "(
        full_name LIKE ? OR 
        srcode LIKE ? OR 
        purpose LIKE ?
    )";
    $like_term = '%' . $search_term . '%';
    $params[] = $like_term; // for full_name
    $params[] = $like_term; // for srcode
    $params[] = $like_term; // for purpose
}

// Build the final WHERE clause
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Fetch the Data/s
try {
    $pdo = connect_db(); 
    // Select specific columns
    $sql = "SELECT visitor_id, time_in, full_name, user_type, srcode, contact, purpose, time_out FROM visitors" . $where_sql . " ORDER BY time_in DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database Error: Could not fetch data for export.");
}

// Generate XML Output
// Set the headers to indicate an XML file download
header('Content-Type: text/xml');
header('Content-Disposition: attachment; filename="visitor_log_' . date('Ymd_His') . '.xml"');

// Create the root element of the XML document
$xml = new SimpleXMLElement('<VisitorLog/>');

foreach ($visitors as $visitor) {
    // Create the <Visitor> node for each record
    $visitor_node = $xml->addChild('Visitor');
    
    // Add attributes/elements for visitor data
    foreach ($visitor as $key => $value) {
        // XML nodes cannot start with numbers or contain hyphens, so we clean the keys
        $clean_key = str_replace(['_', '-'], '', $key); 
        
        // Convert NULL values to empty string for XML output
        $clean_value = ($value === NULL) ? '' : htmlspecialchars($value);

        // Add the data as an element
        $visitor_node->addChild($clean_key, $clean_value);
    }
}

// Output the generated XML content
echo $xml->asXML();
exit;

?>