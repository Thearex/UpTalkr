<?php
session_start();


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

require '/var/uptalkr/db.php';

if (isset($_SESSION["userid"])) {
    $userID = $_SESSION["userid"];

    $userQuery = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userResult && $userRow = $userResult->fetch_assoc()) {
        $username = $userRow['username'];

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $newUsername = $_POST["new_username"];

            if (strlen($newUsername) < 4) {
                echo "Username must be at least four (4) letters"; 
                header("Location: https://uptalkr.com/settings?error=55");
                exit;
            } elseif (strlen($newUsername) > 22) {
                echo "Username must not be more than 22 characters long";
                header("Location: https://uptalkr.com/settings?error=6");
                exit;
            } elseif (strpos($newUsername, ' ') !== false) {
                echo "Username must not contain spaces";
                header("Location: https://uptalkr.com/settings?error=7");
                exit; 
            }

            $forbiddenWords = array(
                "tähän-listana"
            );

            if (containsForbiddenWord($newUsername, $forbiddenWords)) {
                echo "Username contains forbidden word.";
                header("Location: https://uptalkr.com/settings?error=6");
                exit; 
            }

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR username LIKE ?");
$stmt->bind_param("ss", $newUsername, $username_like);
$username_like = "%$newUsername%";
$stmt->execute();
$checkResult = $stmt->get_result();

if ($checkResult->num_rows > 0) {
    echo "The username is already in use. Choose something else.";
    header("Location: https://uptalkr.com/settings?error=8");
    exit;
}

            $checkTimeQuery = "SELECT vaihtoaika FROM usernameaika WHERE userid = ? ORDER BY vaihtoaika DESC LIMIT 1";
            $stmt = $conn->prepare($checkTimeQuery);
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $checkTimeResult = $stmt->get_result();

            if ($checkTimeResult->num_rows > 0) {
                $lastChangeTime = $checkTimeResult->fetch_assoc()['vaihtoaika'];
                $currentTime = time();
                $weekInSeconds = 7 * 24 * 60 * 60;

                if ($currentTime - strtotime($lastChangeTime) < $weekInSeconds) {
                    echo "You can only change your username once a week.";
                    header("Location: https://uptalkr.com/settings?error=9");
                    exit;
                }
            }


            $updateQuery = "UPDATE users SET username = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $newUsername, $userID);
            $stmt->execute();

            echo "Username updated successfully.";
            header("Location: https://uptalkr.com/settings");


            $insertTimeQuery = "INSERT INTO usernameaika (userid, vaihtoaika) VALUES (?, NOW())";
            $stmt = $conn->prepare($insertTimeQuery);
            $stmt->bind_param("i", $userID);
            $stmt->execute();
        }
    } else {
        echo "<p>User not found.</p>";
        header("Location: https://uptalkr.com/login");
        exit;
    }
} else {
    echo "<p>Login required.</p>";
    header("Location: https://uptalkr.com/login");
    exit; 
}

$conn->close();


function containsForbiddenWord($input, $forbiddenWords) {
    foreach ($forbiddenWords as $word) {
        if (stripos($input, $word) !== false) {
            return true;
        }
    }
    return false;
}
?>
