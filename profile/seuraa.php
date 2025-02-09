<?php
session_start();
require '/var/uptalkr/updb.php';

if (isset($_SESSION['userid'])) {
    $seuraajaID = $_SESSION['userid']; 

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Tietokantayhteyden muodostaminen epäonnistui: " . $conn->connect_error);
    }

    $seurattavanKayttajaNimi = $_POST['username'];
    $palaamisusername = $_POST['username'];

    $kayttajaKysely = "SELECT id FROM users WHERE username = '$seurattavanKayttajaNimi'";
    $kayttajaTulos = $conn->query($kayttajaKysely);

    if ($kayttajaTulos && $kayttajaTulos->num_rows > 0) {
        $kayttajaRivi = $kayttajaTulos->fetch_assoc();
        $seurattavanKayttajaID = $kayttajaRivi['id'];

        if ($seuraajaID != $seurattavanKayttajaID) {

            $tarkistusKysely = "SELECT * FROM Followers WHERE user_id = '$seurattavanKayttajaID' AND follower_id = '$seuraajaID'";
            $tarkistusTulos = $conn->query($tarkistusKysely);

            if ($tarkistusTulos && $tarkistusTulos->num_rows > 0) {
                $poistoKysely = "DELETE FROM Followers WHERE user_id = '$seurattavanKayttajaID' AND follower_id = '$seuraajaID'";
                if ($conn->query($poistoKysely) === TRUE) {
                    echo "Following user $seurattavanKayttajaNimi removed.";
                    header("Location: https://uptalkr.com/profile/?id=" . urlencode($palaamisusername) . "&yes=1");
                    exit;
                } else {
                    echo "Removing follow for user $seurattavanKayttajaNimi failed: " . $conn->error;
                    header("Location: https://uptalkr.com/profile/?id=" . urlencode($palaamisusername) . "&error=1");
                }
            } else {
                $lisaysKysely = "INSERT INTO Followers (user_id, follower_id) VALUES ('$seurattavanKayttajaID', '$seuraajaID')";
                if ($conn->query($lisaysKysely) === TRUE) {
                    echo "You are now following $seurattavanKayttajaNimi!";
                    header("Location: https://uptalkr.com/profile/?id=" . urlencode($palaamisusername) . "&yes=1");
                    exit;
                } else {
                    echo "Failed to follow user $seurattavanKayttajaNimi: " . $conn->error;
                    header("Location: https://uptalkr.com/profile/?id=" . urlencode($palaamisusername) . "&error=1");
                }
            }
        } else {
            echo "You can't follow yourself.";
            header("Location: https://uptalkr.com/profile/?id=" . urlencode($palaamisusername) . "&error=2");
            exit;
        }
    } else {
        echo "User $seurattavanKayttajaNimi not found.";
        header("Location: https://uptalkr.com/profile/?id=" . urlencode($palaamisusername) . "&error=0");
    }

    $conn->close();
} else {
    echo "You have to login to follow.";
    header("Location: https://uptalkr.com/login");
    exit;
}
?>
