<?php
// export.php

require_once __DIR__ . '/includes/functions.php';

// Ensure session is started
startSessionIfNotStarted();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
    exit;
}

// Function to safely encode data for XML
function encodeCellData($value) {
    // Replace null with empty string and then encode
    return htmlspecialchars($value ?? '', ENT_XML1);
}

try {
    // Fetch all releases from the database
    $sql = "SELECT * FROM release_cheatsheets ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);

    if ($stmt->rowCount() > 0) {
        $releases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- Start Generating Excel File ---

        $filename = "releases_" . date('Y-m-d') . ".xls";
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Start XML structure
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
                xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        echo '<Worksheet ss:Name="All Releases">';
        echo '<Table>';

        // Get original headers from the first row of data
        $headers = array_keys($releases[0]);
        // Add the new custom header
        $headers[] = 'CSC Check';

        // Write header row
        echo '<Row>';
        foreach ($headers as $header) {
            echo '<Cell><Data ss:Type="String">' . encodeCellData($header) . '</Data></Cell>';
        }
        echo '</Row>';

        // Write data rows
        foreach ($releases as $row) {
            echo '<Row>';
            
            // Write original data cells
            foreach ($row as $value) {
                $type = is_numeric($value) ? 'Number' : 'String';
                echo '<Cell><Data ss:Type="' . $type . '">' . encodeCellData($value) . '</Data></Cell>';
            }

            // --- CSC Check Logic ---
            $cscValue = $row['csc'] ?? '';
            $cscCheckResult = 'Not OK'; // Default value
            
            // Case-insensitive check for OXM, OLM, or OXT
            if (stripos($cscValue, 'OXM') !== false || stripos($cscValue, 'OLM') !== false || stripos($cscValue, 'OXT') !== false) {
                $cscCheckResult = 'OK';
            }
            
            // Write the new CSC Check cell
            echo '<Cell><Data ss:Type="String">' . $cscCheckResult . '</Data></Cell>';
            
            echo '</Row>';
        }

        // End XML structure
        echo '</Table>';
        echo '</Worksheet>';
        echo '</Workbook>';

    } else {
        // Handle no data found
        header("Content-Type: text/plain");
        echo "No data available to export.";
    }
} catch (PDOException $e) {
    // Handle database errors
    header("Content-Type: text/plain");
    die("Database error: " . $e->getMessage());
}

exit;
?>