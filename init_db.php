<?php
require_once 'database.php';

// Create users table
$users_table = "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nip VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin'
)";

// Create guest_entries table  
$guest_entries_table = "
CREATE TABLE IF NOT EXISTS guest_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    visitor_name VARCHAR(100) NOT NULL,
    ktp_number VARCHAR(20) NOT NULL,
    institution VARCHAR(100) NOT NULL,
    job VARCHAR(100) NOT NULL,
    required_info TEXT NOT NULL,
    legal_product_purpose TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($users_table);
    $pdo->exec($guest_entries_table);
    echo "Database tables created successfully!";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>