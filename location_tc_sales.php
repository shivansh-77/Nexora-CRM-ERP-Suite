<?php
session_start();
include('connection.php');

// Fetch location data based on location_id and location_code from the URL
$location_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$location_code = isset($_GET['location_code']) ? $_GET['location_code'] : '';

// Fetch location details
$location_query = "SELECT * FROM location_card WHERE id = $location_id";
$location_result = mysqli_query($connection, $location_query);
$location_data = mysqli_fetch_assoc($location_result);

// Fetch existing terms and conditions
$tc_query = "SELECT * FROM location_tc WHERE location_id = $location_id AND location_code = '$location_code' AND tc_type = 'Sales'";
$tc_result = mysqli_query($connection, $tc_query);
$tc_data = mysqli_fetch_assoc($tc_result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $terms_conditions = $_POST['terms_conditions'];

    if ($tc_data) {
        // Update existing record
        $update_query = "UPDATE location_tc SET terms_conditions = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, 'si', $terms_conditions, $tc_data['id']);
    } else {
        // Insert new record
        $insert_query = "INSERT INTO location_tc (location_id, location_code, tc_type, terms_conditions) VALUES (?, ?, 'Sales', ?)";
        $stmt = mysqli_prepare($connection, $insert_query);
        mysqli_stmt_bind_param($stmt, 'iss', $location_id, $location_code, $terms_conditions);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: location_tc_sales.php?id=$location_id&location_code=$location_code");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Location Terms and Conditions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
           :root {
               --primary-color: #3498db;
               --secondary-color: #2c3e50;
               --accent-color: #e74c3c;
               --light-bg: #f8f9fa;
               --dark-bg: #343a40;
           }

           body {
               font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
               background-color: #f5f5f5;
               color: #333;
               background-color: #2c3e50;
           }

           .container {
               max-width: 1200px;
               margin: 30px auto;
               background: white;
               border-radius: 10px;
               box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
               padding: 30px;
               position: relative; /* Ensure positioning context for absolute elements */
           }

           .header {
               border-bottom: 2px solid var(--primary-color);
               padding-bottom: 15px;
               margin-bottom: 25px;
               display: flex;
               justify-content: space-between;
               align-items: center;
           }

           .location-card {
               background: var(--light-bg);
               border-radius: 8px;
               padding: 20px;
               margin-bottom: 25px;
               box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
           }

           .location-card h3 {
               color: var(--secondary-color);
               border-bottom: 1px solid #ddd;
               padding-bottom: 10px;
           }

           .editor-container {
               border: 1px solid #ddd;
               border-radius: 8px;
               overflow: hidden;
           }

           .editor-toolbar {
               background: var(--light-bg);
               padding: 10px;
               border-bottom: 1px solid #ddd;
               display: flex;
               flex-wrap: wrap;
               gap: 5px;
           }

           .editor-toolbar .btn-group {
               margin-right: 5px;
           }

           .editor-toolbar button {
               background: white;
               border: 1px solid #ddd;
               border-radius: 4px;
               padding: 5px 10px;
               cursor: pointer;
               transition: all 0.2s;
           }

           .editor-toolbar button:hover {
               background: #e9ecef;
           }

           .editor-toolbar button.active {
               background: var(--primary-color);
               color: white;
           }

           .ql-container {
               min-height: 300px;
               border: none !important;
           }

           .ql-editor {
               padding: 15px;
               font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
               font-size: 14px;
               line-height: 1.6;
           }

           .save-btn {
               background: var(--primary-color);
               color: white;
               padding: 10px 25px;
               border: none;
               border-radius: 5px;
               font-weight: bold;
               margin-top: 15px;
               transition: all 0.2s;
           }

           .save-btn:hover {
               background: #2980b9;
               transform: translateY(-2px);
           }

           #saveAlert {
               color: var(--accent-color);
               font-weight: bold;
               margin-left: 10px;
               display: none;
           }

           .format-select {
               padding: 5px;
               border-radius: 4px;
               border: 1px solid #ddd;
               margin-right: 5px;
           }

           .color-picker {
               width: 30px;
               height: 30px;
               padding: 0;
               border: 1px solid #ddd;
               vertical-align: middle;
           }

           .back-btn {
               font-size: 1rem;
               color: #333;
               text-decoration: none;
               position: absolute;
               top: 10px;
               right: 10px;
               display: flex;
               align-items: center;
           }

           .back-btn span {
               margin-right: 5px;
           }
           .back-btn {
      font-size: 1rem;
      color: white;
      background-color: #e74c3c; /* Red color */
      border: none;
      border-radius: 5px;
      padding: 8px 16px;
      cursor: pointer;
      position: absolute;
      top: 10px;
      right: 10px;
      display: flex;
      align-items: center;
      transition: background-color 0.3s;
  }

  .back-btn:hover {
      background-color: #c0392b; /* Darker red on hover */
  }

  .back-btn span {
      margin-right: 5px;
  }


           @media (max-width: 768px) {
               .container {
                   padding: 15px;
               }

               .editor-toolbar {
                   flex-direction: column;
                   align-items: flex-start;
               }

               .btn-group {
                   margin-bottom: 5px;
               }
           }
       </style>
   </head>
   <body>
       <div class="container">
           <div class="header">
               <div>
                   <h1 class="text-primary">Terms and Conditions Editor</h1>
                   <p class="text-muted">Edit sales terms and conditions for <?php echo htmlspecialchars($location_data['location'] ?? 'this location'); ?></p>
               </div>
               <a href="locationcard_display.php" class="btn btn-link text-decoration-none back-btn" aria-label="Back">
                   <span</span> Back
               </a>
           </div>

    <div class="row">
        <div class="col-md-6">
            <div class="location-card">
                <h3><i class="fas fa-building"></i> Location Details</h3>
                <p><strong><i class="fas fa-industry"></i> Company Name:</strong> <?php echo htmlspecialchars($location_data['company_name'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-map-marker-alt"></i> Location:</strong> <?php echo htmlspecialchars($location_data['location'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-code"></i> Location Code:</strong> <?php echo htmlspecialchars($location_data['location_code'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-city"></i> City:</strong> <?php echo htmlspecialchars($location_data['city'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-map"></i> State:</strong> <?php echo htmlspecialchars($location_data['state'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-globe"></i> Country:</strong> <?php echo htmlspecialchars($location_data['country'] ?? 'N/A'); ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="location-card">
                <h3><i class="fas fa-info-circle"></i> Contact Information</h3>
                <p><strong><i class="fas fa-map-pin"></i> Pin Code:</strong> <?php echo htmlspecialchars($location_data['pincode'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-phone"></i> Contact Number:</strong> <?php echo htmlspecialchars($location_data['contact_no'] ?? 'N/A'); ?></p>
                <p><strong><i class="fab fa-whatsapp"></i> WhatsApp Number:</strong> <?php echo htmlspecialchars($location_data['whatsapp_no'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-envelope"></i> Email ID:</strong> <?php echo htmlspecialchars($location_data['email_id'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-file-invoice-dollar"></i> GST No:</strong> <?php echo htmlspecialchars($location_data['gstno'] ?? 'N/A'); ?></p>
                <p><strong><i class="fas fa-id-card"></i> Registration No:</strong> <?php echo htmlspecialchars($location_data['registration_no'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>

    <div class="editor-container mt-4">
        <h3 class="mb-3"><i class="fas fa-file-contract"></i> Sales Terms and Conditions</h3>

        <!-- Quill Editor will be inserted here -->
        <div id="editor"><?php echo isset($tc_data['terms_conditions']) ? $tc_data['terms_conditions'] : ''; ?></div>

        <form id="tcForm" method="post" action="">
            <input type="hidden" name="terms_conditions" id="hidden-terms">
            <button type="button" id="saveButton" class="save-btn">
                <i class="fas fa-save"></i> Save Changes
                <span id="saveAlert" style="display: none;"><i class="fas fa-exclamation-circle"></i> Unsaved Changes</span>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Initialize Quill editor
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });

    const saveButton = document.getElementById('saveButton');
    const saveAlert = document.getElementById('saveAlert');
    let isChanged = false;

    // Set initial content if it exists
    <?php if (isset($tc_data['terms_conditions'])): ?>
        quill.clipboard.dangerouslyPasteHTML(<?php echo json_encode($tc_data['terms_conditions']); ?>);
    <?php endif; ?>

    // Track changes in the editor
    quill.on('text-change', function() {
        if (!isChanged) {
            isChanged = true;
            saveAlert.style.display = 'inline';
        }
    });

    // Handle save button click
    saveButton.addEventListener('click', () => {
        if (isChanged) {
            // Get the HTML content and remove <p> tags
            let termsContent = quill.root.innerHTML;
            termsContent = termsContent.replace(/<p>/g, '').replace(/<\/p>/g, '<br>');
            document.getElementById('hidden-terms').value = termsContent;
            document.getElementById('tcForm').submit();
        } else {
            alert('No changes detected to save.');
        }
    });
</script>
</body>
</html>
