<?php
include 'connection.php'; // Replace with your actual connection file

$update_message = '';
$invoice = null;
$items = [];

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $item_ids = $_POST['item_id'];

        // AMC Fields
        $amc_codes = $_POST['amc_code'];
        $amc_paid_dates = $_POST['amc_paid_date'];
        $amc_due_dates = $_POST['amc_due_date'];
        $amc_amounts = $_POST['amc_amount'];

        // Validate each item
        $validation_errors = [];
        foreach ($item_ids as $index => $item_id) {
            // Fetch product_id and product_name for the current item
            $product_query = "SELECT product_id, product_name FROM invoice_items WHERE id = ?";
            $stmt_product = $connection->prepare($product_query);
            $stmt_product->bind_param("i", $item_id);
            $stmt_product->execute();
            $product_result = $stmt_product->get_result();
            $product_row = $product_result->fetch_assoc();
            $product_id = $product_row['product_id'];
            $product_name = $product_row['product_name']; // Get product name
            $stmt_product->close();

            // Fetch tracking flags for the product (only AMC tracking now)
            $tracking_query = "SELECT amc_tracking FROM item WHERE item_code = ?";
            $stmt_tracking = $connection->prepare($tracking_query);
            $stmt_tracking->bind_param("s", $product_id);
            $stmt_tracking->execute();
            $tracking_result = $stmt_tracking->get_result();
            $tracking_row = $tracking_result->fetch_assoc();
            $amc_tracking = $tracking_row['amc_tracking'];
            $stmt_tracking->close();

            // Validate AMC tracking flag
            if ($amc_tracking == 1 && (empty($amc_codes[$index]) || empty($amc_paid_dates[$index]) || empty($amc_due_dates[$index]) || empty($amc_amounts[$index]))) {
                $validation_errors[] = "AMC details are required for product: " . $product_name;
            } elseif ($amc_tracking == 0 && (!empty($amc_codes[$index]) || !empty($amc_paid_dates[$index]) || !empty($amc_due_dates[$index]) || !empty($amc_amounts[$index]))) {
                $validation_errors[] = "AMC details are not required for product: " . $product_name . ". Please remove the entries.";
            }
        }

        // If there are validation errors, show an alert and stop processing
        if (!empty($validation_errors)) {
            echo "<script>alert('" . implode("\\n", $validation_errors) . "'); window.history.back();</script>";
            exit();
        }

        // Get the current year and format it to get the last two digits
        $currentYear = date('y');

        // Generate the new invoice number before updating item_ledger_history
        $last_invoice_query = "
            SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_no, 8) AS UNSIGNED)), 0) AS last_invoice_no
            FROM invoices
            WHERE invoice_no LIKE 'INV/$currentYear/%'
        ";
        $last_invoice_result = $connection->query($last_invoice_query);
        $last_invoice = $last_invoice_result->fetch_assoc();

        // Calculate the new sequential number
        $new_sequence_no = $last_invoice['last_invoice_no'] + 1;

        // Format the new invoice number
        $invoice_no = 'INV/' . $currentYear . '/' . str_pad($new_sequence_no, 4, '0', STR_PAD_LEFT);

        // Prepare the update statements
  $update_invoice_query = "UPDATE invoice_items SET amc_code = ?, amc_paid_date = ?, amc_due_date = ?, amc_amount = ? WHERE id = ? AND invoice_id = ?";
  $stmt = $connection->prepare($update_invoice_query);

  $update_ledger_query = "UPDATE item_ledger_history SET invoice_no = ? WHERE invoice_itemid = ?";
  $stmt_ledger = $connection->prepare($update_ledger_query);

  if ($stmt && $stmt_ledger) {
      $update_successful = true;

      // Get all product names first to check for AMC products
      $product_names = [];
      foreach ($item_ids as $index => $item_id) {
          $product_query = "SELECT product_name FROM invoice_items WHERE id = ?";
          $stmt_product = $connection->prepare($product_query);
          $stmt_product->bind_param("i", $item_id);
          $stmt_product->execute();
          $product_result = $stmt_product->get_result();
          $product_row = $product_result->fetch_assoc();
          $product_names[$item_id] = $product_row['product_name'];
          $stmt_product->close();
      }

      // Loop through each item and update both tables
      foreach ($item_ids as $index => $item_id) {
          $amc_code = $amc_codes[$index];
          $amc_paid_date = $amc_paid_dates[$index];
          $amc_due_date = $amc_due_dates[$index];
          $amc_amount = $amc_amounts[$index];

          // Update invoice_items table (including AMC fields)
          $stmt->bind_param("ssssii", $amc_code, $amc_paid_date, $amc_due_date, $amc_amount, $item_id, $invoice_id);
          if (!$stmt->execute()) {
              $update_successful = false;
          }

          // Update item_ledger_history table
          $stmt_ledger->bind_param("si", $invoice_no, $item_id);
          if (!$stmt_ledger->execute()) {
              $update_successful = false;
          }
      }

      // Close statements
      $stmt->close();
      $stmt_ledger->close();

      if ($update_successful) {
          // Check if any product name ends with "-AMC"
          $has_amc_product = false;
          foreach ($product_names as $product_name) {
              if (strpos($product_name, '-AMC') !== false) {
                  $has_amc_product = true;
                  break;
              }
          }

          // Update the invoice status to 'Finalized' and populate pending_amount
          if ($has_amc_product) {
              // For AMC products, set pending_amount to the sum of all AMC amounts
              $update_status_query = "UPDATE invoices
                                      SET status = 'Finalized',
                                          invoice_no = ?,
                                          pending_amount = (
                                              SELECT SUM(amc_amount)
                                              FROM invoice_items
                                              WHERE invoice_id = ?
                                          )
                                      WHERE id = ?";
          } else {
              // For non-AMC products, set pending_amount to net_amount
              $update_status_query = "UPDATE invoices
                                      SET status = 'Finalized',
                                          invoice_no = ?,
                                          pending_amount = net_amount
                                      WHERE id = ?";
          }

          $stmt_status = $connection->prepare($update_status_query);
          if ($has_amc_product) {
              $stmt_status->bind_param("sii", $invoice_no, $invoice_id, $invoice_id);
          } else {
              $stmt_status->bind_param("si", $invoice_no, $invoice_id);
          }
          $stmt_status->execute();
          $stmt_status->close();

                // Update AMC references
                $fetch_reference_query = "SELECT reference_invoice_no FROM invoice_items WHERE invoice_id = ? AND reference_invoice_no IS NOT NULL";
                $stmt_fetch_reference = $connection->prepare($fetch_reference_query);
                $stmt_fetch_reference->bind_param("i", $invoice_id);
                $stmt_fetch_reference->execute();
                $result_fetch_reference = $stmt_fetch_reference->get_result();

                while ($row = $result_fetch_reference->fetch_assoc()) {
                    $reference_invoice_no = $row['reference_invoice_no'];

                    $fetch_invoice_id_query = "SELECT id FROM invoices WHERE invoice_no = ?";
                    $stmt_fetch_invoice_id = $connection->prepare($fetch_invoice_id_query);
                    $stmt_fetch_invoice_id->bind_param("s", $reference_invoice_no);
                    $stmt_fetch_invoice_id->execute();
                    $result_invoice_id = $stmt_fetch_invoice_id->get_result();

                    if ($invoice_row = $result_invoice_id->fetch_assoc()) {
                        $ref_invoice_id = $invoice_row['id'];

                        $update_amc_invoice_query = "UPDATE invoice_items SET new_amc_invoice_no = ?, new_amc_invoice_gen_date = NOW() WHERE invoice_id = ?";
                        $stmt_update_amc_invoice = $connection->prepare($update_amc_invoice_query);
                        $stmt_update_amc_invoice->bind_param("si", $invoice_no, $ref_invoice_id);
                        $stmt_update_amc_invoice->execute();
                        $stmt_update_amc_invoice->close();
                    }

                    $stmt_fetch_invoice_id->close();
                }

                $stmt_fetch_reference->close();

                // Insert entry into party_ledger table
                $insert_party_ledger_query = "INSERT INTO party_ledger
                    (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, ref_doc_no)
                    SELECT
                        'Customer Ledger' AS ledger_type,
                        client_id AS party_no,
                        client_name AS party_name,
                        'Customer' AS party_type,
                        'Sales Invoice' AS document_type,
                        ? AS document_no,
                        -net_amount AS amount,
                        reference_invoice_no AS ref_doc_no
                    FROM invoices
                    WHERE id = ?";

                $stmt_party_ledger = $connection->prepare($insert_party_ledger_query);
                $stmt_party_ledger->bind_param("si", $invoice_no, $invoice_id);

                if ($stmt_party_ledger->execute()) {
                    echo "<script>alert('Record Updated Successfully and Party Ledger Entry Created'); window.location.href='invoice_display.php';</script>";
                } else {
                    echo "<script>alert('Record Updated Successfully but Failed to Create Party Ledger Entry'); window.location.href='invoice_display.php';</script>";
                }

                $stmt_party_ledger->close();
            } else {
                $update_message = "Error updating records.";
            }
        } else {
            $update_message = "Error preparing statement: " . $connection->error;
        }
    }

    // Fetch invoice details
    $invoice_query = "SELECT * FROM invoices WHERE id = ?";
    $stmt_invoice = $connection->prepare($invoice_query);
    $stmt_invoice->bind_param("i", $invoice_id);
    $stmt_invoice->execute();
    $invoice_result = $stmt_invoice->get_result();
    $invoice = $invoice_result->fetch_assoc();

    if ($invoice) {
        // Fetch invoice items
        $items_query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
        $stmt_items = $connection->prepare($items_query);
        $stmt_items->bind_param("i", $invoice_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt_items->close();
    } else {
        $update_message = "No invoice found for the given ID.";
    }

    $stmt_invoice->close();
} else {
    $update_message = "No ID provided.";
}

