<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $breed = htmlspecialchars($_POST['breed']);
    $price = $_POST['price'];
    $age_weeks = $_POST['age_weeks'];
    $description = htmlspecialchars($_POST['description']);
    
    // Default image
    $image_url = "uploads/default.jpg"; 

    if (isset($_FILES['puppy_image']) && $_FILES['puppy_image']['error'] == 0) {
        $file = $_FILES['puppy_image'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if (in_array($fileExt, $allowedExtensions)) {
            if ($fileSize < 5000000) {
                $newFileName = uniqid('puppy_', true) . "." . $fileExt;
                $uploadDirectory = 'uploads/' . $newFileName;
                if (move_uploaded_file($fileTmpName, $uploadDirectory)) {
                    $image_url = $uploadDirectory; 
                }
            }
        }
    }

    try {
        // We still keep the columns in the DB, but we pass your permanent info automatically!
        $seller_name = "Our Puppy Boutique"; // Change to your business name
        $seller_contact = "(555) 123-4567 / owner@puppyboutique.com"; // Change to your contact

        $sql = "INSERT INTO puppies (breed, price, age_weeks, description, seller_name, seller_contact, image_url) 
                VALUES (:breed, :price, :age_weeks, :description, :seller_name, :seller_contact, :image_url)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':breed', $breed);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':age_weeks', $age_weeks);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':seller_name', $seller_name);
        $stmt->bindParam(':seller_contact', $seller_contact);
        $stmt->bindParam(':image_url', $image_url);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>Puppy listed successfully!</p>";
        }
    } catch(PDOException $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Inventory</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f9f9f9; }
        .form-container { max-width: 500px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        .nav-btn { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #00a8ff; }
        button { background-color: #ff4757; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; width: 100%; font-weight: bold; }
        button:hover { background-color: #e84118; }
    </style>
</head>
<body>

<div class="form-container">
    <a href="index.php" class="nav-btn">← Back to Dashboard</a>
    <h2>Add a New Puppy to Inventory</h2>
    
    <form action="create.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Breed:</label>
            <input type="text" name="breed" required>
        </div>
        <div class="form-group">
            <label>Price ($):</label>
            <input type="number" step="0.01" name="price" required>
        </div>
        <div class="form-group">
            <label>Age (Weeks):</label>
            <input type="number" name="age_weeks" required>
        </div>
        <div class="form-group">
            <label>Puppy Photo:</label>
            <input type="file" name="puppy_image" accept="image/*" required>
        </div>
        <div class="form-group">
            <label>Description / Temperament:</label>
            <textarea name="description" rows="4" required></textarea>
        </div>
        <button type="submit">Publish Listing</button>
    </form>
</div>

</body>
</html>