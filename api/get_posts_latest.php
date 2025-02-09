<?php
header("Access-Control-Allow-Origin: https://www.uptalkr.com");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
require '/var/uptalkr/updb.php';
$user_id = $_SESSION['userid'];

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Yhteyden muodostaminen epäonnistui: " . mysqli_connect_error());
}

$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;

$sql = "SELECT p.post_id, p.title, p.content, p.file, u.username, p.created_at, u.id AS user_id
        FROM postaukset AS p
        INNER JOIN users AS u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT $offset, 10";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $post_id = $row["post_id"];
        $title = $row["title"];
        $content = $row["content"];
        $file = $row["file"];
        $username = $row["username"];
        $created_at = $row["created_at"];
        $poster_user_id = $row["user_id"];


        $profile_sql = "SELECT profiilikuva FROM profiili WHERE userid = ?";
        $stmt_profile = $conn->prepare($profile_sql);
        $stmt_profile->bind_param('i', $poster_user_id);
        $stmt_profile->execute();
        $result_profile = $stmt_profile->get_result();
        $profile_row = $result_profile->fetch_assoc();
        $profile_picture = $profile_row['profiilikuva'] ?? 'https://uptalkr.com/profile/default-profile.png';

        echo "<div class='post-container' onclick=\"location.href='https://uptalkr.com/post/?post=" . htmlspecialchars($post_id) . "'\">";
        
        echo "<div class='post-header'>";
        echo "<div class='profile-picture'>";
        echo "<img src='" . htmlspecialchars($profile_picture) . "' alt='Profile Picture'>";
        echo "</div>";
        echo "<div>";
        echo "<a href='https://uptalkr.com/profile/?id=" . htmlspecialchars($username) . "' class='username-link'>" . htmlspecialchars($username) . "</a>";
        echo "<span class='post-timestamp'>" . htmlspecialchars($created_at) . "</span>";
        echo "</div>";
        echo "</div>";

        echo "<div class='post-title'>" . htmlspecialchars($title) . "</div>";
        echo "<div class='post-content'>" . htmlspecialchars($content) . "</div>";


        if (!empty($file)) {
            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                echo "<img src='" . htmlspecialchars($file) . "' alt='Post Image' class='post-image'>";
            } elseif ($file_extension == "mp4") {
                echo "<video class='post-video' controls controlsList='nodownload'>
                        <source src='" . htmlspecialchars($file) . "' type='video/mp4'>
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


echo '<button type="button" class="interaction-btn" onclick="likePost(' . htmlspecialchars($post_id) . ', this, event)">';
$like_image = $like ? 'like2.png' : 'like1.png';
echo '<img src="https://uptalkr.com/assets/' . htmlspecialchars($like_image) . '" alt="Like"> ';
echo '<span class="like-count">' . htmlspecialchars($likes_count) . '</span>';
echo '</button>';
        

        echo '<button class="interaction-btn" commentPost(' . htmlspecialchars($post_id) . ');">';
        echo '<img src="https://uptalkr.com/assets/comment.png" alt="Comment"> Comment</button>';
        

echo '<button class="interaction-btn" onclick="event.stopPropagation(); copyPostLink(' . htmlspecialchars($post_id) . ');">';
echo '<img src="https://uptalkr.com/assets/share.png" alt="Share"> Share</button>';

        echo '</div>';
        echo '</div>';
    }
}

mysqli_close($conn);
?>