<?php
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


if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

require '/var/uptalkr/updb.php';
require '/var/uptalkr/check-cookie-token.php';
$config = require '/var/uptalkr/config.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Tietokantaan yhdistäminen epäonnistui: " . htmlspecialchars($e->getMessage()));
}

$logged_in_user_id = $_SESSION['userid'];
$logged_in_username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

function getContacts($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.username
        FROM users u
        JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
        WHERE u.id != :user_id AND (m.sender_id = :user_id OR m.receiver_id = :user_id)
        ORDER BY (SELECT MAX(sent_at) FROM messages WHERE (sender_id = u.id AND receiver_id = :user_id) OR (sender_id = :user_id AND receiver_id = u.id)) DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMessages($user_id, $contact_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT m.id, u.username AS sender_username, m.message_text, m.iv, m.sent_at
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = :user_id AND m.receiver_id = :contact_id)
           OR (m.sender_id = :contact_id AND m.receiver_id = :user_id)
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute(['user_id' => $user_id, 'contact_id' => $contact_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_GET['search'])) {
    $search_query = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
    if ($search_query) {
        $stmt = $pdo->prepare("
            SELECT id, username FROM users 
            WHERE username LIKE CONCAT('%', ?, '%') AND id != ?
            ORDER BY username ASC
            LIMIT 1
        ");
        $stmt->execute([$search_query, $logged_in_user_id]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        echo json_encode($search_results);
    }
    exit;
}


if (isset($_GET['ajax']) && isset($_GET['contact_id'])) {
    $contact_id = filter_input(INPUT_GET, 'contact_id', FILTER_VALIDATE_INT);
    if ($contact_id) {
        $messages = getMessages($logged_in_user_id, $contact_id);
        $response = '';
    
        foreach ($messages as $message) {
            $decryption_key = $config['encryption_key'];
            $iv = base64_decode($message['iv']);
            $decrypted_message = openssl_decrypt($message['message_text'], 'AES-256-CBC', $decryption_key, 0, $iv);
    
            $response .= '<div class="message-item ' . ($message['sender_username'] == $logged_in_username ? 'self' : '') . '">';
            $response .= '<strong>' . htmlspecialchars($message['sender_username'], ENT_QUOTES, 'UTF-8') . ':</strong> ';
            $response .= htmlspecialchars($decrypted_message, ENT_QUOTES, 'UTF-8');
            $response .= '<br><small>' . htmlspecialchars($message['sent_at'], ENT_QUOTES, 'UTF-8') . '</small>';
            $response .= '</div>';
        }
    
        echo $response;
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch.");
    }

    $receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
    $message_text = filter_input(INPUT_POST, 'message_text', FILTER_SANITIZE_STRING);

    if ($receiver_id && $message_text) {
        $sender_id = $logged_in_user_id;


        $stmt = $pdo->prepare("SELECT receive_messages, blocked_users FROM users WHERE id = ?");
        $stmt->execute([$receiver_id]);
        $receiver_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($receiver_data['receive_messages'] === 0) {
            echo "The user has disabled private messages.";
            exit;
        }

        $blocked_users = explode(',', $receiver_data['blocked_users']);
        if (in_array($sender_id, $blocked_users)) {
            echo "You are blocked from sending messages to this user.";
            exit;
        }


        $stmt = $pdo->prepare("SELECT blocked_users FROM users WHERE id = ?");
        $stmt->execute([$sender_id]);
        $sender_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $sender_blocked_users = explode(',', $sender_data['blocked_users']);
        if (in_array($receiver_id, $sender_blocked_users)) {
            echo "You cannot send messages to this user.";
            exit;
        }

        $encryption_key = $config['encryption_key'];
        $iv = random_bytes(16);
        $encrypted_message = openssl_encrypt($message_text, 'AES-256-CBC', $encryption_key, 0, $iv);

        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, iv) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sender_id, $receiver_id, $encrypted_message, base64_encode($iv)]);
    }
}

