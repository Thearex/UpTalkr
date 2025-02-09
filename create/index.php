<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.uptalkr.com', 
    'secure' => true,
    'httponly' => true, 
    'samesite' => 'Lax' 
]);

require '/var/uptalkr/check-cookie-token.php';
session_start();


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login');
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

$posterror = "";

if (isset($_GET['error'])) {
    $error = $_GET['error'];

    switch ($error) {
        case '1':
            $posterror = "Database connection failed";
            break;

        case '2':
            $posterror = "The post must have at least one word!";
            break;
        case '3':
            $posterror = "The file is not an image or video!";
            break;
        case '4':
            $posterror = "Failed to add ZyPoints!";
            break;
        case '5':
            $posterror = "Error saving the post to the database!";
            break;
        case '6':
            $posterror = "Video/Photo is too big. Limit is 2GB";
            break;
        case '7':
            $posterror = "Please wait for 10 minutes before you can submit a new post.";
            break;
        case '8':
            $posterror = "Invalid CAPTCHA";
            break;
        case '9':
            $posterror = "Invalid Charecters";
            break;
        case '10':
            $posterror = "File does not comply with our community guidelines.";
            break;
        // TÄMÄ ERROR ON TEKOÄLYN ERROR 11!!!
        case '11':
            $posterror = "There was an error sending the post. If this continues, please contact staff at contact@uptalkr.com (ERROR 11)";
            break;

        default:
            $posterror = "Contact contact@uptalkr.com";
    }

}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create a cool post on UpTalkr">
    <title>UpTalkr - Create</title>
    <link rel="icon" href="../assets/logo.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://uptalkr.com/assets/navbar2.css?ver=1">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #50e3c2;
            --background-color: #f4f7fa;
            --container-bg: #ffffff;
            --text-color: #333333;
            --border-color: #e0e0e0;
            --error-color: #e74c3c;
            --button-hover: #357ab8;
            --input-focus: #4a90e2;
            --font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--background-color);
            font-family: var(--font-family);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .post-form-container {
            background-color: var(--container-bg);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 24px;
            font-weight: 700;
        }

        .post-form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        label {
            font-weight: 500;
            margin-bottom: 10px;
            font-size: 16px;
            width: 100%;
            text-align: left;
        }

        .input-style input[type="text"], 
.input-style textarea {
    width: 100%;
    padding: 14px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}


        input[type="text"]:focus, textarea:focus {
            border-color: var(--input-focus);
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 150px;
        }

        .custom-file-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            text-align: center;
        }

        .custom-file-upload input[type="file"] {
            display: none;
        }

        .custom-upload-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            transition: background-color 0.3s ease;
            border: none;
        }

        .custom-upload-btn:hover {
            background-color: var(--button-hover);
        }

        .file-chosen {
            margin-top: 10px;
            font-size: 14px;
            color: var(--text-color);
        }

        .custom-submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 14px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            transition: background-color 0.3s ease;
            border: none;
        }

        .custom-submit-btn:hover {
            background-color: var(--button-hover);
        }

        .guidelines {
            margin-top: 20px;
            font-size: 14px;
        }

        .guidelines a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .guidelines a:hover {
            text-decoration: underline;
        }

        .cf-turnstile-container {
            margin: 25px 0;
            display: flex;
            justify-content: center;
        }

        .error-message {
            color: var(--error-color);
            margin-top: 10px;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .post-form-container {
                padding: 30px;
            }

            h1 {
                font-size: 24px;
            }

            .custom-submit-btn {
                padding: 12px;
                font-size: 14px;
            }

            input[type="text"], textarea {
                padding: 12px;
                font-size: 14px;
            }
        }
        
    </style>
</head>
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
        <a href="/create">
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
<body>

    <div class="post-form-container">
        <h1>Create a Cool Post on UpTalkr</h1>
        <form class="post-form" action="https://api.uptalkr.com/upload.php" method="post" enctype="multipart/form-data">
        <div class="input-style">
    <label for="post-title">Post Title *</label>
    <input type="text" name="post-title" id="post-title" maxlength="100" required placeholder="Enter your post title">

    <label for="post-content">Write Your Post *</label>
    <textarea name="post-content" id="post-content" rows="6" maxlength="250" required placeholder="Share your thoughts..."></textarea>
</div>


            <div class="custom-file-upload">
                <label class="custom-upload-btn" for="file-upload">Choose Image/Video</label>
                <input type="file" id="file-upload" name="file-upload" accept="image/*,video/*" onchange="updateFileName()">
                <div id="file-name" class="file-chosen">No image/video chosen</div>
            </div>

            <input type="hidden" name="MAX_FILE_SIZE" value="2147483648">

            <div class="cf-turnstile-container">
                <div class="cf-turnstile" data-sitekey=""></div>
            </div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

            <input type="submit" class="custom-submit-btn" value="Upload" <?php if (isset($_SESSION['next_post_time']) && time() < $_SESSION['next_post_time']) { echo "disabled"; } ?>>

            <div class="guidelines">
                <p>By posting, you agree to the <a href="https://uptalkr.com/community-guidelines/" target="_blank">Community Guidelines</a>.</p>
            </div>
            <p class="error-message"><?php echo $posterror; ?></p>
        </form>
    </div>

    <script>
        function updateFileName() {
            const fileInput = document.getElementById('file-upload');
            const fileName = fileInput.files.length > 0 ? fileInput.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        }
    </script>

</body>
</html>
