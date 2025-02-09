<?php
session_start();

require '/var/uptalkr/updb.php';

$conn = new mysqli($dbsrv, $dbusr, $pass_db, $dbnam);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags($data));
}

if (isset($_GET['verify'])) {
    $token = sanitizeInput($_GET['verify']);

    $token_check_sql = $conn->prepare("SELECT user_id FROM email_verification_tokens WHERE token = ?");
    $token_check_sql->bind_param("s", $token);
    $token_check_sql->execute();
    $token_check_sql->bind_result($user_id);
    $token_check_sql->fetch();
    $token_check_sql->close();

    if ($user_id) {

        $update_user_sql = $conn->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
        $update_user_sql->bind_param("i", $user_id);
        $update_user_sql->execute();
        $update_user_sql->close();

        $delete_token_sql = $conn->prepare("DELETE FROM email_verification_tokens WHERE token = ?");
        $delete_token_sql->bind_param("s", $token);
        $delete_token_sql->execute();
        $delete_token_sql->close();

        $_SESSION['loggedin'] = true;
        $_SESSION['userid'] = $user_id;

        $new_token = generateRandomToken();
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 hour')); 

        $insert_token_sql = $conn->prepare("INSERT INTO token (user_id, token, vanhenee) VALUES (?, ?, ?)");
        $insert_token_sql->bind_param("iss", $user_id, $new_token, $expiry_date);
        $insert_token_sql->execute();
        $insert_token_sql->close();


        setcookie('uptalkr_token', $new_token, strtotime($expiry_date), '/', 'uptalkr.com', true, true);
    } else {

        header('Location: https://uptalkr.com/');
        exit;
    }
} else {

    header('Location: https://uptalkr.com/');
    exit;
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified - UpTalkr</title>
    <link rel="stylesheet" href="../../assets/login2.css?version=2">
    <style>
        .verification-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: rgba(0,0,0,0.5);
        }
        .verification-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            max-width: 400px;
        }
        .verification-content h2 {
            margin-top: 0;
        }
        .verification-content button {
            margin: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: green;
            color: white;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-content">
            <h2>Your email has been verified!</h2>
            <p>Thank you for verifying your email. You can now use all features of UpTalkr.</p>
            <a href="https://uptalkr.com/"><button>Go to Homepage</button></a>
        </div>
    </div>
</body>
</html>
