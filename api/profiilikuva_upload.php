<?php
require '/var/www/vendor/autoload.php';
require '/var/uptalkr/db.php';
require '/var/secure/config.php';

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\VideoIntelligence\V1\VideoIntelligenceServiceClient;
use Google\Cloud\VideoIntelligence\V1\Feature;
use Google\Cloud\VideoIntelligence\V1\Likelihood;

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}

$storagebox_username = $webdav_user;
$storagebox_password = $webdav_pass;
$storagebox_url = $webdav_server;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['userid']; 

    $stmt_update = null;
    $stmt_insert_profile_picture = null;

    if (empty($_FILES['profile-picture']['name']) || $_FILES['profile-picture']['error'] !== UPLOAD_ERR_OK) {
        echo "Virheellinen profiilikuva.";
        header('Location: https://uptalkr.com/settings?error=12');
        exit;
    }

    $file_name = generateRandomString(20);
    $file_tmp_name = $_FILES['profile-picture']['tmp_name'];
    $file_ext = strtolower(pathinfo($_FILES['profile-picture']['name'], PATHINFO_EXTENSION));

    if ($_FILES['profile-picture']['size'] > 5 * 1024 * 1024) {
        echo "Profiilikuva on liian suuri. Kuvan maksimikoko on 5MB.";
        header('Location: https://uptalkr.com/settings?error=14');
        exit;
    }

    $allowed_extensions = array('jpg', 'jpeg', 'png');
    if (!in_array($file_ext, $allowed_extensions)) {
        echo "Tiedosto ei ole sallittu tyyppi. Vain kuvat ovat sallittuja.";
        header('Location: https://uptalkr.com/settings?error=13');
        exit;
    }

    require '/var/www/vendor/autoload.php';
    $service_account_file = '/path/to/your/service-account.json'; // your google cloud service account tiedot .json
    $imageData = base64_encode(file_get_contents($file_tmp_name));

    $client = new Google_Client();
    $client->setAuthConfig($service_account_file);
    $client->addScope(Google_Service_Vision::CLOUD_VISION);
    $client->fetchAccessTokenWithAssertion();
    $accessToken = $client->getAccessToken();

    $apiUrl = 'https://vision.googleapis.com/v1/images:annotate';
    $jsonRequest = json_encode([
        'requests' => [
            'image' => ['content' => $imageData],
            'features' => [['type' => 'LABEL_DETECTION'], ['type' => 'SAFE_SEARCH_DETECTION']]
        ]
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken['access_token'],
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $decodedResponse = json_decode($response, true);
    $safeSearch = $decodedResponse['responses'][0]['safeSearchAnnotation'];
    if ($safeSearch['adult'] === 'LIKELY' || $safeSearch['adult'] === 'VERY_LIKELY' ||
        $safeSearch['medical'] === 'LIKELY' || $safeSearch['medical'] === 'VERY_LIKELY' ||
        $safeSearch['violence'] === 'LIKELY' || $safeSearch['violence'] === 'VERY_LIKELY' ||
        $safeSearch['racy'] === 'LIKELY' || $safeSearch['racy'] === 'VERY_LIKELY') {
        echo "Image does not comply with our community guidelines.";
        header('Location: ../create/?error=21');
        exit;
    }
    curl_close($ch);

    $remote_path = "/" . $file_name . "." . $file_ext;
    $url = $storagebox_url . $remote_path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file_tmp_name));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $storagebox_username . ":" . $storagebox_password);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code == 201) {
        $profile_picture_url = "https://uptalkr.com/api/load_photo.php?filename=" . $file_name . "." . $file_ext;

        $sql_check_profile_picture = "SELECT profiilikuva FROM profiili WHERE userid=?";
        $stmt_check_profile_picture = mysqli_prepare($conn, $sql_check_profile_picture);
        mysqli_stmt_bind_param($stmt_check_profile_picture, "i", $user_id);
        mysqli_stmt_execute($stmt_check_profile_picture);
        mysqli_stmt_store_result($stmt_check_profile_picture);

        if (mysqli_stmt_num_rows($stmt_check_profile_picture) > 0) {

            $sql_update = "UPDATE profiili SET profiilikuva=? WHERE userid=?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "si", $profile_picture_url, $user_id);
        } else {

            $sql_insert_profile_picture = "INSERT INTO profiili (userid, profiilikuva) VALUES (?, ?)";
            $stmt_insert_profile_picture = mysqli_prepare($conn, $sql_insert_profile_picture);
            mysqli_stmt_bind_param($stmt_insert_profile_picture, "is", $user_id, $profile_picture_url);
        }

        if (mysqli_stmt_execute(isset($stmt_update) ? $stmt_update : $stmt_insert_profile_picture)) {
            echo "Profiilikuva on ladattu onnistuneesti.";
            header('Location: https://uptalkr.com/settings');
            exit;
        } else {
            echo "Virhe tietokantaan tallennettaessa: " . mysqli_error($conn);
            header('Location: https://uptalkr.com/settings?error=10');
            exit;
        }
    } else {
        echo "Virhe tallennettaessa tiedostoa StorageBoxiin. HTTP-koodi: " . $http_code;
        header('Location: https://uptalkr.com/settings?error=11');
        exit;
    }
}

mysqli_close($conn);

function generateRandomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
?>
