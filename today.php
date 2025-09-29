<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Check if user is logged in
// if (!isLoggedIn()) {
//     header('Location: login.php');
//     exit;
// }

// Date handling logic
$displayDateStr = $_GET['date'] ?? date('Y-m-d');
try {
    // Sanitize the date input to ensure it's in the expected format
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

// Calculate previous and next dates
$prevDate = (clone $displayDate)->modify('-1 day')->format('Y-m-d');
$nextDate = (clone $displayDate)->modify('+1 day')->format('Y-m-d');

// Build query string for navigation links to preserve filters
$queryParams = $_GET; // Get all current filters

// Prepare link for the previous day
$prevDateParams = $queryParams;
$prevDateParams['date'] = $prevDate;
$prevLink = '?' . http_build_query($prevDateParams);

// Prepare link for the next day
$nextDateParams = $queryParams;
$nextDateParams['date'] = $nextDate;
$nextLink = '?' . http_build_query($nextDateParams);

// Get filter from query parameter (default to 'my')
$filter = $_GET['filter'] ?? 'my';

// Get checkbox states from query parameters or default
$hideDoneAndSkipped = isset($_GET['hideDoneAndSkipped']) && $_GET['hideDoneAndSkipped'] === 'true';
$filterApInclude = isset($_GET['filterApInclude']) && $_GET['filterApInclude'] === 'true';
$filterApExclude = isset($_GET['filterApExclude']) && $_GET['filterApExclude'] === 'true';
$filterEmptyP4Path = isset($_GET['filterEmptyP4Path']) && $_GET['filterEmptyP4Path'] === 'true';

// Get search query from parameter
$searchQuery = $_GET['search'] ?? '';

// Determine statuses to exclude
$excludeStatuses = [];
if ($hideDoneAndSkipped) {
    $excludeStatuses = ['done', 'skipped'];
}

// Determine AP filter type
$apFilterType = 'none';
if ($filterApInclude && !$filterApExclude) {
    $apFilterType = 'include';
} elseif ($filterApExclude && !$filterApInclude) {
    $apFilterType = 'exclude';
}

// Fetch releases based on the selected date
$todayReleases = getTodayReleasesFiltered($displayDateStr, $filter, $excludeStatuses, $apFilterType, $searchQuery, $filterEmptyP4Path);
?>

<style>
    .container {
        max-width: 100%; /* Make container full width */
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .bg-white {
        border-radius: 0.5rem; /* Add border radius like other pages */
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); /* Add subtle shadow */
    }

    .overflow-x-auto {
        margin-left: 0; /* Remove custom margins */
        margin-right: 0;
    }

    .table-container {
        overflow-x: auto;
    }

    .table {
        width: auto; /* Allow table to expand horizontally */
        min-width: 100%; /* Ensure it takes at least the container width */
    }

    .highlight-bold {
        background-color: #fefcbf; /* Light yellow highlight */
        font-weight: bold;
    }
    .highlight-blue {
        background-color: #bfdbfe; /* Light blue highlight */
        font-weight: bold;
    }
    .highlight {
        background-color: #ffa89c; /* Light red highlight */
        font-weight: bold;
    }

    .editable:hover {
        background-color: #e2e8f0; /* Light gray on hover for editable cells */
        cursor: pointer;
    }

    .editing {
        background-color: #f0f4f8; /* Light background when editing */
    }

    .editable-input {
        width: 100%;
        padding: 0.25rem;
        border: 1px solid #d1d5db;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
    }

    /* Table wrapper for side-by-side layout */
    .table-wrapper {
        display: flex;
        position: relative;
        overflow: hidden;
    }

    .main-table-container {
        flex: 1;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .action-table-container {
        position: sticky;
        right: 0;
        top: 0;
        background: white;
        border-left: 2px solid #e5e7eb;
        z-index: 10;
        min-width: 320px;
        max-width: 320px;
    }

    .action-table {
        width: 100%;
        background: white;
    }

    /* Ensure both tables have same row heights */
    .main-table-container table,
    .action-table {
        table-layout: fixed;
    }

    /* Synchronize header heights - make them exactly the same */
    .main-table-container thead th,
    .action-table thead th {
        height: 48px; /* Fixed header height */
        padding: 12px; /* Consistent padding */
        vertical-align: middle;
    }

    /* Synchronize row heights for body */
    .main-table-container tbody tr,
    .action-table tbody tr {
        height: 65px; /* Fixed height for consistency */
    }

    /* Ensure both table headers have same background and styling */
    .main-table-container thead,
    .action-table thead {
        background-color: #1f2937;
        color: white;
    }

    /* Make action table header aligns with main table header */
    .action-table thead th {
        border-left: 2px solid #374151;
        font-weight: 600;
        font-size: 0.875rem;
        line-height: 1.25rem;
    }

    /* Remove divide between Status and Action headers */
    .action-table thead th:first-child {
        border-right: none;
    }

    .action-table thead th:last-child {
        border-left: none;
    }

    /* Remove divide between Status and Action body cells */
    .action-table tbody td:first-child {
        border-right: none;
    }

    .action-table tbody td:last-child {
        border-left: none;
    }

    /* Make Status and Action columns look unified */
    .action-table thead th {
        border-right: none;
        border-left: none;
    }

    .action-table thead th:first-child {
        border-right: none;
        border-left: 2px solid #374151; /* Only left border for first column */
    }

    .action-table tbody td {
        border-right: none;
        border-left: none;
    }

    .action-table tbody td:first-child {
        border-right: none;
        border-left: none;
    }

    /* Add shadow to action table for visual separation */
    .action-table-container::before {
        content: '';
        position: absolute;
        left: -2px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to right, rgba(0,0,0,0.1), transparent);
        z-index: 1;
    }
</style>

<div class="bg-white">
    <div class="p-6">
        <div class="flex items-center space-x-4 mb-4">
            <a href="<?php echo $prevLink; ?>" class="text-gray-600 hover:text-blue-600 p-2 rounded-full hover:bg-gray-100" title="Previous Day">
                <i class="fas fa-chevron-left fa-lg"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">
                Releases (<?php echo htmlspecialchars($displayDate->format('Y-m-d')); ?>)
            </h1>
            <?php if ($displayDate < $today): // Only show next day button if the date is in the past or today ?>
                <a href="<?php echo $nextLink; ?>" class="text-gray-600 hover:text-blue-600 p-2 rounded-full hover:bg-gray-100" title="Next Day">
                    <i class="fas fa-chevron-right fa-lg"></i>
                </a>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <form id="filterForm" method="GET" class="flex flex-wrap items-center gap-4 justify-end">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($displayDateStr); ?>">
                
                <div class="flex items-center space-x-2">
                    <label for="filter" class="text-sm font-medium text-gray-700">Filter Releases:</label>
                    <select name="filter" id="filter" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Releases</option>
                        <option value="my" <?php echo $filter === 'my' ? 'selected' : ''; ?>>My Releases</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="hideDoneAndSkipped" id="hideDoneAndSkipped" class="form-checkbox h-4 w-4 text-blue-600" value="true" <?php echo $hideDoneAndSkipped ? 'checked' : ''; ?>>
                    <label for="hideDoneAndSkipped" class="text-sm font-medium text-gray-700">Hide Done & Skipped</label>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="filterApInclude" id="filterApInclude" class="form-checkbox h-4 w-4 text-blue-600" value="true" <?php echo $filterApInclude ? 'checked' : ''; ?>>
                    <label for="filterApInclude" class="text-sm font-medium text-gray-700">SMR</label>
                </div>
                
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="filterApExclude" id="filterApExclude" class="form-checkbox h-4 w-4 text-blue-600" value="true" <?php echo $filterApExclude ? 'checked' : ''; ?>>
                    <label for="filterApExclude" class="text-sm font-medium text-gray-700">MR</label>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="filterEmptyP4Path" id="filterEmptyP4Path" class="form-checkbox h-4 w-4 text-blue-600" value="true" <?php echo $filterEmptyP4Path ? 'checked' : ''; ?>>
                    <label for="filterEmptyP4Path" class="text-sm font-medium text-gray-700">Empty P4 Path</label>
                </div>

                <div class="flex items-center space-x-2">
                    <label for="search" class="text-sm font-medium text-gray-700">Search:</label>
                    <input type="text" name="search" id="search" placeholder="Model, PIC, AP, CSC..."
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded whitespace-nowrap">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-wrapper">
        <div class="main-table-container">
            <table class="w-full bg-white overflow-hidden table" id="mainTable">
                <thead class="bg-gray-800 text-white sticky top-0 z-10">
                    <tr>
                        <th class="py-2 px-3 text-left text-sm min-w-[120px]">Model</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">PIC</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">AP</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">CP</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[120px]">CSC VERSION OXM/OLM BARU</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">OXM/OLM QB User</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">CSC VERSION XID LAMA</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[120px]">Previous Release QB XID</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">New QB CSC User (XID)</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">New QB CSC Eng (XID)</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">Additional CL</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">P4 Path</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[100px]">Partial CL CSC LAMA</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[120px]">CSC Version Up</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[60px]">Release Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="releaseTableBody">
                <?php if (empty($todayReleases)): ?>
                    <tr>
                        <td colspan="17" class="py-6 px-3 text-center text-sm text-gray-500">
                            No releases found for <?php echo htmlspecialchars($displayDateStr); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($todayReleases as $release): ?>
                        <tr class="hover:bg-gray-50" data-status="<?php echo htmlspecialchars($release['status']); ?>">
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['model']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['pic']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm highlight-blue <?php echo (stripos($release['ap'], 'XXS') !== false || stripos($release['ap'], 'DXS') !== false || stripos($release['ap'], 'TBS') !== false) ? 'highlight' : ''; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['ap']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['cp']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm font-bold text-green-600 editable" data-field="csc" data-id="<?php echo $release['id']; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit"><?php echo htmlspecialchars($release['csc']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm editable" data-field="qb_csc_user" data-id="<?php echo $release['id']; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit"><?php echo htmlspecialchars($release['qb_csc_user'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm font-bold text-blue-600 editable" data-field="ole_version" data-id="<?php echo $release['id']; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit"><?php echo htmlspecialchars($release['ole_version']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm editable" data-field="qb_user" data-id="<?php echo $release['id']; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit"><?php echo htmlspecialchars($release['qb_user'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm highlight-bold editable" data-field="qb_csc_user_xid" data-id="<?php echo $release['id']; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit"><?php echo htmlspecialchars($release['qb_csc_user_xid'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm highlight-bold editable" data-field="qb_csc_eng" data-id="<?php echo $release['id']; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit"><?php echo htmlspecialchars($release['qb_csc_eng'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm editable" data-field="additional_cl" data-id="<?php echo $release['id']; ?>">
                                <span class="copyable cursor-pointer relative truncate max-w-[150px]" data-tooltip="Double-click to edit"><?php echo htmlspecialchars($release['additional_cl'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm text-center">
                                <?php if (!empty($release['p4_path'])): ?>
                                    <i class="fas fa-check-circle text-green-500 copyable cursor-pointer relative"
                                        data-tooltip="Click to copy"
                                        data-text="<?php echo htmlspecialchars($release['p4_path']); ?>"
                                        data-debug="Present"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-red-500 cursor-not-allowed"
                                        data-tooltip="No P4 Path"
                                        data-debug="Empty"></i>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-3 text-sm text-center">
                                <?php if (!empty($release['partial_cl'])): ?>
                                    <i class="fas fa-check-circle text-green-500 copyable cursor-pointer relative"
                                        data-tooltip="Click to copy"
                                        data-text="<?php echo htmlspecialchars($release['partial_cl']); ?>"
                                        data-debug="Present"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-red-500 cursor-not-allowed"
                                        data-tooltip="No Partial CL"
                                        data-debug="Empty"></i>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-3 text-sm font-bold text-green-600 editable" data-field="csc_version_up" data-id="<?php echo $release['id']; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit"><?php echo htmlspecialchars($release['csc_version_up']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm text-center">
                                <?php if (!empty($release['release_note_format'])): ?>
                                    <i class="fas fa-file-alt copyable cursor-pointer relative text-gray-600 hover:text-gray-800"
                                        data-tooltip="Click to copy"
                                        data-text="<?php echo htmlspecialchars($release['release_note_format']); ?>"
                                        data-debug="Present"></i>
                                <?php else: ?>
                                    <i class="fas fa-file-alt text-gray-400 cursor-not-allowed"
                                        data-tooltip="No release note available"
                                        data-debug="Empty"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        
        <div class="action-table-container">
            <table class="action-table" id="actionTable">
                <thead class="bg-gray-800 text-white sticky top-0 z-10">
                    <tr>
                        <th class="py-2 px-3 text-left text-sm min-w-[120px]">Status</th>
                        <th class="py-2 px-3 text-left text-sm min-w-[200px]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="actionTableBody">
                    <?php if (empty($todayReleases)): ?>
                        <tr>
                            <td colspan="2" class="py-6 px-3 text-center text-sm text-gray-500">
                                No actions available
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($todayReleases as $release): ?>
                            <tr class="hover:bg-gray-50" data-status="<?php echo htmlspecialchars($release['status']); ?>">
                                <td class="py-2 px-3 text-sm" data-status-cell>
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
                                <td class="py-2 px-3 text-sm">
                                    <div class="flex justify-end space-x-2">
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
</div>

<script>
    // Utility function to copy text to clipboard
    function copyToClipboard(text, element) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showCopyFeedback(element, 'Copied!');
            }).catch(err => {
                console.error('Clipboard API failed:', err);
                fallbackCopy(text, element);
            });
        } else {
            fallbackCopy(text, element);
        }
    }

    // Fallback for older browsers
    function fallbackCopy(text, element) {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            const success = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (success) {
                showCopyFeedback(element, 'Copied!');
            } else {
                showCopyFeedback(element, 'Copy failed', true);
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            showCopyFeedback(element, 'Copy failed', true);
        }
    }

    // Show feedback for copy or save actions
    function showCopyFeedback(element, message, isError = false) {
        element.classList.add(isError ? 'bg-red-200' : 'bg-yellow-200');
        setTimeout(() => element.classList.remove(isError ? 'bg-red-200' : 'bg-yellow-200'), 1500);

        const tooltip = document.createElement('div');
        tooltip.textContent = message;
        tooltip.className = `absolute ${isError ? 'bg-red-800' : 'bg-gray-800'} text-white text-sm rounded px-2 py-1 -top-8 left-1/2 transform -translate-x-1/2`;
        element.appendChild(tooltip);
        setTimeout(() => tooltip.remove(), 1500);
    }

    // Use event delegation for copyable elements
    document.querySelector('.table').addEventListener('click', (e) => {
        const copyableElement = e.target.closest('.copyable');
        if (copyableElement) {
            e.stopPropagation();
            const text = copyableElement.dataset.text ? copyableElement.dataset.text.trim() : copyableElement.textContent.trim();
            if (text) {
                copyToClipboard(text, copyableElement);
            }
        }
    });

    // Inline editing functionality
    let isEditing = false; // Prevent multiple simultaneous edits

    document.querySelectorAll('.editable').forEach(cell => {
        cell.addEventListener('dblclick', () => {
            if (isEditing || cell.classList.contains('editing')) return;
            isEditing = true;

            const releaseId = cell.dataset.id;
            const field = cell.dataset.field;
            const span = cell.querySelector('span');
            const originalText = span.textContent.trim();
            const currentRow = cell.closest('tr');
            const statusCell = currentRow.querySelector('td[data-status-cell]');
            const currentStatus = statusCell ? statusCell.querySelector('span').textContent.trim().toLowerCase() : '';

            // Update status to 'in_progress' if 'new' or 'pending'
            if (currentStatus === 'new !' || currentStatus === 'pending') {
                updateStatus(releaseId, currentRow, statusCell);
            }

            // Create input field
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'editable-input';
            input.value = originalText === '-' ? '' : originalText;

            cell.classList.add('editing');
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();

            // Handle save or cancel
            const saveEdit = () => {
                const newValue = input.value.trim() || '-';
                if (!validateInput(field, newValue)) {
                    showCopyFeedback(cell, `Invalid input for ${field}`, true);
                    revertEdit(cell, originalText);
                    isEditing = false;
                    return;
                }

                // Update UI immediately
                cell.classList.remove('editing');
                cell.innerHTML = `<span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit">${newValue}</span>`;

                // Skip AJAX if value hasn't changed
                if (newValue === originalText) {
                    isEditing = false;
                    return;
                }

                // Send AJAX request to update
                const data = new URLSearchParams();
                data.append('id', releaseId);
                data.append('field', field);
                data.append('value', newValue);

                fetch('update_release.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: data.toString()
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showCopyFeedback(cell, 'Saved!', false);
                    } else {
                        console.error('Save failed:', data.message);
                        showCopyFeedback(cell, `Save failed: ${data.message}`, true);
                        revertEdit(cell, originalText);
                    }
                })
                .catch(error => {
                    console.error('AJAX error:', error.message);
                    showCopyFeedback(cell, `Save failed: ${error.message}`, true);
                    revertEdit(cell, originalText);
                })
                .finally(() => {
                    isEditing = false;
                });
            };

            // Event listeners for input
            input.addEventListener('blur', saveEdit);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    saveEdit();
                } else if (e.key === 'Escape') {
                    revertEdit(cell, originalText);
                    isEditing = false;
                }
            });
        });
    });

    // Validate input based on field type
    function validateInput(field, value) {
        const rules = {
            'qb_csc_user': value => value === '-' || /^[A-Za-z0-9-]+$/.test(value),
            'ole_version': value => value === '-' || /^[A-Za-z0-9._-]+$/.test(value),
            'qb_user': value => value === '-' || /^[A-Za-z0-9-]+$/.test(value),
            'qb_csc_user_xid': value => value === '-' || /^[A-Za-z0-9-]+$/.test(value),
            'qb_csc_eng': value => value === '-' || /^[A-Za-z0-9-]+$/.test(value),
            'additional_cl': value => value === '-' || /^[0-9, ]+$/.test(value),
        };
        return rules[field] ? rules[field](value) : true;
    }

    // Revert edit on error or cancel
    function revertEdit(cell, originalText) {
        cell.classList.remove('editing');
        cell.innerHTML = `<span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit">${originalText}</span>`;
    }

    // Update status to 'in_progress'
    function updateStatus(releaseId, currentRow, statusCell) {
        const statusData = new URLSearchParams();
        statusData.append('id', releaseId);
        statusData.append('status', 'in_progress');

        fetch('update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: statusData.toString()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            }
            throw new Error('Non-JSON response from update_status.php');
        })
        .then(data => {
            if (data.success) {
                statusCell.innerHTML = '<span class="text-yellow-600 font-normal bg-yellow-100 px-2 py-1 rounded">Progress</span>';
                currentRow.dataset.status = 'in_progress';
            } else {
                console.error('Failed to update status:', data.message);
                showCopyFeedback(statusCell, `Status update failed: ${data.message}`, true);
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            showCopyFeedback(statusCell, `Status update failed: ${error.message}`, true);
        });
    }

    // Filter form submission
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm = document.getElementById('filterForm');
        const filterSelect = document.getElementById('filter');
        const hideDoneAndSkippedCheckbox = document.getElementById('hideDoneAndSkipped');
        const filterApIncludeCheckbox = document.getElementById('filterApInclude');
        const filterApExcludeCheckbox = document.getElementById('filterApExclude');
        const filterEmptyP4PathCheckbox = document.getElementById('filterEmptyP4Path');
        const searchInput = document.getElementById('search');

        const submitForm = () => {
            filterForm.submit();
        };

        filterSelect.addEventListener('change', submitForm);
        hideDoneAndSkippedCheckbox.addEventListener('change', submitForm);
        filterApIncludeCheckbox.addEventListener('change', () => {
            if (filterApIncludeCheckbox.checked) {
                filterApExcludeCheckbox.checked = false;
            }
            submitForm();
        });
        filterApExcludeCheckbox.addEventListener('change', () => {
            if (filterApExcludeCheckbox.checked) {
                filterApIncludeCheckbox.checked = false;
            }
            submitForm();
        });
        filterEmptyP4PathCheckbox.addEventListener('change', submitForm);
    });

    // Synchronize scroll between main table and action table
    const mainTableContainer = document.querySelector('.main-table-container');
    const actionTableContainer = document.querySelector('.action-table-container');

    if (mainTableContainer && actionTableContainer) {
        mainTableContainer.addEventListener('scroll', () => {
            actionTableContainer.scrollTop = mainTableContainer.scrollTop;
        });
        actionTableContainer.addEventListener('scroll', () => {
            mainTableContainer.scrollTop = actionTableContainer.scrollTop;
        });
    }

    // Align headers on page load and resize
    function alignHeaders() {
        const mainTable = document.querySelector('#mainTable');
        const actionTable = document.querySelector('#actionTable');
        if (mainTable && actionTable) {
            const mainHeader = mainTable.querySelector('thead');
            const actionHeader = actionTable.querySelector('thead');
            if (mainHeader && actionHeader) {
                const mainHeaderHeight = mainHeader.offsetHeight;
                actionHeader.style.height = mainHeaderHeight + 'px';
                actionHeader.style.minHeight = mainHeaderHeight + 'px';
                actionHeader.style.maxHeight = mainHeaderHeight + 'px';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', alignHeaders);
    window.addEventListener('resize', alignHeaders);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>