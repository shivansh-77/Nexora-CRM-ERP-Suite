<?php
session_start();
include('connection.php');
include('topbar.php');

// Fetch menu and user ID from the URL
$menu = $_GET['menu'] ?? null;
$user_id = $_GET['id'] ?? null;
$user_name = $_GET['name'] ?? null;

if (!$menu || !$user_id) {
    die("Invalid menu or user ID.");
}

// Define submenus based on the selected menu (hardcoded for now)
$submenus = [];
switch ($menu) {
    case 'CRM':
        $submenus = [
            "Contacts" => "contact_display.php",
            "Fresh Followups" => "followup_display.php",
            "Repeat Followups" => "repeat_followups.php",
            "Today Followups" => "today_followup.php",
            "Missed Followups" => "missed_followup.php",
            "Closed Followups" => "closed_followup.php"
        ];
        break;
    case 'CMS':
        $submenus = [
            "Lead For" => "lead_for.php",
            "Lead Source" => "Source_Lead.php"
        ];
        break;
    case 'Sales':
        $submenus = [
            "Contacts" => "contact_display.php",
            "Items" => "item_display.php",
            "Quotation" => "quotation_display.php",
            "Invoice" => [
                "Draft Invoices" => "invoice_draft.php",
                "Finalized Invoice" => "invoice_display.php",
                "Returned Invoice" => "invoice_closed.php"
            ],
            "AMC Dues" => "amc_due_display.php",
            "Item Ledger Display" => "item_ledger_display.php",
            "Party Ledger" => "party_ledger.php",
            "Expenses" => "expense_display.php" // Added Expenses
        ];
        break;
    case 'Purchase': // New Purchase Menu
        $submenus = [
            "Purchase Order" => "purchase_order_display.php",
            "Purchase Invoice" => "purchase_invoice_display.php",
            "Returned Invoice" => "purchase_invoice_closed_display.php"
        ];
        break;
    case 'Settings':
        $submenus = [
            "Company Card" => "companycard.php",
            "Location Card" => "locationcard_display.php",
            "Financial Year" => "financial_years_display.php",
            "GST" => "gst_display.php",
            "HSN/SAC" => "hsn_sac_display.php",
            "Units" => "unit_measurement_display.php",
            "Items" => "item_category_display.php",
            "AMC" => "amc_display.php",
            "Departments" => "department_display.php",
            "Designations" => "designation_display.php",
            "Expense Tracker" => "expense_tracker_display.php",
            "User" => "display.php"
        ];
        break;
    case 'Human Resource':
        $submenus = [
            "Attendance" => "attendance_display.php",
            "Leave Applications" => "leave_display.php",
            "Leave Balance" => "leave_balance_display.php"
        ];
        break;
    default:
        $submenus = [];
}

