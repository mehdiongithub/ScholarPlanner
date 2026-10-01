<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .card {
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: var(--radius-2xl);
        padding: clamp(20px, 4vw, 32px);
        box-shadow: var(--shadow-sm);
        margin-bottom: 32px;
    }
    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-900);
        margin-bottom: 24px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 12px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .form-group.full-width {
        grid-column: span 2;
    }
    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-700);
    }
    .form-control {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        width: 100%;
        background: var(--bg-white);
    }
    .form-control:focus {
        border-color: var(--primary);
        outline: none;
    }
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        padding: 8px 0;
    }
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.875rem;
        color: var(--text-700);
        cursor: pointer;
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
        background: var(--primary-dark);
    }
    .btn-secondary {
        background: var(--bg-white);
        color: var(--text-700);
        border: 1px solid var(--border);
    }
    .btn-secondary:hover {
        background: #f1f5f9;
    }
    .btn-danger {
        background: #ef4444;
        color: var(--bg-white);
    }
    .btn-danger:hover {
        background: #dc2626;
    }
    .alert {
        padding: 16px;
        border-radius: var(--radius-lg);
        margin-bottom: 24px;
        font-size: 0.875rem;
    }
    .alert-danger {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    .dynamic-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }
    .dynamic-table th, .dynamic-table td {
        padding: 8px;
        border: 1px solid var(--border);
        text-align: left;
    }
    .dynamic-table th {
        background: #f8fafc;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-600);
        text-transform: uppercase;
    }
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .form-group.full-width {
            grid-column: span 1;
        }
    }
    .status-badge.draft {
        background-color: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .status-badge.published {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .status-badge.archived {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
</style>

<?php
$recordId = encode_id((int)$scholarship['id']);
$schStatus = strtolower($scholarship['status'] ?? 'draft');
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <a href="<?= url('/admin/scholarships') ?>" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i>
        <span>Back to List</span>
    </a>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <span class="status-badge <?= e($schStatus) ?>" style="padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.8125rem; text-transform: uppercase;">
            Status: <?= e(ucfirst($schStatus)) ?>
        </span>

        <?php if ($schStatus !== 'published'): ?>
            <form action="<?= url('/admin/scholarships/' . $recordId . '/publish') ?>" method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <button type="submit" class="btn" style="background: #16a34a; color: #fff; font-weight: 600;">
                    <i data-lucide="globe" style="width: 15px; height: 15px;"></i> Publish
                </button>
            </form>
        <?php else: ?>
            <form action="<?= url('/admin/scholarships/' . $recordId . '/unpublish') ?>" method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <button type="submit" class="btn" style="background: #ca8a04; color: #fff; font-weight: 600;">
                    <i data-lucide="pause-circle" style="width: 15px; height: 15px;"></i> Unpublish (Draft)
                </button>
            </form>
        <?php endif; ?>

        <?php if ($schStatus !== 'archived'): ?>
            <form action="<?= url('/admin/scholarships/' . $recordId . '/archive') ?>" method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <button type="submit" class="btn btn-secondary" style="font-weight: 600; color: #475569;">
                    <i data-lucide="archive" style="width: 15px; height: 15px;"></i> Archive
                </button>
            </form>
        <?php endif; ?>

        <?php if ($schStatus === 'draft'): ?>
            <form action="<?= url('/admin/scholarships/' . $recordId . '/delete') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this draft scholarship?');">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <button type="submit" class="btn btn-danger" style="font-weight: 600;">
                    <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i> Delete
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

            <?php
            $errors = $_SESSION['scholarship_errors'] ?? [];
            $old = $_SESSION['scholarship_old'] ?? [];
            unset($_SESSION['scholarship_errors'], $_SESSION['scholarship_old']);
            
            // Default to saved record values if not redirecting with old input
            $titleVal = $old['title'] ?? $scholarship['title'];
            $providerVal = $old['provider_name'] ?? $scholarship['provider_name'];
            $providerTypeVal = $old['provider_type'] ?? $scholarship['provider_type'];
            $shortDescVal = $old['short_description'] ?? $scholarship['short_description'];
            $descVal = $old['description'] ?? $scholarship['description'];
            $websiteVal = $old['official_website'] ?? $scholarship['official_website'];
            $appUrlVal = $old['official_application_url'] ?? $scholarship['official_application_url'];
            $countryVal = $old['country_id'] ?? $scholarship['country_id'];
            $fundingVal = $old['funding_type'] ?? $scholarship['funding_type'];
            
            $openDateVal = $old['application_open_date'] ?? $scholarship['application_open_date'];
            $deadlineDateVal = $old['application_deadline'] ?? $scholarship['application_deadline'];
            $deadlineTypeVal = $old['deadline_type'] ?? ($scholarship['deadline_type'] ?? 'single');
            $recurringVal = $old['recurring_interval'] ?? $scholarship['recurring_interval'];
            $featuredVal = $old['is_featured'] ?? $scholarship['is_featured'];
            $qualityVal = $old['quality_status'] ?? $scholarship['quality_status'];

            $degreesVal = $old['preferred_degrees'] ?? $selectedDegrees;
            $fieldsVal = $old['preferred_fields'] ?? $selectedFields;
            $countriesVal = $old['preferred_countries'] ?? $selectedCountries;
            $nationalitiesVal = $old['eligible_nationalities'] ?? $selectedNationalities;
            $docsVal = $old['required_documents'] ?? $selectedDocs;
            $statesVal = $old['target_states'] ?? ($selectedStates ?? []);
            $institutionsVal = $old['target_institutions'] ?? ($selectedInstitutions ?? []);

            $minAgeVal = $old['minimum_age'] ?? ($rules['minimum_age'] ?? '');
            $maxAgeVal = $old['maximum_age'] ?? ($rules['maximum_age'] ?? '');
            $minCgpaVal = $old['minimum_cgpa'] ?? ($rules['minimum_cgpa'] ?? '');
            $cgpaScaleVal = $old['cgpa_scale'] ?? ($rules['cgpa_scale'] ?? '');
            $minPctVal = $old['minimum_percentage'] ?? ($rules['minimum_percentage'] ?? '');
            $genderReqVal = $old['gender_requirement'] ?? ($rules['gender_requirement'] ?? '');

            $sourceNameVal = $old['source_name'] ?? ($source['source_name'] ?? ($scholarship['provider_name'] ?? ''));
            $sourceUrlVal = $old['source_url'] ?? ($source['source_url'] ?? ($scholarship['official_website'] ?? ''));
            ?>

            <?php if (!empty($errors['system'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($errors['system']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors['duplicate'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($errors['duplicate']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors['csrf'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($errors['csrf']) ?>
                </div>
            <?php endif; ?>

            <form id="scholarshipEditForm" action="<?= url('/admin/scholarships/' . $scholarship['id'] . '/update') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">

                <!-- Section 1: Basic Information -->
                <div class="card">
                    <h2 class="card-title">1. Basic Information</h2>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label" for="title">Scholarship Title *</label>
                            <input type="text" id="title" name="title" class="form-control" value="<?= e($titleVal) ?>" required>
                            <?php if (!empty($errors['title'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['title']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label" for="cover_image">Scholarship Cover Image</label>
                            <input type="file" id="cover_image" name="cover_image" class="form-control" accept="image/*" onchange="previewImage(event)">
                            <?php if (!empty($errors['cover_image'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['cover_image']) ?></span><?php endif; ?>
                            
                            <input type="hidden" name="remove_cover_image" id="remove_cover_image" value="0">
                            
                            <?php
                                $hasCover = !empty($scholarship['cover_image']);
                                $coverSrc = $hasCover ? url($scholarship['cover_image']) : '';
                            ?>
                            <div id="imagePreviewContainer" style="margin-top: 12px; position: relative; width: fit-content; <?= $hasCover ? '' : 'display: none;' ?>">
                                <img id="imagePreview" src="<?= e($coverSrc) ?>" style="max-width: 320px; max-height: 180px; border-radius: 8px; border: 1px solid var(--border-slate-200); object-fit: cover;">
                                <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: 8px; right: 8px; width: auto; padding: 4px 8px;" onclick="removeSelectedImage()">Remove Image</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="provider_name">Provider / Organization *</label>
                            <input type="text" id="provider_name" name="provider_name" class="form-control" value="<?= e($providerVal) ?>" required>
                            <?php if (!empty($errors['provider_name'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['provider_name']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="provider_type">Provider Type</label>
                            <select id="provider_type" name="provider_type" class="form-control">
                                <option value="">Select Type</option>
                                <option value="government" <?= $providerTypeVal === 'government' ? 'selected' : '' ?>>Government</option>
                                <option value="university" <?= $providerTypeVal === 'university' ? 'selected' : '' ?>>University</option>
                                <option value="ngo" <?= $providerTypeVal === 'ngo' ? 'selected' : '' ?>>NGO / Foundation</option>
                                <option value="private" <?= $providerTypeVal === 'private' ? 'selected' : '' ?>>Private Corporation</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label" for="short_description">Short Description (Max 500 chars)</label>
                            <input type="text" id="short_description" name="short_description" class="form-control" value="<?= e($shortDescVal) ?>">
                            <?php if (!empty($errors['short_description'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['short_description']) ?></span><?php endif; ?>
                        </div>
                        <!-- Include WordPress-style TinyMCE Editor -->
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

                        <div class="form-group full-width">
                            <label class="form-label" for="description">
                                <span>Full Description <span style="color: #ef4444;">*</span></span>
                                <span style="font-weight: normal; color: #64748b; font-size: 0.8125rem; margin-left: 8px;">(WordPress-style Rich Text Editor &bull; Supports Headings, Lists, Tables, Media & HTML)</span>
                            </label>
                            <textarea id="description" name="description" class="form-control" style="min-height: 480px; width: 100%;"><?= e($descVal) ?></textarea>
                            <?php if (!empty($errors['description'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['description']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="official_website">Official Website URL</label>
                            <input type="url" id="official_website" name="official_website" class="form-control" value="<?= e($websiteVal) ?>">
                            <?php if (!empty($errors['official_website'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['official_website']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="official_application_url">Official Application URL</label>
                            <input type="url" id="official_application_url" name="official_application_url" class="form-control" value="<?= e($appUrlVal) ?>">
                            <?php if (!empty($errors['official_application_url'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['official_application_url']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="country_id">Primary Host Country</label>
                            <select id="country_id" name="country_id" class="form-control">
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?= e($c['id']) ?>" <?= $countryVal == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="funding_type">Funding Type</label>
                            <select id="funding_type" name="funding_type" class="form-control">
                                <?php foreach ($fundings as $f): ?>
                                    <option value="<?= e($f['name']) ?>" <?= $fundingVal === $f['name'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Study Criteria & normalization -->
                <div class="card">
                    <h2 class="card-title">2. Scope & Target Preferences</h2>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Target Degree Levels (Select all that apply)</label>
                        <div class="checkbox-group">
                            <?php foreach ($degrees as $d): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="preferred_degrees[]" value="<?= e($d['name']) ?>" <?= in_array($d['name'], $degreesVal) ? 'checked' : '' ?>>
                                    <span><?= e($d['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <label class="form-label" style="margin-bottom: 0;">Disciplines / Fields of Study</label>
                                <span id="matricInterFieldsNote" style="display: none; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 4px;">
                                    Optional for Matric / Intermediate
                                </span>
                            </div>
                            <label class="checkbox-label" style="font-size: 0.8125rem; font-weight: 600; color: var(--primary); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; margin: 0; user-select: none;">
                                <input type="checkbox" id="toggleAllFields">
                                <span id="toggleAllFieldsLabel">Select All</span>
                            </label>
                        </div>
                        <div class="checkbox-group" id="fieldsCheckboxGroup">
                            <?php foreach ($fields as $f): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="preferred_fields[]" class="field-checkbox" value="<?= e($f['id']) ?>" <?= in_array($f['id'], $fieldsVal) ? 'checked' : '' ?>>
                                    <span><?= e($f['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Supported Study Countries (Many-to-Many)</label>
                        <div class="checkbox-group">
                            <?php foreach ($countries as $c): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="preferred_countries[]" value="<?= e($c['id']) ?>" <?= in_array($c['id'], $countriesVal) ? 'checked' : '' ?>>
                                    <span><?= e($c['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Target Provinces / States (Leave empty for all provinces / Pakistan-wide)</label>
                        <div class="checkbox-group">
                            <?php foreach ($states as $st): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="target_states[]" value="<?= e($st['id']) ?>" <?= in_array($st['id'], $statesVal) ? 'checked' : '' ?>>
                                    <span><?= e($st['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Target Specific Institutions (Leave empty for all schools/colleges/universities)</label>
                        <div class="checkbox-group" style="max-height: 200px; overflow-y: auto; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">
                            <?php foreach ($institutions as $inst): ?>
                                <label class="checkbox-label" style="margin-bottom: 6px;">
                                    <input type="checkbox" name="target_institutions[]" value="<?= e($inst['id']) ?>" <?= in_array($inst['id'], $institutionsVal) ? 'checked' : '' ?>>
                                    <span><?= e($inst['name']) ?> <small style="color: #64748b;">(<?= e(ucfirst($inst['institution_type'])) ?>)</small></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Eligibility Rules -->
                <div class="card">
                    <h2 class="card-title">3. Eligibility Criteria</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="minimum_age">Minimum Age</label>
                            <input type="number" id="minimum_age" name="minimum_age" class="form-control" value="<?= e($minAgeVal) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="maximum_age">Maximum Age</label>
                            <input type="number" id="maximum_age" name="maximum_age" class="form-control" value="<?= e($maxAgeVal) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="minimum_cgpa">Minimum CGPA Required</label>
                            <input type="number" step="0.01" id="minimum_cgpa" name="minimum_cgpa" class="form-control" value="<?= e($minCgpaVal) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cgpa_scale">CGPA Scale</label>
                            <input type="number" step="0.1" id="cgpa_scale" name="cgpa_scale" class="form-control" value="<?= e($cgpaScaleVal) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="minimum_percentage">Minimum Percentage (%)</label>
                            <input type="number" step="0.1" id="minimum_percentage" name="minimum_percentage" class="form-control" value="<?= e($minPctVal) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="gender_requirement">Gender Restriction</label>
                            <select id="gender_requirement" name="gender_requirement" class="form-control">
                                <option value="">No Restriction</option>
                                <option value="male" <?= $genderReqVal === 'male' ? 'selected' : '' ?>>Male Only</option>
                                <option value="female" <?= $genderReqVal === 'female' ? 'selected' : '' ?>>Female Only</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label">Eligible Student Nationalities (Leave blank if open to ALL Nationalities)</label>
                            <div class="checkbox-group">
                                <?php foreach ($countries as $c): ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="eligible_nationalities[]" value="<?= e($c['id']) ?>" <?= in_array($c['id'], $nationalitiesVal) ? 'checked' : '' ?>>
                                        <span><?= e($c['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Benefits, Documents & Deadlines -->
                <div class="card">
                    <h2 class="card-title">4. Structured Benefits & Deadlines</h2>
                    
                    <h3 style="font-size:0.95rem; font-weight:600; color:var(--text-800); margin-bottom:12px;">Add Benefits</h3>
                    <table class="dynamic-table" id="benefitsTable">
                        <thead>
                            <tr>
                                <th>Benefit Type</th>
                                <th>Brief title</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($benefits)): ?>
                                <tr>
                                    <td>
                                        <select name="benefit_type[]" class="form-control">
                                            <option value="Tuition coverage">Tuition coverage</option>
                                            <option value="Monthly stipend">Monthly stipend</option>
                                            <option value="Annual stipend">Annual stipend</option>
                                            <option value="Accommodation">Accommodation</option>
                                            <option value="Airfare">Airfare</option>
                                            <option value="Visa support">Visa support</option>
                                            <option value="Health insurance">Health insurance</option>
                                            <option value="Books">Books</option>
                                            <option value="Living allowance">Living allowance</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="benefit_title[]" class="form-control" placeholder="e.g. 100% Tuition Fee Waiver"></td>
                                    <td><input type="number" step="0.01" name="benefit_amount[]" class="form-control" placeholder="e.g. 15000"></td>
                                    <td><input type="text" name="benefit_currency[]" class="form-control" value="EUR"></td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();">Remove</button></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($benefits as $b): ?>
                                    <tr>
                                        <td>
                                            <select name="benefit_type[]" class="form-control">
                                                <option value="Tuition coverage" <?= $b['benefit_type'] === 'Tuition coverage' ? 'selected' : '' ?>>Tuition coverage</option>
                                                <option value="Monthly stipend" <?= $b['benefit_type'] === 'Monthly stipend' ? 'selected' : '' ?>>Monthly stipend</option>
                                                <option value="Annual stipend" <?= $b['benefit_type'] === 'Annual stipend' ? 'selected' : '' ?>>Annual stipend</option>
                                                <option value="Accommodation" <?= $b['benefit_type'] === 'Accommodation' ? 'selected' : '' ?>>Accommodation</option>
                                                <option value="Airfare" <?= $b['benefit_type'] === 'Airfare' ? 'selected' : '' ?>>Airfare</option>
                                                <option value="Visa support" <?= $b['benefit_type'] === 'Visa support' ? 'selected' : '' ?>>Visa support</option>
                                                <option value="Health insurance" <?= $b['benefit_type'] === 'Health insurance' ? 'selected' : '' ?>>Health insurance</option>
                                                <option value="Books" <?= $b['benefit_type'] === 'Books' ? 'selected' : '' ?>>Books</option>
                                                <option value="Living allowance" <?= $b['benefit_type'] === 'Living allowance' ? 'selected' : '' ?>>Living allowance</option>
                                                <option value="Other" <?= $b['benefit_type'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="benefit_title[]" class="form-control" value="<?= e($b['title']) ?>"></td>
                                        <td><input type="number" step="0.01" name="benefit_amount[]" class="form-control" value="<?= e($b['amount'] ?? '') ?>"></td>
                                        <td><input type="text" name="benefit_currency[]" class="form-control" value="<?= e($b['currency']) ?>"></td>
                                        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();">Remove</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-secondary btn-sm" style="margin-bottom:24px;" onclick="addBenefitRow();">Add Benefit Row</button>

                    <h3 style="font-size:0.95rem; font-weight:600; color:var(--text-800); margin-bottom:12px;">Add Optional Language Test Criteria</h3>
                    <table class="dynamic-table" id="langsTable">
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>Minimum Score</th>
                                <th>Required?</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($languages)): ?>
                                <tr>
                                    <td><input type="text" name="lang_test_name[]" class="form-control" placeholder="e.g. IELTS"></td>
                                    <td><input type="text" name="lang_min_score[]" class="form-control" placeholder="e.g. 6.5"></td>
                                    <td>
                                        <input type="checkbox" name="lang_is_required[0]" value="1" checked>
                                    </td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();">Remove</button></td>
                                </tr>
                            <?php else: ?>
                                <?php $lIdx = 0; foreach ($languages as $l): ?>
                                    <tr>
                                        <td><input type="text" name="lang_test_name[]" class="form-control" value="<?= e($l['test_name']) ?>"></td>
                                        <td><input type="text" name="lang_min_score[]" class="form-control" value="<?= e($l['minimum_score']) ?>"></td>
                                        <td>
                                            <input type="checkbox" name="lang_is_required[<?= $lIdx ?>]" value="1" <?= $l['is_required'] ? 'checked' : '' ?>>
                                        </td>
                                        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();">Remove</button></td>
                                    </tr>
                                <?php $lIdx++; endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-secondary btn-sm" style="margin-bottom:24px;" onclick="addLangRow();">Add Language Row</button>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label class="form-label">Required Documents Checklists</label>
                        <div class="checkbox-group">
                            <?php foreach ($documents as $d): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="required_documents[]" value="<?= e($d['id']) ?>" <?= in_array($d['id'], $docsVal) ? 'checked' : '' ?>>
                                    <span><?= e($d['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="application_open_date">Opening Date</label>
                            <input type="date" id="application_open_date" name="application_open_date" class="form-control" value="<?= e($openDateVal) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="application_deadline">Closing Date</label>
                            <input type="date" id="application_deadline" name="application_deadline" class="form-control" value="<?= e($deadlineDateVal) ?>">
                            <?php if (!empty($errors['application_deadline'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['application_deadline']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="deadline_type">Deadline Type</label>
                            <select id="deadline_type" name="deadline_type" class="form-control">
                                <option value="single" <?= $deadlineTypeVal === 'single' ? 'selected' : '' ?>>Single Fixed Date</option>
                                <option value="rolling" <?= $deadlineTypeVal === 'rolling' ? 'selected' : '' ?>>Rolling Admissions</option>
                                <option value="multiple_rounds" <?= $deadlineTypeVal === 'multiple_rounds' ? 'selected' : '' ?>>Multiple Rounds</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="recurring_interval">Is Recurring?</label>
                            <select id="recurring_interval" name="recurring_interval" class="form-control">
                                <option value="non-recurring" <?= $recurringVal === 'non-recurring' ? 'selected' : '' ?>>Non-Recurring</option>
                                <option value="annual" <?= $recurringVal === 'annual' ? 'selected' : '' ?>>Annual Recurrence</option>
                                <option value="biannual" <?= $recurringVal === 'biannual' ? 'selected' : '' ?>>Biannual Recurrence</option>
                                <option value="monthly" <?= $recurringVal === 'monthly' ? 'selected' : '' ?>>Monthly Recurrence</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Source Verification -->
                <div class="card">
                    <h2 class="card-title">5. Source Verification Details</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="source_name">Official Source Name</label>
                            <input type="text" id="source_name" name="source_name" class="form-control" value="<?= e($sourceNameVal) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="source_url">Official Source URL Link</label>
                            <input type="url" id="source_url" name="source_url" class="form-control" value="<?= e($sourceUrlVal) ?>">
                            <?php if (!empty($errors['source_url'])): ?><span style="color:#ef4444; font-size:0.75rem;"><?= e($errors['source_url']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="quality_status">Quality Tier</label>
                            <select id="quality_status" name="quality_status" class="form-control">
                                <option value="good" <?= $qualityVal === 'good' ? 'selected' : '' ?>>Good</option>
                                <option value="excellent" <?= $qualityVal === 'excellent' ? 'selected' : '' ?>>Excellent (Official Government)</option>
                                <option value="poor" <?= $qualityVal === 'poor' ? 'selected' : '' ?>>Needs Review</option>
                            </select>
                        </div>
                        <div class="form-group" style="justify-content: flex-end; padding-bottom: 8px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_featured" value="1" <?= $featuredVal == 1 ? 'checked' : '' ?>>
                                <strong>Mark as Featured Scholarship</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submission Actions -->
                <div style="display:flex; justify-content:flex-end; gap:16px; margin-bottom: 40px;">
                    <a href="<?= url('/admin/scholarships') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </main>
    </div>

    <script>
        lucide.createIcons();

        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    document.getElementById('imagePreviewContainer').style.display = 'block';
                    document.getElementById('remove_cover_image').value = '0';
                };
                reader.readAsDataURL(file);
            }
        }

        function removeSelectedImage() {
            const fileInput = document.getElementById('cover_image');
            fileInput.value = '';
            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('remove_cover_image').value = '1';
        }

        function addBenefitRow() {
            const tableBody = document.querySelector('#benefitsTable tbody');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <select name="benefit_type[]" class="form-control">
                        <option value="Tuition coverage">Tuition coverage</option>
                        <option value="Monthly stipend">Monthly stipend</option>
                        <option value="Annual stipend">Annual stipend</option>
                        <option value="Accommodation">Accommodation</option>
                        <option value="Airfare">Airfare</option>
                        <option value="Visa support">Visa support</option>
                        <option value="Health insurance">Health insurance</option>
                        <option value="Books">Books</option>
                        <option value="Living allowance">Living allowance</option>
                        <option value="Other">Other</option>
                    </select>
                </td>
                <td><input type="text" name="benefit_title[]" class="form-control" placeholder="e.g. 100% Tuition Fee Waiver"></td>
                <td><input type="number" step="0.01" name="benefit_amount[]" class="form-control" placeholder="e.g. 15000"></td>
                <td><input type="text" name="benefit_currency[]" class="form-control" placeholder="e.g. EUR" value="EUR"></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();">Remove</button></td>
            `;
            tableBody.appendChild(row);
        }

        let langIndex = <?= count($languages) ?: 1 ?>;
        function addLangRow() {
            const tableBody = document.querySelector('#langsTable tbody');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="lang_test_name[]" class="form-control" placeholder="e.g. IELTS"></td>
                <td><input type="text" name="lang_min_score[]" class="form-control" placeholder="e.g. 6.5"></td>
                <td>
                    <input type="checkbox" name="lang_is_required[\${langIndex}]" value="1" checked>
                </td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();">Remove</button></td>
            `;
            tableBody.appendChild(row);
            langIndex++;
        }

        // Initialize WordPress-style TinyMCE Editor safely
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#description',
                height: 540,
                menubar: 'file edit view insert format tools table help',
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'directionality'
                ],
                toolbar: 'undo redo | blocks fontfamily fontsize | ' +
                         'bold italic underline strikethrough | forecolor backcolor | ' +
                         'alignleft aligncenter alignright alignjustify | ' +
                         'bullist numlist outdent indent | table link image media hr | ' +
                         'blockquote code removeformat | fullscreen code preview',
                toolbar_mode: 'sliding',
                branding: false,
                promotion: false,
                content_style: `
                    body {
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        font-size: 15px;
                        line-height: 1.75;
                        color: #334155;
                        padding: 16px 20px;
                    }
                    h1, h2, h3, h4, h5, h6 {
                        color: #0f172a;
                        font-weight: 700;
                        line-height: 1.35;
                        margin-top: 1.5em;
                        margin-bottom: 0.5em;
                    }
                    h1 { font-size: 1.75em; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
                    h2 { font-size: 1.4em; border-left: 4px solid #2563eb; padding-left: 12px; }
                    h3 { font-size: 1.2em; }
                    p { margin-bottom: 1.25em; line-height: 1.75; }
                    ul, ol { padding-left: 24px; margin-bottom: 1.25em; }
                    ul { list-style-type: disc !important; }
                    ol { list-style-type: decimal !important; }
                    li { margin-bottom: 0.5em; line-height: 1.7; }
                    blockquote {
                        border-left: 4px solid #2563eb;
                        background: #f8fafc;
                        padding: 14px 18px;
                        margin: 1.5em 0;
                        border-radius: 0 8px 8px 0;
                        font-style: italic;
                        color: #1e293b;
                    }
                    table {
                        border-collapse: separate;
                        border-spacing: 0;
                        width: 100%;
                        margin: 1.5em 0;
                        border: 1px solid #cbd5e1;
                        border-radius: 6px;
                        overflow: hidden;
                    }
                    table th, table td {
                        border: 1px solid #cbd5e1;
                        padding: 10px 14px;
                        text-align: left;
                    }
                    table th {
                        background: #f1f5f9;
                        font-weight: 700;
                        color: #0f172a;
                    }
                    code {
                        background: #f1f5f9;
                        color: #0f172a;
                        padding: 2px 6px;
                        border-radius: 4px;
                        font-size: 0.875em;
                    }
                    pre {
                        background: #0f172a;
                        color: #f8fafc;
                        padding: 14px 18px;
                        border-radius: 8px;
                        overflow-x: auto;
                    }
                    img {
                        max-width: 100%;
                        height: auto;
                        border-radius: 8px;
                    }
                    hr {
                        border: none;
                        border-top: 1px solid #e2e8f0;
                        margin: 2em 0;
                    }
                `,
                setup: function (editor) {
                    editor.on('change keyup input paste Undo Redo', function () {
                        editor.save();
                    });
                }
            });
        }

        // Form submit sync for TinyMCE
        var scholarshipForm = document.getElementById('scholarshipEditForm') || document.querySelector('form');
        if (scholarshipForm) {
            scholarshipForm.addEventListener('submit', function() {
                if (typeof tinymce !== 'undefined' && tinymce.get('description')) {
                    tinymce.get('description').save();
                }
            });
        }

        // Toggle All Disciplines / Fields of Study
        (function() {
            var toggleAllFields = document.getElementById('toggleAllFields');
            var toggleAllFieldsLabel = document.getElementById('toggleAllFieldsLabel');
            var fieldCheckboxes = document.querySelectorAll('.field-checkbox');

            function updateToggleAllState() {
                if (!toggleAllFields || fieldCheckboxes.length === 0) return;
                var total = fieldCheckboxes.length;
                var checkedCount = document.querySelectorAll('.field-checkbox:checked').length;

                if (checkedCount === total) {
                    toggleAllFields.checked = true;
                    toggleAllFields.indeterminate = false;
                    if (toggleAllFieldsLabel) toggleAllFieldsLabel.textContent = 'Remove All';
                } else if (checkedCount === 0) {
                    toggleAllFields.checked = false;
                    toggleAllFields.indeterminate = false;
                    if (toggleAllFieldsLabel) toggleAllFieldsLabel.textContent = 'Select All';
                } else {
                    toggleAllFields.checked = false;
                    toggleAllFields.indeterminate = true;
                    if (toggleAllFieldsLabel) toggleAllFieldsLabel.textContent = 'Select All';
                }
            }

            if (toggleAllFields) {
                toggleAllFields.addEventListener('change', function() {
                    var shouldCheck = this.checked;
                    fieldCheckboxes.forEach(function(cb) {
                        cb.checked = shouldCheck;
                    });
                    if (toggleAllFieldsLabel) {
                        toggleAllFieldsLabel.textContent = shouldCheck ? 'Remove All' : 'Select All';
                    }
                });

                fieldCheckboxes.forEach(function(cb) {
                    cb.addEventListener('change', updateToggleAllState);
                });

                updateToggleAllState();
            }

            // Target Degree Levels: Matric / Intermediate handler
            function checkMatricIntermediateSelected() {
                var matricInterNote = document.getElementById('matricInterFieldsNote');
                if (!matricInterNote) return;

                var hasMatricOrInter = false;
                document.querySelectorAll('input[name="preferred_degrees[]"]:checked').forEach(function(cb) {
                    var val = (cb.value || '').toLowerCase();
                    if (val.indexOf('matric') !== -1 || val.indexOf('intermediate') !== -1) {
                        hasMatricOrInter = true;
                    }
                });

                matricInterNote.style.display = hasMatricOrInter ? 'inline-block' : 'none';
            }

            document.querySelectorAll('input[name="preferred_degrees[]"]').forEach(function(cb) {
                cb.addEventListener('change', checkMatricIntermediateSelected);
            });
            checkMatricIntermediateSelected();
        })();
    </script>
<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
