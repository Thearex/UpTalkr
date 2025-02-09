<link rel="icon" href="../assets/logo.png" type="image/x-icon">


<?php
session_start();
require '/var/uptalkr/check-cookie-token.php';
require '/var/uptalkr/updb.php';

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
try {
    if (!isset($_GET['id'])) {
        if (isset($_SESSION['userid'])) {
            $user_id = $_SESSION['userid'];

            $conn = new mysqli($servername, $username, $password_db, $dbname);

            if ($conn->connect_error) {
                throw new Exception("Tietokantayhteys ep onnistui: " . $conn->connect_error);
            }

            $sql = "SELECT username FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Valmistelussa tapahtui virhe: " . $conn->error);
            }

            $stmt->bind_param("i", $user_id);

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $username = htmlspecialchars($row['username']);

                header("Location: https://uptalkr.com/profile?id=" . urlencode($username));
                exit();
            }

            $stmt->close();
            $conn->close();
        }
    }
} catch (Exception $e) {
} 

$conn = new mysqli($servername, $username, $password_db, $dbname);

if ($conn->connect_error) {
  die("Database connection failed");
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $kayttajanimi = $_GET['id'];
    $kayttajanimi = mysqli_real_escape_string($conn, $kayttajanimi);

    $query = "SELECT id FROM users WHERE username = '$kayttajanimi'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userID = $row['id'];

    } else {
        echo "User not found";
        header("Location: https://uptalkr.com");
        exit;
    }
} else {
    echo "ID-parametri puuttuu tai on tyhj !";
    header("Location: https://uptalkr.com");
    exit;
}

$aboutMeQuery = "SELECT about_me FROM settings WHERE user_id = '$userID'";
$aboutMeResult = mysqli_query($conn, $aboutMeQuery);
$aboutMeRow = mysqli_fetch_assoc($aboutMeResult);
$aboutMeText = $aboutMeRow['about_me'];

$usernameQuery = "SELECT username FROM users WHERE id = '$userID'";
$usernameResult = mysqli_query($conn, $usernameQuery);
$usernameRow = mysqli_fetch_assoc($usernameResult);
$username = $usernameRow['username'];

$followersQuery = "SELECT COUNT(*) AS followerCount FROM Followers WHERE user_id = '$userID'";
$followersResult = mysqli_query($conn, $followersQuery);
$followersRow = mysqli_fetch_assoc($followersResult);
$followerCount = $followersRow['followerCount'];

$pisteQuery = "SELECT SUM(piste) AS megapiste FROM megapisteet WHERE userid = '$userID'";
$pisteResult = mysqli_query($conn, $pisteQuery);
$pisteRow = mysqli_fetch_assoc($pisteResult);

if (is_null($pisteRow['megapiste'])) {
    $pisteCount = 0;
} else {
    $pisteCount = $pisteRow['megapiste'];
}

$luotuQuery = "SELECT luotu FROM users WHERE id = '$userID'";
$luotuResult = mysqli_query($conn, $luotuQuery);
$luotuRow = mysqli_fetch_assoc($luotuResult);
$luotuData = $luotuRow['luotu'];

$postQuery = "SELECT COUNT(*) AS postCount FROM postaukset WHERE user_id = '$userID'";
$postResult = mysqli_query($conn, $postQuery);
$postRow = mysqli_fetch_assoc($postResult);
$postCount = $postRow['postCount'];

$sql = "SELECT p.post_id, p.title, p.content, p.file, u.username, p.created_at
        FROM postaukset AS p
        INNER JOIN users AS u ON p.user_id = u.id
        WHERE p.user_id = $userID
        ORDER BY p.created_at DESC";

$result = mysqli_query($conn, $sql);

$profilePicQuery = "SELECT profiilikuva FROM profiili WHERE userid = '$userID'";
$profilePicResult = mysqli_query($conn, $profilePicQuery);
$profilePicRow = mysqli_fetch_assoc($profilePicResult);
$profilePicURL = $profilePicRow['profiilikuva'];