?>


<!-- HTML form and other UI elements go here -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
      body {
          font-family: 'Arial', sans-serif;
          margin: 0;
          padding: 0;
          background-color: #f9f9f9;
          color: #333;
      }

      .invoice-container {
          width: 95%;
          margin: 20px auto;
          background: #fff;
          padding: 25px;
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          position: relative;
      }

      .header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 20px;
      }

      .logo {
          max-width: 150px;
          height: auto;
      }

      .header h1 {
          margin: 0;
          font-size: 28px;
          color: #333;
      }

      .invoice-info {
          display: flex;
          justify-content: space-between;
          margin-top: 20px;
          font-size: 16px;
      }

      .details {
          display: flex;
          flex-wrap: wrap;
          gap: 20px;
          margin: 20px 0;
      }

      .details div {
          flex: 1;
          min-width: 48%;
      }

      .scrollable-table-container {
          width: 100%;
          overflow-x: auto;
          margin-top: 20px;
      }

      table {
          width: 100%;
          border-collapse: collapse;
          min-width: 800px;
      }

      table th, table td {
          border: 1px solid #ddd;
          padding: 12px;
          text-align: left;
      }

      table th {
          background-color: #f2f2f2;
          font-weight: bold;
      }

      table input[type="text"], table input[type="date"] {
          width: 100%;
          box-sizing: border-box;
          padding: 6px;
          font-size: 14px;
          border: 1px solid #ccc;
          border-radius: 4px;
      }

      .amount {
          text-align: right;
          margin-top: 20px;
          font-size: 16px;
      }

      .footer {
          text-align: center;
          margin-top: 20px;
          font-size: 14px;
          color: #777;
      }

      .terms-conditions {
          margin-top: 20px;
      }

      .update-message {
          background-color: #dff0d8;
          border: 1px solid #d6e9c6;
          color: #3c763d;
          padding: 15px;
          margin-bottom: 20px;
          border-radius: 4px;
      }

      .close-button {
          position: absolute;
          top: 1px;
          right: 2px;
          background: none;
          border: none;
          font-size: 21px;
          cursor: pointer;
          text-decoration: none;
      }

      .loading-spinner {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 9999;
          justify-content: center;
          align-items: center;
      }

      .spinner {
          border: 5px solid #f3f3f3;
          border-top: 5px solid #3498db;
          border-radius: 50%;
          width: 50px;
          height: 50px;
          animation: spin 2s linear infinite;
      }

      @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
      }

      button {
          background-color: #2c3e50;
          color: white;
          padding: 10px 20px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          font-size: 16px;
      }

      button:hover {
          background-color: #2c3e50;
      }
  </style>

