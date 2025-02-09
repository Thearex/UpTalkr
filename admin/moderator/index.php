<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UpTalkr - Mods</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        h1, h2 {
            color: #333;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        form {
            margin-top: 20px;
        }

        input[type="text"], input[type="submit"] {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<?php
session_start();

if(isset($_SESSION['userid'])) {
    require '/var/uptalkr/updb.php';

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $userid = $_SESSION['userid'];
    $sql = "SELECT moderator FROM moderators WHERE userid = ? AND moderator = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<h2>Tervetuloa Moderaattorit!</h2>";

        echo '<h1>Delete Post</h1>';
        echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">
                Post ID: <input type="text" name="post_id"><br>
                <input type="submit" name="delete_post" value="Delete Post">
              </form>';

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_post"])) {
            $post_id = $_POST["post_id"];

            $sql_check_post = "SELECT * FROM postaukset WHERE post_id = ?";
            $stmt_check_post = $conn->prepare($sql_check_post);
            $stmt_check_post->bind_param("i", $post_id);
            $stmt_check_post->execute();
            $result_check_post = $stmt_check_post->get_result();

            if ($result_check_post->num_rows > 0) {
                $sql_delete_post = "DELETE FROM postaukset WHERE post_id = ?";
                $stmt_delete_post = $conn->prepare($sql_delete_post);
                $stmt_delete_post->bind_param("i", $post_id);
                if ($stmt_delete_post->execute()) {
                    echo "Post deleted successfully.";
                } else {
                    echo "Error deleting post.";
                }
            } else {
                echo "Post not found.";
            }
        }

        $sql_reports = "SELECT * FROM report";
        $result_reports = $conn->query($sql_reports);

        if ($result_reports->num_rows > 0) {
            echo "<h1>Reports</h1>";
            echo "<table>";
            echo "<tr><th>Report ID</th><th>Reported Item Type</th><th>Reported Item ID</th><th>Reason</th><th>Additional Info</th></tr>";

            while ($row = $result_reports->fetch_assoc()) {
		$report_id = htmlspecialchars($row['report_id'], ENT_QUOTES, 'UTF-8');
		$reported_item_type = htmlspecialchars($row['reported_item_type'], ENT_QUOTES, 'UTF-8');
		$reported_item_id = htmlspecialchars($row['reported_item_id'], ENT_QUOTES, 'UTF-8');
		$reason = htmlspecialchars($row['reason'], ENT_QUOTES, 'UTF-8');
		$additional_info = htmlspecialchars($row['additional_info'], ENT_QUOTES, 'UTF-8');
                echo "<tr><td>$report_id</td><td>$reported_item_type</td><td>$reported_item_id</td><td>$reason</td><td>$additional_info</td></tr>";
            }

            echo "</table>";
        } else {
            echo "No reports found.";
        }

        $sql_comments = "SELECT * FROM kommentit";
        $result_comments = $conn->query($sql_comments);

        if ($result_comments->num_rows > 0) {
            echo "<h1>Comments</h1>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Comment</th><th>Post ID</th><th>User ID</th><th>Created At</th></tr>";

            while ($row = $result_comments->fetch_assoc()) {
		$comment_id = htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');
		$comment = htmlspecialchars($row['kommentti'], ENT_QUOTES, 'UTF-8');
		$post_id = htmlspecialchars($row['postauksen_id'], ENT_QUOTES, 'UTF-8');
		$user_id = htmlspecialchars($row['user_id'], ENT_QUOTES, 'UTF-8');
		$created_at = htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8');
                echo "<tr><td>$comment_id</td><td>$comment</td><td>$post_id</td><td>$user_id</td><td>$created_at</td></tr>";
            }

            echo "</table>";
        } else {
            echo "No comments found.";
        }

        echo '<h1>Delete Comment</h1>';
        echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">
                Comment ID: <input type="text" name="comment_id"><br>
                <input type="submit" name="delete_comment" value="Delete Comment">
              </form>';

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_comment"])) {
            $comment_id = $_POST["comment_id"];

            $sql_check_comment = "SELECT * FROM kommentit WHERE id = ?";
            $stmt_check_comment = $conn->prepare($sql_check_comment);
            $stmt_check_comment->bind_param("i", $comment_id);
            $stmt_check_comment->execute();
            $result_check_comment = $stmt_check_comment->get_result();

            if ($result_check_comment->num_rows > 0) {
                $sql_delete_comment = "DELETE FROM kommentit WHERE id = ?";
                $stmt_delete_comment = $conn->prepare($sql_delete_comment);
                $stmt_delete_comment->bind_param("i", $comment_id);
                if ($stmt_delete_comment->execute()) {
                    echo "Comment deleted successfully.";
                } else {
                    echo "Error deleting comment.";
                }
            } else {
                echo "Comment not found.";
            }
        }

    } else {
        header("Location: https://uptalkr.com/404");
        exit;
    }

    $stmt->close();
    $conn->close();
} else {

    header("Location: https://uptalkr.com/404");
    exit;
}
?>
</body>
</html>
