<?php
require '/var/www/vendor/autoload.php';
require '/var/uptalkr/updb.php';
require '/var/secure/config.php';

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\VideoIntelligence\V1\VideoIntelligenceServiceClient;
use Google\Cloud\VideoIntelligence\V1\Feature;
use Google\Cloud\VideoIntelligence\V1\Likelihood;

$bucketName = 'YOUR-OWN-GOOGLE-CLOUD-BUCKET-NAME';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login');
    exit;
}


if (isset($_SESSION['last_post_time'])) {
    $lastPostTime = $_SESSION['last_post_time'];
} else {
    $lastPostTime = 0; 
}


$cooldownTime = 10 * 60; 


if (time() - $lastPostTime < $cooldownTime) {
    header('Location: ../create/?error=7');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $captchaResponse = $_POST['cf-turnstile-response'];
    $secretKey = ''; // Your OWN CLOUDFLARE TURNSTILE KEY

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $secretKey, 'response' => $captchaResponse)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $responseKeys = json_decode($response, true);

    if (!$responseKeys["success"]) {
        header('Location: https://uptalkr.com/create/?error=8');
        exit;
    }

    $conn = mysqli_connect($servername, $username, $password, $dbname);

    if (!$conn) {
        header('Location: ../create/?error=1');
        exit;
    }

    $user_id = $_SESSION['userid'];
    $post_title = $_POST['post-title'];
    $post_content = $_POST['post-content'];

    if (preg_match('/[-_]/', $post_title) || preg_match('/[-_]/', $post_content)) {
        header('Location: ../create/?error=9');
        exit;
    }

    if (strlen($post_content) < 3 && empty($_FILES['file-upload']['name'])) {
        header('Location: ../create/?error=2');
        exit;
    }

    $file_url = "";

    if (!empty($_FILES['file-upload']['name']) && $_FILES['file-upload']['error'] === UPLOAD_ERR_OK) {
        $file_name = generateRandomString(20); 
        $file_tmp_name = $_FILES['file-upload']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['file-upload']['name'], PATHINFO_EXTENSION));

        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mkv');
        if (!in_array($file_ext, $allowed_extensions)) {
            header('Location: ../create/?error=3');
            exit;
        }

        $max_file_size = 2 * 1024 * 1024 * 1024; // 2 gigabytes
        if ($_FILES['file-upload']['size'] > $max_file_size) {
            header('Location: ../create/?error=6');
            exit;
        }

        if (in_array($file_ext, ['mp4', 'avi', 'mkv'])) {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=/var/secure/server2.json');
            $storage = new StorageClient();
            $bucket = $storage->bucket($bucketName);
            $object = $bucket->upload(fopen($file_tmp_name, 'r'), [
                'name' => $file_name . "." . $file_ext
            ]);
            $uri = 'gs://' . $bucketName . '/' . $file_name . "." . $file_ext;

            $videoClient = new VideoIntelligenceServiceClient();
            $features = [Feature::LABEL_DETECTION, Feature::EXPLICIT_CONTENT_DETECTION];
            $operation = $videoClient->annotateVideo([
                'inputUri' => $uri,
                'features' => $features
            ]);
            $operation->pollUntilComplete();

            if ($operation->operationSucceeded()) {
                $result = $operation->getResult();
                $explicitContentLikelihood = Likelihood::LIKELIHOOD_UNSPECIFIED;
                foreach ($result->getAnnotationResults() as $annotationResult) {
                    if ($annotationResult->hasExplicitAnnotation()) {
                        foreach ($annotationResult->getExplicitAnnotation()->getFrames() as $frame) {
                            $likelihood = $frame->getPornographyLikelihood();
                            $explicitContentLikelihood = max($explicitContentLikelihood, $likelihood);
                        }
                    }
                }
                if ($explicitContentLikelihood >= Likelihood::LIKELY || $explicitContentLikelihood >= Likelihood::VERY_LIKELY) {
                    header('Location: ../create/?error=10');
                    exit;
                }
            } else {
                header('Location: ../create/?error=11');
                exit;
            }

            $object->delete(); 
            $videoClient->close();
            $file_url = "https://uptalkr.com/api/load_video.php?filename=" . $file_name . "." . $file_ext;

        } else if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
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
                    [
                        'image' => ['content' => $imageData],
                        'features' => [
                            ['type' => 'LABEL_DETECTION'],
                            ['type' => 'SAFE_SEARCH_DETECTION']
                        ]
                    ]
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
            if (curl_errno($ch)) {
                header('Location: ../create/?error=11');
                exit;
            }

            $decodedResponse = json_decode($response, true);
            if (isset($decodedResponse['error'])) {
                header('Location: ../create/?error=11');
                exit;
            }

            $safeSearch = $decodedResponse['responses'][0]['safeSearchAnnotation'];
            if ($safeSearch['adult'] == 'LIKELY' || $safeSearch['adult'] == 'VERY_LIKELY' ||
                $safeSearch['medical'] == 'LIKELY' || $safeSearch['medical'] == 'VERY_LIKELY' ||
                $safeSearch['violence'] == 'LIKELY' || $safeSearch['violence'] == 'VERY_LIKELY' ||
                $safeSearch['racy'] == 'LIKELY' || $safeSearch['racy'] == 'VERY_LIKELY') {
                header('Location: ../create/?error=10');
                exit;
            }
            curl_close($ch);
        }

        $remote_path = "/" . $file_name . "." . $file_ext;
        $url = $webdav_server . $remote_path;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file_tmp_name));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $webdav_user . ":" . $webdav_pass);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code == 201) {
            $file_url = "https://uptalkr.com/api/load_photo.php?filename=" . $file_name . "." . $file_ext;
        } else {
            header('Location: ../create/?error=8');
            exit;
        }
        curl_close($ch);
    }

    $sql = "INSERT INTO postaukset (user_id, title, content, file) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $post_title, $post_content, $file_url);

    if (mysqli_stmt_execute($stmt)) {
        $post_id = mysqli_insert_id($conn);
        $_SESSION['last_post_time'] = time();
        $post_link = "../post/?post=" . $post_id;

        $pisteet = 5; 
        $sql_pisteet = "INSERT INTO megapisteet (userid, piste) VALUES (?, ?)";
        $stmt_pisteet = mysqli_prepare($conn, $sql_pisteet);
        mysqli_stmt_bind_param($stmt_pisteet, "ii", $user_id, $pisteet);
        mysqli_stmt_execute($stmt_pisteet);
        
        header("Location: $post_link");
    } else {
        header('Location: ../create/?error=5');
        exit;
    }

    mysqli_close($conn);
}

function generateRandomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
?>
