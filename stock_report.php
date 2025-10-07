<?php
session_start();
include('connection.php');
include('topbar.php');

// Fetch locations for dropdown
$locations = [];
$location_query = "SELECT DISTINCT location_code FROM location_card ORDER BY location_code";
$location_result = mysqli_query($connection, $location_query);
while ($row = mysqli_fetch_assoc($location_result)) {
    $locations[] = $row['location_code'];
}

// Fetch all item categories
$categories = [];
$category_query = "SELECT DISTINCT code FROM item_category ORDER BY code";
$category_result = mysqli_query($connection, $category_query);
while ($row = mysqli_fetch_assoc($category_result)) {
    $categories[] = $row['code'];
}

// Fetch all items for dropdown
$items = [];
$item_query = "SELECT item_name, item_code, unit_of_measurement_code, item_category
               FROM item
               WHERE block = 0 AND item_type = 'inventory'
               ORDER BY item_name";
$item_result = mysqli_query($connection, $item_query);
while ($row = mysqli_fetch_assoc($item_result)) {
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>Stock Report Generator</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            overflow-x: hidden;
        }

        .filter-container {
            width: calc(100% - 260px);
            margin-left: 260px;
            margin-top: 140px;
            padding: 40px;
            box-sizing: border-box;
            min-height: calc(100vh - 140px);
            overflow-y: auto;
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

        .filter-form {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: 0 auto;
        }

        .form-title {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: bold;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
        }

        .filter-group select, .filter-group input {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .filter-group select:focus, .filter-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .filter-group select[multiple] {
            height: 120px;
        }

        .btn-generate {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .btn-generate:hover {
            background-color: #c0392b;
        }

        .error-messages {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .required {
            color: #e74c3c;
        }

        .help-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        .custom-multiselect {
            position: relative;
            width: 100%;
        }

        .multiselect-input {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: border-color 0.3s;
            min-height: 20px;
        }

        .multiselect-input:hover {
            border-color: #3498db;
        }

        .multiselect-input.active {
            border-color: #3498db;
        }

        .multiselect-input.disabled {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
            border-color: #ddd;
        }

        .dropdown-arrow {
            transition: transform 0.3s;
            color: #666;
        }

        .dropdown-arrow.rotated {
            transform: rotate(180deg);
        }

        .multiselect-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #3498db;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-container {
            padding: 10px;
            border-bottom: 1px solid #eee;
            background-color: #f8f9fa;
        }

        .search-container input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .search-container input:focus {
            outline: none;
            border-color: #3498db;
        }

        .items-container {
            max-height: 180px;
            overflow-y: auto;
        }

        .item-option {
            display: flex;
            align-items: center;
            padding: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-option:hover {
            background-color: #f8f9fa;
        }

        .item-option:last-child {
            border-bottom: none;
        }

        .item-option input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 3px;
            margin-right: 10px;
            position: relative;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .item-option input[type="checkbox"]:checked + .checkmark {
            background-color: #3498db;
            border-color: #3498db;
        }

        .item-option input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: -2px;
            left: 2px;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }

        .item-text {
            flex: 1;
            font-size: 14px;
            color: #333;
        }

        .selected-count {
            background-color: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }

        .category-info {
            background-color: #e8f4fd;
            color: #2980b9;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 5px;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Stock Report Generator</h2>
    </div>

    <div class="filter-container">
        <div class="filter-form">
            <h2 class="form-title">Generate Stock Report</h2>

            <form method="POST" action="report_generate.php" id="reportForm">
                <!-- First Row: Items and Location -->
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Items <span class="required">*</span>:</label>
                        <div class="custom-multiselect" id="itemsMultiselect">
                            <div class="multiselect-input" onclick="toggleDropdown()" id="multiselectInput">
                                <span id="selectedItemsText">Select Items...</span>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="multiselect-dropdown" id="itemsDropdown">
                                <div class="search-container">
                                    <input type="text" id="itemSearch" placeholder="Search items..." onkeyup="filterItems()">
                                </div>
                                <div class="items-container" id="itemsContainer">
                                    <?php foreach ($items as $item): ?>
                                        <label class="item-option"
                                               data-name="<?php echo strtolower(htmlspecialchars($item['item_name'])); ?>"
                                               data-category="<?php echo htmlspecialchars($item['item_category']); ?>">
                                            <input type="checkbox" name="items[]" value="<?php echo htmlspecialchars($item['item_code']); ?>">
                                            <span class="checkmark"></span>
                                            <span class="item-text"><?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ')'); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="help-text" id="itemsHelpText">Search and select multiple items</div>
                        <div class="category-info" id="categoryInfo" style="display: none;">
                            Items auto-selected based on category. Clear category to manually select items.
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Location <span class="required">*</span>:</label>
                        <select name="location" id="locationSelect" required>
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location); ?>">
                                    <?php echo htmlspecialchars($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Choose the location for stock report</div>
                    </div>
                </div>

                <!-- Second Row: Start Date and End Date -->
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Start Date <span class="required">*</span>:</label>
                        <input type="date" name="start_date" id="startDate" required>
                        <div class="help-text">Report period start date</div>
                    </div>

                    <div class="filter-group">
                        <label>End Date <span class="required">*</span>:</label>
                        <input type="date" name="end_date" id="endDate" required>
                        <div class="help-text">Report period end date</div>
                    </div>
                </div>

                <!-- Third Row: Item Category -->
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Item Category:</label>
                        <select name="item_category" id="categorySelect" onchange="handleCategoryChange()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Optional: Select category to auto-select all items</div>
                    </div>

                    <div class="filter-group">
                        <!-- Empty div to maintain equal spacing -->
                    </div>
                </div>

                <button type="submit" class="btn-generate">📊 Generate Report</button>
            </form>
        </div>
    </div>

    <script>
        let isDropdownOpen = false;
        let isCategoryMode = false;

        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');

            // Date validation
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
                if (endDate.value && endDate.value < this.value) {
                    endDate.value = this.value;
                }
            });

            endDate.addEventListener('change', function() {
                if (startDate.value && this.value < startDate.value) {
                    alert('End date must be greater than or equal to start date');
                    this.value = startDate.value;
                }
            });

            // Add event listeners to checkboxes
            const checkboxes = document.querySelectorAll('input[name="items[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedText);
            });

            // Form validation
            document.getElementById('reportForm').addEventListener('submit', function(e) {
                const location = document.getElementById('locationSelect').value;
                const checkedItems = document.querySelectorAll('input[name="items[]"]:checked');
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;

                if (!location) {
                    alert('Please select a location');
                    e.preventDefault();
                    return;
                }

                if (checkedItems.length === 0) {
                    alert('Please select at least one item or choose a category');
                    e.preventDefault();
                    return;
                }

                if (!startDate) {
                    alert('Please select start date');
                    e.preventDefault();
                    return;
                }

                if (!endDate) {
                    alert('Please select end date');
                    e.preventDefault();
                    return;
                }

                if (endDate < startDate) {
                    alert('End date must be greater than or equal to start date');
                    e.preventDefault();
                    return;
                }
            });
        });

        function handleCategoryChange() {
            const categorySelect = document.getElementById('categorySelect');
            const selectedCategory = categorySelect.value;
            const locationSelect = document.getElementById('locationSelect');
            const selectedLocation = locationSelect.value;
            const multiselectInput = document.getElementById('multiselectInput');
            const categoryInfo = document.getElementById('categoryInfo');
            const itemsHelpText = document.getElementById('itemsHelpText');

            // Clear all current selections
            const checkboxes = document.querySelectorAll('input[name="items[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });

            if (selectedCategory) {
                // Category mode - disable manual selection
                isCategoryMode = true;
                multiselectInput.classList.add('disabled');
                categoryInfo.style.display = 'block';
                itemsHelpText.style.display = 'none';

                if (selectedLocation) {
                    // Auto-select items from the selected category and location
                    selectItemsByCategory(selectedCategory, selectedLocation);
                } else {
                    // Show message to select location first
                    document.getElementById('selectedItemsText').innerHTML = 'Please select location first';
                }
            } else {
                // Manual mode - enable manual selection
                isCategoryMode = false;
                multiselectInput.classList.remove('disabled');
                categoryInfo.style.display = 'none';
                itemsHelpText.style.display = 'block';
                updateSelectedText();
            }
        }

        function selectItemsByCategory(category, location) {
            // This would normally require an AJAX call to get filtered items
            // For now, we'll select all items with matching category
            const itemOptions = document.querySelectorAll('.item-option');
            let selectedCount = 0;

            itemOptions.forEach(option => {
                const itemCategory = option.getAttribute('data-category');
                const checkbox = option.querySelector('input[type="checkbox"]');

                if (itemCategory === category) {
                    checkbox.checked = true;
                    selectedCount++;
                }
            });

            updateSelectedText();
        }

        // Handle location change to update category-based selection
        document.getElementById('locationSelect').addEventListener('change', function() {
            const categorySelect = document.getElementById('categorySelect');
            if (categorySelect.value) {
                handleCategoryChange();
            }
        });

        function toggleDropdown() {
            if (isCategoryMode) {
                return; // Don't allow dropdown in category mode
            }

            const dropdown = document.getElementById('itemsDropdown');
            const input = document.querySelector('.multiselect-input');
            const arrow = document.querySelector('.dropdown-arrow');

            if (isDropdownOpen) {
                dropdown.style.display = 'none';
                input.classList.remove('active');
                arrow.classList.remove('rotated');
                isDropdownOpen = false;
            } else {
                dropdown.style.display = 'block';
                input.classList.add('active');
                arrow.classList.add('rotated');
                isDropdownOpen = true;
                document.getElementById('itemSearch').focus();
            }
        }

        function filterItems() {
            const searchTerm = document.getElementById('itemSearch').value.toLowerCase();
            const items = document.querySelectorAll('.item-option');
            const container = document.getElementById('itemsContainer');

            // Convert NodeList to Array and sort
            const itemsArray = Array.from(items);

            // Filter and sort items
            itemsArray.forEach(item => {
                const itemName = item.getAttribute('data-name');
                if (itemName.includes(searchTerm)) {
                    item.style.display = 'flex';
                    // Move matching items to top
                    if (searchTerm && itemName.startsWith(searchTerm)) {
                        container.insertBefore(item, container.firstChild);
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function updateSelectedText() {
            const checkboxes = document.querySelectorAll('input[name="items[]"]:checked');
            const selectedText = document.getElementById('selectedItemsText');

            if (checkboxes.length === 0) {
                selectedText.textContent = 'Select Items...';
                selectedText.style.color = '#999';
            } else if (checkboxes.length === 1) {
                const itemText = checkboxes[0].parentElement.querySelector('.item-text').textContent;
                selectedText.innerHTML = itemText + ' <span class="selected-count">1</span>';
                selectedText.style.color = '#333';
            } else {
                selectedText.innerHTML = `${checkboxes.length} items selected <span class="selected-count">${checkboxes.length}</span>`;
                selectedText.style.color = '#333';
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const multiselect = document.getElementById('itemsMultiselect');
            if (!multiselect.contains(event.target) && isDropdownOpen && !isCategoryMode) {
                toggleDropdown();
            }
        });
    </script>
</body>
</html>
