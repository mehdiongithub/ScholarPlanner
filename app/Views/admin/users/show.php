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
    .user-profile-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
    }
    @media (max-width: 991px) {
        .user-profile-layout {
            grid-template-columns: 1fr;
        }
    }
    .profile-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        text-align: center;
    }
    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #3b82f6;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        margin: 0 auto 16px auto;
    }
    .profile-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 4px 0;
    }
    .profile-email {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0 0 16px 0;
    }
    .info-list {
        text-align: left;
        margin-top: 24px;
        border-top: 1px solid var(--border-slate-200);
        padding-top: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-size: 0.875rem;
    }
    .info-item {
        display: flex;
        justify-content: space-between;
    }
    .info-label {
        color: #64748b;
    }
    .info-val {
        font-weight: 600;
        color: #1e293b;
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

    .record-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .record-item {
        padding: 16px;
        background-color: var(--bg-slate-50);
        border: 1px solid var(--border-slate-200);
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .record-title {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    .record-sub {
        font-size: 0.75rem;
        color: #64748b;
    }
    .score-badge {
        background-color: #dbeafe;
        color: #1e40af;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.8125rem;
    }
</style>

<a href="/admin/users" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to Students List</span>
</a>

<div class="user-profile-layout">
    <!-- Left Column -->
    <div>
        <div class="profile-card">
            <div class="profile-avatar">
                <?php 
                    $initials = '';
                    if (!empty($targetUser['first_name'])) $initials .= strtoupper($targetUser['first_name'][0]);
                    if (!empty($targetUser['last_name'])) $initials .= strtoupper($targetUser['last_name'][0]);
                    echo $initials ?: 'ST';
                ?>
            </div>
            <h2 class="profile-name"><?= e($targetUser['first_name'] . ' ' . $targetUser['last_name']) ?></h2>
            <p class="profile-email"><?= e($targetUser['email']) ?></p>

            <span class="status-badge <?= e($targetUser['status']) ?>"><?= e($targetUser['status']) ?></span>

            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Email Verification:</span>
                    <span class="info-val"><?= $targetUser['email_verified_at'] ? 'Verified' : 'Unverified' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Active Plan:</span>
                    <span class="info-val"><?= e($subscription['plan_name'] ?? 'Free Guest') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Wizard Completion:</span>
                    <span class="info-val"><?= (int)($profile['profile_completion_percentage'] ?? 0) ?>%</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Registered At:</span>
                    <span class="info-val"><?= date('M d, Y', strtotime($targetUser['created_at'])) ?></span>
                </div>
            </div>

            <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 8px;">
                <a href="/admin/users/<?= $targetUser['id'] ?>/edit" class="btn btn-secondary" style="justify-content: center; width: 100%;">Edit Profile Info</a>
                
                <?php if ($targetUser['status'] !== 'suspended'): ?>
                    <form action="/admin/users/<?= $targetUser['id'] ?>/suspend" method="POST" onsubmit="return confirm('Suspend this student account?');" style="width: 100%;">
                        <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                        <button type="submit" class="btn btn-danger" style="justify-content: center; width: 100%;">Suspend User</button>
                    </form>
                <?php else: ?>
                    <form action="/admin/users/<?= $targetUser['id'] ?>/activate" method="POST" onsubmit="return confirm('Activate this student account?');" style="width: 100%;">
                        <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                        <button type="submit" class="btn btn-primary" style="justify-content: center; width: 100%;">Activate User</button>
                    </form>
                <?php endif; ?>

                <form action="/admin/users/<?= $targetUser['id'] ?>/delete" method="POST" onsubmit="return confirm('Are you sure you want to permanently deactivate this account? Actions cannot be undone.');" style="width: 100%;">
                    <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                    <button type="submit" class="btn btn-secondary" style="justify-content: center; width: 100%; border-color: #fca5a5; color: #ef4444; background: #fff;">Deactivate / Delete</button>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card" style="margin-top: 24px;">
            <h3 class="card-title">Reset Password</h3>
            <form action="/admin/users/<?= $targetUser['id'] ?>/password" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                <div class="form-group">
                    <label class="form-label" for="password">New Password (min 8 chars)</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter new strong password" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Update Password</button>
            </form>
        </div>
    </div>

    <!-- Right Column -->
    <div>
        <!-- Education History -->
        <div class="card">
            <h3 class="card-title"><i data-lucide="book"></i> Education History</h3>
            <div class="record-list">
                <?php if (empty($education)): ?>
                    <div style="color: #64748b; font-style: italic;">No education records added.</div>
                <?php else: ?>
                    <?php foreach ($education as $edu): ?>
                        <div class="record-item">
                            <div>
                                <div class="record-title"><?= e($edu['degree_title']) ?> - <?= e($edu['institution_name'] ?: ($edu['college_name'] ?? $edu['school_name'])) ?></div>
                                <div class="record-sub">
                                    Field of Study: <?= e($edu['field_of_study'] ?? 'Not Specified') ?> | 
                                    <?= e($edu['city_name']) ?>, <?= e($edu['state_name']) ?>, <?= e($edu['country_name']) ?>
                                </div>
                            </div>
                            <div class="score-badge">
                                <?= e($edu['result_type']) ?>: <?= e($edu['obtained_cgpa']) ?> / <?= e($edu['cgpa_scale']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Matching Statistics -->
        <div class="card">
            <h3 class="card-title"><i data-lucide="sparkles"></i> Scholarship Matches (Top 5)</h3>
            <div class="record-list">
                <?php if (empty($matches)): ?>
                    <div style="color: #64748b; font-style: italic;">No active scholarship matches calculated.</div>
                <?php else: ?>
                    <?php foreach (array_slice($matches, 0, 5) as $match): ?>
                        <div class="record-item">
                            <div>
                                <div class="record-title"><a href="/scholarships/<?= e($match['slug']) ?>" target="_blank" style="color: var(--primary); text-decoration: none;"><?= e($match['title']) ?></a></div>
                                <div class="record-sub">Provider: <?= e($match['provider_name']) ?></div>
                            </div>
                            <div class="score-badge" style="background-color: #dcfce7; color: #166534;">
                                Match Score: <?= (int)$match['match_score'] ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Application Trackers -->
        <div class="card">
            <h3 class="card-title"><i data-lucide="file-check"></i> Recent Applications</h3>
            <div class="record-list">
                <?php if (empty($applications)): ?>
                    <div style="color: #64748b; font-style: italic;">No application records found.</div>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <div class="record-item">
                            <div>
                                <div class="record-title"><a href="/scholarships/<?= e($app['slug']) ?>" target="_blank" style="color: var(--primary); text-decoration: none;"><?= e($app['title']) ?></a></div>
                                <div class="record-sub">Applied On: <?= date('M d, Y', strtotime($app['created_at'])) ?></div>
                            </div>
                            <span class="status-badge active" style="text-transform: uppercase; background-color: #dbeafe; color: #1e40af;">
                                <?= e($app['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
