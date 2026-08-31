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

<a href="/admin/locations/cities" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to Cities</span>
</a>

<div class="form-card">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 8px 0; color: #1e293b;">Edit City Details</h1>
    <p style="margin: 0 0 24px 0; color: #64748b; font-size: 0.875rem;">Modify city parent state/province bindings dynamically.</p>

    <form action="/admin/locations/cities/<?= $city['id'] ?>/update" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="name">City Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= e($city['name']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="country_select">Country</label>
            <select id="country_select" class="form-control" required>
                <?php foreach ($countries as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $city['country_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="state_select">State / Province</label>
            <select name="state_id" id="state_select" class="form-control" required>
                <?php foreach ($states as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $city['state_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
            <a href="/admin/locations/cities" class="btn btn-secondary">Cancel</a>
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

            fetch('/api/states?country_id=' + countryId)
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
