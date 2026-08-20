<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/push_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}
require_csrf_json();

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? 'subscribe';
$userId = (int)$_SESSION['user_id'];

if ($action === 'unsubscribe') {
    $stmt = $conn->prepare('UPDATE push_subscriptions SET is_active = 0 WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Browser push alerts disabled.' : 'Unable to disable browser push alerts.']);
    exit();
}

$endpoint = trim((string)($input['endpoint'] ?? ''));
$p256dh = trim((string)($input['keys']['p256dh'] ?? ''));
$auth = trim((string)($input['keys']['auth'] ?? ''));
if ($endpoint === '' || $p256dh === '' || $auth === '' || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid browser push subscription.']);
    exit();
}

$stmt = $conn->prepare('INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth), is_active = 1');
$stmt->bind_param('isss', $userId, $endpoint, $p256dh, $auth);
$ok = $stmt->execute();
$stmt->close();
if (!$ok) http_response_code(500);
echo json_encode(['success' => $ok, 'configured' => function_exists('push_is_configured') ? push_is_configured() : false, 'message' => $ok ? 'Browser push alerts enabled.' : 'Unable to save browser push subscription.']);
?>
