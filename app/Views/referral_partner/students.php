<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_header.php'; ?>

<style>
    .card {
        background: white;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 28px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin-top: 0;
        margin-bottom: 16px;
    }
    .students-table {
        width: 100%;
        border-collapse: collapse;
    }
    .students-table th, .students-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .students-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-active { background: #d1fae5; color: #065f46; }
    .badge-inactive { background: #f1f5f9; color: #475569; }
    
    .pagination-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
    }
    .pagination-links {
        display: flex;
        gap: 6px;
    }
    .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 8px;
        border: 1px solid var(--border-slate-200);
        border-radius: 6px;
        color: #475569;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .page-link:hover {
        background-color: var(--bg-slate-50);
        color: var(--primary);
    }
    .page-link.active {
        background-color: var(--primary);
        border-color: var(--primary);
        color: white;
    }
    .page-link.disabled {
        color: #94a3b8;
        pointer-events: none;
        background-color: #fafafa;
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Attributed Referred Students</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">A privacy-compliant log of all students who registered using your referral link.</p>
</div>

<div class="card">
    <div style="overflow-x: auto;">
        <?php if (empty($students)): ?>
            <p style="text-align: center; color: #64748b; padding: 40px 0; margin: 0; font-size: 0.875rem;">
                No students registered under your referral code yet.
            </p>
        <?php else: ?>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Registration Date</th>
                        <th>Subscription Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <strong style="color: #0f172a;"><?= e($s['display_name']) ?></strong>
                            </td>
                            <td style="color: #475569; font-size: 0.875rem;">
                                <?= e(date('M d, Y', strtotime($s['created_at']))) ?>
                            </td>
                            <td>
                                <?php $badgeClass = $s['sub_status'] === 'Active' ? 'badge-active' : 'badge-inactive'; ?>
                                <span class="badge <?= $badgeClass ?>"><?= e($s['sub_status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Basic Pagination Controls -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-bar">
                    <span style="font-size: 0.875rem; color: #64748b;">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $per_page, $total_items) ?> of <?= $total_items ?> students
                    </span>
                    <div class="pagination-links">
                        <a href="?page=<?= max(1, $current_page - 1) ?>" class="page-link <?= $current_page <= 1 ? 'disabled' : '' ?>">Prev</a>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-link <?= $current_page === $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?= min($total_pages, $current_page + 1) ?>" class="page-link <?= $current_page >= $total_pages ? 'disabled' : '' ?>">Next</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_footer.php'; ?>
