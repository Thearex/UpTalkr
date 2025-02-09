<?php
require '/var/uptalkr/updb.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Tietokantayhteys ep�onnistui: " . $conn->connect_error);
}

$postId = $conn->real_escape_string($_GET["post_id"]);
$sql = "SELECT kommentit.id, kommentit.user_id, kommentit.kommentti, kommentit.paivamaara, users.username FROM kommentit INNER JOIN users ON kommentit.user_id = users.id WHERE kommentit.postauksen_id = '" . $postId . "' ORDER BY kommentit.paivamaara DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $id = htmlspecialchars($row["id"], ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars($row["username"], ENT_QUOTES, 'UTF-8');
    $comment = htmlspecialchars($row["kommentti"], ENT_QUOTES, 'UTF-8');
    $date = date("d.m.Y H:i", strtotime($row["paivamaara"]));
    echo "<div class='comment'>";
    echo "<div class='comment-header'><strong>" . $username . "</strong> (" . $date . ")</div>";
    echo "<div class='comment-body'>" . $comment . "</div>";
    echo "</div>";
  }
} else {
  echo "No comments";
}

$conn->close();
?>
