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

<a href="/admin/locations/states" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to States</span>
</a>

<div class="form-card">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 8px 0; color: #1e293b;">Edit State Details</h1>
    <p style="margin: 0 0 24px 0; color: #64748b; font-size: 0.875rem;">Modify state/province parent country bindings.</p>

    <form action="/admin/locations/states/<?= $state['id'] ?>/update" method="POST">
        <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="name">State Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= e($state['name']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="country_id">Parent Country</label>
            <select name="country_id" id="country_id" class="form-control" required>
                <?php foreach ($countries as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $state['country_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
            <a href="/admin/locations/states" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
