<?php
session_start();
require '/var/uptalkr/updb.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Yhteys ep onnistui: " . $conn->connect_error);
}

$kommentti = $_POST["kommentti"];

$words = explode(" ", $kommentti);
if (count($words) > 0 && trim($words[0]) != '' && str_word_count($kommentti) <= 100) {
  $safe_kommentti = mysqli_real_escape_string($conn, $kommentti);
} else {
  header("Location: /post/?post=$postauksen_id&error=2");
  exit();
}

if(isset($_POST["post_id"])){
  $postauksen_id = $_POST["post_id"];
  $sql = "SELECT post_id FROM postaukset WHERE post_id = '$postauksen_id'";
  $result = $conn->query($sql);
  if ($result->num_rows == 0) {
    die("Postausta ei l ydy.");
  }
}

session_start();
$user_id = $_SESSION['userid'];

$sql = "INSERT INTO kommentit (kommentti, postauksen_id, user_id) VALUES ('$kommentti', '$postauksen_id', '$user_id')";
if ($conn->query($sql) === TRUE) {
  echo "Kommentti tallennettu onnistuneesti!";
  
  $pisteet = 1; 
  $sql_pisteet = "INSERT INTO megapisteet (userid, piste) VALUES (?, ?)";
  $stmt_pisteet = mysqli_prepare($conn, $sql_pisteet);
  mysqli_stmt_bind_param($stmt_pisteet, "ii", $user_id, $pisteet);
  if (mysqli_stmt_execute($stmt_pisteet)) {
      echo "Pisteet lisatty.";
      header("Location: /post/?post=$postauksen_id&jes=1");
      exit();
  header("Location: /post/?post=$postauksen_id&jes=1");
  exit();
} else {
    echo "Virhe pisteiden lis  misess : " . mysqli_error($conn);
    header('Location: ../create/?error=4');
}

} else {
  echo "Virhe tallennettaessa kommenttia: " . $conn->error;
  header("Location: /post/?post=$postauksen_id&error=1");
}

$conn->close();
?>
