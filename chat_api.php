<?php
require __DIR__ . '/backend/common.php';
require __DIR__ . '/backend/chat.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if (!$user) { throw new ChatError('Please sign in again to continue.', 401); }
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        throw new ChatError('Method not allowed.', 405);
    }
    if ($method === 'POST' && (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf']))) {
        throw new ChatError('This form expired. Refresh the page and try again.', 403);
    }
    // Polling and sending should not hold up other requests from this session.
    session_write_close();
    $action = $method === 'POST' ? input('action') : ($_GET['action'] ?? 'messages');
    if ($method === 'POST') {
        $conversation_id = chat_id($_POST['conversation_id'] ?? '');
        if ($action === 'send') {
            $data = ['message' => chat_send($conn, $conversation_id, $user['id'], $_POST['body'] ?? null, $_POST['message_token'] ?? null)];
        } elseif ($action === 'read') {
            chat_mark_read($conn, $conversation_id, $user['id'], chat_id($_POST['up_to'] ?? '0', true));
            $data = ['ok' => true];
        } else { throw new ChatError('Unknown chat action.'); }
    } elseif ($action === 'inbox') {
        $data = ['conversations' => chat_inbox($conn, $user['id'])];
    } elseif ($action === 'messages') {
        $conversation_id = chat_id($_GET['conversation_id'] ?? '');
        $conversation = chat_conversation($conn, $conversation_id, $user['id']);
        $after = chat_id($_GET['after_id'] ?? '0', true);
        $before = chat_id($_GET['before_id'] ?? '0', true);
        $data = chat_history($conn, $conversation_id, $after, $before, isset($_GET['after_id']));
        $data['peer_active'] = (bool) $conversation['peer_active'];
        $data['peer_read_up_to'] = (int) $conn->execute_query('SELECT COALESCE(MAX(id), 0) AS id FROM chat_messages WHERE conversation_id = ? AND sender_id = ? AND read_at IS NOT NULL', [$conversation_id, $user['id']])->fetch_assoc()['id'];
        if (isset($_GET['inbox'])) { $data['conversations'] = chat_inbox($conn, $user['id']); }
    } else { throw new ChatError('Unknown chat action.'); }
    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    http_response_code($error instanceof ChatError ? $error->status : 503);
    echo json_encode(['error' => chat_error_message($error)], JSON_INVALID_UTF8_SUBSTITUTE);
}
