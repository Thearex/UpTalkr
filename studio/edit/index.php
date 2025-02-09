<?php
session_start();
require '/var/uptalkr/check-cookie-token.php';
require '/var/uptalkr/updb.php';
require '/var/secure/config.php';

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
$post_id = $_GET['post_id'];

$stmt_post = $conn->prepare("
    SELECT p.post_id, p.user_id, p.title, p.content, p.file, p.created_at, p.views,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = p.post_id) AS likes
    FROM postaukset p
    WHERE p.post_id = ? AND p.user_id = ?
");
$stmt_post->bind_param("ii", $post_id, $user_id);
$stmt_post->execute();
$post = $stmt_post->get_result()->fetch_assoc();

if (!$post) {
    header("Location: https://uptalkr.com/studio");
    exit;
}

if ($post['user_id'] !== $user_id) {
    header("Location: https://uptalkr.com/studio");
    exit;
}

$stmt_likes = $conn->prepare("
    SELECT l.created_at, u.username 
    FROM likes l 
    INNER JOIN users u ON l.user_id = u.id 
    WHERE l.post_id = ?
    ORDER BY l.created_at DESC
    LIMIT 10
");
$stmt_likes->bind_param("i", $post_id);
$stmt_likes->execute();
$likes = $stmt_likes->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_likes_count = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE post_id = ?");
$stmt_likes_count->bind_param("i", $post_id);
$stmt_likes_count->execute();
$total_likes = $stmt_likes_count->get_result()->fetch_assoc()['like_count'];

$stmt_comments = $conn->prepare("
    SELECT k.id, k.kommentti, k.created_at, u.username
    FROM kommentit k
    INNER JOIN users u ON k.user_id = u.id
    WHERE k.postauksen_id = ?
    ORDER BY k.created_at DESC
    LIMIT 10
");
$stmt_comments->bind_param("i", $post_id);
$stmt_comments->execute();
$comments = $stmt_comments->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_comments_count = $conn->prepare("SELECT COUNT(*) as comment_count FROM kommentit WHERE postauksen_id = ?");
$stmt_comments_count->bind_param("i", $post_id);
$stmt_comments_count->execute();
$total_comments = $stmt_comments_count->get_result()->fetch_assoc()['comment_count'];

if (isset($_POST['delete_media'])) {
    $media_url = $post['file'];
    if (!empty($media_url)) {
        $parsed_url = parse_url($media_url);
        parse_str($parsed_url['query'], $params);
        $file_name = $params['filename'];

        $storagebox_username = $webdav_user;
        $storagebox_password = $webdav_pass;
        $storagebox_url = $webdav_server;

        $delete_url = $storagebox_url . '/' . $file_name;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $delete_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_USERPWD, $storagebox_username . ":" . $storagebox_password);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 204) {
            $stmt_update = $conn->prepare("UPDATE postaukset SET file = NULL WHERE post_id = ?");
            $stmt_update->bind_param("i", $post_id);
            $stmt_update->execute();

            header("Location: ?post_id=" . $post_id);
            exit;
        } else {
            echo "Failed to delete the media. HTTP status code: $http_code";
        }
    }
}


if (isset($_POST['edit_post'])) {
    $new_title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
    $new_content = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');
    
    $stmt_update_post = $conn->prepare("UPDATE postaukset SET title = ?, content = ? WHERE post_id = ? AND user_id = ?");
    $stmt_update_post->bind_param("ssii", $new_title, $new_content, $post_id, $user_id);
    $stmt_update_post->execute();
    header("Location: ?post_id=" . $post_id);
    exit;
}


if (isset($_POST['delete_post'])) {
    $stmt_delete_comments = $conn->prepare('DELETE FROM kommentit WHERE postauksen_id = ?');
    $stmt_delete_comments->bind_param('i', $post_id);
    $stmt_delete_comments->execute();

    $stmt_delete_post = $conn->prepare('DELETE FROM postaukset WHERE post_id = ? AND user_id = ?');
    $stmt_delete_post->bind_param('ii', $post_id, $user_id);
    $stmt_delete_post->execute();
    header('Location: studio.php');
    exit;
}


if (isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];
    $stmt_delete_comment = $conn->prepare("DELETE FROM kommentit WHERE id = ?");
    $stmt_delete_comment->bind_param("i", $comment_id);
    $stmt_delete_comment->execute();
    header("Location: ?post_id=" . $post_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpTalkr - Edit Post</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/navbar2.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .header h2 {
            font-size: 28px;
            color: #333;
            text-align: center;
            text-transform: capitalize;
            margin-bottom: 30px;
        }

        .form-group label {
            font-size: 16px;
            font-weight: bold;
            color: #444;
            display: block;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            border-radius: 8px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            box-sizing: border-box;
        }

        .buttons {
            margin-top: 20px;
            text-align: center;
        }

        .buttons button {
            margin: 10px 15px;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .buttons .btn-success {
            background-color: #28a745;
            color: #fff;
        }

        .buttons .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .buttons .btn-info {
            background-color: #17a2b8;
            color: #fff;
        }

        .post-media {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: contain;
            margin-bottom: 20px;
        }

        .stat-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background-color: #f0f2f5;
            padding: 20px;
            border-radius: 12px;
        }

        .stat {
            text-align: center;
            flex: 1;
        }

        .stat h3 {
            font-size: 22px;
            color: #6c5ce7;
        }

        .comments-section, .likes-section {
            margin-top: 40px;
        }

        .comment-container, .like-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f1f3f5;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .likes-chart {
            width: 100%;
            height: 300px;
        }

        .comment-header, .like-header {
            display: flex;
            justify-content: space-between;
        }

        .comment-header span, .like-header span {
            font-weight: bold;
            color: #2d3436;
        }

        .show-more {
            margin-top: 20px;
            text-align: center;
        }

        .show-more button {
            background-color: #6c5ce7;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .show-more button:hover {
            background-color: #5e50cc;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .header h2 {
                font-size: 22px;
            }

            .buttons button {
                font-size: 14px;
            }
        }
