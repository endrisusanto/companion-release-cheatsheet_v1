<?php

/**
 * ExportManager Class
 * Handles all export functionality for the application
 */
class ExportManager
{
    private $pdo;
    private $headers;
    private $data;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Export all releases to Excel format
     * 
     * @param array $filters Optional filters for data
     * @return void
     */
    public function exportAllReleases($filters = [])
    {
        try {
            $releases = $this->fetchReleases($filters);
            
            if (empty($releases)) {
                $this->handleNoData();
                return;
            }

            $this->prepareData($releases);
            $this->generateExcelFile();
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e);
        }
    }

    /**
     * Fetch releases from database with optional filters
     * 
     * @param array $filters
     * @return array
     */
    private function fetchReleases($filters = [])
    {
        $sql = "SELECT * FROM release_cheatsheets ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Prepare data for export including headers and processed data
     * 
     * @param array $releases
     * @return void
     */
    private function prepareData($releases)
    {
        // Get original headers from first row
        $this->headers = array_keys($releases[0]);
        
        // Add custom headers
        $this->headers[] = 'CSC Check';
        
        // Process data with additional calculations
        $this->data = [];
        foreach ($releases as $row) {
            $processedRow = $row;
            
            // Add CSC Check calculation
            $processedRow['csc_check'] = $this->calculateCscCheck($row['csc'] ?? '');
            
            $this->data[] = $processedRow;
        }
    }

    /**
     * Calculate CSC Check result
     * 
     * @param string $cscValue
     * @return string
     */
    private function calculateCscCheck($cscValue)
    {
        if (empty($cscValue)) {
            return '';
        }
        
        $cscValue = strtolower($cscValue);
        $validPatterns = ['oxm', 'olm', 'oxt'];
        
        foreach ($validPatterns as $pattern) {
            if (strpos($cscValue, $pattern) !== false) {
                return strtoupper($pattern);
            }
        }
        
        return '';
    }

    /**
     * Generate Excel XML file
     * 
     * @return void
     */
    private function generateExcelFile()
    {
        $filename = "releases_" . date('Y-m-d') . ".xls";
        
        // Set headers for file download
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        
        // Start XML document
        $this->startXmlDocument();
        
        // Write table structure
        $this->writeTableStructure();
        
        // Write header row
        $this->writeHeaderRow();
        
        // Write data rows
        $this->writeDataRows();
        
        // End XML document
        $this->endXmlDocument();
    }

    /**
     * Start XML document with proper namespaces
     * 
     * @return void
     */
    private function startXmlDocument()
    {
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
                xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        echo '<Worksheet ss:Name="All Releases">';
    }

    /**
     * Write table structure with proper attributes
     * 
     * @return void
     */
    private function writeTableStructure()
    {
        $columnCount = count($this->headers);
        $rowCount = count($this->data) + 1; // +1 for header row
        
        echo '<Table ss:ExpandedColumnCount="' . $columnCount . '" ss:ExpandedRowCount="' . $rowCount . '">';
    }

    /**
     * Write header row
     * 
     * @return void
     */
    private function writeHeaderRow()
    {
        echo '<Row>';
        foreach ($this->headers as $header) {
            echo '<Cell><Data ss:Type="String">' . $this->encodeCellData($header) . '</Data></Cell>';
        }
        echo '</Row>';
    }

    /**
     * Write data rows
     * 
     * @return void
     */
    private function writeDataRows()
    {
        foreach ($this->data as $row) {
            echo '<Row>';
            
            // Write original data cells
            foreach ($row as $key => $value) {
                if ($key === 'csc_check') {
                    continue; // Skip the calculated field, we'll add it separately
                }
                
                // Clean the value before determining type
                $cleanedValue = $this->cleanNumericValue($value);
                $type = $this->determineDataType($cleanedValue);
                
                echo '<Cell><Data ss:Type="' . $type . '">' . $this->encodeCellData($cleanedValue) . '</Data></Cell>';
            }
            
            // Add CSC Check result
            echo '<Cell><Data ss:Type="String">' . $this->encodeCellData($row['csc_check']) . '</Data></Cell>';
            
            echo '</Row>';
        }
    }

    /**
     * Clean numeric values by removing trailing spaces and non-numeric characters
     * 
     * @param mixed $value
     * @return string
     */
    private function cleanNumericValue($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        
        // Convert to string and trim
        $cleaned = trim((string)$value);
        
        // If it looks like a number, remove any non-numeric characters except decimal point
        if (preg_match('/^\s*[\d\s]+\s*$/', $cleaned)) {
            // Remove all spaces and non-numeric characters except decimal point
            $cleaned = preg_replace('/[^\d.]/', '', $cleaned);
        }
        
        return $cleaned;
    }

    /**
     * Determine the appropriate data type for Excel
     * 
     * @param mixed $value
     * @return string
     */
    private function determineDataType($value)
    {
        if ($value === null || $value === '') {
            return 'String';
        }
        
        // Check if it's a valid number
        if (is_numeric($value)) {
            return 'Number';
        }
        
        return 'String';
    }

    /**
     * End XML document
     * 
     * @return void
     */
    private function endXmlDocument()
    {
        echo '</Table>';
        echo '</Worksheet>';
        echo '</Workbook>';
    }

    /**
     * Encode cell data for safe use in Excel XML
     * 
     * @param mixed $value
     * @return string
     */
    private function encodeCellData($value)
    {
        if ($value === null) {
            return '';
        }
        
        // Convert to string and trim whitespace
        $cleanedValue = trim((string)$value);
        
        // If empty after trimming, return empty string
        if ($cleanedValue === '') {
            return '';
        }
        
        // Remove non-printable characters except for tab, newline, and carriage return
        $cleanedValue = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $cleanedValue);
        
        // Remove any remaining leading/trailing whitespace
        $cleanedValue = trim($cleanedValue);
        
        // If empty after cleaning, return empty string
        if ($cleanedValue === '') {
            return '';
        }
        
        // Encode for XML
        return htmlspecialchars($cleanedValue, ENT_XML1);
    }

    /**
     * Handle case when no data is available
     * 
     * @return void
     */
    private function handleNoData()
    {
        header("Content-Type: text/plain");
        echo "No data available to export.";
    }

    /**
     * Handle database errors
     * 
     * @param PDOException $e
     * @return void
     */
    private function handleDatabaseError($e)
    {
        header("Content-Type: text/plain");
        die("Database error: " . $e->getMessage());
    }

    /**
     * Export filtered releases based on date
     * 
     * @param string $date
     * @return void
     */
    public function exportByDate($date)
    {
        $filters = ['date' => $date];
        $this->exportAllReleases($filters);
    }

    /**
     * Export releases with custom filters
     * 
     * @param array $filters
     * @return void
     */
    public function exportWithFilters($filters)
    {
        $this->exportAllReleases($filters);
    }
}
