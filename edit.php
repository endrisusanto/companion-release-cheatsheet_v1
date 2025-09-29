<?php
// Ensure no whitespace before this
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Ensure session is started
startSessionIfNotStarted();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
    exit;
}

// Get release ID from query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    redirect('index.php');
    exit;
}

// Fetch release data
$release = getReleaseById($id);

if (!$release) {
    redirect('index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'model' => $_POST['model'] ?? '',
        'ole_version' => $_POST['ole_version'] ?? '',
        'qb_user' => $_POST['qb_user'] ?? '',
        'oxm_olm_new_version' => $_POST['oxm_olm_new_version'] ?? '',
        'ap' => $_POST['ap'] ?? '',
        'cp' => $_POST['cp'] ?? '',
        'csc' => $_POST['csc'] ?? '',
        'qb_csc_user' => $_POST['qb_csc_user'] ?? '',
        'additional_cl' => $_POST['additional_cl'] ?? '',
        'partial_cl' => $_POST['partial_cl'] ?? '',
        'p4_path' => $_POST['p4_path'] ?? '', // Added p4_path here
        'new_build_xid' => $_POST['new_build_xid'] ?? '',
        'qb_csc_user_xid' => $_POST['qb_csc_user_xid'] ?? '',
        'qb_csc_eng' => $_POST['qb_csc_eng'] ?? '',
        'pic' => $_POST['pic'] ?? '',
        'release_note_format' => $_POST['release_note_format'] ?? '',
        'ap_mapping' => $_POST['ap_mapping'] ?? '',
        'cp_mapping' => $_POST['cp_mapping'] ?? '',
        'csc_version_up' => $_POST['csc_version_up'] ?? '' // Value from readonly field, will be recalculated later
    ];

    // Apply the OLE/OLP + 5 chars logic to ole_version
    // Use ?? '' to ensure it's a string even if $_POST['ole_version'] is not set
    $data['ole_version'] = processVersionStringByKeyword($data['ole_version'] ?? '', ['OLE', 'OLP'], 5);

    // Calculate the effective csc_version_up based on the csc input, as it's needed for P4 path logic
    // Use ?? '' for safety if $_POST['csc'] is not set
    $effective_csc_version_up = convertCscVersion($data['csc'] ?? '');

    // --- NEW LOGIC FOR P4 PATH PROCESSING ---
    $p4_path_input = $data['p4_path'] ?? ''; // Get the raw P4 path input

    // Check if the p4_path ends with '...'
    if (substr($p4_path_input, -3) === '...') {
        $replacement_suffix = '';

        // Determine replacement based on the *effective* csc_version_up
        if (strpos($effective_csc_version_up, 'OLE') !== false) {
            $replacement_suffix = 'OLE';
        } elseif (strpos($effective_csc_version_up, 'OLP') !== false) {
            $replacement_suffix = 'OLP';
        }
        
        // Replace '...' with the determined suffix
        if (!empty($replacement_suffix)) {
            $data['p4_path'] = substr($p4_path_input, 0, -3) . $replacement_suffix;
        }
    }
    // --- END NEW LOGIC ---

    // Update release data
    if (updateRelease($id, $data)) {
        showAlert('Release data updated successfully!', 'success');
        redirect('today.php?id=' . urlencode($id));
        exit;
    } else {
        showAlert('Failed to update release data!', 'error');
    }
}

// Update status to 'in_progress' if it's 'new' or empty
if ($release['status'] === 'new' || empty($release['status'])) {
    updateReleaseStatus($id, 'in_progress');
    // Refresh release data to reflect updated status
    $release = getReleaseById($id);
}
?>

<style>
.glow-input {
    animation: glow 2s infinite alternate;
}

@keyframes glow {
    from {
        box-shadow: 0 0 5px rgba(66, 153, 225, 0.5);
        background-color: rgba(219, 234, 254, 0.5);
    }
    to {
        box-shadow: 0 0 15px rgba(66, 153, 225, 0.8);
        background-color: rgba(219, 234, 254, 0.8);
    }
}
</style>

