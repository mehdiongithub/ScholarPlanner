<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .stats-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    @media (max-width: 991px) {
        .stats-layout {
            grid-template-columns: 1fr;
        }
    }
    .card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Matching Engine Analytics</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">View aggregates of matches, top matching lists, and zero-match users audit.</p>
    </div>
    <a href="/admin/matching/rules" class="btn btn-secondary">
        <i data-lucide="help-circle" style="width: 16px; height: 16px;"></i>
        <span>Matching Rules Details</span>
    </a>
</div>

<div class="stats-layout">
    <!-- Left Column: Top Matched & Zero Match Users -->
    <div>
        <!-- Top Matched Scholarships -->
        <div class="card">
            <h2 class="card-title"><i data-lucide="award" style="color: var(--primary);"></i> Most Matched Scholarships</h2>
            <div style="overflow-x: auto;">
                <table class="employees-table">
                    <thead>
                        <tr>
                            <th>Scholarship Title</th>
                            <th>Provider</th>
                            <th>Match Count</th>
                            <th>Avg Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mostMatched)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">No matches computed yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mostMatched as $m): ?>
                                <tr>
                                    <td><strong><?= e($m['title']) ?></strong></td>
                                    <td><?= e($m['provider_name']) ?></td>
                                    <td><span class="badge badge-secondary"><?= $m['match_count'] ?> students</span></td>
                                    <td><strong><?= $m['avg_score'] ?>%</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Zero-Match Users Audit -->
        <div class="card">
            <h2 class="card-title"><i data-lucide="alert-triangle" style="color: #ea580c;"></i> Students with 0 matches (Needs Profile Completion)</h2>
            <p style="margin-top: -8px; margin-bottom: 16px; color: #64748b; font-size: 0.8125rem;">Students listed below have zero matches calculated. Check their wizard details to advise them.</p>
            <div style="overflow-x: auto;">
                <table class="employees-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($noMatchUsers)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #166534; padding: 20px;">All registered students have at least one match calculated! Excellent.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($noMatchUsers as $user): ?>
                                <tr>
                                    <td><strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong></td>
                                    <td><?= e($user['email']) ?></td>
                                    <td>
                                        <a href="/admin/users/<?= $user['id'] ?>" class="action-link">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Aggregate Stats -->
    <div>
        <div class="card">
            <h2 class="card-title"><i data-lucide="database" style="color: #3b82f6;"></i> Match Aggregates</h2>
            <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.875rem; margin-top: 12px;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-slate-200); padding-bottom: 8px;">
                    <span style="color: #64748b;">Total Computed Matches:</span>
                    <strong style="color: #1e293b;"><?= number_format($totalMatches) ?></strong>
                </div>
                <p style="margin: 0; font-size: 0.75rem; color: #64748b; line-height: 1.4;">Matches are cached automatically. Running matches diagnostic recalculations on profile edit or wizard submit triggers a background refresh.</p>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
