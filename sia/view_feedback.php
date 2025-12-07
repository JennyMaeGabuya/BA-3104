<?php
include('db.php');

if (!isset($_GET['complaint_id'])) {
    echo "<p>Invalid request.</p>";
    exit;
}

$complaint_id = intval($_GET['complaint_id']);
$result = $conn->query("SELECT * FROM feedback WHERE complaint_id = $complaint_id ORDER BY created_at DESC");

if ($result->num_rows > 0) {
    echo "<table style='width:100%; border-collapse: collapse;'>";
    echo "<tr><th>Feedback</th><th>Date Sent</th></tr>";

    while ($fb = $result->fetch_assoc()) {
        $feedbackId = $fb['id'];
        $feedbackText = htmlspecialchars($fb['feedback_text'], ENT_QUOTES);
        $createdAt = $fb['created_at'];

        echo "
        <tr>
            <td>
                <button class='show-complaint-btn' onclick='showSingleFeedback(\"$feedbackText\")'>Show Feedback</button>
            </td>
            <td>{$createdAt}</td>
        </tr>";
    }

    echo "</table>";
} else {
    echo "<p style='text-align: center; color: #666; padding: 1rem;'>No feedback yet for this complaint.</p>";
}
?>
