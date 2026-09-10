<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Block non-admins from hitting this script
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("Unauthorized administrative access level error.");
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['hero_bg_image'])) {
    $file = $_FILES['hero_bg_image'];
    
    // Validate file errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $file['tmp_name'];
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed image types verification
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            // Give the image a clean, randomized unique name to prevent layout cache sticking
            $new_file_name = "hero_bg_" . time() . "." . $file_ext;
            $upload_target = "uploads/" . $new_file_name;
            
            // Move file to the uploads directory
            if (move_uploaded_file($file_tmp, $upload_target)) {
                try {
                    // Update the image string value in your site configuration table
                    $sql = "UPDATE site_content SET content_value = :bg_url WHERE content_key = 'hero_background_url'";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':bg_url' => $upload_target]);
                    
                    // Redirect safely straight back to the hero block view on index.php
                    header("Location: index.php#hero");
                    exit;
                } catch (PDOException $e) {
                    die("Database update error: " . $e->getMessage());
                }
            } else {
                die("Failed to move uploaded file to destination directory.");
            }
        } else {
            die("Invalid image file type extension format detected.");
        }
    } else {
        die("An error occurred during file transfer package transmission.");
    }
} else {
    header("Location: index.php");
    exit;
}