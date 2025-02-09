<?php

require '/var/uptalkr/updb.php';

$post_id = $_POST['post_id'];

session_start();
if (!isset($_SESSION['userid'])) {
  header("Location: https://uptalkr.com/login");
  exit();
}

$user_id = $_SESSION['userid'];

try {
  $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "Tietokantayhteyden muodostaminen ep�onnistui: " . $e->getMessage();
}

$stmt = $pdo->prepare('SELECT user_id FROM postaukset WHERE post_id = ?');
$stmt->execute([$post_id]);
$post_creator_id = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM likes WHERE post_id = ? AND user_id = ?');
$stmt->execute([$post_id, $user_id]);
$like = $stmt->fetch();

if ($like) {

  $stmt = $pdo->prepare('DELETE FROM likes WHERE id = ?');
  $stmt->execute([$like['id']]);
  error_log("The post was already liked, so the like was removed");
  

  $stmt = $pdo->prepare('DELETE FROM ilmoitus_like WHERE tykkaaja_id = ? AND ilmoitus_id = ?');
  $stmt->execute([$user_id, $post_creator_id]);
  $deletedRows = $stmt->rowCount();
  if ($deletedRows > 0) {
    error_log("The like was removed from ilmoitus_like table.");
  } else {
    error_log("No like found in ilmoitus_like table for deletion.");
  }
} else {

  $stmt = $pdo->prepare('INSERT INTO likes (post_id, user_id) VALUES (?, ?)');
  $stmt->execute([$post_id, $user_id]);
  error_log("The user had not previously liked the post, so the like has been saved.");
  

  $stmt = $pdo->prepare('INSERT INTO ilmoitus_like (tykkaaja_id, ilmoitus_id, postauksen_id) VALUES (?, ?, ?)');
  $stmt->execute([$user_id, $post_creator_id, $post_id]); 
  error_log("The user's like has been saved to ilmoitus_like table.");
}


header("Location: ../post/?post={$post_id}");
exit();
?>
