<?php
error_reporting(E_ALL); // Report all PHP errors
ini_set('display_errors', 1); // Display errors to the browser
session_start(); // Start the session
include("connection.php"); // Include the database connection file

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $pwd = $_POST['password'];

    // Step 1: Fetch the user's hashed password from the database
    $stmt = $connection->prepare("SELECT id, email, name, role, password FROM login_db WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['password'];

        // Step 2: Verify the user-provided password against the hashed password
        if (password_verify($pwd, $hashed_password)) {
            // Password is correct, proceed with login
            $_SESSION['user_id'] = $row['id']; // Store user ID in session
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $row['role'];

            // Function to fetch all permissions from the relevant tables
            function get_all_permissions($connection) {
                $all_permissions = [];
                $query = "SELECT DISTINCT fy_code FROM emp_fy_permission";
                $result = mysqli_query($connection, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    $all_permissions[] = $row['fy_code'];
                }
                return $all_permissions;
            }

            // Function to fetch all menu and submenu permissions
            function get_all_menu_permissions($connection) {
                $all_menu_permissions = [];
                $query = "SELECT DISTINCT menu_name, submenu_name FROM user_menu_permission";
                $result = mysqli_query($connection, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    $all_menu_permissions[$row['menu_name']][] = $row['submenu_name'];
                }
                return $all_menu_permissions;
            }

            // Check if the user is the super admin or has the Admin role
            if ($row['id'] == 1 || $row['role'] == "Admin") {
                // Grant all permissions
                $allowed_fy_codes = get_all_permissions($connection);
                $allowed_submenus = get_all_menu_permissions($connection);
            } else {
                // Step 3: Retrieve FY Codes with Permission = 1
                $user_id = $row['id'];
                $permission_stmt = $connection->prepare("SELECT fy_code FROM emp_fy_permission WHERE emp_id = ? AND permission = 1");
                $permission_stmt->bind_param("i", $user_id);
                $permission_stmt->execute();
                $permission_result = $permission_stmt->get_result();

                // Fetch all allowed fy_code into an array
                $allowed_fy_codes = [];
                while ($perm_row = $permission_result->fetch_assoc()) {
                    $allowed_fy_codes[] = $perm_row['fy_code'];
                }

                // Step 4: Retrieve Allowed Submenus for the User
                $submenu_stmt = $connection->prepare("SELECT menu_name, submenu_name FROM user_menu_permission WHERE user_id = ?");
                $submenu_stmt->bind_param("i", $user_id);
                $submenu_stmt->execute();
                $submenu_result = $submenu_stmt->get_result();

                // Fetch all allowed submenus into an associative array
                $allowed_submenus = [];
                while ($submenu_row = $submenu_result->fetch_assoc()) {
                    $allowed_submenus[$submenu_row['menu_name']][] = $submenu_row['submenu_name'];
                }
            }

            // Store allowed fy_codes and submenus in session
            $_SESSION['allowed_fy_codes'] = $allowed_fy_codes;
            $_SESSION['allowed_submenus'] = $allowed_submenus;

            // Debug: Output session data
            echo '<pre>';
            print_r($_SESSION);
            echo '</pre>';

            // Redirect based on user role
            if ($row['role'] == "Admin") {
                header('Location: index.php');
            } else {
                header('Location: user_dashboard.php');
            }
            exit();
        } else {
            // Password is incorrect
            echo "<script>alert('Login Failed! Please check your credentials.');</script>";
        }
    } else {
        // User not found
        echo "<script>alert('Login Failed! Please check your credentials.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="login-style.css">
    <title>Login Form</title>
</head>
<body>
    <form action="" method="POST">
        <div class="center">
            <div class="logo-container">
                <?php
                // Fetch company logo from database
                $query = "SELECT company_logo FROM company_card WHERE id = 1"; // Change `1` to the correct company ID
                $result = mysqli_query($connection, $query);
                $company = mysqli_fetch_assoc($result);

                // Set logo path (fallback to default if not available)
                $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
                ?>

                <!-- Display Dynamic Logo -->
                <img src="<?php echo $company_logo; ?>" alt="Logo" class="logo" />
            </div>
            <h1>Login</h1>
            <div class="form">
                <div class="input-container">
                    <i class="icon user-icon"></i>
                    <input type="text" name="username" class="textfield" placeholder="Username" required>
                </div>
                <div class="input-container">
                    <i class="icon password-icon"></i>
                    <input type="password" name="password" class="textfield" placeholder="Password" required>
                </div>

                <div class="forgetpass">
                    <a href="forgot_password.php" class="link" onclick="message(event)">Forgot Password?</a>
                </div>

                <input type="submit" name="login" value="Login" class="btn">
            </div>
        </div>
    </form>

    <script type="text/javascript">
        function message(event) {
            // Display the confirmation dialog
            var result = confirm("Can't Remember your password, Reset your password with your Email Id !?");

            // If the user clicks "Cancel", prevent the default action (navigation)
            if (!result) {
                event.preventDefault();  // This prevents the link from navigating to the forgot_password.php page
            }
        }
    </script>
</body>
</html>
