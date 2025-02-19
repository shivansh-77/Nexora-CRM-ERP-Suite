<?php
session_start();
include('connection.php');
include('topbar.php');

// Function to fetch data for all categories
function fetchData($connection) {
    $queries = [
        'contacts' => [
            'total' => "SELECT COUNT(*) AS total FROM contact",
            'yearly' => "SELECT COUNT(*) AS yearly FROM contact WHERE YEAR(created_at) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM contact WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(created_at) AS month, COUNT(*) AS count FROM contact WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at)",
            'monthly_data' => "SELECT DAY(created_at) AS day, COUNT(*) AS count FROM contact WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) GROUP BY DAY(created_at)"
        ],
        'followups' => [
            'total' => "SELECT COUNT(*) AS total FROM followup WHERE lead_status = 'Open'",
            'yearly' => "SELECT COUNT(*) AS yearly FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE()) AND MONTH(followup_date_nxt) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(followup_date_nxt) AS month, COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE()) GROUP BY MONTH(followup_date_nxt)",
            'monthly_data' => "SELECT DAY(followup_date_nxt) AS day, COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE()) AND MONTH(followup_date_nxt) = MONTH(CURDATE()) GROUP BY DAY(followup_date_nxt)"
        ],
        'sales' => [
            'total' => "SELECT SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT SUM(net_amount) AS yearly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE())",
            'monthly' => "SELECT SUM(net_amount) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(invoice_date) AS month, SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) GROUP BY MONTH(invoice_date)",
            'monthly_data' => "SELECT DAY(invoice_date) AS day, SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE()) GROUP BY DAY(invoice_date)"
        ],
        'invoices' => [
            'total' => "SELECT COUNT(*) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT COUNT(*) AS yearly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(invoice_date) AS month, COUNT(*) AS count FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) GROUP BY MONTH(invoice_date)",
            'monthly_data' => "SELECT DAY(invoice_date) AS day, COUNT(*) AS count FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE()) GROUP BY DAY(invoice_date)"
        ],
        'quotations' => [
            'total' => "SELECT COUNT(*) AS total FROM quotations",
            'yearly' => "SELECT COUNT(*) AS yearly FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE()) AND MONTH(quotation_date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(quotation_date) AS month, COUNT(*) AS count FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE()) GROUP BY MONTH(quotation_date)",
            'monthly_data' => "SELECT DAY(quotation_date) AS day, COUNT(*) AS count FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE()) AND MONTH(quotation_date) = MONTH(CURDATE()) GROUP BY DAY(quotation_date)"
        ],
        'transactions' => [
            'total' => "SELECT COUNT(*) AS total FROM party_ledger",
            'yearly' => "SELECT COUNT(*) AS yearly FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(date) AS month, COUNT(*) AS count FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE()) GROUP BY MONTH(date)",
            'monthly_data' => "SELECT DAY(date) AS day, COUNT(*) AS count FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) GROUP BY DAY(date)"
        ],
        'today_followups' => "SELECT COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND followup_date_nxt = CURDATE()",
        'amc_dues_today' => "SELECT COUNT(*) AS count FROM invoice_items WHERE amc_due_date = CURDATE()",
        'sales_today' => "SELECT SUM(net_amount) AS sales_today FROM invoices WHERE status = 'Finalized' AND DATE(invoice_date) = CURDATE()",
        'pending_amount' => "SELECT SUM(pending_amount) AS pending_amount FROM invoices WHERE status = 'Finalized'"
    ];

    $data = [];
    foreach ($queries as $category => $query) {
        if (is_array($query)) {
            foreach ($query as $type => $sql) {
                $result = mysqli_query($connection, $sql);
                if ($type === 'yearly_data' || $type === 'monthly_data') {
                    $data[$category][$type] = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $data[$category][$type][$row['month'] ?? $row['day']] = $row['count'] ?? $row['total'];
                    }
                } else {
                    $data[$category][$type] = mysqli_fetch_assoc($result)[$type] ?? 0;
                }
            }
        } else {
            $result = mysqli_query($connection, $query);
            $row = mysqli_fetch_assoc($result);
            if ($category === 'pending_amount') {
                $data[$category] = $row['pending_amount'] ?? 0;
            } elseif ($category === 'sales_today') {
                $data[$category] = $row['sales_today'] ?? 0;
            } else {
                $data[$category] = $row['count'] ?? 0;
            }
        }
    }

    return $data;
}

$data = fetchData($connection);

// You can now use $data to access all the fetched information
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
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .leadforhead {
            position: fixed;
            width: 75%;
            height: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2c3e50;
            color: white;
            padding: 0 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow: visible; /* Ensure child elements are visible */
            margin-left: 260px;
            margin-top: 80px;
        }

    .leadforhead h2 {
        font-size: 24px;
        font-weight: 600;
        margin: 0; /* Ensure no extra margin is pushing the text out */
    }

        .content {
            margin-top: 100px;
            padding: 20px;
        }

        .hidden {
            margin-bottom: 20px;
        }


        .card-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .card p {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            margin: 0;
        }

        .card p span {
            color: #333;
            font-weight: 500;
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
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 100%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-left: 300px;

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

        #chartModal h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        #chartModal button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        #chartModal button:hover {
            background-color: #2980b9;
        }

        #additionalCards {
            background-color: #ff9999;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #additionalCards .card {
            background-color: rgba(255, 255, 255, 0.9);
        }
        .card h3, .card p {
    transition: opacity 0.5s ease, transform 0.5s ease;
}

    </style>
