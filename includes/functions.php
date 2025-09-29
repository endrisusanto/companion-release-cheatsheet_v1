<?php
require_once __DIR__ . '/../config/database.php';

// Fungsi manajemen session
function startSessionIfNotStarted() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Fungsi autentikasi
function isLoggedIn() {
    startSessionIfNotStarted();
    return isset($_SESSION['user_id']);
}

function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        startSessionIfNotStarted();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    
    return false;
}

function registerUser($username, $email, $password) {
    global $pdo;
    
    // Validasi input
    if (empty($username) || empty($email) || empty($password)) {
        return ['status' => false, 'message' => 'All fields are required'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        return ['status' => true, 'message' => 'Registration successful'];
    } catch (PDOException $e) {
        return ['status' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function logout() {
    startSessionIfNotStarted();
    session_unset();
    session_destroy();
}

function getCurrentUser() {
    if (isLoggedIn()) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

// Fungsi CRUD untuk Release Cheat Sheet
function getAllReleases($search = '', $page = 1, $perPage = 10) {
    global $pdo;
    
    $offset = ($page - 1) * $perPage;
    $sql = "SELECT * FROM release_cheatsheets";
    
    if (!empty($search)) {
        $sql .= " WHERE model LIKE :search OR ole_version LIKE :search";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :offset, :perPage";
    
    $stmt = $pdo->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReleaseById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM release_cheatsheets WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createRelease($data) {
    global $pdo;
    
    // Auto-generate fields
    $csc_version_up = convertCscVersion($data['csc']);
    $release_note = generateReleaseNote($data['ap'], $data['cp'], $csc_version_up);
    
    $stmt = $pdo->prepare("INSERT INTO release_cheatsheets 
        (model, ole_version, qb_user, oxm_olm_new_version, ap, cp, csc, 
        qb_csc_user, additional_cl, partial_cl, p4_path, new_build_xid, qb_csc_user_xid, 
        qb_csc_eng, release_note_format, ap_mapping, cp_mapping, 
        csc_version_up, pic, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    return $stmt->execute([
        $data['model'],
        $data['ole_version'],
        $data['qb_user'],
        $data['oxm_olm_new_version'],
        $data['ap'],
        $data['cp'],
        $data['csc'],
        $data['qb_csc_user'],
        $data['additional_cl'],
        $data['partial_cl'],
        $data['p4_path'],
        $data['new_build_xid'],
        $data['qb_csc_user_xid'],
        $data['qb_csc_eng'],
        $release_note,
        $data['ap'],
        $data['cp'],
        $csc_version_up,
        $data['pic']
    ]);
}

function updateRelease($id, $data) {
    global $pdo;
    
    // Auto-generate fields
    $csc_version_up = convertCscVersion($data['csc']);
    $release_note = generateReleaseNote($data['ap'], $data['cp'], $csc_version_up);
    
    $stmt = $pdo->prepare("UPDATE release_cheatsheets SET 
        model = ?, ole_version = ?, qb_user = ?, oxm_olm_new_version = ?, ap = ?, cp = ?, csc = ?, 
        qb_csc_user = ?, additional_cl = ?, partial_cl = ?, p4_path = ?, new_build_xid = ?, qb_csc_user_xid = ?, qb_csc_eng = ?, 
        release_note_format = ?, ap_mapping = ?, cp_mapping = ?, csc_version_up = ?, pic = ?
        WHERE id = ?");
    
    return $stmt->execute([
        $data['model'],
        $data['ole_version'],
        $data['qb_user'],
        $data['oxm_olm_new_version'],
        $data['ap'],
        $data['cp'],
        $data['csc'],
        $data['qb_csc_user'],
        $data['additional_cl'],
        $data['partial_cl'],
        $data['p4_path'],
        $data['new_build_xid'],
        $data['qb_csc_user_xid'],
        $data['qb_csc_eng'],
        $release_note,
        $data['ap'],
        $data['cp'],
        $csc_version_up,
        $data['pic'],
        $id
    ]);
}

function deleteRelease($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM release_cheatsheets WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Processes a version string based on keywords like 'OLE' or 'OLP'.
 * Extracts the prefix, the keyword, and the next 'charsAfterKeyword' characters after the keyword.
 * This function is kept because it's used for 'ole_version' in edit.php.
 *
 * @param string $inputString The version string to process.
 * @param array $keywords An array of keywords to search for (e.g., ['OLE', 'OLP']).
 * @param int $charsAfterKeyword The number of characters to take after the keyword.
 * @return string The processed version string, or the original if no keyword is found.
 */
function processVersionStringByKeyword($inputString, $keywords, $charsAfterKeyword) {
    $inputString = trim($inputString); // Trim whitespace

    foreach ($keywords as $keyword) {
        $pos_keyword = strpos($inputString, $keyword);
        if ($pos_keyword !== false) {
            $prefix = substr($inputString, 0, $pos_keyword); // Get everything before the keyword
            $suffix_start_pos = $pos_keyword + strlen($keyword); // Start after the keyword
            // Ensure there are enough characters for the suffix
            $suffix = substr($inputString, $suffix_start_pos, $charsAfterKeyword); 
            return $prefix . $keyword . $suffix;
        }
    }
    // If no keyword is found, return the original string
    return $inputString;
}

// REVERTED: convertCscVersion is back to its original str_replace logic
function convertCscVersion($csc) {
    $csc = trim($csc); // Trim whitespace from the input
    if (strpos($csc, 'OXM') !== false || strpos($csc, 'OLM') !== false) {
        return str_replace(['OXM', 'OLM'], 'OLE', $csc);
    } elseif (strpos($csc, 'OXT') !== false || strpos($csc, 'OLO') !== false) {
        return str_replace(['OXT', 'OLO'], 'OLP', $csc);
    }
    return $csc;
}

function generateReleaseNote($ap, $cp, $csc_version_up) {
    return "Binary Release\n" .
           "AP Mapping to Base -> $ap\n" .
           "CP Mapping to Base -> $cp\n" .
           "CSC Version Up -> $csc_version_up";
}


// START: MODIFIED FUNCTIONS FOR DATE NAVIGATION
/**
 * Fetches releases for a specific date with filtering options.
 *
 * @param string $date The date to fetch releases for in 'Y-m-d' format.
 * @param array $excludeStatuses Statuses to exclude.
 * @param string $apFilterType AP filter type ('include', 'exclude', 'none').
 * @param string $searchQuery Search term.
 * @param bool $filterEmptyP4Path Filter for empty p4_path.
 * @return array
 */
function getReleasesByDate($date, $excludeStatuses = [], $apFilterType = 'none', $searchQuery = '', $filterEmptyP4Path = false) {
    global $pdo;
    $sql = "SELECT id, model, ap, cp, csc, ole_version, pic, created_at, status, 
                   qb_user, qb_csc_user, additional_cl, partial_cl, p4_path, qb_csc_user_xid, qb_csc_eng, release_note_format, csc_version_up
            FROM release_cheatsheets 
            WHERE DATE(created_at) = ?";
    
    $params = [$date]; // Start params with the date

    if (!empty($excludeStatuses)) {
        $placeholders = implode(',', array_fill(0, count($excludeStatuses), '?'));
        $sql .= " AND status NOT IN ($placeholders)";
        $params = array_merge($params, $excludeStatuses);
    }

    if ($apFilterType === 'include') {
        $sql .= " AND (ap LIKE '%XXS%' OR ap LIKE '%DXS%' OR ap LIKE '%TBS%')";
    } elseif ($apFilterType === 'exclude') {
        $sql .= " AND (ap NOT LIKE '%XXS%' AND ap NOT LIKE '%DXS%' AND ap NOT LIKE '%TBS%')";
    }

    if ($filterEmptyP4Path) {
        $sql .= " AND (p4_path IS NULL OR p4_path = '')";
    }

    if (!empty($searchQuery)) {
        $sql .= " AND (model LIKE ? OR pic LIKE ? OR ap LIKE ? OR csc LIKE ? OR csc_version_up LIKE ? OR p4_path LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Fetches releases for a specific date and PIC with filtering options.
 *
 * @param string $date The date to fetch releases for in 'Y-m-d' format.
 * @param string $pic The PIC (Person In Charge) to filter by.
 * @param array $excludeStatuses Statuses to exclude.
 * @param string $apFilterType AP filter type ('include', 'exclude', 'none').
 * @param string $searchQuery Search term.
 * @param bool $filterEmptyP4Path Filter for empty p4_path.
 * @return array
 */
function getReleasesByDateAndPic($date, $pic, $excludeStatuses = [], $apFilterType = 'none', $searchQuery = '', $filterEmptyP4Path = false) {
    global $pdo;
    $sql = "SELECT id, model, ap, cp, csc, ole_version, pic, created_at, status, 
                   qb_user, qb_csc_user, additional_cl, partial_cl, p4_path, qb_csc_user_xid, qb_csc_eng, release_note_format, csc_version_up
            FROM release_cheatsheets 
            WHERE DATE(created_at) = ? AND pic = ?";
    
    $params = [$date, $pic]; // Start params with date and PIC

    if (!empty($excludeStatuses)) {
        $placeholders = implode(',', array_fill(0, count($excludeStatuses), '?'));
        $sql .= " AND status NOT IN ($placeholders)";
        $params = array_merge($params, $excludeStatuses);
    }

    if ($apFilterType === 'include') {
        $sql .= " AND (ap LIKE '%XXS%' OR ap LIKE '%DXS%' OR ap LIKE '%TBS%')";
    } elseif ($apFilterType === 'exclude') {
        $sql .= " AND (ap NOT LIKE '%XXS%' AND ap NOT LIKE '%DXS%' AND ap NOT LIKE '%TBS%')";
    }

    if ($filterEmptyP4Path) {
        $sql .= " AND (p4_path IS NULL OR p4_path = '')";
    }

    if (!empty($searchQuery)) {
        $sql .= " AND (model LIKE ? OR ap LIKE ? OR csc LIKE ? OR csc_version_up LIKE ? OR p4_path LIKE ?)";
        $searchParam = "%$searchQuery%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches releases with optional filtering, now based on a specific date.
 *
 * @param string $date The date to fetch releases for in 'Y-m-d' format.
 * @param string $filter 'all' for all releases, 'my' for releases by the logged-in PIC.
 * @param array $excludeStatuses An array of statuses to exclude (e.g., ['done', 'skipped']).
 * @param string $apFilterType 'include' for XXS/DXS/TBS, 'exclude' for not XXS/DXS/TBS, 'none' for no AP filter.
 * @param string $searchQuery Optional search string for model, pic, ap, csc, csc_version_up, p4_path.
 * @param bool $filterEmptyP4Path Whether to filter for empty p4_path.
 * @return array An array of release data.
 */
function getTodayReleasesFiltered($date, $filter, $excludeStatuses = [], $apFilterType = 'none', $searchQuery = '', $filterEmptyP4Path = false) {
    startSessionIfNotStarted();
    if ($filter === 'my' && isset($_SESSION['username'])) {
        return getReleasesByDateAndPic($date, $_SESSION['username'], $excludeStatuses, $apFilterType, $searchQuery, $filterEmptyP4Path);
    } else {
        return getReleasesByDate($date, $excludeStatuses, $apFilterType, $searchQuery, $filterEmptyP4Path);
    }
}
// END: MODIFIED FUNCTIONS FOR DATE NAVIGATION


function countReleases($search = '') {
    global $pdo;
    
    $sql = "SELECT COUNT(*) FROM release_cheatsheets";
    if (!empty($search)) {
        $sql .= " WHERE model LIKE ? OR ole_version LIKE ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query($sql);
    }
    
    return $stmt->fetchColumn();
}

function countModels() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(DISTINCT model) FROM release_cheatsheets");
    return $stmt->fetchColumn();
}

function countUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    return $stmt->fetchColumn();
}

// MODIFIED: Added error logging for debugging
function updateReleaseStatus($id, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE release_cheatsheets SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $id]);

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("Failed to update status for ID: $id, Status: $status. PDO Error: " . $errorInfo[2]);
        }
        return $result;
    } catch (PDOException $e) {
        error_log("PDOException in updateReleaseStatus for ID: $id. Error: " . $e->getMessage());
        return false;
    }
}

function getReleaseStats() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_releases,
            COUNT(DISTINCT model) as total_models,
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as monthly_count
        FROM release_cheatsheets
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDailyStats() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            pic,
            COUNT(*) as pic_count
        FROM release_cheatsheets
        WHERE YEAR(created_at) = YEAR(CURDATE())
            AND MONTH(created_at) = MONTH(CURDATE())
        GROUP BY DATE(created_at), pic
        ORDER BY date
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("getDailyStats Result: " . print_r($result, true));
    return $result;
}

function showAlert($message, $type = 'success') {
    startSessionIfNotStarted();
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function updateReleaseField($id, $field, $value) {
    global $pdo; 
    
    // Added 'p4_path' to validFields
    $validFields = ['additional_cl', 'partial_cl', 'p4_path', 'qb_csc_eng', 'qb_csc_user_xid', 'qb_user', 'ole_version', 'qb_csc_user', 'csc'];
    if (!in_array($field, $validFields)) {
        error_log("Invalid field attempted for update: " . $field);
        return false;
    }

    try {
        $stmt = $pdo->prepare("UPDATE release_cheatsheets SET $field = ? WHERE id = ?");
        $success = $stmt->execute([$value, $id]);

        if (!$success) {
            error_log("Failed to update release ID $id, field $field with value $value. ErrorInfo: " . print_r($stmt->errorInfo(), true));
        }
        return $success;
    } catch (PDOException $e) {
        error_log("PDOException during updateReleaseField: " . $e->getMessage());
        return false;
    }
}

function createBulkReleases($bulkData) {
    global $pdo;

    if (empty($bulkData)) {
        return ['status' => false, 'message' => 'No data provided'];
    }

    // Split data into rows
    $rows = array_filter(array_map('trim', explode("\n", $bulkData)));
    if (empty($rows)) {
        return ['status' => false, 'message' => 'No valid rows found'];
    }

    $successCount = 0;
    $errors = [];

    // Begin transaction for atomicity
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO release_cheatsheets
            (model, ole_version, qb_user, oxm_olm_new_version, ap, cp, csc,
            qb_csc_user, additional_cl, partial_cl, p4_path, new_build_xid, qb_csc_user_xid,
            qb_csc_eng, release_note_format, ap_mapping, cp_mapping,
            csc_version_up, pic, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rows as $index => $row) {
            $columns = array_map('trim', explode("\t", $row));
            
            $expected_columns_from_paste = 19; 
            if (count($columns) < $expected_columns_from_paste) {
                $errors[] = "Row " . ($index + 1) . ": Invalid number of columns (expected {$expected_columns_from_paste} for full data, got " . count($columns) . "). Please ensure all columns are copied, or adjust the bulk paste mapping.";
                continue;
            }

            [$check, $model_name, $release_purpose, $stage, $customer, $ap_code_ver, $ap_code_diff, 
             $cp_bb_ver, $cp_bb_diff, $csc_ver, $csc_diff, $confirm_status, $sw_em, $reg_by, 
             $reg_date, $release_date_time, $security_patch_level, $listed_date, $one_ui_ver] = array_pad($columns, $expected_columns_from_paste, '');

            $pic = $check;
            $model = $model_name;
            $ap = $ap_code_ver;
            $cp = $cp_bb_ver;
            $csc = $csc_ver;
            $created_at_input = $listed_date; 

            $ole_version = ''; 
            $qb_user = ''; 
            $oxm_olm_new_version = ''; 
            $qb_csc_user = ''; 
            $additional_cl = ''; 
            $partial_cl = ''; 
            $p4_path = ''; // Initialized p4_path for bulk creation
            $new_build_xid = ''; 
            $qb_csc_user_xid = ''; 
            $qb_csc_eng = '';

            // Validate required fields (excluding CP from mandatory check for bulk import)
            if (empty($pic) || empty($model) || empty($ap) || empty($csc) || empty($created_at_input)) {
                $errors[] = "Row " . ($index + 1) . ": Missing required field(s) (PIC, Model, AP, CSC, Listed Date). Ensure these columns are populated in your Excel data.";
                continue;
            }

            // Convert date format from DD/MM/YYYY to YYYY-MM-DD for database
            $date = DateTime::createFromFormat('d/m/Y', $created_at_input); 
            if ($date === false) {
                $errors[] = "Row " . ($index + 1) . ": Invalid date format for 'Listed' column. Expected DD/MM/YYYY. Value found: '{$created_at_input}'";
                continue;
            }
            $created_at_formatted = $date->format('Y-m-d'); 

            // Generate auto fields
            $csc_version_up = convertCscVersion($csc); 
            $release_note = generateReleaseNote($ap, $cp, $csc_version_up);

            // Execute insert
            $success = $stmt->execute([
                $model,
                $ole_version, 
                $qb_user,
                $oxm_olm_new_version,
                $ap,
                $cp, 
                $csc,
                $qb_csc_user,
                $additional_cl,
                $partial_cl, 
                $p4_path, // Passed p4_path
                $new_build_xid,
                $qb_csc_user_xid,
                $qb_csc_eng,
                $release_note,
                $ap,          
                $cp,          
                $csc_version_up,
                $pic,
                $created_at_formatted
            ]);

            if ($success) {
                $successCount++;
            } else {
                $errors[] = "Row " . ($index + 1) . ": Failed to insert data into database.";
            }
        }

        if ($successCount > 0) {
            $pdo->commit();
            return ['status' => true, 'count' => $successCount, 'message' => "$successCount rows inserted successfully"];
        } else {
            $pdo->rollBack();
            return ['status' => false, 'message' => 'No rows inserted. Errors: ' . implode(', ', $errors)];
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['status' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}     

function generatePagination($totalItems, $itemsPerPage, $currentPage, $baseUrl, $search = '') {
    // Calculate total pages
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // Ensure current page is valid
    $currentPage = max(1, min($currentPage, $totalPages));
    
    // Determine the range of pages to show (max 10)
    $maxPagesToShow = 10;
    $halfPagesToShow = floor($maxPagesToShow / 2);
    
    // Calculate start and end pages
    $startPage = max(1, $currentPage - $halfPagesToShow);
    $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
    
    // Adjust start if end is at max
    if ($endPage - $startPage + 1 < $maxPagesToShow) {
        $startPage = max(1, $endPage - $maxPagesToShow + 1);
    }
    
    // Build query string with search parameter
    $queryString = $search ? '&search=' . urlencode($search) : '';
    
    // Generate pagination HTML
    $html = '<nav class="inline-flex rounded-md shadow" aria-label="Page navigation">';
    
    // Previous button
    $prevDisabled = $currentPage == 1 ? 'pointer-events-none opacity-50' : '';
    $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . $queryString . '" class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 ' . $prevDisabled . '" aria-label="Previous">';
    $html .= '<i class="fas fa-chevron-left mr-1"></i>Previous';
    $html .= '</a>';
    
    // Page numbers
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $currentPage ? 'bg-blue-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . $queryString . '" class="' . $active . ' px-3 py-2 border-t border-b border-gray-300">';
        $html .= $i;
        $html .= '</a>';
    }
    
    // Next button
    $nextDisabled = $currentPage == $totalPages ? 'pointer-events-none opacity-50' : '';
    $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . $queryString . '" class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 ' . $nextDisabled . '" aria-label="Next">';
    $html .= 'Next<i class="fas fa-chevron-right ml-1"></i>';
    $html .= '</a>';
    
    $html .= '</nav>';
    
    return $html;
}
?>