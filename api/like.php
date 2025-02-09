<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: https://uptalkr.com/login');
    exit;
}


require '/var/uptalkr/db-pdo.php';

if (!isset($_SESSION['userid']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = intval($_SESSION['userid']);
$post_id = intval($_POST['post_id']);

try {
    $stmt = $pdo->prepare('SELECT id FROM likes WHERE post_id = :post_id AND user_id = :user_id');
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $like = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($like) {
        $stmt = $pdo->prepare('DELETE FROM likes WHERE id = :id');
        $stmt->bindParam(':id', $like['id'], PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $pdo->prepare('DELETE FROM ilmoitus_like WHERE tykkaaja_id = :user_id AND ilmoitus_id = :post_creator_id');
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':post_creator_id', $post_creator_id, PDO::PARAM_INT);
        $stmt->execute();
        $liked = false;
    } else {
        $stmt = $pdo->prepare('INSERT INTO likes (post_id, user_id) VALUES (:post_id, :user_id)');
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $liked = true;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM likes WHERE post_id = :post_id');
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();
    $likes_count = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likes_count' => $likes_count
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
?>
