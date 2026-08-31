<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 24px;
    }
    .back-btn:hover {
        color: var(--primary);
    }
    .form-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 30px;
        max-width: 600px;
        margin: 0 auto;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
</style>

<a href="/admin/academic/degrees" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to Degrees</span>
</a>

<div class="form-card">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 8px 0; color: #1e293b;">Edit Degree Level</h1>
    <p style="margin: 0 0 24px 0; color: #64748b; font-size: 0.875rem;">Modify sort weight and activation statuses.</p>

    <form action="/admin/academic/degrees/<?= $degree['id'] ?>/update" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="name">Degree Level Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= e($degree['name']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="sort_order">Sort Order / Weight</label>
            <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?= e($degree['sort_order']) ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="active" <?= $degree['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $degree['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
            <a href="/admin/academic/degrees" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
