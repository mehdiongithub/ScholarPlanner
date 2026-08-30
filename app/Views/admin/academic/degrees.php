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
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Academic Configurations</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Fields of Study, Degree Levels, and Funding Types.</p>
</div>

<!-- Navigation Tabs -->
<div class="location-nav">
    <a href="/admin/academic/fields" class="location-nav-link">Fields of Study</a>
    <a href="/admin/academic/degrees" class="location-nav-link active">Degree Levels</a>
    <a href="/admin/academic/funding" class="location-nav-link">Funding Types</a>
</div>

<div class="location-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add Degree Level</h2>
            <form action="/admin/academic/degrees" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">Degree level Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Master's" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sort_order">Sort Order / Weight</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" placeholder="e.g. 1" value="0">
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Add Degree Level</button>
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
                            <th>Degree Level Name</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($degrees)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 24px;">No degree levels registered.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($degrees as $d): ?>
                                <tr>
                                    <td><strong><?= e($d['name']) ?></strong></td>
                                    <td><code><?= e($d['sort_order']) ?></code></td>
                                    <td>
                                        <span class="status-badge <?= $d['status'] === 'active' ? 'active' : 'suspended' ?>"><?= e($d['status']) ?></span>
                                    </td>
                                    <td>
                                        <a href="/admin/academic/degrees/<?= $d['id'] ?>/edit" class="action-link">Edit</a>
                                        <form action="/admin/academic/degrees/<?= $d['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete this degree level?');" style="display: inline;">
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
