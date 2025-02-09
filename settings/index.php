<?php
session_start();

require '/var/uptalkr/updb.php';
require '/var/uptalkr/check-cookie-token.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $loginLink = '<a href="/login">Login</a>';
} else {
  $loginLink = '
  <div class="dropdown">
    <a class="profile-button">Profile</a>
    <div class="dropdown-content">
      <a href="https://uptalkr.com/profile">Profile</a>
      <a href="https://uptalkr.com/settings">Settings</a>
      <a href="https://uptalkr.com/messages">Messages</a>
      <a href="https://uptalkr.com/studio">Studio</a>
      <a href="https://uptalkr.com/logout">Logout</a>
    </div>
  </div>';
}

$host = $servername;
$username = $username;
$password = $password;
$dbname = $dbname;


$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Tietokantayhteys ep onnistui: " . htmlspecialchars($conn->connect_error));
}

$username = '';
$aboutmetext = '';
$googleUser = false;
$user_email = '';
$error_message = "";

if (isset($_SESSION["userid"])) {
    $userID = $_SESSION["userid"];

    $userQuery = "SELECT username, email, google_userid, receive_messages, blocked_users FROM users WHERE id = ?";
    $stmt1 = $conn->prepare($userQuery);
    $stmt1->bind_param("i", $userID);
    $stmt1->execute();
    $stmt1->bind_result($username, $user_email, $google_userid, $receive_messages, $blocked_users);
    $stmt1->fetch();
    $stmt1->close();

    $googleUser = !is_null($google_userid);

    $aboutQuery = "SELECT about_me FROM settings WHERE user_id = ?";
    $stmt2 = $conn->prepare($aboutQuery);
    $stmt2->bind_param("i", $userID);
    $stmt2->execute();
    $aboutResult = $stmt2->get_result();
    $aboutmetext = ($aboutRow = $aboutResult->fetch_assoc()) ? htmlspecialchars($aboutRow['about_me'], ENT_QUOTES, 'UTF-8') : "About Me has not been set!";
    $stmt2->close();
}

$virhe = "";

