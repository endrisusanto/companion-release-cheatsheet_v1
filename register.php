<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm_password) {
        showAlert('Passwords do not match', 'error');
    } else {
        $result = registerUser($username, $email, $password);
        if ($result['status']) {
            showAlert($result['message'], 'success');
            redirect('login.php');
        } else {
            showAlert($result['message'], 'error');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6 mt-10">
    <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Register</h1>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 bg-<?php echo $_SESSION['flash_type'] === 'error' ? 'red' : 'green'; ?>-100 border border-<?php echo $_SESSION['flash_type'] === 'error' ? 'red' : 'green'; ?>-400 text-<?php echo $_SESSION['flash_type'] === 'error' ? 'red' : 'green'; ?>-700 rounded">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-4">
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" id="username" name="username" required
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="email" name="email" required
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="password" name="password" required
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div>
            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Register
            </button>
        </div>
        
        <div class="text-center text-sm text-gray-600">
            Already have an account? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Login here</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>