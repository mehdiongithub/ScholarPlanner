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
    <a href="/admin/locations/countries" class="location-nav-link active">Countries</a>
    <a href="/admin/locations/states" class="location-nav-link">States / Provinces</a>
    <a href="/admin/locations/cities" class="location-nav-link">Cities</a>
</div>

<div class="location-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add New Country</h2>
            <form action="/admin/locations/countries" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">Country Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Pakistan" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="iso_code">ISO 2-Letter Code</label>
                    <input type="text" name="iso_code" id="iso_code" class="form-control" placeholder="e.g. PK" required maxlength="2">
                </div>

                <div class="form-group">
                    <label class="form-label" for="currency_code">Currency Code</label>
                    <input type="text" name="currency_code" id="currency_code" class="form-control" placeholder="e.g. PKR" maxlength="3">
                </div>

                <div class="form-group">
                    <label class="form-label" for="dial_code">Dial Code</label>
                    <input type="text" name="dial_code" id="dial_code" class="form-control" placeholder="e.g. +92">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Add Country</button>
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
                            <th>Country Name</th>
                            <th>ISO Code</th>
                            <th>Currency</th>
                            <th>Dial Code</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($countries)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 24px;">No countries registered in system.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($countries as $c): ?>
                                <tr>
                                    <td><strong><?= e($c['name']) ?></strong></td>
                                    <td><code><?= e($c['iso_code']) ?></code></td>
                                    <td><?= e($c['currency_code'] ?? '-') ?></td>
                                    <td><?= e($c['dial_code'] ?? '-') ?></td>
                                    <td>
                                        <a href="/admin/locations/countries/<?= $c['id'] ?>/edit" class="action-link">Edit</a>
                                        <form action="/admin/locations/countries/<?= $c['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete this country? This will fail if states are mapped.');" style="display: inline;">
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

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
