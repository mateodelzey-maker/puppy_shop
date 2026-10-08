<?php
// Check if Render provided the Supabase connection string
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // Parse the DATABASE_URL connection string from Render
    $dbopts = parse_url($dbUrl);

    $host     = $dbopts["host"];
    $port     = $dbopts["port"] ?? 5432;
    $user     = $dbopts["user"];
    $password = $dbopts["pass"];
    $dbname   = ltrim($dbopts["path"], '/');

    // DSN for PostgreSQL driver (pgsql)
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";
} else {
    // Local fallback for XAMPP / MySQL
    $host     = "localhost";
    $user     = "root";
    $password = "";
    $dbname   = "puppy_db";

    $dsn = "mysql:host={$host};dbname={$dbname}";
}

// Create connection using PDO
try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>