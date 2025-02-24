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

     <div class="topbar-left">Welcome to the Splendid Infotech CMS !</div>


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
     <a href="update_form.php?id=<?php echo $_SESSION['user_id']; ?>" class="dropdown-item">Profile</a>
     <a href="logout.php" class="dropdown-item">Logout</a>
   </div>
 </div>



  </header>

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

      <li><a href="index.php" class="active"><i class="icon">🏠</i>Dashboard</a></li>

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
                <?php if (is_submenu_allowed('Sales', 'Returned Invoice')): ?>
                  <li><a href="invoice_closed.php">📃⛔ Returned Invoice</a></li>
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
              <li><a href="item_category_display.php">🛒 Items</a></li>
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

    /* Topbar Styles */
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
    }

    .topbar-left {
      font-size: 22px;
      font-weight: bold;
      margin-right: 140px;
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
      width: 18.5%;
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


  </style>
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector(".sidebar")
    const menuItems = document.querySelectorAll(".sidebar .nav-menu li > a")

    function saveSidebarScroll() {
      localStorage.setItem("sidebarScrollPosition", sidebar.scrollTop)
    }

    function restoreSidebarScroll() {
      const scrollPosition = localStorage.getItem("sidebarScrollPosition")
      if (scrollPosition) {
        sidebar.scrollTop = Number.parseInt(scrollPosition, 10)
      }
    }

    function toggleSubmenu(item, isNested = false) {
      const submenu = item.nextElementSibling
      if (submenu && submenu.classList.contains("submenu")) {
        submenu.classList.toggle("visible")
        const menuId = item.textContent.trim()
        localStorage.setItem(menuId, submenu.classList.contains("visible"))

        if (!isNested) {
          // Close other top-level submenus
          menuItems.forEach((otherItem) => {
            if (otherItem !== item && !otherItem.closest(".submenu")) {
              const otherSubmenu = otherItem.nextElementSibling
              if (
                otherSubmenu &&
                otherSubmenu.classList.contains("submenu") &&
                otherSubmenu.classList.contains("visible")
              ) {
                otherSubmenu.classList.remove("visible")
                localStorage.setItem(otherItem.textContent.trim(), "false")
              }
            }
          })
        }

        saveSidebarScroll()
      }
    }

    menuItems.forEach((item) => {
      item.addEventListener("click", function (e) {
        const isNested = this.closest(".submenu") !== null
        if (this.nextElementSibling && this.nextElementSibling.classList.contains("submenu")) {
          e.preventDefault()
          toggleSubmenu(this, isNested)
        }
      })
    })

    // Restore submenu states and scroll position on page load
    function restoreMenuState() {
      menuItems.forEach((item) => {
        const submenu = item.nextElementSibling
        if (submenu && submenu.classList.contains("submenu")) {
          const menuId = item.textContent.trim()
          const isOpen = localStorage.getItem(menuId) === "true"
          if (isOpen) {
            submenu.classList.add("visible")
          }
        }
      })
    }

    restoreMenuState()
    restoreSidebarScroll()

    // Save scroll position when user scrolls the sidebar
    sidebar.addEventListener("scroll", saveSidebarScroll)

    // Avatar dropdown logic
    const avatarButton = document.querySelector(".avatar-button")
    const dropdownMenu = document.querySelector(".dropdown-menu")

    avatarButton.addEventListener("click", (e) => {
      e.stopPropagation()
      dropdownMenu.classList.toggle("active")
    })

    document.addEventListener("click", (e) => {
      if (!avatarButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.remove("active")
      }
    })
  })


  </script>
