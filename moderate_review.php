<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Guardrail: Reject unauthenticated actions
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("Access denied. Administrative authorization required.");
}

require_once 'db.php';

$action = $_GET['action'] ?? '';
$review_id = intval($_GET['id'] ?? 0);

if ($review_id > 0) {
    if ($action === 'approve') {
        try {
            $sql = "UPDATE site_reviews SET is_approved = 1 WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $review_id]);
        } catch(PDOException $e) {}
    } 
    elseif ($action === 'delete') {
        try {
            // Optional cleanup step: look up media file asset tracking path to clear storage disk space
            $stmt_file = $conn->prepare("SELECT media_url FROM site_reviews WHERE id = :id");
            $stmt_file->execute([':id' => $review_id]);
            $row = $stmt_file->fetch(PDO::FETCH_ASSOC);
            
            if ($row && !empty($row['media_url']) && file_exists($row['media_url'])) {
                @unlink($row['media_url']); // erase image asset from uploads directory safely
            }

            // Delete entry rows
            $sql = "DELETE FROM site_reviews WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $review_id]);
        } catch(PDOException $e) {}
    }
}

// Redirect back cleanly to reviews section view
header("Location: index.php#reviews");
exit;