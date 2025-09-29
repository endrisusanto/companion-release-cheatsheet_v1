<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Include dashboard at the top
include __DIR__ . '/dashboard.php';

// NOTE: The pagination logic below seems to be for a different section and is not used by the "Today's Releases" table.
// It is kept here in case it's used elsewhere, but it's not active for the table shown on this page.
/*
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$perPage = 5;
$releases = getAllReleases($search, $page, $perPage);
$totalItems = countReleases($search);
$totalPages = ceil($totalItems / $perPage);
*/


// --- START: Logic for the daily releases list with date navigation ---

// 1. Date handling logic
$displayDateStr = $_GET['date'] ?? date('Y-m-d');
try {
    // Sanitize the date input
    $tempDate = new DateTime($displayDateStr);
    $displayDateStr = $tempDate->format('Y-m-d');
    $displayDate = new DateTime($displayDateStr);
    $today = new DateTime('today');
} catch (Exception $e) {
    // Default to today if date format is invalid
    $displayDate = new DateTime('today');
    $today = new DateTime('today');
    $displayDateStr = $displayDate->format('Y-m-d');
}

// 2. Calculate previous and next dates and build links that preserve filters
$prevDate = (clone $displayDate)->modify('-1 day')->format('Y-m-d');
$nextDate = (clone $displayDate)->modify('+1 day')->format('Y-m-d');

$queryParams = $_GET; // Get all current filters

// Prepare link for the previous day
$prevDateParams = $queryParams;
$prevDateParams['date'] = $prevDate;
$prevLink = '?' . http_build_query($prevDateParams);

// Prepare link for the next day
$nextDateParams = $queryParams;
$nextDateParams['date'] = $nextDate;
$nextLink = '?' . http_build_query($nextDateParams);

// 3. Get filters from URL
$filter = $_GET['filter'] ?? 'all';

// 4. Fetch releases using the updated function which handles date and filtering
// We pass empty/default values for the more complex filters that are not present in this simplified view.
$todayReleases = getTodayReleasesFiltered($displayDateStr, $filter, [], 'none', '', false);

// --- END: Logic for the daily releases list ---
?>

<div class="bg-white shadow rounded-lg p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        
        <div class="flex items-center space-x-4">
            <a href="<?php echo $prevLink; ?>" class="text-gray-600 hover:text-blue-600 p-2 rounded-full hover:bg-gray-100" title="Previous Day">
                <i class="fas fa-chevron-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">
                Releases (<?php echo htmlspecialchars($displayDate->format('Y-m-d')); ?>)
            </h1>
            <?php if ($displayDate < $today): // Only show next day button if the date is in the past ?>
                <a href="<?php echo $nextLink; ?>" class="text-gray-600 hover:text-blue-600 p-2 rounded-full hover:bg-gray-100" title="Next Day">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md mt-4 md:mt-0">
            <i class="fas fa-plus mr-2"></i>Add New
        </a>
    </div>

    <div class="mb-4">
        <form method="GET" class="flex items-center space-x-2">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($displayDateStr); ?>">

            <label for="filter" class="text-sm font-medium text-gray-700">Filter Releases:</label>
            <select name="filter" id="filter" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Releases</option>
                <option value="my" <?php echo $filter === 'my' ? 'selected' : ''; ?>>My Releases</option>
            </select>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg overflow-hidden">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="py-3 px-4 text-left">Model</th>
                    <th class="py-3 px-4 text-left">AP</th>
                    <th class="py-3 px-4 text-left">CP</th>
                    <th class="py-3 px-4 text-left">New Base CSC Version</th>
                    <th class="py-3 px-4 text-left">Previous OLE Version</th>
                    <th class="py-3 px-4 text-left">PIC</th>
                    <th class="py-3 px-4 text-left">Status</th>
                    <th class="py-3 px-4 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($todayReleases)): ?>
                    <tr>
                        <td colspan="8" class="py-6 px-4 text-center text-gray-500">
                            No releases found for <?php echo htmlspecialchars($displayDateStr); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($todayReleases as $release): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4"><?php echo htmlspecialchars($release['model']); ?></td>
                            <td class="py-4 px-4"><?php echo htmlspecialchars($release['ap']); ?></td>
                            <td class="py-4 px-4"><?php echo htmlspecialchars($release['cp']); ?></td>
                            <td class="py-4 px-4 font-bold text-green-600"><?php echo htmlspecialchars($release['csc']); ?></td>
                            <td class="py-4 px-4 font-bold text-blue-600"><?php echo htmlspecialchars($release['ole_version']); ?></td>
                            <td class="py-4 px-4"><?php echo htmlspecialchars($release['pic']); ?></td>
                            <td class="py-4 px-4 text-sm">
                                <?php if ($release['status'] === 'done'): ?>
                                    <span class="text-green-600 font-bold bg-green-100 px-2 py-1 rounded">Done</span>
                                <?php elseif ($release['status'] === 'in_progress'): ?>
                                    <span class="text-yellow-600 font-normal bg-yellow-100 px-2 py-1 rounded">Progress</span>
                                <?php elseif ($release['status'] === 'skipped'): ?>
                                    <span class="text-red-600 font-bold bg-red-100 px-2 py-1 rounded">Skipped</span>
                                <?php else: ?>                                
                                    <span class="text-red-600 font-bold bg-red-100 px-2 py-1 rounded">New !</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex justify-start space-x-2">
                                    <a href="view.php?id=<?php echo $release['id']; ?>" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded-md text-sm">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                    <a href="edit.php?id=<?php echo $release['id']; ?>" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded-md text-sm">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <a href="delete.php?id=<?php echo $release['id']; ?>" 
                                       class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded-md text-sm"
                                       onclick="return confirm('Are you sure you want to delete this release?')">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>