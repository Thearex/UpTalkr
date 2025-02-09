<?php
session_start();
require '/var/uptalkr/updb.php';

if (isset($_SESSION['userid'])) {
    $user_id = $_SESSION['userid'];
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT admin FROM admin WHERE userid = $user_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $admin = $row['admin'];

        if ($admin == 1) {
            echo "Welcome admin user!";
                
            $sql = "SELECT COUNT(*) as user_count FROM users";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $user_count = $row['user_count'];

            $sql = "SELECT COUNT(*) as post_count FROM postaukset";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $post_count = $row['post_count'];

            echo "<p>Number of users in Zykel: $user_count</p>";
            echo "<p>Number of posts in Zykel: $post_count</p>";

            echo '<body>
                    <h1>Add badge</h1>
                    <form method="post" action="' . $_SERVER['PHP_SELF'] . '">
                        UserID: <input type="text" name="user_id"><br>
                        Badge: 
                        <input type="radio" name="badge" value="verified"> Verified
                        <input type="radio" name="badge" value="staff"> Staff<br>
                        <input type="submit" name="submit" value="add badge">
                    </form>';

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
                $user_id = $_POST["user_id"];
                $badge = $_POST["badge"];

                $sql = "INSERT INTO badges (user_id, $badge) VALUES ($user_id, 1)";

                if ($conn->query($sql) === TRUE) {
                    echo "Badge added successfully.";
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            }

            echo '<h1>Delete Badge</h1>';
            echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
                    UserID: <input type="text" name="badge_user_id"><br>
                    Badge: 
                    <input type="radio" name="badge_to_delete" value="verified"> Verified
                    <input type="radio" name="badge_to_delete" value="staff"> Staff<br>
                    <input type="submit" name="delete_badge" value="Delete Badge">
                  </form>';

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_badge"])) {
                $badge_user_id = $_POST["badge_user_id"];
                $badge_to_delete = $_POST["badge_to_delete"];

                $sql = "DELETE FROM badges WHERE user_id = $badge_user_id AND $badge_to_delete = 1";

                if ($conn->query($sql) === TRUE) {
                    echo "Badge deleted successfully.";
                } else {
                    echo "Error deleting badge: " . $conn->error;
                }
            }

            echo '<h1>Delete Post</h1>';
            echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
                    Post ID: <input type="text" name="post_id"><br>
                    <input type="submit" name="delete_post" value="Delete Post">
                  </form>';

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_post"])) {
                $post_id = $_POST["post_id"];

                $sql = "SELECT * FROM postaukset WHERE post_id = $post_id";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    $sql = "DELETE FROM postaukset WHERE post_id = $post_id";
                    if ($conn->query($sql) === TRUE) {
                        echo "Post deleted successfully.";
                    } else {
                        echo "Error deleting post: " . $conn->error;
                    }
                } else {
                    echo "Post not found.";
                }
            }

            echo '<h1>Delete User</h1>';
            echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
                    User ID: <input type="text" name="user_id_to_delete"><br>
                    <input type="hidden" name="delete_user" value="true">
                    <input type="submit" name="delete_user_button" value="Delete User">
                  </form>';

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_user"])) {

                if (isset($_POST["user_id_to_delete"])) {
                    $user_id_to_delete = $_POST["user_id_to_delete"];


                    $sql = "DELETE FROM Followers WHERE user_id = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM badges WHERE user_id = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM color WHERE user_id = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM kommentit WHERE user_id = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM likes WHERE user_id = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM megapisteet WHERE userid = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM postaukset WHERE user_id = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM profiili WHERE userid = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM settings WHERE user_id = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM usernameaika WHERE userid = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM token WHERE user_id = $user_id_to_delete";
                    $conn->query($sql);

                    $sql = "DELETE FROM users WHERE id = $user_id_to_delete";

                    if ($conn->query($sql) === TRUE) {
                        echo "User deleted successfully.";
                    } else {
                        echo "Virhe poistaessa k�ytt�j��: " . $conn->error;
                    }
                } else {
                    echo "User ID is required.";
                }
            }

            $sql = "SELECT id, username FROM users";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo "<h1>All Users</h1>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Username</th></tr>";

                while ($row = $result->fetch_assoc()) {
                    $user_id = $row['id'];
                    $username = $row['username'];

                    echo "<tr><td>" . htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') . "</td><td>" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</td></tr>";
                }

                echo "</table>";
            } else {
                echo "No users found.";
            }

            $sql = "SELECT * FROM report";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo "<h1>Reports</h1>";
                echo "<table>";
                echo "<tr><th>Report ID</th><th>Reported Item Type</th><th>Reported Item ID</th><th>Reason</th><th>Additional Info</th><th>Contact Email</th></tr>";

                while ($row = $result->fetch_assoc()) {
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


            echo '<h1>Search users from supportid</h1>
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '">
                SupportID: <input type="text" name="supportID">
                <input type="submit" name="fetch_user" value="Hae">
            </form>';

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["fetch_user"])) {
                $supportID = $_POST['supportID'];

                $sql = "SELECT userid FROM supportid WHERE supportid = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $supportID);
                $stmt->execute();
                $stmt->bind_result($userID);
                $stmt->fetch();
                $stmt->close();

                if ($userID) {
                    $sql = "SELECT username FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $userID);
                    $stmt->execute();
                    $stmt->bind_result($username);
                    $stmt->fetch();
                    $stmt->close();

                    if ($username) {
                        echo "UserID: " . htmlspecialchars($userID, ENT_QUOTES, 'UTF-8') . "<br>";
                        echo "Username: " . htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
                    } else {
                        echo "K�ytt�j�� ei l�ytynyt.";
                    }
                } else {
                    echo "SupportID:t� ei l�ytynyt.";
                }
            }

            echo '</body></html>';
        } else {
            header("Location: https://uptalkr.com");
            exit;
        }
    } else {

        header("Location: https://uptalkr.com");
        exit;
    }

    $conn->close();
} else {
    header("Location: https://uptalkr.com");
    exit;
}
?>
