<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Ensure session is started
startSessionIfNotStarted();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
    exit;
}

// Get filter and pagination parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$pic = isset($_GET['pic']) ? trim($_GET['pic']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // Number of records per page

// Build query for filtering
$whereClauses = [];
$params = [];

// Search across all relevant columns
if (!empty($search)) {
    $searchFields = [
        'model',
        'ole_version',
        'ap',
        'cp',
        'csc',
        'pic',
        'qb_user',
        'qb_csc_user',
        'additional_cl',
        'qb_csc_user_xid',
        'qb_csc_eng',
        'release_note_format'
    ];
    $searchClauses = [];
    foreach ($searchFields as $field) {
        $searchClauses[] = "$field LIKE ?";
        $params[] = "%$search%";
    }
    $whereClauses[] = "(" . implode(" OR ", $searchClauses) . ")";
}

if (!empty($status)) {
    $whereClauses[] = "status = ?";
    $params[] = $status;
}

if (!empty($pic)) {
    $whereClauses[] = "pic = ?";
    $params[] = $pic;
}

if (!empty($start_date) && !empty($end_date)) {
    $whereClauses[] = "DATE(created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
} elseif (!empty($start_date)) {
    $whereClauses[] = "DATE(created_at) >= ?";
    $params[] = $start_date;
} elseif (!empty($end_date)) {
    $whereClauses[] = "DATE(created_at) <= ?";
    $params[] = $end_date;
}

// Get total number of releases for pagination
$sqlCount = "SELECT COUNT(*) FROM release_cheatsheets";
if (!empty($whereClauses)) {
    $sqlCount .= " WHERE " . implode(" AND ", $whereClauses);
}
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalItems = $stmtCount->fetchColumn();

// Fetch releases with filters
$offset = ($page - 1) * $perPage;
$sql = "SELECT id, model, ap, cp, csc, ole_version, pic, created_at, status, qb_user, qb_csc_user, additional_cl, qb_csc_user_xid, qb_csc_eng, release_note_format 
        FROM release_cheatsheets";
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY created_at DESC LIMIT ?, ?";

$stmt = $pdo->prepare($sql);

// Bind parameters, ensuring LIMIT parameters are integers
$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
}
// Bind LIMIT parameters as integers
$stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);

$stmt->execute();
$releases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique PICs for filter dropdown
$picStmt = $pdo->query("SELECT DISTINCT pic FROM release_cheatsheets ORDER BY pic");
$pics = $picStmt->fetchAll(PDO::FETCH_COLUMN);

// Generate pagination
$baseUrl = 'all_releases.php';
$queryString = http_build_query(array_filter([
    'search' => $search,
    'status' => $status,
    'pic' => $pic,
    'start_date' => $start_date,
    'end_date' => $end_date
]));
$pagination = generatePagination($totalItems, $perPage, $page, $baseUrl, $queryString ? "?$queryString" : '');
?>

<div class="bg-white shadow rounded-lg p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">All Releases</h1>

    <!-- Filter Form -->
    <form method="GET" class="mb-6 flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label for="search" class="block text-sm font-medium text-gray-700">Search Any Field</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Search model, AP, CP, CSC, PIC, etc.">
        </div>
        <div class="flex-1">
            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
            <select id="status" name="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Statuses</option>
                <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="done" <?php echo $status === 'done' ? 'selected' : ''; ?>>Done</option>
                <option value="skipped" <?php echo $status === 'skipped' ? 'selected' : ''; ?>>Skipped</option>
            </select>
        </div>
        <div class="flex-1">
            <label for="pic" class="block text-sm font-medium text-gray-700">PIC</label>
            <select id="pic" name="pic" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">All PICs</option>
                <?php foreach ($pics as $picOption): ?>
                    <option value="<?php echo htmlspecialchars($picOption); ?>" <?php echo $pic === $picOption ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($picOption); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1">
            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
            <input type="text" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Select start date">
        </div>
        <div class="flex-1">
            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
            <input type="text" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Select end date">
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
            <a href="export.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md ml-2">
                <i class="fas fa-file-excel mr-2"></i>Export to Excel
            </a>
        </div>
    </form>

    <!-- Releases Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CSC</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OLE Version</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PIC</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($releases)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">No releases found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($releases as $release): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($release['model']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($release['ap']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($release['cp']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($release['csc']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($release['ole_version']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($release['pic']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $release['status'] === 'done' ? 'bg-green-100 text-green-800' : 
                                              ($release['status'] === 'skipped' ? 'bg-red-100 text-red-800' : 
                                              ($release['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')); ?>">
                                    <?php echo htmlspecialchars(ucfirst($release['status'] ?: 'New')); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($release['created_at']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="edit.php?id=<?php echo $release['id']; ?>" class="text-yellow-600 hover:text-yellow-800 mr-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="view.php?id=<?php echo $release['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        <?php echo $pagination; ?>
    </div>
</div>

<!-- Flatpickr CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Initialize Flatpickr for date inputs
    flatpickr("#start_date", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        allowInput: true
    });
    flatpickr("#end_date", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        allowInput: true
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>