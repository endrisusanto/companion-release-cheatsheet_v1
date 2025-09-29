<?php
require_once __DIR__ . '/includes/functions.php';

// Mendeteksi apakah ini adalah permintaan AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null; // Ini bisa 'done', 'skipped', atau 'in_progress'

    $success = false;
    $message = '';

    if ($id && $status) { // Pastikan ID dan status ada
        // Panggil fungsi updateReleaseStatus yang generik (sudah ada di functions.php)
        // Fungsi ini bisa menerima status apapun ('done', 'skipped', 'in_progress', 'pending', dll.)
        if (updateReleaseStatus($id, $status)) {
            $success = true;
            $message = 'Status berhasil diperbarui menjadi ' . htmlspecialchars($status) . '.';
        } else {
            $message = 'Gagal memperbarui status di database.';
        }
    } else {
        $message = 'ID atau status tidak valid diberikan.';
    }

    if ($isAjax) {
        // Untuk permintaan AJAX, kembalikan JSON dan keluar
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    } else {
        // Untuk permintaan non-AJAX (misalnya, pengiriman formulir langsung dari tombol 'Mark as Done'/'Skip')
        if ($success) {
            showAlert($message, 'success');
        } else {
            showAlert($message, 'error');
        }
        // Redirect kembali ke today.php. Anda bisa menambahkan filter yang aktif jika perlu.
        header('Location: today.php');
        exit;
    }
} else {
    // Jika bukan permintaan POST
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
        exit;
    } else {
        header('Location: today.php'); // Redirect jika akses langsung tanpa POST
        exit;
    }
}
?>