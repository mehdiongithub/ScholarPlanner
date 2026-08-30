<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .welcome-banner {
        background: linear-gradient(135deg, #1e293b, #3b82f6);
        color: #fff;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .welcome-banner h1 {
        font-size: 1.75rem;
        margin: 0 0 8px 0;
        font-weight: 700;
    }
    .welcome-banner p {
        margin: 0;
        opacity: 0.9;
        font-size: 0.9375rem;
    }

    /* Stats Grid */
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    @media (max-width: 480px) {
        .dashboard-stats {
            grid-template-columns: 1fr !important;
        }
    }
    .stat-box {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-info {
        flex-grow: 1;
        min-width: 0;
    }
    .stat-value {
        font-size: 1.375rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 2px;
    }
    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .stat-trend {
        font-size: 0.7rem;
        font-weight: 600;
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 3px;
    }
    .stat-trend.up {
        color: #10b981;
    }

    /* Action Center */
    .action-center-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 30px;
    }
    .action-required-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 16px;
    }
    .action-required-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 18px;
        background-color: var(--bg-slate-50);
        border: 1px solid var(--border-slate-200);
        border-radius: 10px;
        gap: 16px;
        flex-wrap: wrap;
    }
    .action-badge-count {
        background-color: #ef4444;
        color: #fff;
        font-weight: 700;
        font-size: 0.8125rem;
        padding: 4px 10px;
        border-radius: 8px;
    }

    /* Scholarship Overview Cards */
    .status-overview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 16px;
        margin-bottom: 30px;
    }
    .status-overview-item {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 10px;
        padding: 16px;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s;
        display: block;
    }
    .status-overview-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .status-overview-count {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    .status-overview-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
    }

    /* Layout column styling */
    .dashboard-cols {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    @media (max-width: 1200px) {
        .dashboard-cols {
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
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .sch-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8125rem;
    }
    .sch-table th, .sch-table td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .sch-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.75rem;
    }
</style>

<div class="welcome-banner">
    <h1>Admin Dashboard</h1>
    <p>Manage scholarships, students, applications and the ScholarMatch platform.</p>
</div>

<!-- Admin Quick Actions -->
<div class="card" style="margin-bottom: 24px;">
    <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0 0 16px 0;">Quick Actions</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
        <?php if (\App\Services\Auth::hasPermission('scholarships.create')): ?>
            <a href="<?= url('/admin/scholarships/create') ?>" class="btn btn-primary" style="width: auto; display: inline-flex; align-items: center; gap: 8px;">
                <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i> Add Scholarship
            </a>
        <?php endif; ?>

        <?php if (\App\Services\Auth::hasPermission('scholarships.view')): ?>
            <a href="<?= url('/admin/scholarships') ?>" class="btn btn-secondary" style="width: auto; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-slate-200); background: #fff; color: #334155;">
                <i data-lucide="award" style="width: 16px; height: 16px;"></i> Manage Scholarships
            </a>
        <?php endif; ?>

        <?php if (\App\Services\Auth::hasPermission('users.view')): ?>
            <a href="<?= url('/admin/users') ?>" class="btn btn-secondary" style="width: auto; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-slate-200); background: #fff; color: #334155;">
                <i data-lucide="users" style="width: 16px; height: 16px;"></i> Manage Students
            </a>
        <?php endif; ?>

        <?php if (\App\Services\Auth::hasPermission('employees.manage')): ?>
            <a href="<?= url('/admin/employees/create') ?>" class="btn btn-secondary" style="width: auto; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-slate-200); background: #fff; color: #334155;">
                <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i> Add Employee
            </a>
        <?php endif; ?>

        <?php if (\App\Services\Auth::currentUser()['role_name'] === 'admin'): ?>
            <a href="<?= url('/admin/institutions') ?>" class="btn btn-secondary" style="width: auto; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-slate-200); background: #fff; color: #334155;">
                <i data-lucide="building-2" style="width: 16px; height: 16px;"></i> Review Institutions
            </a>
        <?php endif; ?>

        <?php if (\App\Services\Auth::hasPermission('applications.view')): ?>
            <a href="<?= url('/admin/applications') ?>" class="btn btn-secondary" style="width: auto; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-slate-200); background: #fff; color: #334155;">
                <i data-lucide="file-text" style="width: 16px; height: 16px;"></i> View Applications
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Primary KPIs Grid -->
<div class="dashboard-stats">
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #eff6ff; color: #3b82f6;"><i data-lucide="award"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['total_scholarships']) ?></div>
            <div class="stat-label">Total Scholarships</div>
            <div class="stat-trend up">+<?= $stats['new_scholarships_30d'] ?> this month</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #ecfdf5; color: #10b981;"><i data-lucide="globe"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['active_scholarships']) ?></div>
            <div class="stat-label">Published Active</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #fffbeb; color: #d97706;"><i data-lucide="alert-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['pending_scholarships']) ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #fef2f2; color: #ef4444;"><i data-lucide="calendar-x"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['expired_scholarships']) ?></div>
            <div class="stat-label">Expired Listings</div>
        </div>
    </div>
</div>

<div class="dashboard-stats">
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #f0fdf4; color: #22c55e;"><i data-lucide="users"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
            <div class="stat-label">Total Students</div>
            <div class="stat-trend up">+<?= $stats['new_students_30d'] ?> this month</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #eff6ff; color: #2563eb;"><i data-lucide="check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['verified_users']) ?></div>
            <div class="stat-label">Email Verified</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #fffbeb; color: #ea580c;"><i data-lucide="user-x"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['pending_users']) ?></div>
            <div class="stat-label">Email Not Verified</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #fef2f2; color: #ef4444;"><i data-lucide="shield-alert"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['suspended_users']) ?></div>
            <div class="stat-label">Suspended</div>
        </div>
    </div>
</div>

<div class="dashboard-stats" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #faf5ff; color: #a855f7;"><i data-lucide="file-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['total_applications']) ?></div>
            <div class="stat-label">Total Applications</div>
            <div class="stat-trend up">+<?= $stats['new_applications_30d'] ?> this month</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #ecfdf5; color: #047857;"><i data-lucide="credit-card"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($stats['active_subscriptions']) ?></div>
            <div class="stat-label">Active Subscriptions</div>
            <div class="stat-trend up">+<?= $stats['new_subscriptions_30d'] ?> this month</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-icon" style="background-color: #eff6ff; color: #1e40af;"><i data-lucide="dollar-sign"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= e($stats['formatted_revenue']) ?></div>
            <div class="stat-label">Total Revenue Generated</div>
        </div>
    </div>
</div>

<!-- Action required Center -->
<div class="action-center-card">
    <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0;"><i data-lucide="alert-triangle" style="vertical-align: middle; color: #ef4444; margin-right: 6px; display: inline;"></i> ACTION REQUIRED</h2>
    <div class="action-required-list">
        <?php if ($stats['pending_scholarships'] > 0): ?>
            <div class="action-required-item">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="action-badge-count"><?= $stats['pending_scholarships'] ?></span>
                    <span style="font-weight: 600; color: #334155;">Scholarships awaiting review and verification.</span>
                </div>
                <a href="<?= url('/admin/scholarships?status=pending_review') ?>" class="btn btn-secondary btn-sm" style="width: auto;">View Review Board</a>
            </div>
        <?php endif; ?>

        <?php if ($stats['pending_institutions'] > 0): ?>
            <div class="action-required-item">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="action-badge-count"><?= $stats['pending_institutions'] ?></span>
                    <span style="font-weight: 600; color: #334155;">Institutions/Universities awaiting registration approvals.</span>
                </div>
                <a href="<?= url('/admin/institutions') ?>" class="btn btn-secondary btn-sm" style="width: auto;">Approve Partners</a>
            </div>
        <?php endif; ?>

        <?php if ($stats['pending_users'] > 0): ?>
            <div class="action-required-item">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="action-badge-count"><?= $stats['pending_users'] ?></span>
                    <span style="font-weight: 600; color: #334155;">Registered student profiles awaiting email/system verification.</span>
                </div>
                <a href="<?= url('/admin/users') ?>" class="btn btn-secondary btn-sm" style="width: auto;">Verify Students</a>
            </div>
        <?php endif; ?>

        <?php if ($stats['pending_applications'] > 0): ?>
            <div class="action-required-item">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="action-badge-count"><?= $stats['pending_applications'] ?></span>
                    <span style="font-weight: 600; color: #334155;">Submissions requesting administrative audit.</span>
                </div>
                <a href="<?= url('/admin/applications') ?>" class="btn btn-secondary btn-sm" style="width: auto;">Review Applications</a>
            </div>
        <?php endif; ?>

        <?php if ($stats['payment_issues'] > 0): ?>
            <div class="action-required-item">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="action-badge-count"><?= $stats['payment_issues'] ?></span>
                    <span style="font-weight: 600; color: #334155;">Unresolved failed payment gateway transactions.</span>
                </div>
                <a href="<?= url('/admin/payments') ?>" class="btn btn-secondary btn-sm" style="width: auto;">Audit Billing Logs</a>
            </div>
        <?php endif; ?>

        <?php if ($stats['pending_scholarships'] == 0 && $stats['pending_institutions'] == 0 && $stats['pending_users'] == 0 && $stats['pending_applications'] == 0 && $stats['payment_issues'] == 0): ?>
            <div style="text-align: center; color: #166534; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 16px; font-weight: 600; font-size: 0.875rem;">
                ✔ You're all caught up. No action is required.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scholarship Quick Filters -->
<h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 12px; margin-top: 0;">Scholarship Overview Statuses</h3>
<div class="status-overview-grid">
    <a href="<?= url('/admin/scholarships?status=published') ?>" class="status-overview-item">
        <div class="status-overview-count"><?= $stats['active_scholarships'] ?></div>
        <div class="status-overview-label" style="color: #10b981;">Published</div>
    </a>
    <a href="<?= url('/admin/scholarships?status=draft') ?>" class="status-overview-item">
        <div class="status-overview-count"><?= $stats['draft_scholarships'] ?></div>
        <div class="status-overview-label" style="color: #64748b;">Draft</div>
    </a>
    <a href="<?= url('/admin/scholarships?status=pending_review') ?>" class="status-overview-item">
        <div class="status-overview-count"><?= $stats['pending_scholarships'] ?></div>
        <div class="status-overview-label" style="color: #f59e0b;">Pending Review</div>
    </a>
    <a href="<?= url('/admin/scholarships?status=published') ?>&expired=1" class="status-overview-item">
        <div class="status-overview-count"><?= $stats['expired_scholarships'] ?></div>
        <div class="status-overview-label" style="color: #ef4444;">Expired</div>
    </a>
    <a href="<?= url('/admin/scholarships?status=archived') ?>" class="status-overview-item">
        <div class="status-overview-count"><?= $stats['archived_scholarships'] ?></div>
        <div class="status-overview-label" style="color: #475569;">Archived</div>
    </a>
</div>

<!-- Two Column Data Tables -->
<div class="dashboard-cols">
    <!-- Left Column: Recent Scholarships -->
    <div>
        <div class="card">
            <h2 class="card-title">
                <span>Recent Scholarships</span>
                <a href="<?= url('/admin/scholarships') ?>" style="font-size: 0.8125rem; color: var(--primary); text-decoration: none; font-weight: 600;">View All Opportunities &rarr;</a>
            </h2>
            <div style="overflow-x: auto;">
                <table class="sch-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Provider</th>
                            <th>Country</th>
                            <th>Degree Level</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_scholarships)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; color: #94a3b8; padding: 24px;">
                                    <div style="margin-bottom: 12px;">No scholarships have been added yet.</div>
                                    <a href="<?= url('/admin/scholarships/create') ?>" class="btn btn-primary btn-sm" style="display: inline-block; width: auto;">Add Scholarship</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_scholarships as $rs): ?>
                                <?php 
                                    $imgUrl = !empty($rs['cover_image']) ? asset($rs['cover_image']) : asset('assets/images/default-scholarship.svg');
                                    
                                    if (empty($rs['application_deadline'])) {
                                        $deadlineText = 'Rolling';
                                        $deadlineSub = '';
                                        $deadlineColor = '#64748b';
                                    } else {
                                        $deadlineDate = strtotime($rs['application_deadline']);
                                        $deadlineText = date('M d, Y', $deadlineDate);
                                        $today = strtotime(date('Y-m-d'));
                                        $diff = ($deadlineDate - $today) / 86400;
                                        
                                        if ($diff < 0) {
                                            $deadlineSub = 'Expired';
                                            $deadlineColor = '#ef4444';
                                        } elseif ($diff == 0) {
                                            $deadlineSub = 'Closing today';
                                            $deadlineColor = '#ea580c';
                                        } elseif ($diff == 1) {
                                            $deadlineSub = '1 day left';
                                            $deadlineColor = '#ea580c';
                                        } elseif ($diff <= 7) {
                                            $deadlineSub = $diff . ' days left';
                                            $deadlineColor = '#ca8a04';
                                        } else {
                                            $deadlineSub = '';
                                            $deadlineColor = '#64748b';
                                        }
                                    }

                                    $statusClass = 'status-badge ';
                                    $statusText = ucfirst(e($rs['status']));
                                    if ($rs['status'] === 'published') {
                                        if (!empty($rs['application_deadline']) && strtotime($rs['application_deadline']) < strtotime(date('Y-m-d'))) {
                                            $statusClass .= 'suspended';
                                            $statusText = 'Expired';
                                        } else {
                                            $statusClass .= 'active';
                                        }
                                    } elseif ($rs['status'] === 'draft') {
                                        $statusClass .= 'pending';
                                    } else {
                                        $statusClass .= 'suspended';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <img src="<?= $imgUrl ?>" alt="Cover" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong><?= e($rs['title']) ?></strong>
                                    </td>
                                    <td><?= e($rs['provider_name']) ?></td>
                                    <td><?= e($rs['country_name'] ?? 'Global') ?></td>
                                    <td><?= e($rs['study_level'] ?? 'N/A') ?></td>
                                    <td>
                                        <div><?= e($deadlineText) ?></div>
                                        <?php if ($deadlineSub): ?>
                                            <div style="font-size: 0.7rem; font-weight: 600; color: <?= $deadlineColor ?>;"><?= $deadlineSub ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td style="color: #64748b;"><?= date('M d, Y', strtotime($rs['created_at'])) ?></td>
                                    <td>
                                        <a href="<?= url('/admin/scholarships/' . $rs['id'] . '/edit') ?>" class="action-link">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Students Registry -->
        <div class="card">
            <h2 class="card-title">
                <span>Recent Users (Students)</span>
                <a href="<?= url('/admin/users') ?>" style="font-size: 0.8125rem; color: var(--primary); text-decoration: none; font-weight: 600;">View All Students &rarr;</a>
            </h2>
            <div style="overflow-x: auto;">
                <table class="sch-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Verification</th>
                            <th>Profile Complete</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_students)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 16px;">No students registered recently.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_students as $st): ?>
                                <tr>
                                    <td><strong><?= e($st['first_name'] . ' ' . $st['last_name']) ?></strong></td>
                                    <td><?= e($st['email']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $st['email_verified_at'] ? 'active' : 'suspended' ?>"><?= $st['email_verified_at'] ? 'Verified' : 'Pending' ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $pct = (int)($st['completion_percentage'] ?? 0);
                                            $color = '#ea580c';
                                            if ($pct >= 80) {
                                                $color = '#16a34a';
                                            } elseif ($pct >= 40) {
                                                $color = '#ca8a04';
                                            }
                                        ?>
                                        <span style="color: <?= $color ?>; font-weight: 600;"><?= $pct ?>%</span>
                                    </td>
                                    <td style="color: #64748b;"><?= date('M d, Y', strtotime($st['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Audit Logs -->
    <div>
        <div class="card">
            <h2 class="card-title">
                <span>Administrative logs</span>
                <a href="<?= url('/admin/audit-logs') ?>" style="font-size: 0.8125rem; color: var(--primary); text-decoration: none; font-weight: 600;">Full Audit &rarr;</a>
            </h2>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php if (empty($recent_logs)): ?>
                    <div style="text-align: center; color: #94a3b8; padding: 16px;">No recent activity.</div>
                <?php else: ?>
                    <?php foreach ($recent_logs as $log): ?>
                        <?php
                            $actor = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''));
                            if (empty($actor)) {
                                $actor = 'System';
                            }
                            $actionText = ucfirst(str_replace('_', ' ', $log['action']));
                        ?>
                        <div style="border-bottom: 1px solid var(--border-slate-200); padding-bottom: 10px;">
                            <div style="font-weight: 600; color: #334155; font-size: 0.8125rem;"><?= e($actor) ?></div>
                            <div style="margin: 4px 0; font-size: 0.75rem;"><span style="background-color: var(--bg-slate-100); padding: 2px 6px; border-radius: 4px; color: #475569; font-weight: 500; border: 1px solid var(--border-slate-200);"><?= e($actionText) ?></span> on <strong><?= e(ucfirst($log['module'])) ?></strong></div>
                            <div style="font-size: 0.6875rem; color: #64748b;"><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
