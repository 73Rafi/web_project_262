<?php
require __DIR__ . '/backend/common.php';
require_login();

require_admin();
require __DIR__ . '/backend/admin_account.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int) input('id');
        $action = input('action');
        if ($action === 'create_admin') {
            $current_password = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
            if (!password_verify($current_password, $user['password'])) {
                throw new InvalidArgumentException('Your current password is incorrect.');
            }
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if ($password !== ($_POST['confirm_password'] ?? null)) {
                throw new InvalidArgumentException('New passwords do not match.');
            }
            create_admin_account($conn, input('full_name'), input('email'), $password);
            flash('New admin account created. They can now sign in.');
        } elseif ($action === 'user') {
            $conn->execute_query("UPDATE users SET is_active = ?, session_version = session_version + 1 WHERE id = ? AND role <> 'admin'", [input('active') === '1' ? 1 : 0, $id]);
            flash('Account status updated.');
        } elseif ($action === 'reset') {
            $account = $conn->execute_query('SELECT u.id FROM users u JOIN password_resets r ON r.user_id = u.id WHERE u.id = ? AND u.is_active = 1', [$id])->fetch_assoc();
            if (!$account) { throw new InvalidArgumentException('No active reset request found.'); }
            $token = bin2hex(random_bytes(32));
            $conn->execute_query('UPDATE password_resets SET token_hash = ?, expires_at = NOW() + INTERVAL 30 MINUTE WHERE user_id = ?', [hash('sha256', $token), $id]);
            $_SESSION['reset_link'] = 'reset_password.php?token=' . $token;
            flash('Reset link created. Share it privately with the verified account owner. It expires in 30 minutes.');
        } elseif ($action === 'paper' || $action === 'project') {
            $decision = input('decision');
            if (!in_array($decision, ['approved', 'rejected'], true)) { throw new InvalidArgumentException('Invalid review decision.'); }
            $conn->begin_transaction();
            if ($action === 'paper') {
                $item = $conn->execute_query("SELECT user_id FROM papers WHERE id = ? AND status = 'pending' FOR UPDATE", [$id])->fetch_assoc();
                if (!$item) { throw new InvalidArgumentException('This paper is no longer awaiting review.'); }
                $conn->execute_query('UPDATE papers SET status = ? WHERE id = ?', [$decision, $id]);
            } else {
                $item = $conn->execute_query("SELECT user_id FROM projects WHERE id = ? AND approval = 'pending' FOR UPDATE", [$id])->fetch_assoc();
                if (!$item) { throw new InvalidArgumentException('This project is no longer awaiting review.'); }
                $conn->execute_query('UPDATE projects SET approval = ? WHERE id = ?', [$decision, $id]);
            }
            notify_user($item['user_id'], 'Your ' . $action . ' was ' . $decision . '.');
            $conn->commit();
            flash(ucfirst($action) . ' ' . $decision . '.');
        } else { throw new InvalidArgumentException('Unknown action.'); }
        go('admin_index.php');
    } catch (Throwable $exception) { $conn->rollback(); $error = page_error($exception); }
}
$accounts = $conn->query('SELECT id, full_name, email, role, department, is_active, cv_path FROM users ORDER BY id DESC LIMIT 100')->fetch_all(MYSQLI_ASSOC);
$papers = $conn->query("SELECT p.*, u.full_name FROM papers p JOIN users u ON u.id = p.user_id WHERE p.status = 'pending' ORDER BY p.id")->fetch_all(MYSQLI_ASSOC);
$projects = $conn->query("SELECT p.*, u.full_name FROM projects p JOIN users u ON u.id = p.user_id WHERE p.approval = 'pending' ORDER BY p.id")->fetch_all(MYSQLI_ASSOC);
$requests = $conn->query('SELECT r.*, u.full_name, u.email FROM password_resets r JOIN users u ON u.id = r.user_id WHERE u.is_active = 1 ORDER BY r.requested_at')->fetch_all(MYSQLI_ASSOC);
$reset_link = $_SESSION['reset_link'] ?? '';
unset($_SESSION['reset_link']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UIU Research Portal - Admin Panel</title>
  <link rel="stylesheet" href="mystyle.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="portal.css"></head>
<body class="portal-page">

  <div class="app-container portal-layout">
    
    <?php require __DIR__ . '/backend/sidebar.php'; ?>

    <main class="main-content"><div class="live-content">
<?php show_message(); ?>

<h1>Admin Control Center</h1><p>Review submissions and manage accounts.</p>
<?php if ($error): ?><p class="notice error"><?= e($error) ?></p><?php endif; ?>
<section class="box" id="add-admin" aria-labelledby="add-admin-title">
    <h2 id="add-admin-title">Add New Admin</h2>
    <form method="post">

        <input type="hidden" name="action" value="create_admin">
        <label class="field">New admin's full name
            <input name="full_name" maxlength="255" value="<?= e(input('full_name')) ?>" required>
        </label>
        <label class="field">New admin's email
            <input type="email" name="email" maxlength="255" value="<?= e(input('email')) ?>" required>
        </label>
        <label class="field">New admin's password
            <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        </label>
        <label class="field">Confirm new password
            <input type="password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
        </label>
        <label class="field">Your current password
            <input type="password" name="current_password" autocomplete="current-password" required>
        </label>
        <button>Create Admin Account</button>
    </form>
</section>
<?php if ($reset_link): ?><div class="box"><h2>One-time password reset link</h2><p><a href="<?= e($reset_link) ?>"><?= e($reset_link) ?></a></p><p class="muted">Copy the link address and share it privately. This link is displayed only once.</p></div><?php endif; ?>
<div class="box row"><a href="#users">Users</a><a href="#papers">Pending papers (<?= count($papers) ?>)</a><a href="#projects">Pending projects (<?= count($projects) ?>)</a><a href="#resets">Password reset requests</a></div>
<h2 id="papers">Pending Papers</h2><?php if (!$papers): ?><p class="box empty">No papers waiting for review.</p><?php endif; ?>
<?php foreach ($papers as $paper): ?><article class="box"><h3><?= e($paper['title']) ?></h3><p>Submitted by <?= e($paper['full_name']) ?></p><p><?= nl2br(e($paper['abstract'])) ?></p><p><a href="download.php?type=paper&amp;id=<?= $paper['id'] ?>">Review PDF</a></p><form method="post"><input type="hidden" name="action" value="paper"><input type="hidden" name="id" value="<?= $paper['id'] ?>"><button name="decision" value="approved">Approve</button> <button class="danger" name="decision" value="rejected">Reject</button></form></article><?php endforeach; ?>
<h2 id="projects">Pending Projects</h2><?php if (!$projects): ?><p class="box empty">No projects waiting for review.</p><?php endif; ?>
<?php foreach ($projects as $project): ?><article class="box"><h3><?= e($project['title']) ?></h3><p>Led by <?= e($project['full_name']) ?></p><p><?= nl2br(e($project['description'])) ?></p><form method="post"><input type="hidden" name="action" value="project"><input type="hidden" name="id" value="<?= $project['id'] ?>"><button name="decision" value="approved">Approve</button> <button class="danger" name="decision" value="rejected">Reject</button></form></article><?php endforeach; ?>
<h2 id="users">Users (latest 100)</h2><?php foreach ($accounts as $account): ?><article class="box"><h3><?= e($account['full_name']) ?></h3><p><?= e($account['email']) ?> · <?= e($account['role']) ?> · <?= e($account['department']) ?></p><p><?= $account['is_active'] ? 'Active' : 'Disabled' ?> · <?php if ($account['cv_path'] !== ''): ?><a href="download.php?type=cv&amp;id=<?= $account['id'] ?>">Review CV</a><?php else: ?>No CV uploaded<?php endif; ?></p>
<?php if ($account['role'] !== 'admin'): ?><form method="post"><input type="hidden" name="action" value="user"><input type="hidden" name="id" value="<?= $account['id'] ?>"><input type="hidden" name="active" value="<?= $account['is_active'] ? 0 : 1 ?>"><button class="secondary"><?= $account['is_active'] ? 'Disable Account' : 'Enable Account' ?></button></form><?php endif; ?></article><?php endforeach; ?>
<h2 id="resets">Password Reset Requests</h2><p>Verify the person's identity before creating a link.</p><?php if (!$requests): ?><p class="box empty">No reset requests.</p><?php endif; ?>
<?php foreach ($requests as $request): ?><article class="box"><h3><?= e($request['full_name']) ?></h3><p><?= e($request['email']) ?></p><p class="muted">Requested <?= e($request['requested_at']) ?></p><form method="post"><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= $request['user_id'] ?>"><button>Identity Verified — Create Reset Link</button></form></article><?php endforeach; ?>
</div></main>

  </div>

</body>
</html>
