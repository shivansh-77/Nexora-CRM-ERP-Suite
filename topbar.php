<?php
include('connection.php');
// Start the session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Retrieve User ID from Session
$user_id = $_SESSION['user_id'];

// Fetch allowed fiscal year codes for the user
$fy_codes = [];
$fy_query = "SELECT fy_code FROM emp_fy_permission WHERE emp_id = ? AND permission = 1";
$stmt = $connection->prepare($fy_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $fy_codes[] = $row['fy_code'];
}

// Convert fy_codes array to a comma-separated string for SQL IN clause
$fy_codes_string = implode("','", $fy_codes);

if (!empty($fy_codes)) {
    // Count Today Followups
    $dateToday = date('Y-m-d');
    $queryToday = "SELECT COUNT(*) AS total
                   FROM followup
                   WHERE followup_date_nxt = CURDATE()
                   AND lead_status = 'Open'
                   AND fy_code IN ('$fy_codes_string')";
    $resultToday = mysqli_query($connection, $queryToday);
    $rowToday = mysqli_fetch_assoc($resultToday);
    $totalTodayEntries = $rowToday['total'] ?? 0;

    // Count Fresh Followups
    $queryFresh = "SELECT COUNT(*) AS total
                   FROM followup
                   WHERE lead_status = 'Open'
                   AND lead_followup = 'Fresh'
                   AND fy_code IN ('$fy_codes_string')";
    $resultFresh = mysqli_query($connection, $queryFresh);
    $rowFresh = mysqli_fetch_assoc($resultFresh);
    $totalFreshEntries = $rowFresh['total'] ?? 0;

    // Count Repeat Followups
    $queryRepeat = "SELECT COUNT(*) AS total
                    FROM followup
                    WHERE lead_status = 'Open'
                    AND lead_followup = 'Repeat'
                    AND fy_code IN ('$fy_codes_string')";
    $resultRepeat = mysqli_query($connection, $queryRepeat);
    $rowRepeat = mysqli_fetch_assoc($resultRepeat);
    $totalRepeatEntries = $rowRepeat['total'] ?? 0;

    // Count Missed Followups
    $queryMissed = "SELECT COUNT(*) AS total
                    FROM followup
                    WHERE followup_date_nxt < CURDATE()
                    AND lead_status = 'Open'
                    AND fy_code IN ('$fy_codes_string')";
    $resultMissed = mysqli_query($connection, $queryMissed);
    $rowMissed = mysqli_fetch_assoc($resultMissed);
    $totalMissedEntries = $rowMissed['total'] ?? 0;
} else {
    // If no fy_codes, set all counts to 0
    $totalTodayEntries = 0;
    $totalFreshEntries = 0;
    $totalRepeatEntries = 0;
    $totalMissedEntries = 0;
}

?>
<div class="container">
  <!-- Topbar -->
  <header class="topbar">
      <?php
      include('connection.php'); // Include database connection

      // Fetch company logo from database
      $query = "SELECT company_logo FROM company_card WHERE id = 1"; // Change `1` to the correct company ID
      $result = mysqli_query($connection, $query);
      $company = mysqli_fetch_assoc($result);

      // Set logo path (fallback to default if not available)
      $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
      ?>

      <!-- Display Dynamic Logo -->
      <img src="<?php echo $company_logo; ?>" alt="Logo" class="sidebar-logo" />

      <div class="topbar-left">Welcome to Splendid Infotech CRM !</div>

          <!-- Search Container -->
          <div class="search-container">
              <input type="text" id="menuSearch" placeholder="Search menus...">
              <div id="searchResults" class="search-results"></div>
          </div>

          <button id="checkInOutButton" class="check-in-out-button">CHECK-IN</button>
          <div id="loadingSpinner" style="display: none;">
              <img src="uploads/Iphone-spinner-2.gif" alt="Loading..." />
          </div>

          <div class="avatar-dropdown">
          <div class="avatar-button">
              <div class="avatar-circle">
                  <?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U'; ?>
              </div>
              <span class="username">
                  <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Username'; ?>
              </span>
          </div>
          <div class="dropdown-menu">
    <a href="profile_display.php?id=<?php echo $_SESSION['user_id']; ?>" class="dropdown-item">Profile</a>
    <a href="user_checkinout_status.php?id=<?php echo $_SESSION['user_id']; ?>&name=<?php echo urlencode($_SESSION['user_name']); ?>" class="dropdown-item">Attendance</a>
    <a href="user_leave_display.php?id=<?php echo $_SESSION['user_id']; ?>&name=<?php echo urlencode($_SESSION['user_name']); ?>" class="dropdown-item">Apply Leave</a>
    <a href="holidays_display_user.php" class="dropdown-item">Holidays</a> <!-- Added Holidays option -->
    <a href="logout.php" class="dropdown-item">Logout</a>
