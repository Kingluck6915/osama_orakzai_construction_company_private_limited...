<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$error = '';
$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    if ($action === 'delete') {
        if ($id) {
            db()->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
            flash('Service removed.');
        }
        redirect('/admin/services.php');
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $summary = trim((string) ($_POST['summary'] ?? ''));
    $sortOrder = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);
    $published = isset($_POST['published']) ? 1 : 0;
    if ($title === '' || mb_strlen($title) > 160 || $summary === '' || mb_strlen($summary) > 500 || $sortOrder === false || $sortOrder < 0 || $sortOrder > 9999) {
        $error = 'Enter a title, a summary of up to 500 characters, and a valid display order.';
    } else {
        if ($id) {
            $stmt = db()->prepare('UPDATE services SET title = ?, summary = ?, sort_order = ?, published = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$title, $summary, $sortOrder, $published, $id]);
            flash('Service updated.');
        } else {
            $stmt = db()->prepare('INSERT INTO services (title, summary, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$title, $summary, $sortOrder, $published]);
            flash('Service added.');
        }
        redirect('/admin/services.php');
    }
    $editing = [
        'id' => $id,
        'title' => $title,
        'summary' => $summary,
        'sort_order' => $sortOrder === false ? 0 : $sortOrder,
        'published' => $published,
    ];
}

$services = db()->query('SELECT id, title, summary, sort_order, published FROM services ORDER BY sort_order ASC, created_at ASC')->fetchAll();
admin_layout_start('Services');
?>
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> WHAT WE DO</span><h1>Services</h1><p>Manage the service descriptions shown on the public website.</p></div></div>
<?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="admin-panel project-editor">
    <div class="admin-panel-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> <?= $editing ? 'EDIT SERVICE' : 'NEW SERVICE' ?></span><h2><?= $editing ? 'Service details' : 'Add a service' ?></h2></div><?php if ($editing): ?><a class="text-link" href="<?= e(app_path('/admin/services.php')) ?>">Cancel <span aria-hidden="true">×</span></a><?php endif; ?></div>
    <form method="post" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
        <label>Service title<input name="title" required maxlength="160" value="<?= e($editing['title'] ?? '') ?>"></label>
        <label>Short description<textarea name="summary" required maxlength="500" rows="3"><?= e($editing['summary'] ?? '') ?></textarea></label>
        <label>Display order<input name="sort_order" type="number" min="0" max="9999" value="<?= (int) ($editing['sort_order'] ?? 0) ?>"></label>
        <label class="checkbox-label"><input type="checkbox" name="published" value="1" <?= !isset($editing['published']) || (int) $editing['published'] === 1 ? 'checked' : '' ?>> Show this service on the public website</label>
        <button type="submit" class="button button-dark"><?= $editing ? 'Save service' : 'Add service' ?> <span aria-hidden="true">↗</span></button>
    </form>
</section>
<section class="admin-panel">
    <div class="admin-panel-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> SERVICES</span><h2>All services <small>(<?= count($services) ?>)</small></h2></div></div>
    <?php if ($services): ?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Service</th><th>Visibility</th><th>Order</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($services as $service): ?><tr><td><?= e($service['title']) ?><small><?= e($service['summary']) ?></small></td><td><span class="status-pill"><?= (int) $service['published'] ? 'Published' : 'Draft' ?></span></td><td><?= (int) $service['sort_order'] ?></td><td class="table-actions"><a href="<?= e(app_path('/admin/services.php?edit=' . (int) $service['id'])) ?>">Edit</a><form method="post" data-confirm="Remove this service? This cannot be undone."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><button type="submit">Delete</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div><?php else: ?><p class="admin-empty">No services are listed. Add one above to display it on the public website.</p><?php endif; ?>
</section>
<?php admin_layout_end(); ?>