</head>
<body>
    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner"></div>
    </div>

    <form id="invoiceForm" method="post">
        <div class="invoice-container">
            <a href="invoice_draft.php" class="close-button" onclick="closeForm()">✖</a>

            <?php if (!empty($update_message)): ?>
                <div class="update-message"><?php echo $update_message; ?></div>
            <?php endif; ?>

            <div class="header">
                <?php
                include('connection.php');
                $query = "SELECT company_logo FROM company_card WHERE id = 1";
                $result = mysqli_query($connection, $query);
                $company = mysqli_fetch_assoc($result);
                $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
                ?>
                <img src="<?php echo $company_logo; ?>" alt="Logo" class="logo" />
                <h1>Invoice</h1>
            </div>

            <div class="invoice-info">
                <p><strong>Invoice No:</strong> <?php echo isset($invoice['invoice_no']) ? htmlspecialchars($invoice['invoice_no']) : ''; ?></p>
                <p><strong>Date:</strong> <?php echo isset($invoice['invoice_date']) ? htmlspecialchars($invoice['invoice_date']) : ''; ?></p>
            </div>

            <div class="details">
                <?php if ($invoice) { ?>
                    <div>
                        <h4>Bill To:</h4>
                        <p>Name: <?php echo htmlspecialchars($invoice['client_company_name']); ?></p>
                        <p>Contact Person: <?php echo htmlspecialchars($invoice['client_name']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($invoice['client_phone']); ?></p>
                        <p>Address: <?php echo htmlspecialchars($invoice['client_address']); ?></p>
                        <p>City: <?php echo htmlspecialchars($invoice['client_city']); ?></p>
                        <p>State: <?php echo htmlspecialchars($invoice['client_state']); ?></p>
                        <p>Country: <?php echo htmlspecialchars($invoice['client_country']); ?></p>
                        <p>Pincode: <?php echo htmlspecialchars($invoice['client_pincode']); ?></p>
                        <p>GSTIN: <?php echo htmlspecialchars($invoice['client_gstno']); ?></p>
                    </div>
                    <div>
                        <h4>Ship To:</h4>
                        <p>Name: <?php echo htmlspecialchars($invoice['shipper_company_name']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($invoice['shipper_phone']); ?></p>
                        <p>Address: <?php echo htmlspecialchars($invoice['shipper_address']); ?></p>
                        <p>City: <?php echo htmlspecialchars($invoice['shipper_city']); ?></p>
                        <p>State: <?php echo htmlspecialchars($invoice['shipper_state']); ?></p>
                        <p>Country: <?php echo htmlspecialchars($invoice['shipper_country']); ?></p>
                        <p>Pincode: <?php echo htmlspecialchars($invoice['shipper_pincode']); ?></p>
                        <p>GSTIN: <?php echo htmlspecialchars($invoice['shipper_gstno']); ?></p>
                    </div>
                <?php } else { ?>
                    <p>No details found for the given invoice ID.</p>
                <?php } ?>
            </div>

            <div class="scrollable-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>GST (%)</th>
                            <th>IGST</th>
                            <th>CGST</th>
                            <th>SGST</th>
                            <th>Amount</th>
                            <th>AMC Code</th>
                            <th>AMC Paid Date</th>
                            <th>AMC Due Date</th>
                            <th>AMC Amount</th>
                            <th>Lot Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)) { ?>
                            <?php foreach ($items as $index => $item) { ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($item['rate']); ?></td>
                                    <td><?php echo htmlspecialchars($item['gst']); ?></td>
                                    <td><?php echo htmlspecialchars($item['igst']); ?></td>
                                    <td><?php echo htmlspecialchars($item['cgst']); ?></td>
                                    <td><?php echo htmlspecialchars($item['sgst']); ?></td>
                                    <td><?php echo htmlspecialchars($item['amount']); ?></td>
                                    <td>
                                        <select name="amc_code[]" onchange="updateDueDate(this)">
                                            <option value="">Select Code</option>
                                            <?php
                                            $query = "SELECT id, code, value FROM amc";
                                            $result = $connection->query($query);
                                            while ($row = $result->fetch_assoc()) {
                                                $selected = ($row['value'] == $item['amc_code']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($row['value']) . '" ' . $selected . '>' . htmlspecialchars($row['code']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <input type="text" name="amc_value[]" value="<?php echo htmlspecialchars($item['amc_code']); ?>" readonly>
                                    </td>
                                    <td><input type="date" name="amc_paid_date[]" value="<?php echo htmlspecialchars($item['amc_paid_date']); ?>" readonly></td>
                                    <td>
                                        <input type="date" name="amc_due_date[]" value="<?php echo htmlspecialchars($item['amc_due_date']); ?>">
                                    </td>
                                    <td><input type="text" name="amc_amount[]" value="<?php echo htmlspecialchars($item['amc_amount']); ?>"></td>
                                    <td>
                                        <a href="invoice_draft_sale_lot_add.php?invoice_id=<?php echo $invoice_id; ?>&item_id=<?php echo $item['id']; ?>&product_id=<?php echo $item['product_id']; ?>"
                                           class="btn btn-primary"
                                           title="View/Add Lot Details">
                                            Lot Details
                                        </a>
                                    </td>
                                    <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="15">No items found for this invoice.</td>
                            </tr>
                        <?php } ?>
                    </tbody>

                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <div class="amount" style="width: 48%;">
                    <p><strong>Base Amount:</strong> <?php echo isset($invoice['base_amount']) ? htmlspecialchars($invoice['base_amount']) : ''; ?></p>
                    <p><strong>Total CGST:</strong> <?php echo isset($invoice['total_cgst']) ? htmlspecialchars($invoice['total_cgst']) : ''; ?></p>
                    <p><strong>Total SGST:</strong> <?php echo isset($invoice['total_sgst']) ? htmlspecialchars($invoice['total_sgst']) : ''; ?></p>
                    <p><strong>Total IGST:</strong> <?php echo isset($invoice['total_igst']) ? htmlspecialchars($invoice['total_igst']) : ''; ?></p>
                    <p><strong>Gross Amount:</strong> <?php echo isset($invoice['gross_amount']) ? htmlspecialchars($invoice['gross_amount']) : ''; ?></p>
                    <p><strong>Discount:</strong> <?php echo isset($invoice['discount']) ? htmlspecialchars($invoice['discount']) : ''; ?></p>
                    <p><strong>Net Amount:</strong> <?php echo isset($invoice['net_amount']) ? htmlspecialchars($invoice['net_amount']) : ''; ?></p>
                </div>
            </div>

            <div class="footer">
                <p>Thank you for your business!</p>
            </div>

            <div class="no-print">
                <button type="submit">Save Changes</button>
            </div>
        </div>
    </form>
</body>
</html>

      <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
      <script>
          var quill = new Quill('#editor', {
              theme: 'snow'
          });
          function printInvoice() {
              window.print();
          }
      </script>
      <script>
  function updateValue(selectElement) {
      // Get selected value and set it to the corresponding input field
      var selectedValue = selectElement.value;
      var inputField = selectElement.nextElementSibling;
      inputField.value = selectedValue;
  }

  function updateDueDate(selectElement) {
      var selectedValue = parseInt(selectElement.value); // Get the selected value (days)
      var inputField = selectElement.nextElementSibling; // Reference to the AMC value field
      inputField.value = selectedValue; // Set the AMC value field
      // Calculate the new due date
      if (!isNaN(selectedValue)) {
          var today = new Date();
          today.setDate(today.getDate() + selectedValue); // Add selected days
          var formattedDate = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
          // Update the AMC due date field in the same row
          var row = selectElement.closest("tr");
          var dueDateField = row.querySelector("input[name='amc_due_date[]']");
          if (dueDateField) {
              dueDateField.value = formattedDate;
          }
      }
  }

  // Function to validate lot details before form submission
  function validateLotDetails(invoiceId) {
      return new Promise((resolve, reject) => {
          // Show loading spinner
          document.getElementById('loadingSpinner').style.display = 'flex';

          // Make an AJAX request to validate_sale_lot_details.php
          fetch('validate_sale_lot_details.php?invoice_id=' + invoiceId)
              .then(response => {
                  if (!response.ok) {
                      throw new Error('Network response was not ok');
                  }
                  return response.json();
              })
              .then(data => {
                  // Hide loading spinner
                  document.getElementById('loadingSpinner').style.display = 'none';

                  if (data.valid) {
                      // If validation passes, resolve the promise
                      resolve(true);
                  } else {
                      // If validation fails, show error messages
                      let errorMessage = "Lot validation failed:\n";

                      // Add error messages
                      if (data.errors && data.errors.length > 0) {
                          errorMessage += "\nErrors:\n" + data.errors.join("\n");
                      }

                      // Add warning messages
                      if (data.warnings && data.warnings.length > 0) {
                          errorMessage += "\nWarnings:\n" + data.warnings.join("\n");
                      }

                      // Show alert with error messages
                      alert(errorMessage);
                      reject(false);
                  }
              })
              .catch(error => {
                  // Hide loading spinner
                  document.getElementById('loadingSpinner').style.display = 'none';

                  console.error("Error validating lot details:", error);
                  alert("Error validating lot details. Please try again.");
                  reject(error);
              });
      });
  }

  // Set up form submission handler
  document.addEventListener('DOMContentLoaded', function() {
      const invoiceForm = document.getElementById('invoiceForm');

      if (invoiceForm) {
          invoiceForm.addEventListener('submit', async function(event) {
              event.preventDefault();

              // Get the invoice ID from the URL
              const urlParams = new URLSearchParams(window.location.search);
              const invoiceId = urlParams.get('id');

              if (!invoiceId) {
                  alert("Invoice ID not found");
                  return;
              }

              try {
                  // Validate lot details first
                  const isValid = await validateLotDetails(invoiceId);

                  if (isValid) {
                      // If validation passes, submit the form
                      this.submit();
                  }
              } catch (error) {
                  // Validation failed, form submission is prevented
                  console.log("Validation failed, form not submitted");
              }
          });
      }
  });
  </script>
  </body>
  </html>