</div>

      </div>
  </header>

</div>


<!-- Sidebar -->
<nav class="sidebar">
    <h2 href="index.php" class="logo">My Dashboard</h2>
    <ul class="nav-menu">
        <?php
        // Check if allowed_submenus is set in the session
        $allowed_submenus = $_SESSION['allowed_submenus'] ?? [];

        // Function to check if a submenu is allowed
        function is_submenu_allowed($menu, $submenu) {
            global $allowed_submenus;
            return isset($allowed_submenus[$menu]) && in_array($submenu, $allowed_submenus[$menu]);
        }

        // Function to check if a menu has any allowed submenus
        function has_allowed_submenus($menu) {
            global $allowed_submenus;
            return isset($allowed_submenus[$menu]) && !empty($allowed_submenus[$menu]);
        }
        ?>
        <?php
        // Check if the user is logged in and has a role set
        if (isset($_SESSION['user_role'])) {
            $userRole = $_SESSION['user_role'];

            // Determine the dashboard URL based on the user's role
            if ($userRole == "Admin") {
                $dashboardUrl = "index.php";
            } else {
                $dashboardUrl = "user_dashboard.php";
            }
        } else {
            // If the user is not logged in, you might want to redirect them to the login page
            $dashboardUrl = "login.php";
        }
        ?>
        <li><a href="<?php echo $dashboardUrl; ?>" class="active"><i class="icon">🏠</i>Dashboard</a></li>

        <?php if (has_allowed_submenus('CRM')): ?>
            <li>
                <a href="#"><i class="icon">👥</i>CRM</a>
                <ul class="submenu">
                    <?php if (is_submenu_allowed('CRM', 'Contacts')): ?>
                        <li><a href="contact_display.php">🧑‍🤝‍🧑 Contacts</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('CRM', 'Fresh Followups')): ?>
                        <li><a href="followup_display.php">🌱 Fresh Followups <span class="circle"><?php echo $totalFreshEntries; ?></span></a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('CRM', 'Repeat Followups')): ?>
                        <li><a href="repeat_followups.php">🔂 Repeat Followups <span class="circle"><?php echo $totalRepeatEntries; ?></span></a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('CRM', 'Today Followups')): ?>
                        <li><a href="today_followup.php">⏰ Today Followups <span class="circle"><?php echo $totalTodayEntries; ?></span></a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('CRM', 'Missed Followups')): ?>
                        <li><a href="missed_followup.php">⚠️ Missed Followups <span class="circle"><?php echo $totalMissedEntries; ?></span></a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('CRM', 'Closed Followups')): ?>
                        <li><a href="closed_followup.php">🔒 Closed Followups</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_allowed_submenus('CMS')): ?>
            <li>
                <a href="#"><i class="icon">⚙️</i>CMS</a>
                <ul class="submenu">
                    <?php if (is_submenu_allowed('CMS', 'Lead For')): ?>
                        <li><a href="lead_for_display.php">📝 Lead For</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('CMS', 'Lead Source')): ?>
                        <li><a href="lead_source_display.php">🔍 Lead Source</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_allowed_submenus('Sales')): ?>
            <li>
                <a href="#"><i class="icon">📊</i>Sales</a>
                <ul class="submenu">
                    <?php if (is_submenu_allowed('Sales', 'Contacts')): ?>
                        <li><a href="contact_display.php">🧑‍🤝‍🧑 Contacts</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Sales', 'Items')): ?>
                        <li><a href="item_display.php">📦 Items</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Sales', 'Quotation')): ?>
                        <li><a href="quotation_display.php">📋 Quotation</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="#">📃 Invoice</a>
                        <ul class="submenu nested">
                            <?php if (is_submenu_allowed('Sales', 'Draft Invoices')): ?>
                                <li><a href="invoice_draft.php">📋📦 Draft Invoices</a></li>
                            <?php endif; ?>
                            <?php if (is_submenu_allowed('Sales', 'Finalized Invoice')): ?>
                                <li><a href="invoice_display.php">📋✅ Finalized Invoice</a></li>
                            <?php endif; ?>
                            <?php if (is_submenu_allowed('Sales', 'Sales Lot Tracking')): ?>
                                <li><a href="invoice_add_lot_display.php">📋 Sales Lot Tracking</a></li>
                            <?php endif; ?>

                        </ul>
                    </li>
                    <li>
                        <a href="#">📃 Sale Invoice Return</a>
                        <ul class="submenu nested">
                            <?php if (is_submenu_allowed('Sales', 'Returned Invoice')): ?>
                                <li><a href="invoice_cancel_display.php">📋📦 Returned Invoice</a></li>
                            <?php endif; ?>
                            <?php if (is_submenu_allowed('Sales', 'Lot Add')): ?>
                                <li><a href="invoice_cancel_add_lot_display.php">📋✅ Lot Add</a></li>
                            <?php endif; ?>

                        </ul>
                    </li>
                    <?php if (is_submenu_allowed('Sales', 'AMC Dues')): ?>
                        <li><a href="amc_due_display.php">📦 AMC Dues</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Sales', 'Item Ledger Display')): ?>
                        <li><a href="item_ledger_display.php">🚚 Item Ledger Display</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Sales', 'Party Ledger')): ?>
                        <li><a href="party_ledger.php">📜 Party Ledger</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Sales', 'Payments')): ?>
                        <li><a href="payment_advance.php">💸 Payments</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Sales', 'Payment Records')): ?>
                        <li><a href="payment_display.php">📒 Payment Records</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Sales', 'Stock Report')): ?>
                        <li><a href="stock_report.php">📋 Stock Report</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Sales', 'Expenses')): ?>
                        <li><a href="expense_display.php">💸 Expenses</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_allowed_submenus('Purchase')): ?>
            <li>
                <a href="#"><i class="icon">🛒</i>Purchase </a>
                <ul class="submenu">
                  <?php if (is_submenu_allowed('Purchase', 'Vendor Contact')): ?>
                      <li><a href="contact_vendor_display.php">📃 Vendor Contacts</a></li>
                  <?php endif; ?>
                    <li>
                        <a href="#">📋 Purchase Order</a>
                        <ul class="submenu nested">
                            <?php if (is_submenu_allowed('Purchase', 'Pending Orders')): ?>
                                <li><a href="purchase_order_pending_display.php">⏳ Pending Orders</a></li>
                            <?php endif; ?>
                            <?php if (is_submenu_allowed('Purchase', 'Completed Orders')): ?>
                                <li><a href="purchase_order_completed_display.php">✅ Completed Orders</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php if (is_submenu_allowed('Purchase', 'Purchase Invoice')): ?>
                        <li><a href="purchase_invoice_display.php">📃 Purchase Invoice</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="#">📋 Purchase Inv. Return</a>
                        <ul class="submenu nested">
                            <?php if (is_submenu_allowed('Purchase', 'Returned Purchase Invoice')): ?>
                                <li><a href="purchase_invoice_cancel_display.php">⏳ Returned Invoices</a></li>
                            <?php endif; ?>
                            <?php if (is_submenu_allowed('Purchase', 'Add Lot')): ?>
                                <li><a href="purchase_invoice_cancel_add_lot.php">✅ Add Lot</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php if (is_submenu_allowed('Purchase', 'Lot Tracking')): ?>
                        <li><a href="purchase_invoice_add_lot.php">📃 Lot Tracking</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Purchase', 'Purchase Ledger Display')): ?>
                        <li><a href="purchase_item_ledger_display.php">📦 Purchase Ledger</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_allowed_submenus('Human Resource')): ?>
            <li>
                <a href="#"><i class="icon">👩‍💼</i>Human Resource</a>
                <ul class="submenu">
                    <?php if (is_submenu_allowed('Human Resource', 'Attendance')): ?>
                        <li><a href="attendance_display.php">🕒 Attendance</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Human Resource', 'Leave Applications')): ?>
                        <li><a href="leave_display.php">📅 Leave Applications</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Human Resource', 'Leave Balance')): ?>
                        <li><a href="leave_balance_display.php">📅 Leave Balance</a></li>
                    <?php endif; ?>
                    <?php if (is_submenu_allowed('Human Resource', 'Salary Sheet')): ?>
                        <li><a href="salary_sheet_fy_display.php">💰 Salary Sheet</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (has_allowed_submenus('Settings')): ?>
      <li>
          <a href="#"><i class="icon">⚙️</i>Settings</a>
          <ul class="submenu">
              <?php if (is_submenu_allowed('Settings', 'Company Card')): ?>
                  <li><a href="companycard.php">💼 Company Card</a></li>
              <?php endif; ?>

              <?php if (is_submenu_allowed('Settings', 'Location Card')): ?>
                  <li><a href="locationcard_display.php">📌 Location Card</a></li>
              <?php endif; ?>

              <!-- ✅ Newly Added Section -->
              <?php if (is_submenu_allowed('Settings', 'Projects')): ?>
                  <li><a href="project_display.php">📂 Projects</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Operation Group')): ?>
                  <li><a href="operation_group_display.php">⚙️ Operation Group</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Operations')): ?>
                  <li><a href="operation_display.php">🧩 Operations</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Task Group')): ?>
                  <li><a href="task_group_display.php">🗂️ Task Group</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Tasks')): ?>
                  <li><a href="task_display.php">✅ Tasks</a></li>
              <?php endif; ?>
              <!-- ✅ End of Newly Added Section -->

              <?php if (is_submenu_allowed('Settings', 'Financial Year')): ?>
                  <li><a href="financial_years_display.php">📈 Financial Year</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'GST')): ?>
                  <li><a href="gst_display.php">💰 GST</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'HSN/SAC')): ?>
                  <li><a href="hsn_sac_display.php">💼 HSN/SAC</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Units')): ?>
                  <li><a href="unit_measurement_display.php">⏳ Units</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Items')): ?>
                  <li><a href="item_category_display.php">🛒 Item Category</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'AMC')): ?>
                  <li><a href="amc_display.php">📆 AMC</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Departments')): ?>
                  <li><a href="department_display.php">🏢 Departments</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Designations')): ?>
                  <li><a href="designation_display.php">🎓 Designations</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Expense Tracker')): ?>
                  <li><a href="expense_tracker_display.php">💸 Expense Type</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'Holidays')): ?>
                  <li><a href="holidays_display.php">📅 Holidays</a></li>
              <?php endif; ?>
              <?php if (is_submenu_allowed('Settings', 'User')): ?>
                  <li><a href="display.php">👤 User</a></li>
              <?php endif; ?>
          </ul>
      </li>
  <?php endif; ?>

    </ul>
