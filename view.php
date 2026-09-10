<?php
require_once 'db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Puppy ID not specified.");
}
$puppy_id = intval($_GET['id']);

// Handle Comment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
    $viewer_name = htmlspecialchars($_POST['viewer_name']);
    $comment_text = htmlspecialchars($_POST['comment_text']);

    if (!empty($viewer_name) && !empty($comment_text)) {
        try {
            $sql_comment = "INSERT INTO comments (puppy_id, viewer_name, comment_text) VALUES (:puppy_id, :viewer_name, :comment_text)";
            $stmt_comment = $conn->prepare($sql_comment);
            $stmt_comment->bindParam(':puppy_id', $puppy_id);
            $stmt_comment->bindParam(':viewer_name', $viewer_name);
            $stmt_comment->bindParam(':comment_text', $comment_text);
            $stmt_comment->execute();
            
            header("Location: index.php?id=" . $puppy_id); // Redirect back to homepage keeping overlay open
            exit();
        } catch(PDOException $e) {
            echo "<p style='color: red;'>Error saving comment: " . $e->getMessage() . "</p>";
        }
    }
}

// Fetch Details
try {
    $sql_puppy = "SELECT * FROM puppies WHERE id = :id";
    $stmt_puppy = $conn->prepare($sql_puppy);
    $stmt_puppy->bindParam(':id', $puppy_id);
    $stmt_puppy->execute();
    $puppy = $stmt_puppy->fetch(PDO::FETCH_ASSOC);

    if (!$puppy) {
        die("Puppy listing not found.");
    }

    $sql_get_comments = "SELECT * FROM comments WHERE puppy_id = :puppy_id ORDER BY created_at DESC";
    $stmt_get_comments = $conn->prepare($sql_get_comments);
    $stmt_get_comments->bindParam(':puppy_id', $puppy_id);
    $stmt_get_comments->execute();
    $comments = $stmt_get_comments->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<style>
    /* Fade-in animation for the background blurring layer */
    .modal-backdrop {
        animation: fadeInBackdrop 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Cinematic scale & slide up animation for the card container */
    .modal-content {
        transform: translateY(40px) scale(0.96);
        opacity: 0;
        animation: slideUpModal 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s forwards;
    }

    /* Keyframes Logic */
    @keyframes fadeInBackdrop {
        from {
            background: rgba(30, 39, 46, 0);
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
        }
        to {
            background: rgba(15, 23, 42, 0.75); /* Darker sleek tone */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
    }

    @keyframes slideUpModal {
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    /* Subtle zoom on the photo inside the modal layout */
    .modal-media img {
        transform: scale(1.08);
        opacity: 0;
        animation: revealImg 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.2s forwards;
    }

    @keyframes revealImg {
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>

<div class="modal-backdrop">
    <div class="modal-content">
        <a href="index.php" class="modal-close-btn">&times;</a>

        <div class="modal-layout">
            <div class="modal-media" style="overflow: hidden; border-radius: 16px 0 0 16px;">
                <img src="<?php echo htmlspecialchars($puppy['image_url']); ?>" alt="Puppy picture">
            </div>
            
            <div class="modal-details">
                <h1 class="modal-title"><?php echo htmlspecialchars($puppy['breed']); ?></h1>
                <div class="modal-price">$<?php echo number_format($puppy['price'], 2); ?></div>
                <div class="modal-meta-tag">⏳ <?php echo $puppy['age_weeks']; ?> Weeks Old</div>
                
                <div class="modal-adoption-card">
                    <h4>✨ Direct Inquiries</h4>
                    <p>📞 Phone/Text: (555) 123-4567<br>✉️ owner@puppyboutique.com</p>
                </div>

                <p class="modal-description"><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($puppy['description'])); ?></p>
                
                <div class="modal-comments-area">
                    <h3>Inquiries & Guestbook</h3>
                    <form class="modal-comment-form" action="view.php?id=<?php echo $puppy_id; ?>" method="POST">
                        <input type="text" name="viewer_name" placeholder="Your Name" required>
                        <textarea name="comment_text" rows="2" placeholder="Ask a question..." required></textarea>
                        <button type="submit" name="submit_comment">Send Message</button>
                    </form>

                    <div class="modal-comments-list">
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="modal-comment-bubble">
                                    <strong><?php echo htmlspecialchars($comment['viewer_name']); ?></strong>
                                    <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #718093; font-style: italic; font-size: 0.85rem;">No questions asked yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>