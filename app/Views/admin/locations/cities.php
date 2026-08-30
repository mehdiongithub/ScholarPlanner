<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .location-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
    }
    @media (max-width: 991px) {
        .location-layout {
            grid-template-columns: 1fr;
        }
    }
    .form-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
    }
    .data-table-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
    }
    .location-nav {
        display: flex;
        gap: 8px;
        border-bottom: 1px solid var(--border-slate-200);
        margin-bottom: 24px;
    }
    .location-nav-link {
        padding: 12px 20px;
        font-weight: 600;
        text-decoration: none;
        color: #64748b;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
    }
    .location-nav-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Location Management</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Geographic Countries, States/Provinces, and Cities lookups.</p>
</div>

<!-- Navigation Tabs -->
<div class="location-nav">
    <a href="/admin/locations/countries" class="location-nav-link">Countries</a>
    <a href="/admin/locations/states" class="location-nav-link">States / Provinces</a>
    <a href="/admin/locations/cities" class="location-nav-link active">Cities</a>
</div>

<div class="location-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add New City</h2>
            <form action="/admin/locations/cities" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">City Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Lahore" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="country_select">Country</label>
                    <select id="country_select" class="form-control" required>
                        <option value="">-- Choose Country --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="state_select">State / Province</label>
                    <select name="state_id" id="state_select" class="form-control" required disabled>
                        <option value="">-- Select Country First --</option>
                    </select>
                </div>

                <button type="submit" id="addCitySubmit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;" disabled>Add City</button>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div>
        <div class="data-table-card">
            <div style="overflow-x: auto;">
                <table class="employees-table">
                    <thead>
                        <tr>
                            <th>City Name</th>
                            <th>Parent State / Province</th>
                            <th>Country</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cities)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 24px;">No cities registered in system.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cities as $ci): ?>
                                <tr>
                                    <td><strong><?= e($ci['name']) ?></strong></td>
                                    <td><?= e($ci['state_name']) ?></td>
                                    <td><?= e($ci['country_name']) ?></td>
                                    <td>
                                        <a href="/admin/locations/cities/<?= $ci['id'] ?>/edit" class="action-link">Edit</a>
                                        <form action="/admin/locations/cities/<?= $ci['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete this city?');" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                            <button type="submit" class="action-link danger" style="background: none; border: none; cursor: pointer; font-family: inherit;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Cascading state selection script
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const submitBtn = document.getElementById('addCitySubmit');

    if (countrySelect && stateSelect) {
        countrySelect.addEventListener('change', function() {
            const countryId = this.value;
            stateSelect.innerHTML = '<option value="">-- Loading States... --</option>';
            stateSelect.disabled = true;
            submitBtn.disabled = true;

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
                        submitBtn.disabled = false;
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