</nav>
  <style>
    /* General Styles */
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f4f4f9;
    }

    .container {
      display: flex;
      flex-direction: column;
    }

    /* Updated Topbar Styles */
  .topbar {
    position: fixed;
    width: 100%;
    height: 70px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #2c3e50;
    color: white;
    padding: 0 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 10000;
    gap: 15px; /* Add gap between flex items */
  }

  .topbar-left {
    font-size: 18px; /* Slightly smaller font */
    font-weight: bold;
    white-space: nowrap; /* Prevent text from wrapping */
    min-width: max-content; /* Ensure it doesn't shrink too much */
    margin-right: 40; /* Remove the large right margin */
  }

  /* Updated Search Bar Styles - made it smaller */
  .search-container {
    position: relative;
    margin-right: 10px; /* Reduced margin */
    flex-grow: 1;
    max-width: 250px; /* Reduced max width */
    min-width: 150px; /* Minimum width */
  }

  #menuSearch {
    width: 100%;
    padding: 6px 12px; /* Smaller padding */
    border-radius: 20px;
    border: 1px solid #ccc;
    font-size: 13px; /* Smaller font */
    outline: none;
  }

  /* Avatar dropdown adjustments */
  .avatar-dropdown {
    position: relative;
    display: flex;
    align-items: center;
    z-index: 20000;
    margin-right: 0;
    min-width: max-content; /* Prevent shrinking */
  }

  /* Check-in button adjustments */
  .check-in-out-button {
    padding: 8px 15px; /* Slightly smaller */
    font-size: 14px; /* Smaller font */
    color: white;
    background-color: green;
    border: none;
    border-radius: 5px;
    box-shadow: 0 4px #999;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.1s;
    margin-right: 10px;
    white-space: nowrap;
  }

  /* Responsive adjustments */
  @media (max-width: 1200px) {
    .topbar-left {
      font-size: 16px;
    }
    .search-container {
      max-width: 200px;
    }
  }

  @media (max-width: 992px) {
    .topbar-left {
      font-size: 14px;
    }
    #menuSearch {
      padding: 5px 10px;
      font-size: 12px;
    }
    .check-in-out-button {
      padding: 6px 12px;
      font-size: 13px;
    }
  }

  @media (max-width: 768px) {
    .topbar {
      padding: 0 10px;
      gap: 10px;
    }
    .topbar-left {
      display: none; /* Hide welcome text on very small screens */
    }
    .search-container {
      max-width: 180px;
      margin-right: 5px;
    }
  }

    .logout-button {
      background-color: #e74c3c;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      margin-right: 30px;
    }

    .logout-button:hover {
      background-color: #c0392b;
    }

    /* Sidebar Styles */
    .sidebar {
      width: 258px;
      background-color: #2c3e50;
      color: white;
      position: fixed;
      top: 60px;
      bottom: 0;
      left: 0;
      overflow-y: auto;
      padding-top: 20px;
    }

    .sidebar-logo {
      width: 250px;
      margin: 10px auto 10px;
      display: block;
      margin-left: -20px;
    }

    .logo {
      text-align: center;
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
    }

    .nav-menu {
      list-style: none;
      padding: 0;
    }

    .nav-menu li {
      margin: 10px 0;
    }

    .nav-menu a {
      text-decoration: none;
      color: white;
      padding: 10px 20px;
      display: block;
      font-size: 18px;
      transition: background-color 0.3s;
    }

    .nav-menu a:hover,
    .nav-menu a.active {
      background-color: #34495e;
    }
    /* Submenu Styles */
