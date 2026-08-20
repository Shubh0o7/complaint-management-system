<?php
require_once __DIR__ . '/email_helper.php';
require_once __DIR__ . '/push_helper.php';

function queue_notification(mysqli $conn, string $channel, int $userId, ?int $complaintId, string $recipient, string $title, string $body, array $payload = [], int $maxAttempts = 5): bool
{
    if (!in_array($channel, ['email', 'push'], true)) return false;
    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}';
    $stmt = $conn->prepare('INSERT INTO notification_queue (channel, user_id, complaint_id, recipient, title, body, payload_json, status, attempts, max_attempts, available_at) VALUES (?, ?, ?, ?, ?, ?, ?, \'queued\', 0, ?, NOW())');
    if (!$stmt) return false;
    $stmt->bind_param('siissssi', $channel, $userId, $complaintId, $recipient, $title, $body, $payloadJson, $maxAttempts);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function queue_next_item(mysqli $conn, string $workerId): ?array
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM notification_queue WHERE status = 'queued' AND available_at <= NOW() AND attempts < max_attempts ORDER BY available_at ASC, id ASC LIMIT 1 FOR UPDATE");
        if (!$stmt) throw new RuntimeException('Unable to prepare queue claim.');
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$item) { $conn->commit(); return null; }
        $stmt = $conn->prepare("UPDATE notification_queue SET status = 'processing', attempts = attempts + 1, locked_at = NOW(), locked_by = ?, updated_at = NOW() WHERE id = ? AND status = 'queued'");
        if (!$stmt) throw new RuntimeException('Unable to claim queue item.');
        $stmt->bind_param('si', $workerId, $item['id']);
        if (!$stmt->execute() || $stmt->affected_rows !== 1) throw new RuntimeException('Queue item was claimed by another worker.');
        $stmt->close();
        $conn->commit();
        $item['attempts'] = (int)$item['attempts'] + 1;
        return $item;
    } catch (Throwable $exception) {
        try { $conn->rollback(); } catch (Throwable $ignored) { }
        return null;
    }
}

function queue_mark_result(mysqli $conn, int $queueId, bool $success, string $status, ?string $error = null): void
{
    if ($success) {
        $finalStatus = $status === 'disabled' ? 'disabled' : 'sent';
        $sentAt = $finalStatus === 'sent' ? date('Y-m-d H:i:s') : null;
        $stmt = $conn->prepare("UPDATE notification_queue SET status = ?, sent_at = ?, locked_at = NULL, locked_by = NULL, last_error = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt) { $emptyError = $finalStatus === 'disabled' ? substr($error, 0, 500) : null; $stmt->bind_param('sssi', $finalStatus, $sentAt, $emptyError, $queueId); $stmt->execute(); $stmt->close(); }
        return;
    }
    $stmt = $conn->prepare("UPDATE notification_queue SET status = CASE WHEN attempts >= max_attempts THEN 'failed' ELSE 'queued' END, available_at = CASE WHEN attempts >= max_attempts THEN available_at ELSE TIMESTAMPADD(MINUTE, POW(2, LEAST(attempts, 5)), NOW()) END, locked_at = NULL, locked_by = NULL, last_error = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt) { $safeError = substr($error ?: ($status ?: 'Delivery failed'), 0, 500); $stmt->bind_param('si', $safeError, $queueId); $stmt->execute(); $stmt->close(); }
}

function queue_delivery_counts(mysqli $conn): array
{
    $counts = ['queued' => 0, 'processing' => 0, 'sent' => 0, 'failed' => 0, 'disabled' => 0];
    $result = $conn->query('SELECT status, COUNT(*) AS total FROM notification_queue GROUP BY status');
    if ($result) foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) if (array_key_exists($row['status'], $counts)) $counts[$row['status']] = (int)$row['total'];
    return $counts;
}

function queue_recent_deliveries(mysqli $conn, string $status = '', string $channel = '', int $limit = 80): array
{
    $limit = max(1, min($limit, 200));
    $sql = 'SELECT q.*, u.full_name, c.reference_no FROM notification_queue q LEFT JOIN users u ON u.id = q.user_id LEFT JOIN complaints c ON c.id = q.complaint_id WHERE 1=1';
    $types = ''; $params = [];
    if (in_array($status, ['queued', 'processing', 'sent', 'failed', 'disabled'], true)) { $sql .= ' AND q.status = ?'; $types .= 's'; $params[] = $status; }
    if (in_array($channel, ['email', 'push'], true)) { $sql .= ' AND q.channel = ?'; $types .= 's'; $params[] = $channel; }
    $sql .= ' ORDER BY q.created_at DESC LIMIT ' . $limit;
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    return $rows;
}

function queue_log_delivery(mysqli $conn, array $item, string $status, string $message = ''): void
{
    $stmt = $conn->prepare('INSERT INTO notification_delivery_logs (queue_id, channel, user_id, complaint_id, status, attempt, message) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) return;
    $queueId = (int)$item['id']; $userId = (int)$item['user_id']; $complaintId = $item['complaint_id'] !== null ? (int)$item['complaint_id'] : null; $attempt = (int)$item['attempts']; $safeMessage = substr($message, 0, 500);
    $stmt->bind_param('isiisis', $queueId, $item['channel'], $userId, $complaintId, $status, $attempt, $safeMessage);
    $stmt->execute(); $stmt->close();
}

function queue_recover_stale_claims(mysqli $conn, int $minutes = 15): int
{
    $minutes = max(5, min($minutes, 120));
    $stmt = $conn->prepare("UPDATE notification_queue SET status = 'queued', locked_at = NULL, locked_by = NULL, available_at = NOW(), last_error = 'Recovered stale worker claim', updated_at = NOW() WHERE status = 'processing' AND locked_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    if (!$stmt) return 0;
    $stmt->bind_param('i', $minutes); $stmt->execute(); $affected = $stmt->affected_rows; $stmt->close();
    return $affected;
}
