<?php
$title = $scholarship['title'] . ' | ScholarPlanner';
$description = $scholarship['short_description'] ?: substr(strip_tags($scholarship['description']), 0, 160);
$canonicalUrl = 'https://scholarplanner.com/scholarships/' . $scholarship['slug'];
$ogType = 'article';
$ogImage = !empty($scholarship['cover_image']) ? (str_starts_with($scholarship['cover_image'], 'http') ? $scholarship['cover_image'] : 'https://scholarplanner.com' . url($scholarship['cover_image'])) : 'https://scholarplanner.com/assets/images/logo.webp';

$canApply = $canApply ?? (
    \App\Services\Auth::isAuthenticated() && (
        in_array(\App\Services\Auth::currentUser()['role_name'] ?? '', ['super_admin', 'admin', 'employee'], true) ||
        \App\Services\SubscriptionService::hasActivePaidSubscription(\App\Services\Auth::userId())
    )
);

include ROOT_PATH . '/app/Views/layouts/public_header.php';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Grant",
  "name": <?= json_encode($scholarship['title'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
  "description": <?= json_encode($scholarship['short_description'] ?: substr(strip_tags($scholarship['description']), 0, 200), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
  "sponsor": {
    "@type": "Organization",
    "name": <?= json_encode($scholarship['provider_name'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  },
  "recipient": {
    "@type": "EducationalAudience",
    "educationalRole": <?= json_encode($scholarship['study_level'] ?? 'All levels', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  },
  "amount": {
    "@type": "MonetaryAmount",
    "currency": "USD",
    "description": <?= json_encode($scholarship['funding_type'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  }
  <?php if (!empty($scholarship['application_deadline'])): ?>,
  "endDate": "<?= date('Y-m-d', strtotime($scholarship['application_deadline'])) ?>"
  <?php endif; ?>
}
</script>

    <style>
        .page-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .page-content {
            max-width: 1280px;
            width: 100%;
            margin: 36px auto 60px auto;
            padding: 0 24px;
            flex-grow: 1;
        }
        .banner-card {
            margin-bottom: 28px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
        }
        .banner-info {
            flex-grow: 1;
            max-width: 860px;
        }
        .banner-provider {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .banner-title {
            font-size: clamp(1.5rem, 3.5vw, 2.25rem);
            font-weight: 800;
            color: var(--text-900);
            line-height: 1.25;
            letter-spacing: -0.025em;
            margin-bottom: 14px;
        }
        .meta-tags-container {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .meta-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: var(--radius-full);
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .meta-tag-featured {
            background: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
        }
        .meta-tag-verified {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .deadline-badge {
            padding: 10px 20px;
            border-radius: var(--radius-xl);
            font-size: 0.875rem;
            font-weight: 700;
            text-align: center;
            min-width: 140px;
            box-shadow: var(--shadow-sm);
            flex-shrink: 0;
        }
        .deadline-badge-open {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .deadline-badge-closing {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .deadline-badge-passed {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        /* 75% Main Content & 25% Quick Info Sidebar on Big Screen */
        .grid-layout {
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(260px, 1fr);
            gap: 28px;
            align-items: start;
        }
        .detail-main {
            min-width: 0;
        }
        .detail-sidebar {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(20px, 3.5vw, 32px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }
        .card-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 14px;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title i {
            color: var(--primary);
            width: 20px;
            height: 20px;
        }
        .reading-time-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-500);
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border);
        }
        .reading-time-badge i {
            width: 13px;
            height: 13px;
            color: var(--text-400);
        }

        /* ============================================================
           Professional Editorial / Blog-Post Typography for Description
           ============================================================ */
        .description-content {
            font-size: 1.0625rem;
            line-height: 1.8;
            color: #334155;
            word-break: break-word;
            letter-spacing: -0.005em;
        }

        .description-content p {
            margin-top: 0;
            margin-bottom: 1.35rem;
            line-height: 1.8;
            color: #334155;
        }

        .description-content p:last-child {
            margin-bottom: 0;
        }

        /* Headings Typography */
        .description-content h1,
        .description-content h2,
        .description-content h3,
        .description-content h4,
        .description-content h5,
        .description-content h6 {
            color: #0f172a;
            line-height: 1.35;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .description-content h1 {
            font-size: 1.75rem;
            margin-top: 2.25rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .description-content h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-top: 2.25rem;
            margin-bottom: 0.875rem;
            padding-left: 14px;
            border-left: 4px solid var(--primary);
            color: #0f172a;
        }

        .description-content h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-top: 1.85rem;
            margin-bottom: 0.75rem;
            color: #1e293b;
        }

        .description-content h4 {
            font-size: 1.075rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .description-content h5,
        .description-content h6 {
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
            color: #475569;
        }

        /* Strong / Bold Elements */
        .description-content strong,
        .description-content b {
            font-weight: 700;
            color: #0f172a;
        }

        /* Lists - Force Beautiful Bullets & Numbers */
        .description-content ul {
            list-style: disc outside !important;
            margin: 1.15rem 0 1.5rem 1.75rem !important;
            padding-left: 6px !important;
        }

        .description-content ol {
            list-style: decimal outside !important;
            margin: 1.15rem 0 1.5rem 1.75rem !important;
            padding-left: 6px !important;
        }

        .description-content li {
            margin-bottom: 0.65rem !important;
            line-height: 1.75;
            color: #334155;
            padding-left: 4px;
        }

        .description-content li:last-child {
            margin-bottom: 0 !important;
        }

        .description-content li::marker {
            color: var(--primary);
            font-weight: 700;
        }

        .description-content ul ul {
            list-style: circle outside !important;
            margin: 0.5rem 0 0.5rem 1.25rem !important;
        }

        .description-content ol ol {
            list-style: lower-alpha outside !important;
            margin: 0.5rem 0 0.5rem 1.25rem !important;
        }

        .description-content ul ol,
        .description-content ol ul {
            margin: 0.5rem 0 0.5rem 1.25rem !important;
        }

        /* Blockquotes */
        .description-content blockquote {
            border-left: 4px solid var(--primary);
            background: #f8fafc;
            padding: 16px 22px;
            margin: 1.75rem 0;
            border-radius: 0 12px 12px 0;
            font-style: italic;
            color: #1e293b;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .description-content blockquote p {
            margin-bottom: 0;
            color: #1e293b;
        }

        /* Tables */
        .description-content table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.75rem 0;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            display: table;
        }

        .description-content th {
            background: #f1f5f9;
            padding: 12px 16px;
            font-weight: 700;
            font-size: 0.8125rem;
            color: #0f172a;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
        }

        .description-content th:last-child {
            border-right: none;
        }

        .description-content td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.9375rem;
            line-height: 1.6;
        }

        .description-content td:last-child {
            border-right: none;
        }

        .description-content tr:last-child td {
            border-bottom: none;
        }

        .description-content tr:nth-child(even) td {
            background: #f8fafc;
        }

        .description-content tr:hover td {
            background: #f1f5f9;
        }

        /* Links */
        .description-content a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: all 150ms ease;
        }

        .description-content a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Code & Preformatted */
        .description-content code {
            background: #f1f5f9;
            color: #0f172a;
            padding: 2px 7px;
            border-radius: 6px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.875em;
            border: 1px solid #e2e8f0;
        }

        .description-content pre {
            background: #0f172a;
            color: #f8fafc;
            padding: 18px 20px;
            border-radius: 10px;
            overflow-x: auto;
            margin: 1.75rem 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.875rem;
            line-height: 1.65;
        }

        .description-content pre code {
            background: none;
            color: inherit;
            padding: 0;
            border: none;
        }

        /* Divider & Images */
        .description-content hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 2.25rem 0;
        }

        .description-content img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 1.75rem auto;
            display: block;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        }
        .list-unstyled {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .list-item-checklist {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            font-size: 0.875rem;
            color: var(--text-700);
            border-bottom: 1px dashed var(--border);
        }
        .list-item-checklist:last-child {
            border-bottom: none;
        }
        .list-item-checklist i {
            color: var(--primary);
            flex-shrink: 0;
            margin-top: 2px;
            width: 18px;
            height: 18px;
        }

        /* Quick Info Sidebar Box */
        .apply-box {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 24px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -2px rgba(15, 23, 42, 0.03);
            text-align: left;
            height: fit-content;
            position: sticky;
            top: 92px;
            z-index: 10;
        }
        .quick-info-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }
        .quick-info-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-900);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        .quick-info-title i {
            color: var(--primary);
            width: 20px;
            height: 20px;
        }
        .quick-info-status {
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: var(--radius-full);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Spec List in Quick Info */
        .spec-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        .spec-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: var(--radius-xl);
            transition: all 0.15s ease;
        }
        .spec-row:hover {
            background: #f1f5f9;
            border-color: #e2e8f0;
        }
        .spec-icon-wrap {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .spec-icon-wrap i {
            width: 17px;
            height: 17px;
        }
        .spec-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            flex-grow: 1;
        }
        .spec-label {
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--text-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1.2;
        }
        .spec-value {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-900);
            line-height: 1.35;
            word-break: break-word;
        }

        .apply-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: var(--radius-lg);
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            text-decoration: none;
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
        .apply-box .btn-primary {
            width: 100%;
            justify-content: center;
            padding: 12px 18px;
            font-size: 0.9375rem;
            font-weight: 700;
            border-radius: var(--radius-xl);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
        }
        .apply-box .btn-primary:hover {
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
            transform: translateY(-1px);
        }
        .btn-tracker {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 16px;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text-700);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-tracker:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: var(--text-900);
        }
        .locked-link-box {
            padding: 14px;
            background: #fffbeb;
            border: 1px dashed #fde68a;
            border-radius: var(--radius-xl);
            text-align: center;
            margin-bottom: 4px;
        }
        .locked-link-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 700;
            font-size: 0.8125rem;
            color: #92400e;
            margin-bottom: 4px;
        }
        .locked-link-desc {
            font-size: 0.75rem;
            color: #78350f;
            margin-bottom: 10px;
            line-height: 1.45;
        }

        .rec-status-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            text-transform: uppercase;
        }
        .status-eligible { background: #d1fae5; color: #065f46; }
        .status-possibly { background: #e0f2fe; color: #0369a1; }
        .status-insufficient { background: #fef3c7; color: #92400e; }
        .status-ineligible { background: #fee2e2; color: #991b1b; }

        .rec-criteria-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.8125rem;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .rec-criteria-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.4;
        }
        .rec-criteria-item i {
            margin-top: 2px;
            flex-shrink: 0;
        }
        .crit-success { color: #10b981; }
        .crit-warning { color: #f59e0b; }
        .crit-danger { color: #ef4444; }

        .mobile-sticky-bar {
            display: none;
        }

        /* Responsive Media Queries */
        @media (max-width: 992px) {
            .page-content {
                margin: 24px auto 60px auto;
                padding: 0 18px;
            }
            .grid-layout {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            .apply-box {
                position: static;
                margin-top: 0;
            }
            .spec-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .page-content {
                margin: 16px auto 76px auto;
                padding: 0 14px;
            }
            .banner-card {
                margin-bottom: 20px;
                gap: 14px;
            }
            .banner-title {
                font-size: clamp(1.35rem, 5vw, 1.8rem);
                line-height: 1.3;
                margin-bottom: 12px;
            }
            .meta-tags-container {
                gap: 6px;
                margin-bottom: 12px;
            }
            .meta-tag {
                font-size: 0.7rem;
                padding: 3px 10px;
            }
            .deadline-badge {
                width: 100%;
                min-width: unset;
                padding: 10px 16px;
                border-radius: var(--radius-lg);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .deadline-badge div:first-child {
                margin-bottom: 0;
            }
            .deadline-badge div:last-child {
                margin-top: 0;
                font-size: 1rem;
            }
            .card {
                padding: 18px 16px;
                border-radius: var(--radius-xl);
                margin-bottom: 20px;
            }
            .card-title {
                font-size: 1.125rem;
                margin-bottom: 16px;
                padding-bottom: 10px;
            }
            .apply-box {
                padding: 18px 16px;
                border-radius: var(--radius-xl);
            }
            .spec-list {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
            .spec-row {
                padding: 8px 10px;
                gap: 8px;
            }
            .spec-icon-wrap {
                width: 32px;
                height: 32px;
                border-radius: 8px;
            }
            .spec-icon-wrap i {
                width: 15px;
                height: 15px;
            }
            .spec-label {
                font-size: 0.625rem;
            }
            .spec-value {
                font-size: 0.8125rem;
            }

            .mobile-sticky-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.96);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border-top: 1px solid var(--border);
                box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
                padding: 10px 16px;
                z-index: 999;
            }
            .mobile-sticky-info {
                display: flex;
                flex-direction: column;
                gap: 2px;
                min-width: 0;
            }
            .mobile-sticky-deadline {
                font-size: 0.75rem;
                font-weight: 700;
                color: #0f172a;
                display: flex;
                align-items: center;
                gap: 4px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .mobile-sticky-funding {
                font-size: 0.6875rem;
                font-weight: 600;
                color: #059669;
            }
            .mobile-sticky-btn {
                padding: 9px 18px;
                font-size: 0.8125rem;
                font-weight: 700;
                border-radius: var(--radius-lg);
                white-space: nowrap;
                flex-shrink: 0;
            }
        }

        @media (max-width: 480px) {
            .spec-list {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-layout">
        <div class="page-content">
            <!-- Cover Image Section -->
            <div style="margin-bottom: 24px; border-radius: var(--radius-2xl); overflow: hidden; height: clamp(200px, 30vw, 320px); border: 1px solid var(--border); box-shadow: var(--shadow-sm); position: relative; background: #fff;">
                <?php if (!empty($scholarship['cover_image'])): ?>
                    <img src="<?= e(url($scholarship['cover_image'])) ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="<?= e($scholarship['title']) ?>">
                <?php else: ?>
                    <img src="<?= e(url('/assets/images/default-scholarship.svg')) ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Default Scholarship Image">
                <?php endif; ?>
            </div>

            <!-- Header Banner -->
            <div class="banner-card">
                <div class="banner-info">
                    <div class="banner-provider"><?= e($scholarship['provider_name']) ?></div>
                    <h1 class="banner-title"><?= e($scholarship['title']) ?></h1>
                    
                    <div class="meta-tags-container">
                        <?php if ($scholarship['is_featured']): ?>
                            <span class="meta-tag meta-tag-featured"><i data-lucide="sparkles" style="width:12px; height:12px; display:inline; vertical-align:middle; margin-right:4px;"></i>Featured</span>
                        <?php endif; ?>
                        <?php if ($scholarship['verification_status'] === 'verified'): ?>
                            <span class="meta-tag meta-tag-verified"><i data-lucide="check" style="width:12px; height:12px; display:inline; vertical-align:middle; margin-right:4px;"></i>Verified Opportunity</span>
                        <?php endif; ?>
                        <span class="meta-tag"><?= e($scholarship['funding_type']) ?></span>
                        <span class="meta-tag"><?= e($scholarship['country_name'] ?? 'Multiple Countries') ?></span>
                    </div>
                </div>

                <!-- Deadline Badge -->
                <?php
                $badgeClass = 'deadline-badge-open';
                if ($deadlineStatus === 'Closing Soon') {
                    $badgeClass = 'deadline-badge-closing';
                } elseif ($deadlineStatus === 'Deadline Passed') {
                    $badgeClass = 'deadline-badge-passed';
                }
                ?>
                <div class="deadline-badge <?= $badgeClass ?>">
                    <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; opacity:0.8;">Status</div>
                    <div style="font-size:1.1rem; margin-top:2px; font-weight:800;"><?= e($deadlineStatus) ?></div>
                </div>
            </div>

            <!-- Detail Grid Layout -->
            <div class="grid-layout">
                <!-- Main detail column -->
                <div class="detail-main">
                    <!-- Description -->
                    <div class="card" id="scholarship-description">
                        <div class="card-header-flex">
                            <h2 class="card-title">
                                <i data-lucide="book-open"></i>
                                <span>Scholarship Overview & Full Details</span>
                            </h2>
                            <?php 
                            $wordCount = str_word_count(strip_tags($scholarship['description'] ?? ''));
                            $readTime = max(1, (int)ceil($wordCount / 200));
                            ?>
                            <span class="reading-time-badge">
                                <i data-lucide="clock"></i>
                                <span><?= $readTime ?> min read</span>
                            </span>
                        </div>
                        <div class="description-content blog-typography">
                            <!-- Safe output of sanitized description -->
                            <?= $scholarship['description'] ?>
                        </div>
                    </div>

                    <!-- Eligibility criteria -->
                    <div class="card">
                        <h2 class="card-title">
                            <i data-lucide="user-check"></i>
                            <span>Eligibility Criteria</span>
                        </h2>
                        <ul class="list-unstyled">
                            <?php if (!empty($degrees)): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="graduation-cap"></i>
                                    <span>Target Study Tiers: <strong><?= e(implode(', ', $degrees)) ?></strong></span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($fields)): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="book-open"></i>
                                    <span>Eligible Disciplines: <strong><?= e(implode(', ', $fields)) ?></strong></span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($rules['minimum_cgpa'])): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="award"></i>
                                    <span>Minimum Academic Score: <strong><?= e($rules['minimum_cgpa']) ?> CGPA (out of <?= e($rules['cgpa_scale'] ?? '4.0') ?>)</strong> or equivalent.</span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($rules['minimum_percentage'])): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="percent"></i>
                                    <span>Minimum Percentage Required: <strong><?= e($rules['minimum_percentage']) ?>%</strong></span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($rules['minimum_age']) || !empty($rules['maximum_age'])): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="calendar-days"></i>
                                    <span>Age Restriction: 
                                        <?php if (!empty($rules['minimum_age']) && !empty($rules['maximum_age'])): ?>
                                            Between <strong><?= e($rules['minimum_age']) ?> and <?= e($rules['maximum_age']) ?> years old.</strong>
                                        <?php elseif (!empty($rules['minimum_age'])): ?>
                                            Must be at least <strong><?= e($rules['minimum_age']) ?> years old.</strong>
                                        <?php else: ?>
                                            Must not exceed <strong><?= e($rules['maximum_age']) ?> years old.</strong>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($nationalities)): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="globe-2"></i>
                                    <span>Eligible Nationalities: <strong><?= e(implode(', ', $nationalities)) ?></strong></span>
                                </li>
                            <?php else: ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="globe-2"></i>
                                    <span>Eligible Nationalities: <strong>Open to All Nationalities</strong></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Benefits -->
                    <?php if (!empty($benefits)): ?>
                        <div class="card">
                            <h2 class="card-title">
                                <i data-lucide="gift"></i>
                                <span>Scholarship Inclusions & Benefits</span>
                            </h2>
                            <ul class="list-unstyled">
                                <?php foreach ($benefits as $b): ?>
                                    <li class="list-item-checklist">
                                        <i data-lucide="circle-check"></i>
                                        <span>
                                            <strong><?= e($b['benefit_type']) ?></strong>: <?= e($b['title']) ?>
                                            <?php if ($b['amount'] !== null): ?>
                                                (<?= e(number_format($b['amount'], 0)) ?> <?= e($b['currency']) ?>)
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Language Criteria -->
                    <?php if (!empty($languages)): ?>
                        <div class="card">
                            <h2 class="card-title">
                                <i data-lucide="message-square"></i>
                                <span>Language Test Requirements</span>
                            </h2>
                            <ul class="list-unstyled">
                                <?php foreach ($languages as $l): ?>
                                    <li class="list-item-checklist">
                                        <i data-lucide="clipboard-list"></i>
                                        <span>
                                            <strong><?= e($l['test_name']) ?></strong> (Score: <strong><?= e($l['minimum_score']) ?></strong>) &mdash; 
                                            <?= $l['is_required'] ? '<span style="color:#ef4444; font-weight:600;">Mandatory</span>' : '<span style="color:#64748b;">Optional</span>' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Required Documents -->
                    <?php if (!empty($requiredDocs)): ?>
                        <div class="card">
                            <h2 class="card-title">
                                <i data-lucide="files"></i>
                                <span>Application Documents & Readiness</span>
                            </h2>
                            
                            <?php if (isset($docReadiness) && $docReadiness !== null): ?>
                                <div style="margin-bottom: 20px; background: #f8fafc; border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-xl);">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; font-weight: 600; margin-bottom: 6px;">
                                        <span>Your Document Readiness for this Scholarship</span>
                                        <span><?= e($docReadiness['readiness_percentage']) ?>%</span>
                                    </div>
                                    <div class="progress-bar-container" style="background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                                        <div class="progress-bar-fill" style="width: <?= e($docReadiness['readiness_percentage']) ?>%; height:100%; background:var(--primary); transition: width 0.3s;"></div>
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--text-500); margin-top:8px;">
                                        <a href="<?= url('/documents') ?>" style="color:var(--primary); text-decoration:none; font-weight:600;">Upload missing documents &rarr;</a>
                                    </div>
                                </div>

                                <ul class="list-unstyled">
                                    <?php foreach ($docReadiness['details'] as $detail): 
                                        $status = $detail['status'];
                                        $statusClass = 'color: #475569;';
                                        $statusLabel = 'Missing';
                                        $icon = 'alert-circle';
                                        
                                        if ($status === 'approved') {
                                            $statusClass = 'color: #059669; font-weight: 600;';
                                            $statusLabel = 'Approved';
                                            $icon = 'check-circle';
                                        } elseif ($status === 'rejected') {
                                            $statusClass = 'color: #dc2626; font-weight: 600;';
                                            $statusLabel = 'Rejected';
                                            $icon = 'x-circle';
                                        } elseif ($status === 'uploaded') {
                                            $statusClass = 'color: #0284c7; font-weight: 600;';
                                            $statusLabel = 'Under Review';
                                            $icon = 'clock';
                                        }
                                    ?>
                                        <li style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--border);">
                                            <span style="display: flex; align-items: center; gap: 8px;">
                                                <i data-lucide="file" style="color:var(--text-400); width:16px; height:16px;"></i>
                                                <span><?= e($detail['name']) ?> <?= $detail['is_required'] ? '<span style="color:#ef4444; font-size:0.75rem; font-weight:500;">(Required)</span>' : '<span style="color:#64748b; font-size:0.75rem;">(Optional)</span>' ?></span>
                                            </span>
                                            <span style="display: flex; align-items: center; gap: 6px; font-size: 0.8125rem; <?= $statusClass ?>">
                                                <i data-lucide="<?= $icon ?>" style="width:14px; height:14px;"></i>
                                                <span><?= $statusLabel ?></span>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($requiredDocs as $docName): ?>
                                        <li class="list-item-checklist">
                                            <i data-lucide="file"></i>
                                            <span><?= e($docName) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <p style="font-size:0.8125rem; color:var(--text-500); margin-top:12px;">
                                    <a href="<?= url('/login') ?>" style="color:var(--primary); font-weight:600; text-decoration:none;">Log in</a> to track your document readiness checklist!
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Box -->
                <div class="detail-sidebar">
                    <?php if (isset($matchResult) && $matchResult !== null): 
                        $status = $matchResult['eligibility_status'];
                        $badgeClass = 'status-eligible';
                        $statusLabel = 'Eligible';
                        if ($status === 'NOT_ELIGIBLE') {
                            $badgeClass = 'status-ineligible';
                            $statusLabel = 'Ineligible';
                        } elseif ($status === 'INSUFFICIENT_DATA') {
                            $badgeClass = 'status-insufficient';
                            $statusLabel = 'Missing Data';
                        } elseif ($status === 'POSSIBLY_ELIGIBLE') {
                            $badgeClass = 'status-possibly';
                            $statusLabel = 'Possibly Eligible';
                        }
                    ?>
                        <div class="card" style="padding: 24px; margin-bottom: 24px; border: 1px solid var(--border);">
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-900); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="award" style="color: var(--primary);"></i>
                                <span>Your Eligibility Match</span>
                            </h3>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                <span style="font-size: 1.75rem; font-weight: 800; color: var(--primary);"><?= e($matchResult['match_score']) ?>%</span>
                                <span class="rec-status-badge <?= $badgeClass ?>"><?= e($statusLabel) ?></span>
                            </div>

                            <div style="font-size: 0.8125rem; margin-bottom: 16px; font-weight: 600; color: var(--text-600);">
                                Recommendation: <span style="text-transform: uppercase; color: var(--primary);"><?= e(str_replace('_', ' ', $matchResult['recommendation_level'])) ?></span>
                            </div>

                            <ul class="rec-criteria-list" style="margin-bottom: 0;">
                                <?php foreach ($matchResult['matched_criteria'] as $rule => $msg): ?>
                                    <li class="rec-criteria-item crit-success">
                                        <i data-lucide="check-circle" style="width: 14px; height: 14px; margin-top: 1px;"></i>
                                        <span><?= e($msg) ?></span>
                                    </li>
                                <?php endforeach; ?>

                                <?php foreach ($matchResult['missing_criteria'] as $rule => $msg): ?>
                                    <li class="rec-criteria-item crit-warning">
                                        <i data-lucide="alert-circle" style="width: 14px; height: 14px; margin-top: 1px;"></i>
                                        <span><?= e($msg) ?></span>
                                    </li>
                                <?php endforeach; ?>

                                <?php foreach ($matchResult['failed_criteria'] as $rule => $msg): ?>
                                    <li class="rec-criteria-item crit-danger">
                                        <i data-lucide="x-circle" style="width: 14px; height: 14px; margin-top: 1px;"></i>
                                        <span><?= e($msg) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif (!\App\Services\Auth::isAuthenticated()): ?>
                        <div class="card" style="padding: 24px; margin-bottom: 24px; text-align: center; border: 1px dashed var(--border); background: #fafafa;">
                            <i data-lucide="help-circle" style="color: var(--text-400); width: 36px; height: 36px; margin-bottom: 12px; margin-inline: auto;"></i>
                            <h3 style="font-size: 0.9375rem; font-weight: 700; color: var(--text-900); margin-bottom: 6px;">Want to see your Match?</h3>
                            <p style="font-size: 0.8125rem; color: var(--text-500); margin-bottom: 16px;">Log in or create a profile to calculate your personalized eligibility percentage.</p>
                            <a href="<?= url('/register') ?>" class="btn btn-secondary" style="font-size: 0.8125rem;">
                                <span>Get Started</span>
                                <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="apply-box" id="quick-info-box">
                        <div class="quick-info-header">
                            <h3 class="quick-info-title">
                                <i data-lucide="zap"></i>
                                <span>Quick Info</span>
                            </h3>
                            <span class="quick-info-status <?= $badgeClass ?>"><?= e($deadlineStatus) ?></span>
                        </div>
                        
                        <?php
                            // Degree formatting
                            $degreeText = 'All Degree Levels';
                            if (!empty($degrees)) {
                                if (count($degrees) <= 2) {
                                    $degreeText = implode(', ', $degrees);
                                } else {
                                    $degreeText = $degrees[0] . ', ' . $degrees[1] . ' +' . (count($degrees) - 2) . ' more';
                                }
                            } elseif (!empty($scholarship['study_level'])) {
                                $degreeText = $scholarship['study_level'];
                            }

                            // Nationality formatting
                            $nationalityText = 'Open to All';
                            if (!empty($nationalities)) {
                                if (count($nationalities) <= 2) {
                                    $nationalityText = implode(', ', $nationalities);
                                } else {
                                    $nationalityText = $nationalities[0] . ', ' . $nationalities[1] . ' +' . (count($nationalities) - 2) . ' more';
                                }
                            }

                            // Deadline calculation
                            $deadlineText = 'Open / Rolling';
                            $deadlineSub = null;
                            if (!empty($scholarship['application_deadline'])) {
                                $dTime = strtotime($scholarship['application_deadline']);
                                $deadlineText = date('M d, Y', $dTime);
                                $daysDiff = (int)ceil(($dTime - strtotime(date('Y-m-d'))) / 86400);
                                if ($daysDiff > 0) {
                                    $deadlineSub = $daysDiff . ' day' . ($daysDiff === 1 ? '' : 's') . ' remaining';
                                } elseif ($daysDiff === 0) {
                                    $deadlineSub = 'Ends today!';
                                } else {
                                    $deadlineSub = 'Deadline passed';
                                }
                            }
                        ?>

                        <div class="spec-list">
                            <div class="spec-row">
                                <div class="spec-icon-wrap" style="background: #eff6ff; color: #2563eb;">
                                    <i data-lucide="globe"></i>
                                </div>
                                <div class="spec-text">
                                    <span class="spec-label">Host Country</span>
                                    <span class="spec-value"><?= e($scholarship['country_name'] ?? 'Multiple Countries') ?></span>
                                </div>
                            </div>

                            <div class="spec-row">
                                <div class="spec-icon-wrap" style="background: #ecfdf5; color: #059669;">
                                    <i data-lucide="badge-dollar-sign"></i>
                                </div>
                                <div class="spec-text">
                                    <span class="spec-label">Funding Mode</span>
                                    <span class="spec-value"><?= e($scholarship['funding_type']) ?></span>
                                </div>
                            </div>

                            <div class="spec-row">
                                <div class="spec-icon-wrap" style="background: #f5f3ff; color: #7c3aed;">
                                    <i data-lucide="building-2"></i>
                                </div>
                                <div class="spec-text">
                                    <span class="spec-label">Provider</span>
                                    <span class="spec-value"><?= e($scholarship['provider_name']) ?></span>
                                </div>
                            </div>

                            <div class="spec-row">
                                <div class="spec-icon-wrap" style="background: #fdf4ff; color: #a21caf;">
                                    <i data-lucide="graduation-cap"></i>
                                </div>
                                <div class="spec-text">
                                    <span class="spec-label">Degree Level</span>
                                    <span class="spec-value"><?= e($degreeText) ?></span>
                                </div>
                            </div>

                            <div class="spec-row">
                                <div class="spec-icon-wrap" style="background: #f0fdfa; color: #0d9488;">
                                    <i data-lucide="users"></i>
                                </div>
                                <div class="spec-text">
                                    <span class="spec-label">Eligible Applicants</span>
                                    <span class="spec-value"><?= e($nationalityText) ?></span>
                                </div>
                            </div>

                            <div class="spec-row">
                                <div class="spec-icon-wrap" style="background: #fff7ed; color: #ea580c;">
                                    <i data-lucide="calendar"></i>
                                </div>
                                <div class="spec-text">
                                    <span class="spec-label">Closing Date</span>
                                    <span class="spec-value"><?= e($deadlineText) ?></span>
                                    <?php if ($deadlineSub): ?>
                                        <span style="font-size: 0.6875rem; color: #ea580c; font-weight: 600;"><?= e($deadlineSub) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="apply-actions">
                            <?php if ($canApply): ?>
                                <?php if (!empty($scholarship['official_application_url'])): ?>
                                    <a href="<?= e($scholarship['official_application_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="margin-bottom:4px;">
                                        <span>Apply on Official Website</span>
                                        <i data-lucide="external-link" style="width:16px; height:16px;"></i>
                                    </a>
                                <?php elseif (!empty($scholarship['official_website'])): ?>
                                    <a href="<?= e($scholarship['official_website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="margin-bottom:4px;">
                                        <span>Visit Official Portal</span>
                                        <i data-lucide="external-link" style="width:16px; height:16px;"></i>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="locked-link-box">
                                    <div class="locked-link-badge">
                                        <i data-lucide="lock" style="width: 14px; height: 14px; color: #d97706;"></i>
                                        <span>Official Application Link</span>
                                    </div>
                                    <p class="locked-link-desc">
                                        Direct application links are available exclusively to active paid subscribers.
                                    </p>
                                    <?php if (!\App\Services\Auth::isAuthenticated()): ?>
                                        <a href="<?= url('/login') ?>" class="btn btn-secondary" style="width: 100%; justify-content: center; font-size: 0.8125rem; padding: 9px 16px; min-height: 38px; gap: 6px;">
                                            <i data-lucide="log-in" style="width: 14px; height: 14px;"></i>
                                            <span>Log In to Access</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= url('/pricing') ?>" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 0.8125rem; padding: 9px 16px; min-height: 38px; gap: 6px;">
                                            <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i>
                                            <span>Upgrade Plan to Apply</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (\App\Services\Auth::isAuthenticated() && \App\Services\Auth::currentUser()['role_name'] === 'visitor'): ?>
                                <?php
                                    $db = \App\Services\Database::connection();
                                    $stmtTrack = $db->prepare("SELECT id FROM scholarship_applications WHERE user_id = :uid AND scholarship_id = :sid LIMIT 1");
                                    $stmtTrack->execute(['uid' => \App\Services\Auth::userId(), 'sid' => $scholarship['id']]);
                                    $trackingAppId = $stmtTrack->fetchColumn();
                                ?>
                                <?php if ($trackingAppId): ?>
                                    <a href="/applications/<?= e($trackingAppId) ?>" class="btn btn-secondary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                        <i data-lucide="folder-check" style="width:16px; height:16px;"></i>
                                        <span>View in Tracker</span>
                                    </a>
                                <?php else: ?>
                                    <form method="POST" action="/applications" style="width: 100%;">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="scholarship_id" value="<?= e($scholarship['id']) ?>">
                                        <input type="hidden" name="status" value="interested">
                                        <button type="submit" class="btn btn-tracker">
                                            <i data-lucide="folder-plus" style="width:16px; height:16px;"></i>
                                            <span>Add to Tracker</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($source['source_name'])): ?>
                                <div style="font-size:0.75rem; color:var(--text-500); margin-top:14px; text-align:left; border-top: 1px solid var(--border); padding-top:14px;">
                                    <div style="display:flex; align-items:center; gap:5px; font-weight:600; color:var(--text-700); margin-bottom:4px;">
                                        <i data-lucide="shield-check" style="width:14px; height:14px; color:#059669;"></i>
                                        <span>Verified Source Link</span>
                                    </div>
                                    <?php if ($canApply): ?>
                                        <a href="<?= e($source['source_url']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--primary); text-decoration:none; word-break:break-all; font-weight: 500;"><?= e($source['source_name']) ?></a>
                                    <?php else: ?>
                                        <span style="color:var(--text-600);"><?= e($source['source_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($source['last_checked_at'])): ?>
                                        <div style="margin-top:3px; color: #94a3b8; font-size: 0.6875rem;">Last verified: <?= e(date('M d, Y', strtotime($source['last_checked_at']))) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sticky Bottom CTA Bar -->
    <div class="mobile-sticky-bar">
        <div class="mobile-sticky-info">
            <span class="mobile-sticky-deadline">
                <i data-lucide="calendar" style="width: 13px; height: 13px;"></i>
                <?= $scholarship['application_deadline'] ? e(date('M d, Y', strtotime($scholarship['application_deadline']))) : 'Open / Rolling' ?>
            </span>
            <span class="mobile-sticky-funding"><?= e($scholarship['funding_type']) ?></span>
        </div>
        <div class="mobile-sticky-btn-wrap">
            <?php if ($canApply): ?>
                <?php if (!empty($scholarship['official_application_url'])): ?>
                    <a href="<?= e($scholarship['official_application_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary mobile-sticky-btn">
                        <span>Apply</span>
                        <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                    </a>
                <?php elseif (!empty($scholarship['official_website'])): ?>
                    <a href="<?= e($scholarship['official_website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary mobile-sticky-btn">
                        <span>Apply</span>
                        <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="#quick-info-box" class="btn btn-primary mobile-sticky-btn">
                    <i data-lucide="lock" style="width: 13px; height: 13px;"></i>
                    <span>Apply Link</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

<?php include ROOT_PATH . '/app/Views/layouts/public_footer.php'; ?>
