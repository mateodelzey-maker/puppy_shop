<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Kick them out immediately if they aren't authorized
    header("Location: index.php");
    exit();
}
require_once 'db.php';

// Change these to your preferred login details!
$user = 'admin'; 
$pass = 'YourSecurePassword123'; 

$hashed_password = password_hash($pass, PASSWORD_BCRYPT);

try {
    $sql = "INSERT INTO site_admins (username, password_hash) VALUES (:username, :password_hash)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $user);
    $stmt->bindParam(':password_hash', $hashed_password);
    $stmt->execute();
    echo "Admin account created successfully! DELETE THIS FILE NOW.";
} catch(PDOException $e) {
    echo "Error creating account: " . $e->getMessage();
}