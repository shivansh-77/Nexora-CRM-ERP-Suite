<?php
include 'connection.php'; // Include your database connection file

// Initialize variables for filter inputs
$contact_id = $_GET['id'] ?? ''; // Get contact_id from the URL
$followup_id = $_POST['followup_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$lead_for = $_POST['lead_for'] ?? '';

// Initialize arrays for follow-up IDs and Lead For options
$followup_ids = [];
$lead_for_options = [];

// Fetch distinct follow-up IDs for the selected contact ID
if (!empty($contact_id)) {
    $stmt = $connection->prepare("SELECT DISTINCT followup_id FROM followup_history WHERE contact_id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $followup_ids[] = $row['followup_id'];
    }
    $stmt->close();

    // Fetch distinct Lead For values for the selected contact ID
    $stmt = $connection->prepare("SELECT DISTINCT lead_for FROM followup_history WHERE contact_id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $lead_for_options[] = $row['lead_for'];
    }
    $stmt->close();
}

// Initialize the filtered data
$filtered_data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filter data based on submitted filters
    $filter_query = "SELECT * FROM followup_history WHERE contact_id = ?";
    $params = [$contact_id];
    $types = "i";

    if (!empty($followup_id)) {
        $filter_query .= " AND followup_id = ?";
        $params[] = $followup_id;
        $types .= "i";
    }
    if (!empty($start_date) && !empty($end_date)) {
        $filter_query .= " AND followup_date_nxt BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }
    if (!empty($lead_for)) {
        $filter_query .= " AND lead_for = ?";
        $params[] = $lead_for;
        $types .= "s";
    }

    // Prepare and execute the statement
    $stmt = $connection->prepare($filter_query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $filtered_data = $stmt->get_result();
}
?>
