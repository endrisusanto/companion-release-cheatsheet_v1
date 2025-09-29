<?php
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$id = $_GET['id'] ?? null;

if ($id) {
    if (deleteRelease($id)) {
        showAlert('Release data deleted successfully!', 'success');
    } else {
        showAlert('Failed to delete release data!', 'error');
    }
}

redirect('index.php');
?>