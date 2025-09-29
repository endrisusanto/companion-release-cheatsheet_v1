<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi input
    if (empty($username) || empty($email)) {
        $error = 'Username and email are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        // Verifikasi password saat ini jika ingin mengubah password
        if (!empty($new_password) && !password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            try {
                global $pdo;
                
                // Jika ada password baru, update password
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$username, $email, $hashed_password, $user['id']]);
                    $message = 'Profile and password updated successfully!';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$username, $email, $user['id']]);
                    $message = 'Profile updated successfully!';
                }
                
                // Update session username jika berubah
                $_SESSION['username'] = $username;
                $user = getCurrentUser(); // Refresh data user
                
            } catch (PDOException $e) {
                $error = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">User Profile</h1>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button class="ml-auto focus:outline-none" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button class="ml-auto focus:outline-none" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>

            <div class="mb-6">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password (only required if changing password)</label>
                <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       id="current_password" name="current_password">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password (leave blank to keep current)</label>
                    <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           id="new_password" name="new_password">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input type="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           id="confirm_password" name="confirm_password">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Created</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" 
                       value="<?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?>" readonly>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Updated</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" 
                       value="<?php echo date('Y-m-d H:i:s', strtotime($user['updated_at'])); ?>" readonly>
            </div>

            <div class="flex space-x-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Update Profile
                </button>
                <a href="index.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>