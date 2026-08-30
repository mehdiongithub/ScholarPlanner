<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Document | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .review-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .review-header {
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
        .review-content {
            max-width: 800px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
            color: var(--text-600);
            text-decoration: none;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .back-link:hover {
            color: var(--primary);
        }
        .card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(20px, 4vw, 32px);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-900);
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }
        .detail-row {
            display: flex;
            border-bottom: 1px solid #f1f5f9;
            padding: 12px 0;
            font-size: 0.875rem;
        }
        .detail-label {
            width: 30%;
            font-weight: 600;
            color: var(--text-500);
        }
        .detail-value {
            width: 70%;
            color: var(--text-800);
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-uploaded { background: #e0f2fe; color: #0369a1; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fef2f2; color: #991b1b; }
        
        .action-forms-box {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        .action-card-approve {
            border: 1px solid #d1fae5;
            background: #f0fdf4;
            padding: 20px;
            border-radius: var(--radius-xl);
        }
        .action-card-reject {
            border: 1px solid #fee2e2;
            background: #fef2f2;
            padding: 20px;
            border-radius: var(--radius-xl);
        }
        .btn-approve-submit {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-approve-submit:hover {
            background: #059669;
        }
        .btn-reject-submit {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-reject-submit:hover {
            background: #dc2626;
        }
        .reason-textarea {
            width: 100%;
            height: 80px;
            padding: 8px 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            margin-bottom: 12px;
            resize: none;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="review-layout">
        <header class="review-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch Admin</span>
            </a>
            
            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-800);">
                Staff Panel
            </div>
        </header>

        <main class="review-content">
            <a href="<?= url('/admin/documents') ?>" class="back-link">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                <span>Back to Document List</span>
            </a>

            <!-- Document Details Card -->
            <div class="card">
                <h2 class="card-title">Document Details</h2>
                
                <div class="detail-row">
                    <div class="detail-label">Applicant</div>
                    <div class="detail-value">
                        <strong><?= e($record['first_name'] . ' ' . $record['last_name']) ?></strong>
                        <div style="font-size: 0.75rem; color: var(--text-500);"><?= e($record['user_email']) ?></div>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Document Type</div>
                    <div class="detail-value">
                        <strong><?= e($record['doc_type_name']) ?></strong>
                        <div style="font-size: 0.75rem; color: var(--text-500);"><?= e($record['doc_desc']) ?></div>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Original Filename</div>
                    <div class="detail-value" style="word-break: break-all; font-family: monospace;">
                        <?= e($record['original_filename']) ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">File Size</div>
                    <div class="detail-value">
                        <?= number_format($record['file_size'] / 1024, 2) ?> KB (<?= e($record['file_size']) ?> bytes)
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">MIME Type</div>
                    <div class="detail-value" style="font-family: monospace;"><?= e($record['mime_type']) ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">SHA-256 Checksum</div>
                    <div class="detail-value" style="font-family: monospace; font-size: 0.75rem; word-break: break-all;">
                        <?= e($record['checksum']) ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Uploaded At</div>
                    <div class="detail-value"><?= date('d M Y H:i:s', strtotime($record['uploaded_at'])) ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Current Status</div>
                    <div class="detail-value">
                        <?php 
                            $badgeClass = 'badge-uploaded';
                            if ($record['status'] === 'approved') $badgeClass = 'badge-approved';
                            elseif ($record['status'] === 'rejected') $badgeClass = 'badge-rejected';
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= $record['status'] === 'uploaded' ? 'Under Review' : e($record['status']) ?></span>
                    </div>
                </div>

                <?php if ($record['status'] === 'rejected' && !empty($record['rejection_reason'])): ?>
                    <div class="detail-row" style="background: #fff5f5;">
                        <div class="detail-label" style="color: #b91c1c; padding-left: 8px;">Rejection Reason</div>
                        <div class="detail-value" style="color: #991b1b;">
                            <?= e($record['rejection_reason']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 24px; text-align: center;">
                    <a href="<?= url('/admin/documents/' . $record['id'] . '/download') ?>" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; border-radius: var(--radius-md); text-decoration: none; font-weight: 700; color: white;" target="_blank">
                        <i data-lucide="download"></i>
                        <span>Download & Inspect File</span>
                    </a>
                </div>
            </div>

            <!-- Review Actions Card -->
            <div class="card">
                <h2 class="card-title">Review Actions</h2>
                
                <div class="action-forms-box">
                    <!-- Approve form -->
                    <div class="action-card-approve">
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #065f46; margin-bottom: 12px;">Approve Document</h3>
                        <p style="font-size: 0.8125rem; color: #047857; margin-bottom: 16px;">Mark this document as valid and approved. The applicant will be notified.</p>
                        
                        <form action="<?= url('/admin/documents/' . $record['id'] . '/approve') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                            <button type="submit" class="btn-approve-submit">Approve</button>
                        </form>
                    </div>

                    <!-- Reject form -->
                    <div class="action-card-reject">
                        <h3 style="font-size: 0.9375rem; font-weight: 700; color: #991b1b; margin-bottom: 12px;">Reject Document</h3>
                        <p style="font-size: 0.8125rem; color: #b91c1c; margin-bottom: 16px;">Reject this document. You must specify a clear reason for the applicant.</p>
                        
                        <form action="<?= url('/admin/documents/' . $record['id'] . '/reject') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                            <textarea name="rejection_reason" class="reason-textarea" placeholder="E.g., Document image is blurry or expired..." required></textarea>
                            <button type="submit" class="btn-reject-submit">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
