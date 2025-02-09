<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

require '/var/uptalkr/updb.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("DB FAILED");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["about_me"])) {
    $userID = $_SESSION["userid"];
    $aboutMeText = htmlspecialchars($_POST["about_me"]);

    if (strlen($aboutMeText) > 60) {
        header('Location: https://uptalkr.com/settings/?error=1');
        exit;
    } elseif (preg_match('/https?:\/\/[^\s]+/', $aboutMeText)) {
        header('Location: https://uptalkr.com/settings/?error=2');
        exit;
    } else {
        $updateQuery = $conn->prepare("INSERT INTO settings (user_id, about_me) VALUES (?, ?) ON DUPLICATE KEY UPDATE about_me = ?");
        $updateQuery->bind_param("iss", $userID, $aboutMeText, $aboutMeText);
        $updateQuery->execute();

        if ($updateQuery->affected_rows > 0) {
            header('Location: https://uptalkr.com/settings/?success=1');
            exit;
        } else {
            header('Location: https://uptalkr.com/settings/');
            exit;
        }
    }
} else {
    header('Location: https://uptalkr.com/settings/?error=4');
    exit;
}
?>
