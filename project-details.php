<?php
require __DIR__ . '/backend/common.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$project = $conn->execute_query('SELECT p.*, u.full_name, u.department AS lead_department FROM projects p JOIN users u ON u.id = p.user_id WHERE p.id = ?', [$id])->fetch_assoc();
if (!$project || ($project['approval'] !== 'approved' && (int) $project['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin')) {
    http_response_code(404);
    exit('Project not found.');
}
$is_owner = (int) $project['user_id'] === (int) $user['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = input('action');
        if ($action === 'status') {
            if (!$is_owner) {
                throw new RuntimeException('Only the project lead can update the project status.');
            }
            $status = input('status');
            if (!in_array($status, ['Active', 'Recruiting', 'Completed'], true)) {
                throw new InvalidArgumentException('Choose a valid project status.');
            }
            $conn->execute_query('UPDATE projects SET status = ? WHERE id = ?', [$status, $id]);
            flash('Project status updated.');
        } elseif ($action === 'request_join') {
            if ($is_owner || $project['approval'] !== 'approved' || $project['status'] !== 'Recruiting') {
                throw new InvalidArgumentException('This project is not accepting requests right now.');
            }
            $message = substr(input('message'), 0, 1000);
            $existing = $conn->execute_query('SELECT status FROM project_join_requests WHERE project_id = ? AND user_id = ?', [$id, $user['id']])->fetch_assoc();
            if ($existing && $existing['status'] === 'pending') {
                throw new InvalidArgumentException('Your request is already waiting for review.');
            }
            if ($existing && $existing['status'] === 'approved') {
                throw new InvalidArgumentException('You are already an approved project member.');
            }
            $conn->execute_query(
                "INSERT INTO project_join_requests (project_id, user_id, message, status, reviewed_by, reviewed_at) VALUES (?, ?, ?, 'pending', NULL, NULL)
                 ON DUPLICATE KEY UPDATE message = VALUES(message), status = 'pending', reviewed_by = NULL, reviewed_at = NULL, updated_at = CURRENT_TIMESTAMP",
                [$id, $user['id'], $message ?: null]
            );
            notify_user($project['user_id'], $user['full_name'] . ' requested to join “' . $project['title'] . '”.');
            flash('Your join request was sent to the project lead for review.');
        } elseif ($action === 'withdraw_request') {
            $conn->execute_query("UPDATE project_join_requests SET status = 'withdrawn' WHERE project_id = ? AND user_id = ? AND status = 'pending'", [$id, $user['id']]);
            flash('Your join request was withdrawn.');
        } elseif ($action === 'review_request') {
            if (!$is_owner && $user['role'] !== 'admin') {
                throw new RuntimeException('Only the project lead or an administrator can review requests.');
            }
            $request_user_id = (int) input('user_id');
            $decision = input('decision');
            if (!in_array($decision, ['approved', 'rejected'], true)) {
                throw new InvalidArgumentException('Choose a valid review decision.');
            }
            $conn->begin_transaction();
            $request = $conn->execute_query("SELECT r.user_id, u.full_name FROM project_join_requests r JOIN users u ON u.id = r.user_id WHERE r.project_id = ? AND r.user_id = ? AND r.status = 'pending' FOR UPDATE", [$id, $request_user_id])->fetch_assoc();
            if (!$request) {
                throw new InvalidArgumentException('This request is no longer waiting for review.');
            }
            $conn->execute_query('UPDATE project_join_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE project_id = ? AND user_id = ?', [$decision, $user['id'], $id, $request_user_id]);
            if ($decision === 'approved') {
                $conn->execute_query('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)', [$id, $request_user_id]);
            }
            notify_user($request_user_id, 'Your request to join “' . $project['title'] . '” was ' . $decision . '.');
            $conn->commit();
            flash('Join request ' . $decision . '.');
        } elseif ($action === 'leave') {
            $conn->execute_query('DELETE FROM project_members WHERE project_id = ? AND user_id = ?', [$id, $user['id']]);
            flash('You left the project.');
        } else {
            throw new InvalidArgumentException('Unknown project action.');
        }
        go('project-details.php?id=' . $id);
    } catch (Throwable $exception) {
        $conn->rollback();
        $error = page_error($exception);
    }
}

