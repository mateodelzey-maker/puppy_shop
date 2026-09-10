<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

require_once 'db.php';

// Fetch all customizable text pieces from database
$content = [];
try {
    $stmt_content = $conn->query("SELECT content_key, content_value FROM site_content");
    while ($row = $stmt_content->fetch(PDO::FETCH_ASSOC)) {
        $content[$row['content_key']] = $row['content_value'];
    }
} catch(PDOException $e) {}

// Fallback helper function if table strings are missing
function get_text($key, $fallback, $content) {
    return isset($content[$key]) ? htmlspecialchars($content[$key]) : $fallback;
}

// Handle Comment form submission inside the single-page script context
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment']) && isset($_GET['id'])) {
    $puppy_id = intval($_GET['id']);
    $viewer_name = trim($_POST['viewer_name'] ?? '');
    $comment_text = trim($_POST['comment_text'] ?? '');
    
    if (!empty($viewer_name) && !empty($comment_text)) {
        try {
            $sql_ins = "INSERT INTO comments (puppy_id, viewer_name, comment_text, created_at) VALUES (:puppy_id, :name, :txt, NOW())";
            $stmt_ins = $conn->prepare($sql_ins);
            $stmt_ins->execute([
                ':puppy_id' => $puppy_id,
                ':name' => $viewer_name,
                ':txt' => $comment_text
            ]);
            header("Location: index.php?id=" . $puppy_id . "#inventory");
            exit;
        } catch(PDOException $e) {}
    }
}

try {
    // If admin is logged in, show everything. If not, show only approved reviews.
    if ($is_admin) {
        $sql_rev = "SELECT * FROM site_reviews ORDER BY is_approved ASC, created_at DESC";
    } else {
        $sql_rev = "SELECT * FROM site_reviews WHERE is_approved = 1 ORDER BY created_at DESC";
    }
    $stmt_rev = $conn->query($sql_rev);
    $reviews = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $reviews = [];
}

try {
    $sql = "SELECT * FROM puppies ORDER BY created_at DESC";
    $stmt = $conn->query($sql);
    $puppies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Could not fetch data: " . $e->getMessage());
}

