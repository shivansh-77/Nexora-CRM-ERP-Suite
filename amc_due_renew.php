<?php
require 'connection.php'; // Include your database connection file

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid request.");
}

$invoice_item_id = $_GET['id']; // Directly using the GET parameter

// Step 1: Fetch invoice_id from invoice_items
$query = "SELECT invoice_id FROM invoice_items WHERE id = $invoice_item_id";
$result = mysqli_query($connection, $query);

if (mysqli_num_rows($result) === 0) {
    die("No matching invoice found.");
}

$invoice_item = mysqli_fetch_assoc($result);
$invoice_id = $invoice_item['invoice_id'];

// Step 2: Fetch invoice details from invoices table
$query = "SELECT * FROM invoices WHERE id = $invoice_id";
$result = mysqli_query($connection, $query);

if (mysqli_num_rows($result) === 0) {
    die("Invoice not found.");
}

$invoice = mysqli_fetch_assoc($result);
$reference_invoice_no = $invoice['invoice_no']; // Get the reference invoice number

// Step 3: Fetch the current financial year code
$query_fy = "SELECT fy_code FROM financial_years WHERE is_current = 1 LIMIT 1";
$result_fy = mysqli_query($connection, $query_fy);

if (mysqli_num_rows($result_fy) === 0) {
    die("No current financial year found.");
}

$financial_year = mysqli_fetch_assoc($result_fy);
$fy_code = $financial_year['fy_code'];

// Step 4: Insert new invoice (excluding invoice_no, keeping status as 'Draft')
$query = "INSERT INTO invoices
    (invoice_date, gross_amount, discount, net_amount, total_igst, total_cgst, total_sgst,
    client_name, client_address, client_phone, client_city, client_state, client_country, client_pincode, client_gstno,
    shipper_company_name, shipper_address, shipper_city, shipper_state, shipper_country, shipper_pincode,
    shipper_phone, shipper_gstno, created_at, updated_at, quotation_no, quotation_id, client_id,
    shipper_location_code, shipper_id, base_amount, status, fy_code,client_company_name)
    VALUES (
    '{$invoice['invoice_date']}', '{$invoice['gross_amount']}', '{$invoice['discount']}', '{$invoice['net_amount']}',
    '{$invoice['total_igst']}', '{$invoice['total_cgst']}', '{$invoice['total_sgst']}',
    '{$invoice['client_name']}', '{$invoice['client_address']}', '{$invoice['client_phone']}',
    '{$invoice['client_city']}', '{$invoice['client_state']}', '{$invoice['client_country']}',
    '{$invoice['client_pincode']}', '{$invoice['client_gstno']}',
    '{$invoice['shipper_company_name']}', '{$invoice['shipper_address']}', '{$invoice['shipper_city']}',
    '{$invoice['shipper_state']}', '{$invoice['shipper_country']}', '{$invoice['shipper_pincode']}',
    '{$invoice['shipper_phone']}', '{$invoice['shipper_gstno']}',
    NOW(), NOW(), '{$invoice['quotation_no']}', '{$invoice['quotation_id']}', '{$invoice['client_id']}',
    '{$invoice['shipper_location_code']}', '{$invoice['shipper_id']}', '{$invoice['base_amount']}', 'Draft', '$fy_code','{$invoice['client_company_name']}')";
mysqli_query($connection, $query);
$new_invoice_id = mysqli_insert_id($connection); // Get the newly created invoice ID

// Step 5: Fetch all invoice items except AMC fields
$query = "SELECT id, product_name, product_id, unit, value, quantity, rate, gst, igst, cgst, sgst, amount, lot_tracking, expiration_tracking, expiration_date, lot_trackingid, amc_code, amc_amount
          FROM invoice_items WHERE invoice_id = $invoice_id";
$result = mysqli_query($connection, $query);