.nav-menu .submenu {
  display: none; /* Hidden by default */
  font-size: 14px; /* Adjust this to change submenu font size */
  list-style: none;
  padding-left: 20px;
  background: #5a738c;
  margin: 0;
  transition: max-height 0.3s ease, opacity 0.3s ease;
  max-height: 0;
  overflow: hidden;
  opacity: 0;
}

.nav-menu .submenu.visible {
  display: block; /* Only displayed when the visible class is toggled */
  max-height: 500px; /* Adjust as needed */
  opacity: 1;
}

/* Make submenu link font-size smaller */
.nav-menu .submenu li a {
  font-size: 16px; /* Adjust this value for submenu links */
  padding: 8px 20px; /* Adjust padding if necessary */
}

    .icon {
      margin-right: 10px;
    }

    /* Content Styles */
    .content {
      margin-left: 250px;
      margin-top: 60px;
      padding: 20px;
    }

    .card-container {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 20px;
    }

    .card {
      flex: 1 1 calc(25% - 20px);
      background-color: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .card h3 {
      font-size: 20px;
      margin-bottom: 10px;
    }

    .card p {
      font-size: 16px;
      margin: 5px 0;
    }

    .table-container {
      margin-top: 40px;
      background-color: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      text-align: left;
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #34495e;
      color: white;
    }
    .circle {
    display: inline-block;
    width: 20px;  /* Adjust size as needed */
    height: 20px; /* Adjust size as needed */
    border-radius: 50%;
    background-color: #ff5733; /* Change color as needed */
    color: white;
    text-align: center;
    line-height: 24px; /* Center the text vertically */
    margin-left: 2; /* Remove space between link and circle */
    font-size: 14px; /* Adjust font size as needed */
    vertical-align: middle; /* Align vertically with text */
}
.avatar-dropdown {
  position: relative;
  display: inline-block;
  z-index: 20000;
  margin-right: 10px;
}

.avatar-button {
  display: flex;
  align-items: center;
  cursor: pointer;
  color: white;
}

.avatar-circle {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background-color: #34495e;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  margin-right: 7px;
  font-size: 16px;
}

.username {
  margin-right: 19px;
  font-size: 16px;
}

.arrow {
  font-size: 12px;
}

.dropdown-menu {
  position: absolute;
  top: calc(100% - 4px); /* Dropdown starts below the avatar circle */
  left: 0; /* Align dropdown with the avatar */
  right: auto;
  margin: 0;
  background-color: white;
  color: #34495e;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border-radius: 5px;
  overflow: hidden;
  display: none;
  flex-direction: column;
  z-index: 10000;
}

.dropdown-item {
  padding: 10px 20px;
  text-decoration: none;
  color: #34495e;
  font-size: 14px;
  transition: background-color 0.2s;
  display: block;
}

.dropdown-item:hover {
  background-color: #f4f4f9;
}

.avatar-dropdown:hover .dropdown-menu {
  display: block;
}

.submenu.nested {
  background-color: #4a6075;
  padding-left: 20px;
}

.submenu.nested a {
  font-size: 14px;
}

.nav-menu .submenu {
  display: none;
  font-size: 14px;
  list-style: none;
  padding-left: 20px;
  background: #5a738c;
  margin: 0;
  transition: max-height 0.3s ease, opacity 0.3s ease;
  max-height: 0;
  overflow: hidden;
  opacity: 0;
}

.nav-menu .submenu.visible {
  display: block;
  max-height: 1000px; /* Increased max-height to accommodate nested submenus */
  opacity: 1;
}

.nav-menu .submenu li a {
  font-size: 16px;
  padding: 8px 20px;
}

.submenu.nested {
  background-color: #4a6075;
  padding-left: 11px;

}

.submenu.nested a {
  font-size: 14px;
}

.check-in-out-button {
    padding: 10px 18px;
    font-size: 16px;
    color: white;
    background-color: green;
    border: none;
    border-radius: 5px;
    box-shadow: 0 4px #999;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.1s;
    margin-right: 10px; /* Space between button and avatar */
}

.check-in-out-button:active {
    transform: translateY(4px);
    box-shadow: 0 0 #999;
}

.check-in-out-button.checked-out {
    background-color: red;
}

#loadingSpinner {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1000; /* Ensure it appears above other elements */
    width: 100px; /* Adjust the width to make it bigger */
    height: 100px; /* Adjust the height to make it bigger */
}

