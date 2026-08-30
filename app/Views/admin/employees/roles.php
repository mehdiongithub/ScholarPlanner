<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .roles-grid {
        display: grid;
        grid-template-columns: 1fr 3fr;
        gap: 30px;
    }
    @media (max-width: 991px) {
        .roles-grid {
            grid-template-columns: 1fr;
        }
    }
    .role-nav-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 20px;
    }
    .role-nav-item {
        display: block;
        padding: 12px 16px;
        color: #475569;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        margin-bottom: 4px;
        transition: all 0.2s;
    }
    .role-nav-item:hover {
        background-color: var(--bg-slate-50);
        color: var(--primary);
    }
    .role-nav-item.active {
        background-color: var(--bg-slate-100);
        color: var(--primary);
    }
    
    .permissions-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
    }
    .permissions-columns {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
        margin-top: 20px;
        margin-bottom: 30px;
    }
    .permission-checkbox-label {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        cursor: pointer;
        padding: 10px;
        border: 1px solid var(--border-slate-200);
        border-radius: 8px;
        font-size: 0.8125rem;
        background-color: var(--bg-slate-50);
        transition: all 0.2s;
    }
    .permission-checkbox-label:hover {
        background-color: #fff;
        border-color: #cbd5e1;
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Access Control & Permissions Mapping</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Configure backoffice staff permissions. Mappings apply globally instantly.</p>
</div>

<div class="roles-grid">
    <!-- Left Role Navigation Column -->
    <div>
        <div class="role-nav-card">
            <h3 style="font-size: 0.875rem; text-transform: uppercase; color: #64748b; margin-top: 0; margin-bottom: 16px; font-weight: 700;">Role Groups</h3>
            <div id="rolesList">
                <?php foreach ($roles as $idx => $r): ?>
                    <a href="javascript:void(0)" onclick="selectRole(<?= $r['id'] ?>, '<?= e($r['name']) ?>')" class="role-nav-item <?= $idx === 0 ? 'active' : '' ?>" id="role-nav-<?= $r['id'] ?>">
                        <?= e($r['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Permissions Config Column -->
    <div>
        <div class="permissions-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 4px;" id="selectedRoleTitle">Configure Permissions</h2>
            <p style="margin: 0; color: #64748b; font-size: 0.8125rem;">Check permissions this group of backoffice staff should have access to.</p>

            <form action="/admin/employees/roles" method="POST" id="permissionsForm">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                <input type="hidden" name="role_id" id="formRoleId" value="">

                <div class="permissions-columns">
                    <?php foreach ($permissions as $p): ?>
                        <label class="permission-checkbox-label">
                            <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" class="perm-checkbox" id="perm-chk-<?= $p['id'] ?>">
                            <div>
                                <strong style="display: block; color: #1e293b;"><?= e($p['name']) ?></strong>
                                <span style="color: #64748b; font-size: 0.75rem;"><?= e($p['description']) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid var(--border-slate-200); padding-top: 20px;">
                    <button type="submit" class="btn btn-primary">Save Access Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // JS Object representation of role-permissions mappings
    const mappings = <?= json_encode($activeMap) ?>;

    function selectRole(roleId, roleName) {
        // Update navigation active class
        document.querySelectorAll('.role-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.getElementById('role-nav-' + roleId).classList.add('active');

        // Update Title & Form ID
        document.getElementById('selectedRoleTitle').innerText = 'Configure Permissions for ' + roleName.toUpperCase();
        document.getElementById('formRoleId').value = roleId;

        // Reset all checkboxes
        document.querySelectorAll('.perm-checkbox').forEach(chk => {
            chk.checked = false;
        });

        // Set mapped checkboxes
        if (mappings[roleId]) {
            mappings[roleId].forEach(pId => {
                const chk = document.getElementById('perm-chk-' + pId);
                if (chk) {
                    chk.checked = true;
                }
            });
        }
    }

    // Auto-select first role on load
    document.addEventListener("DOMContentLoaded", function() {
        const firstRoleLink = document.querySelector('.role-nav-item');
        if (firstRoleLink) {
            firstRoleLink.click();
        }
    });
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
