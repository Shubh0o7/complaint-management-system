<?php
if (is_file(__DIR__ . '/../vendor/autoload.php')) require_once __DIR__ . '/../vendor/autoload.php';
/**
 * Browser push delivery adapter.
 *
 * If Composer's minishlink/web-push package and VAPID environment variables are
 * configured, queued subscriptions receive real Web Push alerts. Without that
 * optional provider configuration, subscriptions are recorded and deliveries
 * are logged as not_configured so local/Docker demos continue to work safely.
 */
function push_public_key(): string
{
    return trim((string)(getenv('VAPID_PUBLIC_KEY') ?: ''));
}

function push_is_configured(): bool
{
    return push_public_key() !== ''
        && trim((string)(getenv('VAPID_PRIVATE_KEY') ?: '')) !== ''
        && trim((string)(getenv('VAPID_SUBJECT') ?: 'mailto:support@campus.edu')) !== ''
        && class_exists('Minishlink\\WebPush\\WebPush');
}

function push_subscription_is_enabled(mysqli $conn, int $userId): bool
{
    $stmt = $conn->prepare('SELECT push_notifications FROM user_preferences WHERE user_id = ? LIMIT 1');
    if (!$stmt) return true;
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row === null ? true : (bool)$row['push_notifications'];
}

function send_push_notification(mysqli $conn, int $userId, ?int $complaintId, string $title, string $message, array $data = []): int
{
    if (!push_subscription_is_enabled($conn, $userId)) return 0;
    $stmt = $conn->prepare('SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ? AND is_active = 1');
    if (!$stmt) return 0;
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $subscriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!$subscriptions) return 0;

    $delivered = 0;
    $payload = json_encode(['title' => $title, 'body' => $message, 'url' => $data['url'] ?? 'notifications.php', 'complaint_id' => $complaintId], JSON_UNESCAPED_SLASHES);
    foreach ($subscriptions as $subscription) {
        $status = push_is_configured() ? 'failed' : 'not_configured';
        $error = push_is_configured() ? 'Web Push delivery failed' : 'Configure VAPID keys and Composer Web Push provider';
        if (push_is_configured()) {
            try {
                $auth = [
                    'VAPID' => [
                        'subject' => getenv('VAPID_SUBJECT') ?: 'mailto:support@campus.edu',
                        'publicKey' => push_public_key(),
                        'privateKey' => getenv('VAPID_PRIVATE_KEY')
                    ]
                ];
                $webPush = new Minishlink\WebPush\WebPush($auth);
                $webPush->queueNotification(
                    Minishlink\WebPush\Subscription::create([
                        'endpoint' => $subscription['endpoint'],
                        'publicKey' => $subscription['p256dh'],
                        'authToken' => $subscription['auth']
                    ]),
                    $payload
                );
                foreach ($webPush->flush() as $report) {
                    if ($report->isSuccess()) { $status = 'sent'; $error = null; $delivered++; }
                    else { $error = substr((string)$report->getReason(), 0, 255); }
                }
            } catch (Throwable $exception) {
                $error = substr($exception->getMessage(), 0, 255);
            }
        }
        $log = $conn->prepare('INSERT INTO push_notifications (user_id, complaint_id, subscription_id, title, body, status, error_message, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)' );
        if ($log) {
            $sentAt = $status === 'sent' ? date('Y-m-d H:i:s') : null;
            $log->bind_param('iiisssss', $userId, $complaintId, $subscription['id'], $title, $message, $status, $error, $sentAt);
            $log->execute(); $log->close();
        }
    }
    return $delivered;
}
?>
