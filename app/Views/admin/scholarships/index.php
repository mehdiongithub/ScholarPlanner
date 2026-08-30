<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .filter-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        align-items: end;
    }
    .data-table-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .sch-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .sch-table th, .sch-table td {
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .sch-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .sch-table tbody tr:hover {
        background-color: #fafafb;
    }
    .thumb-img {
        width: 60px;
        height: 38px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--border-slate-200);
        background-color: #f8fafc;
    }
    
    /* Pagination */
    .pagination-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pagination-links {
        display: flex;
        gap: 6px;
    }
    .page-link {
        padding: 8px 14px;
        background-color: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 6px;
        color: #334155;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .page-link:hover {
        background-color: var(--bg-slate-50);
        border-color: #cbd5e1;
    }
    .page-link.active {
        background-color: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
    .page-link.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Scholarship Registry</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage eligibility rules, cover media, publishing pipelines, and match triggers.</p>
    </div>
    <a href="<?= url('/admin/scholarships/create') ?>" class="btn btn-primary">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
        <span>Add Scholarship</span>
    </a>
</div>

<!-- Filters -->
<div class="filter-card">
    <form method="GET" class="filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search by title, provider..." value="<?= e($search) ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Country</label>
            <select name="country_id" class="form-control">
                <option value="">All Countries</option>
                <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= $countryId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Funding</label>
            <select name="funding_type" class="form-control">
                <option value="">All Funding</option>
                <option value="Fully Funded" <?= $funding === 'Fully Funded' ? 'selected' : '' ?>>Fully Funded</option>
                <option value="Partially Funded" <?= $funding === 'Partially Funded' ? 'selected' : '' ?>>Partially Funded</option>
                <option value="Tuition waiver" <?= $funding === 'Tuition waiver' ? 'selected' : '' ?>>Tuition Waiver</option>
                <option value="Stipend" <?= $funding === 'Stipend' ? 'selected' : '' ?>>Stipend</option>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="pending_review" <?= $status === 'pending_review' ? 'selected' : '' ?>>Pending Review</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="<?= url('/admin/scholarships') ?>" class="btn btn-secondary" style="padding: 10px 14px;" title="Clear Filters">Clear</a>
            <button type="submit" class="btn btn-primary" style="padding: 10px 18px; flex-grow: 1;">Filter</button>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="sch-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Scholarship Details</th>
                    <th>Provider</th>
                    <th>Country</th>
                    <th>Funding</th>
                    <th>Status</th>
                    <th>Deadline</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($scholarships)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 32px;">No scholarship records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($scholarships as $s): ?>
                        <tr>
                            <td>
                                <?php if ($s['cover_image']): ?>
                                    <img src="<?= e(url($s['cover_image'])) ?>" class="thumb-img" alt="Cover">
                                <?php else: ?>
                                    <img src="<?= e(url('/assets/images/default-scholarship.svg')) ?>" class="thumb-img" alt="Default">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($s['title']) ?></strong>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">Slug: <code><?= e($s['slug']) ?></code></div>
                            </td>
                            <td><?= e($s['provider_name']) ?></td>
                            <td><?= e($s['country_name'] ?? 'Multi-Country') ?></td>
                            <td><?= e($s['funding_type']) ?></td>
                            <td>
                                <span class="status-badge <?= $s['status'] === 'published' ? 'active' : ($s['status'] === 'draft' ? 'pending' : 'suspended') ?>"><?= e($s['status']) ?></span>
                            </td>
                            <td>
                                <?= $s['application_deadline'] ? date('M d, Y', strtotime($s['application_deadline'])) : '<span style="color: #94a3b8;">Rolling</span>' ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <a href="<?= url('/scholarships/' . $s['slug']) ?>" target="_blank" class="action-link" title="Preview/View Public Page">View</a>
                                    <a href="<?= url('/admin/scholarships/' . $s['id'] . '/edit') ?>" class="action-link" style="color: var(--primary);" title="Edit Content & Eligibility">Edit</a>
                                    
                                    <form action="<?= url('/admin/scholarships/' . $s['id'] . '/duplicate') ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                        <button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #7c3aed;">Duplicate</button>
                                    </form>

                                    <?php if ($s['status'] !== 'published'): ?>
                                        <form action="<?= url('/admin/scholarships/' . $s['id'] . '/publish') ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                            <button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #16a34a;">Publish</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= url('/admin/scholarships/' . $s['id'] . '/unpublish') ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                            <button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #ca8a04;">Unpublish</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($s['status'] !== 'archived'): ?>
                                        <form action="<?= url('/admin/scholarships/' . $s['id'] . '/archive') ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                            <button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #64748b;">Archive</button>
                                        </form>
                                    <?php endif; ?>

                                    <form action="<?= url('/admin/scholarships/' . $s['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this scholarship?');" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                        <button type="submit" class="action-link danger" style="background:none; border:none; cursor:pointer; font-family:inherit;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-bar">
            <span style="font-size: 0.8125rem; color: #64748b;">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalCount ?> records)</span>
            <div class="pagination-links">
                <a href="?search=<?= urlencode($search) ?>&country_id=<?= urlencode($countryId) ?>&funding_type=<?= urlencode($funding) ?>&status=<?= urlencode($status) ?>&page=<?= $page - 1 ?>" class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">&larr; Previous</a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?search=<?= urlencode($search) ?>&country_id=<?= urlencode($countryId) ?>&funding_type=<?= urlencode($funding) ?>&status=<?= urlencode($status) ?>&page=<?= $i ?>" class="page-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a href="?search=<?= urlencode($search) ?>&country_id=<?= urlencode($countryId) ?>&funding_type=<?= urlencode($funding) ?>&status=<?= urlencode($status) ?>&page=<?= $page + 1 ?>" class="page-link <?= $page >= $totalPages ? 'disabled' : '' ?>">Next &rarr;</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
