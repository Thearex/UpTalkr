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

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UpTalkr - SupportID</title>
<link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/navbar2.css">
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<style>
    body {
        font-family: Verdana, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        height: 100vh;
    }

    #supportid-container {
        text-align: center;
    }

    #supportid {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    #supportid-description {
        font-size: 14px;
        margin-bottom: 20px;
    }

    .reset-button {
        padding: 10px 20px;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .reset-button:hover {
        background-color: #0056b3;
    }

    .cf-turnstile {
        display: none;
    }

    .error-message {
        color: red;
        font-size: 16px;
        margin-top: 20px;
    }
</style>

<link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
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

$host = $servername;
$user = $username;
$error = "";

if (!isset($_SESSION['userid'])) {
    header("Location: https://uptalkr.com/login");
    exit;
}

$userid = $_SESSION['userid'];

function secureInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_supportid'])) {
    if (!empty($_POST['cf-turnstile-response'])) {
        $token = $_POST['cf-turnstile-response'];
        $secretKey = '';

        $verifyResponse = file_get_contents("https://challenges.cloudflare.com/turnstile/v0/siteverify", false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'secret' => $secretKey,
                    'response' => $token
                ])
            ]
        ]));

        $responseData = json_decode($verifyResponse);
        if ($responseData->success) {
            $userid = secureInput($userid);

            $conn = new mysqli($host, $user, $password, $dbname);

            if ($conn->connect_error) {
                $error = "Tietokantayhteyden muodostaminen ep�onnistui: " . $conn->connect_error;
            } else {
                $supportid = generateRandomString(16);

                $stmt = $conn->prepare("UPDATE supportid SET supportid = ? WHERE userid = ?");
                if (!$stmt) {
                    $error = "Virhe valmisteltaessa kysely�: " . $conn->error;
                } else {
                    $stmt->bind_param("ss", $supportid, $userid);
                    if (!$stmt->execute()) {
                        $error = "Virhe suorituksessa: " . $stmt->error;
                    }
                }
                $stmt->close();
                $conn->close();
            }
        } else {
            $error = "CAPTCHA FALED! Please try again.";
        }
    } else {
        $error = "CAPTCHA FALED! Please try again.";
    }
} else {
    $userid = secureInput($userid);

    $conn = new mysqli($host, $user, $password, $dbname);

    if ($conn->connect_error) {
        $error = "Tietokantayhteyden muodostaminen ep�onnistui: " . $conn->connect_error;
    } else {
        $stmt = $conn->prepare("SELECT supportid FROM supportid WHERE userid = ?");
        if (!$stmt) {
            $error = "Virhe valmisteltaessa kysely�: " . $conn->error;
        } else {
            $stmt->bind_param("s", $userid);
            if (!$stmt->execute()) {
                $error = "Virhe suorituksessa: " . $stmt->error;
            } else {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $supportid = $row['supportid'];
                } else {
                    $supportid = generateRandomString(16);

                    $stmt = $conn->prepare("INSERT INTO supportid (userid, supportid) VALUES (?, ?)");
                    if (!$stmt) {
                        $error = "Virhe valmisteltaessa kysely�: " . $conn->error;
                    } else {
                        $stmt->bind_param("ss", $userid, $supportid);
                        if (!$stmt->execute()) {
                            $error = "Virhe suorituksessa: " . $stmt->error;
                        }
                    }
                }
            }
            $stmt->close();
        }
        $conn->close();
    }
}

function generateRandomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
?>

<div id="supportid-container">
    <h2>Your SupportID:</h2>
    <div id="supportid">
        <?php echo isset($supportid) ? $supportid : ''; ?>
    </div>
    <div id="supportid-description">
        SupportID is a unique identifier used for support purposes. eg Discord support or email support
    </div>
    <form method="POST">
        <div class="cf-turnstile" data-sitekey="" data-callback="onTurnstileCompleted" data-size="invisible"></div>
        <button type="submit" name="reset_supportid" class="reset-button" onclick="turnstile.execute()">Reset SupportID</button>
        <input type="hidden" name="cf-turnstile-response" id="cf-turnstile-response">
    </form>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
</div>

<script>
    function onTurnstileCompleted(token) {
        document.getElementById('cf-turnstile-response').value = token;
    }
</script>

</body>
</html>
