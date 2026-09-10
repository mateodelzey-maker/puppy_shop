<?php
require_once 'db.php';

// Forcefully clear out any broken records
$conn->exec("TRUNCATE TABLE site_admins");

// Set exactly what you want here
$user = 'admin'; 
$pass = 'YourSecurePassword123'; 
$hashed_password = password_hash($pass, PASSWORD_BCRYPT);

try {
    $sql = "INSERT INTO site_admins (username, password_hash) VALUES (:username, :password_hash)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':username' => $user,
        ':password_hash' => $hashed_password
    ]);
    echo "<h1>Database cleaned & Admin reset successfully!</h1>";
    echo "Username: <strong>$user</strong><br>";
    echo "Password: <strong>$pass</strong><br><br>";
    echo "<a href='login.php'>Go to Login Page</a>";
} catch(PDOException $e) {
    die("Query Error: " . $e->getMessage());
}