if(empty($profilePicURL)) {
    $profilePicURL = "default-profile.png";
}

$bannerPicQuery = "SELECT banneri FROM profiili WHERE userid = '$userID'";
$bannerPicResult = mysqli_query($conn, $bannerPicQuery);
$bannerPicRow = mysqli_fetch_assoc($bannerPicResult);
$bannerPicURL = $bannerPicRow['banneri'];

if(empty($bannerPicURL)) {
    $bannerPicURL = "default-banner.png";
}

if (isset($_SESSION['userid'])) {
    $loggedInUserID = $_SESSION['userid'];

    $followCheckQuery = "SELECT * FROM Followers WHERE user_id = '$userID' AND follower_id = '$loggedInUserID'";
    $followCheckResult = $conn->query($followCheckQuery);
    if ($followCheckResult->num_rows > 0) {
        $followText = "UNFOLLOW";
    } else {
        $followText = "FOLLOW";
    }
} else {
    $followText = "FOLLOW";
}

$followerror = "";

if (isset($_GET['error'])) {
    $error = $_GET['error'];

    switch ($error) {
        case '3':
            $followerror = "Contact contact@uptalkr.com";
            break;

        case '2':
            $followerror = "You cant follow yourself!";
            break;
        case '1':
            $followerror = "Database error.";
            break;

        default:
            $followerror = "Contact contact@uptalkr.com";
    }


}

$badgeQuery = "SELECT * FROM badges WHERE user_id = '$userID' AND (verified = TRUE OR staff = TRUE OR Axsoter = 1)";
$badgeResult = mysqli_query($conn, $badgeQuery);

$badges = array();

