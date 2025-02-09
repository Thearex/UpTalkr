<?php

require '/var/uptalkr/updb.php';


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $postedSupportId = isset($_POST['supportid']) ? $_POST['supportid'] : '';


    $postedSupportId = secureInput($postedSupportId);


    $stmt = $conn->prepare("SELECT supportid FROM supportid WHERE supportid = ?");
    $stmt->bind_param("s", $postedSupportId);
    $stmt->execute();
    $result = $stmt->get_result();


    if ($result->num_rows > 0) {

        $response = array("valid" => "yes");
    } else {

        $response = array("valid" => "no");
    }


    $conn->close();


    header('Content-Type: application/json');


    echo json_encode($response);
    
} else {

    echo json_encode(array("error" => "Invalid request method. Please use POST."));
}


function secureInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>
