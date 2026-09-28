<?php
class ChatError extends RuntimeException {
    public function __construct($message, public int $status = 400) {
        parent::__construct($message);
    }
}

function chat_id($value, $allow_zero = false) {
    if ((!is_string($value) && !is_int($value)) || !preg_match('/^\d{1,10}$/D', (string) $value)) {
        throw new ChatError('Invalid chat request.');
    }
    $id = (int) $value;
    if ($id > 2147483647 || $id < ($allow_zero ? 0 : 1)) {
        throw new ChatError('Invalid chat request.');
    }
    return $id;
}

function chat_conversation($conn, $id, $user_id, $lock = false) {
    $row = $conn->execute_query(
        'SELECT c.*, u.id AS peer_id, u.full_name AS peer_name, u.department AS peer_department, u.is_active AS peer_active
         FROM chat_conversations c JOIN users u ON u.id = IF(c.user_one_id = ?, c.user_two_id, c.user_one_id)
         WHERE c.id = ? AND (c.user_one_id = ? OR c.user_two_id = ?)' . ($lock ? ' FOR UPDATE' : ''),
        [$user_id, $id, $user_id, $user_id]
    )->fetch_assoc();
    if (!$row) { throw new ChatError('Conversation not found.', 404); }
    return $row;
}

function chat_start($conn, $user_id, $recipient_id) {
    if ($recipient_id === (int) $user_id) { throw new ChatError('Choose another person to message.', 422); }
    $recipient = $conn->execute_query('SELECT id FROM users WHERE id = ? AND is_active = 1', [$recipient_id])->fetch_assoc();
    if (!$recipient) { throw new ChatError('This person is unavailable.', 404); }
    $conn->execute_query(
        'INSERT INTO chat_conversations (user_one_id, user_two_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)',
        [min($user_id, $recipient_id), max($user_id, $recipient_id)]
    );
    return (int) $conn->insert_id;
}

function chat_inbox($conn, $user_id) {
    return $conn->execute_query(
        'SELECT c.id, u.full_name AS peer_name, u.is_active AS peer_active, c.updated_at,
          m.body AS last_body, m.created_at AS last_at,
          (SELECT COUNT(*) FROM chat_messages unread WHERE unread.conversation_id = c.id AND unread.sender_id <> ? AND unread.read_at IS NULL) AS unread_count
         FROM chat_conversations c
         JOIN users u ON u.id = IF(c.user_one_id = ?, c.user_two_id, c.user_one_id)
         LEFT JOIN chat_messages m ON m.id = (SELECT MAX(latest.id) FROM chat_messages latest WHERE latest.conversation_id = c.id)
         WHERE c.user_one_id = ? OR c.user_two_id = ?
         ORDER BY c.updated_at DESC, m.id DESC, c.id DESC LIMIT 100',
        [$user_id, $user_id, $user_id, $user_id]
    )->fetch_all(MYSQLI_ASSOC);
}

function chat_history($conn, $conversation_id, $after = 0, $before = 0, $forward = false) {
    if ($forward) {
        $rows = $conn->execute_query('SELECT id, sender_id, body, created_at, read_at FROM chat_messages WHERE conversation_id = ? AND id > ? ORDER BY id LIMIT 101', [$conversation_id, $after])->fetch_all(MYSQLI_ASSOC);
    } else {
        $rows = $conn->execute_query('SELECT id, sender_id, body, created_at, read_at FROM chat_messages WHERE conversation_id = ? AND (? = 0 OR id < ?) ORDER BY id DESC LIMIT 51', [$conversation_id, $before, $before])->fetch_all(MYSQLI_ASSOC);
    }
    $has_more = count($rows) > ($forward ? 100 : 50);
    if ($has_more) { array_pop($rows); }
    return ['messages' => $forward ? $rows : array_reverse($rows), 'has_more' => $has_more];
}

function chat_send($conn, $conversation_id, $user_id, $body, $token) {
    if (!is_string($body) || trim($body) === '' || strlen($body) > 8000 || preg_match('//u', $body) !== 1 || preg_match_all('/./us', $body) > 2000) {
        throw new ChatError('Enter a message of up to 2,000 characters.', 422);
    }
    if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/D', $token)) {
        throw new ChatError('Refresh the page before sending this message.', 422);
    }
    $conn->begin_transaction();
    try {
        // Lock the thread so simultaneous sends commit in message order.
        $conversation = chat_conversation($conn, $conversation_id, $user_id, true);
        if (!$conversation['peer_active']) { throw new ChatError('This account is disabled. You can still read your messages.', 403); }
        $existing = $conn->execute_query('SELECT id, sender_id, body, created_at, read_at FROM chat_messages WHERE conversation_id = ? AND sender_id = ? AND client_token = ?', [$conversation_id, $user_id, $token])->fetch_assoc();
        if (!$existing) {
            $conn->execute_query('INSERT INTO chat_messages (conversation_id, sender_id, body, client_token) VALUES (?, ?, ?, ?)', [$conversation_id, $user_id, trim($body), $token]);
            $message_id = $conn->insert_id;
            $conn->execute_query('UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?', [$conversation_id]);
            $existing = $conn->execute_query('SELECT id, sender_id, body, created_at, read_at FROM chat_messages WHERE id = ?', [$message_id])->fetch_assoc();
        }
        $conn->commit();
        return $existing;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function chat_mark_read($conn, $conversation_id, $user_id, $up_to) {
    chat_conversation($conn, $conversation_id, $user_id);
    $conn->execute_query('UPDATE chat_messages SET read_at = NOW() WHERE conversation_id = ? AND sender_id <> ? AND id <= ? AND read_at IS NULL', [$conversation_id, $user_id, $up_to]);
}

function chat_error_message($error) {
    if ($error instanceof ChatError) { return $error->getMessage(); }
    error_log($error->getMessage());
    return 'Private messaging is temporarily unavailable. Please try again later.';
}
