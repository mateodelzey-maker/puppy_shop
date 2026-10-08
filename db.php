<?php
// Enable full error reporting to diagnose 502 crashes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    $dbopts = parse_url($dbUrl);

    if ($dbopts === false) {
        die("Error: Unable to parse DATABASE_URL environment variable.");
    }

    $host     = $dbopts["host"] ?? '';
    $port     = $dbopts["port"] ?? 5432;
    $user     = $dbopts["user"] ?? 'postgres';
    $password = isset($dbopts["pass"]) ? urldecode($dbopts["pass"]) : '';
    $dbname   = isset($dbopts["path"]) ? ltrim($dbopts["path"], '/') : 'postgres';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";
} else {
    // Local XAMPP Fallback
    $host     = "localhost";
    $user     = "root";
    $password = "";
    $dbname   = "puppy_db";

    $dsn = "mysql:host={$host};dbname={$dbname}";
}

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5 // Prevent hanging requests
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>