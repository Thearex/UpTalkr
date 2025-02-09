<?php
require '/var/www/vendor/autoload.php';

require '/var/uptalkr/updb.php';

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

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Tietokantayhteys epäonnistui: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['userid'];

    if (empty($_FILES['banner-picture']['name']) || $_FILES['banner-picture']['error'] !== UPLOAD_ERR_OK) {
        echo "Invalid banner image.";
        header('Location: https://uptalkr.com/settings?virhe=15');
        exit;
    }

    $file_name = generateRandomString(20);
    $file_tmp_name = $_FILES['banner-picture']['tmp_name'];
    $file_ext = strtolower(pathinfo($_FILES['banner-picture']['name'], PATHINFO_EXTENSION));


    $allowed_extensions = array('jpg', 'jpeg', 'png');
    if (!in_array($file_ext, $allowed_extensions)) {
        echo "Invalid banner image. has to be jpg, jpeg or png.";
        header('Location: https://uptalkr.com/settings?virhe=17');
        exit;
    }

    require '/var/www/vendor/autoload.php';
    $service_account_file = '/var/secure/server2.json';
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
        $banner_picture_url = "https://uptalkr.com/api/load_photo.php?filename=" . $file_name . "." . $file_ext;

        $sql_update = "UPDATE profiili SET banneri=? WHERE userid=?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "si", $banner_picture_url, $user_id);

        if (mysqli_stmt_execute($stmt_update)) {
            echo "Banneri on ladattu onnistuneesti.";
            header('Location: https://uptalkr.com/settings');
            exit;
        } else {
            echo "Virhe tietokantaan tallennettaessa: " . mysqli_error($conn);
            header('Location: https://uptalkr.com/settings?error=10');
            exit;
        }
    } else {
        echo "Error";
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
