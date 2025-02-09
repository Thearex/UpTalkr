<link rel="icon" href="../assets/logo.png" type="image/x-icon">

<?php
session_start();
require '/var/uptalkr/updb.php';
require '/var/uptalkr/check-cookie-token.php';

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


if (isset($_GET['post'])) {
  $post_id = intval($_GET['post']);
} else {
  header("Location: https://uptalkr.com");
  exit();
}

$conn = mysqli_connect($servername, $username, $password, $dbname);


if (!$conn) {
  die("Database connection failed: " . mysqli_connect_error());
}


$session_key = 'uptalkr-view-' . $post_id;


if (!isset($_SESSION[$session_key])) {

    $update_views_stmt = $conn->prepare('UPDATE postaukset SET views = views + 1 WHERE post_id = ?');
    $update_views_stmt->bind_param('i', $post_id);
    $update_views_stmt->execute();


    $_SESSION[$session_key] = time();
}


$stmt = $conn->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();
$likes_count = $result->fetch_row()[0];


$user_id = $_SESSION['userid'];

$stmt = $conn->prepare('SELECT * FROM likes WHERE post_id = ? AND user_id = ?');
$stmt->bind_param('ii', $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$like = $result->fetch_assoc(); 

$like_image = $like ? 'like2.png' : 'like1.png';


$image_path = '';
$stmt = $conn->prepare('SELECT file FROM postaukset WHERE post_id = ?');
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $image_name = $row['file'];
  
  if (!empty($image_name) && strpos($image_name, 'lol') === false) {
    $image_path = "$image_name";
  }
}


