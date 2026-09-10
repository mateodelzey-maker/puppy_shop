<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Kick them out immediately if they aren't authorized
    header("Location: index.php");
    exit();
}
require_once 'db.php';

if (isset($_GET['id']) && isset($_GET['current_status'])) {
    $puppy_id = intval($_GET['id']);
    $current_status = $_GET['current_status'];
    
    // Toggle logic: if available -> make sold. If sold -> make available.
    $new_status = ($current_status === 'available') ? 'sold' : 'available';

    try {
        $sql = "UPDATE puppies SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $puppy_id);
        $stmt->execute();
    } catch(PDOException $e) {
        // Silently fail or log error
    }
}

// Redirect back to the marketplace instantly
header("Location: index.php");
exit();