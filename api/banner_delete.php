<?php
session_start();

require '/var/uptalkr/updb.php';
require '/var/secure/config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

$storagebox_username = $webdav_user;
$storagebox_password = $webdav_pass;
$storagebox_url = $webdav_server;

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Tietokantayhteys ep onnistui: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['userid'];

    $sql_check_banner_picture = "SELECT banneri FROM profiili WHERE userid=?";
    $stmt_check_banner_picture = mysqli_prepare($conn, $sql_check_banner_picture);
    mysqli_stmt_bind_param($stmt_check_banner_picture, "i", $user_id);
    mysqli_stmt_execute($stmt_check_banner_picture);
    mysqli_stmt_bind_result($stmt_check_banner_picture, $banner_picture_url);
    mysqli_stmt_fetch($stmt_check_banner_picture);
    mysqli_stmt_close($stmt_check_banner_picture);

    if ($banner_picture_url) {
        $url_parts = parse_url($banner_picture_url);
        parse_str($url_parts['query'], $query_params);
        $file_name = $query_params['filename'];
        $url_to_delete = $storagebox_url . "/" . $file_name;
        $ch = curl_init($url_to_delete);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $storagebox_username . ":" . $storagebox_password);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 204) {
            $sql_delete_banner_picture = "UPDATE profiili SET banneri=NULL WHERE userid=?";
            $stmt_delete_banner_picture = mysqli_prepare($conn, $sql_delete_banner_picture);
            mysqli_stmt_bind_param($stmt_delete_banner_picture, "i", $user_id);
            mysqli_stmt_execute($stmt_delete_banner_picture);
            mysqli_stmt_close($stmt_delete_banner_picture);

            echo "Banneri on poistettu onnistuneesti.";
            header('Location: https://uptalkr.com/settings');
            exit;
        } else {
            echo "Virhe poistaessa banneria StorageBoxista. HTTP-koodi: " . $http_code;
            header('Location: https://uptalkr.com/settings?error=199');
            exit;
        }
    } else {
        echo "K ytt j ll  ei ole banneria poistettavaksi.";
        header('Location: https://uptalkr.com/settings?error=19');
        exit;
    }
}

mysqli_close($conn);
?>
