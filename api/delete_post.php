<?php

session_start();

require '/var/uptalkr/updb.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);

$storagebox_username = "";
$storagebox_password = "";
$storagebox_url = "";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['userid'];

if ($user_id) {
    $post_id = $_POST['post_id'];
    $stmt_check = $conn->prepare('SELECT user_id, file FROM postaukset WHERE post_id = ?');
    $stmt_check->bind_param('i', $post_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $post = $result_check->fetch_assoc();

    if ($post && $post['user_id'] === $user_id) {

        $media_url = $post['file'];


        $stmt_delete_comments = $conn->prepare('DELETE FROM kommentit WHERE postauksen_id = ?');
        $stmt_delete_comments->bind_param('i', $post_id);
        $stmt_delete_comments->execute();
        $stmt_delete_comments->close();

        $stmt_delete_post = $conn->prepare('DELETE FROM postaukset WHERE post_id = ?');
        $stmt_delete_post->bind_param('i', $post_id);
        $stmt_delete_post->execute();

        if ($stmt_delete_post->affected_rows > 0) {
            if (!empty($media_url)) {

                $parsed_url = parse_url($media_url);
                parse_str($parsed_url['query'], $params);
                $file_name = $params['filename'];


                $delete_url = $storagebox_url . '/' . $file_name;


                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $delete_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_USERPWD, $storagebox_username . ":" . $storagebox_password);
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code == 204) {
                    echo "Post and associated media deleted successfully.";
                    header('Location: https://uptalkr.com/studio?delete=3');
                } else {
                    echo "Failed to delete the media. HTTP status code: $http_code";
                    header('Location: https://uptalkr.com/studio?delete=4');
                }
            }
        } else {
            echo "Failed to delete the post.";
            header('Location: https://uptalkr.com/studio?delete=2');
        }
        $stmt_delete_post->close();
    } else {
        echo "That is not your post.";
        header('Location: https://uptalkr.com/studio?delete=1');
    }

    $stmt_check->close();
} else {
    echo "User not found.";
    header('Location: https://uptalkr.com/login');
}

$conn->close();
?>
