
<?php
// Start output buffering to capture any unintended output
ob_start();

// Set error reporting to log errors instead of displaying them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log'); // Ensure this path is writable

// Set JSON content type
header('Content-Type: application/json');

// Log script start
error_log('update_release.php started');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$id = $_POST['id'] ?? '';
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

// Validate input
$validFields = ['additional_cl', 'qb_csc_eng', 'qb_csc_user_xid', 'qb_user', 'ole_version', 'qb_csc_user', 'csc'];
if (!$id || !in_array($field, $validFields)) {
    error_log("Invalid input: id=$id, field=$field");
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

/**
 * Updates a specific field for a release in the database
 * @param int $id The release ID
 * @param string $field The field to update
 * @param string $value The new value
 * @return bool True on success, false on failure
 */
function updateReleaseField($id, $field, $value) {
    try {
        // Database connection
        $db = new PDO('mysql:host=localhost;dbname=companion_release_db', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log('Database connection successful');

        // Sanitize the field name
        $validFields = ['additional_cl', 'qb_csc_eng', 'qb_csc_user_xid', 'qb_user', 'ole_version', 'qb_csc_user', 'csc'];
        if (!in_array($field, $validFields)) {
            error_log("Invalid field: $field");
            return false;
        }

        // Prepare the update query
        $query = "UPDATE release_cheatsheets SET $field = :value WHERE id = :id";
        $stmt = $db->prepare($query);

        // Bind parameters
        $stmt->bindValue(':value', $value === '-' ? null : $value, $value === '-' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Execute and log
        $result = $stmt->execute();
        error_log("Update query: $query, id=$id, value=$value, result=" . ($result ? 'success' : 'failure'));
        return $result;
    } catch (PDOException $e) {
        error_log('Update failed: ' . $e->getMessage());
        return false;
    }
}

// Update the database
try {
    $success = updateReleaseField($id, $field, $value);
    ob_end_clean();
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Update successful' : 'Update failed'
    ]);
} catch (Exception $e) {
    error_log('Update error: ' . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Ensure no extra output
ob_end_flush();
?>