while ($badgeRow = mysqli_fetch_assoc($badgeResult)) {
    if ($badgeRow['verified']) {
        $badges[] = "../assets/verified.png";
    }
    if ($badgeRow['staff']) {
        $badges[] = "../assets/moderatorbadge.png";
    }
    if ($badgeRow['Epic']) {
        $badges[] = "../assets/epicbadge.png";
    }
    if ($badgeRow['Axsoter']) {
        $badges[] = "../assets/axsoterbadge.png";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://uptalkr.com/assets/navbar2.css?version=1">
    <title>UpTalkr - <?php echo htmlspecialchars($kayttajanimi, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<style>



body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f5f5f5;
}


.profile-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 20px;
  border-bottom: 1px solid #ddd;
}

.username {
  font-size: 24px;
  margin: 0;
  font-weight: bold;
  color: #333;
}

.user-stats {
  font-size: 14px;
  margin: 5px 0;
  color: #555;
}

.follow-button {
  padding: 10px 20px;
  background-color: #007bff;
  color: #fff;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  text-transform: uppercase;
}



.tooltip {
  position: relative;
  display: inline-block;
  border-bottom: 1px dotted black;
}

.tooltip .tooltiptext {
  visibility: hidden;
  width: 120px;
  background-color: black;
  color: #fff;
  text-align: center;
  border-radius: 6px;
  padding: 5px 0;
  position: absolute;
  z-index: 1;
  bottom: 100%;
  left: 50%;
  margin-left: -60px;
}

.tooltip:hover .tooltiptext {
  visibility: visible;
}

.about-me h2 {
  font-size: 18px;
  margin: 0;
  font-weight: bold;
  color: #333;
}

.about-me p {
  font-size: 16px;
  line-height: 1.5;
  color: #555;
}

a {
  color: #007bff;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

.banner {
  width: 100%;
  height: 200px; 
  background-image: url('banner-default2.png');
  background-size: cover;
  background-position: center;
}




.profile-picture {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  overflow: hidden;
  margin: -75px auto 20px; 
}

.profile-picture img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

body > *:not(nav) {
  margin-top: 70px; 
}

nav {
  background-color: #fff;
  padding: 20px;
  transition: transform 0.3s ease;
  top: 0;
  left: 0;
  height: 40px;
  width: 100%;
  position: fixed;
  display: flex;
  align-items: center;
  justify-content: space-between;
  z-index: 100;
}

.nav-logo {
  margin-left: 20px;
  text-align: center;
}

.nav-logo img {
  width: 60px;
  height: 60px;
  border-radius: 50%;
}

.nav-links {
  display: flex;
  align-items: center;
}

.nav-item {
  margin-left: 20px;
}

.nav-item a {
  text-decoration: none;
  color: #fff;
  padding: 10px 16px;
  transition: color 0.3s;
  gap: 10px;
}

.nav-item:hover a {
  color: #fff;
}

.nav-title {
color: #fff;
font-size: 24px;
margin-left: 20px;
}

ul {
list-style-type: none;
margin: 0;
padding: 0;
display: flex;
align-items: center;
}

li {
margin-left: 20px;
}

.text {
text-decoration: none;
color: #fff;
padding: 10px 20px;
border-radius: 30px;
transition: background-color 0.3s;
}

.text:hover {
background-color: #333;
}

.search-form {
  display: flex;
  align-items: center;
  border: 1px solid black;
  border-radius: 8px;
  padding: 5px;
  margin-top: 16px !important;
}

.search-form input {
  padding: 8px;
  border-radius: 4px;
  border: none;
  margin-right: 4px;
}

.load-more-button {
  background-color: #eaeaea;
  color: #333;
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.load-more-button:hover {
  background-color: #ccc;
}


.icon {
  width: 40px; 
  height: 40px;
}

.search-form button {
  padding: 8px 16px;
  border-radius: 4px;
  border: none;
  background-color: #555;
  color: #fff;
  transition: background-color 0.3s;
}

.search-form button:hover {
  background-color: #333;
}



.icon-container {
  position: relative;
  display: inline-block;
}

.icon-text {
  position: absolute;
  top: -30px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #000;
  color: #fff;
  padding: 5px;
  font-size: 12px;
  visibility: hidden;
  opacity: 0;
  transition: opacity 0.3s;
}

.icon-container:hover .icon-text {
  visibility: visible;
  opacity: 1;
}

  .login {
    display: inline-block;
    margin-right: 10px;
  }

  .login a {
    display: inline-block;
    padding: 10px 20px;
    background: linear-gradient(#d284ff, #00afff);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s ease;
  }

  .login a:hover {
    background: linear-gradient(#d284ff, #00afff);
  }

.nav-logo span {
  margin-right: 3 !important;
  display: flex;
  align-items: center;
}

.profile-info {
  margin: 0 auto;
}
.profile-container {
  max-width: 600px;
  margin: 20px auto;
  background-color: #ffffff;
  border: 1px solid #ccc;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
  position: relative;
  text-align: center;
  display: flex; 
  flex-direction: column; 
  align-items: center; 
}

.created {
  position: absolute; 
  bottom: 0; 
  left: 0; 
  padding: 10px;
  font-size: 12px; 
  text-align: left;
}

.banner {
  width: 100%; 
  height: 20vh; 
  position: relative; 
  overflow: hidden; 
}

.banner img {
  width: 100%; 
  height: 100%; 
  object-fit: cover; 
}
.badgee img {
width: 10px;
height: 10px;
border-radius: 50%;
margin-left: 15px;
}


#badge-container {
display: flex;
}

.badge-wrapper {
position: relative;
width: 24px;
height: 24px;
}

.badgee {
width: 20px;
height: 20px;
background-size: cover;
border-radius: 50%;
}

.tooltip-text {
visibility: hidden;
position: absolute;
bottom: 100%;
left: 50%;
transform: translateX(-50%);
padding: 5px;
background-color: #333;
color: #fff;
border-radius: 5px;
}

.badge-wrapper:hover .tooltip-text {
visibility: visible;
}
  #sidebar {
      position: fixed;
      top: 0;
      left: -250px;
      width: 250px;
      height: 100%;
      background-color: #333;
      overflow-x: hidden;
      transition: 0.3s;
      padding-top: 60px;
  }
  #sidebar-toggle {
      position: fixed;
      top: 20px;
      left: 20px;
  }
  #sidebar.active {
      left: 0;
  }
  #sidebar .logo {
      position: absolute;
      top: 10px;
      left: 50%;
      transform: translateX(-50%);
  }
  #sidebar .buttons {
      margin-top: 20px;
  }
  #sidebar .buttons a {
      display: block;
      padding: 10px;
      color: #fff;
      text-decoration: none;
      border-bottom: 1px solid #444;
  }
