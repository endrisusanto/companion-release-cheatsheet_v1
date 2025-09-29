<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .container {
        max-width: 100%; /* Make container full width */
        padding-left: 0.5rem;
        padding-right:  0.5rem;
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
        background-color: #bfdbfe; /* Light yellow highlight */
        font-weight: bold;
    }
    .highlight {
        background-color: #ffa89c; /* Light yellow highlight */
        font-weight: bold;
    }
</style>

<?php
// Check if user is logged in
// if (!isLoggedIn()) {
//     header('Location: login.php');
//     exit;
// }

// Get filter from query parameter (default to 'my')
$filter = $_GET['filter'] ?? 'my';

// Fetch releases based on filter
if ($filter === 'my' && isset($_SESSION['username'])) {
    $todayReleases = getTodayReleasesByPic($_SESSION['username']);
} else {
    $todayReleases = getTodayReleases();
}
?>

<div class="bg-white">
    <div class="p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Today's Releases (<?php echo date('Y-m-d'); ?>)</h1>

        <div class="mb-4">
            <form method="GET" class="flex items-center space-x-2">
                <label for="filter" class="text-sm font-medium text-gray-700">Filter Releases:</label>
                <select name="filter" id="filter" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Releases</option>
                    <option value="my" <?php echo $filter === 'my' ? 'selected' : ''; ?>>My Releases</option>
                </select>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full bg-white overflow-hidden table">
            <thead class="bg-gray-800 text-white sticky top-0 z-10">
                <tr>
                    <th class="py-2 px-3 text-left text-sm min-w-[120px]">Model</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">PIC</th>
                    <!-- <th class="py-2 px-3 text-left text-sm min-w-[100px]">Date</th> -->
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">AP</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">CP</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[120px]">CSC OXM/OLM</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">OXM/OLM CSC User</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">Previous Release XID</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[120px]">Previous Release QB XID</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">New QB CSC User (XID)</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">New QB CSC Eng (XID)</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[120px]">Additional CL</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[60px]">Release Note</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[100px]">Status</th>
                    <th class="py-2 px-3 text-left text-sm min-w-[200px]">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($todayReleases)): ?>
                    <tr>
                        <td colspan="15" class="py-6 px-3 text-center text-gray-500 text-sm">No releases found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($todayReleases as $release): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['model']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['pic']); ?></span>
                            </td>
                            
                            <td class="py-2 px-3 text-sm highlight-blue <?php echo (stripos($release['ap'], 'XXS') !== false || stripos($release['ap'], 'DXS') !== false) ? 'highlight' : ''; ?>">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['ap']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['cp']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm font-bold text-green-600">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['csc']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['qb_csc_user'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm font-bold text-blue-600">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['ole_version']); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['qb_user'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm highlight-bold">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['qb_csc_user_xid'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm highlight-bold">
                                <span class="copyable cursor-pointer relative whitespace-nowrap" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['qb_csc_eng'] ?? '-'); ?></span>
                            </td>
                            <td class="py-2 px-3 text-sm">
                                <span class="copyable cursor-pointer relative truncate max-w-[150px]" data-tooltip="Click to copy"><?php echo htmlspecialchars($release['additional_cl'] ?? '-'); ?></span>
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
                                    <span class="text-green-600 font-normal">Done</span>
                                <?php elseif ($release['status'] === 'in_progress'): ?>
                                    <span class="text-yellow-600 font-normal">In Progress</span>
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
        // Try using Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showCopyFeedback(element, 'Copied!');
            }).catch(err => {
                console.error('Clipboard API failed:', err);
                fallbackCopy(text, element);
            });
        } else {
            // Fallback to execCommand
            fallbackCopy(text, element);
        }
    }

    function fallbackCopy(text, element) {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed'; // Avoid scrolling
            textarea.style.opacity = '0'; // Hide element
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
        // Apply background highlight
        element.classList.add(isError ? 'bg-red-200' : 'bg-yellow-200');
        setTimeout(() => element.classList.remove(isError ? 'bg-red-200' : 'bg-yellow-200'), 1500);

        // Show tooltip
        const tooltip = document.createElement('div');
        tooltip.textContent = message;
        tooltip.className = `absolute ${isError ? 'bg-red-800' : 'bg-gray-800'} text-white text-xs rounded px-2 py-1 -top-8 left-1/2 transform -translate-x-1/2`;
        element.appendChild(tooltip);
        setTimeout(() => tooltip.remove(), 1500);
    }

    document.querySelectorAll('.copyable').forEach(element => {
        element.addEventListener('click', () => {
            // Use data-text if available (for icons), otherwise use textContent
            const text = element.dataset.text ? element.dataset.text.trim() : element.textContent.trim();
            if (text) {
                copyToClipboard(text, element);
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>