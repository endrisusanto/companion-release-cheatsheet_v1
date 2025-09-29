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

// Fetch all releases from the database
try {
    $sql = "SELECT * FROM release_cheatsheets ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);

    if ($stmt->rowCount() > 0) {
        $releases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set headers for Excel XML download
        $filename = "releases_" . date('Y-m-d') . ".xls";
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        // Start XML workbook
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml .= '<Worksheet ss:Name="All Releases">';
        $xml .= '<Table>';

        // Add header row
        $xml .= '<Row>';
        foreach (array_keys($releases[0]) as $header) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>';
        }
        $xml .= '</Row>';

        // Add data rows
        foreach ($releases as $row) {
            $xml .= '<Row>';
            foreach ($row as $cell) {
                // Determine data type, default to String
                $type = 'String';
                if (is_numeric($cell)) {
                    $type = 'Number';
                }
                // *** FIX HERE: Handle NULL values by replacing them with an empty string ***
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($cell ?? '') . '</Data></Cell>';
            }
            $xml .= '</Row>';
        }

        $xml .= '</Table>';
        $xml .= '</Worksheet>';
        $xml .= '</Workbook>';

        echo $xml;

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