/* If the spinner contains an image or icon, you can scale it as well */
#loadingSpinner img, #loadingSpinner svg, #loadingSpinner div {
    width: 100%; /* Make the internal element fill the container */
    height: 100%;
}

/* Search Bar Styles */
.search-container {
    position: relative;
    margin-right: 20px;
    flex-grow: 1;
    max-width: 400px;
}

#menuSearch {
    width: 100%;
    padding: 8px 15px;
    border-radius: 20px;
    border: 1px solid #ccc;
    font-size: 14px;
    outline: none;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.search-result-item {
    padding: 10px 15px;
    color: #333;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.search-result-item.highlighted {
    background-color: #e0e0e0;
}
/* Add to your existing CSS */
.highlighted-menu {
    background-color: #ffeb3b !important;
    color: #333 !important;
    transition: background-color 0.5s ease;
}
.search-result-item .icon {
    margin-right: 8px;
    vertical-align: middle;
}
  </style>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const sidebar = document.querySelector(".sidebar");
      const menuItems = document.querySelectorAll(".sidebar .nav-menu li > a");

      function saveSidebarScroll() {
        localStorage.setItem("sidebarScrollPosition", sidebar.scrollTop);
      }

      function restoreSidebarScroll() {
        const scrollPosition = localStorage.getItem("sidebarScrollPosition");
        if (scrollPosition) {
          sidebar.scrollTop = Number.parseInt(scrollPosition, 10);
        }
      }

      function toggleSubmenu(item, isNested = false) {
        const submenu = item.nextElementSibling;
        if (submenu && submenu.classList.contains("submenu")) {
          submenu.classList.toggle("visible");
          const menuId = item.textContent.trim();
          localStorage.setItem(menuId, submenu.classList.contains("visible"));

          if (!isNested) {
            // Close other top-level submenus
            menuItems.forEach((otherItem) => {
              if (otherItem !== item && !otherItem.closest(".submenu")) {
                const otherSubmenu = otherItem.nextElementSibling;
                if (
                  otherSubmenu &&
                  otherSubmenu.classList.contains("submenu") &&
                  otherSubmenu.classList.contains("visible")
                ) {
                  otherSubmenu.classList.remove("visible");
                  localStorage.setItem(otherItem.textContent.trim(), "false");
                }
              }
            });
          }

          saveSidebarScroll();
        }
      }

      menuItems.forEach((item) => {
        item.addEventListener("click", function (e) {
          const isNested = this.closest(".submenu") !== null;
          if (this.nextElementSibling && this.nextElementSibling.classList.contains("submenu")) {
            e.preventDefault();
            toggleSubmenu(this, isNested);
          }
        });
      });

      // Restore submenu states and scroll position on page load
      function restoreMenuState() {
        menuItems.forEach((item) => {
          const submenu = item.nextElementSibling;
          if (submenu && submenu.classList.contains("submenu")) {
            const menuId = item.textContent.trim();
            const isOpen = localStorage.getItem(menuId) === "true";
            if (isOpen) {
              submenu.classList.add("visible");
            }
          }
        });
      }

      restoreMenuState();
      restoreSidebarScroll();

      // Save scroll position when user scrolls the sidebar
      sidebar.addEventListener("scroll", saveSidebarScroll);

      // Avatar dropdown logic
      const avatarButton = document.querySelector(".avatar-button");
      const dropdownMenu = document.querySelector(".dropdown-menu");

      avatarButton.addEventListener("click", (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle("active");
      });

      document.addEventListener("click", (e) => {
        if (!avatarButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
          dropdownMenu.classList.remove("active");
        }
      });

      // Check session status on page load
      checkSessionStatus();

      // Attach the getLocation function to the button click event
      const button = document.getElementById("checkInOutButton");
      button.addEventListener("click", function () {
        const action = button.classList.contains('checked-out') ? 'check-out' : 'check-in';
        const confirmationMessage = action === 'check-in' ? 'Do you want to check-in?' : 'Do you want to check-out?';
        if (confirm(confirmationMessage)) {
          getLocation(action);
        }
      });
    });

    // Function to get the user's current location with high accuracy
    function getLocation(action) {
      if (navigator.geolocation) {
        const options = {
          enableHighAccuracy: true, // Request high-precision location
          timeout: 15000,          // Timeout after 15 seconds (increase if needed)
          maximumAge: 0            // Force fresh location data
        };
        navigator.geolocation.getCurrentPosition(
          (position) => showPosition(position, action),
          handleError,
          options
        );
      } else {
        alert("Geolocation is not supported by this browser.");
      }
    }

    // Function to handle errors from the Geolocation API
    function handleError(error) {
      switch (error.code) {
        case error.PERMISSION_DENIED:
          alert("User denied the request for Geolocation.");
          break;
        case error.POSITION_UNAVAILABLE:
          alert("Location information is unavailable.");
          break;
        case error.TIMEOUT:
          alert("The request to get user location timed out.");
          break;
        default:
          alert("An unknown error occurred.");
          break;
      }
    }

    function showPosition(position, action) {
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;

      console.log("Latitude:", latitude);
      console.log("Longitude:", longitude);

      // Show loading spinner
      const loadingSpinner = document.getElementById("loadingSpinner");
      loadingSpinner.style.display = "block";

      // Prepare the data to send via AJAX
      const data = new URLSearchParams();
      data.append('checkin_latitude', latitude);
      data.append('checkin_longitude', longitude);

      // Send AJAX request
      const xhr = new XMLHttpRequest();
      xhr.open("POST", action === 'check-in' ? 'checkin.php' : 'checkout.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          // Hide loading spinner
          loadingSpinner.style.display = "none";

          if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === "success") {
              // Toggle button state
              const button = document.getElementById("checkInOutButton");
              if (action === 'check-in') {
                button.classList.add('checked-out');
                button.textContent = 'CHECK-OUT';
                startTimer(); // Start the timer
              } else {
                button.classList.remove('checked-out');
                button.textContent = 'CHECK-IN';
                stopTimer(); // Stop the timer
              }

              // Show success message
              alert(response.message);

              // Refresh the page after both check-in and check-out
              location.reload(); // Refresh the page
            } else {
              alert(response.message); // Show error message
            }
          } else {
            alert("An error occurred while processing your request.");
          }
        }
      };
      xhr.send(data);
    }

    function checkSessionStatus() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "check_session_status.php", true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        console.log("Session Status Response:", response); // Debugging line
        if (response.status === "success") {
          const button = document.getElementById("checkInOutButton");

          if (response.session_status === "active") {
            // First fetch company checkout time and user's checkin time
            fetchCompanyCheckoutTime().then(companyCheckoutTime => {
              const currentTime = new Date();
              const checkinTime = new Date(response.start_time);

              // Create date string from checkin time (YYYY-MM-DD)
              const checkinDate = checkinTime.toISOString().split('T')[0];

              // Create cutoff datetime (checkin date + company checkout time)
              const cutoffTime = new Date(`${checkinDate}T${companyCheckoutTime}`);

              // If current time is after cutoff time, auto checkout
              if (currentTime > cutoffTime) {
                checkOutSession();
              } else {
                button.classList.add('checked-out');
                button.textContent = 'CHECK-OUT';
                startTimer(); // Start the timer if the session is active
              }
            });
          } else {
            button.classList.remove('checked-out');
            button.textContent = 'CHECK-IN';
          }
        } else {
          alert(response.message); // Show error message
        }
      }
    };
    xhr.send();
  }

  // Helper function to fetch company checkout time
  function fetchCompanyCheckoutTime() {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "get_company_checkout_time.php", true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);
          if (response.status === "success") {
            resolve(response.checkout_time);
          } else {
            console.error("Failed to fetch company checkout time");
            // Fallback to default 23:59:59 if there's an error
            resolve("23:59:59");
          }
        }
      };
      xhr.send();
    });
  }

    // Function to automatically check out the user
    function checkOutSession() {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "checkout.php", true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);
          if (response.status === "success") {
            const button = document.getElementById("checkInOutButton");
            button.classList.remove('checked-out');
            button.textContent = 'CHECK-IN';
            alert("Automatically checked out due to inactivity.");
            location.reload(); // Refresh the page after automatic check-out
          } else {
            alert(response.message); // Show error message
          }
        }
      };
      xhr.send('auto=true'); // Send the auto parameter
    }

    // Menu Search Functionality
