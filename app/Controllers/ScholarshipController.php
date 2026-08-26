<?php

namespace App\Controllers;

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;
use App\Services\Logger;
use PDO;
use Exception;

class ScholarshipController {
    
    /**
     * Helper to sanitize rich text descriptions
     */
    private function sanitizeHtml(string $html): string {
        // Preprocess tags with slashes immediately after tag name (e.g. <p/onmouseover)
        $clean = preg_replace('/<([a-z1-6]+)\//i', '<$1 /', $html);
        // Recursively remove script and style tags along with their inner contents
        $prev = '';
        while ($clean !== $prev) {
            $prev = $clean;
            $clean = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $clean);
            $clean = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $clean);
        }
        // Strip all other tags except whitelisted basic formatting elements
        $clean = strip_tags($clean, '<p><br><strong><em><ul><ol><li>');
        // Strip all attributes from whitelisted tags to block any event-handler injection
        $clean = preg_replace('/<([a-z1-6]+)\b[^>]*>/i', '<$1>', $clean);
        return trim($clean);
    }

    /**
     * Helper to generate a unique slug
     */
    private function generateSlug(string $title, ?int $excludeId = null): string {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        if (empty($slug)) {
            $slug = 'scholarship-' . time();
        }
        $db = Database::connection();
        $baseSlug = $slug;
        $count = 1;
        while (true) {
            $sql = "SELECT COUNT(*) FROM scholarships WHERE slug = :slug";
            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
            }
            $stmt = $db->prepare($sql);
            $params = ['slug' => $slug];
            if ($excludeId) {
                $params['exclude_id'] = $excludeId;
            }
            $stmt->execute($params);
            if ((int)$stmt->fetchColumn() === 0) {
                break;
            }
            $slug = $baseSlug . '-' . $count;
            $count++;
        }
        return $slug;
    }

    /**
     * Helper to write audit trail logs
     */
    private function logAudit(string $action, int $scholarshipId, ?array $metadata = null): void {
        try {
            $db = Database::connection();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, module, resource_type, resource_id, ip_address, user_agent, metadata) 
                VALUES (:uid, :action, 'scholarships', 'scholarship', :resource_id, :ip, :ua, :meta)
            ");
            $stmt->execute([
                'uid' => Auth::userId(),
                'action' => $action,
                'resource_id' => $scholarshipId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'meta' => $metadata ? json_encode($metadata) : null
            ]);
        } catch (Exception $e) {
            Logger::error("Audit logging failed: " . $e->getMessage());
        }
    }

    /**
     * GET /admin/scholarships
     * Admin/Employee Dashboard Listing
     */
    public function index(): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.view');

        $db = Database::connection();

        // 1. Inputs
        $search = trim($_GET['search'] ?? '');
        $countryId = !empty($_GET['country_id']) ? (int)$_GET['country_id'] : null;
        $degree = trim($_GET['degree'] ?? '');
        $funding = trim($_GET['funding_type'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $verified = trim($_GET['verified'] ?? '');
        $featured = trim($_GET['featured'] ?? '');

        // Sort params
        $sort = trim($_GET['sort'] ?? 'created_at');
        $direction = strtoupper(trim($_GET['direction'] ?? 'DESC'));
        if (!in_array($sort, ['title', 'provider_name', 'application_deadline', 'status', 'created_at'])) {
            $sort = 'created_at';
        }
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'DESC';
        }

        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // 2. Build Query
        $whereClauses = [];
        $params = [];

        if ($search !== '') {
            $whereClauses[] = "(s.title LIKE :search OR s.provider_name LIKE :search OR s.description LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        if ($countryId !== null) {
            $whereClauses[] = "s.country_id = :country_id";
            $params['country_id'] = $countryId;
        }
        if ($degree !== '') {
            // Check mapping pivot table exists matching degree level
            $whereClauses[] = "EXISTS (SELECT 1 FROM scholarship_degree_levels sdl WHERE sdl.scholarship_id = s.id AND sdl.degree_level = :degree)";
            $params['degree'] = $degree;
        }
        if ($funding !== '') {
            $whereClauses[] = "s.funding_type = :funding";
            $params['funding'] = $funding;
        }
        if ($status !== '') {
            $whereClauses[] = "s.status = :status";
            $params['status'] = $status;
        }
        if ($verified !== '') {
            $whereClauses[] = "s.verification_status = :verified";
            $params['verified'] = $verified;
        }
        if ($featured !== '') {
            $whereClauses[] = "s.is_featured = :featured";
            $params['featured'] = (int)$featured;
        }

        $whereSql = '';
        if (!empty($whereClauses)) {
            $whereSql = "WHERE " . implode(" AND ", $whereClauses);
        }

        // Total count
        $countQuery = "SELECT COUNT(*) FROM scholarships s $whereSql";
        $stmtCount = $db->prepare($countQuery);
        $stmtCount->execute($params);
        $totalCount = (int)$stmtCount->fetchColumn();
        $totalPages = ceil($totalCount / $limit);

        // Fetch records
        $query = "
            SELECT s.*, c.name as country_name 
            FROM scholarships s
            LEFT JOIN countries c ON s.country_id = c.id
            $whereSql
            ORDER BY s.{$sort} $direction
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch dropdown values
        $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        view('admin.scholarships.index', [
            'scholarships' => $scholarships,
            'countries' => $countries,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'search' => $search,
            'countryId' => $countryId,
            'degree' => $degree,
            'funding' => $funding,
            'status' => $status,
            'verified' => $verified,
            'featured' => $featured,
            'sort' => $sort,
            'direction' => $direction
        ]);
    }

    /**
     * GET /admin/scholarships/create
     */
    public function create(): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.create');

        $db = Database::connection();
        $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $fields = $db->query("SELECT id, name FROM fields_of_study ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $documents = $db->query("SELECT id, name FROM documents ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $db->query("SELECT id, name FROM scholarship_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        view('admin.scholarships.create', [
            'countries' => $countries,
            'fields' => $fields,
            'documents' => $documents,
            'categories' => $categories,
            'csrf_token' => Security::csrfToken(),
            'errors' => [],
            'old' => []
        ]);
    }

    /**
     * POST /admin/scholarships
     */
    public function store(): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.create');

        $db = Database::connection();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed.'], $_POST);
        }

        // Whitelist variables
        $title = trim($_POST['title'] ?? '');
        $description = $this->sanitizeHtml($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $providerName = trim($_POST['provider_name'] ?? '');
        $providerType = trim($_POST['provider_type'] ?? '');
        $officialWebsite = trim($_POST['official_website'] ?? '');
        $officialApplicationUrl = trim($_POST['official_application_url'] ?? '');
        $primaryCountryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        $studyLevel = trim($_POST['study_level'] ?? '');
        $fundingType = trim($_POST['funding_type'] ?? '');
        $applicationType = trim($_POST['application_type'] ?? '');
        
        $openDate = trim($_POST['application_open_date'] ?? '');
        $deadlineDate = trim($_POST['application_deadline'] ?? '');
        $deadlineType = trim($_POST['deadline_type'] ?? 'single');
        $recurringInterval = trim($_POST['recurring_interval'] ?? 'non-recurring');
        
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $qualityStatus = trim($_POST['quality_status'] ?? 'good');
        
        $prefFields = (array)($_POST['preferred_fields'] ?? []);
        $prefCountries = (array)($_POST['preferred_countries'] ?? []);
        $prefNationalities = (array)($_POST['eligible_nationalities'] ?? []);
        $prefDegrees = (array)($_POST['preferred_degrees'] ?? []);
        $reqDocs = (array)($_POST['required_documents'] ?? []);

        // Eligibility Rules parameters
        $minAge = !empty($_POST['minimum_age']) ? (int)$_POST['minimum_age'] : null;
        $maxAge = !empty($_POST['maximum_age']) ? (int)$_POST['maximum_age'] : null;
        $minCgpa = !empty($_POST['minimum_cgpa']) ? (float)$_POST['minimum_cgpa'] : null;
        $cgpaScale = !empty($_POST['cgpa_scale']) ? (float)$_POST['cgpa_scale'] : null;
        $minPct = !empty($_POST['minimum_percentage']) ? (float)$_POST['minimum_percentage'] : null;
        $genderReq = trim($_POST['gender_requirement'] ?? '');
        
        // Structured Benefits
        $benefitTypes = (array)($_POST['benefit_type'] ?? []);
        $benefitTitles = (array)($_POST['benefit_title'] ?? []);
        $benefitAmts = (array)($_POST['benefit_amount'] ?? []);
        $benefitCurs = (array)($_POST['benefit_currency'] ?? []);
        
        // Dynamic Languages test
        $langNames = (array)($_POST['lang_test_name'] ?? []);
        $langScores = (array)($_POST['lang_min_score'] ?? []);
        $langRequired = (array)($_POST['lang_is_required'] ?? []);

        // Dynamic Source details
        $sourceName = trim($_POST['source_name'] ?? '');
        $sourceUrl = trim($_POST['source_url'] ?? '');

        // Validations
        $errors = [];
        if (empty($title)) $errors['title'] = 'Scholarship title is required.';
        if (strlen($title) > 200) $errors['title'] = 'Title must not exceed 200 characters.';
        if (empty($providerName)) $errors['provider_name'] = 'Provider organization name is required.';
        if (strlen($providerName) > 150) $errors['provider_name'] = 'Provider name must not exceed 150 characters.';
        if (empty($description)) $errors['description'] = 'Full description is required.';
        if (strlen($shortDescription) > 500) $errors['short_description'] = 'Short description must not exceed 500 characters.';

        // Url schemes
        if (!empty($officialApplicationUrl)) {
            if (!filter_var($officialApplicationUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $officialApplicationUrl)) {
                $errors['official_application_url'] = 'Application URL must start with http:// or https://';
            }
        }
        if (!empty($officialWebsite)) {
            if (!filter_var($officialWebsite, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $officialWebsite)) {
                $errors['official_website'] = 'Official website must start with http:// or https://';
            }
        }
        if (!empty($sourceUrl)) {
            if (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $sourceUrl)) {
                $errors['source_url'] = 'Source URL must start with http:// or https://';
            }
        }

        // Validate CGPA
        if ($minCgpa !== null) {
            if ($minCgpa < 0 || ($cgpaScale !== null && $minCgpa > $cgpaScale)) {
                $errors['minimum_cgpa'] = 'Minimum CGPA must be between 0 and the CGPA scale.';
            }
        }
        if ($cgpaScale !== null && $cgpaScale <= 0) {
            $errors['cgpa_scale'] = 'CGPA scale must be a positive number.';
        }
        if ($minPct !== null && ($minPct < 0 || $minPct > 100)) {
            $errors['minimum_percentage'] = 'Minimum percentage must be between 0 and 100.';
        }

        // Validate Dates
        if (!empty($openDate) && !empty($deadlineDate)) {
            if (strtotime($openDate) > strtotime($deadlineDate)) {
                $errors['application_deadline'] = 'Deadline cannot be before the open date.';
            }
        }

        // Validate whitelisted Degree selection
        $allowedDegrees = ['Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral', 'Diploma', 'Certificate', 'Exchange'];
        foreach ($prefDegrees as $lvl) {
            if (!in_array($lvl, $allowedDegrees)) {
                $errors['preferred_degrees'] = 'Invalid degree level selection.';
            }
        }

        // Validate whitelisted recurring interval selection
        $allowedIntervals = ['non-recurring', 'annual', 'biannual', 'monthly', 'custom'];
        if (!in_array($recurringInterval, $allowedIntervals)) {
            $errors['recurring_interval'] = 'Invalid recurring interval selection.';
        }

        // Deterministic duplicate check
        $stmtCheck = $db->prepare("
            SELECT COUNT(*) FROM scholarships 
            WHERE provider_name = :provider AND title = :title AND official_application_url = :app_url
        ");
        $stmtCheck->execute([
            'provider' => $providerName,
            'title' => $title,
            'app_url' => $officialApplicationUrl
        ]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $errors['duplicate'] = 'A scholarship with the same title, provider, and application URL already exists.';
        }

        if (!empty($errors)) {
            $this->redirectBackWithErrors($errors, $_POST);
        }

        // Save transactions
        try {
            $db->beginTransaction();

            $slug = $this->generateSlug($title);

            // 1. Insert scholarship main record
            $stmt = $db->prepare("
                INSERT INTO scholarships (
                    title, slug, provider_name, provider_type, description, short_description, 
                    official_website, official_application_url, country_id, study_level, funding_type, 
                    application_type, status, verification_status, application_open_date, application_deadline, 
                    is_featured, quality_status, recurring_interval, created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :title, :slug, :provider_name, :provider_type, :description, :short_description, 
                    :official_website, :official_application_url, :country_id, :study_level, :funding_type, 
                    :application_type, 'draft', 'unverified', :open_date, :deadline_date, 
                    :is_featured, :quality_status, :recurring_interval, :created_by, :updated_by, NOW(), NOW()
                )
            ");

            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'provider_name' => $providerName,
                'provider_type' => $providerType ?: null,
                'description' => $description,
                'short_description' => $shortDescription ?: null,
                'official_website' => $officialWebsite ?: null,
                'official_application_url' => $officialApplicationUrl ?: null,
                'country_id' => $primaryCountryId,
                'study_level' => $studyLevel ?: null,
                'funding_type' => $fundingType ?: null,
                'application_type' => $applicationType ?: null,
                'open_date' => $openDate ?: null,
                'deadline_date' => $deadlineDate ?: null,
                'is_featured' => $isFeatured,
                'quality_status' => $qualityStatus,
                'recurring_interval' => $recurringInterval,
                'created_by' => Auth::userId(),
                'updated_by' => Auth::userId()
            ]);

            $scholarshipId = $db->lastInsertId();

            // 2. Save Pivot countries
            $prefCountries = array_unique(array_filter(array_map('intval', $prefCountries)));
            if (!empty($prefCountries)) {
                $stmtCountry = $db->prepare("INSERT INTO scholarship_countries (scholarship_id, country_id) VALUES (:sid, :cid)");
                foreach ($prefCountries as $cId) {
                    $stmtCountry->execute(['sid' => $scholarshipId, 'cid' => $cId]);
                }
            }

            // 3. Save Pivot fields
            $prefFields = array_unique(array_filter(array_map('intval', $prefFields)));
            if (!empty($prefFields)) {
                $stmtField = $db->prepare("INSERT INTO scholarship_fields (scholarship_id, field_of_study_id) VALUES (:sid, :fid)");
                foreach ($prefFields as $fId) {
                    $stmtField->execute(['sid' => $scholarshipId, 'fid' => $fId]);
                }
            }

            // 4. Save Pivot nationalities
            $prefNationalities = array_unique(array_filter(array_map('intval', $prefNationalities)));
            if (!empty($prefNationalities)) {
                $stmtNat = $db->prepare("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES (:sid, :cid)");
                foreach ($prefNationalities as $cId) {
                    $stmtNat->execute(['sid' => $scholarshipId, 'cid' => $cId]);
                }
            }

            // 5. Save Pivot degree levels
            $prefDegrees = array_unique(array_filter(array_map('trim', $prefDegrees)));
            if (!empty($prefDegrees)) {
                $stmtDeg = $db->prepare("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES (:sid, :lvl)");
                foreach ($prefDegrees as $lvl) {
                    $stmtDeg->execute(['sid' => $scholarshipId, 'lvl' => $lvl]);
                }
            }

            // 6. Save Documents
            $reqDocs = array_unique(array_filter(array_map('intval', $reqDocs)));
            if (!empty($reqDocs)) {
                $stmtDoc = $db->prepare("INSERT INTO scholarship_documents (scholarship_id, document_id) VALUES (:sid, :did)");
                foreach ($reqDocs as $dId) {
                    $stmtDoc->execute(['sid' => $scholarshipId, 'did' => $dId]);
                }
            }

            // 7. Save Eligibility rules
            $stmtRules = $db->prepare("
                INSERT INTO scholarship_eligibility_rules (
                    scholarship_id, minimum_age, maximum_age, minimum_cgpa, cgpa_scale, minimum_percentage, 
                    gender_requirement, degree_requirement, created_at, updated_at
                ) VALUES (
                    :sid, :min_age, :max_age, :min_cgpa, :cgpa_scale, :min_pct, :gender, :degree, NOW(), NOW()
                )
            ");
            $stmtRules->execute([
                'sid' => $scholarshipId,
                'min_age' => $minAge,
                'max_age' => $maxAge,
                'min_cgpa' => $minCgpa,
                'cgpa_scale' => $cgpaScale,
                'min_pct' => $minPct,
                'gender' => $genderReq ?: null,
                'degree' => $studyLevel ?: null
            ]);

            // 8. Save Benefits
            if (!empty($benefitTypes)) {
                $stmtBenefit = $db->prepare("
                    INSERT INTO scholarship_benefits (scholarship_id, benefit_type, title, amount, currency, created_at, updated_at) 
                    VALUES (:sid, :type, :title, :amount, :currency, NOW(), NOW())
                ");
                for ($i = 0; $i < count($benefitTypes); $i++) {
                    $type = trim($benefitTypes[$i] ?? '');
                    $bTitle = trim($benefitTitles[$i] ?? '');
                    if (empty($type) || empty($bTitle)) continue;

                    $stmtBenefit->execute([
                        'sid' => $scholarshipId,
                        'type' => $type,
                        'title' => $bTitle,
                        'amount' => !empty($benefitAmts[$i]) ? (float)$benefitAmts[$i] : null,
                        'currency' => !empty($benefitCurs[$i]) ? trim($benefitCurs[$i]) : 'PKR'
                    ]);
                }
            }

            // 9. Save Languages requirements
            if (!empty($langNames)) {
                $stmtLang = $db->prepare("
                    INSERT INTO scholarship_languages (scholarship_id, test_name, minimum_score, is_required) 
                    VALUES (:sid, :name, :score, :req)
                ");
                for ($i = 0; $i < count($langNames); $i++) {
                    $lName = trim($langNames[$i] ?? '');
                    $lScore = trim($langScores[$i] ?? '');
                    if (empty($lName) || empty($lScore)) continue;

                    $stmtLang->execute([
                        'sid' => $scholarshipId,
                        'name' => $lName,
                        'score' => $lScore,
                        'req' => isset($langRequired[$i]) ? 1 : 0
                    ]);
                }
            }

            // 10. Save Deadlines record
            if (!empty($deadlineDate)) {
                $stmtDead = $db->prepare("
                    INSERT INTO scholarship_deadlines (scholarship_id, deadline_type, deadline_date, status, created_at, updated_at) 
                    VALUES (:sid, :type, :date, 'active', NOW(), NOW())
                ");
                $stmtDead->execute([
                    'sid' => $scholarshipId,
                    'type' => $deadlineType,
                    'date' => $deadlineDate
                ]);
            }

            // 11. Save Sources
            if (!empty($sourceName) || !empty($sourceUrl)) {
                $stmtSource = $db->prepare("
                    INSERT INTO scholarship_sources (scholarship_id, source_name, source_url, verification_status, created_at, updated_at) 
                    VALUES (:sid, :name, :url, 'unverified', NOW(), NOW())
                ");
                $stmtSource->execute([
                    'sid' => $scholarshipId,
                    'name' => $sourceName ?: 'Official Website',
                    'url' => $sourceUrl ?: $officialWebsite
                ]);
            }

            $db->commit();
            $this->logAudit('created', $scholarshipId);

            header("Location: " . url('/admin/scholarships?success=Scholarship created successfully.'));
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            Logger::error("Failed to store scholarship: " . $e->getMessage());
            $this->redirectBackWithErrors(['system' => 'Failed to create scholarship. Please verify your inputs.'], $_POST);
        }
    }

    /**
     * GET /admin/scholarships/{id}/edit
     */
    public function edit(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.edit');

        $id = (int)$id;
        $db = Database::connection();

        // 1. Fetch main record
        $stmt = $db->prepare("SELECT * FROM scholarships WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$scholarship) {
            header("Location: " . url('/admin/scholarships?error=Scholarship not found.'));
            exit();
        }

        // 2. Fetch dependencies
        $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $fields = $db->query("SELECT id, name FROM fields_of_study ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $documents = $db->query("SELECT id, name FROM documents ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $db->query("SELECT id, name FROM scholarship_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch selected options
        $selectedFields = $db->query("SELECT field_of_study_id FROM scholarship_fields WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_COLUMN);
        $selectedCountries = $db->query("SELECT country_id FROM scholarship_countries WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_COLUMN);
        $selectedNationalities = $db->query("SELECT country_id FROM scholarship_eligible_nationalities WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_COLUMN);
        $selectedDegrees = $db->query("SELECT degree_level FROM scholarship_degree_levels WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_COLUMN);
        $selectedDocs = $db->query("SELECT document_id FROM scholarship_documents WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_COLUMN);

        // Fetch rules
        $rules = $db->query("SELECT * FROM scholarship_eligibility_rules WHERE scholarship_id = $id")->fetch(PDO::FETCH_ASSOC) ?: [];

        // Fetch benefits
        $benefits = $db->query("SELECT * FROM scholarship_benefits WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch languages
        $languages = $db->query("SELECT * FROM scholarship_languages WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch source details
        $source = $db->query("SELECT * FROM scholarship_sources WHERE scholarship_id = $id")->fetch(PDO::FETCH_ASSOC) ?: [];

        view('admin.scholarships.edit', [
            'scholarship' => $scholarship,
            'countries' => $countries,
            'fields' => $fields,
            'documents' => $documents,
            'categories' => $categories,
            'selectedFields' => $selectedFields,
            'selectedCountries' => $selectedCountries,
            'selectedNationalities' => $selectedNationalities,
            'selectedDegrees' => $selectedDegrees,
            'selectedDocs' => $selectedDocs,
            'rules' => $rules,
            'benefits' => $benefits,
            'languages' => $languages,
            'source' => $source,
            'csrf_token' => Security::csrfToken(),
            'errors' => []
        ]);
    }

    /**
     * POST /admin/scholarships/{id}/update
     */
    public function update(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.edit');

        $id = (int)$id;
        $db = Database::connection();

        // Verify exists
        $stmtExist = $db->prepare("SELECT id, title, slug FROM scholarships WHERE id = :id");
        $stmtExist->execute(['id' => $id]);
        $oldRecord = $stmtExist->fetch();
        if (!$oldRecord) {
            header("Location: " . url('/admin/scholarships?error=Scholarship not found.'));
            exit();
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed.'], $_POST);
        }

        $title = trim($_POST['title'] ?? '');
        $description = $this->sanitizeHtml($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $providerName = trim($_POST['provider_name'] ?? '');
        $providerType = trim($_POST['provider_type'] ?? '');
        $officialWebsite = trim($_POST['official_website'] ?? '');
        $officialApplicationUrl = trim($_POST['official_application_url'] ?? '');
        $primaryCountryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        $studyLevel = trim($_POST['study_level'] ?? '');
        $fundingType = trim($_POST['funding_type'] ?? '');
        $applicationType = trim($_POST['application_type'] ?? '');
        
        $openDate = trim($_POST['application_open_date'] ?? '');
        $deadlineDate = trim($_POST['application_deadline'] ?? '');
        $deadlineType = trim($_POST['deadline_type'] ?? 'single');
        $recurringInterval = trim($_POST['recurring_interval'] ?? 'non-recurring');
        
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $qualityStatus = trim($_POST['quality_status'] ?? 'good');
        
        $prefFields = (array)($_POST['preferred_fields'] ?? []);
        $prefCountries = (array)($_POST['preferred_countries'] ?? []);
        $prefNationalities = (array)($_POST['eligible_nationalities'] ?? []);
        $prefDegrees = (array)($_POST['preferred_degrees'] ?? []);
        $reqDocs = (array)($_POST['required_documents'] ?? []);

        // Eligibility Rules parameters
        $minAge = !empty($_POST['minimum_age']) ? (int)$_POST['minimum_age'] : null;
        $maxAge = !empty($_POST['maximum_age']) ? (int)$_POST['maximum_age'] : null;
        $minCgpa = !empty($_POST['minimum_cgpa']) ? (float)$_POST['minimum_cgpa'] : null;
        $cgpaScale = !empty($_POST['cgpa_scale']) ? (float)$_POST['cgpa_scale'] : null;
        $minPct = !empty($_POST['minimum_percentage']) ? (float)$_POST['minimum_percentage'] : null;
        $genderReq = trim($_POST['gender_requirement'] ?? '');
        
        // Structured Benefits
        $benefitTypes = (array)($_POST['benefit_type'] ?? []);
        $benefitTitles = (array)($_POST['benefit_title'] ?? []);
        $benefitAmts = (array)($_POST['benefit_amount'] ?? []);
        $benefitCurs = (array)($_POST['benefit_currency'] ?? []);
        
        // Dynamic Languages test
        $langNames = (array)($_POST['lang_test_name'] ?? []);
        $langScores = (array)($_POST['lang_min_score'] ?? []);
        $langRequired = (array)($_POST['lang_is_required'] ?? []);

        // Dynamic Source details
        $sourceName = trim($_POST['source_name'] ?? '');
        $sourceUrl = trim($_POST['source_url'] ?? '');

        // Validations
        $errors = [];
        if (empty($title)) $errors['title'] = 'Scholarship title is required.';
        if (strlen($title) > 200) $errors['title'] = 'Title must not exceed 200 characters.';
        if (empty($providerName)) $errors['provider_name'] = 'Provider organization name is required.';
        if (strlen($providerName) > 150) $errors['provider_name'] = 'Provider name must not exceed 150 characters.';
        if (empty($description)) $errors['description'] = 'Full description is required.';
        if (strlen($shortDescription) > 500) $errors['short_description'] = 'Short description must not exceed 500 characters.';

        // Url schemes validation
        if (!empty($officialApplicationUrl)) {
            if (!filter_var($officialApplicationUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $officialApplicationUrl)) {
                $errors['official_application_url'] = 'Application URL must start with http:// or https://';
            }
        }
        if (!empty($officialWebsite)) {
            if (!filter_var($officialWebsite, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $officialWebsite)) {
                $errors['official_website'] = 'Official website must start with http:// or https://';
            }
        }
        if (!empty($sourceUrl)) {
            if (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $sourceUrl)) {
                $errors['source_url'] = 'Source URL must start with http:// or https://';
            }
        }

        // Validate CGPA
        if ($minCgpa !== null) {
            if ($minCgpa < 0 || ($cgpaScale !== null && $minCgpa > $cgpaScale)) {
                $errors['minimum_cgpa'] = 'Minimum CGPA must be between 0 and the CGPA scale.';
            }
        }
        if ($cgpaScale !== null && $cgpaScale <= 0) {
            $errors['cgpa_scale'] = 'CGPA scale must be a positive number.';
        }
        if ($minPct !== null && ($minPct < 0 || $minPct > 100)) {
            $errors['minimum_percentage'] = 'Minimum percentage must be between 0 and 100.';
        }

        // Validate Dates
        if (!empty($openDate) && !empty($deadlineDate)) {
            if (strtotime($openDate) > strtotime($deadlineDate)) {
                $errors['application_deadline'] = 'Deadline cannot be before the open date.';
            }
        }

        // Validate whitelisted Degree selection
        $allowedDegrees = ['Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral', 'Diploma', 'Certificate', 'Exchange'];
        foreach ($prefDegrees as $lvl) {
            if (!in_array($lvl, $allowedDegrees)) {
                $errors['preferred_degrees'] = 'Invalid degree level selection.';
            }
        }

        // Validate whitelisted recurring interval selection
        $allowedIntervals = ['non-recurring', 'annual', 'biannual', 'monthly', 'custom'];
        if (!in_array($recurringInterval, $allowedIntervals)) {
            $errors['recurring_interval'] = 'Invalid recurring interval selection.';
        }

        // Duplicate check
        $stmtCheck = $db->prepare("
            SELECT COUNT(*) FROM scholarships 
            WHERE provider_name = :provider AND title = :title AND official_application_url = :app_url AND id != :id
        ");
        $stmtCheck->execute([
            'provider' => $providerName,
            'title' => $title,
            'app_url' => $officialApplicationUrl,
            'id' => $id
        ]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $errors['duplicate'] = 'A scholarship with the same title, provider, and application URL already exists.';
        }

        if (!empty($errors)) {
            $this->redirectBackWithErrors($errors, $_POST);
        }

        // Process Updates inside transactions
        try {
            $db->beginTransaction();

            // Handle title slug change safely
            $slug = $oldRecord['slug'];
            if ($oldRecord['title'] !== $title) {
                $slug = $this->generateSlug($title, $id);
            }

            // 1. Update main record
            $stmt = $db->prepare("
                UPDATE scholarships 
                SET title = :title, slug = :slug, provider_name = :provider_name, provider_type = :provider_type, 
                    description = :description, short_description = :short_description, official_website = :official_website, 
                    official_application_url = :official_application_url, country_id = :country_id, study_level = :study_level, 
                    funding_type = :funding_type, application_type = :application_type, application_open_date = :open_date, 
                    application_deadline = :deadline_date, is_featured = :is_featured, quality_status = :quality_status, 
                    recurring_interval = :recurring_interval, updated_by = :updated_by, updated_at = NOW() 
                WHERE id = :id
            ");

            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'provider_name' => $providerName,
                'provider_type' => $providerType ?: null,
                'description' => $description,
                'short_description' => $shortDescription ?: null,
                'official_website' => $officialWebsite ?: null,
                'official_application_url' => $officialApplicationUrl ?: null,
                'country_id' => $primaryCountryId,
                'study_level' => $studyLevel ?: null,
                'funding_type' => $fundingType ?: null,
                'application_type' => $applicationType ?: null,
                'open_date' => $openDate ?: null,
                'deadline_date' => $deadlineDate ?: null,
                'is_featured' => $isFeatured,
                'quality_status' => $qualityStatus,
                'recurring_interval' => $recurringInterval,
                'updated_by' => Auth::userId(),
                'id' => $id
            ]);

            // 2. Sync Countries
            $db->prepare("DELETE FROM scholarship_countries WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            $prefCountries = array_unique(array_filter(array_map('intval', $prefCountries)));
            if (!empty($prefCountries)) {
                $stmtCountry = $db->prepare("INSERT INTO scholarship_countries (scholarship_id, country_id) VALUES (:sid, :cid)");
                foreach ($prefCountries as $cId) {
                    $stmtCountry->execute(['sid' => $id, 'cid' => $cId]);
                }
            }

            // 3. Sync Fields
            $db->prepare("DELETE FROM scholarship_fields WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            $prefFields = array_unique(array_filter(array_map('intval', $prefFields)));
            if (!empty($prefFields)) {
                $stmtField = $db->prepare("INSERT INTO scholarship_fields (scholarship_id, field_of_study_id) VALUES (:sid, :fid)");
                foreach ($prefFields as $fId) {
                    $stmtField->execute(['sid' => $id, 'fid' => $fId]);
                }
            }

            // 4. Sync Nationalities
            $db->prepare("DELETE FROM scholarship_eligible_nationalities WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            $prefNationalities = array_unique(array_filter(array_map('intval', $prefNationalities)));
            if (!empty($prefNationalities)) {
                $stmtNat = $db->prepare("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES (:sid, :cid)");
                foreach ($prefNationalities as $cId) {
                    $stmtNat->execute(['sid' => $id, 'cid' => $cId]);
                }
            }

            // 5. Sync Degrees
            $db->prepare("DELETE FROM scholarship_degree_levels WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            $prefDegrees = array_unique(array_filter(array_map('trim', $prefDegrees)));
            if (!empty($prefDegrees)) {
                $stmtDeg = $db->prepare("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES (:sid, :lvl)");
                foreach ($prefDegrees as $lvl) {
                    $stmtDeg->execute(['sid' => $id, 'lvl' => $lvl]);
                }
            }

            // 6. Sync Documents
            $db->prepare("DELETE FROM scholarship_documents WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            $reqDocs = array_unique(array_filter(array_map('intval', $reqDocs)));
            if (!empty($reqDocs)) {
                $stmtDoc = $db->prepare("INSERT INTO scholarship_documents (scholarship_id, document_id) VALUES (:sid, :did)");
                foreach ($reqDocs as $dId) {
                    $stmtDoc->execute(['sid' => $id, 'did' => $dId]);
                }
            }

            // 7. Sync Eligibility rules
            $db->prepare("DELETE FROM scholarship_eligibility_rules WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            $stmtRules = $db->prepare("
                INSERT INTO scholarship_eligibility_rules (
                    scholarship_id, minimum_age, maximum_age, minimum_cgpa, cgpa_scale, minimum_percentage, 
                    gender_requirement, degree_requirement, created_at, updated_at
                ) VALUES (
                    :sid, :min_age, :max_age, :min_cgpa, :cgpa_scale, :min_pct, :gender, :degree, NOW(), NOW()
                )
            ");
            $stmtRules->execute([
                'sid' => $id,
                'min_age' => $minAge,
                'max_age' => $maxAge,
                'min_cgpa' => $minCgpa,
                'cgpa_scale' => $cgpaScale,
                'min_pct' => $minPct,
                'gender' => $genderReq ?: null,
                'degree' => $studyLevel ?: null
            ]);

            // 8. Sync Benefits
            $db->prepare("DELETE FROM scholarship_benefits WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            if (!empty($benefitTypes)) {
                $stmtBenefit = $db->prepare("
                    INSERT INTO scholarship_benefits (scholarship_id, benefit_type, title, amount, currency, created_at, updated_at) 
                    VALUES (:sid, :type, :title, :amount, :currency, NOW(), NOW())
                ");
                for ($i = 0; $i < count($benefitTypes); $i++) {
                    $type = trim($benefitTypes[$i] ?? '');
                    $bTitle = trim($benefitTitles[$i] ?? '');
                    if (empty($type) || empty($bTitle)) continue;

                    $stmtBenefit->execute([
                        'sid' => $id,
                        'type' => $type,
                        'title' => $bTitle,
                        'amount' => !empty($benefitAmts[$i]) ? (float)$benefitAmts[$i] : null,
                        'currency' => !empty($benefitCurs[$i]) ? trim($benefitCurs[$i]) : 'PKR'
                    ]);
                }
            }

            // 9. Sync Languages
            $db->prepare("DELETE FROM scholarship_languages WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            if (!empty($langNames)) {
                $stmtLang = $db->prepare("
                    INSERT INTO scholarship_languages (scholarship_id, test_name, minimum_score, is_required) 
                    VALUES (:sid, :name, :score, :req)
                ");
                for ($i = 0; $i < count($langNames); $i++) {
                    $lName = trim($langNames[$i] ?? '');
                    $lScore = trim($langScores[$i] ?? '');
                    if (empty($lName) || empty($lScore)) continue;

                    $stmtLang->execute([
                        'sid' => $id,
                        'name' => $lName,
                        'score' => $lScore,
                        'req' => isset($langRequired[$i]) ? 1 : 0
                    ]);
                }
            }

            // 10. Sync Deadlines
            $db->prepare("DELETE FROM scholarship_deadlines WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            if (!empty($deadlineDate)) {
                $stmtDead = $db->prepare("
                    INSERT INTO scholarship_deadlines (scholarship_id, deadline_type, deadline_date, status, created_at, updated_at) 
                    VALUES (:sid, :type, :date, 'active', NOW(), NOW())
                ");
                $stmtDead->execute([
                    'sid' => $id,
                    'type' => $deadlineType,
                    'date' => $deadlineDate
                ]);
            }

            // 11. Sync Sources
            $db->prepare("DELETE FROM scholarship_sources WHERE scholarship_id = :sid")->execute(['sid' => $id]);
            if (!empty($sourceName) || !empty($sourceUrl)) {
                $stmtSource = $db->prepare("
                    INSERT INTO scholarship_sources (scholarship_id, source_name, source_url, verification_status, created_at, updated_at) 
                    VALUES (:sid, :name, :url, 'unverified', NOW(), NOW())
                ");
                $stmtSource->execute([
                    'sid' => $id,
                    'name' => $sourceName ?: 'Official Website',
                    'url' => $sourceUrl ?: $officialWebsite
                ]);
            }

            $db->commit();
            $this->logAudit('updated', $id);

            header("Location: " . url('/admin/scholarships?success=Scholarship updated successfully.'));
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            Logger::error("Failed to update scholarship $id: " . $e->getMessage());
            $this->redirectBackWithErrors(['system' => 'Failed to save scholarship updates. Please try again.'], $_POST);
        }
    }

    /**
     * POST /admin/scholarships/{id}/delete
     */
    public function delete(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.delete');

        $id = (int)$id;
        $db = Database::connection();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            header("Location: " . url('/admin/scholarships?error=CSRF validation failed.'));
            exit();
        }

        try {
            $stmtStatus = $db->prepare("SELECT status FROM scholarships WHERE id = :id");
            $stmtStatus->execute(['id' => $id]);
            $status = $stmtStatus->fetchColumn();

            if ($status && $status !== 'draft') {
                header("Location: " . url('/admin/scholarships?error=Only draft scholarships can be permanently deleted. Please archive published, expired, or archived scholarships instead to preserve historical records.'));
                exit();
            }

            $db->beginTransaction();

            // Pivot deletes automatically cascade by foreign key ON DELETE CASCADE rules
            $stmt = $db->prepare("DELETE FROM scholarships WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $db->commit();
            $this->logAudit('deleted', $id);

            header("Location: " . url('/admin/scholarships?success=Scholarship deleted successfully.'));
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            Logger::error("Failed to delete scholarship $id: " . $e->getMessage());
            header("Location: " . url('/admin/scholarships?error=Failed to delete scholarship.'));
            exit();
        }
    }

    /**
     * POST /admin/scholarships/{id}/publish
     */
    public function publish(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.publish');

        $id = (int)$id;
        $db = Database::connection();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            header("Location: " . url('/admin/scholarships?error=CSRF validation failed.'));
            exit();
        }

        try {
            // Fetch the scholarship to verify completeness
            $stmt = $db->prepare("SELECT * FROM scholarships WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$scholarship) {
                header("Location: " . url('/admin/scholarships?error=Scholarship not found.'));
                exit();
            }

            // Fetch eligibility rules
            $stmtRules = $db->prepare("SELECT * FROM scholarship_eligibility_rules WHERE scholarship_id = :id");
            $stmtRules->execute(['id' => $id]);
            $rules = $stmtRules->fetch(PDO::FETCH_ASSOC);

            // Fetch source info
            $stmtSource = $db->prepare("SELECT * FROM scholarship_sources WHERE scholarship_id = :id");
            $stmtSource->execute(['id' => $id]);
            $source = $stmtSource->fetch(PDO::FETCH_ASSOC);

            // Validate required fields
            if (
                empty($scholarship['title']) ||
                empty($scholarship['description']) ||
                empty($scholarship['provider_name']) ||
                empty($scholarship['country_id']) ||
                empty($scholarship['official_application_url']) ||
                (empty($scholarship['application_deadline']) && ($scholarship['deadline_type'] ?? '') !== 'rolling') ||
                empty($rules) ||
                empty($source['source_url'])
            ) {
                header("Location: " . url('/admin/scholarships?error=Cannot publish scholarship. Required information (title, description, provider, host country, application URL, source verification, and deadline or rolling status) is missing.'));
                exit();
            }

            $stmt = $db->prepare("
                UPDATE scholarships 
                SET status = 'published', published_at = NOW(), verification_status = 'verified', 
                    verified_at = NOW(), verified_by = :uid, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmt->execute([
                'uid' => Auth::userId(),
                'id' => $id
            ]);

            $this->logAudit('published', $id);

            header("Location: " . url('/admin/scholarships?success=Scholarship published successfully.'));
            exit();
        } catch (Exception $e) {
            Logger::error("Failed to publish scholarship $id: " . $e->getMessage());
            header("Location: " . url('/admin/scholarships?error=Failed to publish scholarship.'));
            exit();
        }
    }

    /**
     * POST /admin/scholarships/{id}/archive
     */
    public function archive(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.archive');

        $id = (int)$id;
        $db = Database::connection();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            header("Location: " . url('/admin/scholarships?error=CSRF validation failed.'));
            exit();
        }

        try {
            $stmt = $db->prepare("UPDATE scholarships SET status = 'archived', updated_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->logAudit('archived', $id);

            header("Location: " . url('/admin/scholarships?success=Scholarship archived successfully.'));
            exit();
        } catch (Exception $e) {
            Logger::error("Failed to archive scholarship $id: " . $e->getMessage());
            header("Location: " . url('/admin/scholarships?error=Failed to archive scholarship.'));
            exit();
        }
    }

    /**
     * GET /scholarships
     * Public Listings (Only shows 'published' records and filters expired ones dynamically)
     */
    public function publicList(): void {
        $db = Database::connection();

        // Input criteria
        $search = trim($_GET['search'] ?? '');
        $countryId = !empty($_GET['country_id']) ? (int)$_GET['country_id'] : null;
        $degree = trim($_GET['degree'] ?? '');
        $funding = trim($_GET['funding_type'] ?? '');
        $fieldId = !empty($_GET['field_id']) ? (int)$_GET['field_id'] : null;
        $verified = trim($_GET['verified'] ?? '');
        $featured = trim($_GET['featured'] ?? '');

        // Sorting options
        $sort = trim($_GET['sort'] ?? 'published_at');
        $direction = strtoupper(trim($_GET['direction'] ?? 'DESC'));
        if (!in_array($sort, ['title', 'provider_name', 'application_deadline', 'published_at'])) {
            $sort = 'published_at';
        }
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'DESC';
        }

        // Pagination parameters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 9;
        $offset = ($page - 1) * $limit;

        // Build query
        $whereClauses = ["s.status = 'published'"];
        $params = [];

        if ($search !== '') {
            $whereClauses[] = "(s.title LIKE :search OR s.provider_name LIKE :search OR s.description LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        if ($countryId !== null) {
            // Checks if country is either primary or supported in pivot study countries
            $whereClauses[] = "(s.country_id = :country_id OR EXISTS (SELECT 1 FROM scholarship_countries sc WHERE sc.scholarship_id = s.id AND sc.country_id = :country_id))";
            $params['country_id'] = $countryId;
        }
        if ($degree !== '') {
            $whereClauses[] = "EXISTS (SELECT 1 FROM scholarship_degree_levels sdl WHERE sdl.scholarship_id = s.id AND sdl.degree_level = :degree)";
            $params['degree'] = $degree;
        }
        if ($funding !== '') {
            $whereClauses[] = "s.funding_type = :funding";
            $params['funding'] = $funding;
        }
        if ($fieldId !== null) {
            $whereClauses[] = "EXISTS (SELECT 1 FROM scholarship_fields sf WHERE sf.scholarship_id = s.id AND sf.field_of_study_id = :field_id)";
            $params['field_id'] = $fieldId;
        }
        if ($verified === '1') {
            $whereClauses[] = "s.verification_status = 'verified'";
        }
        if ($featured === '1') {
            $whereClauses[] = "s.is_featured = 1";
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Fetch counts
        $countQuery = "SELECT COUNT(*) FROM scholarships s $whereSql";
        $stmtCount = $db->prepare($countQuery);
        $stmtCount->execute($params);
        $totalCount = (int)$stmtCount->fetchColumn();
        $totalPages = ceil($totalCount / $limit);

        // Fetch rows using efficient joins
        $query = "
            SELECT s.*, c.name as country_name 
            FROM scholarships s
            LEFT JOIN countries c ON s.country_id = c.id
            $whereSql
            ORDER BY s.{$sort} $direction
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch filter options
        $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $fields = $db->query("SELECT id, name FROM fields_of_study ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        view('scholarships.index', [
            'scholarships' => $scholarships,
            'countries' => $countries,
            'fields' => $fields,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'search' => $search,
            'countryId' => $countryId,
            'degree' => $degree,
            'funding' => $funding,
            'fieldId' => $fieldId,
            'verified' => $verified,
            'featured' => $featured,
            'sort' => $sort,
            'direction' => $direction
        ]);
    }

    /**
     * GET /scholarships/{slug}
     * Public Detail View
     */
    public function publicDetail(string $slug): void {
        $db = Database::connection();

        // 1. Fetch main record (only allow published or auth admin/employees drafts)
        $stmt = $db->prepare("
            SELECT s.*, c.name as country_name 
            FROM scholarships s
            LEFT JOIN countries c ON s.country_id = c.id
            WHERE s.slug = :slug LIMIT 1
        ");
        $stmt->execute(['slug' => $slug]);
        $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$scholarship) {
            http_response_code(404);
            view('errors.404');
            exit();
        }

        // Gated access check for non-published states
        if ($scholarship['status'] !== 'published') {
            if (!Auth::isAuthenticated() || !Auth::hasRole(['admin', 'employee'])) {
                http_response_code(403);
                view('errors.403');
                exit();
            }
        }

        $id = $scholarship['id'];

        // 2. Fetch study fields mappings
        $fields = $db->query("
            SELECT f.name FROM scholarship_fields sf 
            JOIN fields_of_study f ON sf.field_of_study_id = f.id 
            WHERE sf.scholarship_id = $id
        ")->fetchAll(PDO::FETCH_COLUMN);

        // 3. Fetch study countries mappings
        $studyCountries = $db->query("
            SELECT c.name FROM scholarship_countries sc 
            JOIN countries c ON sc.country_id = c.id 
            WHERE sc.scholarship_id = $id
        ")->fetchAll(PDO::FETCH_COLUMN);

        // 4. Fetch nationalities eligibility mappings
        $nationalities = $db->query("
            SELECT c.name FROM scholarship_eligible_nationalities sen 
            JOIN countries c ON sen.country_id = c.id 
            WHERE sen.scholarship_id = $id
        ")->fetchAll(PDO::FETCH_COLUMN);

        // 5. Fetch target degree levels mappings
        $degrees = $db->query("
            SELECT degree_level FROM scholarship_degree_levels 
            WHERE scholarship_id = $id
        ")->fetchAll(PDO::FETCH_COLUMN);

        // 6. Fetch requirements documents mappings
        $requiredDocs = $db->query("
            SELECT d.name FROM scholarship_documents sd 
            JOIN documents d ON sd.document_id = d.id 
            WHERE sd.scholarship_id = $id
        ")->fetchAll(PDO::FETCH_COLUMN);

        // 7. Fetch language tests
        $languages = $db->query("SELECT * FROM scholarship_languages WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_ASSOC);

        // 8. Fetch benefits
        $benefits = $db->query("SELECT * FROM scholarship_benefits WHERE scholarship_id = $id")->fetchAll(PDO::FETCH_ASSOC);

        // 9. Fetch sources info
        $source = $db->query("SELECT * FROM scholarship_sources WHERE scholarship_id = $id")->fetch(PDO::FETCH_ASSOC) ?: [];

        // 10. Fetch eligibility criteria records
        $rules = $db->query("SELECT * FROM scholarship_eligibility_rules WHERE scholarship_id = $id")->fetch(PDO::FETCH_ASSOC) ?: [];

        // Dynamic deadline status check
        $deadlineStatus = 'Open';
        if (!empty($scholarship['application_deadline'])) {
            $deadlineTime = strtotime($scholarship['application_deadline']);
            $today = strtotime(date('Y-m-d'));
            if ($today > $deadlineTime) {
                $deadlineStatus = 'Deadline Passed';
            } elseif (($deadlineTime - $today) <= 7 * 86400) {
                // Within 7 days
                $deadlineStatus = 'Closing Soon';
            }
        }

        // 11. Run matching evaluation if user is authenticated and is 'visitor' role
        $matchResult = null;
        $docReadiness = null;
        if (Auth::isAuthenticated() && Auth::currentUser()['role_name'] === 'visitor') {
            $matchingService = new \App\Services\ScholarshipMatchingService();
            $matchResult = $matchingService->matchUserAndScholarship(Auth::userId(), $id);
            $matchingService->saveMatch($matchResult);

            $readinessService = new \App\Services\DocumentReadinessService();
            $docReadiness = $readinessService->calculateForScholarship(Auth::userId(), $id);
        }

        view('scholarships.show', [
            'scholarship' => $scholarship,
            'fields' => $fields,
            'studyCountries' => $studyCountries,
            'nationalities' => $nationalities,
            'degrees' => $degrees,
            'requiredDocs' => $requiredDocs,
            'languages' => $languages,
            'benefits' => $benefits,
            'source' => $source,
            'rules' => $rules,
            'deadlineStatus' => $deadlineStatus,
            'matchResult' => $matchResult,
            'docReadiness' => $docReadiness
        ]);
    }

    /**
     * Helper to return validation error states
     */
    private function redirectBackWithErrors(array $errors, array $old): void {
        $_SESSION['scholarship_errors'] = $errors;
        $_SESSION['scholarship_old'] = $old;
        
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (!empty($referer)) {
            header("Location: " . $referer);
        } else {
            header("Location: " . url('/admin/scholarships'));
        }
        exit();
    }
}
