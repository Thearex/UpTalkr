<?php
require '/var/uptalkr/check-cookie-token.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $loginLink = '<a href="/login">Login</a>';
} else {
  $loginLink = '
  <div class="dropdown">
    <a class="profile-button">Profile</a>
    <div class="dropdown-content">
      <a href="/profile">Profile</a>
      <a href="/settings">Settings</a>
      <a href="/messages">Messages</a>
      <a href="https://uptalkr.com/studio">Studio</a>
      <a href="/logout">Logout</a>
    </div>
  </div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
    <title>UpTalkr - Search</title>
    <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/navbar2.css">
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        color: #000; 
    }

    .container {
        max-width: 800px;
        background-color: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        text-align: left;
        width: 40%; 
        margin-top: 120px;
        overflow-y: auto; 
    }

    h1 {
        font-size: 24px;
        color: #333;
        margin-bottom: 20px;
    }

    .results {
        margin-bottom: 20px;
    }

    .username {
        font-weight: bold;
        margin-bottom: 5px;
        margin-top: 5px;
    }

    .user-profile {
        display: flex;
        align-items: center;
        justify-content: center; 
        margin-bottom: 10px; 
    }

    .profile-img {
        width: 50px; 
        height: 50px; 
        border-radius: 50%;
        margin-right: 10px;
    }

    .username a,
    .post-title a {
        text-decoration: none;
    }

    .divider {
        width: 100%;
        margin: 20px 0;
        border-top: 1px solid #ccc;
    }

    .scrollable-posts {
        max-height: calc(90vh - 120px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .post-container {
        background-color: #fff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        max-width: 500px;
        margin: 50px auto 10px auto;
        transition: transform 0.3s ease;
        cursor: pointer;
        border: 1px solid #000;
    }

    .post-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .profile-picture img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .username {
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }

    .post-timestamp {
        font-size: 12px;
        color: #999;
        margin-left: 10px;
    }

    .post-title {
        font-size: 22px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }

    .post-content {
        font-size: 16px;
        color: #555;
        margin-bottom: 10px;
    }

    .post-image {
        width: 100%;
        border-radius: 10px;
        max-width: 500px;
        max-height: 500px;
        object-fit: cover;
    }

    .post-video {
        width: 100%;
        border-radius: 10px;
        max-width: 500px;
        max-height: 500px;
        object-fit: cover;
    }

    .post-interactions {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
    }

    .interaction-btn {
        display: flex;
        align-items: center;
        background: none;
        border: none;
        font-size: 16px;
        color: #888;
        cursor: pointer;
    }

    .interaction-btn img {
        width: 20px;
        height: 20px;
        margin-right: 5px;
    }

    .interaction-btn:hover {
        color: #333;
    }

    .username-link {
        color: #333; 
        text-decoration: none;
        font-weight: bold;
    }

    .username-link:hover {
        color: #007bff;
        text-decoration: none;
    }
</style>
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
                    <img src="https://uptalkr.com/assets/post2.png" alt="Luo!" class="icon">
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
    <div class="center-content">
        <?php
        require '/var/uptalkr/updb.php';

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Yhteys ep�onnistui: " . $conn->connect_error);
        }

        $hakusana = $_GET['s'];

        $stmt_users = $conn->prepare("SELECT * FROM users WHERE username LIKE ?");
        $search_term_user = '%' . $hakusana . '%';
        $stmt_users->bind_param("s", $search_term_user);

        $stmt_users->execute();
        $result_users = $stmt_users->get_result();

        if ($result_users->num_rows > 0) {
            echo "<div class='results'>";
            echo "<h2>Search:</h2>";
            while ($row_user = $result_users->fetch_assoc()) {
                $user_id = $row_user["id"];
                $username = $row_user["username"];

                $stmt_profile = $conn->prepare("SELECT profiilikuva FROM profiili WHERE userid = ?");
                $stmt_profile->bind_param("i", $user_id);
                $stmt_profile->execute();
                $result_profile = $stmt_profile->get_result();

                if ($result_profile->num_rows > 0) {
                    $row_profile = $result_profile->fetch_assoc();
                    $profile_img = $row_profile["profiilikuva"];
                } else {
                    $profile_img = "https://uptalkr.com/profile/default-profile.png";
                }
                $stmt_profile->close();
                echo "<div class='user-profile'><img src='" . htmlspecialchars($profile_img, ENT_QUOTES, 'UTF-8') . "' alt='Profile Picture' class='profile-img'><p class='username'><a href='https://uptalkr.com/profile/?id=" . urlencode($username) . "'>" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</a></p></div>";
            }
            echo "</div>";
            echo "<div class='divider'></div>"; 
        }

        $stmt_posts = $conn->prepare("SELECT * FROM postaukset WHERE title LIKE ?");
        $search_term_post = '%' . $hakusana . '%';
        $stmt_posts->bind_param("s", $search_term_post);

        $stmt_posts->execute();
        $result_posts = $stmt_posts->get_result();

        if ($result_posts->num_rows > 0) {
            echo "<div class='results'>";
            if ($result_users->num_rows == 0) {
                echo "<h2>Search</h2>";
            }
            while ($row_post = $result_posts->fetch_assoc()) {
                $post_id = $row_post["post_id"];
                $user_id = $row_post["user_id"];
                $title = $row_post["title"];
                $content = $row_post["content"];                
                $file = $row_post["file"];
                $created_at = $row_post["created_at"];

                $stmt_username = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt_username->bind_param("i", $user_id);
                $stmt_username->execute();
                $result_username = $stmt_username->get_result();
                $username_row = $result_username->fetch_assoc();
                $username = $username_row["username"] ?? 'Unknown';

                $profile_sql = "SELECT profiilikuva FROM profiili WHERE userid = ?";
                $stmt_profile = $conn->prepare($profile_sql);
                $stmt_profile->bind_param('i', $user_id);
                $stmt_profile->execute();
                $result_profile = $stmt_profile->get_result();
                $profile_row = $result_profile->fetch_assoc();
                $profile_picture = $profile_row['profiilikuva'] ?? 'https://uptalkr.com/profile/default-profile.png';

                echo "<div class='post-container' onclick=\"location.href='https://uptalkr.com/post/?post=" . htmlspecialchars($post_id, ENT_QUOTES, 'UTF-8') . "'\">";
                
                echo "<div class='post-header'>";
                echo "<div class='profile-picture'>";
                echo "<img src='" . htmlspecialchars($profile_picture, ENT_QUOTES, 'UTF-8') . "' alt='Profile Picture'>";
                echo "</div>";
                echo "<div>";
                echo "<a href='https://uptalkr.com/profile/?id=" . urlencode($username) . "' class='username-link'>" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</a>";
                echo "<span class='post-timestamp'>" . htmlspecialchars($created_at, ENT_QUOTES, 'UTF-8') . "</span>";
                echo "</div>";
                echo "</div>";

                echo "<div class='post-title'>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</div>";
                echo "<div class='post-content'>" . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . "</div>";

                // Image or video
                if (!empty($file)) {
                    $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                        echo "<img src='" . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "' alt='Post Image' class='post-image'>";
                    } elseif ($file_extension == "mp4") {
                        echo "<video class='post-video' controls controlsList='nodownload'>
                                <source src='" . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "' type='video/mp4'>
                              </video>";
                    }
                }

                echo '<div class="post-interactions">';

                $stmt_like = $conn->prepare('SELECT * FROM likes WHERE post_id = ? AND user_id = ?');
                $stmt_like->bind_param('ii', $post_id, $user_id);
                $stmt_like->execute();
                $result_like = $stmt_like->get_result();
                $like = $result_like->fetch_assoc();

                $stmt_likes_count = $conn->prepare('SELECT COUNT(*) as count FROM likes WHERE post_id = ?');
                $stmt_likes_count->bind_param('i', $post_id);
                $stmt_likes_count->execute();
                $result_likes_count = $stmt_likes_count->get_result();
                $likes_count = $result_likes_count->fetch_assoc()['count'];

                echo '<button type="button" class="interaction-btn" onclick="likePost(' . htmlspecialchars($post_id, ENT_QUOTES, 'UTF-8') . ', this, event)">';
                $like_image = $like ? 'like2.png' : 'like1.png';
                echo '<img src="https://uptalkr.com/assets/' . htmlspecialchars($like_image, ENT_QUOTES, 'UTF-8') . '" alt="Like"> ';
                echo '<span class="like-count">' . htmlspecialchars($likes_count, ENT_QUOTES, 'UTF-8') . '</span>';
                echo '</button>';
                

                echo '<button class="interaction-btn" onclick="commentPost(' . htmlspecialchars($post_id, ENT_QUOTES, 'UTF-8') . ');">';
                echo '<img src="https://uptalkr.com/assets/comment.png" alt="Comment"> Comment</button>';
                

                echo '<button class="interaction-btn" onclick="event.stopPropagation(); copyPostLink(' . htmlspecialchars($post_id, ENT_QUOTES, 'UTF-8') . ');">';
                echo '<img src="https://uptalkr.com/assets/share.png" alt="Share"> Share</button>';

                echo '</div>';
                echo '</div>'; 
            }
            echo "</div>";
        }

        if ($result_users->num_rows == 0 && $result_posts->num_rows == 0) {
            echo "<p class='no-results'>No results</p>";
        }


        $stmt_users->close();
        $stmt_posts->close();
        $conn->close();
        ?>
    </div>
</div>
</body>
</html>