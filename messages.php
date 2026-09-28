<?php
require __DIR__ . '/backend/common.php';
require_login();
require __DIR__ . '/backend/chat.php';

$error = '';
$available = true;
$conversation_id = 0;
$conversation = null;
$inbox = $people = [];
$history = ['messages' => [], 'has_more' => false];
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if (strlen($search) > 100) { $search = ''; }
try {
    $conversation_id = chat_id($_GET['conversation_id'] ?? '0', true);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        if (input('action') === 'start') {
            $conversation_id = chat_start($conn, $user['id'], chat_id($_POST['recipient_id'] ?? ''));
        } elseif (input('action') === 'send') {
            $conversation_id = chat_id($_POST['conversation_id'] ?? '');
            chat_send($conn, $conversation_id, $user['id'], $_POST['body'] ?? null, $_POST['message_token'] ?? null);
        } elseif (input('action') === 'read') {
            $conversation_id = chat_id($_POST['conversation_id'] ?? '');
            chat_mark_read($conn, $conversation_id, $user['id'], chat_id($_POST['up_to'] ?? '0', true));
        } else { throw new ChatError('Unknown chat action.'); }
        go('messages.php?conversation_id=' . $conversation_id);
    }
} catch (Throwable $exception) {
    http_response_code($exception instanceof ChatError ? $exception->status : 503);
    $error = chat_error_message($exception);
    $available = $exception instanceof ChatError;
}
if ($available) {
    try {
        $inbox = chat_inbox($conn, $user['id']);
        $people = $conn->execute_query('SELECT id, full_name, department, role FROM users WHERE is_active = 1 AND id <> ? AND full_name LIKE ? ORDER BY full_name, id LIMIT 20', [$user['id'], '%' . $search . '%'])->fetch_all(MYSQLI_ASSOC);
        if ($conversation_id) {
            $conversation = chat_conversation($conn, $conversation_id, $user['id']);
            $history = chat_history($conn, $conversation_id);
        }
    } catch (Throwable $exception) {
        http_response_code($exception instanceof ChatError ? $exception->status : 503);
        $error = chat_error_message($exception);
        $available = $exception instanceof ChatError;
    }
}
$message_token = input('message_token');
if (!preg_match('/^[a-f0-9]{32}$/D', $message_token)) { $message_token = bin2hex(random_bytes(16)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private Messages - UIU Research Portal</title>
    <link rel="stylesheet" href="mystyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="portal.css">
    <link rel="stylesheet" href="chat.css">
    <script src="chat.js" defer></script>
</head>
<body class="portal-page">
<div class="app-container portal-layout">
    <?php require __DIR__ . '/backend/sidebar.php'; ?>
    <main class="chat-main"><div class="live-content">
        <div class="chat-page-heading">
            <div><h1>Private Messages</h1><p>Have a one-to-one conversation with someone in your community.</p></div>
            <a class="button secondary" href="Community_Forum.php">Community Forum</a>
        </div>
        <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($available): ?>
        <div class="chat-workspace" id="chat-app" data-conversation-id="<?= (int) ($conversation['id'] ?? 0) ?>" data-user-id="<?= (int) $user['id'] ?>" data-csrf="<?= e($_SESSION['csrf']) ?>">
            <section class="chat-rail" aria-label="Conversations and people">
                <h2>Inbox</h2>
                <div class="chat-inbox" id="chat-inbox">
                    <?php foreach ($inbox as $thread): ?>
                    <a class="chat-inbox-item<?= (int) $thread['id'] === $conversation_id ? ' selected' : '' ?>" href="messages.php?conversation_id=<?= (int) $thread['id'] ?>"<?= (int) $thread['id'] === $conversation_id ? ' aria-current="page"' : '' ?>>
                        <span class="chat-contact-name"><?= e($thread['peer_name']) ?></span>
                        <span class="chat-preview"><?= e($thread['last_body'] ?? 'Start your conversation') ?></span>
                        <?php if ($thread['unread_count']): ?><span class="chat-unread" aria-label="<?= (int) $thread['unread_count'] ?> unread messages"><?= (int) $thread['unread_count'] ?></span><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <?php if (!$inbox): ?><p class="muted">No conversations yet. Choose someone below to get started.</p><?php endif; ?>
                </div>
                <details class="chat-people" <?= !$conversation || $search !== '' ? 'open' : '' ?>>
                    <summary>New conversation</summary>
                    <form method="get" class="chat-search">
                        <?php if ($conversation_id): ?><input type="hidden" name="conversation_id" value="<?= $conversation_id ?>"><?php endif; ?>
                        <label for="chat-search">Find a person by name</label>
                        <div><input id="chat-search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Search people"><button type="submit" aria-label="Search people"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button></div>
                    </form>
                    <div class="chat-people-list">
                        <?php foreach ($people as $person): ?>
                        <form method="post" class="chat-person">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="start"><input type="hidden" name="recipient_id" value="<?= (int) $person['id'] ?>">
                            <div><strong><?= e($person['full_name']) ?></strong><span class="muted"><?= e($person['department']) ?> · <?= e(ucfirst($person['role'])) ?></span></div>
                            <button type="submit" aria-label="Message <?= e($person['full_name']) ?>"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i></button>
                        </form>
                        <?php endforeach; ?>
                        <?php if (!$people): ?><p class="muted">No people found. Try another name.</p><?php endif; ?>
                    </div>
                </details>
            </section>
            <section class="chat-thread" aria-label="Private conversation">
                <?php if ($conversation): ?>
                <?php preg_match('/^./us', $conversation['peer_name'], $chat_initial); ?>
                <header class="chat-thread-header">
                    <div class="chat-avatar" aria-hidden="true"><?= e(strtoupper($chat_initial[0] ?? '')) ?></div>
                    <div><h2><?= e($conversation['peer_name']) ?></h2><p>One-to-one conversation · <?= e($conversation['peer_department']) ?></p></div>
                </header>
                <div class="chat-history" id="chat-history" tabindex="0" aria-label="Message history">
                    <button class="secondary chat-older" id="chat-older" type="button" <?= $history['has_more'] ? '' : 'hidden' ?>>Load older messages</button>
                    <ol class="chat-message-list" id="chat-messages" role="log" aria-label="Messages" aria-live="polite" aria-relevant="additions">
                        <?php foreach ($history['messages'] as $message): $mine = (int) $message['sender_id'] === (int) $user['id']; ?>
                        <li class="chat-message<?= $mine ? ' mine' : '' ?>" data-message-id="<?= (int) $message['id'] ?>">
                            <div class="chat-bubble"><p><?= e($message['body']) ?></p><div class="chat-message-meta"><time><?= e($message['created_at']) ?></time><?php if ($mine): ?><span class="chat-receipt"><?= $message['read_at'] ? 'Seen' : 'Sent' ?></span><?php endif; ?></div></div>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <p class="chat-empty" id="chat-empty" <?= $history['messages'] ? 'hidden' : '' ?>>Say hello to <?= e($conversation['peer_name']) ?> to start the conversation.</p>
                </div>
                <p class="chat-status" id="chat-status" role="status"><?= $conversation['peer_active'] ? '' : 'This account is disabled. You can still read your messages.' ?></p>
                <form method="post" class="chat-composer" id="chat-composer">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="send"><input type="hidden" name="conversation_id" value="<?= $conversation_id ?>"><input type="hidden" name="message_token" value="<?= e($message_token) ?>">
                    <label class="chat-sr-only" for="chat-body">Message</label>
                    <textarea id="chat-body" name="body" maxlength="2000" rows="2" placeholder="Write a message…" required <?= $conversation['peer_active'] ? '' : 'disabled' ?>><?= e(input('body')) ?></textarea>
                    <button type="submit" aria-label="Send message" <?= $conversation['peer_active'] ? '' : 'disabled' ?>><i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>Send</span></button>
                </form>
                <noscript><p class="muted">Refresh to see new messages.</p><form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="read"><input type="hidden" name="conversation_id" value="<?= $conversation_id ?>"><input type="hidden" name="up_to" value="<?= (int) (end($history['messages'])['id'] ?? 0) ?>"><button>Mark as read</button></form></noscript>
                <?php else: ?>
                <div class="chat-welcome"><i class="fa-regular fa-comments" aria-hidden="true"></i><h2>Your private conversations</h2><p>Open a conversation from your inbox, or find someone to message.</p><p class="chat-status" id="chat-status" role="status"></p></div>
                <?php endif; ?>
            </section>
        </div>
        <?php endif; ?>
    </div></main>
</div>
</body>
</html>
