<?php
session_start();
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
?>

<link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">

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



<?php
require '/var/uptalkr/updb.php';
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$sql = "
    SELECT users.username, SUM(megapisteet.piste) as total_points
    FROM megapisteet
    JOIN users ON megapisteet.userid = users.id
    GROUP BY megapisteet.userid
    ORDER BY total_points DESC
    LIMIT 10
";


$result = $conn->query($sql);


$sql_users = "SELECT COUNT(*) as user_count FROM users";
$sql_posts = "SELECT COUNT(*) as post_count FROM postaukset";

$result_users = $conn->query($sql_users);
$result_posts = $conn->query($sql_posts);

$user_count = $result_users->fetch_assoc()['user_count'];
$post_count = $result_posts->fetch_assoc()['post_count'];


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UpTalkr - Leaderboard</title>
<link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/navbar2.css?v=1">
<link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/leaderboard.css">
</head>
<style>
.search-form {
  margin-top: 15px !important;
}
.bottom-left {
    position: fixed;
    bottom: 1px;
    left: 13px;
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


@media (max-width: 810px) {
    .bottom-left {
        display: none;
    }
}
</style>
<body>
<div class="info-container">
    <span id="statsTitle">General Stats</span>
    <b>Users in UpTalkr:</b> <?= htmlspecialchars($user_count) ?><br>
    <b>Posts in UpTalkr:</b> <?= htmlspecialchars($post_count) ?>
</div>
<div class="container">
<h1>UpPoints - Top10</h1>
<?php
if ($result->num_rows > 0) {
    $count = 1;
    while($row = $result->fetch_assoc()) {
        echo "<b>" . htmlspecialchars($count) . "</b>. " . htmlspecialchars($row["username"]). " - UpPoints: " . htmlspecialchars($row["total_points"]). "<br>";
        $count++;
    }
} else {
    echo "0 results";
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
</p>

</body>
</html>
