<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Kick them out immediately if they aren't authorized
    header("Location: index.php");
    exit();
}
require_once 'db.php';

// 1. Get the Puppy ID from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Puppy ID not specified.");
}
$puppy_id = intval($_GET['id']);

// 2. Fetch current puppy data to populate the form and know the existing image path
try {
    $sql_fetch = "SELECT * FROM puppies WHERE id = :id";
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bindParam(':id', $puppy_id);
    $stmt_fetch->execute();
    $puppy = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$puppy) {
        die("Puppy listing not found.");
    }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// 3. If the form is submitted, process the changes
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $breed = htmlspecialchars($_POST['breed']);
    $price = $_POST['price'];
    $age_weeks = $_POST['age_weeks'];
    $description = htmlspecialchars($_POST['description']);
    
    // Default to the existing image path currently stored in the DB
    $image_url = $puppy['image_url']; 

    // Check if the user selected a NEW file
    if (isset($_FILES['puppy_image']) && $_FILES['puppy_image']['error'] == 0) {
        $file = $_FILES['puppy_image'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if (in_array($fileExt, $allowedExtensions)) {
            if ($fileSize < 5000000) { // 5MB Limit
                
                $newFileName = uniqid('puppy_', true) . "." . $fileExt;
                $uploadDirectory = 'uploads/' . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $uploadDirectory)) {
                    
                    // OPTIONAL CLEANUP: Delete the old image file if it exists and isn't a default placeholder
                    if (!empty($puppy['image_url']) && file_exists($puppy['image_url']) && $puppy['image_url'] != 'uploads/default.jpg') {
                        unlink($puppy['image_url']); 
                    }
                    
                    // Overwrite our variable with the new path
                    $image_url = $uploadDirectory;
                } else {
                    echo "<p style='color: red;'>Failed to move uploaded file.</p>";
                }
            } else {
                echo "<p style='color: red;'>File is too large! Maximum size is 5MB.</p>";
            }
        } else {
            echo "<p style='color: red;'>Invalid file type!</p>";
        }
    }

    try {
        // Update database including the image_url field
        $sql_update = "UPDATE puppies 
                       SET breed = :breed, price = :price, age_weeks = :age_weeks, description = :description, image_url = :image_url 
                       WHERE id = :id";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindParam(':breed', $breed);
        $stmt_update->bindParam(':price', $price);
        $stmt_update->bindParam(':age_weeks', $age_weeks);
        $stmt_update->bindParam(':description', $description);
        $stmt_update->bindParam(':image_url', $image_url);
        $stmt_update->bindParam(':id', $puppy_id);
        
        if ($stmt_update->execute()) {
            header("Location: index.php");
            exit();
        }
    } catch(PDOException $e) {
        echo "<p style='color: red;'>Error updating listing: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Puppy Listing</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f9f9f9; }
        .form-container { max-width: 500px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .current-img-preview { width: 100px; height: 100px; object-fit: cover; display: block; margin-top: 5px; border-radius: 4px; border: 1px solid #ddd; }
        .btn-container { display: flex; gap: 10px; margin-top: 20px; }
        button { background-color: #fbc531; color: #2f3640; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; font-weight: bold; flex: 1; }
        button:hover { background-color: #e1b12c; }
        .cancel-btn { background-color: #dcdde1; color: #2f3640; text-align: center; text-decoration: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; flex: 1; }
        .cancel-btn:hover { background-color: #c2c3c7; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Puppy Listing</h2>
    
    <form action="update.php?id=<?php echo $puppy_id; ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Breed:</label>
            <input type="text" name="breed" value="<?php echo htmlspecialchars($puppy['breed']); ?>" required>
        </div>
        <div class="form-group">
            <label>Price ($):</label>
            <input type="number" step="0.01" name="price" value="<?php echo $puppy['price']; ?>" required>
        </div>
        <div class="form-group">
            <label>Age (Weeks):</label>
            <input type="number" name="age_weeks" value="<?php echo $puppy['age_weeks']; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Current Photo:</label>
            <img src="<?php echo $puppy['image_url']; ?>" class="current-img-preview" alt="Current puppy layout">
            
            <label style="margin-top: 15px;">Upload New Photo (Leave blank to keep current):</label>
            <input type="file" name="puppy_image" accept="image/*">
        </div>

        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" rows="4" required><?php echo htmlspecialchars($puppy['description']); ?></textarea>
        </div>
        <div class="btn-container">
            <button type="submit">Save Changes</button>
            <a href="index.php" class="cancel-btn">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>