const menuSearch = document.getElementById('menuSearch');
const searchResults = document.getElementById('searchResults');

// Collect all menu items and their relationships when page loads
const menuData = [];
document.querySelectorAll('.nav-menu > li').forEach(menuItem => {
    const menuLink = menuItem.querySelector('a');
    const submenu = menuItem.querySelector('.submenu');
    const submenuItems = submenu ? Array.from(submenu.querySelectorAll('a')).map(item => ({
        text: item.textContent.trim(),
        element: item,
        href: item.getAttribute('href')
    })) : [];

    menuData.push({
        menuText: menuLink.textContent.trim(),
        menuElement: menuLink,
        submenuItems: submenuItems
    });
});

menuSearch.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    searchResults.innerHTML = '';

    if (searchTerm.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    const matchingItems = [];

    // Search through all menu data
    menuData.forEach(menu => {
        // Check if search term matches menu text (parent)
        const menuMatch = menu.menuText.toLowerCase().includes(searchTerm);

        // Find matching submenu items
        const matchingSubItems = menu.submenuItems.filter(item =>
            item.text.toLowerCase().includes(searchTerm)
        );

        // If search matches menu text, include all its submenu items
        if (menuMatch) {
            matchingItems.push(...menu.submenuItems);
        }
        // Otherwise include just the matching submenu items
        else if (matchingSubItems.length > 0) {
            matchingItems.push(...matchingSubItems);
        }
    });

    // Display results
    if (matchingItems.length === 0) {
        const noResults = document.createElement('div');
        noResults.className = 'search-result-item';
        noResults.textContent = 'No results found';
        searchResults.appendChild(noResults);
    } else {
        // Remove duplicates (in case a submenu item appears in multiple parent matches)
        const uniqueItems = matchingItems.filter((item, index, self) =>
            index === self.findIndex(i => i.href === item.href && i.text === item.text)
        );

        uniqueItems.forEach(item => {
            const resultItem = document.createElement('div');
            resultItem.className = 'search-result-item';

            // Add icon if available
            const icon = item.element.querySelector('.icon')?.cloneNode(true);
            if (icon) {
                resultItem.appendChild(icon);
            }

            resultItem.appendChild(document.createTextNode(item.text));

            resultItem.addEventListener('click', function() {
                // Navigate to the page
                if (item.href && item.href !== '#') {
                    window.location.href = item.href;
                }

                // Highlight the selected item in the sidebar
                highlightMenuItem(item.element);

                // Clear search and hide results
                menuSearch.value = '';
                searchResults.style.display = 'none';
            });

            searchResults.appendChild(resultItem);
        });
    }

    searchResults.style.display = matchingItems.length ? 'block' : 'none';
});