$contacts = getContacts($logged_in_user_id);
$selected_contact_id = filter_input(INPUT_GET, 'contact_id', FILTER_VALIDATE_INT);
$selected_contact_name = '';
$messages = [];
if ($selected_contact_id) {
    $messages = getMessages($logged_in_user_id, $selected_contact_id);
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$selected_contact_id]);
    $selected_contact_name = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UpTalkr - Private Messages</title>
    <link rel="icon" href="https://uptalkr.com/assets/logo.png" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="https://uptalkr.com/assets/navbar2.css?v=2">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
            display: flex;
            height: 100vh;
            padding-top: 80px;
            box-sizing: border-box;
        }
        .container {
            display: flex;
            width: 100%;
        }
        #user-list {
            width: 30%;
            background-color: #ffffff;
            overflow-y: auto;
            padding: 10px;
            border-right: 1px solid #ddd;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        #message-view {
            width: 70%;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 10px;
        }
        .user-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            color: #333;
        }
        .user-item:hover {
            background-color: #f0f0f0;
        }
        .user-item.active {
            background-color: #e0e0e0;
        }
        .message-item {
            padding: 10px;
            margin: 10px;
            background-color: #f1f1f1;
            border-radius: 5px;
            max-width: 60%;
        }
        .message-item.self {
            background-color: #d1e7dd;
            align-self: flex-end;
        }
        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        .message-form {
            display: flex;
            align-items: center;
            padding: 10px;
            border-top: 1px solid #ddd;
            gap: 10px;
        }
        .message-form textarea {
            flex: 1;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: #ffffff;
            color: #333;
            resize: none;
            height: 40px;
        }
        .message-form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .message-form button:hover {
            background-color: #0056b3;
        }
        .search-bar {
            padding: 10px;
            background-color: #ffffff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
        }
        .search-bar input {
            flex: 1;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            color: #333;
        }
        .search-bar button {
            padding: 10px;
            margin-left: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .no-user-selected {
            text-align: center;
            margin-top: 20%;
            color: #888;
            font-size: 18px;
        }
        .no-results {
            text-align: center;
            color: #888;
            margin-top: 10px;
            margin-bottom: 10px;
        }
.message-header {
    padding: 10px;
    font-size: 18px;
    font-weight: bold;
    color: #333;
}
    </style>
</head>
<body>


<nav>
  <div class="nav-logo">
    <a href="https://uptalkr.com">
      <img src="/assets/logo.png" alt="Logo">
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
          <img src="/assets/post2.png" alt="Create!" class="icon">
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
    <div id="user-list">
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search for users">
            <button id="searchButton">🔍</button>
        </div>
        <div id="noResults" class="no-results" style="display: none;">User not found...</div>
        <div id="userResults">
            <?php foreach ($contacts as $contact): ?>
                <div class="user-item <?php echo $selected_contact_id == $contact['id'] ? 'active' : ''; ?>" data-contact-id="<?php echo $contact['id']; ?>">
                    <?php echo htmlspecialchars($contact['username']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


    <div id="message-view">
        <div class="message-header">
            <?php echo htmlspecialchars($selected_contact_name, ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div class="messages">
            <?php if ($selected_contact_id): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message-item <?php echo ($message['sender_username'] == $logged_in_username) ? 'self' : ''; ?>">
                        <?php
                        $decryption_key = $config['encryption_key'];
                        $iv = base64_decode($message['iv']);
                        $decrypted_message = openssl_decrypt($message['message_text'], 'AES-256-CBC', $decryption_key, 0, $iv);
                        ?>
                        <strong><?php echo htmlspecialchars($message['sender_username']); ?>:</strong>
                        <?php echo htmlspecialchars($decrypted_message); ?>
                        <br>
                        <small><?php echo htmlspecialchars($message['sent_at']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-user-selected">Welcome to UpTalkr Private Messages!<br>You can chat with other users here. Messages are ENCRYPTED!</div>
            <?php endif; ?>
        </div>
        <?php if ($selected_contact_id): ?>
        <div class="message-form">
            <form method="post" style="display: flex; width: 100%; gap: 10px;">
		<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($selected_contact_id); ?>">
                <textarea name="message_text" placeholder="Write a message... or send a carrier pigeon. Whichever is faster!" required></textarea>
                <button type="submit" name="send_message">Send</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function fetchMessages(contactId) {
        fetch('?ajax=1&contact_id=' + contactId)
            .then(response => response.text())
            .then(data => {
                document.querySelector('.messages').innerHTML = data;
            })
    }
    setInterval(function() {
        const selectedContactId = '<?php echo $selected_contact_id; ?>';
        if (selectedContactId) {
            fetchMessages(selectedContactId);
        }
    }, 5000);
    function loadMessages(contactId) {
        window.history.pushState({ contactId: contactId }, '', '?contact_id=' + contactId);
        fetchMessages(contactId);
	location.reload();
    }

    function searchUsers() {
        const query = document.getElementById('searchInput').value.trim();
        const userResults = document.getElementById('userResults');
        const noResults = document.getElementById('noResults');

        if (query === '') {
            noResults.style.display = 'none';
            userResults.innerHTML = '';
            <?php foreach ($contacts as $contact): ?>
                var userItem = document.createElement('div');
                userItem.className = 'user-item';
                userItem.setAttribute('data-contact-id', '<?php echo $contact["id"]; ?>');
                userItem.innerText = '<?php echo htmlspecialchars($contact["username"]); ?>';
                userItem.onclick = function() { loadMessages(<?php echo $contact["id"]; ?>); };
                userResults.appendChild(userItem);
            <?php endforeach; ?>
        } else {
            fetch('?search=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    userResults.innerHTML = '';
                    if (data.length > 0) {
                        noResults.style.display = 'none';
                        data.forEach(user => {
                            var userItem = document.createElement('div');
                            userItem.className = 'user-item';
                            userItem.setAttribute('data-contact-id', user.id);
                            userItem.innerText = user.username;
                            userItem.onclick = function() { loadMessages(user.id); };
                            userResults.appendChild(userItem);
                        });
                    } else {
                        noResults.style.display = 'block';
                    }
                })
                .catch(error => console.error('Error searching users:', error));
        }
    }
    document.getElementById('searchButton').addEventListener('click', searchUsers);

    document.querySelectorAll('.user-item').forEach(function (element) {
        element.addEventListener('click', function () {
            const contactId = this.getAttribute('data-contact-id');
            loadMessages(contactId);
        });
    });
    window.addEventListener('popstate', function(event) {
        const state = event.state;
        if (state && state.contactId) {
            fetchMessages(state.contactId);
        }
    });
});
</script>

</body>
</html>