<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_GET['id'])) {
    $quotation_id = $_GET['id']; // Directly using input without sanitization

    // Check if an invoice already exists for this quotation
    $check_existing_invoice = "SELECT id FROM invoices WHERE quotation_id = $quotation_id";
    $result_check = $connection->query($check_existing_invoice);

    if ($result_check->num_rows > 0) {
        echo "<script>alert('Invoice already present for this quotation.'); window.history.back();</script>";
        exit();
    }

    // Fetch quotation details
    $quotation_query = "SELECT * FROM quotations WHERE id = $quotation_id";
    $quotation_result = $connection->query($quotation_query);
    $quotation = $quotation_result->fetch_assoc();

    if ($quotation) {
        // Insert into invoices table **without** invoice_no
        $insert_invoice_query = "INSERT INTO invoices (
            invoice_no, quotation_no, quotation_id, gross_amount, discount, net_amount, total_igst, total_cgst, total_sgst,
            client_name, client_address, client_phone, client_city, client_state, client_country,
            client_pincode, client_gstno, shipper_company_name, shipper_address, shipper_city,
            shipper_state, shipper_country, shipper_pincode, shipper_phone, shipper_gstno, client_id,
            shipper_location_code, shipper_id, base_amount, fy_code
        ) VALUES (
            NULL, '{$quotation['quotation_no']}', $quotation_id, {$quotation['gross_amount']}, {$quotation['discount']},
            {$quotation['net_amount']}, {$quotation['total_igst']}, {$quotation['total_cgst']}, {$quotation['total_sgst']},
            '{$quotation['client_name']}', '{$quotation['client_address']}', '{$quotation['client_phone']}', '{$quotation['client_city']}',
            '{$quotation['client_state']}', '{$quotation['client_country']}', '{$quotation['client_pincode']}',
            '{$quotation['client_gstno']}', '{$quotation['shipper_company_name']}', '{$quotation['shipper_address']}',
            '{$quotation['shipper_city']}', '{$quotation['shipper_state']}', '{$quotation['shipper_country']}',
            '{$quotation['shipper_pincode']}', '{$quotation['shipper_phone']}', '{$quotation['shipper_gstno']}',
            '{$quotation['client_id']}', '{$quotation['shipper_location_code']}', '{$quotation['shipper_id']}', '{$quotation['base_amount']}','{$quotation['fy_code']}'
        )";

        if ($connection->query($insert_invoice_query) === TRUE) {
            $invoice_id = $connection->insert_id;

            // Fetch quotation items with lot_tracking and expiration_tracking
            $items_query = "SELECT qi.*, i.lot_tracking, i.expiration_tracking, i.item_type
                            FROM quotation_items qi
                            LEFT JOIN item i ON qi.product_id = i.item_code
                            WHERE qi.quotation_id = $quotation_id";
            $items_result = $connection->query($items_query);

            // Insert items into invoice_items table
            while ($item = $items_result->fetch_assoc()) {
                $item_type = $item['item_type']; // Define $item_type inside the loop

                $insert_item_query = "INSERT INTO invoice_items (
                    invoice_id, product_id, product_name, unit, value, quantity, rate, gst, igst, cgst, sgst, amount, lot_tracking, expiration_tracking
                ) VALUES (
                    $invoice_id, '{$item['product_id']}', '{$item['product_name']}', '{$item['unit']}', '{$item['value']}',
                    {$item['quantity']}, {$item['rate']}, {$item['gst']}, {$item['igst']}, {$item['cgst']},
                    {$item['sgst']}, {$item['amount']}, '{$item['lot_tracking']}', '{$item['expiration_tracking']}'
                )";

                $connection->query($insert_item_query);
                $invoice_itemid = $connection->insert_id;

                if ($item_type === 'Inventory') {
                    // Insert into item_ledger_history
                    $document_type = 'Sale';
                    $entry_type = 'Sales Invoice';
                    $item_quantity = -((float)$item['quantity'] * (float)$item['value']);
                    $location = $quotation['shipper_location_code'];
                    $date = date('Y-m-d');
                    $item_value = $item['value'];

                    $insert_ledger_history = "INSERT INTO item_ledger_history (
                        document_type, entry_type, product_id, product_name, quantity, location, unit, date, value, invoice_itemid
                    ) VALUES (
                        '$document_type', '$entry_type', '{$item['product_id']}', '{$item['product_name']}',
                        $item_quantity, '$location', '{$item['unit']}', '$date', $item_value, $invoice_itemid
                    )";

                    $connection->query($insert_ledger_history);
                }
            }

            // Redirect to the invoice page with success message
            echo "<script>alert('Invoice generated successfully!'); window.location.href='quotation_display.php';</script>";
            exit();
        } else {
            echo "Error creating invoice: " . $connection->error;
        }
    } else {
        echo "Quotation not found.";
    }

    $connection->close();
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="number"] {
            padding: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Register Invoice</h1>
        <form method="GET">
            <label for="quotation_id">Quotation ID:</label>
            <input type="number" id="quotation_id" name="id" required>
            <input type="submit" value="Create Invoice">
        </form>
    </div>
</body>
</html>
