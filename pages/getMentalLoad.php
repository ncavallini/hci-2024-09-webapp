<?php
require_once __DIR__ . "/../utils/init.php";

if (!Auth::is_logged_in()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_GET['user_id'] ?? null;

if (!$user_id || !is_numeric($user_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$sql = "
    SELECT SUM(estimated_load) AS total_load, u.name
    FROM group_tasks gt
    JOIN users u ON gt.user_id = u.user_id
    WHERE gt.user_id = ?";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$user_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

echo json_encode($data);
