<?php

require '/var/uptalkr/check-cookie-token.php';

session_start();

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
?>

<!DOCTYPE html>
<html>
<head>
  <title>UpTalkr - terms of service</title>
  <meta http-equiv="refresh" content="120">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="UTF-8">
  <link rel="icon" href="../assets/logo.png" type="image/x-icon">
  <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/terms-of-service.css">
</head>
<style>
  body {
    font-family: "Circular Std Medium", Arial, sans-serif;
    background-color: #fff;
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
    background-color: #555;
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
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p><strong>Terms of Service for UpTalkr Social Media Platform</strong></p>
<p><strong>Last Updated: 14.10.2024 19:12 (UTC+3)</strong></p>
<p>Welcome to <strong>UpTalkr</strong>, a non-commercial social media platform designed to connect people and facilitate content sharing. By using UpTalkr, you agree to these Terms of Service.</p>
<p>Please read these Terms of Service carefully before using UpTalkr. If you do not agree to these terms, please do not use the platform.</p>
<h3>1. Acceptance of Terms</h3>
<p>By accessing or using UpTalkr, you agree to be bound by these Terms of Service. If you do not agree, refrain from using UpTalkr.</p>
<h3>2. Registration and Account Security</h3>
<ul>
<li>You are responsible for maintaining the security of your UpTalkr account. Do not share your password or allow unauthorized access.</li>
<li>You must be at least 13 years old to use UpTalkr. Users under 13 must have explicit parental consent or supervision (if allowed by applicable laws).</li>
<li>You agree to provide accurate and complete information during registration.</li>
<li>You are responsible for any activity that occurs under your account.</li>
</ul>
<h3>3. User Content</h3>
<ul>
<li>You retain ownership of the content you post on UpTalkr. By posting content, you grant UpTalkr a worldwide, non-exclusive, royalty-free license to use, display, and distribute your content.</li>
<li>Do not post content that infringes on others' rights, including copyright, trademark, or privacy.</li>
<li>UpTalkr may remove or block access to any content that violates these Terms or our content guidelines.</li>
</ul>
<h3>4. Private Messages</h3>
<ul>
<li>UpTalkr does not take responsibility for the content of private messages between users. These communications are solely between the users involved, and UpTalkr is not liable for any disputes or issues arising from such interactions.</li>
<li>While UpTalkr encourages users to engage in respectful and safe communication, private messages are not monitored by UpTalkr unless reported for violations of these Terms or the law.</li>
</ul>
<h3>5. Prohibited Activities</h3>
<ul>
<li>You may not use UpTalkr for illegal or unauthorized purposes.</li>
<li>You may not post harmful, offensive, or discriminatory content.</li>
<li>You may not engage in spamming or disruptive activities.</li>
<li>Report any inappropriate or harmful content to UpTalkr administrators.</li>
</ul>
<h3>6. Reporting Violations</h3>
<p>If you encounter content or behavior that violates these Terms, report it to UpTalkr administrators. We take reports seriously and will take appropriate action.</p>
<h3>7. Termination</h3>
<p>We reserve the right to terminate or suspend your access to UpTalkr without notice for violations of these Terms of Service or for any other reason at our discretion.</p>
<h3>8. Changes to Terms</h3>
<p>We may update these Terms of Service from time to time. Changes will be posted on UpTalkr, and your continued use after such changes constitutes acceptance.</p>
<h3>9. Limitation of Liability</h3>
<p>UpTalkr is not responsible for any direct, indirect, incidental, or consequential damages resulting from your use of the platform. Use the platform at your own risk.</p>
<h3>10. Privacy</h3>
<p>For details on how we collect and use your information, please refer to our <a href="https://uptalkr.com/privacy-policy/" target="_new" rel="noopener">Privacy Policy</a>.</p>
<h3>11. Dispute Resolution</h3>
<p>Any legal disputes arising from these Terms or your use of UpTalkr shall be resolved in accordance with Finnish law. Disputes will be handled by the courts of Finland, unless otherwise required by mandatory applicable laws.</p>
<h3>12. Contact Us</h3>
<p>If you have any questions or concerns regarding these Terms, please contact us at <strong><a rel="noopener">contact@uptalkr.com</a></strong>.</p>
<p>By using UpTalkr, you agree to the terms outlined in these Terms of Service. We hope you enjoy your experience!</p>