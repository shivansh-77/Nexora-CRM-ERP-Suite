<?php
session_start();
include('connection.php');
include('topbar.php');

// Function to fetch data for all categories
function fetchData($connection) {
    $queries = [
        'contacts' => [
            'total' => "SELECT COUNT(*) AS total FROM contact",
            'yearly' => "SELECT COUNT(*) AS yearly FROM contact WHERE YEAR(followupdate) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM contact WHERE YEAR(followupdate) = YEAR(CURDATE()) AND MONTH(followupdate) = MONTH(CURDATE())"
        ],
        'followups' => [
            'total' => "SELECT COUNT(*) AS total FROM followup WHERE lead_status = 'Open'",
            'yearly' => "SELECT COUNT(*) AS yearly FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE()) AND MONTH(followup_date_nxt) = MONTH(CURDATE())"
        ],
        'sales' => [
            'total' => "SELECT SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT SUM(net_amount) AS yearly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE())",
            'monthly' => "SELECT SUM(net_amount) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())"
        ],
        'today_followups' => "SELECT COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND followup_date_nxt = CURDATE()",
        'amc_dues_today' => "SELECT COUNT(*) AS count FROM invoice_items WHERE amc_due_date = CURDATE()",
        'pending_amount' => "SELECT SUM(amount) AS pending FROM party_ledger", // Updated query
        'invoices' => [
            'total' => "SELECT COUNT(*) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT COUNT(*) AS yearly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())"
        ],
        'quotations' => [
            'total' => "SELECT COUNT(*) AS total FROM quotations",
            'yearly' => "SELECT COUNT(*) AS yearly FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE()) AND MONTH(quotation_date) = MONTH(CURDATE())"
        ],
        'transactions' => [
            'total' => "SELECT COUNT(*) AS total FROM party_ledger",
            'yearly' => "SELECT COUNT(*) AS yearly FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())"
        ]
    ];

    $data = [];
    foreach ($queries as $category => $query) {
        if (is_array($query)) {
            foreach ($query as $type => $sql) {
                $result = mysqli_query($connection, $sql);
                $data[$category][$type] = mysqli_fetch_assoc($result)[$type] ?? 0;
            }
        } else {
            $result = mysqli_query($connection, $query);
            $data[$category] = mysqli_fetch_assoc($result)['count'] ?? mysqli_fetch_assoc($result)['pending'] ?? 0;
        }
    }

    return $data;
}

