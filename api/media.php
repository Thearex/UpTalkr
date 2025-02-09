<?php
require '/var/secure/config.php';
ini_set('memory_limit', '2048M');

$filename = $_GET['filename'] ?? ''; 


if (empty($filename) || preg_match('/\.\.|\/|\0/', $filename)) {
    header("Location: https://uptalkr.com");
    exit;
}

$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
switch ($file_extension) {
    case 'jpg':
    case 'jpeg':
        $content_type = 'image/jpeg';
        break;
    case 'png':
        $content_type = 'image/png';
        break;
    case 'gif':
        $content_type = 'image/gif';
        break;
    case 'mp4':
        $content_type = 'video/mp4';
        break;
    default:
        header("Location: https://uptalkr.com/500.html");
        exit;
}


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webdav_server . '/' . urlencode($filename));
curl_setopt($ch, CURLOPT_USERPWD, $webdav_user . ':' . $webdav_pass);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


header('Content-Type: ' . $content_type);


curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    echo $data; 
    ob_flush(); 
    flush();    
    return strlen($data);
});


curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    header("Location: https://uptalkr.com/404");
    exit;
}
?>
