<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true, 
    'use_strict_mode' => true
]);

require '/var/uptalkr/check-cookie-token.php';
require '/var/www/vendor/autoload.php';
require '/var/uptalkr/updb.php';

if ((isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) || isset($_SESSION['userid'])) {
    header("Location: https://uptalkr.com/settings/");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$conn = new mysqli($dbsrv, $dbusr, $pass_db, $dbnam);

if ($conn->connect_error) {
    error_log("Connection failed: " . htmlspecialchars($conn->connect_error));
    die("An error occurred. Please try again later.");
}


function sanitize_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}


if (isset($_GET['token'])) {
    $token = sanitize_output($_GET['token']);


    $check_token_sql = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $check_token_sql->bind_param("s", $token);
    $check_token_sql->execute();
    $check_token_sql->store_result();

    if ($check_token_sql->num_rows == 0) {
        $error_message = "The password reset link is invalid or has expired. Please request a new one.";
        unset($_GET['token']); 
    } else {

        $check_token_sql->bind_result($user_email);
        $check_token_sql->fetch();
    }
    $check_token_sql->close();
}


function generateRandomToken($length = 200) {
    return bin2hex(random_bytes($length / 2));
}


function sendResetEmail($email, $token) {
    $reset_link = "https://uptalkr.com/auth/password-reset?token=" . urlencode($token);
    $subject = "Password Reset for UpTalkr";
    $message = '
<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            padding: 20px;
            text-align: center;
        }
        .footer {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 0 0 10px 10px;
        }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>UpTalkr</h1>
        </div>
        <div class="content">
            <h2>Password Reset Request</h2>
            <p>Please click the button below to reset your password.</p>
            <a href="' . $reset_link . '" class="button">Reset Password</a>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' UpTalkr. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
    ';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = '';
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('no-reply@uptalkr.com', 'UpTalkr Verify');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        die("An error occurred while sending the email. Please try again later.");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $user_email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)); 

    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        if (isset($_POST['cf-turnstile-response'])) {
            $captcha_response = $_POST['cf-turnstile-response'];
            $secret_key = ""; // Cloudflare OWN Turnstile secret key

            $data = [
                'secret' => $secret_key,
                'response' => $captcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ];
            $ch = curl_init("https://challenges.cloudflare.com/turnstile/v0/siteverify");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $captcha_result = curl_exec($ch);
            curl_close($ch);

            $captcha_result = json_decode($captcha_result, true);

            if ($captcha_result['success']) {
                $check_email_sql = $conn->prepare("SELECT google_userid FROM users WHERE email = ?");
                $check_email_sql->bind_param("s", $user_email);
                $check_email_sql->execute();
                $check_email_sql->store_result();

                if ($check_email_sql->num_rows > 0) {

                    $check_email_sql->bind_result($google_userid);
                    $check_email_sql->fetch();
                    $check_email_sql->close();

                    if ($google_userid === null) {

                        $check_token_sql = $conn->prepare("SELECT token FROM password_resets WHERE email = ? AND expires_at > NOW()");
                        $check_token_sql->bind_param("s", $user_email);
                        $check_token_sql->execute();
                        $check_token_sql->store_result();

                        if ($check_token_sql->num_rows > 0) {

                            $success_message = "A password reset email has already been sent. Please check your inbox or spam folder.";
                        } else {

                            $token = generateRandomToken(200);
                            $expiry_time = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                            $insert_token_sql = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                            $insert_token_sql->bind_param("sss", $user_email, $token, $expiry_time);
                            if ($insert_token_sql->execute()) {

                                sendResetEmail($user_email, $token);
				$success_message = "A password reset email has been sent to your email address.";
                            } else {
                                $error_message = "Failed to process your request. Please try again later.";
                            }
                            $insert_token_sql->close();
                        }
                        $check_token_sql->close();
                    } else {

                        $error_message = "This user is logged in with Google and cannot reset the password.";
                    }
                } else {

                    $error_message = "Email not found!";
                    $check_email_sql->close();
                }
            } else {
                $error_message = "CAPTCHA validation failed. Please try again.";
            }
        } else {
            $error_message = "CAPTCHA is required.";
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password']) && isset($_POST['confirm_password']) && isset($user_email)) {
    

    $new_password = sanitize_output($_POST['new_password']);
    $confirm_password = sanitize_output($_POST['confirm_password']);
    $user_email = sanitize_output($user_email); 


    if ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($new_password) < 10) {
        $error_message = "Password must be at least 10 characters long.";
    } elseif (strlen($new_password) > 150) {
        $error_message = "Password must not exceed 150 characters.";
    } else {

        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);


        $update_password_sql = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_password_sql->bind_param("ss", $hashed_password, $user_email);

        if ($update_password_sql->execute()) {

            $delete_token_sql = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete_token_sql->bind_param("s", $user_email);
            $delete_token_sql->execute();
            $delete_token_sql->close();


            header("Location: https://uptalkr.com/login");
            exit();
        } else {
            $error_message = "Failed to update the password. Please try again.";
        }

        $update_password_sql->close();
    }

}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UpTalkr - Reset Password</title>
  <link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
  <style> 
    body {
        margin: 0;
        padding: 0;
        background: linear-gradient(to right, #56ccf2, #2f80ed);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        font-family: 'Arial', sans-serif;
    }

    .popup-container {
        background-color: #fff;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0px 10px 40px rgba(0, 0, 0, 0.1);
        text-align: center;
        max-width: 400px;
        width: 100%;
        position: relative;
    }

    .popup-content {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .popup-content h2 {
        font-size: 28px;
        margin-bottom: 20px;
        color: #333;
    }

    .popup-content input[type="email"], 
    .popup-content input[type="password"] {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
        box-sizing: border-box;
    }

    .popup-content input[type="submit"] {
        background-color: #2f80ed;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        margin-top: 20px;
        transition: background-color 0.3s ease;
    }

    .popup-content input[type="submit"]:hover {
        background-color: #1d6bcf;
    }

    .success-message, .error-message {
        font-size: 14px;
        margin-top: 10px;
    }

    .success-message {
        color: green;
    }

    .error-message {
        color: red;
    }

    .bottom-left {
        color: white;
        position: fixed;
        bottom: 0;
        left: 0;
        margin: 10px;
    }
.bottom-left {
    position: fixed;
    bottom: 0;
    left: 0;
    margin: 10px;
    color: white;
}
  </style>
</head>
<body>
  <div class="popup-container">
    <div class="popup-content">
      <?php if (isset($_GET['token'])): ?>
        <h2>Reset Your Password</h2>
        <?php if (isset($error_message)): ?>
          <p class="error-message"><?php echo $error_message; ?></p>
        <?php elseif (isset($success_message)): ?>
          <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (!isset($success_message)): ?>
          <form method="post" action="">
            <input type="password" name="new_password" required placeholder="Enter your new password" />
            <input type="password" name="confirm_password" required placeholder="Confirm your new password" />
            <input type="submit" value="Reset Password" />
          </form>
        <?php endif; ?>
      <?php else: ?>
        <h2>Forgot Your Password?</h2>
        <?php if (isset($error_message)): ?>
          <p class="error-message"><?php echo $error_message; ?></p>
        <?php elseif (isset($success_message)): ?>
          <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <form method="post" action="">
          <input type="email" name="email" required placeholder="Enter your email" />
          <div class="cf-turnstile" data-sitekey="0x4AAAAAAAi5uLu_AFgfoFva"></div>
          <input type="submit" value="Send Password Reset Email" />
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <p class="bottom-left">
        &copy; uptalkr.com 2024 
    </p>
</body>
</html>
