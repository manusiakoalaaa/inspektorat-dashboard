<?php
/**
 * Konfigurasi koneksi database
 * Sesuaikan dengan environment lokal (XAMPP/Laragon/dll)
 */
$DB_HOST = 'localhost';
$DB_NAME = 'inspektorat_reviu';
$DB_USER = 'root';
$DB_PASS = '';
$DB_PORT = '3306';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal. Pastikan database "inspektorat_reviu" sudah dibuat dan file database.sql sudah diimport. Detail: ' . $e->getMessage());
}