if (isset($_GET['error'])) {
    $error = $_GET['error'];
    $errorMessages = [
        '1' => "About me text can be up to 60 characters long.",
        '2' => "About me text must not contain links.",
        '3' => "Error updating about me text. Try again later.",
        '4' => "The request could not be processed. Try again later.",
        '5' => "Username must be at least five (5) letters.",
        '6' => "Username must not be more than 22 characters long.",
        '7' => "Username must not contain spaces.",
        '8' => "The username is already in use. Choose something else.",
        '9' => "You can only change your username once a week.",
        '10' => "Database error.",
        '11' => "Storage error.",
        '12' => "Invalid profile picture.",
        '13' => "The file is not an allowed file type. Only pictures are allowed.",
        '14' => "Profile picture is too big. Max 5MB",
        '15' => "Invalid banner image.",
        '16' => "Banner size has to be 1382   634 px.",
        '17' => "Invalid banner image.",
        '18' => "New password and confirm password don't match.",
        '19' => "New password cannot contain spaces.",
        '20' => "New password must be between 10 and 150 characters long.",
        '21' => "File does not comply with our community guidelines.",
    ];
    $virhe = $errorMessages[$error] ?? "Unknown error.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_email']) && !$googleUser) {
    $current_password = $_POST['current_password'];

    $password_check_sql = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $password_check_sql->bind_param("i", $userID);
    $password_check_sql->execute();
    $password_check_sql->bind_result($hashed_password);
    $password_check_sql->fetch();
    $password_check_sql->close();

    if (password_verify($current_password, $hashed_password)) {
        $update_email_sql = $conn->prepare("UPDATE users SET email = NULL, email_verified = 0 WHERE id = ?");
        $update_email_sql->bind_param("i", $userID);
        $update_email_sql->execute();
        $update_email_sql->close();

        setcookie('uptalkr_token', '', time() - 3600, '/', 'uptalkr.com', true, true);

        if (isset($_COOKIE['uptalkr_token'])) {
            $token = $_COOKIE['uptalkr_token'];
            $delete_token_query = "DELETE FROM token WHERE token = ?";
            $delete_token_stmt = $conn->prepare($delete_token_query);
            $delete_token_stmt->bind_param("s", $token);
            $delete_token_stmt->execute();
            $delete_token_stmt->close();
        }

        unset($_SESSION['loggedin']);
        header('Location: https://uptalkr.com/auth/set');
        exit;
    } else {
        $virhe = "Incorrect password. Email change failed.";
    }
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_settings'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $receive_messages = isset($_POST['receive_messages']) ? 1 : 0;
    $blocked_users = $_POST['blocked_users'];

    if (preg_match('/\s/', $blocked_users)) {
        $error_message = "Blocked users list must not contain spaces.";
    } else {
        if (!preg_match('/^(\d+,)*\d+$/', $blocked_users)) {
            $error_message = "Blocked users list must be a comma-separated list of user IDs.";
        } else {
            $update_query = $conn->prepare("UPDATE users SET receive_messages = ?, blocked_users = ? WHERE id = ?");
            $update_query->bind_param("isi", $receive_messages, $blocked_users, $userID);

            if ($update_query->execute()) {
                echo "<div class='alert alert-success'>Settings updated successfully.</div>";
            } else {
                echo "<div class='alert alert-danger'>Failed to update settings.</div>";
            }
            $update_query->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>UpTalkr - Settings</title>
<link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
<link rel="stylesheet" href="https://uptalkr.com/assets/zysettings.css?v=1">
<link rel="stylesheet" href="https://uptalkr.com/assets/navbar2.css">
</head>
<style>
  body {
    font-family: "Circular Std Medium", Arial, sans-serif;
    margin: 0; 
    padding: 0;
  }

  nav {
    background-color: #fff;
    padding: 20px;
    transition: transform 0.3s ease;
    top: 0;
    left: 0;
    height: 40px;
    width: 100%;
    position: fixed;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 100;
  }

  .nav-logo {
    margin-left: 20px;
    text-align: center;
  }

  .nav-logo img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
  }

  .nav-links {
    display: flex;
    align-items: center;
  }

  .nav-item {
    margin-left: 20px;
  }

  .nav-item a {
    text-decoration: none;
    color: #fff;
    padding: 10px 16px;
    transition: color 0.3s;
    gap: 10px;
  }

  .nav-item:hover a {
    color: #fff;
  }

  .nav-title {
    color: #fff;
    font-size: 24px;
    margin-left: 20px;
  }

  ul {
    list-style-type: none;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
  }

  li {
    margin-left: 20px;
  }

  .text {
    text-decoration: none;
    color: #fff;
    padding: 10px 20px;
    border-radius: 30px;
    transition: background-color 0.3s;
  }

  .text:hover {
    background-color: #333;
  }

  .search-form {
    display: flex;
    align-items: center;
    border: 1px solid black;
    border-radius: 8px;
    padding: 5px;
    box-shadow: none;
  }

  .search-form input {
    padding: 8px;
    border-radius: 4px;
    border: none;
    margin-right: 4px;
  }

  .icon {
    width: 40px;
    height: 40px;
  }

  .search-form button {
    padding: 8px 16px;
    border-radius: 4px;
    border: none;

    color: #fff;
    transition: background-color 0.3s;
  }

  .search-form button:hover {
    background-color: #333;
  }

  .icon-container {
    position: relative;
    display: inline-block;
  }

  .icon-text {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #000;
    color: #fff;
    padding: 5px;
    font-size: 12px;
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.3s;
  }

  .icon-container:hover .icon-text {
    visibility: visible;
    opacity: 1;
  }

  .nav-logo span {
    margin-right: 3 !important;
    display: flex;
    align-items: center;
  }

  a {
    text-decoration: none;
  }

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #ffffff;
    min-width: 160px;
    max-width: 180px;
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    padding: 0;
}

.dropdown-content a {
    background-color: #ffffff !important;
    color: #333 !important;
    padding: 8px 12px;
    text-decoration: none;
    display: block;
    border-radius: 10px;
    transition: background-color 0.3s ease;
}

.dropdown-content a:hover {
    background-color: #f0f0f0;
}

.dropdown:hover .dropdown-content {
    display: block;
}

.login a {
    display: inline-block;
    padding: 10px 20px;
    background: linear-gradient(#d284ff, #00afff);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.login a:hover {
    background: linear-gradient(#d284ff, #00afff);
}

.dropdown-content a {
    background: none !important;
    background-color: #ffffff !important;
}

.profile-button {
    padding: 10px 20px;
    background: linear-gradient(45deg, #d284ff, #00afff);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    transition: background 0.3s ease;
}

.profile-button:hover {
    background: linear-gradient(45deg, #00afff, #d284ff);
}

form {
    margin: 20px 0;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    font-family: "Circular Std Medium", Arial, sans-serif;
}

.form-control {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 2px solid #ddd;
    border-radius: 8px;
    box-sizing: border-box;
    font-size: 16px;
    font-family: "Circular Std Medium", Arial, sans-serif;
    transition: border-color 0.3s;
}
.form-control:focus {
    border-color: #00afff;
    outline: none;
}

.form-group h4 {
    font-size: 20px;
    color: #333;
    margin-bottom: 10px;
}

.form-group label {
    font-size: 14px;
    color: #555;
    display: block;
    margin-bottom: 5px;
}

textarea.form-control {
    height: 100px;
    resize: none;
}

input[type="checkbox"] {
    margin-right: 10px;
    transform: scale(1.2);
}

.alert {
    padding: 10px 15px;
    border-radius: 5px;
    margin-top: 10px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

button, input[type="submit"] {
    padding: 10px 20px;
    background: linear-gradient(45deg, #00afff, #d284ff);
    color: white;
    border: none;
    border-radius: 30px;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s;
    font-size: 16px;
}

button:hover, input[type="submit"]:hover {
    background: linear-gradient(45deg, #d284ff, #00afff);
    transform: scale(1.05);
}

.text-center {
    text-align: center;
}

input[type="file"] {
    display: none;
}

label[for="profile-picture"], label[for="banner-picture"] {
    display: inline-block;
    padding: 10px 20px;
    background: #00afff;
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 10px;
}

label:hover {
    background-color: #005f99;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    background: linear-gradient(45deg, #d284ff, #00afff);
    color: white;
    text-decoration: none;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s;
    font-size: 16px;
    margin: 5px;
    font-family: "Circular Std Medium", Arial, sans-serif;
}

.btn:hover {
    background: linear-gradient(45deg, #00afff, #d284ff);
    transform: scale(1.05);
}

.btn-danger {
    background: linear-gradient(45deg, #ff4b4b, #ff0000);
}

.btn-danger:hover {
    background: linear-gradient(45deg, #ff0000, #ff4b4b);
}

.btn-secondary {
    background: linear-gradient(45deg, #555555, #333333);
}

.btn-secondary:hover {
    background: linear-gradient(45deg, #333333, #555555);
}

.text-center {
    text-align: center;
    margin-top: 20px;
}

.text-center a {
    margin: 0 10px;
}


</style>
<body>
<nav>
    <div class="nav-logo">
        <a href="https://uptalkr.com">
            <img src="https://uptalkr.com/assets/logo.png" alt="Logo">
        </a>
    </div>
    <div class="nav-links">
        <div class="nav-item">
            <form class="search-form" action="https://uptalkr.com/api/haku.php" method="GET">
                <input type="text" name="s" placeholder="Search" onkeydown="handleKeyDown(event)">
            </form>
        </div>
        <div class="nav-item">
            <div class="icon">
                <a href="https://uptalkr.com/create">
                    <img src="https://uptalkr.com/assets/post2.png" alt="Create!" class="icon">
                    <span class="icon-text">Create!</span>
                </a>
            </div>
        </div>
        <p>&nbsp;</p>
        <div class="nav-item">
            <div class="login">
                <?php echo $loginLink; ?>
            </div>
        </div>
        <div class="nav-item">
            <a></a>
        </div>
    </div>
</nav>
<div class="container">

    <div class="profile-container">
        <div class="profile-details">
            <h2 id="username"><?php echo htmlspecialchars($username); ?></h2>
            <p id="about-me"><?php echo htmlspecialchars($aboutmetext); ?></p>
	    <p id="email">Email: <?php echo htmlspecialchars($user_email); ?></p>
        </div>

        <?php if (isset($virhe) && !empty($virhe)): ?>
            <div style="color: red; margin-bottom: 20px;">
                <?php echo htmlspecialchars($virhe); ?>
            </div>
        <?php endif; ?>


        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>


        <form id="usernameForm" method="POST" action="https://uptalkr.com/api/username.php">
            <div class="form-group">
                <label for="usernameInput">Change username:</label>
                <input type="text" class="form-control" id="usernameInput" name="new_username" placeholder="Enter new username">
                <button type="submit" class="btn btn-primary mt-2">Change</button>
            </div>
        </form>

        <form id="aboutMeForm" method="POST" action="https://uptalkr.com/api/changeaboutme.php">
            <div class="form-group">
                <label for="aboutMeInput">Change About Me:</label>
                <textarea class="form-control" id="aboutMeInput" name="about_me" rows="2" 
                    placeholder="<?php echo isset($aboutmetext) && $aboutmetext !== '' ? htmlspecialchars($aboutmetext, ENT_QUOTES, 'UTF-8') : 'Set About Me text'; ?>" 
                    maxlength="60"><?php echo isset($aboutmetext) && $aboutmetext !== '' ? htmlspecialchars($aboutmetext, ENT_QUOTES, 'UTF-8') : ''; ?>
                </textarea>
                <button type="submit" class="btn btn-primary mt-2">Change</button>
            </div>
        </form>

        <?php if ($googleUser): ?>
            <div class="alert alert-info">
                <p>You are logged in with Google, so you cannot change your password and email.</p>
            </div>
        <?php else: ?>
            <form id="changeEmailForm" method="POST" action="">
                <div class="form-group">
		    <h4>Change Email</h4>
                    <label for="currentPassword">Enter your current password to change email:</label>
                    <input type="password" class="form-control" id="currentPassword" name="current_password" required>
		    <button type="submit" name="change_email" class="btn btn-warning mt-2">Change Email</button>
                </div>
            </form>

            <form method="POST" action="https://uptalkr.com/api/change_password.php">
                <div class="form-group">
                    <h4>Change Password</h4>
                    <label for="currentPassword">Current Password:</label>
                    <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password:</label>
                    <input type="password" class="form-control" id="newPassword" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Confirm New Password:</label>
                    <input type="password" class="form-control" id="confirmNewPassword" name="confirm_new_password" required>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Change Password</button>
            </form>
        <?php endif; ?>
        <p>&nbsp;</p>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group">
                <label for="receive_messages">Receive Private Messages:</label>
                <input type="checkbox" name="receive_messages" id="receive_messages" <?php echo ($receive_messages) ? 'checked' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="blocked_users">Blocked Users (Enter user IDs separated by commas):</label>
                <input type="text" class="form-control" id="blocked_users" name="blocked_users" value="<?php echo htmlspecialchars($blocked_users); ?>">
            </div>
            <button type="submit" class="btn btn-primary" name="update_settings">Update Private Messages settings</button>
        </form>

        <p>&nbsp;</p>

        <form method="POST" action="https://uptalkr.com/api/profiilikuva_upload.php" enctype="multipart/form-data">
            <label for="profile-picture">Change profile picture:</label><br>
            <input type="file" id="profile-picture" name="profile-picture" accept="image/*" required><br>
            <input type="submit" value="Upload" class="change-button">
        </form>
        <p>&nbsp;</p>
        <form method="POST" action="https://uptalkr.com/api/banneri_upload.php" enctype="multipart/form-data">
            <label for="banner-picture">Change banner picture:</label><br>
            <input type="file" id="banner-picture" name="banner-picture" accept="image/*" required><br>
            <input type="submit" value="Upload" class="change-button">
        </form>
        <p>&nbsp;</p>
        <div class="form-group">
            <form method="POST" action="https://uptalkr.com/api/getsupportid.php">
                <button type="submit" class="btn btn-primary mt-2">Get SupportID</button>
            </form>
        </div>
        <p>&nbsp;</p>
        <div class="text-center">
            <a href="https://uptalkr.com/api/profiilikuva_delete.php" class="btn btn-danger">Delete profile picture</a>
            <a href="#" id="deleteLink" class="btn btn-danger">Delete banner picture</a>
            <a href="https://uptalkr.com/logout" class="btn btn-secondary">Logout</a>
        </div>
        <p>&nbsp;</p>
        <?php
        echo '<div class="punainencontainer">';
        echo '<h4> Deleting a user</h4>';
        echo '<p>If you want to delete your user, please contact us at: <a href="mailto:contact@uptalkr.com">contact@uptalkr.com</a></p>';
        echo '</div>';
        ?>
    </div>
</div>
<script>
    document.getElementById('deleteLink').addEventListener('click', function(event) {
        event.preventDefault();
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "https://uptalkr.com/api/banner_delete.php");
        xhr.send();
    });
</script>
</body>
<p class="bottom-left">
    &copy; uptalkr.com
</p>
</html>
