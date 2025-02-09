<?php
session_start();
require '/var/uptalkr/check-cookie-token.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $loginLink = '<a href="/login" class="button">Login</a>';
} else {
  $loginLink = '
  <div class="dropdown">
    <a class="profile-button button">Profile</a>
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
  <title>UpTalkr - Privacy Policy</title>
  <meta http-equiv="refresh" content="120">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/navbar2.css"> 
</head>
<style>
    * {
        margin: 0;
        padding: 0;
    }

    body {
        font-family: "Circular Std Medium", Arial, sans-serif;
        background-color: #f3f4f6;
        color: #333;
        height: 100%;
        overflow-x: hidden;
    }

    html, body {
        height: 100%;
        overflow-x: hidden;
    }

.login {
  margin-top: -20px !important;
}

    .container {
        max-width: 900px;
        width: 100%;
        margin: 40px auto;
        background-color: #ffffff;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: box-shadow 0.3s ease;
    }

    .container:hover {
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
    }

    h1 {
        font-size: 2.8em;
        color: #1a73e8;
        margin-bottom: 20px;
        font-weight: bold;
    }

    h2 {
        font-size: 1.6em;
        color: #333;
        margin-top: 20px;
        font-weight: 600;
    }

    p {
        line-height: 1.7;
        color: #666;
        font-size: 1.1em;
        text-align: left;
        margin-bottom: 20px;
    }

    ul {
        text-align: left;
        padding-left: 20px;
        color: #666;
        font-size: 1.1em;
        line-height: 1.8;
        margin-bottom: 20px;
    }

    a.button {
        background-color: #1a73e8;
        color: white;
        padding: 12px 24px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        margin-top: 20px;
        transition: background-color 0.3s ease;
    }

    a.button:hover {
        background-color: #155ab3;
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

    @media only screen and (max-width: 600px) {
        .container {
            padding: 20px;
        }

        h1 {
            font-size: 2em;
        }

        h2 {
            font-size: 1.4em;
        }

        p, ul {
            font-size: 1em;
        }
    }

    html, body {
        height: 100%;
        overflow-y: auto;
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
<div class="container">
    <h1>UpTalkr Privacy Policy 🍕</h1>
    <p>Last Updated: 02.10.2024 (DD.MM.YYYY)</p>

    <p>Welcome to UpTalkr! We value your privacy as much as we value the last slice of pizza at a party 🍕, and we're committed to protecting the personal information of our users. This privacy policy explains how we collect, use, store, and protect your information when you use the UpTalkr service. By using the UpTalkr platform, you agree to the practices described in this policy. If you don’t agree, feel free to give us a virtual high-five ✋ for being upfront!</p>

    <h2>1. Information We Collect 🤔</h2>
    <p class="emoji">When you register and use the UpTalkr service, we may collect:</p>
    <ul>
        <li>📧 Google User ID, Email, and Name</li>
        <li>🔑 Username, Password, and Email</li>
        <li>🖼 Profile Information (about me section, profile picture, etc.)</li>
        <li>📄 Posts (title, description, media)</li>
        <li>💬 Private Messages (encrypted)</li>
        <li>🍪 Cookies (to remember you, but no marketing or analytics)</li>
    </ul>

    <h2>2. How We Use Your Information 💡</h2>
    <ul>
        <li>🚀 Provide and maintain the service</li>
        <li>📩 Communication (important updates, no spam)</li>
        <li>✨ Personalization of your experience</li>
        <li>🛡 Security measures and AI moderation</li>
    </ul>

    <h2>3. Sharing Your Information 🤝</h2>
    <p>We don’t sell or rent your personal information to third parties! We may share it with:</p>
    <ul>
        <li>🛠 Trusted service providers</li>
        <li>⚖️ Legal authorities (when required by law)</li>
        <li>👮 Protection of rights (for the safety of UpTalkr and its users)</li>
    </ul>

    <h2>4. Data Retention 🗂</h2>
    <p>We retain your information for as long as you have an account on UpTalkr. Once deleted, your data is erased or anonymized 🧹.</p>

    <h2>5. Data Location and Transfer 🌍</h2>
    <p>Media is stored in Finland (Helsinki 🇫🇮), while service-related data is in the United States 🇺🇸. Your data might go on a little cross-Atlantic trip, but we have safeguards in place!</p>

    <h2>6. Data Security 🛡</h2>
    <p>We do our best to keep your data safe! 🔒 Though no system is foolproof, we take proper technical measures to protect your info.</p>

<h2>7. Legal Basis for Processing under GDPR ⚖️</h2>
<p>Under GDPR, we rely on the following legal grounds to process your personal data:</p>
<ul>
    <li>📜 <strong>Consent:</strong> When you provide consent, we process your data as described in this policy.</li>
    <li>🛠 <strong>Contractual Necessity:</strong> We process your data to fulfill the contract of providing our services to you.</li>
    <li>🔐 <strong>Legitimate Interests:</strong> In some cases, we process your data for legitimate business purposes, such as improving our service and ensuring its security.</li>
</ul>


    <h2>8. Your Rights Under GDPR 📜</h2>
    <p>If you’re an EU user, here are your rights:</p>
    <ul>
        <li>📄 Right of access</li>
        <li>✍️ Right to rectification</li>
        <li>🗑 Right to erasure</li>
        <li>🚫 Right to restrict processing</li>
        <li>💼 Right to data portability</li>
        <li>❌ Right to withdraw consent</li>
    </ul>
    <p>Contact us at <strong>contact@uptalkr.com</strong> to exercise any of these rights, and we’ll respond within 30 days (or less if our carrier pigeon 🕊️ flies faster)!</p>

    <h2>9. Contact Us 📬</h2>
    <p>Have questions? Concerns? Want to chat about data or carrier pigeons? Feel free to email us at <strong>contact@uptalkr.com</strong>!</p>

    <a href="mailto:contact@uptalkr.com" class="button">Contact Us</a>
<a>&copy; UpTalkr</a>
</div>

</body>
</html>
