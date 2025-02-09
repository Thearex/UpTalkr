<?php
session_start();
require '/var/uptalkr/check-cookie-token.php';
require '/var/uptalkr/updb.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $loginLink = '<a href="/login">Login</a>';
} else {
  $loginLink = '
  <div class="dropdown">
    <a class="profile-button">Profile</a>
    <div class="dropdown-content">
      <a href="https://uptalkr.com/profile">Profile</a>
      <a href="https://uptalkr.com/settings">Settings</a>
      <a href="https://uptalkr.com/messages">Messages</a>
      <a href="https://uptalkr.com/studio">Studio</a>
      <a href="https://uptalkr.com/logout">Logout</a>
    </div>
  </div>';
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['userid'];

$stmt_posts = $conn->prepare("
    SELECT p.post_id, p.title, p.created_at, p.views, 
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = p.post_id) AS likes 
    FROM postaukset p 
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt_posts->bind_param("i", $user_id);
$stmt_posts->execute();
$posts = $stmt_posts->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_totals = $conn->prepare("
    SELECT SUM(views) as total_views, 
           (SELECT COUNT(*) FROM likes WHERE post_id IN (SELECT post_id FROM postaukset WHERE user_id = ?)) as total_likes
    FROM postaukset
    WHERE user_id = ?
");
$stmt_totals->bind_param("ii", $user_id, $user_id);
$stmt_totals->execute();
$totals = $stmt_totals->get_result()->fetch_assoc();

$stmt_followers = $conn->prepare("SELECT COUNT(*) as total_followers FROM Followers WHERE user_id = ?");
$stmt_followers->bind_param("i", $user_id);
$stmt_followers->execute();
$followers = $stmt_followers->get_result()->fetch_assoc()['total_followers'];

$stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc()['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpTalkr Studio</title>
    <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/navbar2.css">
    <link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
    <style>
        body {
            font-family: "Circular Std Medium", Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 100px auto;
            padding: 20px;
        }

        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .stat-box {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            width: 30%;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-10px);
            background-color: #f9f9f9;
        }

        .stat-box h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-box p {
            margin: 10px 0;
            font-size: 48px;
            font-weight: bold;
            color: #1a73e8;
        }

        .post-container {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .post-container:hover {
            transform: translateY(-5px);
            background-color: #f0f0f0;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .post-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .post-info {
            font-size: 16px;
            color: #777;
        }

        .stat-box:nth-child(2) {
            animation: glow 2s infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 20px #1a73e8;
            }
            to {
                box-shadow: 0 0 40px #ff4500;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                align-items: center;
            }

            .stat-box {
                width: 90%;
                margin-bottom: 20px;
            }

            .post-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .post-title {
                font-size: 20px;
            }

            .post-info {
                font-size: 14px;
            }

            .container {
                padding: 10px;
                margin-top: 50px;
            }
        }


.bottom-left {
    position: fixed;
    bottom: 1px;
    left: 20px;
    font-size: 12px;
    color: #555;
    text-align: left; 
}

.bottom-left a {
    color: #007bff;
    text-decoration: none;
    margin: 3px 0;
    display: block;
    transition: color 0.2s ease;
}

.bottom-left a:hover {
    color: #0056b3;
    text-decoration: underline;
}


@media (max-width: 1518px) {
    .bottom-left {
        display: none;
    }
}

    </style>
</head>
<body>

<nav>
  <div class="nav-logo">
    <a href="https://uptalkr.com">
      <img src="https://uptalkr.com/assets/logo.png" alt="Logo">
    </a>
  </div>
  <div class="nav-links">
    <div class="nav-item">
      <form class="search-form" action="https://uptalkr.com/api/haku.php" method="GET">
        <input type="text" name="s" placeholder="Search" onkeydown="handleKeyDown(event)">
      </form>
    </div>
    <div class="nav-item">
      <div class="icon">
        <a href="https://uptalkr.com/create">
          <img src="https://uptalkr.com/assets/post2.png" alt="Create!" class="icon">
          <span class="icon-text">Create!</span>
        </a>
      </div>
    </div>
    <p>&nbsp;</p>
    <div class="nav-item">
      <div class="login">
        <?php echo $loginLink; ?>
      </div>
    </div>
    <div class="nav-item">
      <a></a>
    </div>
  </div>
</nav>


<div class="container">
    <h1><?= htmlspecialchars($user) ?>'s UpChannel</h1>
    <h3>Welcome to your UpStudio! Here you can manage your posts and track your success.</h3>

    <div class="stats-container">
        <div class="stat-box">
            <h2>Total Views</h2>
            <p><?= $totals['total_views'] ?></p>
        </div>
        <div class="stat-box">
            <h2>Total Likes</h2>
            <p><?= $totals['total_likes'] ?></p>
        </div>
        <div class="stat-box">
            <h2>Followers</h2>
            <p><?= $followers ?></p>
        </div>
    </div>

    <h2>Your Posts</h2>
    <?php foreach ($posts as $post): ?>
        <div class="post-container" onclick="window.location.href='edit/?post_id=<?= htmlspecialchars($post['post_id']) ?>'">
            <div class="post-header">
                <span class="post-title"><?= htmlspecialchars($post['title']) ?></span>
                <span class="post-info">Views: <?= $post['views'] ?> | Likes: <?= $post['likes'] ?> | Created: <?= $post['created_at'] ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<p class="bottom-left">
        <a href="https://uptalkr.com/report">Report</a>
        <a href="https://uptalkr.com/leaderboard/">Leaderboard</a>
        <a href="https://uptalkr.com/privacy-policy/">Privacy Policy</a>
        <a href="https://uptalkr.com/terms-of-service/">Terms of Service</a>
        <a href="https://uptalkr.com/community-guidelines/">Community Guidelines</a>

  &copy; uptalkr.com 2024
</p> 
</body>
</html>

<?php
$conn->close();
?>