while ($row = mysqli_fetch_assoc($result)) {
    // Fetch amc_code and amc_amount from the existing invoice item
    $amc_code = $row['amc_code']; // This should be a number like 31, 60, 90, etc.
    $amc_amount = $row['amc_amount'];

    // 1. Get payment date from party_ledger (latest entry for this invoice)
    $payment_date = null;
    $query_payment = "SELECT DATE(date) as payment_date FROM party_ledger
                     WHERE document_no = '$reference_invoice_no'
                     ORDER BY date DESC LIMIT 1";
    $result_payment = mysqli_query($connection, $query_payment);

    if (mysqli_num_rows($result_payment) > 0) {
        $payment_row = mysqli_fetch_assoc($result_payment);
        $payment_date = $payment_row['payment_date'];
    }

    // 2. Set amc_paid_date (use payment date if available, otherwise NOW())
    $amc_paid_date = $payment_date ? $payment_date : date('Y-m-d');

    // 3. Get the original AMC due date from the invoice
    $query_original_due_date = "SELECT amc_due_date FROM invoice_items
                               WHERE invoice_id = $invoice_id AND product_id = '{$row['product_id']}'
                               ORDER BY id DESC LIMIT 1";
    $result_original_due = mysqli_query($connection, $query_original_due_date);
    $original_due_date = null;

    if (mysqli_num_rows($result_original_due) > 0) {
        $original_due_row = mysqli_fetch_assoc($result_original_due);
        $original_due_date = $original_due_row['amc_due_date'];
    }

    // 4. Calculate new amc_due_date:
    //    - If we have original due date, add amc_code days to it
    //    - Otherwise, add amc_code days to payment date or current date
    if ($original_due_date) {
        $query_due_date = "SELECT DATE_ADD('$original_due_date', INTERVAL $amc_code DAY) AS amc_due_date";
    } else {
        $query_due_date = "SELECT DATE_ADD('$amc_paid_date', INTERVAL $amc_code DAY) AS amc_due_date";
    }

    $result_due_date = mysqli_query($connection, $query_due_date);
    $due_date_row = mysqli_fetch_assoc($result_due_date);
    $amc_due_date = $due_date_row['amc_due_date'];

    // Check if 'AMC' is already in the product name
    $product_name = $row['product_name'];
    if (strpos($product_name, '-AMC') === false) {
        $product_name .= '-AMC';
    }

    // Prepare the INSERT query with proper date values
    $insertQuery = "INSERT INTO invoice_items
                   (invoice_id, product_name, product_id, unit, value, quantity, rate, gst, igst, cgst, sgst,
                    amount, lot_tracking, expiration_tracking, expiration_date, lot_trackingid,
                    reference_invoice_no, amc_code,  amc_due_date, amc_amount)
                    VALUES (
                    '$new_invoice_id', '$product_name', '{$row['product_id']}', '{$row['unit']}', '{$row['value']}',
                    '{$row['quantity']}', '{$row['rate']}', '{$row['gst']}', '{$row['igst']}', '{$row['cgst']}',
                    '{$row['sgst']}', '{$row['amount']}', '{$row['lot_tracking']}', '{$row['expiration_tracking']}',
                    '{$row['expiration_date']}', '{$row['lot_trackingid']}', '$reference_invoice_no',
                    '$amc_code', '$amc_due_date', '$amc_amount')";
    mysqli_query($connection, $insertQuery);
}

// Step 6: Update pending_amount and reference_invoice_no in invoices table
$query_update_pending = "UPDATE invoices
                         SET pending_amount = pending_amount +
                             (SELECT SUM(amc_amount) FROM invoice_items WHERE invoice_id = $new_invoice_id),
                             reference_invoice_no = (SELECT reference_invoice_no FROM invoice_items WHERE invoice_id = $new_invoice_id LIMIT 1)
                         WHERE id = $new_invoice_id";
mysqli_query($connection, $query_update_pending);

// Step 7: Update the existing amc_paid_date in the invoice_items table
$query_update_amc_paid_date = "UPDATE invoice_items
                                SET amc_paid_date = '$amc_paid_date'
                                WHERE id = $invoice_item_id";
mysqli_query($connection, $query_update_amc_paid_date);

// Redirect back to AMC dues display page or confirmation page
echo "<script>alert('AMC Renewed Successfully !'); window.location.href='amc_due_display.php';</script>";
exit;
?>
