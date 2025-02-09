<?php
session_start();
require '/var/uptalkr/check-cookie-token.php';

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

<link rel="icon" href="../assets/logo.png" type="image/x-icon">

<!DOCTYPE html>
<html>
<head>
  <title>UpTalkr - Community-Guidelines</title>
  <meta http-equiv="refresh" content="120">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/navbar2.css?v=1">
  <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/terms-of-service.css">
</head>
<style>
.search-form {
  margin-top: 15px !important;
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
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p style="text-align: left;">Community Guidelines for Uptalkr Social Media Platform</p>
<p style="text-align: left;">Last Updated: 31.8.2024 20.07 (UTC+2 DD.MM.YYYY)</p>
<ol style="text-align: left;">
<li>
<p><strong>Welcome and Kindness</strong>: Create an open and welcoming community where everyone feels invited. Respect and treat other members with kindness and respect.</p>
</li>
<li>
<p><strong>Respect Diversity</strong>: Value diversity and inclusivity. Do not discriminate against anyone based on race, ethnicity, religion, gender, sexual orientation, or any other characteristic.</p>
</li>
<li>
<p><strong>Safe Environment</strong>: Ensure that the community is safe for all members. Immediately report any inappropriate behavior or harassment to the moderators.</p>
</li>
<li>
<p><strong>Respectful Communication</strong>: Use constructive and respectful language. Avoid making offensive, violent, or threatening comments.</p>
</li>
<li>
<p><strong>Privacy and Personal Information</strong>: Respect the privacy of others. Do not share personal information or other confidential information without consent.</p>
</li>
<li>
<p><strong>Against Inappropriate Content</strong>: Immediately report any inappropriate content, such as violence, nudity, or hate speech. Moderators will remove such content and take necessary actions.</p>
</li>
<li>
<p><strong>Copyrights and Permissions</strong>: Follow copyright and other legal rules. Do not share or distribute content created by others without proper permissions.</p>
</li>
<li>
<p><strong>Terms and Rules</strong>: Familiarize yourself with and follow the platform's terms of service and community guidelines. Violations may result in account restrictions or removal.</p>
</li>
<li>
<p><strong>Constructive Feedback and Discussion</strong>: Provide constructive feedback and engage in open and respectful discussion. Avoid trolling and provocation.</p>
</li>
<li>
<p><strong>Community Promotion</strong>: Participate in building and developing the community. Share valuable content and help other members when needed.</p>
</li>
</ol>
<p style="text-align: left;">By adhering to these community guidelines, we ensure that our community remains safe, welcoming, and constructive for all members.</p>