.comment-container form .btn-danger {
    background-color: #dc3545;
    color: #fff;
    padding: 6px 12px;
    font-size: 14px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-left: 10px;
}

.comment-container form .btn-danger:hover {
    background-color: #c82333;
}

.comment-header {
    display: flex;
    align-items: center;
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
    <div class="header">
        <h2>Edit Post: <?= htmlspecialchars($post['title']) ?></h2>
    </div>

    <div class="stat-box">
        <div class="stat">
            <h3>Views</h3>
            <p><?= $post['views'] ?></p>
        </div>
        <div class="stat">
            <h3>Likes</h3>
            <p><?= $post['likes'] ?></p>
        </div>
        <div class="stat">
            <h3>Created</h3>
            <p><?= date('Y-m-d', strtotime($post['created_at'])) ?></p>
        </div>
    </div>

    <?php if (!empty($post['file'])): ?>
        <?php $file_extension = strtolower(pathinfo($post['file'], PATHINFO_EXTENSION)); ?>
        <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png'])): ?>
            <img src="<?= htmlspecialchars($post['file']) ?>" alt="Post Image" class="post-media">
        <?php elseif ($file_extension == "mp4"): ?>
            <video class="post-media" controls>
                <source src="<?= htmlspecialchars($post['file']) ?>" type="video/mp4">
            </video>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($post['title']) ?>" required>
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" class="form-control" required><?= htmlspecialchars($post['content']) ?></textarea>
        </div>

        <div class="buttons">
            <button type="submit" name="edit_post" class="btn btn-success">Save Changes</button>
            <button type="submit" name="delete_post" class="btn btn-danger">Delete Post</button>
            <button type="submit" name="delete_media" class="btn btn-info">Remove Media</button>
        </div>
    </form>

    <div class="likes-section">
        <h3>Users Who Liked This Post</h3>
        <?php if (!empty($likes)): ?>
            <ul id="like-list">
                <?php foreach ($likes as $like): ?>
                    <li class="like-container"><?= htmlspecialchars($like['username']) ?> liked at <?= date('Y-m-d H:i', strtotime($like['created_at'])) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($total_likes > 10): ?>
                <div class="show-more">
                    <button onclick="loadMoreLikes()">Näytä lisää</button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>No likes yet.</p>
        <?php endif; ?>
    </div>

    <div class="comments-section">
        <h3>Comments</h3>
        <?php if (!empty($comments)): ?>
            <ul id="comment-list">
                <?php foreach ($comments as $comment): ?>
                    <li class="comment-container">
                        <div class="comment-header">
                            <span><?= htmlspecialchars($comment['username']) ?></span>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                <button type="submit" name="delete_comment" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                        <p><?= htmlspecialchars($comment['kommentti']) ?></p>
                        <p style="font-size: 12px; color: #999;">Posted at: <?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($total_comments > 10): ?>
                <div class="show-more">
                    <button onclick="loadMoreComments()">Näytä lisää</button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>No comments yet.</p>
        <?php endif; ?>
    </div>

    <div class="likes-timeline">
        <h3>Likes over Time</h3>
        <div class="likes-chart">
            <canvas id="likesChart"></canvas>
        </div>
    </div>
</div>

<script>
let likesOffset = 10;
let commentsOffset = 10;

function loadMoreLikes() {
    const likeList = document.getElementById('like-list');
    fetch(`load_more_likes.php?post_id=<?= $post_id ?>&offset=${likesOffset}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                data.forEach(like => {
                    const li = document.createElement('li');
                    li.className = 'like-container';
                    li.innerHTML = `${like.username} liked at ${new Date(like.created_at).toLocaleString()}`;
                    likeList.appendChild(li);
                });
                likesOffset += 10;
            } else {
                document.querySelector('.show-more button').style.display = 'none';
            }
        });
}

function loadMoreComments() {
    const commentList = document.getElementById('comment-list');
    fetch(`load_more_comments.php?post_id=<?= $post_id ?>&offset=${commentsOffset}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                data.forEach(comment => {
                    const li = document.createElement('li');
                    li.className = 'comment-container';
                    li.innerHTML = `<strong>${comment.username}</strong>: ${comment.kommentti} <em>(${new Date(comment.created_at).toLocaleString()})</em>`;
                    commentList.appendChild(li);
                });
                commentsOffset += 10;
            } else {
                document.querySelector('.show-more button').style.display = 'none';
            }
        });
}

var likeData = <?= json_encode(array_column($likes, 'created_at')) ?>;

let likeCounts = {};
likeData.forEach(function(date) {
    let formattedDate = new Date(date).toLocaleDateString();
    if (!likeCounts[formattedDate]) {
        likeCounts[formattedDate] = 1;
    } else {
        likeCounts[formattedDate]++;
    }
});

let labels = Object.keys(likeCounts);
let data = Object.values(likeCounts);

const ctx = document.getElementById('likesChart').getContext('2d');
const likesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Likes over time',
            data: data,
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 2,
            fill: false
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>
