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

$ok = "";

$conn = new mysqli($servername, $username, $password_db, $dbname);

if ($conn->connect_error) {
    die("DB CONNECTION FAILED");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['cf-turnstile-response'])) {
        $errors[] = "CAPTCHA verification failed. Please go back and verify that you are not a robot.";
        header("Location: https://uptalkr.com/report?ok=4");
    } else {
        $turnstileSecretKey = "0x4AAAAAAAifvQtkQX4x4HEdlbnOqqxPrWE";
        $turnstileResponse = $_POST['cf-turnstile-response'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $turnstileSecretKey,
            'response' => $turnstileResponse
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        $responseKeys = json_decode($response, true);
        if (isset($responseKeys["success"]) && $responseKeys["success"] === true) {

            $item_type = $_POST['item_type'];
            $item_id = $_POST['item_id'];
            $reason = $_POST['reason'];
            $additional_info = $_POST['additional_info'];

            if (empty($item_type) || empty($item_id) || empty($reason)) {
                $errors[] = "Please fill in all required fields.";
                header("Location: https://uptalkr.com/report?ok=3");
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO report (reported_item_type, reported_item_id, reason, additional_info) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $item_type, $item_id, $reason, $additional_info);

            if ($stmt->execute()) {
                $ok = "Report submitted successfully!";
                header("Location: https://uptalkr.com/report?ok=2");
                exit;
            } else {

                $errors[] = "There was an error submitting the report: " . $stmt->error;
                header("Location: https://uptalkr.com/report?ok=1");
                exit;
            }
        } else {
            $errors[] = "CAPTCHA verification failed.";
        }
    }
}

if (!empty($errors)) {
    foreach ($errors as $error) {

    }
}

$conn->close();

if (isset($_GET['ok'])) {
    $ok = $_GET['ok'];
    switch ($ok) {
        case '4':
            $ok = "CAPTCHA verification failed.";
            break;

        case '3':
            $ok = "Please fill in all required fields.";
            break;

        case '2':
            $ok = "Report submitted successfully!";
            break;

        case '1':
            $ok = "There was an error submitting the report.";
            break;

        default:
            $ok = "N/A";
    }
}
?>

<link rel="icon" href="../assets/logo.png" type="image/x-icon">

<!DOCTYPE html>
<html>
<head>
    <title>UpTalkr - Report</title>
    <meta http-equiv="refresh" content="120">
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="../assets/navbar2.css">
    <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/report.css">
</head>
<style>
.search-form {
  margin-top: 15px !important;
}
.bottom-left {
    position: fixed;
    bottom: 1px;

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
<div class="report-form">
    <h2>Report a User or Post</h2>
    <form action="index.php" method="POST">
        <label for="item_type">Select the type of report:</label>
        <select name="item_type" id="item_type">
            <option value="user">User</option>
            <option value="post">Post</option>
        </select>
        
        <label for="item_id">ID (Profile page link or Post link):</label>
        <input type="text" name="item_id" id="item_id" required>
        
        <label for="reason">Why do you want to report this?</label>
        <textarea name="reason" id="reason" rows="4" required></textarea>
        
        <label for="additional_info">Additional Information:</label>
        <textarea name="additional_info" id="additional_info" rows="4"></textarea>
        
        <div class="center-recaptcha">
            <label for="cf-turnstile"></label>
            <div class="cf-turnstile" data-sitekey=""></div>
        </div>
        <br>

        <input type="submit" value="Submit Report">
        <?php echo '<div style="text-align: center;">' . htmlspecialchars($ok) . '</div>'; ?>
    </form>
</div>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</body>
<p class="bottom-left">
        <a href="https://uptalkr.com/report">Report</a>
        <a href="https://uptalkr.com/leaderboard/">Leaderboard</a>
        <a href="https://uptalkr.com/privacy-policy/">Privacy Policy</a>
        <a href="https://uptalkr.com/terms-of-service/">Terms of Service</a>
        <a href="https://uptalkr.com/community-guidelines/">Community Guidelines</a>

  &copy; uptalkr.com 2024
</p>
</html>
