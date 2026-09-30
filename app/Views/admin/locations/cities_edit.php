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

<a href="<?= url('/admin/locations/cities') ?>" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to Cities</span>
</a>

<div class="form-card">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i data-lucide="building-2" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0; color: #0f172a;">Edit City Details</h1>
            <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.8125rem;">Modify city parent state/province bindings dynamically.</p>
        </div>
    </div>

    <form action="<?= url('/admin/locations/cities/' . encode_id((int)$city['id']) . '/update') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="country_select" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                Country <span style="color: #ef4444;">*</span>
            </label>
            <select id="country_select" class="form-control" required style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= ((int)$city['country_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="state_select" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                State / Province <span style="color: #ef4444;">*</span>
            </label>
            <select name="state_id" id="state_select" class="form-control" required style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                <?php foreach ($states as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= ((int)$city['state_id'] === (int)$s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="name" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                City Name <span style="color: #ef4444;">*</span>
            </label>
            <input type="text" name="name" id="name" class="form-control" value="<?= e($city['name']) ?>" required maxlength="100" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="status" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                Status
            </label>
            <select name="status" id="status" class="form-control" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                <option value="active" <?= ($city['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                <option value="inactive" <?= ($city['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <a href="<?= url('/admin/locations/cities') ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<script>
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');

    if (countrySelect && stateSelect) {
        countrySelect.addEventListener('change', function() {
            const countryId = this.value;
            stateSelect.innerHTML = '<option value="">-- Loading States... --</option>';
            stateSelect.disabled = true;

            if (countryId === '') {
                stateSelect.innerHTML = '<option value="">-- Select Country First --</option>';
                return;
            }

            fetch('<?= url("/api/states") ?>?country_id=' + countryId)
                .then(res => res.json())
                .then(data => {
                    stateSelect.innerHTML = '<option value="">-- Choose State --</option>';
                    if (data && data.length > 0) {
                        data.forEach(state => {
                            const opt = document.createElement('option');
                            opt.value = state.id;
                            opt.innerText = state.name;
                            stateSelect.appendChild(opt);
                        });
                        stateSelect.disabled = false;
                    } else {
                        stateSelect.innerHTML = '<option value="">-- No States Found --</option>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    stateSelect.innerHTML = '<option value="">-- Error Loading States --</option>';
                });
        });
    }
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