// Fetch existing permissions for the user
$permissions = [];
$query = "SELECT submenu_name, has_access FROM user_menu_permission WHERE user_id = ? AND menu_name = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("is", $user_id, $menu);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['has_access'] == 1) {
        $permissions[] = $row['submenu_name'];
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submenu Access</title>
    <style>
        html, body {
            overflow: hidden;
            height: 100%;
            margin: 0;
        }
        /* Table Wrapper with Responsive Scroll */
        .user-table-wrapper {
            width: calc(100% - 260px);
            margin-left: 260px;
            margin-top: 140px;
            max-height: calc(100vh - 140px); /* Dynamic height based on viewport */
            min-height: 100vh; /* Ensures it doesn't shrink too much */
            overflow-y: auto; /* Enables vertical scrolling */
            border: 1px solid #ddd;
            background-color: white;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            table-layout: auto;
        }
        .user-table th, .user-table td {
            padding: 12px;
            border: 1px solid #ddd;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: center;
        }
        .user-table th {
            background-color: #2c3e50;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .user-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .user-table tr:hover {
            background-color: #f1f1f1;
        }
        .user-table td:last-child {
            text-align: right;
            width: auto;
            padding: 5px 8px;
        }
        .btn-primary, .btn-secondary, .btn-danger, .btn-warning {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
        }
        .btn-primary { background-color: #a5f3fc; }
        .btn-secondary { background-color: #6c757d; }
        .btn-danger { background-color: #dc3545; }
        .btn-warning { background-color: #3498db; color: black; }
        .leadforhead {
            position: fixed;
            width: calc(100% - 290px); /* Adjust width to account for sidebar */
            height: 50px;
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
        .lead-actions {
            display: flex;
            align-items: center;
            gap: 10px;
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
        .btn-search {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .search-bar {
            display: flex;
            align-items: center;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            margin-right: 40px;
        }
        .search-input {
            border: none;
            padding: 8px;
            outline: none;
            font-size: 14px;
            width: 273px;
        }
        .search-input:focus {
            border: none;
            outline: none;
        }
        .user-table th,
        .user-table td {
            text-align: center;
        }
        .user-table th:last-child,
        .user-table td:last-child {
            text-align: center;
        }
        table th:last-child, table td:last-child {
            width: 100px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Submenu Permissions of <?= htmlspecialchars($menu) ?> For User: <?= htmlspecialchars($user_name)?></h2>
        <div class="lead-actions">
          <button style="background-color:#50C878;" title="Give all the Submenu Permissions to this user" class="btn btn-primary" id="checkAll">🔓</button>
          <button style="background-color:#DC143C;" title="Block all the Submenu Permissions for this user" class="btn btn-primary" id="uncheckAll">🔏</button>
        </div>
    </div>
    <div class="user-table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Submenu</th>
                    <th>Access</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submenus as $key => $value): ?>
                    <?php if (is_array($value)): ?>
                        <?php foreach ($value as $submenu => $link): ?>
                            <tr>
                                <td><?= htmlspecialchars($submenu) ?></td>
                                <td><input type="checkbox" name="submenu_access[]" value="<?= htmlspecialchars($submenu) ?>" <?= in_array($submenu, $permissions) ? 'checked' : '' ?>></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td><?= htmlspecialchars($key) ?></td>
                            <td><input type="checkbox" title="click this checkbox to give the permission for this submenu" name="submenu_access[]" value="<?= htmlspecialchars($key) ?>" <?= in_array($key, $permissions) ? 'checked' : '' ?>></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const checkboxes = document.querySelectorAll('.user-table tbody input[type="checkbox"][name="submenu_access[]"]');
      const checkAllButton = document.getElementById('checkAll');
      const uncheckAllButton = document.getElementById('uncheckAll');
      const userId = <?php echo json_encode($user_id); ?>;

      function updatePermissions(checkboxes, action) {
        const updates = [];
        checkboxes.forEach(checkbox => {
          // For user ID 1, don't uncheck already checked boxes
          if (userId == 1 && action === 'uncheck' && checkbox.checked) {
            // Skip this checkbox - don't uncheck it
            return;
          }

          checkbox.checked = action === 'check';
          updates.push(updatePermission(checkbox));
        });

        if (updates.length === 0) {
          if (userId == 1 && action === 'uncheck') {
            alert("Permission can't be revoked or unchecked for this user");
          }
          return;
        }

        Promise.all(updates)
          .then(results => {
            const successCount = results.filter(r => r.success).length;
            const failCount = results.length - successCount;
            if (failCount === 0) {
              alert(`All permissions ${action === 'check' ? 'granted' : 'revoked'} successfully.`);
            } else {
              alert(`${successCount} permissions ${action === 'check' ? 'granted' : 'revoked'} successfully. ${failCount} updates failed.`);
            }
          })
          .catch(error => {
            console.error('Error updating permissions:', error);
            alert('An error occurred while updating permissions.');
          });
      }

      checkAllButton.addEventListener('click', () => updatePermissions(checkboxes, 'check'));
      uncheckAllButton.addEventListener('click', () => updatePermissions(checkboxes, 'uncheck'));

      function updatePermission(checkbox) {
        const submenu = checkbox.value;
        const menuName = <?php echo json_encode($menu); ?>;
        const hasAccess = checkbox.checked ? 1 : 0;

        console.log(`Updating: submenu=${submenu}, user_id=${userId}, menu=${menuName}, has_access=${hasAccess}`);

        return fetch('update_submenu_permission.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `submenu=${encodeURIComponent(submenu)}&user_id=${userId}&menu=${encodeURIComponent(menuName)}&has_access=${hasAccess}`
        })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.json();
        })
        .then(data => {
          if (data.success) {
            console.log(`Success: ${submenu} permission updated`);
            return { success: true };
          } else {
            console.error('Server error:', data.error);
            return { success: false, error: data.error || 'Unknown error' };
          }
        })
        .catch(error => {
          console.error('Fetch error:', error);
          return { success: false, error: error.message };
        });
      }

      checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
          // Check if this is user ID 1 and trying to uncheck a permission
          if (userId == 1 && this.checked === false) {
            // Prevent unchecking by setting back to checked
            this.checked = true;
            alert("Permission can't be revoked or unchecked for this user");
            return;
          }

          this.disabled = true;
          updatePermission(this)
            .then(result => {
              this.disabled = false;
              if (result.success) {
                alert(`Permission ${this.checked ? 'granted' : 'revoked'} for ${this.value}`);
              } else {
                alert('Failed to update permission: ' + (result.error || 'Unknown error'));
                this.checked = !this.checked; // Revert the checkbox state
              }
            });
        });
      });
    });
    </script>
</body>
</html>
