<?php
include('connection.php');

// Debug: Check the quotation ID
$quotation_id = isset($_GET['id']) ? $_GET['id'] : null;
if ($quotation_id === null) {
    die('Quotation ID not provided.');
}

$query = "SELECT * FROM quotation_items WHERE quotation_id = ?";
$stmt = $connection->prepare($query);
if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($connection->error));
}

$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die('Execute failed: ' . htmlspecialchars($stmt->error));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Follow-up History</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS file -->
    <style>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            margin: 0; /* Remove margin */
    padding: 0; /* Remove padding */
    height: 100vh; /* Ensure body takes full viewport height */
    overflow: hidden; /* Disable scroll unless necessary */
}

        .wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 0; /* Remove margin */
  height: 100%; /* Ensure wrapper takes full height */
}

  .container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 20px;
      width: 120%; /* Ensures the container spans full width */
      max-width: 1200px;
      max-height: 5500px;
      height: 100%;
  }

        .card, .table-container {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            width: 100%; /* Makes cards wide enough to cover the screen */
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-item {
            flex: 1 1 calc(25% - 20px); /* Ensure all fields are in a single row */
            display: flex;
            flex-direction: column;
        }

        .filter-item label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .filter-item select,
        .filter-item input {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .filter-button-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .filter-button-container button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .filter-button-container button:hover {
            background-color: #2c3e50;
        }
        .filter-form {
            display: flex;
            gap: 20px;
            flex-wrap: nowrap; /* Keep all items in a single row */
            align-items: center; /* Align button with form fields */
        }

        .filter-button-container {
            display: flex;
            justify-content: flex-start; /* Keep the button aligned beside the last input */
            gap: 20px;
        }

        .filter-button-container button {
            margin-left: 20px; /* Space between the button and the last filter item */
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table-container table th,
        .table-container table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            font-size: 14px;

        }

        .table-container table th {
            background-color: #f4f4f4;
            color: #333;
        }

        .table-container table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table-container table tr:hover {
            background-color: #f1f1f1;
        }

        .contact-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Two columns */
            gap: 15px; /* Space between items */
            justify-content: right; /* Aligns the grid to the right */
            text-align: left; /* Keeps the text aligned to the left within each cell */

        }

        .contact-details p {
            margin: 0;
            font-size: 16px;
        }

        .contact-details strong {
            color: #2c3e50;
        }
        /* Styles for the export button */
        .card-header {
    display: flex;
    justify-content: space-between; /* Place h2 and button on the same line with space between them */
    align-items: center; /* Align items vertically in the center */
    margin-bottom: 15px; /* Add spacing below the header */


}

.card-header h2 {
    margin: 0; /* Remove default margin from the heading */
    margin-left: 400px;
    padding: 0;
    margin-bottom: 8px;
}

.export-button-container button {
    padding: 7px 15px;
    font-size: 16px;
    background-color: #2c3e50;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;


}

.export-button-container button:hover {
    background-color: #2c3e50;
}

    </style>

    <div class="table-container">
         <h2>Quotation Details</h2>
         <table>
             <thead>
                 <tr>
                     <th>ID</th>
                     <th>Product ID</th>
                     <th>Quantity</th>
                     <th>Rate</th>
                     <th>GST</th>
                     <th>Amount</th>
                     <th>Created At</th>
                 </tr>
             </thead>
             <tbody>
                 <?php if ($result && $result->num_rows > 0): ?>
                     <?php while ($row = $result->fetch_assoc()): ?>
                         <tr>
                             <td><?= htmlspecialchars($row['id']) ?></td>
                             <td><?= htmlspecialchars($row['product_id']) ?></td>
                             <td><?= htmlspecialchars($row['quantity']) ?></td>
                             <td><?= htmlspecialchars($row['rate']) ?></td>
                             <td><?= htmlspecialchars($row['gst']) ?></td>
                             <td><?= htmlspecialchars($row['amount']) ?></td>
                             <td><?= htmlspecialchars($row['created_at']) ?></td>
                         </tr>
                     <?php endwhile; ?>
                 <?php else: ?>
                     <tr>
                         <td colspan="7">No entries found for this quotation ID.</td>
                     </tr>
                 <?php endif; ?>
             </tbody>
         </table>
     </div>


</html>
