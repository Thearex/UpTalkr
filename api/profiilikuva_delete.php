<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

require '/var/secure/config.php';

$storagebox_username = $webdav_user;
$storagebox_password = $webdav_pass;
$storagebox_url = $webdav_server;

require '/var/uptalkr/db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_SESSION['userid']) || !is_numeric($_SESSION['userid'])) {
        header('Location: https://uptalkr.com/login');
        exit;
    }
    $user_id = $_SESSION['userid'];

    $sql_check_profile_picture = "SELECT profiilikuva FROM profiili WHERE userid=?";
    $stmt_check_profile_picture = mysqli_prepare($conn, $sql_check_profile_picture);
    mysqli_stmt_bind_param($stmt_check_profile_picture, "i", $user_id);
    mysqli_stmt_execute($stmt_check_profile_picture);
    mysqli_stmt_bind_result($stmt_check_profile_picture, $profile_picture_url);
    mysqli_stmt_fetch($stmt_check_profile_picture);
    mysqli_stmt_close($stmt_check_profile_picture);

    if ($profile_picture_url) {

        $file_name_parts = explode('?filename=', $profile_picture_url);
        $file_name = end($file_name_parts);


        $remote_path = "/" . $file_name;
        $url = $storagebox_url . $remote_path;


        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $storagebox_username . ":" . $storagebox_password);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 204) {

            $sql_delete_profile_picture = "UPDATE profiili SET profiilikuva=NULL WHERE userid=?";
            $stmt_delete_profile_picture = mysqli_prepare($conn, $sql_delete_profile_picture);
            mysqli_stmt_bind_param($stmt_delete_profile_picture, "i", $user_id);
            mysqli_stmt_execute($stmt_delete_profile_picture);
            mysqli_stmt_close($stmt_delete_profile_picture);

            header('Location: https://uptalkr.com/settings');
            exit;
        } else {

            header('Location: https://uptalkr.com/settings?error=11');
            exit;
        }
    } else {

        header('Location: https://uptalkr.com/settings');
        exit;
    }
}

mysqli_close($conn);
?>
