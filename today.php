<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Check if user is logged in
// if (!isLoggedIn()) {
//     header('Location: login.php');
//     exit;
// }

// NEW: Date handling logic
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

// NEW: Calculate previous and next dates
$prevDate = (clone $displayDate)->modify('-1 day')->format('Y-m-d');
$nextDate = (clone $displayDate)->modify('+1 day')->format('Y-m-d');

// NEW: Build query string for navigation links to preserve filters
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
$filterEmptyP4Path = isset($_GET['filterEmptyP4Path']) && $_GET['filterEmptyP4Path'] === 'true'; // New checkbox

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

// MODIFIED: Fetch releases based on the selected date
$todayReleases = getTodayReleasesFiltered($displayDateStr, $filter, $excludeStatuses, $apFilterType, $searchQuery, $filterEmptyP4Path);

?>

<style>
    .container {
        max-width: 100%; /* Make container full width */
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .bg-white {
        border-radius: 0; /* Remove border radius */
        box-shadow: none; /* Remove shadow */
    }

    .overflow-x-auto {
        margin-left: 1rem; /* Adjust margin to negate default padding */
        margin-right: 1rem;
    }

    @media (min-width: 640px) {
        .overflow-x-auto {
            margin-left: 1.5rem; /* Adjust for larger screens */
            margin-right: 1.5rem;
        }
    }

    @media (min-width: 768px) {
        .overflow-x-auto {
            margin-left: 2rem; /* Adjust for even larger screens */
            margin-right: 2rem;
        }
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

        <div class="mb-4 flex flex-wrap items-center space-x-4">
            <form id="filterForm" method="GET" class="flex flex-wrap items-center gap-4">
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
                </div>

                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Apply Filters</button>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full bg-white overflow-hidden table">
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
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">Status</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[200px]">Action</th>
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
                            <td class="py-2 px-3 text-sm  highlight-blue <?php echo (stripos($release['ap'], 'XXS') !== false || stripos($release['ap'], 'DXS') !== false || stripos($release['ap'], 'TBS') !== false) ? 'highlight' : ''; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['ap']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm ">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['cp']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm font-bold text-green-600 " data-field="csc" data-id="<?php echo $release['id']; ?>">
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
                            <td class="py-2 px-3 text-sm font-bold text-green-600" data-field="csc_version_up" data-id="<?php echo $release['id']; ?>">
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
                            <td class="py-2 px-3 text-sm">
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

<script>
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
    document.querySelectorAll('.editable').forEach(cell => {
        cell.addEventListener('dblclick', () => {
            if (cell.classList.contains('editing')) return;

            const releaseId = cell.dataset.id;
            const currentRow = cell.closest('tr');
            // Re-calculate the status column index as a new column was added before it
            const statusCells = currentRow.querySelectorAll('td');
            const currentStatusCell = statusCells[15]; // Now index 15 for the 16th td (0-indexed)
            const currentStatusSpan = currentStatusCell.querySelector('span');
            const currentStatusText = currentStatusSpan ? currentStatusSpan.textContent.trim().toLowerCase() : '';

            // Condition: Change status to 'in_progress' if it's 'new' or 'pending'
            if (currentStatusText === 'new !' || currentStatusText === 'pending') {
                // Send AJAX to update status to 'in_progress'
                const statusData = new URLSearchParams();
                statusData.append('id', releaseId);
                statusData.append('status', 'in_progress'); // Target status

                fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
                    },
                    body: statusData.toString()
                })
                .then(response => {
                    // Check if response is JSON, if not, handle redirect or plain text
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        console.warn("Expected JSON but received non-JSON response from update_status.php. Assuming redirect or non-critical.");
                        return Promise.reject('Non-JSON response from update_status.php');
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Update the status cell in UI
                        currentStatusCell.innerHTML = '<span class="text-yellow-600 font-normal bg-yellow-100 px-2 py-1 rounded">Progress</span>';
                        currentRow.dataset.status = 'in_progress'; // Update data-status attribute on row
                    } else {
                        console.error('Failed to update status via AJAX:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error updating status via AJAX:', error);
                });
            }

            // Proceed with making the cell editable
            const span = cell.querySelector('span');
            const originalText = span.textContent.trim();
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'editable-input';
            input.value = originalText === '-' ? '' : originalText;

            cell.classList.add('editing');
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();

            input.addEventListener('blur', () => saveEdit(cell, input, originalText));
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    saveEdit(cell, input, originalText);
                } else if (e.key === 'Escape') {
                    cell.classList.remove('editing');
                    cell.innerHTML = `<span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit">${originalText}</span>`;
                }
            });
        });
    });

    function saveEdit(cell, input, originalText) {
        const newValue = input.value.trim() || '-';
        const field = cell.dataset.field;
        const id = cell.dataset.id;

        // Validate inputs
        if (!id || !field) {
            console.error('Missing id or field:', { id, field });
            showCopyFeedback(cell, 'Invalid data', true);
            cell.classList.remove('editing');
            cell.innerHTML = `<span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit">${originalText}</span>`;
            return;
        }

        // Update UI immediately
        cell.classList.remove('editing');
        cell.innerHTML = `<span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit">${newValue}</span>`;

        // Only send AJAX if value changed
        if (newValue === originalText) {
            console.log('No change in value, skipping AJAX');
            return;
        }

        // Prepare data for AJAX
        const data = new URLSearchParams();
        data.append('id', id);
        data.append('field', field);
        data.append('value', newValue);

        console.log('Sending AJAX request:', { id, field, value: newValue });

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
            console.log('AJAX response:', data);
            if (data.success) {
                showCopyFeedback(cell, 'Saved!', false);
            } else {
                console.error('Save failed:', data.message);
                showCopyFeedback(cell, `Save failed: ${data.message}`, true);
                cell.innerHTML = `<span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit">${originalText}</span>`;
            }
        })
        .catch(error => {
            console.error('AJAX error:', error.message);
            showCopyFeedback(cell, `Save failed: ${error.message}`, true);
            cell.innerHTML = `<span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Double-click to edit">${originalText}</span>`;
        });
    }

    // JavaScript for filtering using the form submission
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm = document.getElementById('filterForm');
        const filterSelect = document.getElementById('filter');
        const hideDoneAndSkippedCheckbox = document.getElementById('hideDoneAndSkipped');
        const filterApIncludeCheckbox = document.getElementById('filterApInclude');
        const filterApExcludeCheckbox = document.getElementById('filterApExclude');
        const filterEmptyP4PathCheckbox = document.getElementById('filterEmptyP4Path'); // New checkbox
        const searchInput = document.getElementById('search');

        // Function to submit the form
        const submitForm = () => {
            filterForm.submit();
        };

        // oke Add event listeners to the select, checkboxes, and search input to trigger form submission
        filterSelect.addEventListener('change', submitForm);
        hideDoneAndSkippedCheckbox.addEventListener('change', submitForm);
        
        filterApIncludeCheckbox.addEventListener('change', () => {
            if (filterApIncludeCheckbox.checked) {
                filterApExcludeCheckbox.checked = false; // Uncheck the other if this one is checked
            }
            submitForm();
        });

        filterApExcludeCheckbox.addEventListener('change', () => {
            if (filterApExcludeCheckbox.checked) {
                filterApIncludeCheckbox.checked = false; // Uncheck the other if this one is checked
            }
            submitForm();
        });

        filterEmptyP4PathCheckbox.addEventListener('change', submitForm); // Add event listener for new checkbox
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
