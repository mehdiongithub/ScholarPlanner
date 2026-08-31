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
    .form-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 30px;
        max-width: 600px;
        margin: 0 auto;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
</style>

<a href="/admin/employees" class="back-btn">
    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
    <span>Back to Employees List</span>
</a>

<div class="form-card">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 8px 0; color: #1e293b;">Register Employee Account</h1>
    <p style="margin: 0 0 24px 0; color: #64748b; font-size: 0.875rem;">Create new staff access. Credentials will be securely initialized in the system.</p>

    <form action="/admin/employees" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="Enter first name" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Enter last name">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="staffname@scholarmatch.com" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password (min 8 chars)</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Initialize strong password" required minlength="8">
        </div>

        <div class="form-group">
            <label class="form-label" for="role_id">Access Level / Role</label>
            <select name="role_id" id="role_id" class="form-control" required>
                <option value="">-- Choose Role --</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
            <a href="/admin/employees" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