$data = fetchData($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Splendid Infotech Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>


        .content {
            margin-top: 140px;
        }
        .hidden {
            margin-bottom: 30px;
        }
        .leadforhead {
            position: fixed;
            width: 75%;
            height: 54px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2c3e50;
            color: white;
            padding: 0 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow: visible;
            margin-left: 260px;
            margin-top: 80px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .modal-content {
    background-color: #fefefe;
    margin: 5% auto; /* Adjusted margin */
    padding: 20px;
    border: 1px solid #888;
    width: 90%; /* Increased width */
    max-width: 800px; /* Increased max-width */
}
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2>DASHBOARD</h2>
    </div>

    <!-- Main Content -->
  <div class="content" style="background-color:#dbe4ec;">
    <div id="additionalCards" class="card-container hidden" style="background-color: #ff9999; padding: 10px; border-radius: 5px;">
    <div class="card">
        <h3 style="margin-top: -10px; padding: 10px;">Today's Followups</h3>
        <p style="font-weight: bold; color: red;">
            <span id="todayFollowupsValue">Total:<?php echo $data['today_followups']; ?></span>
        </p>
    </div>
    <div class="card">
        <h3 style="margin-top: -10px;padding: 10px;">AMC Dues Today</h3>
        <p style="font-weight: bold; color: red;">
            <span id="amcDuesTodayValue">Total:<?php echo $data['amc_dues_today']; ?></span>
        </p>
    </div>
    <div class="card">
        <h3 style="margin-top: -10px; padding: 10px;">Pending Amount</h3>
        <p style="font-weight: bold; color: red;">
            Total:$<span id="pendingAmountValue"><?php echo number_format($data['pending_amount'], 2); ?></span>
        </p>
    </div>
</div>

<div class="card-container hidden">
<div class="card" id="contactsCard">
    <h3 style="margin-top: -10px; padding: 10px;">Contacts</h3>
    <p style="font-weight: bold;">
        <span id="contactsLabel">Total:</span>
        <span id="contactsValue" style="color: red;"><?php echo $data['contacts']['total']; ?></span>
    </p>
</div>
<div class="card" id="followupsCard">
    <h3 style="margin-top: -10px; padding: 10px;">Follow-ups</h3>
    <p style="font-weight: bold;">
        <span id="followupsLabel">Total:</span>
        <span id="followupsValue" style="color: red;"><?php echo $data['followups']['total']; ?></span>
    </p>
</div>
<div class="card" id="salesCard">
    <h3 style="margin-top: -10px; padding: 10px;">Sales</h3>
    <p style="font-weight: bold;">
        <span id="salesLabel">Total:</span>
        <span id="salesValue" style="color: red;">₹<?php echo number_format($data['sales']['total'], 2); ?></span>
    </p>
</div>
</div>
<div class="card-container">
<div class="card" id="invoicesCard">
    <h3 style="margin-top: -10px; padding: 10px;">Invoices</h3>
    <p style="font-weight: bold;">
        <span id="invoicesLabel">Total:</span>
        <span id="invoicesValue" style="color: red;"><?php echo $data['invoices']['total']; ?></span>
    </p>
</div>
<div class="card" id="quotationsCard">
    <h3 style="margin-top: -10px; padding: 10px;">Quotations</h3>
    <p style="font-weight: bold;">
        <span id="quotationsLabel">Total:</span>
        <span id="quotationsValue" style="color: red;"><?php echo $data['quotations']['total']; ?></span>
    </p>
</div>
<div class="card" id="transactionsCard">
    <h3 style="margin-top: -10px; padding: 10px;">Transactions</h3>
    <p style="font-weight: bold;">
        <span id="transactionsLabel">Total:</span>
        <span id="transactionsValue" style="color: red;"><?php echo $data['transactions']['total']; ?></span>
    </p>
</div>
</div>

  </div>


    <!-- Modal for Line Chart -->
    <div id="chartModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="chartTitle"></h2>
            <div>
                <button id="totalBtn">Total</button>
                <button id="yearlyBtn">This Year</button>
                <button id="monthlyBtn">This Month</button>
            </div>
            <canvas id="lineChart"></canvas>
        </div>
    </div>

    <script>
    let currentState = 'total';
    let cardData = <?php echo json_encode($data); ?>;
    let chartModal = document.getElementById('chartModal');
    let lineChart = null;
    let currentCard = '';

    // Function to update cards
    function updateCards() {
        const cards = ['contacts', 'followups', 'sales', 'invoices', 'quotations', 'transactions'];
        const labels = {
            'total': 'Total:',
            'yearly': 'This Year:',
            'monthly': 'This Month:'
        };

        cards.forEach(card => {
            const labelElement = document.getElementById(`${card}Label`);
            const valueElement = document.getElementById(`${card}Value`);

            labelElement.textContent = labels[currentState];

            let value = cardData[card][currentState];
            if (card === 'sales') {
                value = parseFloat(value).toFixed(2);
            }
            valueElement.textContent = value;
        });

        // Cycle through states
        if (currentState === 'total') {
            currentState = 'yearly';
        } else if (currentState === 'yearly') {
            currentState = 'monthly';
        } else {
            currentState = 'total';
        }
    }

    // Function to open modal and display chart
    function openChartModal(card) {
        currentCard = card;
        document.getElementById('chartTitle').textContent = card.charAt(0).toUpperCase() + card.slice(1);
        chartModal.style.display = 'block';
        updateChart('total');
    }

    // Function to update the chart
    function updateChart(type) {
        const ctx = document.getElementById('lineChart').getContext('2d');
        let labels = [];
        let dataPoints = [];
        let tooltipData = []; // Store tooltip data for sales

        if (type === 'yearly') {
            // Generate labels for 12 months
            labels = Array.from({ length: 12 }, (_, i) => `Month ${i + 1}`);
            dataPoints = Array(12).fill(0); // Initialize with 0
            tooltipData = Array(12).fill(0); // Initialize tooltip data

            // Populate data for each month
            if (cardData[currentCard][type]) {
                for (const [month, value] of Object.entries(cardData[currentCard][type])) {
                    dataPoints[month - 1] = value; // Months are 1-indexed
                    tooltipData[month - 1] = value; // Store tooltip data
                }
            }
        } else if (type === 'monthly') {
            // Generate labels for 30 days
            labels = Array.from({ length: 30 }, (_, i) => `Day ${i + 1}`);
            dataPoints = Array(30).fill(0); // Initialize with 0
            tooltipData = Array(30).fill(0); // Initialize tooltip data

            // Populate data for each day
            if (cardData[currentCard][type]) {
                for (const [day, value] of Object.entries(cardData[currentCard][type])) {
                    dataPoints[day - 1] = value; // Days are 1-indexed
                    tooltipData[day - 1] = value; // Store tooltip data
                }
            }
        } else {
            // For total, use a single data point
            labels = ['Total'];
            dataPoints = [cardData[currentCard][type]];
            tooltipData = [cardData[currentCard][type]]; // Store tooltip data
        }

        if (lineChart) {
            lineChart.destroy();
        }

        // Calculate the max value for the Y-axis
        const maxValue = Math.max(...dataPoints);

        lineChart = new Chart(ctx, {
            type: 'bar', // Default to bar chart
            data: {
                labels: labels,
                datasets: [
                    {
                        label: `${type.charAt(0).toUpperCase() + type.slice(1)} (Line)`,
                        type: 'line', // Line chart
                        data: dataPoints,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: false
                    },
                    {
                        label: `${type.charAt(0).toUpperCase() + type.slice(1)} (Column)`,
                        type: 'bar', // Column chart
                        data: dataPoints,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: maxValue + (maxValue * 0.1), // Add 10% padding to the max value
                        ticks: {
                            stepSize: maxValue > 10 ? Math.ceil(maxValue / 10) : 1 // Dynamic step size
                        }
                    }
                },
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: `${currentCard.charAt(0).toUpperCase() + currentCard.slice(1)} Data`
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.dataset.label || '';
                                const value = context.raw || 0;
                                if (currentCard === 'sales') {
                                    if (type === 'yearly') {
                                        return `${label}: $${value.toFixed(2)} (Total for Month ${context.dataIndex + 1})`;
                                    } else if (type === 'monthly') {
                                        return `${label}: $${value.toFixed(2)} (Total for Day ${context.dataIndex + 1})`;
                                    } else {
                                        return `${label}: $${value.toFixed(2)} (Total)`;
                                    }
                                } else {
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    // Add click event listeners to cards
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('click', () => {
            const cardId = card.id.replace('Card', '').toLowerCase();
            openChartModal(cardId);
        });
    });

    // Add click event listeners to modal buttons
    document.getElementById('totalBtn').addEventListener('click', () => updateChart('total'));
    document.getElementById('yearlyBtn').addEventListener('click', () => updateChart('yearly'));
    document.getElementById('monthlyBtn').addEventListener('click', () => updateChart('monthly'));

    // Close modal when clicking on the close button
    document.querySelector('.close').addEventListener('click', () => {
        chartModal.style.display = 'none';
    });

    // Close modal when clicking outside of it
    window.addEventListener('click', (event) => {
        if (event.target === chartModal) {
            chartModal.style.display = 'none';
        }
    });

    // Update cards every 3 seconds
    setInterval(updateCards, 3000);

    // Initial update
    updateCards();
    </script>
</body>
</html>