<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Release Details: <?php echo htmlspecialchars($release['model']); ?></h1>
        <div class="flex space-x-2">
            <a href="edit.php?id=<?php echo $release['id']; ?>" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded-md">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            <?php if ($release['status'] !== 'done' && $release['status'] !== 'skipped'): ?>
                <form action="update_status.php" method="POST" id="markDoneForm">
                    <input type="hidden" name="id" value="<?php echo $release['id']; ?>">
                    <input type="hidden" name="status" value="done">
                    <button type="submit" class="bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1 rounded-md">
                        <i class="fas fa-check mr-1"></i>Mark as Done
                    </button>
                </form>
                <form action="update_status.php" method="POST" id="skipForm">
                    <input type="hidden" name="id" value="<?php echo $release['id']; ?>">
                    <input type="hidden" name="status" value="skipped">
                    <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded-md">
                        <i class="fas fa-forward mr-1"></i>Skip
                    </button>
                </form>
            <?php endif; ?>
            <a href="today.php" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded-md">
                <i class="fas fa-list mr-1"></i>View All
            </a>
        </div>
    </div>

    <form method="POST" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label for="model" class="block text-sm font-medium text-gray-700">Model</label>
                <input type="text" id="model" name="model" required
                        value="<?php echo htmlspecialchars($release['model']); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">        
                <div>
                    <label for="qb_user" class="block text-sm font-medium text-gray-700">QB USER</label>
                    <input type="text" id="qb_user" name="qb_user"
                            value="<?php echo htmlspecialchars($release['qb_user']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
                </div>
                <div>
                    <label for="ole_version" class="block text-sm font-medium text-gray-700">CSC OLE VERSION</label>
                    <input type="text" id="ole_version" name="ole_version" 
                            value="<?php echo htmlspecialchars($release['ole_version']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
                </div>
            </div>

            <div class="md:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2">OXM/OLM NEW VERSION</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label for="ap" class="block text-sm font-medium text-gray-700">AP</label>
                        <input type="text" id="ap" name="ap" required
                                value="<?php echo htmlspecialchars($release['ap']); ?>"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="cp" class="block text-sm font-medium text-gray-700">CP</label>
                        <input type="text" id="cp" name="cp"
                                value="<?php echo htmlspecialchars($release['cp']); ?>"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="csc" class="block text-sm font-medium text-gray-700">CSC</label>
                        <input type="text" id="csc" name="csc" required
                                value="<?php echo htmlspecialchars($release['csc']); ?>"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <div>
                <label for="qb_csc_user" class="block text-sm font-medium text-gray-700">QB CSC USER</label>
                <input type="text" id="qb_csc_user" name="qb_csc_user"
                        value="<?php echo htmlspecialchars($release['qb_csc_user']); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
            </div>

            <div>
                <label for="additional_cl" class="block text-sm font-medium text-gray-700">ADDITIONAL CL</label>
                <input type="text" id="additional_cl" name="additional_cl"
                        value="<?php echo htmlspecialchars($release['additional_cl']); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
            </div>

            <div>
                <label for="partial_cl" class="block text-sm font-medium text-gray-700">PARTIAL CL</label>
                <input type="text" id="partial_cl" name="partial_cl"
                        value="<?php echo htmlspecialchars($release['partial_cl'] ?? ''); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
            </div>

            <div>
                <label for="p4_path" class="block text-sm font-medium text-gray-700">P4 Path</label>
                <input type="text" id="p4_path" name="p4_path" 
                        value="<?php echo htmlspecialchars($release['p4_path'] ?? ''); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
            </div>
            <div class="md:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2">NEW BUILD XID</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="qb_csc_user_xid" class="block text-sm font-medium text-gray-700">QB CSC USER (XID)</label>
                        <input type="text" id="qb_csc_user_xid" name="qb_csc_user_xid"
                                value="<?php echo htmlspecialchars($release['qb_csc_user_xid']); ?>"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
                    </div>
                    <div>
                        <label for="qb_csc_eng" class="block text-sm font-medium text-gray-700">QB CSC ENG</label>
                        <input type="text" id="qb_csc_eng" name="qb_csc_eng"
                                value="<?php echo htmlspecialchars($release['qb_csc_eng']); ?>"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
                    </div>
                </div>
            </div>

            <div>
                <label for="pic" class="block text-sm font-medium text-gray-700">PIC</label>
                <input type="text" id="pic" name="pic" required
                        value="<?php echo htmlspecialchars($release['pic']); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="release_note_format" class="block text-sm font-medium text-gray-700">Release Note Format</label>
                <input readonly type="text" id="release_note_format" name="release_note_format"
                        value="<?php echo htmlspecialchars($release['release_note_format']); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="ap_mapping" class="block text-sm font-medium text-gray-700">AP Mapping</label>
                <input readonly type="text" id="ap_mapping" name="ap_mapping"
                        value="<?php echo htmlspecialchars($release['ap_mapping']); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="cp_mapping" class="block text-sm font-medium text-gray-700">CP Mapping</label>
                <input readonly type="text" id="cp_mapping" name="cp_mapping"
                        value="<?php echo htmlspecialchars($release['cp_mapping']); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="csc_version_up" class="block text-sm font-medium text-gray-700">CSC Version Up</label>
                <input readonly type="text" id="csc_version_up" name="csc_version_up"
                        value="<?php echo htmlspecialchars($release['csc_version_up']); ?>"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div class="flex justify-between pt-4">
            <a href="today.php?id=<?php echo $release['id']; ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md">
                <i class="fas fa-arrow-left mr-2"></i>Cancel
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-save mr-2"></i>Update
            </button>
        </div>
    </form>
</div>

<div id="confirmationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
        <h3 id="modalTitle" class="text-lg font-semibold text-gray-800 mb-4"></h3>
        <p id="modalMessage" class="text-gray-600 mb-6"></p>
        <div class="flex justify-end space-x-3">
            <button id="cancelButton" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
            <button id="confirmButton" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Confirm</button>
        </div>
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
            const text = element.textContent.trim();
            if (text) {
                copyToClipboard(text, element);
            }
        });
    });

    // Modal handling
    const modal = document.getElementById('confirmationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmButton = document.getElementById('confirmButton');
    const cancelButton = document.getElementById('cancelButton');
    let activeForm = null;

    function showModal(title, message, form) {
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        activeForm = form;
        modal.classList.remove('hidden');
    }

    function hideModal() {
        modal.classList.add('hidden');
        activeForm = null;
    }

    document.getElementById('markDoneForm').addEventListener('submit', function(e) {
        e.preventDefault();
        showModal('Confirm Mark as Done', 'Are you sure you want to mark this release as Done?', this);
    });

    document.getElementById('skipForm').addEventListener('submit', function(e) {
        e.preventDefault();
        showModal('Confirm Skip', 'Are you sure you want to skip this release?', this);
    });

    confirmButton.addEventListener('click', () => {
        if (activeForm) {
            activeForm.submit();
        }
        hideModal();
    });

    cancelButton.addEventListener('click', () => {
        hideModal();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>