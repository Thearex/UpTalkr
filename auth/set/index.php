<?php
require_once '/var/www/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '/var/www/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '/var/www/vendor/phpmailer/phpmailer/src/Exception.php';
require '/var/uptalkr/updb.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();

if (!isset($_SESSION['userid'])) {
    header('Location: https://uptalkr.com/login');
    exit;
}

$user_id = $_SESSION['userid'];


$conn = new mysqli($dbsrv, $dbusr, $pass_db, $dbnam);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$check_policy_sql = $conn->prepare("SELECT * FROM privacy_policy_accepted WHERE user_id = ?");
$check_policy_sql->bind_param("i", $user_id);
$check_policy_sql->execute();
$check_policy_sql->store_result();


$check_email_sql = $conn->prepare("SELECT email, email_verified FROM users WHERE id = ?");
$check_email_sql->bind_param("i", $user_id);
$check_email_sql->execute();
$check_email_sql->bind_result($user_email, $email_verified);
$check_email_sql->fetch();
$check_email_sql->close();


$email_sent = false;
$check_token_sql = $conn->prepare("SELECT * FROM email_verification_tokens WHERE user_id = ?");
$check_token_sql->bind_param("i", $user_id);
$check_token_sql->execute();
$check_token_sql->store_result();

if ($check_token_sql->num_rows > 0) {
    $email_sent = true;
}

$check_token_sql->close();

if ($check_policy_sql->num_rows > 0) {

    if ($email_verified) {

        header('Location: https://uptalkr.com/index.php');
        exit;
    } else {

        $policy_accepted = true;
    }
} else {
    $policy_accepted = false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['accept_policy'])) {

        $insert_policy_sql = $conn->prepare("INSERT INTO privacy_policy_accepted (user_id, accepted_at) VALUES (?, NOW())");
        $insert_policy_sql->bind_param("i", $user_id);
        $insert_policy_sql->execute();
        $insert_policy_sql->close();

        $policy_accepted = true;
    }

    if (isset($_POST['submit_email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {

        if (isset($_POST['cf-turnstile-response'])) {
            $captcha_response = $_POST['cf-turnstile-response'];


            $secret_key = ""; // YOUR OWN!!!!
            $verify_url = "https://challenges.cloudflare.com/turnstile/v0/siteverify";
            
            $data = [
                'secret' => $secret_key,
                'response' => $captcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ];

            $ch = curl_init($verify_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

            $response = curl_exec($ch);
            curl_close($ch);

            $response_data = json_decode($response, true);

            if ($response_data['success'] == true) {


                $user_email = $_POST['email'];


                $check_email_exist_sql = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $check_email_exist_sql->bind_param("s", $user_email);
                $check_email_exist_sql->execute();
                $check_email_exist_sql->store_result();

                if ($check_email_exist_sql->num_rows > 0) {

                    $email_error = "This email address is already in use. Please try a different email.";
                    $check_email_exist_sql->close();
                } else {

                    $update_email_sql = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $update_email_sql->bind_param("si", $user_email, $user_id);
                    $update_email_sql->execute();
                    $update_email_sql->close();


                    $verification_token = generateRandomToken(32);


                    $insert_token_sql = $conn->prepare("INSERT INTO email_verification_tokens (user_id, token) VALUES (?, ?)");
                    $insert_token_sql->bind_param("is", $user_id, $verification_token);
                    $insert_token_sql->execute();
                    $insert_token_sql->close();


                    sendVerificationEmail($user_email, $verification_token);


                    header('Location: ?email_sent=1');
                    exit;
                }
            } else {
                $captcha_error = "Captcha validation failed. Please try again.";
            }
        } else {
            $captcha_error = "Captcha not completed.";
        }
    }
}

$conn->close();

function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function sendVerificationEmail($email, $token) {
    require_once '/var/www/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once '/var/www/vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once '/var/www/vendor/phpmailer/phpmailer/src/Exception.php';

    $verification_link = "https://uptalkr.com/auth/email?verify=" . $token;
    $subject = "Email Verification for UpTalkr";
    $message = '
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
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
            <h2>Email Verification Required</h2>
            <p>Thank you for registering with UpTalkr. Please click the button below to verify your email address.</p>
            <a href="' . $verification_link . '" class="button">Verify Email</a>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' UpTalkr. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
    ';

    $mail = new PHPMailer();
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
        $mail->Body    = $message;

        $mail->send();
        echo 'Verification email has been sent.';
    } catch (PHPMailer\PHPMailer\Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UpTalkr</title>
  <link rel="stylesheet" href="https://uptalkr.com/assets/login2.css?version=2">
  <style>

.popup-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
}


.popup-content {
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
    max-width: 400px;
    width: 100%;
}


.popup-content h2 {
    font-family: 'Arial', sans-serif;
    margin-top: 0;
    font-size: 24px;
    color: #4CAF50;
}


.popup-content input[type="email"] {
    width: 80%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}


.cf-turnstile {
    margin: 20px 0;
}


.popup-content input[type="submit"] {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

.popup-content input[type="submit"]:hover {
    background-color: #45a049;
}

.cf-turnstile-success {
    background-color: #DFF0D8;
    color: #3C763D;
    padding: 10px;
    border: 1px solid #D6E9C6;
    border-radius: 5px; 
}

@media (max-width: 600px) {
    .popup-content {
        max-width: 300px;
    }

    .popup-content input[type="email"] {
        width: 100%;
    }
}
  </style>
</head>
<body>
  <div class="popup-container">
    <div class="popup-content">
      <h2>Welcome to UpTalkr!</h2>
      <?php if ($policy_accepted && !$email_verified): ?>
        <?php if (!$email_sent): ?>
          <p>Please provide your email address to continue:</p>
          <form method="post" action="">
              <input type="email" name="email" required placeholder="Enter your email" />
	      <div class="cf-turnstile" data-sitekey=""></div>
              <input type="submit" name="submit_email" value="Send Verification Email" class="accept-btn" />
              <?php if (isset($email_error)): ?>
                  <p class="error-message"><?php echo $email_error; ?></p>
              <?php endif; ?>
              <?php if (isset($captcha_error)): ?>
                  <p class="error-message"><?php echo $captcha_error; ?></p>
              <?php endif; ?>
          </form>
        <?php else: ?>
          <p>An email has already been sent! Please check your inbox and spam folder.</p>
        <?php endif; ?>
      <?php else: ?>
        <p>To continue using our service, you must accept our <a href="https://uptalkr.com/privacy-policy/" target="_blank">Privacy Policy</a>.</p>
        <form method="post" action="">
          <input type="hidden" name="accept_policy" value="1">
          <button type="submit" class="accept-btn">I Accept</button>
          <button type="button" class="decline-btn">I Don't Accept</button>
        </form>
        <div class="instructions" id="instructions">
          <p>Your account will be deleted because you do not accept the PRIVACY POLICY. Please send an email to <a href="mailto:contact@uptalkr.com">contact@uptalkr.com</a> and state that you want your account to be deleted. Make sure to include the following SupportID: <strong id="support-id"><?php echo $support_id; ?></strong></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    document.querySelector('.decline-btn').addEventListener('click', function() {
        document.getElementById('instructions').style.display = 'block';
    });
  </script>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</body>
</html>
