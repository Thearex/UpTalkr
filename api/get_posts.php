<?php
header("Access-Control-Allow-Origin: https://www.uptalkr.com");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
require '/var/uptalkr/updb.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Yhteyden muodostaminen epäonnistui: " . mysqli_connect_error());
}

$limit = 10;
$old_post_limit = round($limit * 0.20); 
$new_post_limit = round($limit * 0.35);  
$followed_post_limit = round($limit * 0.45);  


$user_id = $_SESSION['userid'];


if (!isset($_SESSION['shown_posts'])) {
    $_SESSION['shown_posts'] = [];
}
$shown_posts = $_SESSION['shown_posts'];


function add_post($row, &$posts, &$shown_posts) {
    if (!isset($posts[$row['post_id']]) && !in_array($row['post_id'], $shown_posts)) {
        $posts[$row['post_id']] = $row;
    }
}


function mark_as_shown($post_id, &$shown_posts) {
    $shown_posts[] = $post_id;
}


$sql_all_post_ids = "SELECT post_id FROM postaukset";
$result_all_post_ids = $conn->query($sql_all_post_ids);
$total_posts_in_db = $result_all_post_ids->num_rows;

if (count($shown_posts) >= $total_posts_in_db) {

    $_SESSION['shown_posts'] = [];
    $shown_posts = [];
}


$posts = [];


$sql_old_posts = "SELECT p.post_id, p.title, p.content, p.file, u.username, p.created_at, u.id AS user_id
                  FROM postaukset AS p
                  INNER JOIN users AS u ON p.user_id = u.id
                  WHERE p.created_at < NOW() - INTERVAL 30 DAY
                  ORDER BY RAND()
                  LIMIT ?";
$stmt_old = $conn->prepare($sql_old_posts);
$stmt_old->bind_param('i', $old_post_limit);
$stmt_old->execute();
$result_old = $stmt_old->get_result();

while ($row = $result_old->fetch_assoc()) {
    add_post($row, $posts, $shown_posts);
}


$sql_new_posts = "SELECT p.post_id, p.title, p.content, p.file, u.username, p.created_at, u.id AS user_id
                  FROM postaukset AS p
                  INNER JOIN users AS u ON p.user_id = u.id
                  WHERE p.created_at >= NOW() - INTERVAL 30 DAY
                  ORDER BY RAND()
                  LIMIT ?";
$stmt_new = $conn->prepare($sql_new_posts);
$stmt_new->bind_param('i', $new_post_limit);
$stmt_new->execute();
$result_new = $stmt_new->get_result();

while ($row = $result_new->fetch_assoc()) {
    add_post($row, $posts, $shown_posts);
}


$sql_followed_posts = "SELECT p.post_id, p.title, p.content, p.file, u.username, p.created_at, u.id AS user_id
                       FROM postaukset AS p
                       INNER JOIN users AS u ON p.user_id = u.id
                       LEFT JOIN Followers AS f ON f.user_id = u.id
                       WHERE f.follower_id = ? AND p.created_at >= NOW() - INTERVAL 30 DAY
                       ORDER BY RAND()
                       LIMIT ?";
$stmt_followed = $conn->prepare($sql_followed_posts);
$stmt_followed->bind_param('ii', $user_id, $followed_post_limit);
$stmt_followed->execute();
$result_followed = $stmt_followed->get_result();

while ($row = $result_followed->fetch_assoc()) {
    add_post($row, $posts, $shown_posts);
}


$total_posts = count($posts);
if ($total_posts < $limit) {
    $remaining_posts = $limit - $total_posts;
    $sql_random_posts = "SELECT p.post_id, p.title, p.content, p.file, u.username, p.created_at, u.id AS user_id
                         FROM postaukset AS p
                         INNER JOIN users AS u ON p.user_id = u.id
                         ORDER BY RAND()
                         LIMIT ?";
    $stmt_random = $conn->prepare($sql_random_posts);
    $stmt_random->bind_param('i', $remaining_posts);
    $stmt_random->execute();
    $result_random = $stmt_random->get_result();

    while ($row = $result_random->fetch_assoc()) {
        add_post($row, $posts, $shown_posts);
    }
}


$posts = array_values($posts);
shuffle($posts);


$posts = array_slice($posts, 0, 10);


foreach ($posts as $row) {
    mark_as_shown($row['post_id'], $shown_posts);
}


$_SESSION['shown_posts'] = $shown_posts;


foreach ($posts as $row) {
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
    

    echo '<button class="interaction-btn" onclick="commentPost(' . htmlspecialchars($post_id) . ');">';
    echo '<img src="https://uptalkr.com/assets/comment.png" alt="Comment"> Comment</button>';
    

    echo '<button class="interaction-btn" onclick="event.stopPropagation(); copyPostLink(' . htmlspecialchars($post_id) . ');">';
    echo '<img src="https://uptalkr.com/assets/share.png" alt="Share"> Share</button>';

    echo '</div>'; 
    echo '</div>'; 

mysqli_close($conn);
}
?>
