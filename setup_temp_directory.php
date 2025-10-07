<?php
// Create temp_pdfs directory if it doesn't exist
$temp_dir = __DIR__ . '/temp_pdfs';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
    echo "Created temp_pdfs directory successfully!\n";
} else {
    echo "temp_pdfs directory already exists.\n";
}

// Create .htaccess file to allow PDF downloads
$htaccess_content = "Options +Indexes
<Files *.pdf>
    Header set Content-Disposition attachment
    Header set Content-Type application/pdf
</Files>

# Allow access to PDF files
<FilesMatch \"\.(pdf)$\">
    Order allow,deny
    Allow from all
</FilesMatch>";

file_put_contents($temp_dir . '/.htaccess', $htaccess_content);
echo "Created .htaccess file for PDF access.\n";
echo "Setup complete! You can now use the Email Invoice feature.\n";
?>
