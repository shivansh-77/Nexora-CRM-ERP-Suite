<?php
session_start();
include('connection.php');
include('topbar.php');

// Handle form submission
if ($_POST && isset($_POST['submit_payment'])) {
    include('connection.php');

    $ledger_type = mysqli_real_escape_string($connection, $_POST['ledger_type']);
    $party_no = intval($_POST['party_no']);
    $party_name = mysqli_real_escape_string($connection, $_POST['party_name']);
    $party_type = ($ledger_type == 'Customer Ledger') ? 'Customer' : 'Vendor';
    $document_type = mysqli_real_escape_string($connection, $_POST['document_type']);
    $document_no = !empty($_POST['document_no']) ? mysqli_real_escape_string($connection, $_POST['document_no']) : 'NULL';
    $is_advance = isset($_POST['is_advance']) && $_POST['is_advance'] === 'on';

    $amount = floatval($_POST['amount']);
    $amount = ($ledger_type == 'Customer Ledger') ? -abs($amount) : abs($amount);
    $ref_doc_no = !empty($_POST['ref_doc_no']) ? "'" . mysqli_real_escape_string($connection, $_POST['ref_doc_no']) . "'" : 'NULL';
    $payment_method = mysqli_real_escape_string($connection, $_POST['payment_method']);
    $payment_details = mysqli_real_escape_string($connection, $_POST['payment_details']);
    $payment_date = mysqli_real_escape_string($connection, $_POST['payment_date']);
    $date = date('Y-m-d H:i:s');

    mysqli_begin_transaction($connection);

    try {
        // Generate advance doc number if this is an advance payment
        $advance_doc_no = null;
        if ($is_advance) {
            // Determine prefix based on ledger type and document type
            if ($ledger_type == 'Customer Ledger') {
                $prefix = "PVR";
                $advance_document_type = 'Advance Payment'; // For customer
            } elseif ($ledger_type == 'Vendor Ledger' && $document_type == 'Payment Paid') {
                $prefix = "PVP";
                $advance_document_type = 'Advance Paid'; // For vendor
            } else {
                $prefix = "PVR"; // Default fallback
                $advance_document_type = 'Advance Payment';
            }

            $ym = date('y') . date('m');

            // Get all advance doc numbers for current month/year with the determined prefix
            $query = "SELECT advance_doc_no FROM advance_payments WHERE advance_doc_no LIKE '$prefix/$ym/%'";
            $result = mysqli_query($connection, $query);

            $highest_number = 0;
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $parts = explode('/', $row['advance_doc_no']);
                    $current_number = (int)end($parts); // Get last part as number
                    if ($current_number > $highest_number) {
                        $highest_number = $current_number;
                    }
                }
            }

            $new_number = str_pad($highest_number + 1, 4, '0', STR_PAD_LEFT);
            $advance_doc_no = "$prefix/$ym/$new_number";
            $document_no = $advance_doc_no; // Use for party_ledger
        }

        // Insert into party_ledger
  $insert_query = "INSERT INTO party_ledger (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, ref_doc_no, date, payment_method, payment_details, payment_date)
                   VALUES ('$ledger_type', $party_no, '$party_name', '$party_type', '$document_type', " .
                   ($is_advance ? "'$advance_doc_no'" : ($document_no === 'NULL' ? 'NULL' : "'$document_no'")) . ", $amount, $ref_doc_no, '$date', '$payment_method', '$payment_details', '$payment_date')";
  if (!mysqli_query($connection, $insert_query)) {
      throw new Exception("Error inserting into party_ledger: " . mysqli_error($connection));
  }

  // Insert the same record into advance_payments
  $insert_advance_query = "INSERT INTO advance_payments (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, ref_doc_no, date, payment_method, payment_details, payment_date)
                           VALUES ('$ledger_type', $party_no, '$party_name', '$party_type', '$document_type', " .
                           ($is_advance ? "'$advance_doc_no'" : ($document_no === 'NULL' ? 'NULL' : "'$document_no'")) . ", $amount, $ref_doc_no, '$date', '$payment_method', '$payment_details', '$payment_date')";
  if (!mysqli_query($connection, $insert_advance_query)) {
      throw new Exception("Error inserting into advance_payments: " . mysqli_error($connection));
  }


        // 2. If document is selected (i.e. not advance), update pending amount
        if (!$is_advance && $document_no !== 'NULL') {
            $abs_amount = abs($amount);
            if ($ledger_type == 'Customer Ledger') {
                $update_query = "UPDATE invoices SET pending_amount = pending_amount - $abs_amount WHERE invoice_no = '$document_no' AND client_id = $party_no";
            } else {
                $update_query = "UPDATE purchase_invoice SET pending_amount = pending_amount - $abs_amount WHERE invoice_no = '$document_no' AND vendor_id = $party_no";
            }
            if (!mysqli_query($connection, $update_query)) {
                throw new Exception("Error updating invoice: " . mysqli_error($connection));
            }
        }

        // 3. If advance payment, insert into advance_payment table
        if ($is_advance) {
            // Store both amount and pending_amount as positive values
            $negative_amount = -($amount);
            $pending_amount = abs($amount);

            $advance_query = "INSERT INTO advance_payments (
                ledger_type, party_no, party_name, party_type, document_type, document_no,
                amount, pending_amount, advance_doc_no, date, payment_method, payment_details, payment_date
            ) VALUES (
                '$ledger_type', $party_no, '$party_name', '$party_type', '$advance_document_type', NULL,
                $negative_amount, $pending_amount, '$advance_doc_no', '$date', '$payment_method', '$payment_details', '$payment_date'
            )";
            if (!mysqli_query($connection, $advance_query)) {
                throw new Exception("Error inserting into advance_payments: " . mysqli_error($connection));
            }
        }

        mysqli_commit($connection);
        echo "<script>alert('Payment record added successfully!'); window.location.href='payment_advance.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($connection);
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>Payment Advance</title>
    <style>
        html, body {
            overflow: hidden;
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .form-container {
            width: calc(100% - 260px);
            margin-left: 260px;
            margin-top: 140px;
            max-height: calc(100vh - 150px);
            overflow-y: auto;
            padding: 20px;
            background-color: white;
            border: 1px solid #ddd;
        }
        .leadforhead {
            position: fixed;
            width: calc(100% - 290px);
            height: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2c3e50;
            color: white;
            padding: 0 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            margin-left: 260px;
            margin-top: 80px;
        }
        .payment-form {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: end;
        }
        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0; /* Ensures equal flex distribution */
        }
        .form-group.full-width {
            flex: 100%;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
            box-sizing: border-box; /* Ensures consistent sizing */
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        .autocomplete-container {
            position: relative;
            width: 100%;
        }
        .autocomplete-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
            box-sizing: border-box;
        }
        .autocomplete-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none; /* Hidden by default, shown when needed */
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .autocomplete-dropdown.show {
            display: block;
        }
        .autocomplete-option {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            transition: background-color 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .autocomplete-option:hover {
            background-color: #f8f9fa;
        }
        .autocomplete-option:last-child {
            border-bottom: none;
        }
        .autocomplete-option.highlighted {
            background-color: #e3f2fd;
        }
        .invoice-number {
            font-weight: bold;
            color: #2c3e50;
        }
        .pending-amount {
            color: #e74c3c;
            font-weight: bold;
            font-size: 12px;
        }
        .no-results {
            padding: 12px;
            color: #666;
            font-style: italic;
            text-align: center;
        }
        .dropdown-placeholder {
            padding: 12px;
            color: #999;
            font-style: italic;
            text-align: center;
            background-color: #f8f9fa;
        }
        .amount-info {
            color: #3498db;
            font-size: 12px;
            margin-top: 5px;
            font-weight: bold;
        }
        .amount-warning {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            font-weight: bold;
        }
        .amount-success {
            color: #27ae60;
            font-size: 12px;
            margin-top: 5px;
            font-weight: bold;
        }
        .btn-submit {
            background-color: #2c3e50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background-color: #219a52;
        }
        .btn-reset {
            background-color: #e74c3c;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            margin-left: 10px;
        }
        .btn-reset:hover {
            background-color: #c0392b;
        }
        .required {
            color: red;
        }
        .btn-primary {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        /* New styles for document number and advance checkbox */
        .form-group.document-no-input-group {
            flex: 0.6; /* Make it smaller */
        }
        .form-group.advance-checkbox-group {
            flex: 0.2; /* Allocate space for checkbox */
            display: flex;
            align-items: flex-end; /* Align checkbox at the bottom of its group */
            padding-bottom: 5px; /* Fine-tune vertical alignment */
        }
        .advance-checkbox-group input[type="checkbox"] {
            width: 16px; /* Standard checkbox size */
            height: 16px;
            margin-right: 5px;
            vertical-align: middle;
        }
        .advance-checkbox-group label {
            margin-bottom: 0; /* Remove default label margin */
            font-weight: normal; /* Default label font weight */
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
        .form-group.ref-doc-no-input-group {
            flex: 0.84; /* Match with .document-no-input-group for consistent width */
        }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Payment</h2>
        <div class="lead-actions">
            <button onclick="window.location.href='party_ledger.php'" class="btn-primary">📋 View Ledger</button>
        </div>
    </div>
    <div class="form-container">
        <form class="payment-form" method="POST" action="">
            <h3 style="text-align: center; color: #2c3e50; margin-bottom: 30px;">Add Advance Payment</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="ledger_type">Ledger Type <span class="required">*</span></label>
                    <select id="ledger_type" name="ledger_type" required onchange="loadParties()">
                        <option value="">Select Ledger Type</option>
                        <option value="Customer Ledger">Customer Ledger</option>
                        <option value="Vendor Ledger">Vendor Ledger</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="party_select">Customer/Vendor <span class="required">*</span></label>
                    <div class="autocomplete-container">
                        <input type="text" id="party_select" name="party_name" placeholder="Type to search customer/vendor..." required autocomplete="off" class="autocomplete-input">
                        <input type="hidden" id="party_no" name="party_no">
                        <div id="party_dropdown" class="autocomplete-dropdown">
                            <div class="dropdown-placeholder">Select ledger type first</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="document_type">Document Type <span class="required">*</span></label>
                    <select id="document_type" name="document_type" required>
                        <option value="">Select Ledger Type first</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">Amount <span class="required">*</span></label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" placeholder="Enter amount" required>
                    <div id="amount_message"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group document-no-input-group">
                    <label for="document_no">Document No.</label>
                    <div class="autocomplete-container">
                        <input type="text" id="document_no" name="document_no" placeholder="Type to search invoice or leave empty for advance..." autocomplete="off" class="autocomplete-input">
                        <div id="document_dropdown" class="autocomplete-dropdown">
                            <div class="dropdown-placeholder">Select customer/vendor first</div>
                        </div>
                    </div>
                </div>
                <div class="form-group advance-checkbox-group">
                    <label for="is_advance" class="sr-only">Is Advance Payment</label>
                    <div style="display: flex; align-items: center;">
                        <input type="checkbox" id="is_advance" name="is_advance"> <!-- Removed disabled attribute -->
                        <label for="is_advance">Advance</label>
                    </div>
                </div>
                <div class="form-group ref-doc-no-input-group">
                    <label for="ref_doc_no">Reference Doc. No.</label>
                    <input type="text" id="ref_doc_no" name="ref_doc_no" placeholder="Auto-filled from selected invoice" readonly style="background-color: #f8f9fa;">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="payment_method">Payment Method <span class="required">*</span></label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="UPI">UPI</option>
                        <option value="Cash">Cash</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Draft">Draft</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_date">Payment Date <span class="required">*</span></label>
                    <input type="date" id="payment_date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="payment_details">Payment Details</label>
                    <textarea id="payment_details" name="payment_details" rows="3" placeholder="Additional payment details (optional)"></textarea>
                </div>
            </div>
            <div style="text-align: center;">
                <button type="submit" name="submit_payment" class="btn-submit">💾 Save Payment</button>
                <button type="reset" class="btn-reset" onclick="resetForm()">🔄 Reset Form</button>
            </div>
        </form>
    </div>
    <script>
        let parties = [];
        let invoices = [];
        let currentFocus = -1;
        let selectedInvoicePending = 0;
        let documentNoInput;
        let isAdvanceCheckbox;

        // Function to update the advance checkbox state based on document_no input
        function updateAdvanceCheckboxState() {
            if (!documentNoInput || !isAdvanceCheckbox) {
                documentNoInput = document.getElementById('document_no');
                isAdvanceCheckbox = document.getElementById('is_advance');
            }
            // If document_no is empty, check the advance checkbox
            // Otherwise, uncheck it
            isAdvanceCheckbox.checked = documentNoInput.value.trim() === '';
        }

        function loadParties() {
            const ledgerType = document.getElementById('ledger_type').value;
            const partySelect = document.getElementById('party_select');
            const partyDropdown = document.getElementById('party_dropdown');
            const documentType = document.getElementById('document_type');

            if (!ledgerType) {
                partySelect.value = '';
                document.getElementById('party_no').value = '';
                partyDropdown.innerHTML = '<div class="dropdown-placeholder">Select ledger type first</div>';
                partyDropdown.classList.remove('show');
                // Reset document type
                documentType.innerHTML = '<option value="">Select Ledger Type first</option>';
                clearInvoiceFields();
                return;
            }

            // Auto-select document type based on ledger type
            if (ledgerType === 'Customer Ledger') {
                documentType.innerHTML = '<option value="Payment Received" selected>Payment Received</option>';
                documentType.value = 'Payment Received';
            } else if (ledgerType === 'Vendor Ledger') {
                documentType.innerHTML = '<option value="Payment Paid" selected>Payment Paid</option>';
                documentType.value = 'Payment Paid';
            }

            partySelect.value = '';
            document.getElementById('party_no').value = '';
            partyDropdown.innerHTML = '<div class="dropdown-placeholder">Loading...</div>';
            partyDropdown.classList.add('show');
            clearInvoiceFields(); // This will also call updateAdvanceCheckboxState

            fetch('get_parties.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ledger_type=' + encodeURIComponent(ledgerType)
            })
            .then(response => response.json())
            .then(data => {
                parties = data;
                console.log('Parties loaded:', parties.length);
                showAllParties();
            })
            .catch(error => {
                console.error('Error loading parties:', error);
                partyDropdown.innerHTML = '<div class="dropdown-placeholder">Error loading data</div>';
            });
        }

        function showAllParties() {
            const dropdown = document.getElementById('party_dropdown');
            showAutocompleteOptions(parties, dropdown, document.getElementById('party_select'), 'party_no', 'parties');
        }

        function loadInvoices() {
            const ledgerType = document.getElementById('ledger_type').value;
            const partyNo = document.getElementById('party_no').value;

            if (!ledgerType || !partyNo) {
                invoices = [];
                const docDropdown = document.getElementById('document_dropdown');
                docDropdown.innerHTML = '<div class="dropdown-placeholder">Select customer/vendor first</div>';
                docDropdown.classList.remove('show');
                return;
            }

            console.log('Loading invoices for:', ledgerType, partyNo);
            const docDropdown = document.getElementById('document_dropdown');
            docDropdown.innerHTML = '<div class="dropdown-placeholder">Loading invoices...</div>';

            fetch('get_invoices.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ledger_type=' + encodeURIComponent(ledgerType) + '&party_no=' + encodeURIComponent(partyNo)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Invoices loaded:', data);
                invoices = data;
                showAllInvoices();
            })
            .catch(error => {
                console.error('Error loading invoices:', error);
                invoices = [];
                docDropdown.innerHTML = '<div class="dropdown-placeholder">Error loading invoices</div>';
            });
        }

        function showAllInvoices() {
            const documentDropdown = document.getElementById('document_dropdown');
            if (invoices.length === 0) {
                documentDropdown.innerHTML = '<div class="dropdown-placeholder">No pending invoices found</div>';
                return;
            }
            showAutocompleteOptions(invoices, documentDropdown, document.getElementById('document_no'), null, 'invoices');
        }

        function checkAmountVsPending() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const messageDiv = document.getElementById('amount_message');

            if (selectedInvoicePending > 0) {
                // Always show the pending amount of the selected invoice
                messageDiv.innerHTML = '<div class="amount-info">📋 Invoice Pending Amount: ₹' + selectedInvoicePending.toFixed(2) + '</div>';
                if (amount > 0) {
                    if (amount > selectedInvoicePending) {
                        // Clear amount if it exceeds pending amount
                        document.getElementById('amount').value = '';
                        messageDiv.innerHTML += '<div class="amount-warning">⚠️ Amount cannot exceed pending amount!</div>';
                    } else if (amount === selectedInvoicePending) {
                        messageDiv.innerHTML += '<div class="amount-success">✓ Full payment - Invoice will be cleared</div>';
                    } else {
                        messageDiv.innerHTML += '<div class="amount-success">✓ Partial payment accepted</div>';
                    }
                }
            } else {
                messageDiv.innerHTML = '';
            }
        }

        function setupAutocomplete(inputId, dropdownId, dataSource, hiddenFieldId = null) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);

            input.addEventListener('input', function() {
                const value = this.value.toLowerCase();
                currentFocus = -1;
                if (value.length === 0) {
                    if (dataSource === 'parties' && parties.length > 0) {
                        showAutocompleteOptions(parties, dropdown, input, hiddenFieldId, dataSource);
                        dropdown.classList.add('show');
                    } else if (dataSource === 'invoices' && invoices.length > 0) {
                        showAutocompleteOptions(invoices, dropdown, input, hiddenFieldId, dataSource);
                        dropdown.classList.add('show');
                    }
                    if (hiddenFieldId) {
                        document.getElementById(hiddenFieldId).value = '';
                    }
                    // Reset selected invoice pending amount and reference doc
                    if (dataSource === 'invoices') {
                        selectedInvoicePending = 0;
                        document.getElementById('ref_doc_no').value = '';
                        checkAmountVsPending();
                        updateAdvanceCheckboxState(); // Call here when input becomes empty
                    }
                    return;
                }

                let data = [];
                if (dataSource === 'parties') {
                  data = parties.filter(party =>
  party.name.toLowerCase().includes(value) ||
  (party.company && party.company.toLowerCase().includes(value))
);

                } else if (dataSource === 'invoices') {
                    data = invoices.filter(invoice =>
                        invoice.invoice_no.toLowerCase().includes(value)
                    );
                }
                showAutocompleteOptions(data, dropdown, input, hiddenFieldId, dataSource);
                dropdown.classList.add('show');
                if (dataSource === 'invoices') {
                    updateAdvanceCheckboxState(); // Call here when input has value
                }
            });

            input.addEventListener('focus', function() {
                if (dataSource === 'parties' && parties.length > 0) {
                    showAutocompleteOptions(parties, dropdown, input, hiddenFieldId, dataSource);
                    dropdown.classList.add('show');
                } else if (dataSource === 'invoices' && invoices.length > 0) {
                    showAutocompleteOptions(invoices, dropdown, input, hiddenFieldId, dataSource);
                    dropdown.classList.add('show');
                }
            });

            input.addEventListener('keydown', function(e) {
                const dropdown = document.getElementById(dropdownId);
                const options = dropdown.querySelectorAll('.autocomplete-option');
                if (e.keyCode === 40) { // Down arrow
                    e.preventDefault();
                    currentFocus++;
                    addActive(options);
                } else if (e.keyCode === 38) { // Up arrow
                    e.preventDefault();
                    currentFocus--;
                    addActive(options);
                } else if (e.keyCode === 27) { // Escape
                    dropdown.classList.remove('show');
                    input.blur();
                    currentFocus = -1;
                } else if (e.keyCode === 13) { // Enter
                    e.preventDefault();
                    if (currentFocus > -1 && options[currentFocus]) {
                        options[currentFocus].click();
                    }
                }
            });
        }

        function showAutocompleteOptions(data, dropdown, input, hiddenFieldId, dataSource) {
            dropdown.innerHTML = '';
            if (data.length === 0) {
                const noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.textContent = dataSource === 'invoices' ? 'No pending invoices found' : 'No results found';
                dropdown.appendChild(noResults);
                return;
            }

            data.forEach((item, index) => {
                const option = document.createElement('div');
                option.className = 'autocomplete-option';
                if (dataSource === 'parties') {
                  option.innerHTML = '<strong>' + item.name + '</strong>' +
    (item.company ? ' <span style="color:#888;">(' + item.company + ')</span>' : '');

                } else if (dataSource === 'invoices') {
                    const invoiceSpan = document.createElement('span');
                    invoiceSpan.className = 'invoice-number';
                    invoiceSpan.textContent = item.invoice_no;
                    const amountSpan = document.createElement('span');
                    amountSpan.className = 'pending-amount';
                    amountSpan.textContent = '₹' + item.pending_amount.toFixed(2);
                    option.appendChild(invoiceSpan);
                    option.appendChild(amountSpan);
                }
                option.addEventListener('click', function() {
                    if (dataSource === 'parties') {
                        input.value = item.name;
                        if (hiddenFieldId) {
                            document.getElementById(hiddenFieldId).value = item.id;
                            loadInvoices();
                        }
                    } else if (dataSource === 'invoices') {
                        input.value = item.invoice_no;
                        selectedInvoicePending = item.pending_amount;
                        // Auto-fill reference document number
                        document.getElementById('ref_doc_no').value = item.reference_invoice_no || '';
                        checkAmountVsPending();
                    }
                    dropdown.classList.remove('show');
                    currentFocus = -1;
                    if (dataSource === 'invoices') {
                        updateAdvanceCheckboxState(); // Call after selecting an invoice
                    }
                });
                dropdown.appendChild(option);
            });
        }

        function addActive(options) {
            if (!options) return false;
            removeActive(options);
            if (currentFocus >= options.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = (options.length - 1);
            if (options[currentFocus]) {
                options[currentFocus].classList.add('highlighted');
            }
        }

        function removeActive(options) {
            for (let i = 0; i < options.length; i++) {
                options[i].classList.remove('highlighted');
            }
        }

        function clearInvoiceFields() {
            document.getElementById('document_no').value = '';
            document.getElementById('ref_doc_no').value = '';
            const docDropdown = document.getElementById('document_dropdown');
            docDropdown.innerHTML = '<div class="dropdown-placeholder">Select customer/vendor first</div>';
            docDropdown.classList.remove('show');
            invoices = [];
            selectedInvoicePending = 0;
            document.getElementById('amount_message').innerHTML = '';
            updateAdvanceCheckboxState(); // Call after clearing document_no
        }

        function resetForm() {
            document.querySelector('.payment-form').reset();
            document.getElementById('party_no').value = '';
            const partyDropdown = document.getElementById('party_dropdown');
            const documentType = document.getElementById('document_type');
            partyDropdown.innerHTML = '<div class="dropdown-placeholder">Select ledger type first</div>';
            partyDropdown.classList.remove('show');
            documentType.innerHTML = '<option value="">Select Ledger Type first</option>';
            clearInvoiceFields(); // This will call updateAdvanceCheckboxState
            parties = [];
            invoices = [];
            currentFocus = -1;
            selectedInvoicePending = 0;
            updateAdvanceCheckboxState(); // Ensure state is correct after full reset
        }

        // Hide dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const dropdowns = document.querySelectorAll('.autocomplete-dropdown');
            dropdowns.forEach(dropdown => {
                const container = dropdown.parentElement;
                if (!container.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
            currentFocus = -1;
        });

        // Add amount input listener
        document.getElementById('amount').addEventListener('input', checkAmountVsPending);

        // Initialize autocomplete for all fields
        document.addEventListener('DOMContentLoaded', function() {
            documentNoInput = document.getElementById('document_no'); // Assign global variables
            isAdvanceCheckbox = document.getElementById('is_advance');
            setupAutocomplete('party_select', 'party_dropdown', 'parties', 'party_no');
            setupAutocomplete('document_no', 'document_dropdown', 'invoices');
            updateAdvanceCheckboxState(); // Initial check on load
        });

        // Set today's date as default
        document.getElementById('payment_date').value = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
