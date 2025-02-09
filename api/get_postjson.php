<?php
header("Access-Control-Allow-Origin: https://www.uptalkr.com");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

session_start();
require '/var/uptalkr/updb.php';
$user_id = $_SESSION['userid'];

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
  die("Yhteyden muodostaminen ep�onnistui: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $data = json_decode(file_get_contents("php://input"), true);


  if (isset($data['post_id'])) {
    $post_id = $data['post_id'];


    $sql = "SELECT p.post_id, p.title, p.content, p.file, u.username, p.created_at
            FROM postaukset AS p
            INNER JOIN users AS u ON p.user_id = u.id
            WHERE p.post_id = ?";


    $stmt = mysqli_prepare($conn, $sql);


    if ($stmt) {

      mysqli_stmt_bind_param($stmt, "i", $post_id);

      mysqli_stmt_execute($stmt);

      mysqli_stmt_bind_result($stmt, $post_id, $title, $content, $file, $username, $created_at);

      mysqli_stmt_fetch($stmt);

      if ($post_id) {

        $post = array(
          "post_id" => $post_id,
          "title" => $title,
          "content" => $content,
          "file" => $file,
          "username" => $username,
          "created_at" => $created_at
        );


        header('Content-Type: application/json');
        echo json_encode($post);
      } else {

        header('Content-Type: application/json');
        echo json_encode(array("message" => "Post not found"));
      }


      mysqli_stmt_close($stmt);
    } else {

      header('Content-Type: application/json');
      echo json_encode(array("message" => "Failed to prepare statement"));
    }
  } else {

    header('Content-Type: application/json');
    echo json_encode(array("message" => "Missing post_id parameter"));
  }
} else {

  header('Content-Type: application/json');
  http_response_code(405);
  echo json_encode(array("message" => "Only POST requests are allowed"));
}

mysqli_close($conn);

?>
