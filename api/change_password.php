<?php
require '/var/uptalkr/updb.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $conn = mysqli_connect($servername, $username, $password, $dbname);

    if (!$conn) {
        die("Tietokantayhteys ep�onnistui: " . mysqli_connect_error());
    }

    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmNewPassword = $_POST['confirm_new_password'];
    $userid = $_SESSION['userid'];

    if ($newPassword !== $confirmNewPassword) {
	header("Location: https://uptalkr.com/settings?error=18");
        die("New password and confirm password don't match.");
	exit();
    }

    if (strpos($newPassword, ' ') !== false) {
	header("Location: https://uptalkr.com/settings?error=19");
        die("New password cannot contain spaces.");
	exit();
    }

if (empty($newPassword) || empty($confirmNewPassword)) {
    header("Location: https://uptalkr.com/settings?error=19");
    die("New password and confirm new password cannot be empty.");
    exit();
}


    if (strlen($newPassword) < 10 || strlen($newPassword) > 150) {
	header("Location: https://uptalkr.com/settings?error=20");
        die("New password must be between 10 and 150 characters long.");
	exit();
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashedPassword, $userid);
    $stmt->execute();

    header("Location: https://uptalkr.com/settings");
    exit();
} else {
    header("Location: https://uptalkr.com/settings");
    exit();
}
?>
