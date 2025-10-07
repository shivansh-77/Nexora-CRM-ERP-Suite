<?php
include('connection.php'); // Include your database connection

function generateSalarySheetNo($start_date) {
    global $connection;

    // Generate base for salary_sheet_no
    $current_month = date('m', strtotime($start_date)); // Get month as two digits (04 for April)
    $current_year_short = date('y', strtotime($start_date)); // Get year as two digits (25 for 2025)
    $base_sheet_no = "SAL/{$current_year_short}{$current_month}/";

    // Get the last sequence number used for this month/year
    $last_sequence = 0;
    $last_sheet_query = "SELECT salary_sheet_no FROM salary
                        WHERE salary_sheet_no LIKE ?
                        ORDER BY salary_sheet_no DESC
                        LIMIT 1";
    $stmt = mysqli_prepare($connection, $last_sheet_query);
    $like_pattern = $base_sheet_no . '%';
    mysqli_stmt_bind_param($stmt, 's', $like_pattern);
    mysqli_stmt_execute($stmt);
    $last_sheet_result = mysqli_stmt_get_result($stmt);

    if ($last_sheet_result && $last_sheet_data = mysqli_fetch_assoc($last_sheet_result)) {
        // Extract the numeric part at the end of the string
        if (preg_match('/(\d+)$/', $last_sheet_data['salary_sheet_no'], $matches)) {
            $last_sequence = (int)$matches[1];
        } else {
            error_log("Failed to extract sequence from salary_sheet_no: " . $last_sheet_data['salary_sheet_no']);
        }
    } else {
        error_log("No existing salary_sheet_no found for pattern: $base_sheet_no");
    }

    // Increment sequence for new record
    $last_sequence++;
    $new_sequence = str_pad($last_sequence, 4, '0', STR_PAD_LEFT);
    $salary_sheet_no = $base_sheet_no . $new_sequence;

    // Debug the generated salary_sheet_no
    error_log("Generated salary_sheet_no: $salary_sheet_no");

    return $salary_sheet_no;
}

// Check if the script is called with the correct parameter
if (isset($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
    $salary_sheet_no = generateSalarySheetNo($start_date);
    echo $salary_sheet_no;
} else {
    echo "Error: start_date parameter is missing.";
}
?>
