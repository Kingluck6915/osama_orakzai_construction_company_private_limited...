<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$error = '';
$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = db()->prepare('SELECT image_path FROM projects WHERE id = ?');
            $stmt->execute([$id]);
            $imagePath = $stmt->fetchColumn();
            db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
            if ($imagePath && str_starts_with($imagePath, '/uploads/projects/')) {
                $localPath = dirname(__DIR__) . $imagePath;
                if (is_file($localPath)) {
                    unlink($localPath);
                }
            }
            flash('Project removed.');
        }
        redirect('/admin/projects.php');
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $title = trim((string) ($_POST['title'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $summary = trim((string) ($_POST['summary'] ?? ''));
    $sortOrder = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);
    $published = isset($_POST['published']) ? 1 : 0;
    $imagePath = trim((string) ($_POST['current_image'] ?? ''));
    $upload = $_FILES['image'] ?? null;

    if ($title === '' || mb_strlen($title) > 160 || $category === '' || mb_strlen($category) > 100
        || mb_strlen($location) > 160 || $summary === '' || mb_strlen($summary) > 500
        || $sortOrder === false) {
        $error = 'Please complete the title, category, and project summary. Check their lengths too.';
    } elseif ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024) {
            $error = 'The image must be smaller than 5 MB and upload successfully.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($upload['tmp_name']);
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($extensions[$mime])) {
                $error = 'Use a JPG, PNG, or WebP image.';
            } else {
                $directory = dirname(__DIR__) . '/uploads/projects';
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new RuntimeException('Could not create the project image directory.');
                }
                $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $filename)) {
                    throw new RuntimeException('Could not save the uploaded project image.');
                }
                $imagePath = '/uploads/projects/' . $filename;
            }
        }
    }

    if ($error === '') {
        if ($id) {
            $stmt = db()->prepare('UPDATE projects SET title = ?, category = ?, location = ?, summary = ?, image_path = ?, sort_order = ?, published = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$title, $category, $location, $summary, $imagePath ?: null, $sortOrder, $published, $id]);
            flash('Project updated.');
        } else {
            $stmt = db()->prepare('INSERT INTO projects (title, category, location, summary, image_path, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$title, $category, $location, $summary, $imagePath ?: null, $sortOrder, $published]);
            flash('Project added.');
        }
        redirect('/admin/projects.php');
    }

    $editing = [
        'id' => $id,
        'title' => $title,
        'category' => $category,
        'location' => $location,
        'summary' => $summary,
        'image_path' => $imagePath,
        'sort_order' => $sortOrder === false ? 0 : $sortOrder,
        'published' => $published,
    ];
}

$projects = db()->query('SELECT id, title, category, location, image_path, published, sort_order FROM projects ORDER BY sort_order ASC, created_at DESC')->fetchAll();
admin_layout_start('Projects');
?>
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> PORTFOLIO</span><h1>Projects</h1><p>Add and curate the work shown on the public website.</p></div></div>
<?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="admin-panel project-editor">
    <div class="admin-panel-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> <?= $editing ? 'EDIT PROJECT' : 'NEW PROJECT' ?></span><h2><?= $editing ? 'Project details' : 'Add a project' ?></h2></div><?php if ($editing): ?><a class="text-link" href="<?= e(app_path('/admin/projects.php')) ?>">Cancel <span aria-hidden="true">×</span></a><?php endif; ?></div>
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>"><input type="hidden" name="current_image" value="<?= e($editing['image_path'] ?? '') ?>">
        <div class="form-row"><label>Project title<input name="title" required maxlength="160" value="<?= e($editing['title'] ?? '') ?>"></label><label>Category<input name="category" required maxlength="100" placeholder="Residential, Commercial..." value="<?= e($editing['category'] ?? '') ?>"></label></div>
        <div class="form-row"><label>Location <span>(optional)</span><input name="location" maxlength="160" value="<?= e($editing['location'] ?? '') ?>"></label><label>Display order<input name="sort_order" type="number" min="0" max="9999" value="<?= (int) ($editing['sort_order'] ?? 0) ?>"></label></div>
        <label>Short project summary<textarea name="summary" required maxlength="500" rows="3"><?= e($editing['summary'] ?? '') ?></textarea></label>
        <label>Project image <span>(JPG, PNG or WebP · max 5 MB)</span><input name="image" type="file" accept="image/jpeg,image/png,image/webp"><?php if (!empty($editing['image_path'])): ?><small class="current-image">Current image: <?= e(basename($editing['image_path'])) ?></small><?php endif; ?></label>
        <label class="checkbox-label"><input type="checkbox" name="published" value="1" <?= !isset($editing['published']) || (int) $editing['published'] === 1 ? 'checked' : '' ?>> Show this project on the public website</label>
        <button type="submit" class="button button-dark"><?= $editing ? 'Save project' : 'Add project' ?> <span aria-hidden="true">↗</span></button>
    </form>
</section>
<section class="admin-panel">
    <div class="admin-panel-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> PORTFOLIO</span><h2>All projects <small>(<?= count($projects) ?>)</small></h2></div></div>
    <?php if ($projects): ?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Project</th><th>Category</th><th>Visibility</th><th>Order</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($projects as $project): ?><tr><td><?= e($project['title']) ?><?php if ($project['location']): ?><small><?= e($project['location']) ?></small><?php endif; ?></td><td><?= e($project['category']) ?></td><td><span class="status-pill"><?= (int) $project['published'] ? 'Published' : 'Draft' ?></span></td><td><?= (int) $project['sort_order'] ?></td><td class="table-actions"><a href="<?= e(app_path('/admin/projects.php?edit=' . (int) $project['id'])) ?>">Edit</a><form method="post" data-confirm="Remove this project? This cannot be undone."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $project['id'] ?>"><button type="submit">Delete</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div><?php else: ?><p class="admin-empty">There are no projects yet. Add the first project above.</p><?php endif; ?>
</section>
<?php admin_layout_end(); ?>