$show_modal = false;
$modal_puppy = null;
$comments = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $puppy_id = intval($_GET['id']);
    try {
        $sql_puppy = "SELECT * FROM puppies WHERE id = :id";
        $stmt_puppy = $conn->prepare($sql_puppy);
        $stmt_puppy->bindParam(':id', $puppy_id);
        $stmt_puppy->execute();
        $modal_puppy = $stmt_puppy->fetch(PDO::FETCH_ASSOC);

        if ($modal_puppy) {
            $show_modal = true;
            
            $sql_comments = "SELECT * FROM comments WHERE puppy_id = :puppy_id ORDER BY created_at DESC";
            $stmt_comments = $conn->prepare($sql_comments);
            $stmt_comments->bindParam(':puppy_id', $puppy_id);
            $stmt_comments->execute();
            $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DreamDog Boutique | Premium Purebred Companions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-cream: #fcfbfa;
            --surface-card: #ffffff;
            --text-brown: #33261d;
            --text-muted: #736356;
            --primary-gold: #e5a93b;
            --primary-gold-hover: #c98e28;
            --secondary-pink: #e04870;
            --secondary-pink-hover: #c23358;
            --border-light: #eae3d8;
            --accent-soft: #f4eee3;
            --shadow-sm: 0 4px 12px rgba(51, 38, 29, 0.04);
            --shadow-md: 0 12px 28px rgba(51, 38, 29, 0.08);
            --shadow-lg: 0 22px 45px rgba(51, 38, 29, 0.12);
        }

        html { scroll-behavior: smooth; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-cream); 
            margin: 0; 
            padding: 0; 
            color: var(--text-brown); 
            line-height: 1.6; 
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, .brand-font { font-family: 'Playfair Display', serif; }

        /* --- CONTACT TOP BAR --- */
        .contact-top-bar { 
            background-color: var(--text-brown); 
            color: var(--bg-cream); 
            font-size: 0.82rem; 
            font-weight: 500; 
            padding: 8px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            letter-spacing: 0.3px;
        }
        .contact-top-left, .contact-top-right { display: flex; gap: 24px; align-items: center; }
        .contact-link-item { 
            color: rgba(252, 251, 250, 0.85); 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            transition: color 0.2s ease; 
        }
        .contact-link-item:hover { color: var(--primary-gold); }

        /* --- STICKY NAVIGATION HEADER --- */
        .site-header { 
            position: sticky; 
            top: 0; 
            background: rgba(252, 251, 250, 0.92); 
            backdrop-filter: blur(14px); 
            border-bottom: 1px solid var(--border-light); 
            z-index: 1000; 
            padding: 16px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: var(--shadow-sm);
        }
        .header-logo { 
            font-size: 1.7rem; 
            font-weight: 700; 
            color: var(--text-brown); 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            letter-spacing: -0.5px;
        }
        .header-nav { display: flex; gap: 32px; align-items: center; }
        .header-nav a { 
            text-decoration: none; 
            color: var(--text-brown); 
            font-weight: 600; 
            font-size: 0.95rem; 
            transition: all 0.2s ease; 
            position: relative;
        }
        .header-nav a:hover { color: var(--secondary-pink); }

        /* --- IN-LINE ADMINISTRATIVE EDIT BOXES --- */
        .inline-edit-form { 
            background: #fffdfa; 
            padding: 18px; 
            border-radius: 12px; 
            margin: 15px auto; 
            max-width: 650px; 
            text-align: left; 
            border: 2px dashed #d4c5b3; 
            color: var(--text-brown); 
            box-shadow: var(--shadow-sm);
        }
        .inline-edit-form input[type="text"], .inline-edit-form textarea { 
            width: 100%; 
            padding: 10px 12px; 
            font-family: inherit; 
            margin-bottom: 10px; 
            border: 1px solid var(--border-light); 
            border-radius: 8px; 
            box-sizing: border-box; 
            background: #ffffff;
        }
        .inline-save-btn { 
            background: #25b863; 
            color: white; 
            border: none; 
            padding: 8px 20px; 
            border-radius: 20px; 
            font-weight: 700; 
            cursor: pointer; 
            font-size: 0.8rem; 
            transition: background 0.2s ease;
        }
        .inline-save-btn:hover { background: #1f9e54; }

        /* --- HERO BANNER --- */
        .hero-banner { 
            background: linear-gradient(180deg, rgba(51, 38, 29, 0.55) 0%, rgba(51, 38, 29, 0.35) 100%), 
                        url('<?php echo get_text('hero_background_url', 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?q=80&w=1920', $content); ?>') 
                        center/cover no-repeat; 
            color: white; 
            padding: 160px 20px; 
            text-align: center; 
            position: relative;
        }
        .hero-banner h1 { 
            font-size: 3.8rem; 
            margin: 0 0 18px 0; 
            text-shadow: 0 3px 12px rgba(0,0,0,0.4); 
            letter-spacing: -0.5px;
        }
        .hero-banner p { 
            font-size: 1.3rem; 
            max-width: 700px; 
            margin: 0 auto 36px auto; 
            color: rgba(255, 255, 255, 0.95); 
            text-shadow: 0 2px 8px rgba(0,0,0,0.4); 
            font-weight: 400;
        }
        .hero-cta { 
            background-color: var(--primary-gold); 
            color: var(--text-brown); 
            padding: 16px 38px; 
            border-radius: 50px; 
            text-decoration: none; 
            font-weight: 800; 
            display: inline-block; 
            box-shadow: 0 8px 25px rgba(229, 169, 59, 0.45); 
            transition: all 0.25 ease; 
            letter-spacing: 0.3px;
        }
        .hero-cta:hover { 
            transform: translateY(-3px) scale(1.02); 
            background-color: var(--primary-gold-hover);
            box-shadow: 0 12px 30px rgba(229, 169, 59, 0.55);
        }

        /* --- ABOUT US SECTION --- */
        .about-section { 
            padding: 110px 20px; 
            max-width: 850px; 
            margin: 0 auto; 
            text-align: center; 
            position: relative; 
        }
        .about-section::before { 
            content: '🐾'; 
            position: absolute; 
            left: 2%; 
            top: 15%; 
            font-size: 6rem; 
            opacity: 0.04; 
        }
        .about-section h2 { font-size: 2.8rem; margin-bottom: 12px; color: var(--text-brown); }
        .gold-underline { 
            width: 70px; 
            height: 4px; 
            background: var(--primary-gold); 
            margin: 0 auto 32px auto; 
            border-radius: 4px; 
        }
        .about-section p { font-size: 1.15rem; color: var(--text-muted); line-height: 1.85; }

        /* --- SERVICES CARD GRID --- */
        .services-section { background: var(--accent-soft); padding: 100px 40px; }
        .services-container { max-width: 1200px; margin: 0 auto; }
        .services-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); 
            gap: 30px; 
            margin-top: 50px; 
        }
        .service-card { 
            background: var(--surface-card); 
            padding: 40px 30px; 
            border-radius: 20px; 
            text-align: center; 
            border: 1px solid var(--border-light); 
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); 
        }
        .service-card:hover { 
            transform: translateY(-8px); 
            box-shadow: var(--shadow-lg); 
            border-color: rgba(229, 169, 59, 0.4);
        }
        .service-icon { 
            font-size: 3rem; 
            margin-bottom: 18px; 
            display: inline-block; 
            background: var(--bg-cream);
            padding: 15px;
            border-radius: 50%;
            width: 70px;
            height: 70px;
            line-height: 70px;
            box-shadow: inset 0 0 0 1px var(--border-light);
        }
        .service-card h3 { margin: 0 0 12px 0; font-size: 1.4rem; color: var(--text-brown); }
        .service-card p { font-size: 0.95rem; color: var(--text-muted); margin: 0; line-height: 1.6; }

        /* --- INVENTORY CONTAINER --- */
        .main-container { max-width: 1200px; margin: 90px auto; padding: 0 20px; }
        .toolbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 45px; 
            border-bottom: 2px solid var(--border-light); 
            padding-bottom: 22px; 
        }
        .section-title { font-size: 2.4rem; font-weight: 700; margin: 0; color: var(--text-brown); }
        .admin-btn { 
            background-color: var(--text-brown); 
            color: white; 
            padding: 10px 22px; 
            text-decoration: none; 
            border-radius: 30px; 
            font-size: 0.88rem; 
            font-weight: 700; 
            transition: background 0.2s ease;
        }
        .admin-btn:hover { background-color: #221812; }

        /* --- PUPPY CARDS --- */
        .puppy-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 35px; }
        .puppy-card { 
            background: var(--surface-card); 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: var(--shadow-sm); 
            transition: all 0.3s ease; 
            display: flex; 
            flex-direction: column; 
            border: 1px solid var(--border-light); 
            position: relative; 
        }
        .puppy-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
        .puppy-card.status-sold img { filter: grayscale(50%); opacity: 0.7; }
        .puppy-card.status-sold .puppy-price { text-decoration: line-through; color: var(--text-muted) !important; }
        .img-wrapper { position: relative; width: 100%; height: 270px; overflow: hidden; background-color: #f2ece0; }
        .puppy-card img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .puppy-card:hover img { transform: scale(1.05); }
        .badge-status { 
            position: absolute; 
            top: 16px; 
            right: 16px; 
            padding: 6px 14px; 
            border-radius: 30px; 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.6px; 
            z-index: 2; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .badge-available { background: var(--primary-gold); color: var(--text-brown); }
        .badge-sold { background: var(--text-brown); color: white; }
        .card-body { padding: 28px; flex-grow: 1; display: flex; flex-direction: column; }
        .card-header-meta { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
        .breed-name { font-size: 1.55rem; font-weight: 700; margin: 0; color: var(--text-brown); }
        .puppy-price { font-size: 1.45rem; color: var(--secondary-pink); font-weight: 800; margin: 0; }
        .puppy-age { font-size: 0.85rem; color: var(--text-muted); font-weight: 700; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.6px; }
        .puppy-desc { color: var(--text-brown); font-size: 0.95rem; margin: 0 0 24px 0; opacity: 0.88; line-height: 1.6; }

        .card-actions { display: flex; flex-direction: column; gap: 10px; margin-top: auto; }
        .action-row { display: flex; gap: 10px; width: 100%; }
        .btn-ui { 
            text-align: center; 
            padding: 12px 18px; 
            text-decoration: none; 
            border-radius: 30px; 
            font-size: 0.88rem; 
            font-weight: 700; 
            box-sizing: border-box; 
            transition: all 0.2s ease; 
        }
        .btn-main { background-color: var(--secondary-pink); color: white; flex: 2; }
        .btn-main:hover { background-color: var(--secondary-pink-hover); }
        .btn-alt { background-color: var(--bg-cream); color: var(--text-brown); border: 1px solid var(--border-light); flex: 1; }
        .btn-alt:hover { background-color: var(--border-light); }
        .btn-toggle { width: 100%; border: 1px dashed var(--text-muted); color: var(--text-brown); background: transparent; cursor: pointer; }
        .btn-toggle:hover { background: #fff2f5; border-color: var(--secondary-pink); color: var(--secondary-pink); }
        .btn-delete { background-color: #c0392b; color: white; border: none; cursor: pointer; margin-top: 4px; }
        .btn-delete:hover { background-color: #a93226; }

        /* --- REVIEWS / TESTIMONIALS SECTION --- */
        .reviews-section { background: var(--surface-card); padding: 100px 20px; border-top: 1px solid var(--border-light); }
        .reviews-inner { max-width: 1200px; margin: 0 auto; }
        .reviews-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 60px; margin-top: 45px; }
        @media (max-width: 992px) { .reviews-layout { grid-template-columns: 1fr; gap: 40px; } }
        .review-form-card { 
            background: var(--bg-cream); 
            padding: 38px; 
            border-radius: 22px; 
            border: 1px solid var(--border-light); 
            height: fit-content; 
            box-shadow: var(--shadow-sm);
        }
        .review-form-card h3 { margin: 0 0 20px 0; font-size: 1.7rem; color: var(--text-brown); }
        .review-form-card input, .review-form-card textarea, .review-form-card select { 
            width: 100%; 
            padding: 12px 14px; 
            margin-bottom: 18px; 
            border: 1px solid var(--border-light); 
            border-radius: 10px; 
            box-sizing: border-box; 
            font-family: inherit; 
            background: white; 
            color: var(--text-brown); 
        }
        .btn-submit-review { 
            background-color: var(--text-brown); 
            color: white; 
            border: none; 
            padding: 15px; 
            border-radius: 30px; 
            font-weight: 800; 
            cursor: pointer; 
            width: 100%; 
            font-size: 0.95rem; 
            transition: background 0.2s ease;
        }
        .btn-submit-review:hover { background-color: #221812; }
        .reviews-feed { display: flex; flex-direction: column; gap: 25px; }
        .review-card { 
            background: var(--bg-cream); 
            padding: 32px; 
            border-radius: 22px; 
            border: 1px solid var(--border-light); 
            display: flex; 
            gap: 25px; 
            position: relative; 
            box-shadow: var(--shadow-sm);
        }
        .review-card::before { 
            content: '“'; 
            position: absolute; 
            top: 15px; 
            right: 30px; 
            font-family: 'Playfair Display', serif; 
            font-size: 4.5rem; 
            color: var(--primary-gold); 
            opacity: 0.25; 
            line-height: 1; 
        }
        .review-text-side { flex: 2; }
        .review-media-side { flex: 1; max-width: 180px; display: flex; align-items: center; justify-content: center; }
        .review-media-side img, .review-media-side video { width: 100%; height: 135px; object-fit: cover; border-radius: 14px; box-shadow: var(--shadow-sm); }
        .review-author { font-size: 1.15rem; margin: 0; font-weight: 700; color: var(--text-brown); }
        .review-stars { color: var(--primary-gold); font-size: 1rem; margin: 6px 0 14px 0; }
        .review-body { color: var(--text-brown); opacity: 0.9; font-size: 0.98rem; margin: 0; font-style: italic; line-height: 1.65; }

        /* --- OVERLAY MODALS --- */
        .modal-backdrop { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100vw; 
            height: 100vh; 
            background: rgba(51, 38, 29, 0.65); 
            backdrop-filter: blur(10px); 
            z-index: 99999; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 20px; 
            box-sizing: border-box; 
        }
        .modal-content { 
            background: var(--bg-cream); 
            width: 100%; 
            max-width: 960px; 
            max-height: 90vh; 
            border-radius: 26px; 
            position: relative; 
            box-shadow: 0 35px 70px rgba(0,0,0,0.3); 
            overflow-y: auto; 
            border: 1px solid var(--border-light); 
        }
        .modal-close-btn { position: absolute; top: 18px; right: 28px; font-size: 2.6rem; text-decoration: none; color: var(--text-brown); z-index: 10; font-weight: 300; }
        .modal-layout { display: grid; grid-template-columns: 1.1fr 0.9fr; }
        .modal-media img { width: 100%; height: 100%; min-height: 480px; object-fit: cover; }
        .modal-details { padding: 42px; background: white; }
        .modal-title { margin: 0 0 8px 0; font-size: 2.5rem; color: var(--text-brown); }
        .modal-price { font-size: 1.8rem; font-weight: 800; color: var(--secondary-pink); margin-bottom: 14px; }
        .modal-meta-tag { display: inline-block; background: var(--bg-cream); padding: 6px 18px; border-radius: 30px; font-size: 0.85rem; margin-bottom: 25px; font-weight: 800; color: var(--text-brown); text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-adoption-card { background: #fffdf8; padding: 22px; border-radius: 18px; border: 1px solid var(--primary-gold); margin-bottom: 25px; }
        .modal-adoption-card h4 { margin: 0 0 8px 0; color: var(--text-brown); font-size: 1.15rem; }
        .modal-adoption-card p { margin: 0; font-size: 0.95rem; color: var(--text-muted); line-height: 1.55; }
        .modal-description { font-size: 1rem; color: var(--text-brown); margin-bottom: 30px; line-height: 1.7; }
        .modal-comments-area { border-top: 2px solid var(--bg-cream); padding-top: 25px; }
        .modal-comment-form input, .modal-comment-form textarea { width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid var(--border-light); border-radius: 8px; box-sizing: border-box; background: var(--bg-cream); font-family: inherit; }
        .modal-comment-form button { background: var(--text-brown); color: white; border: none; padding: 10px 20px; border-radius: 30px; font-weight: 700; cursor: pointer; font-size: 0.85rem; }
        .modal-comments-list { margin-top: 20px; max-height: 220px; overflow-y: auto; padding-right: 5px; }
        .modal-comment-bubble { background: var(--bg-cream); padding: 14px; border-radius: 12px; margin-bottom: 10px; font-size: 0.9rem; border-left: 4px solid var(--primary-gold); }

        .site-footer { background: var(--text-brown); color: white; text-align: center; padding: 45px 20px; font-size: 0.9rem; border-top: 1px solid rgba(255,255,255,0.08); }
        .site-footer p { margin: 0; opacity: 0.75; }
    </style>
</head>
<body>

    <!-- DYNAMIC CONTACT TOP BAR -->
    <div class="contact-top-bar">
        <div class="contact-top-left">
            <span class="contact-link-item">📞 <?php echo get_text('contact_phone', '(555) 123-4567', $content); ?></span>
            <a href="mailto:<?php echo get_text('contact_email', 'concierge@dreamdogboutique.com', $content); ?>" class="contact-link-item">✉️ <?php echo get_text('contact_email', 'concierge@dreamdogboutique.com', $content); ?></a>
        </div>
        <div class="contact-top-right">
            <a href="<?php echo get_text('contact_facebook', '#', $content); ?>" target="_blank" class="contact-link-item">📘 Facebook</a>
            <a href="<?php echo get_text('contact_instagram', '#', $content); ?>" target="_blank" class="contact-link-item">📸 Instagram</a>
        </div>
    </div>

    <!-- STICKY SITE HEADER -->
    <header class="site-header">
        <a href="index.php" class="header-logo"><span>🐾</span> DreamDog</a>
        <nav class="header-nav">
            <a href="#about">About Us</a>
            <a href="#services">Services</a>
            <a href="#inventory">Available Breeds</a>
            <a href="#reviews">Success Stories</a>
            <?php if ($is_admin): ?>
                <a href="create.php" class="admin-btn" style="color:white; padding:8px 16px;">+ Add Puppy</a>
                <a href="logout.php" class="admin-btn" style="background:var(--secondary-pink); color:white; padding:8px 16px;">Logout</a>
            <?php else: ?>
                <a href="login.php" class="admin-btn" style="background:transparent; border:1px solid var(--text-brown); color:var(--text-brown); padding:8px 16px;">Staff Portal</a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- HERO SECTION -->
    <section class="hero-banner" id="hero">
    <h1><?php echo get_text('hero_title', 'Excellence In Purebred Companions', $content); ?></h1>
    <p><?php echo get_text('hero_subtitle', 'Ethically bred, comprehensively health-tested, and raised inside a loving home environment ready for your family adoption.', $content); ?></p>
    <a href="#inventory" class="hero-cta"><?php echo get_text('hero_btn_text', 'Explore Available Puppies', $content); ?></a>

    <?php if ($is_admin): ?>
        <div class="inline-edit-form">
            <strong style="color:var(--primary-gold); display:block; margin-bottom:8px;">🖼️ Upload New Background Image From Computer:</strong>
            <form action="update_hero_bg.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="hero_bg_image" accept="image/*" required style="color: white; margin-bottom: 10px;">
                <button type="submit" class="inline-save-btn" style="display:block;">Upload and Apply Background</button>
            </form>
            
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.2); margin: 15px 0;">
            
            <strong style="color:var(--primary-gold);">📝 Edit Hero Texts:</strong>
            <form action="update_content.php" method="POST" style="margin-top:10px;">
                <input type="hidden" name="content_key" value="hero_title">
                <input type="hidden" name="section_anchor" value="hero">
                <input type="text" name="content_value" value="<?php echo get_text('hero_title', 'Excellence In Purebred Companions', $content); ?>">
                <button type="submit" class="inline-save-btn">Save Title</button>
            </form>
            <form action="update_content.php" method="POST" style="margin-top:10px;">
                <input type="hidden" name="content_key" value="hero_subtitle">
                <input type="hidden" name="section_anchor" value="hero">
                <textarea name="content_value" rows="2"><?php echo get_text('hero_subtitle', '', $content); ?></textarea>
                <button type="submit" class="inline-save-btn">Save Subtitle</button>
            </form>
        </div>
    <?php endif; ?>
</section>

    <!-- ABOUT US SECTION -->
    <section id="about" class="about-section">
        <h2><?php echo get_text('about_title', 'Our Breeding Philosophy', $content); ?></h2>
        <div class="gold-underline"></div>
        <p><?php echo get_text('about_text', 'At DreamDog Boutique, we believe a puppy is not just an addition; they are a transformative cornerstone to your household. Every bloodline we look after is scrupulously screened for genetic health, structural correctness, and affectionate temperaments.', $content); ?></p>

        <?php if ($is_admin): ?>
            <div class="inline-edit-form">
                <strong style="color:var(--text-brown);">📝 Edit About Section Wording:</strong>
                <form action="update_content.php" method="POST" style="margin-top:10px;">
                    <input type="hidden" name="content_key" value="about_title">
                    <input type="hidden" name="section_anchor" value="about">
                    <input type="text" name="content_value" value="<?php echo get_text('about_title', 'Our Breeding Philosophy', $content); ?>">
                    <button type="submit" class="inline-save-btn">Save Section Title</button>
                </form>
                <form action="update_content.php" method="POST" style="margin-top:10px;">
                    <input type="hidden" name="content_key" value="about_text">
                    <input type="hidden" name="section_anchor" value="about">
                    <textarea name="content_value" rows="4"><?php echo get_text('about_text', '', $content); ?></textarea>
                    <button type="submit" class="inline-save-btn">Save Paragraph Copy</button>
                </form>
            </div>
        <?php endif; ?>
    </section>

    <!-- BUSINESS SERVICES CARD GRID -->
    <section id="services" class="services-section">
        <div class="services-container">
            <div style="text-align: center;">
                <h2><?php echo get_text('services_title', 'Our Extended Services', $content); ?></h2>
                <div class="gold-underline"></div>
                
                <?php if ($is_admin): ?>
                    <div class="inline-edit-form">
                        <form action="update_content.php" method="POST">
                            <input type="hidden" name="content_key" value="services_title">
                            <input type="hidden" name="section_anchor" value="services">
                            <input type="text" name="content_value" value="<?php echo get_text('services_title', 'Our Extended Services', $content); ?>">
                            <button type="submit" class="inline-save-btn">Update Services Header</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="services-grid">
                <!-- CARD 1 -->
                <div class="service-card">
                    <span class="service-icon"><?php echo get_text('service1_icon', '📜', $content); ?></span>
                    <h3><?php echo get_text('service1_title', 'Litter Registration', $content); ?></h3>
                    <p><?php echo get_text('service1_text', 'Official, verifiable pedigree certification documenting purebred multi-generational family trees seamlessly.', $content); ?></p>
                    
                    <?php if ($is_admin): ?>
                        <div class="inline-edit-form" style="margin-top: 15px; font-size: 0.8rem;">
                            <form action="update_content.php" method="POST">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service1_icon">
                                <label>Emoji:</label> <input type="text" name="content_value" value="<?php echo get_text('service1_icon', '📜', $content); ?>" style="width:40px; text-align:center;">
                                <button type="submit" class="inline-save-btn">Fix Icon</button>
                            </form>
                            <form action="update_content.php" method="POST" style="margin-top:5px;">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service1_title">
                                <input type="text" name="content_value" value="<?php echo get_text('service1_title', '', $content); ?>">
                                <button type="submit" class="inline-save-btn">Save Title</button>
                            </form>
                            <form action="update_content.php" method="POST" style="margin-top:5px;">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service1_text">
                                <textarea name="content_value" rows="2"><?php echo get_text('service1_text', '', $content); ?></textarea>
                                <button type="submit" class="inline-save-btn">Save Description</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CARD 2 -->
                <div class="service-card">
                    <span class="service-icon"><?php echo get_text('service2_icon', '🏥', $content); ?></span>
                    <h3><?php echo get_text('service2_title', 'Health Verification', $content); ?></h3>
                    <p><?php echo get_text('service2_text', 'Comprehensive veterinary reports, core dynamic immunizations, and dynamic deworming guidelines included.', $content); ?></p>
                    
                    <?php if ($is_admin): ?>
                        <div class="inline-edit-form" style="margin-top: 15px; font-size: 0.8rem;">
                            <form action="update_content.php" method="POST">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service2_icon">
                                <label>Emoji:</label> <input type="text" name="content_value" value="<?php echo get_text('service2_icon', '🏥', $content); ?>" style="width:40px; text-align:center;">
                                <button type="submit" class="inline-save-btn">Fix Icon</button>
                            </form>
                            <form action="update_content.php" method="POST" style="margin-top:5px;">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service2_title">
                                <input type="text" name="content_value" value="<?php echo get_text('service2_title', '', $content); ?>">
                                <button type="submit" class="inline-save-btn">Save Title</button>
                            </form>
                            <form action="update_content.php" method="POST" style="margin-top:5px;">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service2_text">
                                <textarea name="content_value" rows="2"><?php echo get_text('service2_text', '', $content); ?></textarea>
                                <button type="submit" class="inline-save-btn">Save Description</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CARD 3 -->
                <div class="service-card">
                    <span class="service-icon"><?php echo get_text('service3_icon', '✂️', $content); ?></span>
                    <h3><?php echo get_text('service3_title', 'Puppy Grooming', $content); ?></h3>
                    <p><?php echo get_text('service3_text', 'Early behavioral sensory handling introductory classes to coat care, nail trimming, and bathing styling sessions.', $content); ?></p>
                    
                    <?php if ($is_admin): ?>
                        <div class="inline-edit-form" style="margin-top: 15px; font-size: 0.8rem;">
                            <form action="update_content.php" method="POST">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service3_icon">
                                <label>Emoji:</label> <input type="text" name="content_value" value="<?php echo get_text('service3_icon', '✂️', $content); ?>" style="width:40px; text-align:center;">
                                <button type="submit" class="inline-save-btn">Fix Icon</button>
                            </form>
                            <form action="update_content.php" method="POST" style="margin-top:5px;">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service3_title">
                                <input type="text" name="content_value" value="<?php echo get_text('service3_title', '', $content); ?>">
                                <button type="submit" class="inline-save-btn">Save Title</button>
                            </form>
                            <form action="update_content.php" method="POST" style="margin-top:5px;">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service3_text">
                                <textarea name="content_value" rows="2"><?php echo get_text('service3_text', '', $content); ?></textarea>
                                <button type="submit" class="inline-save-btn">Save Description</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CARD 4 -->
                <div class="service-card">
                    <span class="service-icon"><?php echo get_text('service4_icon', '✈️', $content); ?></span>
                    <h3><?php echo get_text('service4_title', 'Safe Pet Transport', $content); ?></h3>
                    <p><?php echo get_text('service4_text', 'Certified dedicated custom travel coordination options engineered to deliver your puppy safely anywhere nationwide.', $content); ?></p>
                    
                    <?php if ($is_admin): ?>
                        <div class="inline-edit-form" style="margin-top: 15px; font-size: 0.8rem;">
                            <form action="update_content.php" method="POST">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service4_icon">
                                <label>Emoji:</label> <input type="text" name="content_value" value="<?php echo get_text('service4_icon', '✈️', $content); ?>" style="width:40px; text-align:center;">
                                <button type="submit" class="inline-save-btn">Fix Icon</button>
                            </form>
                            <form action="update_content.php" method="POST" style="margin-top:5px;">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service4_title">
                                <input type="text" name="content_value" value="<?php echo get_text('service4_title', '', $content); ?>">
                                <button type="submit" class="inline-save-btn">Save Title</button>
                            </form>
                            <form action="update_content.php" method="POST" style="margin-top:5px;">
                                <input type="hidden" name="section_anchor" value="services">
                                <input type="hidden" name="content_key" value="service4_text">
                                <textarea name="content_value" rows="2"><?php echo get_text('service4_text', '', $content); ?></textarea>
                                <button type="submit" class="inline-save-btn">Save Description</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- LIVE INVENTORY WRAPPER GRID -->
    <main id="inventory" class="main-container">
        <div class="toolbar">
            <h2 class="section-title">Available Nursery Inventory</h2>
            <div>
                <?php if ($is_admin): ?>
                    <span style="font-size:0.85rem; font-weight:700; color:var(--secondary-pink); text-transform:uppercase;">👑 Admin Mode Active</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="puppy-grid">
            <?php if (count($puppies) > 0): ?>
                <?php foreach ($puppies as $puppy): 
                    $status = $puppy['status'] ?? 'available';
                ?>
                    <article class="puppy-card status-<?php echo $status; ?>">
                        <div class="img-wrapper">
                            <img src="<?php echo htmlspecialchars($puppy['image_url']); ?>" alt="Premium Companion Puppy">
                            <?php if ($status === 'available'): ?>
                                <span class="badge-status badge-available">Available</span>
                            <?php else: ?>
                                <span class="badge-status badge-sold">Adopted 🎉</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <div class="card-header-meta">
                                <h3 class="breed-name"><?php echo htmlspecialchars($puppy['breed']); ?></h3>
                                <p class="puppy-price">$<?php echo number_format($puppy['price'], 2); ?></p>
                            </div>
                            <div class="puppy-age">⏳ <?php echo $puppy['age_weeks']; ?> Weeks Old</div>
                            <p class="puppy-desc"><?php echo htmlspecialchars($puppy['description']); ?></p>
                            
                            <div class="card-actions">
                                <a href="index.php?id=<?php echo $puppy['id']; ?>#inventory" class="btn-ui btn-main" style="width:100%;">View Records & Inquire</a>
                                
                                <?php if ($is_admin): ?>
                                    <div class="action-row" style="margin-top: 8px;">
                                        <a href="update.php?id=<?php echo $puppy['id']; ?>" class="btn-ui btn-alt">Modify Details</a>
                                    </div>
                                    <a href="toggle_status.php?id=<?php echo $puppy['id']; ?>&current_status=<?php echo $status; ?>" class="btn-ui btn-toggle" style="margin-top: 4px;">
                                        <?php echo ($status === 'available') ? '🤝 Toggle Status: Sold' : '🔄 Toggle Status: Active'; ?>
                                    </a>
                                    <?php if ($status === 'sold'): ?>
                                        <a href="delete_puppy.php?id=<?php echo $puppy['id']; ?>" class="btn-ui btn-delete" onclick="return confirm('Are you certain?');">🗑️ Permanent Expunge</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;"><p>No wonderful puppies listed within the system database registry currently.</p></div>
            <?php endif; ?>
        </div>
    </main>

    <!-- LIVE CUSTOMER STORIES SECTION -->
    <section id="reviews" class="reviews-section">
        <div class="reviews-inner">
            <div style="text-align: center;">
                <h2 style="font-size: 2.2rem; margin: 0 0 10px 0;">Success Stories & Reviews</h2>
                <div class="gold-underline"></div>
            </div>
            
            <div class="reviews-layout">
                <div class="review-form-card">
                    <h3>Share Your Experience</h3>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin-top:-10px; margin-bottom:15px;">* Submissions are queued for administrator safety review verification.</p>
                    <form action="add_review.php" method="POST" enctype="multipart/form-data">
                        <input type="text" name="customer_name" placeholder="Your Full Name" required>
                        <select name="rating" required>
                            <option value="5">⭐⭐⭐⭐⭐ (5/5 Excellence Rating)</option>
                            <option value="4">⭐⭐⭐⭐ (4/5 Highly Satisfied)</option>
                            <option value="3">⭐⭐⭐ (3/5 Average Experience)</option>
                            <option value="2">⭐⭐ (2/5 Suboptimal)</option>
                            <option value="1">⭐ (1/5 Dissatisfied)</option>
                        </select>
                        <textarea name="review_text" rows="4" placeholder="Tell us about your lovely puppy companion..." required></textarea>
                        <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:6px; color:var(--text-brown); text-transform: uppercase;">Attach Companion File Assets:</label>
                        <input type="file" name="review_media" accept="image/*,video/*">
                        <button type="submit" class="btn-submit-review">Publish Verified Review</button>
                    </form>
                </div>

                <div class="reviews-feed">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): 
                            $approved = intval($review['is_approved'] ?? 0);
                        ?>
                            <div class="review-card" style="<?php echo ($approved === 0) ? 'border: 2px dashed #e67e22; background: #fffaf5;' : ''; ?>">
                                <div class="review-text-side">
                                    <?php if ($is_admin): ?>
                                        <div style="margin-bottom: 8px;">
                                            <?php if ($approved === 0): ?>
                                                <span style="background:#e67e22; color:white; font-size:0.7rem; font-weight:bold; padding:3px 8px; border-radius:10px; text-transform:uppercase;">⏳ Pending Approval</span>
                                            <?php else: ?>
                                                <span style="background:#2ed573; color:white; font-size:0.7rem; font-weight:bold; padding:3px 8px; border-radius:10px; text-transform:uppercase;">✅ Visible Live</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <h4 class="review-author"><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                    <div class="review-stars">
                                        <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                    </div>
                                    <p class="review-body">"<?php echo nl2br(htmlspecialchars($review['review_text'])); ?>"</p>

                                    <?php if ($is_admin): ?>
                                        <div style="margin-top:15px; display:flex; gap:10px; align-items:center;">
                                            <?php if ($approved === 0): ?>
                                                <a href="moderate_review.php?action=approve&id=<?php echo $review['id']; ?>" class="inline-save-btn" style="background:#2ed573; text-decoration:none; padding: 5px 12px;">Approve & Publish</a>
                                            <?php endif; ?>
                                            <a href="moderate_review.php?action=delete&id=<?php echo $review['id']; ?>" class="inline-save-btn" style="background:#ba2525; text-decoration:none; padding: 5px 12px;" onclick="return confirm('Permanently delete this client review?');">Delete</a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($review['media_url'])): ?>
                                    <div class="review-media-side">
                                        <?php if ($review['media_type'] === 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($review['media_url']); ?>" alt="Customer verified puppy">
                                        <?php elseif ($review['media_type'] === 'video'): ?>
                                            <video src="<?php echo htmlspecialchars($review['media_url']); ?>" controls preload="metadata"></video>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state"><p>No public reviews posted yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER BLOCK WITH ADMIN CONTACT CONTROLS -->
    <footer class="site-footer" id="footer-section">
        <p style="margin-bottom: 15px;">
            <strong>Connect with Us:</strong> 
            Phone: <?php echo get_text('contact_phone', '(555) 123-4567', $content); ?> | 
            Email: <?php echo get_text('contact_email', 'concierge@dreamdogboutique.com', $content); ?>
        </p>
        
        <?php if ($is_admin): ?>
            <div class="inline-edit-form" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3); max-width: 750px;">
                <strong style="color:var(--primary-gold); display:block; margin-bottom:10px;">⚙️ Edit Global Contact Information & Social Network Links:</strong>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <form action="update_content.php" method="POST">
                        <input type="hidden" name="content_key" value="contact_phone">
                        <input type="hidden" name="section_anchor" value="footer-section">
                        <label style="font-size:0.75rem; color:white;">Contact Number:</label>
                        <input type="text" name="content_value" value="<?php echo get_text('contact_phone', '', $content); ?>">
                        <button type="submit" class="inline-save-btn">Save Phone</button>
                    </form>
                    <form action="update_content.php" method="POST">
                        <input type="hidden" name="content_key" value="contact_email">
                        <input type="hidden" name="section_anchor" value="footer-section">
                        <label style="font-size:0.75rem; color:white;">Contact Email Address:</label>
                        <input type="text" name="content_value" value="<?php echo get_text('contact_email', '', $content); ?>">
                        <button type="submit" class="inline-save-btn">Save Email</button>
                    </form>
                    <form action="update_content.php" method="POST">
                        <input type="hidden" name="content_key" value="contact_facebook">
                        <input type="hidden" name="section_anchor" value="footer-section">
                        <label style="font-size:0.75rem; color:white;">Facebook Account Link URL:</label>
                        <input type="text" name="content_value" value="<?php echo get_text('contact_facebook', '', $content); ?>">
                        <button type="submit" class="inline-save-btn">Save Facebook</button>
                    </form>
                    <form action="update_content.php" method="POST">
                        <input type="hidden" name="content_key" value="contact_instagram">
                        <input type="hidden" name="section_anchor" value="footer-section">
                        <label style="font-size:0.75rem; color:white;">Instagram Profile Link URL:</label>
                        <input type="text" name="content_value" value="<?php echo get_text('contact_instagram', '', $content); ?>">
                        <button type="submit" class="inline-save-btn">Save Instagram</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <p style="margin-top:20px;">&copy; <?php echo date('Y'); ?> DreamDog Premium Boutique Inc. All rights explicitly reserved.</p>
    </footer>

    <!-- INTERACTIVE PROFILE DETAILS MODAL OVERLAY -->
    <?php if ($show_modal && $modal_puppy): 
        $modal_status = $modal_puppy['status'] ?? 'available';
    ?>
        <div class="modal-backdrop" id="puppyModal">
            <div class="modal-content">
                <a href="index.php#inventory" class="modal-close-btn">&times;</a>
                <div class="modal-layout">
                    <div class="modal-media">
                        <img src="<?php echo htmlspecialchars($modal_puppy['image_url']); ?>" alt="Detailed Profile Look">
                    </div>
                    <div class="modal-details">
                        <h1 class="modal-title"><?php echo htmlspecialchars($modal_puppy['breed']); ?></h1>
                        <div class="modal-price" style="<?php echo ($modal_status === 'sold') ? 'text-decoration: line-through; color: var(--text-muted);' : ''; ?>">
                            $<?php echo number_format($modal_puppy['price'], 2); ?>
                        </div>
                        <div class="modal-meta-tag">⏳ Verification Status: <?php echo $modal_puppy['age_weeks']; ?> Weeks Old</div>
                        
                        <?php if ($modal_status === 'available'): ?>
                            <div class="modal-adoption-card">
                                <h4>✨ Direct Acquisition Inquiries</h4>
                                <p>Our staff is standing by to assist with reservation coordination:<br>
                                <strong>📞 Line:</strong> <?php echo get_text('contact_phone', '(555) 123-4567', $content); ?><br>
                                <strong>✉️ Registry:</strong> <?php echo get_text('contact_email', 'concierge@dreamdogboutique.com', $content); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="modal-adoption-card" style="background: var(--bg-cream); border-color: var(--text-muted);">
                                <h4 style="color: var(--text-brown);">❤️ Adopted Securely</h4>
                                <p style="color: var(--text-brown); opacity: 0.85;">This magnificent purebred companion has already found their lifelong family home framework.</p>
                            </div>
                        <?php endif; ?>

                        <p class="modal-description"><strong>Pedigree & Biography Profile Description:</strong><br><?php echo nl2br(htmlspecialchars($modal_puppy['description'])); ?></p>
                        
                        <div class="modal-comments-area">
                            <h3>Nursery Public Guestbook</h3>
                            <form class="modal-comment-form" action="index.php?id=<?php echo $modal_puppy['id']; ?>" method="POST">
                                <input type="text" name="viewer_name" placeholder="Your Verified Name" required>
                                <textarea name="comment_text" rows="2" placeholder="Submit public registry inquiry notes..." required></textarea>
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
                                    <p style="color: var(--text-muted); font-style: italic; font-size: 0.85rem;">No historical inquiry entries written on this listing yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.getElementById('puppyModal').addEventListener('click', function(e) {
                if(e.target === this) { window.location.href = 'index.php#inventory'; }
            });
        </script>
    <?php endif; ?>
</body>
</html>