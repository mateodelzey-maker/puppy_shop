<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content_key'])) {
    $key = trim($_POST['content_key']);
    $value = trim($_POST['content_value'] ?? '');
    $section_anchor = trim($_POST['section_anchor'] ?? '');

    if (!empty($key)) {
        try {
            $sql = "INSERT INTO site_content (content_key, content_value) 
                    VALUES (:key, :val) 
                    ON DUPLICATE KEY UPDATE content_value = :val2";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':key' => $key,
                ':val' => $value,
                ':val2' => $value
            ]);
        } catch(PDOException $e) {}
    }
    
    // Redirect cleanly back to the edited section using anchor positioning
    header("Location: index.php" . (!empty($section_anchor) ? "#" . $section_anchor : ""));
    exit();
}

header("Location: index.php");
exit();