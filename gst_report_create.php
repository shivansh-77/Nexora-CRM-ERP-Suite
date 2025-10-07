<?php
include('connection.php');
session_start();

$errors = [];

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect
    $gstin = trim($_POST['gstin_uin_of_recipient'] ?? '');
    $recName = trim($_POST['receiver_name'] ?? '');
    $invNo = trim($_POST['invoice_number'] ?? '');
    $invDate = trim($_POST['invoice_date'] ?? '');
    $invVal = trim($_POST['invoice_value'] ?? '');
    $pos = trim($_POST['place_of_supply'] ?? '');
    $revChg = trim($_POST['reverse_charge'] ?? 'N');
    $appRate = trim($_POST['applicable_tax_rate'] ?? '');
    $invType = trim($_POST['invoice_type'] ?? '');
    $ecomGST = trim($_POST['e_commerce_gstin'] ?? '');
    $rate = trim($_POST['rate'] ?? '');
    $taxable = trim($_POST['taxable_value'] ?? '');
    $cess = trim($_POST['cess_amount'] ?? '');

    // Basic validations (keep it similar to contact.php light checks)
    if ($gstin === '') $errors[] = "GSTIN/UIN is required.";
    if ($invNo === '') $errors[] = "Invoice Number is required.";
    if ($invDate === '') $errors[] = "Invoice Date is required.";
    if ($invVal === '' || !is_numeric($invVal)) $errors[] = "Invoice Value must be numeric.";
    if ($taxable === '' || !is_numeric($taxable)) $errors[] = "Taxable Value must be numeric.";
    if ($rate === '' || !is_numeric($rate)) $errors[] = "Rate must be numeric.";
    if ($appRate === '' || !is_numeric($appRate)) $errors[] = "Applicable % of Tax Rate must be numeric.";
    if ($cess !== '' && !is_numeric($cess)) $errors[] = "Cess Amount must be numeric.";

    // Normalize date to Y-m-d
    if ($invDate) {
        $ts = strtotime($invDate);
        if ($ts === false) $errors[] = "Invalid Invoice Date.";
        else $invDate = date('Y-m-d', $ts);
    }

    if (!$errors) {
        $sql = "INSERT INTO gst_report (gstin_uin_of_recipient, receiver_name, invoice_number, invoice_date, invoice_value, place_of_supply, reverse_charge, applicable_tax_rate, invoice_type, e_commerce_gstin, rate, taxable_value, cess_amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            $errors[] = "Preparation failed: " . $connection->error;
        } else {
            // cast numerics
            $invValF = (float)$invVal;
            $appRateF = (float)$appRate;
            $rateF = (float)$rate;
            $taxableF = (float)$taxable;
            $cessF = ($cess === '') ? 0.0 : (float)$cess;

            // types: s s s s d s s d s s d d d => "ssssdssdssddd"
            $stmt->bind_param(
                "ssssdssdssddd",
                $gstin, $recName, $invNo, $invDate, $invValF, $pos, $revChg, $appRateF, $invType, $ecomGST, $rateF, $taxableF, $cessF
            );

            if ($stmt->execute()) {
                echo "<script>alert('GST Report entry added successfully!'); window.location.href='gst_report_display.php';</script>";
                exit;
            } else {
                $errors[] = "Insert failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// helper to repopulate fields
function old($k, $d='') {
    return htmlspecialchars($_POST[$k] ?? $d);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create GST Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #2c3e50;
        }

        .form-container {
            width: 80%;
            max-width: 1200px;
            background: #fff;
            border-radius: 10px;
            padding: 20px 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
            position: relative;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #2c3e50;
        }

        .form-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .form-group > div {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .form-group label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .form-group input, .form-group select {
            flex: 1;
            padding: 12px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            outline: none;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #007bff;
        }

        .form-group.full {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .form-group.full input {
            width: 100%;
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }

        .form-actions button, .cancel-btn {
            padding: 10px 20px;
            font-size: 16px;
            background: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            color: #2c3e50;
            cursor: pointer;
            transition: color .3s;
            text-decoration: none;
        }

        .close-btn:hover {
            color: #e74c3c;
        }

        .error-box {
            background:#ffe8e8;
            border:1px solid #ffb4b4;
            color:#a30000;
            padding:10px 12px;
            border-radius:6px;
            margin-bottom:12px;
        }

        .muted {
            font-size: 12px;
            color: #777;
        }

        /* Autocomplete Dropdown Styles */
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            border-top: none;
            border-radius: 0 0 5px 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .autocomplete-item:hover {
            background-color: #f5f5f5;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item .invoice-no {
            font-weight: bold;
            color: #2c3e50;
        }

        .autocomplete-item .company-name {
            color: #666;
            font-size: 13px;
        }

        .autocomplete-item .invoice-date {
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <a href="gst_report_display.php" class="close-btn">&times;</a>
        <h2>Add GST Report</h2>

        <?php if ($errors): ?>
        <div class="error-box">
            <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Row 1 -->
            <div class="form-group">
                <div>
                    <label for="invoice_number">Invoice Number *</label>
                    <input type="text" id="invoice_number" name="invoice_number" placeholder="Enter Invoice Number" value="<?= old('invoice_number') ?>" autocomplete="off">
                    <div id="invoice_number_dropdown" class="autocomplete-dropdown"></div>
                </div>
                <div>
                    <label for="receiver_name">Receiver Name</label>
                    <input type="text" id="receiver_name" name="receiver_name" placeholder="Enter Receiver Name" value="<?= old('receiver_name') ?>" autocomplete="off">
                    <div id="receiver_name_dropdown" class="autocomplete-dropdown"></div>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="form-group">
                <div>
                    <label for="gstin">GSTIN/UIN of Recipient *</label>
                    <input type="text" id="gstin" name="gstin_uin_of_recipient" placeholder="e.g., 09AAAGW0168C1DM" value="<?= old('gstin_uin_of_recipient') ?>">
                </div>
                <div>
                    <label for="invoice_date">Invoice Date *</label>
                    <input type="date" id="invoice_date" name="invoice_date" value="<?= old('invoice_date') ?>">
                </div>
                <div>
                    <label for="invoice_value">Invoice Value *</label>
                    <input type="number" step="0.01" id="invoice_value" name="invoice_value" placeholder="0.00" value="<?= old('invoice_value') ?>">
                </div>
            </div>

            <!-- Row 3 -->
            <div class="form-group">
                <div>
                    <label for="place_of_supply">Place Of Supply</label>
                    <input type="text" id="place_of_supply" name="place_of_supply" placeholder="e.g., 09-Uttar Pradesh" value="<?= old('place_of_supply') ?>">
                    <span class="muted">Use "code-state", like 09-Uttar Pradesh.</span>
                </div>
                <div>
                    <label for="reverse_charge">Reverse Charge</label>
                    <select id="reverse_charge" name="reverse_charge">
                        <option value="N" <?= old('reverse_charge','N')==='N'?'selected':''; ?>>N</option>
                        <option value="Y" <?= old('reverse_charge')==='Y'?'selected':''; ?>>Y</option>
                    </select>
                </div>
                <div>
                    <label for="applicable_tax_rate">Applicable % of Tax Rate *</label>
                    <input type="number" step="0.01" id="applicable_tax_rate" name="applicable_tax_rate" placeholder="18" value="<?= old('applicable_tax_rate','18') ?>">
                </div>
            </div>

            <!-- Row 4 -->
            <div class="form-group">
                <div>
                    <label for="invoice_type">Invoice Type</label>
                    <select id="invoice_type" name="invoice_type">
                        <option value="">Select Type</option>
                        <option <?= old('invoice_type')==='Regular B2B'?'selected':''; ?>>Regular B2B</option>
                        <option <?= old('invoice_type')==='SEZ'?'selected':''; ?>>SEZ</option>
                        <option <?= old('invoice_type')==='Export'?'selected':''; ?>>Export</option>
                        <option <?= old('invoice_type')==='Deemed Export'?'selected':''; ?>>Deemed Export</option>
                    </select>
                </div>
                <div>
                    <label for="ecom">E-Commerce GSTIN</label>
                    <input type="text" id="ecom" name="e_commerce_gstin" placeholder="Enter E-Commerce GSTIN" value="<?= old('e_commerce_gstin') ?>">
                </div>
                <div>
                    <label for="rate">Rate (%) *</label>
                    <select id="rate" name="rate">
                        <?php
                        $rates=[0,5,12,18,28];
                        $sel=old('rate','18');
                        foreach($rates as $r){
                            $s=((string)$r===(string)$sel)?'selected':'';
                            echo "<option value=\"$r\" $s>$r</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Row 5 -->
            <div class="form-group">
                <div>
                    <label for="taxable_value">Taxable Value *</label>
                    <input type="number" step="0.01" id="taxable_value" name="taxable_value" placeholder="0.00" value="<?= old('taxable_value') ?>">
                </div>
                <div>
                    <label for="cess_amount">Cess Amount</label>
                    <input type="number" step="0.01" id="cess_amount" name="cess_amount" placeholder="0.00" value="<?= old('cess_amount','0') ?>">
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="button">Submit</button>
                <a href="gst_report_display.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Default invoice date to today
        (function(){
            var d = document.getElementById('invoice_date');
            if (d && !d.value) d.value = new Date().toISOString().split('T')[0];
        })();

        // If user changes Rate and Applicable % is empty/0, copy rate into it
        document.getElementById('rate')?.addEventListener('change', function(){
            var app = document.getElementById('applicable_tax_rate');
            if (app && (!app.value || Number(app.value) === 0)) app.value = this.value;
        });

        // Autocomplete functionality
        let searchTimeout;
        let currentFocus = -1;

        // Function to populate form fields with selected invoice data
        function populateFormFields(invoiceData) {
            document.getElementById('invoice_number').value = invoiceData.invoice_no;
            document.getElementById('receiver_name').value = invoiceData.client_company_name;
            document.getElementById('gstin').value = invoiceData.client_gstno || '';
            document.getElementById('invoice_date').value = invoiceData.invoice_date;
            document.getElementById('invoice_value').value = invoiceData.net_amount;
            document.getElementById('place_of_supply').value = invoiceData.client_state || '';
        }

        // Function to create autocomplete dropdown
        function createAutocompleteDropdown(inputField, dropdownId, searchType) {
            const dropdown = document.getElementById(dropdownId);

            inputField.addEventListener('input', function() {
                const query = this.value.trim();

                clearTimeout(searchTimeout);

                if (query.length < 1) {
                    dropdown.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`fetch_invoices.php?query=${encodeURIComponent(query)}&type=${searchType}`)
                        .then(response => response.json())
                        .then(data => {
                            dropdown.innerHTML = '';
                            currentFocus = -1;

                            if (data.length === 0) {
                                dropdown.style.display = 'none';
                                return;
                            }

                            data.forEach((invoice, index) => {
                                const item = document.createElement('div');
                                item.className = 'autocomplete-item';
                                item.innerHTML = `
                                    <div class="invoice-no">${invoice.invoice_no}</div>
                                    <div class="company-name">${invoice.client_company_name}</div>
                                    <div class="invoice-date">Date: ${invoice.invoice_date} | Amount: ₹${invoice.net_amount}</div>
                                `;

                                item.addEventListener('click', function() {
                                    populateFormFields(invoice);
                                    dropdown.style.display = 'none';
                                });

                                dropdown.appendChild(item);
                            });

                            dropdown.style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Error fetching invoices:', error);
                            dropdown.style.display = 'none';
                        });
                }, 300);
            });

            // Handle keyboard navigation
            inputField.addEventListener('keydown', function(e) {
                const items = dropdown.querySelectorAll('.autocomplete-item');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentFocus++;
                    if (currentFocus >= items.length) currentFocus = 0;
                    setActive(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentFocus--;
                    if (currentFocus < 0) currentFocus = items.length - 1;
                    setActive(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentFocus > -1 && items[currentFocus]) {
                        items[currentFocus].click();
                    }
                } else if (e.key === 'Escape') {
                    dropdown.style.display = 'none';
                    currentFocus = -1;
                }
            });

            function setActive(items) {
                items.forEach((item, index) => {
                    if (index === currentFocus) {
                        item.style.backgroundColor = '#007bff';
                        item.style.color = 'white';
                    } else {
                        item.style.backgroundColor = '';
                        item.style.color = '';
                    }
                });
            }
        }

        // Initialize autocomplete for both fields
        createAutocompleteDropdown(
            document.getElementById('invoice_number'),
            'invoice_number_dropdown',
            'invoice_no'
        );

        createAutocompleteDropdown(
            document.getElementById('receiver_name'),
            'receiver_name_dropdown',
            'company'
        );

        // Hide dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.form-group')) {
                document.getElementById('invoice_number_dropdown').style.display = 'none';
                document.getElementById('receiver_name_dropdown').style.display = 'none';
            }
        });
    </script>
</body>
</html>
