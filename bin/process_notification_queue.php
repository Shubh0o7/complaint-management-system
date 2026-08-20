<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit("CLI only\n"); }
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/notification_queue.php';

$limit = max(1, min((int)($argv[1] ?? 25), 200));
$workerId = gethostname() . ':' . getmypid();
$recovered = queue_recover_stale_claims($conn);
$processed = 0; $sent = 0; $failed = 0; $disabled = 0;

for ($i = 0; $i < $limit; $i++) {
    $item = queue_next_item($conn, $workerId);
    if (!$item) break;
    $processed++;
    $ok = false; $status = 'failed'; $detail = '';
    try {
        if ($item['channel'] === 'email') {
            $ok = send_email_notification($conn, (int)$item['user_id'], $item['complaint_id'] !== null ? (int)$item['complaint_id'] : null, (string)$item['recipient'], (string)$item['title'], (string)$item['body']);
            $mailEnabled = filter_var(getenv('MAIL_ENABLED') ?: '0', FILTER_VALIDATE_BOOLEAN);
            $status = $ok ? ($mailEnabled ? 'sent' : 'logged') : 'failed';
            $detail = $ok ? ($mailEnabled ? 'Email adapter accepted the message.' : 'Email recorded in local delivery log.') : 'Email adapter reported a failure.';
        } else {
            $payload = json_decode((string)($item['payload_json'] ?? '{}'), true) ?: [];
            $delivered = send_push_notification($conn, (int)$item['user_id'], $item['complaint_id'] !== null ? (int)$item['complaint_id'] : null, (string)$item['title'], (string)$item['body'], $payload);
            $hasSubscription = false;
            $sub = $conn->prepare('SELECT COUNT(*) AS total FROM push_subscriptions WHERE user_id = ? AND is_active = 1');
            if ($sub) { $sub->bind_param('i', $item['user_id']); $sub->execute(); $hasSubscription = (int)($sub->get_result()->fetch_assoc()['total'] ?? 0) > 0; $sub->close(); }
            $ok = $delivered > 0;
            $status = $ok ? 'sent' : ($hasSubscription && !push_is_configured() ? 'disabled' : 'failed');
            $detail = $ok ? "Delivered to {$delivered} subscription(s)." : ($status === 'disabled' ? 'Push provider is not configured.' : 'No active subscription accepted the alert.');
        }
    } catch (Throwable $exception) {
        $detail = substr($exception->getMessage(), 0, 500);
    }
    queue_log_delivery($conn, $item, $status, $detail);
    if ($ok) { queue_mark_result($conn, (int)$item['id'], true, $status, $detail); $sent++; }
    elseif ($status === 'disabled') { queue_mark_result($conn, (int)$item['id'], true, 'disabled', $detail); $disabled++; }
    else { queue_mark_result($conn, (int)$item['id'], false, $status, $detail); $failed++; }
}

printf("worker=%s recovered=%d processed=%d sent=%d disabled=%d failed=%d\n", $workerId, $recovered, $processed, $sent, $disabled, $failed);
