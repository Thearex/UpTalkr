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
      <a href="/profile">Profile</a>
      <a href="/settings">Settings</a>
      <a href="/messages">Messages</a>
      <a href="https://uptalkr.com/studio">Studio</a>
      <a href="/logout">Logout</a>
    </div>
  </div>';
}
?>
 

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>UpTalkr - Explore</title>
  <meta name="description" content="The easiest and best social media platform for connecting, sharing, and discovering communities." />
  <link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
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

  .content {
    margin-top: 0px;
  }

  .post-container {
    background-color: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    max-width: 500px;
    margin: 50px auto 10px auto;
    transition: transform 0.3s ease;
    cursor: pointer;
    border: 1px solid #000;
}


  .post-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
  }

  .profile-picture img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 10px;
  }

  .username {
    font-size: 18px;
    font-weight: bold;
    color: #333;
  }

  .post-timestamp {
    font-size: 12px;
    color: #999;
    margin-left: 10px;
  }

  .post-title {
    font-size: 22px;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
  }

  .post-content {
    font-size: 16px;
    color: #555;
    margin-bottom: 10px;
  }

  .post-image {
    width: 100%;
    border-radius: 10px;
    max-width: 500px;
    max-height: 500px;

    object-fit: cover;
}

.post-video {
    width: 100%;
    border-radius: 10px;
    max-width: 500px;
    max-height: 500px;

    object-fit: cover;
}

  .post-interactions {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
  }

  .interaction-btn {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    font-size: 16px;
    color: #888;
    cursor: pointer;
  }

  .interaction-btn img {
    width: 20px;
    height: 20px;
    margin-right: 5px;
  }

  .interaction-btn:hover {
    color: #333;
  }

.username-link {
    color: #333; 
    text-decoration: none;
    font-weight: bold;
}

.username-link:hover {
    color: #007bff;
    text-decoration: none;
}


.bottom-left {
    position: fixed;
    bottom: 1px;
    left: 20px;
    font-size: 12px;
    color: #555;
    text-align: left; 
}

.bottom-left a {
    color: #007bff;
    text-decoration: none;
    margin: 3px 0;
    display: block;
    transition: color 0.2s ease;
}

.bottom-left a:hover {
    color: #0056b3;
    text-decoration: underline;
}


@media (max-width: 810px) {
    .bottom-left {
        display: none;
    }
}

.toggle-buttons {
  display: flex;
  gap: 20px; 
  margin-top: 100px;
  justify-content: center;
}

.toggle-btn {
  font-family: 'Poppins', sans-serif;
  font-size: 1rem;
  font-weight: bold;
  background-color: transparent;
  border: 2px solid #333;
  color: #333;
  padding: 10px 20px;
  border-radius: 25px;
  cursor: pointer;
  transition: background-color 0.3s ease, color 0.3s ease;
}

.toggle-btn.active {
  background-color: #333;
  color: #fff;
}

.toggle-btn:hover {
  background-color: #444;
  color: #fff;
}

  </style>
</head>
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

<div class="toggle-buttons">
  <button class="toggle-btn active" id="feedBtn" onclick="setActive('feed')">FEED</button>
  <button class="toggle-btn" id="latestBtn" onclick="setActive('latest')">LATEST</button>
</div>



<div class="content" id="content">
  <button id="loadMoreBtn" onclick="loadMorePosts()">Load more posts</button>
</div>

<p class="bottom-left">
        <a href="https://uptalkr.com/report">Report</a>
        <a href="https://uptalkr.com/leaderboard/">Leaderboard</a>
        <a href="https://uptalkr.com/privacy-policy/">Privacy Policy</a>
        <a href="https://uptalkr.com/terms-of-service/">Terms of Service</a>
        <a href="https://uptalkr.com/community-guidelines/">Community Guidelines</a>

  &copy; uptalkr.com 2024
</p>
<script>
var offset = 0;
var scrollTimeout;


function loadMorePosts() {
  var xhttp = new XMLHttpRequest();
  var apiUrl = ''; 

  if (document.getElementById('feedBtn').classList.contains('active')) {
    apiUrl = 'https://uptalkr.com/api/get_posts.php?offset=' + offset;
  } else if (document.getElementById('latestBtn').classList.contains('active')) {
    apiUrl = 'https://uptalkr.com/api/get_posts_latest.php?offset=' + offset;
  }

  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      var response = this.responseText;
      document.getElementById("content").innerHTML += response;
      var loadMoreBtn = document.getElementById("loadMoreBtn");
      loadMoreBtn.style.display = "none"; 
    }
  };

  xhttp.open("GET", apiUrl, true); 
  xhttp.send();
  offset += 10;
}

function setActive(selected) {
  const feedBtn = document.getElementById('feedBtn');
  const latestBtn = document.getElementById('latestBtn');

  feedBtn.classList.remove('active');
  latestBtn.classList.remove('active');

  if (selected === 'feed') {
    feedBtn.classList.add('active');
  } else if (selected === 'latest') {
    latestBtn.classList.add('active');
  }

  document.getElementById('content').innerHTML = '';
  offset = 0;

  loadMorePosts();
}

function handleScroll() {
  clearTimeout(scrollTimeout);
  scrollTimeout = setTimeout(checkScroll, 200); 
}

function checkScroll() {
  var distanceToBottom = document.documentElement.scrollHeight - (window.innerHeight + window.scrollY);
  if (distanceToBottom < 400) {
    loadMorePosts();
  }
}

window.onload = function() {
  setActive('feed');
  window.addEventListener("scroll", handleScroll);
};

function copyPostLink(postId) {
  const postUrl = 'https://uptalkr.com/post/?post=' + postId;
  navigator.clipboard.writeText(postUrl).then(() => {
    alert('Post link copied to clipboard!');
  }).catch(err => {
    console.error('Failed to copy text: ', err);
  });
}

function likePost(postId, buttonElement, event) {
  event.stopPropagation();
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      var response = JSON.parse(this.responseText);
      if (response.success) {
        var likeImage = response.liked ? 'like2.png' : 'like1.png'; 
        var likeCount = response.likes_count; 
        buttonElement.querySelector('img').src = 'https://uptalkr.com/assets/' + likeImage;
        buttonElement.querySelector('.like-count').textContent = likeCount;
      } else {
        alert('An error occurred');
      }
    }
  };
  xhttp.open("POST", "https://uptalkr.com/api/like.php", true);
  xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhttp.send("post_id=" + encodeURIComponent(postId));
}

</script>


</body>
</html>