.about-me {
  margin-top: 20px;
  background-color: #f9f9f9;
  padding: 20px;
  border-radius: 5px;
  width: 500px; 
}

.post-list {
display: flex;
flex-direction: column;
align-items: center;
text-decoration: none;
}






@media screen and (min-width: 800px) {
.post-list {
  grid-template-columns: repeat(2, 1fr);
}
}

@media screen and (min-width: 1200px) {
.post-list {
  grid-template-columns: repeat(3, 1fr);
}
}


.profile-button {
    padding: 10px 20px;
    background: linear-gradient(45deg, #d284ff, #00afff);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    transition: background 0.3s ease;
}

.profile-button:hover {
    background: linear-gradient(45deg, #00afff, #d284ff);
}

  .content {
    margin-top: 0px;
  }

.post-container {
  background-color: #fff;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  max-width: 500px; 
  width: 90%; 
  margin: 50px auto 10px auto; 
  transition: transform 0.3s ease;
  cursor: pointer;
  border: 1px solid #000;
  word-wrap: break-word; 
  overflow-wrap: break-word; 
}

  .post-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
  }

  .post-profile-picture img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 10px;
  }

  .post-username {
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
  word-wrap: break-word;
  overflow-wrap: break-word; 
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

.post-username-link {
    color: #333; 
    text-decoration: none;
    font-weight: bold;
}

.post-username-link:hover {
    color: #007bff;
    text-decoration: none;
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


@media (max-width: 831px) {
    .bottom-left {
        display: none;
    }
}

</style>
<body>
<nav>
<div class="nav-logo">
  <a href="https://uptalkr.com">
    <img src="../assets/logo.png" alt="Logo">
  </a>
</div>
  <div class="nav-links">
    <div class="nav-item">
      <form class="search-form" action="../api/haku.php" method="GET">
        <input type="text" name="s" placeholder="Search" onkeydown="handleKeyDown(event)">
      </form>
    </div>
    <div class="nav-item">
     <div class="icon">
      <a href="https://uptalkr.com/create">
       <img src="../assets/post2.png" alt="Postaa" class="icon">
       <span class="icon-text">Posts</span>
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
    
    <div class="banner">
        <img src="<?php echo htmlspecialchars($bannerPicURL, ENT_QUOTES, 'UTF-8'); ?>" alt="Banner Image">
    </div>
    <div class="profile-container">
        <div class="profile-picture">
            <img src="<?php echo htmlspecialchars($profilePicURL, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture">
        </div>

        <div class="profile-header">
            <div class="profile-info">
<h1 class="username" style="display: flex; align-items: center; justify-content: center;">
  <?php echo htmlspecialchars($kayttajanimi, ENT_QUOTES, 'UTF-8'); ?>
<?php
foreach ($badges as $badge) {
  if ($badge === "../assets/verified.png") {
    echo '<div class="badge-wrapper"><img src="' . $badge . '" class="badgee"><span class="tooltip-text">Verified</span></div>';
  } elseif ($badge === "../assets/moderatorbadge.png") {
    echo '<div class="badge-wrapper" style="margin-left: 3px;"><img src="' . $badge . '" class="badgee"><span class="tooltip-text">Staff</span></div>';
  } elseif ($badge === "../assets/epicbadge.png") {
    echo '<div class="badge-wrapper" style="margin-left: 3px;"><img src="' . $badge . '" class="badgee"><span class="tooltip-text">Epic</span></div>';
  } elseif ($badge === "../assets/axsoterbadge.png") {
    echo '<div class="badge-wrapper" style="margin-left: 3px;"><img src="' . $badge . '" class="badgee"><span class="tooltip-text">Axsoter</span></div>';
  }
}
?>
</h1>
                <p class="user-stats"><?php echo $followerCount; ?> followers | <?php echo $pisteCount; ?> uppoints | <?php echo $postCount; ?> posts</p>
                <div class="followw-button">
  <form action="seuraa.php" method="post">
    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
    <button class="follow-button" type="submit"><?php echo $followText; ?></button>
  </form>
</div>
<?php echo $followerror; ?>
            </div>

        </div>
<div class="about-me">
  <h2>About Me</h2>
  <p><?php 
      if(empty($aboutMeText)) {
          echo "About me text has not been set";
      } else {
          echo htmlspecialchars($aboutMeText, ENT_QUOTES, 'UTF-8'); 
      }
  ?></p>
</div>
<p>&nbsp;</p>
<div class="created">
  <p style="text-align: left; font-size: 12px;">Created at <?php echo htmlspecialchars($luotuData, ENT_QUOTES, 'UTF-8'); ?></p>
</div>
            <div class="action-icons">
    <a href="https://uptalkr.com/report" class="tooltip" id="report-icon" data-tooltip="Report">🚩
        <span class="tooltiptext">Report</span>
    </a>
    <a class="tooltip" id="share-icon" data-tooltip="Share">📤
        <span class="tooltiptext">Share</span>
    </a>
</div>
</div>
<div class="post-list">
    <?php
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $post_id = htmlspecialchars($row["post_id"]);
            $title = htmlspecialchars($row["title"]);
            $content = htmlspecialchars($row["content"]);
            $file = htmlspecialchars($row["file"]);
            $username = htmlspecialchars($row["username"]);
            $created_at = htmlspecialchars($row["created_at"]);
            $profile_picture = htmlspecialchars($profilePicURL);

            $post_url = "https://uptalkr.com/post/?post=" . $post_id;

            echo "<div class='post-container' onclick=\"location.href='https://uptalkr.com/post/?post=" . htmlspecialchars($post_id) . "'\">";
            
            echo "<div class='post-header'>";
            echo "<div class='post-profile-picture'>";
            echo "<img src='" . htmlspecialchars($profile_picture) . "' alt='Profile Picture'>";
            echo "</div>";
            echo "<div>";
            echo "<a href='https://uptalkr.com/profile/?id=" . htmlspecialchars($username) . "' class='post-username-link'>" . htmlspecialchars($username) . "</a>";
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
        }
    }
    ?>
</div>
<p class="bottom-left">
        <a href="https://uptalkr.com/report">Report</a>
        <a href="https://uptalkr.com/leaderboard/">Leaderboard</a>
        <a href="https://uptalkr.com/privacy-policy/">Privacy Policy</a>
        <a href="https://uptalkr.com/terms-of-service/">Terms of Service</a>
        <a href="https://uptalkr.com/community-guidelines/">Community Guidelines</a>

  &copy; uptalkr.com 2024
</p></body>
</html>



<script>
function copyLink() {

  const pageLink = window.location.href;

  const tempInput = document.createElement('input');
  tempInput.value = pageLink;

  document.body.appendChild(tempInput);
  tempInput.select();
  document.execCommand('copy');

  document.body.removeChild(tempInput);

  alert('Link copied to clipboard.');
}
</script>
