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

<a href="<?= url('/admin/locations/countries') ?>" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to Countries</span>
</a>

<div class="form-card">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i data-lucide="globe" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0; color: #0f172a;">Edit Country Details</h1>
            <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.8125rem;">Modify metadata properties and regional settings for geographic listing.</p>
        </div>
    </div>

    <form action="<?= url('/admin/locations/countries/' . encode_id((int)$country['id']) . '/update') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="name" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                Country Name <span style="color: #ef4444;">*</span>
            </label>
            <input type="text" name="name" id="name" class="form-control" value="<?= e($country['name']) ?>" required maxlength="100" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="iso2" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                    ISO 2-Letter Code <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="iso2" id="iso2" class="form-control" value="<?= e($country['iso2'] ?? $country['iso_code'] ?? '') ?>" required maxlength="2" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; text-transform: uppercase;">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="iso3" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                    ISO 3-Letter Code
                </label>
                <input type="text" name="iso3" id="iso3" class="form-control" value="<?= e($country['iso3'] ?? '') ?>" maxlength="3" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; text-transform: uppercase;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="phone_code" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                    Dial / Phone Code <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="phone_code" id="phone_code" class="form-control" value="<?= e($country['phone_code'] ?? $country['dial_code'] ?? '') ?>" required maxlength="16" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="currency_code" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                    Currency Code <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="currency_code" id="currency_code" class="form-control" value="<?= e($country['currency_code'] ?? '') ?>" required maxlength="3" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; text-transform: uppercase;">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="status" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                Status
            </label>
            <select name="status" id="status" class="form-control" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                <option value="active" <?= ($country['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible in scholarship filters and forms)</option>
                <option value="inactive" <?= ($country['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Archived / hidden from active listings)</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <a href="<?= url('/admin/locations/countries') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
