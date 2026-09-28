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

<?php 
$encId = encode_id($targetUser['id']);
$userDisplayName = trim(($targetUser['first_name'] ?? '') . ' ' . ($targetUser['last_name'] ?? '')) ?: ($targetUser['email'] ?? 'User');
?>

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
            <h2 class="profile-name"><?= e($userDisplayName) ?></h2>
            <p class="profile-email"><?= e($targetUser['email'] ?? '') ?></p>

            <span class="status-badge <?= e($targetUser['status'] ?? 'active') ?>"><?= e(ucfirst($targetUser['status'] ?? 'active')) ?></span>

            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Email Verification:</span>
                    <span class="info-val"><?= !empty($targetUser['email_verified_at']) ? '<span style="color: #16a34a; font-weight: 600;">Verified</span>' : '<span style="color: #94a3b8;">Unverified</span>' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Active Plan:</span>
                    <span class="info-val"><?= e($subscription['plan_name'] ?? 'Free Guest') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Wizard Completion:</span>
                    <span class="info-val"><?= (int)($profile['profile_completion_percentage'] ?? 0) ?>%</span>
                </div>
                <?php if (!empty($targetUser['phone'])): ?>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-val"><?= e($targetUser['phone']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($targetUser['whatsapp_phone'])): ?>
                    <div class="info-item">
                        <span class="info-label">WhatsApp:</span>
                        <span class="info-val"><?= e($targetUser['whatsapp_phone']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($profile['nationality_name'])): ?>
                    <div class="info-item">
                        <span class="info-label">Nationality:</span>
                        <span class="info-val"><?= e($profile['nationality_name']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($profile['residence_name'])): ?>
                    <div class="info-item">
                        <span class="info-label">Country of Residence:</span>
                        <span class="info-val"><?= e($profile['residence_name']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Registered At:</span>
                    <span class="info-val"><?= !empty($targetUser['created_at']) ? date('M d, Y', strtotime($targetUser['created_at'])) : 'N/A' ?></span>
                </div>
                <?php if (!empty($targetUser['last_login_at'])): ?>
                    <div class="info-item">
                        <span class="info-label">Last Login:</span>
                        <span class="info-val"><?= date('M d, Y H:i', strtotime($targetUser['last_login_at'])) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 8px;">
                <a href="/admin/users/<?= e($encId) ?>/edit" class="btn btn-secondary" style="justify-content: center; width: 100%;">Edit Profile Info</a>
                
                <?php if (($targetUser['status'] ?? '') !== 'suspended'): ?>
                    <form action="/admin/users/<?= e($encId) ?>/suspend" method="POST" style="width: 100%;">
                        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                        <button type="button" class="btn btn-danger" style="justify-content: center; width: 100%;" onclick="confirmUserAction(this, 'suspend', <?= json_encode($userDisplayName) ?>)">Suspend User</button>
                    </form>
                <?php else: ?>
                    <form action="/admin/users/<?= e($encId) ?>/activate" method="POST" style="width: 100%;">
                        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                        <button type="button" class="btn btn-primary" style="justify-content: center; width: 100%;" onclick="confirmUserAction(this, 'activate', <?= json_encode($userDisplayName) ?>)">Activate User</button>
                    </form>
                <?php endif; ?>

                <form action="/admin/users/<?= e($encId) ?>/delete" method="POST" style="width: 100%;">
                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                    <button type="button" class="btn btn-secondary" style="justify-content: center; width: 100%; border-color: #fca5a5; color: #ef4444; background: #fff;" onclick="confirmUserAction(this, 'delete', <?= json_encode($userDisplayName) ?>)">Deactivate / Delete</button>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card" style="margin-top: 24px;">
            <h3 class="card-title"><i data-lucide="key" style="width: 18px; height: 18px;"></i> Reset Password</h3>
            <form action="/admin/users/<?= e($encId) ?>/password" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
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
        <!-- Preferences & Study Goals (if available) -->
        <?php if (!empty($preferredCountries) || !empty($preferredFields) || !empty($preferredDegrees) || !empty($profile['preferred_funding_type']) || !empty($profile['ielts_score']) || !empty($profile['toefl_score'])): ?>
            <div class="card">
                <h3 class="card-title"><i data-lucide="compass"></i> Study Goals & Preferences</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 0.875rem;">
                    <?php if (!empty($preferredDegrees)): ?>
                        <div>
                            <span style="color: #64748b; display: block; margin-bottom: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Target Degree:</span>
                            <strong><?= e(implode(', ', $preferredDegrees)) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($preferredCountries)): ?>
                        <div>
                            <span style="color: #64748b; display: block; margin-bottom: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Target Countries:</span>
                            <strong><?= e(implode(', ', $preferredCountries)) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($preferredFields)): ?>
                        <div>
                            <span style="color: #64748b; display: block; margin-bottom: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Fields of Interest:</span>
                            <strong><?= e(implode(', ', $preferredFields)) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['preferred_funding_type'])): ?>
                        <div>
                            <span style="color: #64748b; display: block; margin-bottom: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Funding Needed:</span>
                            <strong><?= e(ucwords(str_replace('_', ' ', $profile['preferred_funding_type']))) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['ielts_score']) || !empty($profile['toefl_score']) || !empty($profile['pte_score']) || !empty($profile['duolingo_score'])): ?>
                        <div>
                            <span style="color: #64748b; display: block; margin-bottom: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">English Test Scores:</span>
                            <strong>
                                <?php 
                                    $scores = [];
                                    if (!empty($profile['ielts_score'])) $scores[] = 'IELTS ' . e($profile['ielts_score']);
                                    if (!empty($profile['toefl_score'])) $scores[] = 'TOEFL ' . e($profile['toefl_score']);
                                    if (!empty($profile['pte_score'])) $scores[] = 'PTE ' . e($profile['pte_score']);
                                    if (!empty($profile['duolingo_score'])) $scores[] = 'Duolingo ' . e($profile['duolingo_score']);
                                    echo implode(' | ', $scores);
                                ?>
                            </strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Education History -->
        <div class="card">
            <h3 class="card-title"><i data-lucide="book"></i> Education History</h3>
            <div class="record-list">
                <?php if (empty($education)): ?>
                    <div style="color: #64748b; font-style: italic;">No education records added.</div>
                <?php else: ?>
                    <?php foreach ($education as $edu): ?>
                        <?php
                            $degreeText = trim((!empty($edu['degree_level']) ? $edu['degree_level'] . ' - ' : '') . ($edu['degree_title'] ?? ''));
                            if (empty($degreeText)) {
                                $degreeText = 'Education Record';
                            }
                            $institution = !empty($edu['institution_name']) ? $edu['institution_name'] : (!empty($edu['institution_lookup_name']) ? $edu['institution_lookup_name'] : 'Institution Not Specified');
                            
                            $locationParts = array_filter([$edu['city_name'] ?? null, $edu['state_name'] ?? null, $edu['country_name'] ?? null]);
                            $locationStr = !empty($locationParts) ? implode(', ', $locationParts) : 'Location not specified';

                            // Score / Result text
                            $scoreLabel = 'Score';
                            $scoreValue = 'N/A';
                            if (!empty($edu['cgpa'])) {
                                $scoreLabel = 'CGPA';
                                $scoreValue = $edu['cgpa'] . (!empty($edu['cgpa_scale']) ? ' / ' . $edu['cgpa_scale'] : '');
                            } elseif (!empty($edu['percentage'])) {
                                $scoreLabel = 'Percentage';
                                $scoreValue = $edu['percentage'] . '%';
                            } elseif (!empty($edu['result_status'])) {
                                $scoreLabel = 'Result';
                                $scoreValue = ucfirst($edu['result_status']);
                            } elseif (!empty($edu['graduation_status'])) {
                                $scoreLabel = 'Status';
                                $scoreValue = ucfirst($edu['graduation_status']);
                            }
                        ?>
                        <div class="record-item">
                            <div>
                                <div class="record-title"><?= e($degreeText) ?> - <?= e($institution) ?></div>
                                <div class="record-sub">
                                    Field of Study: <?= e($edu['field_of_study'] ?? 'Not Specified') ?> | 
                                    <?= e($locationStr) ?>
                                    <?php if (!empty($edu['is_current'])): ?>
                                        <span style="display: inline-block; margin-left: 6px; padding: 2px 6px; font-size: 0.7rem; background: #e0f2fe; color: #0369a1; border-radius: 4px;">In Progress<?= !empty($edu['current_semester']) ? ' (Sem ' . e($edu['current_semester']) . ')' : '' ?></span>
                                    <?php elseif (!empty($edu['passing_year'])): ?>
                                        <span style="display: inline-block; margin-left: 6px; padding: 2px 6px; font-size: 0.7rem; background: #f1f5f9; color: #475569; border-radius: 4px;">Graduated <?= e($edu['passing_year']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="score-badge">
                                <?= e($scoreLabel) ?>: <?= e($scoreValue) ?>
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
                                <div class="record-sub">Applied On: <?= !empty($app['created_at']) ? date('M d, Y', strtotime($app['created_at'])) : 'N/A' ?></div>
                            </div>
                            <span class="status-badge active" style="text-transform: uppercase; background-color: #dbeafe; color: #1e40af;">
                                <?= e($app['status'] ?? 'submitted') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function confirmUserAction(btn, action, userName) {
    var form = btn.closest('form');
    var isDelete = action === 'delete';
    var isSuspend = action === 'suspend';
    var title = isDelete ? 'Deactivate User Account' : (isSuspend ? 'Suspend User Account' : 'Activate User Account');
    var msg = 'Are you sure you want to ' + action + ' account for <strong>"' + adminEscapeHtml(userName) + '"</strong>?';
    var subtext = isDelete ? 'This will permanently deactivate this account. This cannot be undone.' : (isSuspend ? 'The user will be immediately blocked from signing in.' : 'The user will regain access to their account.');
    var btnText = isDelete ? 'Yes, Deactivate' : (isSuspend ? 'Yes, Suspend' : 'Yes, Activate');
    var btnClass = (isDelete || isSuspend) ? 'btn-danger' : 'btn-primary';
    var icon = isDelete ? 'user-x' : (isSuspend ? 'user-minus' : 'user-check');

    adminConfirm({
        title: title,
        message: msg,
        subtext: subtext,
        confirmText: btnText,
        confirmClass: btnClass,
        icon: icon
    }, function() {
        form.submit();
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