if (isset($_GET['post'])) {
  $post_id = intval($_GET['post']);


  $sql = "SELECT postaukset.*, users.username FROM postaukset JOIN users ON postaukset.user_id = users.id WHERE post_id='$post_id'";
  $result = mysqli_query($conn, $sql);

  $row = array(); 
  if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
  } else {
    header("Location: https://uptalkr.com");
    exit();
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="language" content="en">
  <link rel="stylesheet" href="https://uptalkr.com/assets/navbar2.css?ver=1">  
  <link rel="stylesheet" href="https://uptalkr.com/assets/postpage.css"> 
</head>
<body>
<nav>

<div class="nav-logo">
  <a href="https://uptalkr.com">
    <img src="/assets/logo.png" alt="Logo">
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
      <a href="../create">
       <img src="../assets/post2.png" alt="Create!" class="icon">
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

<div style="height: 100px;"></div>
<div id="imagePopup">
  <span id="closeImagePopup">&times;</span>
  <img id="popupImage" src="" alt="Popup Image">
</div>

<div class="container">
  <?php

  $post_id = $_GET['post'];
  echo "<title>UpTalkr - $post_id</title>";

    echo '<div class="post-details">';

    echo '<p><a href="../profile/?id=' . htmlspecialchars($row['username']) . '">' . htmlspecialchars($row['username']) . '</a></p>';
    echo "<p>&nbsp;</p>";
    echo "<h1>" . htmlspecialchars($row['title']) . "</h1>";
    echo "<p>" . htmlspecialchars($row['content']) . "</p>";
    echo "<p>&nbsp;</p>";

if (!empty($image_path)) {
    $extension = pathinfo($image_path, PATHINFO_EXTENSION);
    if ($extension === 'mp4') {
        echo '<video width="400" height="250" controls controlsList="nodownload">
        <source src="' . $image_path . '" type="video/mp4">
        </video>';
    } else {
        echo '<img src="' . $image_path . '" alt="Photo" style="max-width: 430px; max-height: 430px; min-width: 390px; min-height: 200px; border-radius: 10px;">';
    }
} else {
    echo ''; 
}
    echo "<p>&nbsp;</p>";
    echo '<div>';
    echo '<form action="like.php" method="POST">';
    echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
    echo "<p>&nbsp;</p>";
    echo '<div>';
echo '<form action="like.php" method="POST" style="display: inline-block;">';
echo '<button type="submit" style="border:none; background-color:transparent;"><img src="../assets/' . $like_image . '" width="22" height="22"></button>';


$likes_count = 0; 
$likes_stmt = $conn->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$likes_stmt->bind_param('i', $post_id);
$likes_stmt->execute();
$likes_result = $likes_stmt->get_result();
if ($likes_result) {
    $likes_count = $likes_result->fetch_row()[0];
}
echo '<span>' . htmlspecialchars($likes_count) . '</span>';

echo '</form>';
    echo "&nbsp;";
    echo '<a style="display: inline-block; margin-left: 10px;" onclick="showCommentsPopup()"><img src="../assets/comment.png" alt="Jaa" width="22" height="22"></a>';
    echo '<a style="display: inline-block; margin-left: 10px;" onclick="copyUrl(event)"><img src="../assets/share.png" alt="Jaa" width="22" height="22"></a>';
    echo '<a style="display: inline-block; margin-left: 10px;"><img src="../assets/eyes.png" alt="Views" width="22" height="22"></a>';
    echo '<span>' . htmlspecialchars($row['views']) . '</span>';
    echo '</div>';
    echo '</form>';
    echo '<p><span style="font-size: 10px;">' . htmlspecialchars($row['created_at']) . '</span></p>';
    echo '</div>';
    echo '</div>';

  ?>

</div>
<div id="commentsSection">

  <div id="commentsPopupContent">
    <h2>Comments</h2>
    <form id="commentForm" action="add_comment.php" method="POST">
      <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
      <label for="kommentti">Comment:</label>
      <textarea id="kommentti" name="kommentti"></textarea><br>
      <button type="submit" id="submitComment">Send comment</button>
    </form>
    <div id="commentList">
      <p>&nbsp;</p>
      <?php
        $comments_query = "SELECT * FROM kommentit WHERE postauksen_id = $post_id";
        $comments_result = mysqli_query($conn, $comments_query);

        if ($comments_result) {
          while ($comment = mysqli_fetch_assoc($comments_result)) {
            $user_id = $comment['user_id'];
            $user_query = "SELECT username FROM users WHERE id = $user_id";
            $user_result = mysqli_query($conn, $user_query);

            if ($user_result) {
              $user = mysqli_fetch_assoc($user_result);

		echo '<div class="comment">';
		echo '<p><a href="https://uptalkr.com/profile/?id=' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '</a>:</p>';
		echo '<p>' . htmlspecialchars($comment['kommentti'], ENT_QUOTES, 'UTF-8') . '</p>';
		echo '</div>';
            }
          }
        }
      ?>
    </div>
  </div>
</div>

</body>
</html>




<script>
  function copyUrl() {
    var currentUrl = window.location.href;

    var tempInput = document.createElement('input');
    tempInput.value = currentUrl;
    document.body.appendChild(tempInput);

    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);

    alert('Link copied!');
  }

var image = document.querySelector('img[src="<?php echo $image_path; ?>"]');
if (image) {
  image.addEventListener('click', function() {
    var popup = document.getElementById('imagePopup');
    var popupImage = document.getElementById('popupImage');
    var commentsPopup = document.getElementById('commentsPopup');
    var overlay = document.getElementById('commentsOverlay');
    popupImage.src = this.src;
    commentsPopup.style.display = 'none';
    popup.style.display = 'flex';
    overlay.style.display = 'block';
  });
}

var overlay = document.getElementById('commentsOverlay');

if (overlay) {
  overlay.addEventListener('click', function() {
    var popup = document.getElementById('imagePopup');
    var commentsPopup = document.getElementById('commentsPopup');
    popup.style.display = 'none';
    commentsPopup.style.display = 'block';
    overlay.style.display = 'none';
  });
}

var closePopup = document.getElementById('closeImagePopup');
if (closePopup) {
  closePopup.addEventListener('click', function() {
    var popup = document.getElementById('imagePopup');
    popup.style.display = 'none';
  });
}


</script>

<?php
mysqli_close($conn);
?>
