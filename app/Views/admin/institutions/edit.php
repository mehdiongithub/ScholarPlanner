<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Institution | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .admin-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .admin-header {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo-box {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-900);
            font-weight: 700;
        }
        .logo-box i {
            color: var(--primary);
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-link {
            font-size: 0.875rem;
            color: var(--text-600);
            text-decoration: none;
            font-weight: 500;
        }
        .nav-link:hover {
            color: var(--primary);
        }
        .admin-content {
            max-width: 800px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 24px;
        }
        .card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(24px, 5vw, 40px);
            box-shadow: var(--shadow-sm);
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-800);
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            font-size: 0.9375rem;
            color: var(--text-900);
            background: var(--bg-white);
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            margin-bottom: 20px;
            line-height: 1.5;
            border: 1px solid transparent;
        }
        .alert-danger {
            background: #fef2f2;
            border-color: #fee2e2;
            color: #b91c1c;
        }
        .btn-group {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 32px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: var(--radius-lg);
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--primary);
            color: var(--bg-white);
        }
        .btn-primary:hover {
            background: var(--primary-dark, #1d4ed8);
        }
        .btn-secondary {
            background: var(--bg-white);
            color: var(--text-700);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: #f1f5f9;
        }
        .select2-container .select2-selection--single,
        .select2-container .select2-selection--multiple {
            min-height: 42px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            color: var(--text-900);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        @media (max-width: 280px) {
            .admin-header {
                padding: 10px;
            }
            .btn-group {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <header class="admin-header" role="banner">
            <a href="/admin" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch Admin</span>
            </a>
            
            <nav class="nav-links" role="navigation">
                <a href="/admin" class="nav-link">Dashboard</a>
                <a href="/admin/scholarships" class="nav-link">Scholarships</a>
                <a href="/admin/institutions" class="nav-link" style="color: var(--primary); font-weight: 600;">Institutions</a>
                <a href="/admin/notifications" class="nav-link">Notifications</a>
            </nav>
        </header>

        <main class="admin-content">
            <h1 class="page-title">Edit Institution</h1>

            <?php if (isset($_SESSION['admin_errors'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['admin_errors'] as $err): ?>
                        <p><?= e($err) ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['admin_errors']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form action="/admin/institutions/<?= e($institution['id']) ?>/update" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="form-group">
                        <label for="name" class="form-label">Institution Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= e($institution['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="institution_type" class="form-label">Institution Type</label>
                        <select name="institution_type" id="institution_type" class="form-control" required>
                            <option value="university" <?= $institution['institution_type'] === 'university' ? 'selected' : '' ?>>University</option>
                            <option value="college" <?= $institution['institution_type'] === 'college' ? 'selected' : '' ?>>College</option>
                            <option value="school" <?= $institution['institution_type'] === 'school' ? 'selected' : '' ?>>School</option>
                            <option value="other" <?= $institution['institution_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="approved" <?= $institution['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="pending" <?= $institution['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="rejected" <?= $institution['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="inactive" <?= $institution['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="country_id" class="form-label">Country</label>
                        <select name="country_id" id="country_id" class="form-control select2-el" required>
                            <option value="">-- Select Country --</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= $institution['country_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="coverage_type" class="form-label">Coverage Type</label>
                        <select name="coverage_type" id="coverage_type" class="form-control" required>
                            <option value="state" <?= $institution['coverage_type'] === 'state' ? 'selected' : '' ?>>State Specific</option>
                            <option value="multi_state" <?= $institution['coverage_type'] === 'multi_state' ? 'selected' : '' ?>>Multiple States</option>
                            <option value="national" <?= $institution['coverage_type'] === 'national' ? 'selected' : '' ?>>National</option>
                        </select>
                    </div>

                    <div class="form-group" id="states_group">
                        <label for="states_select" class="form-label" id="states_label">State / Province</label>
                        <select name="states[]" id="states_select" class="form-control" style="width: 100%;">
                            <?php foreach ($states as $s): ?>
                                <option value="<?= e($s['id']) ?>" <?= in_array($s['id'], $selectedStates) || $institution['state_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="city_group">
                        <label for="city_id" class="form-label">City (Optional)</label>
                        <select name="city_id" id="city_id" class="form-control select2-el">
                            <option value="">-- Select City --</option>
                            <?php foreach ($cities as $ci): ?>
                                <option value="<?= e($ci['id']) ?>" <?= $institution['city_id'] == $ci['id'] ? 'selected' : '' ?>><?= e($ci['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="btn-group">
                        <a href="/admin/institutions" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        $(document).ready(function() {
            // Initialize elements
            $('.select2-el').select2({ width: '100%' });

            // Store current loaded states list
            let statesCache = <?= json_encode($states) ?>;

            // Rebuild dropdown to format multi/single state correctly
            rebuildStatesDropdown(true);

            // Event listener for Country selection
            $('#country_id').on('change', function() {
                const countryId = this.value;
                $('#states_select').html('<option value="">-- Loading States --</option>').trigger('change');
                $('#city_id').html('<option value="">-- Select City --</option>').trigger('change');

                if (!countryId) {
                    $('#states_select').html('<option value="">-- Select State --</option>').trigger('change');
                    statesCache = [];
                    return;
                }

                fetch('/api/states?country_id=' + countryId)
                    .then(res => res.json())
                    .then(states => {
                        statesCache = states;
                        rebuildStatesDropdown(false);
                    });
            });

            // Event listener for State selection (only triggers for single state)
            $('#states_select').on('change', function() {
                const coverage = $('#coverage_type').val();
                if (coverage !== 'state') return; // City is only allowed/supported for single state coverage

                const stateId = $(this).val();
                $('#city_id').html('<option value="">-- Loading Cities --</option>').trigger('change');

                if (!stateId) {
                    $('#city_id').html('<option value="">-- Select City --</option>').trigger('change');
                    return;
                }

                fetch('/api/cities?state_id=' + stateId)
                    .then(res => res.json())
                    .then(cities => {
                        let html = '<option value="">-- Select City --</option>';
                        cities.forEach(c => html += `<option value="${c.id}">${c.name}</option>`);
                        $('#city_id').html(html).trigger('change');
                    });
            });

            // Coverage type toggle handler
            $('#coverage_type').on('change', function() {
                rebuildStatesDropdown(false);
            });

            function rebuildStatesDropdown(isInitialLoad) {
                const coverage = $('#coverage_type').val();
                const selected = <?= json_encode($selectedStates) ?>;
                const singleSelected = <?= json_encode($institution['state_id']) ?>;
                
                // Destroy previous Select2 to rebuild
                if ($('#states_select').data('select2')) {
                    $('#states_select').select2('destroy');
                }

                if (coverage === 'national') {
                    $('#states_group').hide();
                    $('#states_select').removeAttr('required').val(null).html('');
                    $('#city_group').hide();
                    $('#city_id').val(null).trigger('change');
                } else if (coverage === 'multi_state') {
                    $('#states_group').show();
                    $('#states_label').text('Coverage States (Multiple)');
                    $('#states_select').attr('multiple', 'multiple').attr('required', 'required');
                    
                    let html = '';
                    statesCache.forEach(s => {
                        const isSel = isInitialLoad && (selected.includes(s.id.toString()) || selected.includes(Number(s.id)) || s.id == singleSelected);
                        html += `<option value="${s.id}" ${isSel ? 'selected' : ''}>${s.name}</option>`;
                    });
                    $('#states_select').html(html).trigger('change');
                    $('#states_select').select2({ width: '100%', placeholder: "Select states/provinces" });
                    
                    // Hide city group for multi-state
                    $('#city_group').hide();
                    if (!isInitialLoad) {
                        $('#city_id').val(null).trigger('change');
                    }
                } else {
                    // state specific
                    $('#states_group').show();
                    $('#states_label').text('State / Province');
                    $('#states_select').removeAttr('multiple').attr('required', 'required');
                    
                    let html = '<option value="">-- Select State --</option>';
                    statesCache.forEach(s => {
                        const isSel = isInitialLoad && (selected.includes(s.id.toString()) || selected.includes(Number(s.id)) || s.id == singleSelected);
                        html += `<option value="${s.id}" ${isSel ? 'selected' : ''}>${s.name}</option>`;
                    });
                    $('#states_select').html(html).trigger('change');
                    $('#states_select').select2({ width: '100%' });
                    
                    $('#city_group').show();
                }
            }
        });
    </script>
</body>
</html>
