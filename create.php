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

<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_data'])) {
        $bulkData = $_POST['bulk_data'] ?? '';
        $result = createBulkReleases($bulkData);
        if ($result['status']) {
            header('Location: index.php?status=bulk_created&count=' . $result['count']);
            exit;
        } else {
            $error = 'Failed to create bulk releases: ' . $result['message'];
        }
    } elseif (isset($_POST['model'])) {
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
            'new_build_xid' => $_POST['new_build_xid'] ?? '',
            'qb_csc_user_xid' => $_POST['qb_csc_user_xid'] ?? '',
            'qb_csc_eng' => $_POST['qb_csc_eng'] ?? '',
            'pic' => $_POST['pic'] ?? ''
        ];

        if (createRelease($data)) {
            header('Location: today.php?status=created');
            exit;
        } else {
            $error = 'Failed to create release data!';
        }
    }
}

// Include header after processing to avoid output before redirect
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white shadow rounded-lg p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Add New Release</h1>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Tabs for Single vs Bulk Entry -->
    <div class="mb-6">
        <ul class="flex border-b">
            <li class="mr-1">
                <a class="bg-white inline-block py-2 px-4 text-blue-600 font-semibold border-b-2 border-blue-600" href="#single" onclick="showTab('single')">Single Entry</a>
            </li>
            <li class="mr-1">
                <a class="bg-white inline-block py-2 px-4 text-gray-600 font-semibold" href="#bulk" onclick="showTab('bulk')">Bulk Entry (Excel Paste)</a>
            </li>
        </ul>
    </div>

    <!-- Single Entry Form -->
    <div id="single" class="tab-content">
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Model</label>
                    <input type="text" name="model" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">OLE VERSION</label>
                    <input type="text" name="ole_version" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">QB USER</label>
                    <input type="text" name="qb_user" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2">OXM/OLM NEW VERSION</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">AP</label>
                            <input type="text" name="ap" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CP</label>
                            <input type="text" name="cp"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CSC</label>
                            <input type="text" name="csc" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 glow-input">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">QB CSC USER</label>
                    <input type="text" name="qb_csc_user" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">ADDITIONAL CL</label>
                    <input type="text" name="additional_cl" value=""
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2">NEW BUILD XID</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">QB CSC USER (XID)</label>
                            <input type="text" name="qb_csc_user_xid" 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">QB CSC ENG</label>
                            <input type="text" name="qb_csc_eng" 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">PIC</label>
                    <input type="text" name="pic" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <div class="flex justify-between pt-4">
                <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Save Release Data
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Entry Form -->
    <div id="bulk" class="tab-content hidden">
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Paste Excel Data (PIC, MODEL, AP, CP, CSC, created_at)</label>
                <textarea name="bulk_data" rows="10"
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Paste tab-separated data here, e.g.&#10;John&#9;SM-G998B&#9;AP123&#9;CP456&#9;CSC789&#9;2025-05-01&#10;Jane&#9;SM-A525F&#9;AP124&#9;CP457&#9;CSC790&#9;2025-05-02"></textarea>
                <p class="mt-2 text-sm text-gray-500">Copy data from Excel with columns: PIC, MODEL, AP, CP, CSC, created_at (in YYYY-MM-DD format).</p>
            </div>
            
            <div class="flex justify-between pt-4">
                <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Save Bulk Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    document.querySelector(`#${tabId}`).classList.remove('hidden');
    
    document.querySelectorAll('.flex li a').forEach(link => {
        link.classList.remove('text-blue-600', 'border-blue-600');
        link.classList.add('text-gray-600');
    });
    document.querySelector(`a[href="#${tabId}"]`).classList.add('text-blue-600', 'border-blue-600');
    document.querySelector(`a[href="#${tabId}"]`).classList.remove('text-gray-600');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>