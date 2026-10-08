<?php
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // Parse DATABASE_URL connection string safely
    $dbopts = parse_url($dbUrl);

    if ($dbopts === false || !isset($dbopts["host"])) {
        die("Database configuration error: Unable to parse DATABASE_URL.");
    }

    $host     = $dbopts["host"];
    $port     = $dbopts["port"] ?? 5432;
    $user     = $dbopts["user"] ?? 'postgres';
    $password = isset($dbopts["pass"]) ? urldecode($dbopts["pass"]) : '';
    $dbname   = isset($dbopts["path"]) ? ltrim($dbopts["path"], '/') : 'postgres';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";
} else {
    // Local fallback
    $host     = "localhost";
    $user     = "root";
    $password = "";
    $dbname   = "puppy_db";

    $dsn = "mysql:host={$host};dbname={$dbname}";
}

try {
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>