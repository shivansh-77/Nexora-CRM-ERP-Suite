<?php
// Fetch contact details from the database
include('connection.php');

// Validate and sanitize the ID from GET
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Use prepared statements to prevent SQL injection
    $stmt = $connection->prepare("
        SELECT contact_person, company_name, mobile_no, whatsapp_no,
               email_id, address, country, state, city, pincode,
               reference_pname, reference_pname_no, estimate_amnt,
               followupdate, remarks, employee
        FROM contact WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $contact = $result->fetch_assoc();
?>
<?php
include('connection.php'); // Include database connection file

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form inputs
    $contact_id = $_POST['contact_id']; // Ensure this is provided in the form
    $lead_source = $_POST['lead_source'];
    $lead_for = $_POST['lead_for'];
    $lead_priority = $_POST['lead_priority'];

    $sql_fy = "SELECT fy_code FROM financial_years WHERE is_current = 1";
    $result_fy = $connection->query($sql_fy);

    if ($result_fy && $result_fy->num_rows > 0) {
        $row_fy = $result_fy->fetch_assoc();
        $fy_code = $row_fy['fy_code']; // Get the fy_code of the current financial year
    } else {
        die("No current financial year found.");
    }

    // Prepare the SQL query to insert into the followup table
    $sql = "INSERT INTO followup (contact_id, lead_source, lead_for, lead_priority, fy_code) VALUES (?, ?, ?, ?, ?)";

    // Prepare the statement
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        die("Preparation failed: " . $connection->error);
    }

    // Bind the parameters
    $stmt->bind_param("issss", $contact_id, $lead_source, $lead_for, $lead_priority, $fy_code);

    // Execute the query
    if ($stmt->execute()) {
        echo "<script>alert('Follow-up entry added successfully!'); window.location.href = 'followup_display.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $connection->close();
}
?>
<style>

body {
    background-color: #2c3e50;
    color: #2c3e50;
    font-family: Arial, sans-serif;
}

.header-card {
    width: 80%;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header-card h2 {
    margin: 0 auto; /* Centers horizontally */
    text-align: center; /* Centers text inside h2 */
    line-height: 1.5; /* Adjust for vertical spacing within the h2 */
    color: #2c3e50; /* Optional: Keep your current color */
    padding-bottom: 20px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.details-grid div {
    background-color: white;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.details-grid strong {
    color: #2c3e50;
}

.new-followup-card {
    width: 80%;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.new-followup-card h2 {
    margin: 0 auto;
    text-align: center;
    line-height: 1.5;
    color: #2c3e50;
    padding-bottom: 20px;
}

.new-followup-card form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.new-followup-card label {
    color: #2c3e50;
    font-weight: bold;
}

.new-followup-card input,
.new-followup-card button {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.new-followup-card button {
    background-color: #2c3e50;
    color: #fff;
    cursor: pointer;
    transition: background-color 0.3s;
}

.new-followup-card button:hover {
    background-color: #1a252f;
}
.new-followup-card form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.details-grid div {
    display: flex;
    flex-direction: column; /* Stack label above input field */
}

.new-followup-card label {
    color: #2c3e50;
    font-weight: bold;
    margin-bottom: 5px; /* Add some space below the label */
}

.new-followup-card select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    width: 100%; /* Make the select field full width */
}

.new-followup-card button {
    background-color: #2c3e50;
    color: #fff;
    cursor: pointer;
    transition: background-color 0.3s;
    padding: 5px 10px; /* Adjust padding for a smaller button */
    width: fit-content; /* Button size based on content */
}

.new-followup-card button:hover {
    background-color: #1a252f;
}
.close-btn{
  font-size: 18px;
  margin-left: 1000px;
  bottom: 30px;

}
</style>
<div class="new-followup-card">
  <a style="text-decoration:None;"href="contact_display.php" class="close-btn">&times;</a>
    <h2>Add New Followup</h2>
    <form method="POST" action="">
        <input type="hidden" name="contact_id" value="<?= $id; ?>" required>

        <div class="details-grid">
            <div>
                <label for="lead_source">Lead Source:</label>
                <select name="lead_source" id="lead_source" required>
                    <option value="">Select Lead Source</option>
                    <?php
                    // Fetch all entries from the lead_source table
                    $query = "SELECT name FROM lead_sourc";
                    $result = mysqli_query($connection, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label for="lead_for">Lead For:</label>
                <select name="lead_for" id="lead_for" required>
                    <option value="">Select Lead For</option>
                    <?php
                    // Fetch all entries from the lead_for table
                    $query = "SELECT name FROM lead_for";
                    $result = mysqli_query($connection, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label for="lead_priority">Lead Priority:</label>
                <select name="lead_priority" id="lead_priority" required>
                    <option value="">Select Priority</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>
        </div>

        <button type="submit" style="margin-top: 10px; margin-left: 435px; background-color: #2c3e50; color: white; padding: 10px 10px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s;">Add Follow-Up</button>
    </form>
</div>
<div class="header-card">
    <h2>Contact Details</h2>
    <div class="details-grid">
        <div><strong>Contact Person:</strong> <?= htmlspecialchars($contact['contact_person']); ?></div>
        <div><strong>Company Name:</strong> <?= htmlspecialchars($contact['company_name']); ?></div>
        <div><strong>Mobile No:</strong> <?= htmlspecialchars($contact['mobile_no']); ?></div>
        <div><strong>WhatsApp No:</strong> <?= htmlspecialchars($contact['whatsapp_no']); ?></div>
        <div><strong>Email ID:</strong> <?= htmlspecialchars($contact['email_id']); ?></div>
        <div><strong>Address:</strong> <?= htmlspecialchars($contact['address']); ?></div>
        <div><strong>Country:</strong> <?= htmlspecialchars($contact['country']); ?></div>
        <div><strong>State:</strong> <?= htmlspecialchars($contact['state']); ?></div>
        <div><strong>City:</strong> <?= htmlspecialchars($contact['city']); ?></div>
        <div><strong>Pincode:</strong> <?= htmlspecialchars($contact['pincode']); ?></div>
        <div><strong>Reference Person:</strong> <?= htmlspecialchars($contact['reference_pname']); ?></div>
        <div><strong>Reference Person No:</strong> <?= htmlspecialchars($contact['reference_pname_no']); ?></div>
        <div><strong>Estimate Amount:</strong> <?= htmlspecialchars($contact['estimate_amnt']); ?></div>
        <div><strong>Follow-up Date:</strong> <?= htmlspecialchars($contact['followupdate']); ?></div>
        <div><strong>Remarks:</strong> <?= htmlspecialchars($contact['remarks']); ?></div>
        <div><strong>Employee:</strong> <?= htmlspecialchars($contact['employee']); ?></div>
    </div>
</div>


<?php
    } else {
        echo "<div class='header-card'><p>No contact details found for the given ID.</p></div>";
    }
    $stmt->close();
} else {
    echo "<div class='header-card'><p>Invalid or missing contact ID.</p></div>";
}
?>