$members = $conn->execute_query('SELECT u.id, u.full_name, u.department, m.member_role FROM project_members m JOIN users u ON u.id = m.user_id WHERE m.project_id = ? ORDER BY u.full_name', [$id])->fetch_all(MYSQLI_ASSOC);
$member_ids = array_map('intval', array_column($members, 'id'));
$is_member = in_array((int) $user['id'], $member_ids, true);
$my_request = $conn->execute_query('SELECT status, message, created_at FROM project_join_requests WHERE project_id = ? AND user_id = ?', [$id, $user['id']])->fetch_assoc();
$requests = $is_owner || $user['role'] === 'admin'
    ? $conn->execute_query("SELECT r.*, u.full_name, u.email, u.department FROM project_join_requests r JOIN users u ON u.id = r.user_id WHERE r.project_id = ? AND r.status = 'pending' ORDER BY r.created_at", [$id])->fetch_all(MYSQLI_ASSOC)
    : [];
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($project['title']) ?> · Research Project</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="portal.css">
<style>
.project-hero{padding:30px;border-radius:20px;background:linear-gradient(135deg,#102a43,#1f55aa);color:white}.project-hero h1{margin:8px 0;font-size:clamp(28px,4vw,44px);letter-spacing:-.04em}.project-hero p{max-width:760px;color:#d9e7ff;line-height:1.7}.project-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}.project-meta span{padding:7px 10px;border-radius:999px;background:rgba(255,255,255,.13);font-size:13px;font-weight:700}.project-layout{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(270px,.8fr);gap:18px;margin-top:18px}.request-card{border-left:4px solid #2457d6!important}.member-list{display:grid;gap:10px}.member{padding:12px;border:1px solid #e5e9f0;border-radius:12px}.member strong,.member span{display:block}.member span{margin-top:3px;color:#667085;font-size:13px}.request-head{display:flex;justify-content:space-between;gap:12px;align-items:start}.review-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.status-pending{background:#fff7e6!important;color:#9a6700!important}.status-approved{background:#ecfdf3!important;color:#067647!important}.status-rejected,.status-withdrawn{background:#fef3f2!important;color:#b42318!important}@media(max-width:850px){.project-layout{grid-template-columns:1fr}.project-hero{padding:23px}}
</style></head>
<body class="portal-page"><div class="app-container portal-layout"><?php require __DIR__ . '/backend/sidebar.php'; ?>
<main class="main-content"><div class="live-content">
<p><a href="research-projects.php"><i class="fa-solid fa-arrow-left"></i> All research projects</a></p><?php show_message(); ?><?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<section class="project-hero"><span class="eyebrow">RESEARCH PROJECT</span><h1><?= e($project['title']) ?></h1><p><?= nl2br(e($project['description'])) ?></p><div class="project-meta"><span><?= e($project['department']) ?></span><span><?= e($project['status']) ?></span><span>Led by <?= e($project['full_name']) ?></span></div></section>
<div class="project-layout"><div>
<?php if ($is_owner): ?><section class="box"><h2>Project settings</h2><form method="post"><label class="field">Recruitment status<select name="status"><?php foreach (['Recruiting','Active','Completed'] as $option): ?><option <?= $project['status'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label><button name="action" value="status">Save status</button></form></section><?php endif; ?>
<?php if (!$is_owner && !$is_member && $project['status'] === 'Recruiting'): ?>
  <?php if ($my_request && $my_request['status'] === 'pending'): ?><section class="box request-card"><h2>Request under review</h2><p>Your request is with the project lead. You will receive a notification when it is reviewed.</p><form method="post"><button class="secondary" name="action" value="withdraw_request">Withdraw request</button></form></section>
  <?php elseif ($my_request && $my_request['status'] === 'rejected'): ?><section class="box request-card"><h2>Request not approved</h2><p>The project lead did not approve your earlier request. You may send an updated request while recruitment is open.</p><form method="post"><label class="field">Message to the project lead<textarea name="message" maxlength="1000" placeholder="Briefly explain your relevant interest or experience."></textarea></label><button name="action" value="request_join">Send a new request</button></form></section>
  <?php else: ?><section class="box request-card"><h2>Request to join</h2><p>This project uses a review process. Share a short note with the project lead; membership starts only after approval.</p><form method="post"><label class="field">Message to the project lead <span class="muted">(optional)</span><textarea name="message" maxlength="1000" placeholder="What perspective, experience, or time can you contribute?"></textarea></label><button name="action" value="request_join">Submit join request</button></form></section><?php endif; ?>
<?php elseif ($is_member && !$is_owner): ?><section class="box"><h2>You are a project member</h2><p>Your membership was approved by the project lead.</p><form method="post"><button class="secondary" name="action" value="leave">Leave project</button></form></section><?php endif; ?>
<?php if ($is_owner || $user['role'] === 'admin'): ?><section class="box"><h2>Join requests <span class="tag"><?= count($requests) ?> pending</span></h2><?php if (!$requests): ?><p class="muted">No join requests are waiting for review.</p><?php endif; ?><?php foreach ($requests as $request): ?><article class="member"><div class="request-head"><div><strong><?= e($request['full_name']) ?></strong><span><?= e($request['department']) ?> · <?= e($request['email']) ?></span></div><span class="tag status-pending">Awaiting review</span></div><?php if ($request['message']): ?><p><?= nl2br(e($request['message'])) ?></p><?php endif; ?><form class="review-actions" method="post"><input type="hidden" name="action" value="review_request"><input type="hidden" name="user_id" value="<?= (int) $request['user_id'] ?>"><button name="decision" value="approved">Approve member</button><button class="danger" name="decision" value="rejected">Decline</button></form></article><?php endforeach; ?></section><?php endif; ?>
</div><aside><section class="box"><h2>Team <span class="tag"><?= count($members) ?></span></h2><div class="member-list"><?php if (!$members): ?><p class="muted">Approved members will appear here.</p><?php endif; ?><?php foreach ($members as $member): ?><div class="member"><strong><?= e($member['full_name']) ?></strong><span><?= e($member['department']) ?> · <?= e(str_replace('_', ' ', $member['member_role'])) ?></span></div><?php endforeach; ?></div></section><section class="box"><h2>How joining works</h2><p class="muted">1. Send a request<br>2. Project lead reviews it<br>3. Get a notification and join after approval</p></section></aside></div>
</div></main></div></body></html>
