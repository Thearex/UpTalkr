<?php
require '/var/uptalkr/updb.php';

ini_set('session.cookie_domain', '.uptalkr.com');
ini_set('session.cookie_domain', '.uptalkr.com');
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
session_start();if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
} else {
    if (isset($_COOKIE['uptalkr_token'])) {
        $token = $_COOKIE['uptalkr_token'];

        $conn = new mysqli($dbsrv, $dbusr, $pass_db, $dbnam);

        if ($conn->connect_error) {
            die("DB CONNECTION FAILED");
        }

        $token_query = $conn->prepare("SELECT user_id FROM token WHERE token = ? AND vanhenee > NOW()");
        $token_query->bind_param("s", $token);
        $token_query->execute();
        $token_result = $token_query->get_result();

        if ($token_result->num_rows > 0) {
            $row = $token_result->fetch_assoc();
            $user_id = $row["user_id"];

            $_SESSION['loggedin'] = true;
            $_SESSION['userid'] = $user_id;

        } else {
            header('Location: https://uptalkr.com/login');
            exit; 
        }

        $conn->close(); 
    } else {
    }
}

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: https://uptalkr.com/');
    exit;

}
?>

<link rel="icon" href="../assets/logo.png" type="image/x-icon">

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpTalkr - Register</title>
    <link rel="stylesheet" href="https://uptalkr.com/assets/assets/login.css?v=2">
    <link rel="stylesheet" href="https://uptalkr.com/assets/prooregister.css?v=2">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<style>
    .bottom-left {
        position: fixed;
        bottom: 0;
        left: 0;
        margin: 10px;
        color: white;
    }
</style>

<body>
<div class="container">
    <div class="logo"></div>
    <form method="POST" id="register-form">
        <div class="form-step active" id="step-1">
            <h2>Sign up for UpTalkr</h2>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-step active" id="step-2">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <label for="confirm-password">Confirm Password:</label>
            <input type="password" id="confirm-password" name="confirm-password" required>
        </div>
        <div class="cf-turnstile" data-sitekey="-RnsG"></div>
        <button type="submit">Register</button>
        <div class="error-message" id="error-message" style="color: red;"></div>
    </form>
    <p class="small-text">By registering, you agree to the <a class="custom-link" href="https://uptalkr.com/terms-of-service/">Terms of Service</a>, <a class="custom-link" href="https://uptalkr.com/community-guidelines/">Community Guidelines</a> and <a class="custom-link" href="https://uptalkr.com/privacy-policy/">Privacy Policy</a></p>

    <?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['cf-turnstile-response'];
    $secretKey = ''; // Secret key
    $turnstileResponse = verifyTurnstile($token, $secretKey);

    if (!$turnstileResponse['success']) {
        echo '<div class="error-message" style="color: red;">CAPTCHA failed. Please try again.</div>';
        exit;
    }

    $conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

    if ($conn->connect_error) {
        die("Connection to the database failed: " . $conn->connect_error);
    }

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $confirmPassword = trim($_POST["confirm-password"]);

    if (empty($password)) {
        echo '<div class="error-message" style="color: red;">Password cannot be empty.</div>';
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR username LIKE ?");
    $stmt->bind_param("ss", $username, $username_like);
    $username_like = "%$username%";
    $stmt->execute();
    $result = $stmt->get_result();

    $forbiddenWords = array(
        "test"
    );

    if ($result->num_rows > 0) {
        echo '<div class="error-message" style="color: red;">Username already in use.</div>';
    } else {
        if (!preg_match('/^[a-zA-Z0-9]{4,16}$/', $username)) {
            echo '<div class="error-message" style="color: red;">Invalid username format. Usernames must contain 4-16 alphanumeric characters.</div>';
        } elseif (!preg_match('/^[a-zA-Z0-9]*((.)\2{2,})*[a-zA-Z0-9]*$/', $username)) {
            echo '<div class="error-message" style="color: red;">Username cannot contain the same character consecutively more than 2 times.</div>';
        } elseif (!preg_match('/^[a-zA-Z0-9]*([-_])*[a-zA-Z0-9]*$/', $username)) {
            echo '<div class="error-message" style="color: red;">Username cannot contain consecutive special characters or multiple underscores or hyphens.</div>';
        } elseif (strlen($password) < 10 || strlen($password) > 150) {
            echo '<div class="error-message" style="color: red;">Password length must be between 10 and 150 characters.</div>';
        } elseif ($password !== $confirmPassword) {
            echo '<div class="error-message" style="color: red;">Passwords do not match.</div>';
        } elseif (containsForbiddenWord($username, $forbiddenWords)) {
            echo "Username contains forbidden word.";
        } else {            

            $sql = "INSERT INTO users (username, password, luotu) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt->bind_param("ss", $username, $hashedPassword);
            if ($stmt->execute()) {
                echo "Registration was successful. You can now login!";
                header("Location: https://uptalkr.com/login");
                exit;
            } else {
                echo "Error in registration: " . $stmt->error;
            }
        }
    }

    $conn->close();
}

function verifyTurnstile($token, $secretKey) {
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return json_decode($response, true);
}


function containsForbiddenWord($username, $forbiddenWords) {
    foreach ($forbiddenWords as $word) {
        if (stripos($username, $word) !== false) {
            return true;
        }
    }
    return false;
}

?>

</div>

<p class="bottom-left">
    &copy; uptalkr.com
</p>
</body>
</html>
