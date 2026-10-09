<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $sql = "SELECT * FROM site_admins WHERE username = :username";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_user'] = $admin['username'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid username or password credentials.";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill out all login parameters.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Gateway Portal</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 360px; }
        h2 { margin-top: 0; color: #2f3640; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ced6e0; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; background: #00a8ff; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        button:hover { background: #0097e6; }
        .err { color: #ff4757; font-size: 0.85rem; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Portal</h2>
        <?php if (!empty($error)): ?>
            <div class="err"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Access Dashboard</button>
        </form>
    </div>
</body>
</html>