<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = htmlspecialchars($_POST['customer_name']);
    $rating = intval($_POST['rating']);
    $review_text = htmlspecialchars($_POST['review_text']);
    
    $media_url = null;
    $media_type = null;

    // Handle Media File Upload (Photo or Video)
    if (isset($_FILES['review_media']) && $_FILES['review_media']['error'] == 0) {
        $file = $_FILES['review_media'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $imgExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $vidExtensions = ['mp4', 'webm', 'mov'];
        
        if (in_array($fileExt, $imgExtensions)) {
            $media_type = 'image';
        } elseif (in_array($fileExt, $vidExtensions)) {
            $media_type = 'video';
        }

        if ($media_type !== null) {
            // Allow up to 20MB for videos
            if ($fileSize < 20000000) { 
                $newFileName = uniqid('review_', true) . "." . $fileExt;
                $uploadDirectory = 'uploads/' . $newFileName;
                
                // Ensure uploads directory exists
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }

                if (move_uploaded_file($fileTmpName, $uploadDirectory)) {
                    $media_url = $uploadDirectory; 
                }
            }
        }
    }

    try {
        $sql = "INSERT INTO site_reviews (customer_name, rating, review_text, media_url, media_type) 
                VALUES (:customer_name, :rating, :review_text, :media_url, :media_type)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_name', $customer_name);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':review_text', $review_text);
        $stmt->bindParam(':media_url', $media_url);
        $stmt->bindParam(':media_type', $media_type);
        $stmt->execute();
    } catch(PDOException $e) {
        // Fail silently or handle error gracefully
    }
}

header("Location: index.php");
exit();