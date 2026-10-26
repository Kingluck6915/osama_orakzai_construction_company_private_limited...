<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$statuses = ['New', 'Reviewing', 'Contacted', 'Meeting Scheduled', 'Proposal', 'Won', 'Lost', 'Closed'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = (string) ($_POST['status'] ?? '');
    $notes = trim((string) ($_POST['internal_notes'] ?? ''));
    if (!$id || !in_array($status, $statuses, true) || mb_strlen($notes) > 5000) {
        $error = 'The inquiry update is invalid. Please check the status and note length.';
    } else {
        $stmt = db()->prepare('UPDATE inquiries SET status = ?, internal_notes = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $notes, $id]);
        flash('Inquiry updated.');
        redirect('/admin/inquiries.php?view=' . $id);
    }
}

$selectedId = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);
$selected = null;
if ($selectedId) {
    $stmt = db()->prepare('SELECT * FROM inquiries WHERE id = ?');
    $stmt->execute([$selectedId]);
    $selected = $stmt->fetch() ?: null;
}
$inquiries = db()->query('SELECT id, name, email, phone, project_type, status, created_at FROM inquiries ORDER BY created_at DESC')->fetchAll();
admin_layout_start('Inquiries');
?>
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> YOUR INBOX</span><h1>Project enquiries</h1><p>Review messages, update their status, and keep private follow-up notes.</p></div></div>
<?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($selected): ?>
    <section class="admin-panel inquiry-detail">
        <div class="admin-panel-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> RECEIVED <?= e(date('M j, Y · g:i a', strtotime($selected['created_at']))) ?></span><h2><?= e($selected['name']) ?></h2></div><a class="text-link" href="<?= e(app_path('/admin/inquiries.php')) ?>">Close <span aria-hidden="true">×</span></a></div>
        <div class="inquiry-contact"><a href="mailto:<?= e($selected['email']) ?>"><?= e($selected['email']) ?></a><?php if ($selected['phone']): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $selected['phone'])) ?>"><?= e($selected['phone']) ?></a><?php endif; ?><span><?= e($selected['project_type']) ?></span></div>
        <div class="inquiry-message"><span class="eyebrow">MESSAGE</span><p><?= nl2br(e($selected['message'])) ?></p></div>
        <form method="post" class="admin-form notes-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
            <label>Status<select name="status"><?php foreach ($statuses as $status): ?><option <?= $selected['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label>
            <label>Internal notes <span>(only visible to admins)</span><textarea name="internal_notes" rows="4" maxlength="5000" placeholder="Private follow-up notes..."><?= e($selected['internal_notes']) ?></textarea></label>
            <button type="submit" class="button button-dark">Save update <span aria-hidden="true">↗</span></button>
        </form>
    </section>
<?php endif; ?>
<section class="admin-panel">
    <div class="admin-panel-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> INBOX</span><h2>All messages <small>(<?= count($inquiries) ?>)</small></h2></div></div>
    <?php if ($inquiries): ?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Contact</th><th>Project type</th><th>Status</th><th>Received</th><th></th></tr></thead><tbody>
        <?php foreach ($inquiries as $inquiry): ?><tr><td><?= e($inquiry['name']) ?><small><?= e($inquiry['email']) ?></small></td><td><?= e($inquiry['project_type']) ?></td><td><span class="status-pill"><?= e($inquiry['status']) ?></span></td><td><?= e(date('M j, Y', strtotime($inquiry['created_at']))) ?></td><td><a href="<?= e(app_path('/admin/inquiries.php?view=' . (int) $inquiry['id'])) ?>">Review</a></td></tr><?php endforeach; ?>
    </tbody></table></div><?php else: ?><p class="admin-empty">No project enquiries have arrived yet.</p><?php endif; ?>
</section>
<?php admin_layout_end(); ?>
