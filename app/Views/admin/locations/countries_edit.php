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

<a href="/admin/locations/countries" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to Countries</span>
</a>

<div class="form-card">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 8px 0; color: #1e293b;">Edit Country Details</h1>
    <p style="margin: 0 0 24px 0; color: #64748b; font-size: 0.875rem;">Modify metadata properties for geographic listing.</p>

    <form action="/admin/locations/countries/<?= $country['id'] ?>/update" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="name">Country Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= e($country['name']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="iso_code">ISO 2-Letter Code</label>
            <input type="text" name="iso_code" id="iso_code" class="form-control" value="<?= e($country['iso_code']) ?>" required maxlength="2">
        </div>

        <div class="form-group">
            <label class="form-label" for="currency_code">Currency Code</label>
            <input type="text" name="currency_code" id="currency_code" class="form-control" value="<?= e($country['currency_code']) ?>" maxlength="3">
        </div>

        <div class="form-group">
            <label class="form-label" for="dial_code">Dial Code</label>
            <input type="text" name="dial_code" id="dial_code" class="form-control" value="<?= e($country['dial_code']) ?>">
        </div>

        <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
            <a href="/admin/locations/countries" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
