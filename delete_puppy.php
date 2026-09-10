<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Kick them out immediately if they aren't authorized
    header("Location: index.php");
    exit();
}

require_once 'db.php';

if (isset($_GET['id'])) {
    $puppy_id = intval($_GET['id']);

    try {
        // 1. Get the image URL first so we can delete the file from the uploads folder
        $img_sql = "SELECT image_url FROM puppies WHERE id = :id";
        $img_stmt = $conn->prepare($img_sql);
        $img_stmt->bindParam(':id', $puppy_id);
        $img_stmt->execute();
        $puppy = $img_stmt->fetch(PDO::FETCH_ASSOC);

        if ($puppy && !empty($puppy['image_url'])) {
            // Delete physical file if it exists locally
            if (file_exists($puppy['image_url'])) {
                unlink($puppy['image_url']);
            }
        }

        // 2. Delete linked comments first to avoid database foreign key constraint errors
        $comment_sql = "DELETE FROM comments WHERE puppy_id = :id";
        $comment_stmt = $conn->prepare($comment_sql);
        $comment_stmt->bindParam(':id', $puppy_id);
        $comment_stmt->execute();

        // 3. Delete the puppy record
        $sql = "DELETE FROM puppies WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $puppy_id);
        $stmt->execute();

    } catch(PDOException $e) {
        // Handle or log error quietly
    }
}

// Redirect straight back to the main layout dashboard
header("Location: index.php");
exit();