</head>
<body>
    <div class="leadforhead">
        <h2>DASHBOARD</h2>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div id="additionalCards" class="card-container hidden">
    <a href="today_followup.php" class="card-link" style="text-decoration: none; color: inherit;">
        <div class="card">
            <h3>Today's Followups</h3>
            <p><span>Total:</span> <?php echo $data['today_followups']; ?></p>
        </div>
    </a>

    <a href="amc_due_display.php" class="card-link" style="text-decoration: none; color: inherit;">
        <div class="card">
            <h3>AMC Dues Today</h3>
            <p><span>Total:</span> <?php echo $data['amc_dues_today']; ?></p>
        </div>
    </a>

    <a href="invoice_display.php" class="card-link" style="text-decoration: none; color: inherit;">
        <div class="card">
            <h3>Pending Amount</h3>
            <p><span>Total:</span> ₹<?php echo number_format($data['pending_amount'], 2); ?></p>
        </div>
    </a>
</div>

        <div class="card-container hidden">
            <div class="card" id="contactsCard">
                <h3>Contacts</h3>
                <p><span id="contactsLabel">Total:</span> <span id="contactsValue" style="color: red;"><?php echo $data['contacts']['total']; ?></span></p>
            </div>
            <div class="card" id="followupsCard">
                <h3>Follow-ups</h3>
                <p><span id="followupsLabel">Total:</span> <span id="followupsValue" style="color: red;"><?php echo $data['followups']['total']; ?></span></p>
            </div>
            <div class="card" id="salesCard">
                <h3> Total Sales</h3>
                <p><span id="salesLabel">Total:₹</span> <span id="salesValue" style="color: red;">₹<?php echo number_format($data['sales']['total'], 2); ?></span></p>
            </div>
        </div>

        <div class="card-container">
            <div class="card" id="invoicesCard">
                <h3>Invoices</h3>
                <p><span id="invoicesLabel">Total:</span> <span id="invoicesValue" style="color: red;"><?php echo $data['invoices']['total']; ?></span></p>
            </div>
            <div class="card" id="quotationsCard">
                <h3>Quotations</h3>
                <p><span id="quotationsLabel">Total:</span> <span id="quotationsValue" style="color: red;"><?php echo $data['quotations']['total']; ?></span></p>
            </div>
            <div class="card" id="transactionsCard">
                <h3>Transactions</h3>
                <p><span id="transactionsLabel">Total:</span> <span id="transactionsValue" style="color: red;"><?php echo $data['transactions']['total']; ?></span></p>
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

              // Add fade-out class
              labelElement.classList.add('fade-out');
              valueElement.classList.add('fade-out');

              setTimeout(() => {
                  // Update the text
                  labelElement.textContent = labels[currentState];

                  let value = cardData[card][currentState];
                  if (card === 'sales') {
                      // Format the sales value with commas for Indian numbering system and 2 decimal places
                      value = parseFloat(value).toLocaleString('en-IN', {
                          maximumFractionDigits: 2,
                          minimumFractionDigits: 2
                      });
                  } else {
                      // For other cards, remove decimal places and format as whole numbers
                      value = parseInt(value).toLocaleString('en-IN');
                  }
                  valueElement.textContent = value;

                  // Remove fade-out and add fade-in class
                  labelElement.classList.remove('fade-out');
                  valueElement.classList.remove('fade-out');
                  labelElement.classList.add('fade-in');
                  valueElement.classList.add('fade-in');

                  // Remove fade-in class after the transition
                  setTimeout(() => {
                      labelElement.classList.remove('fade-in');
                      valueElement.classList.remove('fade-in');
                  }, 500);
              }, 500); // Delay to allow the fade-out to complete
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
        let tooltipData = [];

        if (type === 'yearly') {
            // Generate labels for 12 months with month names
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            labels = monthNames; // Use full month names
            dataPoints = Array(12).fill(0);
            tooltipData = Array(12).fill(0);

            // Populate data for each month
            if (cardData[currentCard][`${type}_data`]) {
                for (const [month, value] of Object.entries(cardData[currentCard][`${type}_data`])) {
                    dataPoints[month - 1] = value;
                    tooltipData[month - 1] = value;
                }
            }
        } else if (type === 'monthly') {
            // Generate labels for 31 days
            labels = Array.from({ length: 31 }, (_, i) => `Day ${i + 1}`);
            dataPoints = Array(31).fill(0);
            tooltipData = Array(31).fill(0);

            // Populate data for each day
            if (cardData[currentCard][`${type}_data`]) {
                for (const [day, value] of Object.entries(cardData[currentCard][`${type}_data`])) {
                    dataPoints[day - 1] = value;
                    tooltipData[day - 1] = value;
                }
            }
        } else {
            // For total, use a single data point
            labels = ['Total'];
            dataPoints = [cardData[currentCard][type]];
            tooltipData = [cardData[currentCard][type]];
        }

        if (lineChart) {
            lineChart.destroy();
        }

        // Calculate the max value for the Y-axis
        const maxValue = Math.max(...dataPoints);

        lineChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: `${type.charAt(0).toUpperCase() + type.slice(1)} (Line)`,
                        type: 'line',
                        data: dataPoints,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: false
                    },
                    {
                        label: `${type.charAt(0).toUpperCase() + type.slice(1)} (Column)`,
                        type: 'bar',
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
                            stepSize: maxValue > 10 ? Math.ceil(maxValue / 10) : 1
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
                    }
                }
            }
        });
    }

      // Add click event listeners to cards
      document.querySelectorAll('.card').forEach(card => {
          card.addEventListener('click', (event) => {
              // Check if the click was on an anchor tag
              if (!event.target.closest('a')) {
                  const cardId = card.id.replace('Card', '').toLowerCase();
                  openChartModal(cardId);
              }
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
