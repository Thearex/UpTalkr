<?php
session_start();
require '/var/uptalkr/updb.php';

$_SESSION = array();

setcookie('uptalkr_token', '', time() - 3600, '/', 'uptalkr.com', true, true);

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

if (isset($_COOKIE['uptalkr_token'])) {
    $token = $_COOKIE['uptalkr_token'];

    $conn = new mysqli($servername, $username, $password_db, $dbname);

    if ($conn->connect_error) {
        die("Yhteys ep onnistui: " . $conn->connect_error);
    }

    $delete_token_query = "DELETE FROM token WHERE token = '$token'";
    $conn->query($delete_token_query);

    $conn->close();
}

header('Location: https://uptalkr.com/');
exit;
?>
