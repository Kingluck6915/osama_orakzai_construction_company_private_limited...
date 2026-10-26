<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$counts = [
    'projects' => (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'published' => (int) db()->query('SELECT COUNT(*) FROM projects WHERE published = 1')->fetchColumn(),
    'services' => (int) db()->query('SELECT COUNT(*) FROM services')->fetchColumn(),
    'new_inquiries' => (int) db()->query('SELECT COUNT(*) FROM inquiries WHERE status = "New"')->fetchColumn(),
    'all_inquiries' => (int) db()->query('SELECT COUNT(*) FROM inquiries')->fetchColumn(),
];
$recent = db()->query('SELECT id, name, email, project_type, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 5')->fetchAll();
admin_layout_start('Overview');
?>
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> PRIVATE WORKSPACE</span><h1>Good to see you.</h1><p>Here’s the latest from your website.</p></div><span class="admin-user"><?= e($_SESSION['admin_email'] ?? '') ?></span></div>
<section class="admin-stats" aria-label="Website summary">
    <article><span>PROJECTS</span><strong><?= $counts['projects'] ?></strong><small><?= $counts['published'] ?> published</small></article>
    <article><span>NEW ENQUIRIES</span><strong><?= $counts['new_inquiries'] ?></strong><small>Of <?= $counts['all_inquiries'] ?> total</small></article>
</section>
<section class="admin-panel">
    <div class="admin-panel-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> INBOX</span><h2>Recent enquiries</h2></div><a href="<?= e(app_path('/admin/inquiries.php')) ?>" class="text-link">View all <span aria-hidden="true">↗</span></a></div>
    <?php if ($recent): ?>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Contact</th><th>Project type</th><th>Status</th><th>Received</th></tr></thead><tbody>
        <?php foreach ($recent as $inquiry): ?><tr><td><a href="<?= e(app_path('/admin/inquiries.php?view=' . (int) $inquiry['id'])) ?>"><?= e($inquiry['name']) ?></a><small><?= e($inquiry['email']) ?></small></td><td><?= e($inquiry['project_type']) ?></td><td><span class="status-pill"><?= e($inquiry['status']) ?></span></td><td><?= e(date('M j, Y', strtotime($inquiry['created_at']))) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><p class="admin-empty">No enquiries yet. New messages from the website will appear here.</p><?php endif; ?>
</section>
<section class="admin-quick-links"><a href="<?= e(app_path('/admin/projects.php')) ?>"><span>01</span><div><h3>Manage projects</h3><p>Add work to your public portfolio.</p></div><b aria-hidden="true">↗</b></a><a href="<?= e(app_path('/admin/services.php')) ?>"><span>02</span><div><h3>Manage services</h3><p><?= $counts['services'] ?> service<?= $counts['services'] === 1 ? '' : 's' ?> in your list.</p></div><b aria-hidden="true">↗</b></a><a href="<?= e(app_path('/admin/inquiries.php')) ?>"><span>03</span><div><h3>Review enquiries</h3><p>Follow up and keep your inbox organised.</p></div><b aria-hidden="true">↗</b></a></section>
<?php admin_layout_end(); ?>
