<?php
include('connection.php');
?>

<html>
<head>
  <title>Display</title>
  <style media="screen">
    body {
      background-color: #D071f9;
    }
    table {
      background-color: white;
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 10px;
      text-align: left;
      border: 1px solid #ddd;
    }
    th {
      background-color: #f2f2f2;
    }
    a {
      text-decoration: none;
      color: #007bff;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
<?php
$query = "SELECT * FROM login_db";
$result = mysqli_query($connection, $query);

if ($result) {
    $total = mysqli_num_rows($result);
    echo "Total records: $total<br>";
?>
<table>
  <thead>
    <tr>
      <th>fname</th>
      <th>lname</th>
      <th>gender</th>
      <th>email</th>
      <th>phone</th>
      <th>password</th>
      <th>role</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
<?php
    if ($total != 0) {
        while ($array = mysqli_fetch_assoc($result)) {
            echo "<tr>
              <td>" . htmlspecialchars($array['fname'], ENT_QUOTES, 'UTF-8') . "</td>
              <td>" . htmlspecialchars($array['lname'], ENT_QUOTES, 'UTF-8') . "</td>
              <td>" . htmlspecialchars($array['gender'], ENT_QUOTES, 'UTF-8') . "</td>
              <td>" . htmlspecialchars($array['email'], ENT_QUOTES, 'UTF-8') . "</td>
              <td>" . htmlspecialchars($array['phone'], ENT_QUOTES, 'UTF-8') . "</td>
              <td>" . htmlspecialchars($array['password'], ENT_QUOTES, 'UTF-8') . "</td>
              <td>" . htmlspecialchars($array['role'], ENT_QUOTES, 'UTF-8') . "</td>
              <td>
                <a href='update_form.php?id=" . htmlspecialchars($array['id'], ENT_QUOTES, 'UTF-8') . "'>Edit</a> |
                <a href='delete_form.php?id=" . htmlspecialchars($array['id'], ENT_QUOTES, 'UTF-8') . "' onclick=\"return confirm('Are you sure you want to delete this record?');\">Delete</a>
              </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='8'>No records found</td></tr>";
    }
} else {
    echo "Error executing query: " . mysqli_error($connection);
}
?>
  </tbody>
</table>
<?php
mysqli_close($connection); // Close the connection
?>
</body>
</html>
