<?php
include('connection.php');
header('Content-Type: text/html');

$location = $_GET['location'] ?? '';

if ($location) {
    $query = "SELECT item_name, item_code
              FROM item
              WHERE block = 0
              AND item_type = 'inventory'
              AND location = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $location);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    echo '<div class="filter-group">
          <label for="items">Items</label>
          <select name="items[]" id="items" multiple size="5" required>';

    while ($row = mysqli_fetch_assoc($result)) {
        echo '<option value="'.htmlspecialchars($row['item_code']).'">'
            .htmlspecialchars($row['item_name']).' ('.htmlspecialchars($row['item_code']).')'
            .'</option>';
    }

    echo '</select></div>';
}
?>
