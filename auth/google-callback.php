<?php
require '/var/uptalkr/updb.php';
require '/var/www/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setClientId($googleClientId);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri('https://uptalkr.com/auth/google-callback.php');
$client->addScope('openid');
$client->addScope('email');
$client->addScope('profile');

if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit();
} else {
    $client->authenticate($_GET['code']);
    $access_token = $client->getAccessToken();

    if (isset($access_token['id_token'])) {
        $id_token = $access_token['id_token'];
        $decoded_token = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $id_token)[1]))), true);

        if ($decoded_token) {
            $google_userid = $decoded_token['sub'];
            $userEmail = $decoded_token['email'];
            $userName = $decoded_token['name'];

            $emailVerified = 1;

            $userName = preg_replace('/\s+/', '', strtolower($userName));

            $conn = new mysqli($dbsrv, $dbusr, $pass_db, $dbnam);
            if ($conn->connect_error) {
                die("Database connection failed: " . $conn->connect_error);
            }

            $originalUserName = $userName;
            $counter = 1;
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            while (true) {
                $stmt->bind_param("s", $userName);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows == 0) {
                    break;
                }
                $userName = $originalUserName . $counter;
                $counter++;
            }

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $userEmail);
            $stmt->execute();
            $result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_id = $row['id'];

    if (empty($row['google_userid'])) {
        $update_stmt = $conn->prepare("UPDATE users SET google_userid = ? WHERE id = ?");
        $update_stmt->bind_param("si", $google_userid, $user_id);
        $update_stmt->execute();
    }

    $_SESSION['loggedin'] = true;
    $_SESSION['userid'] = $user_id;

    header('Location: https://uptalkr.com/');
    exit();
} else {

    $placeholderPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, email_verified, google_userid) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $userName, $userEmail, $placeholderPassword, $emailVerified, $google_userid);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        $_SESSION['loggedin'] = true;
        $_SESSION['userid'] = $user_id;

        echo "<p>New user created! Welcome, " . htmlspecialchars($userName) . "!</p>";
        header('Location: https://uptalkr.com/');
        exit(); 
    } else {
        echo "<p>Error creating user: " . $stmt->error . "</p>";
        header('Location: https://uptalkr.com/login');
        exit();
    }
}



            $token = generateRandomToken();
            $remember_me = isset($_POST['remember_me']);
            $expiry_date = $remember_me ? date('Y-m-d H:i:s', strtotime('+40 days')) : date('Y-m-d H:i:s', strtotime('+1 hour'));

            $insert_token_query = $conn->prepare("INSERT INTO token (user_id, token, vanhenee) VALUES (?, ?, ?)");
            $insert_token_query->bind_param("iss", $user_id, $token, $expiry_date);
            $insert_token_query->execute();


            setcookie('uptalkr_token', $token, strtotime($expiry_date), '/', 'uptalkr.com', true, true);


            $stmt = $conn->prepare("INSERT INTO privacy_policy_accepted (user_id, accepted_at) VALUES (?, NOW())");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $stmt->close();
            $conn->close();
        } else {
            echo "<p>Unable to decode ID token.</p>";
	    header('Location: https://uptalkr.com/login');
        }
    } else {
        echo "<p>ID token not found in access token.</p>";
	header('Location: https://uptalkr.com/login');
    }
}


function generateRandomToken($length = 100) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $token;
}
?>
