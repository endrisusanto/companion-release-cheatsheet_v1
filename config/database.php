<?php
// Pastikan error reporting aktif untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'companion_release_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default timezone
    $pdo->exec("SET time_zone = '+07:00';");
    
    // Create tables if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS release_cheatsheets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        model VARCHAR(50) NOT NULL,
        ole_version VARCHAR(50) NOT NULL,
        qb_user VARCHAR(50) NOT NULL,
        oxm_olm_new_version VARCHAR(100) NOT NULL,
        ap VARCHAR(50) NOT NULL,
        cp VARCHAR(50) NOT NULL,
        csc VARCHAR(50) NOT NULL,
        qb_csc_user VARCHAR(50) NOT NULL,
        additional_cl VARCHAR(50) DEFAULT '-',
        new_build_xid VARCHAR(100) NOT NULL,
        qb_csc_user_xid VARCHAR(50) NOT NULL,
        qb_csc_eng VARCHAR(50) NOT NULL,
        release_note_format TEXT NOT NULL,
        ap_mapping VARCHAR(100) NOT NULL,
        cp_mapping VARCHAR(100) NOT NULL,
        csc_version_up VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Pastikan variabel $pdo tersedia secara global
global $pdo;
?>