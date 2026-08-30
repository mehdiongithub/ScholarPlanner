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
    <a href="/admin/academic/fields" class="location-nav-link active">Fields of Study</a>
    <a href="/admin/academic/degrees" class="location-nav-link">Degree Levels</a>
    <a href="/admin/academic/funding" class="location-nav-link">Funding Types</a>
</div>

<div class="location-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add Field of Study</h2>
            <form action="/admin/academic/fields" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">Field Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Computer Science" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Brief Description</label>
                    <textarea name="description" id="description" class="form-control" placeholder="Optional description..." style="height: 100px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Add Field</button>
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
                            <th>Field Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fields)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #94a3b8; padding: 24px;">No fields of study registered.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $f): ?>
                                <tr>
                                    <td><strong><?= e($f['name']) ?></strong></td>
                                    <td style="color: #64748b; font-size: 0.8125rem;"><?= e($f['description'] ?? '-') ?></td>
                                    <td>
                                        <a href="/admin/academic/fields/<?= $f['id'] ?>/edit" class="action-link">Edit</a>
                                        <form action="/admin/academic/fields/<?= $f['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete this field of study? This will cascade delete matching records.');" style="display: inline;">
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
