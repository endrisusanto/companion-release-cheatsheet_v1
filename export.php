<?php
/**
 * Export functionality for release cheatsheets
 * Refactored to use ExportManager class for better organization
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ExportManager.php';

// Ensure session is started
startSessionIfNotStarted();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
    exit;
}

// Initialize ExportManager
$exportManager = new ExportManager($pdo);

// Handle different export types based on parameters
$exportType = $_GET['type'] ?? 'all';
$date = $_GET['date'] ?? null;
$filters = $_GET['filters'] ?? [];

try {
    switch ($exportType) {
        case 'date':
            if ($date) {
                $exportManager->exportByDate($date);
            } else {
                $exportManager->exportAllReleases();
            }
            break;
            
        case 'filtered':
            $exportManager->exportWithFilters($filters);
            break;
            
        case 'all':
        default:
            $exportManager->exportAllReleases();
            break;
    }
} catch (Exception $e) {
    // Handle any unexpected errors
    header("Content-Type: text/plain");
    die("Export error: " . $e->getMessage());
}

exit;
?>