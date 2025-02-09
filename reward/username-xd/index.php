<?php
session_start();
require '/var/uptalkr/updb.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /login');
    exit;
}

$userID = $_SESSION['userid'];
$rewardName = basename(__FILE__, '.php');
$rewardPoints = 50;


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . htmlspecialchars($conn->connect_error));
}


$checkQuery = "SELECT id FROM rewards_claimed WHERE userid = ? AND reward_name = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("is", $userID, $rewardName);
$stmt->execute();
$stmt->store_result();

$alreadyClaimed = $stmt->num_rows > 0;
$stmt->close();

if (!$alreadyClaimed) {
    $conn->begin_transaction();
    try {

        $updatePointsQuery = "INSERT INTO megapisteet (userid, piste) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE piste = piste + ?";
        $stmt = $conn->prepare($updatePointsQuery);
        $stmt->bind_param("iii", $userID, $rewardPoints, $rewardPoints);
        $stmt->execute();
        $stmt->close();


        $claimRewardQuery = "INSERT INTO rewards_claimed (userid, reward_name) VALUES (?, ?)";
        $stmt = $conn->prepare($claimRewardQuery);
        $stmt->bind_param("is", $userID, $rewardName);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $message = "Congratulations! You have successfully claimed the reward and earned $rewardPoints UpPoints.";
        $success = true;
    } catch (Exception $e) {
        $conn->rollback();
        $message = "An error occurred while claiming the reward: " . htmlspecialchars($e->getMessage());
        $success = false;
    }
} else {
    $message = "You have already claimed this reward.";
    $success = false;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uptalkr</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f8ff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .reward-card {
            max-width: 500px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #f0f8ff, #e6f7ff);
            padding: 20px;
        }
        .reward-card .card-body {
            text-align: center;
        }
        .reward-card .btn {
            margin-top: 15px;
            border-radius: 25px;
        }
        h1 {
            font-size: 2rem;
            font-weight: bold;
        }
        p {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="reward-card">
        <div class="card-body">
            <?php if ($success): ?>
                <h1 class="text-success">🎉 Congratulations! 🎉</h1>
                <p><?php echo htmlspecialchars($message); ?></p>
            <?php else: ?>
                <h1 class="text-danger">⚠️ Notice ⚠️</h1>
                <p><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <a href="/profile" class="btn btn-primary">Go to Profile</a>
        </div>
    </div>
</body>
</html>
