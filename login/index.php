<?php
require '/var/uptalkr/updb.php';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/', 
    'domain' => '.uptalkr.com', 
    'secure' => true,
    'httponly' => true, 
    'samesite' => 'Lax' 
]);session_start();

$conn = new mysqli($dbsrv, $dbusr, $pass_db, $dbnam);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $user_id = $_SESSION['userid'];

    $policy_check_query = $conn->prepare("SELECT * FROM privacy_policy_accepted WHERE user_id = ?");
    $policy_check_query->bind_param("i", $user_id);
    $policy_check_query->execute();
    $policy_check_result = $policy_check_query->get_result();

    $email_check_query = $conn->prepare("SELECT email_verified FROM users WHERE id = ?");
    $email_check_query->bind_param("i", $user_id);
    $email_check_query->execute();
    $email_check_query->bind_result($email_verified);
    $email_check_query->fetch();
    $email_check_query->close();
    
    if ($policy_check_result->num_rows == 0 || !$email_verified) {
        header('Location: https://uptalkr.com/auth/set');
        exit;
    }
    

    header('Location: https://uptalkr.com/');
    exit;
}

if (isset($_COOKIE['uptalkr_token'])) {
    $token = $_COOKIE['uptalkr_token'];

    $token_query = $conn->prepare("SELECT user_id FROM token WHERE token = ? AND vanhenee > NOW()");
    $token_query->bind_param("s", $token);
    $token_query->execute();
    $token_result = $token_query->get_result();

    if ($token_result->num_rows > 0) {
        $row = $token_result->fetch_assoc();
        $user_id = $row["user_id"];


        $policy_check_query = $conn->prepare("SELECT * FROM privacy_policy_accepted WHERE user_id = ?");
        $policy_check_query->bind_param("i", $user_id);
        $policy_check_query->execute();
        $policy_check_result = $policy_check_query->get_result();


        $email_check_query = $conn->prepare("SELECT email_verified FROM users WHERE id = ?");
        $email_check_query->bind_param("i", $user_id);
        $email_check_query->execute();
        $email_check_query->bind_result($email_verified);
        $email_check_query->fetch();
        $email_check_query->close();

        if ($policy_check_result->num_rows == 0 || !$email_verified) {
            $_SESSION['userid'] = $user_id;
            header('Location: https://uptalkr.com/auth/set');
            exit;
        }

        $_SESSION['loggedin'] = true;
        $_SESSION['userid'] = $user_id;

        header('Location: https://uptalkr.com/');
        exit;

    } else {
        header('Location: https://uptalkr.com/login');
        exit; 
    }
}

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $turnstile_response = $_POST['cf-turnstile-response'];

    $secret_key = ""; // TÄHÄN SINUN OMA TURNSTILE CLOUDFLARE KEY
    $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = [
        'secret' => $secret_key,
        'response' => $turnstile_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $verify = file_get_contents($verify_url, false, $context);
    $captcha_success = json_decode($verify);

    if ($captcha_success->success) {

        $username_or_email = $_POST["username"];
        $password = $_POST["password"];
        $remember_me = isset($_POST["remember_me"]) ? true : false;

        $sql = "SELECT id, password, email_verified FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username_or_email, $username_or_email); 
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row["id"];
            $stored_password = $row["password"];
            $email_verified = $row["email_verified"];

            if (password_verify($password, $stored_password)) {
                $_SESSION['userid'] = $user_id;

                $policy_check_query = $conn->prepare("SELECT * FROM privacy_policy_accepted WHERE user_id = ?");
                $policy_check_query->bind_param("i", $user_id);
                $policy_check_query->execute();
                $policy_check_result = $policy_check_query->get_result();

                if ($policy_check_result->num_rows == 0 || !$email_verified) {
                    header('Location: https://uptalkr.com/auth/set');
                    exit;
                }

                $_SESSION['loggedin'] = true;
                $_SESSION['userid'] = $user_id;

                $token = generateRandomToken();
                $expiry_date = $remember_me ? date('Y-m-d H:i:s', strtotime('+40 days')) : date('Y-m-d H:i:s', strtotime('+1 hour')); 

                $insert_token_query = $conn->prepare("INSERT INTO token (user_id, token, vanhenee) VALUES (?, ?, ?)");
                $insert_token_query->bind_param("iss", $user_id, $token, $expiry_date);
                $insert_token_query->execute();

                setcookie('uptalkr_token', $token, strtotime($expiry_date), '/', 'uptalkr.com', true, true);

                header('Location: https://uptalkr.com/index.php');
                exit;
            } else {
                $login_error = "Invalid username/email or password";
            }
        } else {
            $login_error = "User not found";
        }
    } else {
        $login_error = "CAPTCHA verification failed";
    }
}

$conn->close();

function generateRandomToken($length = 100) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $token;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpTalkr - Login</title>
    <link rel="stylesheet" href="../assets/login2.css?version=2">
    <link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
    <style>
        .login-form input[type="checkbox"] {
            display: inline-block;
            vertical-align: middle; 
            margin-bottom: 10px;
        } 
        .login-form label {
            display: inline-block;
            vertical-align: middle; 
        }  
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <img src="../assets/logo.png" alt="uptalkr-logo" class="logo">
            <h2>Login to Uptalkr</h2>
            <form method="post" action="">
                <input type="text" name="username" placeholder="Username / Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <div class="cf-turnstile" data-sitekey="YOUR-CLOUDFLARE-TURNSTILE-KEY"></div>
                <button type="submit">Sign in</button>
                <p style="text-align: center;"><label><input name="remember_me" type="checkbox" /> Remember Me</label></p>
            </form>
      <div class="google-login">
      <a href="https://uptalkr.com/auth/google-callback.php">
        <img src="google.svg" alt="Login with Google">
      </a> 
      </div>
            <?php if (!empty($login_error)) { ?>
                <p class="error-message"><?php echo $login_error; ?></p>
            <?php } ?>
            <p>No account yet? <a href="../register">Register</a></p>
            <p class="small-text">By logging in, you agree to the <a class="custom-link" href="https://uptalkr.com/community-guidelines/">Community Guidelines</a>, <a class="custom-link" href="https://uptalkr.com/terms-of-service/">Terms of Service</a> and <a class="custom-link" href="https://uptalkr.com/privacy-policy/">Privacy Policy</a></p>
        </div>
    </div>
</body>
</html>
