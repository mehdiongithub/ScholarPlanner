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
        transition: color 0.15s;
    }
    .back-btn:hover {
        color: var(--primary);
    }
    .form-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 32px;
        max-width: 640px;
        margin: 0 auto;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
</style>

<a href="<?= url('/admin/locations/states') ?>" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to States</span>
</a>

<div class="form-card">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i data-lucide="map-pin" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0; color: #0f172a;">Edit State Details</h1>
            <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.8125rem;">Modify state/province parent country bindings and metadata.</p>
        </div>
    </div>

    <form action="<?= url('/admin/locations/states/' . encode_id((int)$state['id']) . '/update') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="country_id" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                Parent Country <span style="color: #ef4444;">*</span>
            </label>
            <select name="country_id" id="country_id" class="form-control" required style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= ((int)$state['country_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="name" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                State Name <span style="color: #ef4444;">*</span>
            </label>
            <input type="text" name="name" id="name" class="form-control" value="<?= e($state['name']) ?>" required maxlength="100" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="code" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                    State Code
                </label>
                <input type="text" name="code" id="code" class="form-control" value="<?= e($state['code'] ?? '') ?>" maxlength="20" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; text-transform: uppercase;">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="status" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                    Status
                </label>
                <select name="status" id="status" class="form-control" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                    <option value="active" <?= ($state['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                    <option value="inactive" <?= ($state['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <a href="<?= url('/admin/locations/states') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
