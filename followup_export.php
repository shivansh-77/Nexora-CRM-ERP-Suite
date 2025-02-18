<?php
include('connection.php');
require 'vendor/autoload.php'; // Include PHPSpreadsheet's autoloader

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Fetch data (replace with your actual database logic)
$contact_id = $_POST['contact_id'];
$followup_id = $_POST['followup_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$lead_for = $_POST['lead_for'];

// Example: Fetch filtered data from the database


$query = "SELECT * FROM followups WHERE contact_id = '$contact_id'";
if (!empty($followup_id)) {
    $query .= " AND followup_id = '$followup_id'";
}
if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND followup_date BETWEEN '$start_date' AND '$end_date'";
}
if (!empty($lead_for)) {
    $query .= " AND lead_for = '$lead_for'";
}

$result = $conn->query($query);

// Create a new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add headers
$sheet->setCellValue('A1', 'Follow-up ID')
      ->setCellValue('B1', 'Contact ID')
      ->setCellValue('C1', 'Follow-up Date')
      ->setCellValue('D1', 'Lead For')
      ->setCellValue('E1', 'Remarks');

// Add data rows
$rowNumber = 2; // Start in the second row
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowNumber, $row['followup_id'])
          ->setCellValue('B' . $rowNumber, $row['contact_id'])
          ->setCellValue('C' . $rowNumber, $row['followup_date'])
          ->setCellValue('D' . $rowNumber, $row['lead_for'])
          ->setCellValue('E' . $rowNumber, $row['remarks']);
    $rowNumber++;
}

// Write the spreadsheet to a file
$filename = "Followup_Export_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