function highlightMenuItem(element) {
    // Remove any existing highlights
    document.querySelectorAll('.highlighted-menu').forEach(el => {
        el.classList.remove('highlighted-menu');
    });

    // Add highlight to the selected item
    element.classList.add('highlighted-menu');

    // Open parent menus if this is a submenu item
    const parentMenu = element.closest('.nav-menu > li');
    if (parentMenu) {
        const menuLink = parentMenu.querySelector('a');
        const submenu = parentMenu.querySelector('.submenu');

        if (submenu && !submenu.classList.contains('visible')) {
            toggleSubmenu(menuLink);
        }
    }

    // Scroll to the item in the sidebar
    element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!menuSearch.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});

// Add keyboard navigation for search results
menuSearch.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
        const items = searchResults.querySelectorAll('.search-result-item');
        if (items.length === 0) return;

        let currentIndex = -1;
        items.forEach((item, index) => {
            if (item.classList.contains('highlighted')) {
                item.classList.remove('highlighted');
                currentIndex = index;
            }
        });

        if (e.key === 'ArrowDown') {
            currentIndex = (currentIndex + 1) % items.length;
        } else if (e.key === 'ArrowUp') {
            currentIndex = (currentIndex - 1 + items.length) % items.length;
        }

        if (currentIndex >= 0) {
            items[currentIndex].classList.add('highlighted');
            items[currentIndex].scrollIntoView({ block: 'nearest' });

            if (e.key === 'Enter') {
                items[currentIndex].click();
            }
        }

        e.preventDefault();
    } else if (e.key === 'Escape') {
        searchResults.style.display = 'none';
        menuSearch.blur();
    }
});
  </script>


</body>
</html>
