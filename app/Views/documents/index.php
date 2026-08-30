<?php
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .docs-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .docs-header {
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
            transition: color 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary);
        }
        .docs-content {
            max-width: 1100px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .welcome-section {
            margin-bottom: 32px;
        }
        .welcome-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 6px;
        }
        .welcome-subtitle {
            color: var(--text-500);
            font-size: 0.9375rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-50);
            color: var(--primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-icon.success { background: #ecfdf5; color: #059669; }
        .stat-icon.warning { background: #fffbeb; color: #d97706; }
        .stat-icon.danger { background: #fef2f2; color: #dc2626; }
        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-400);
            letter-spacing: 0.05em;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-900);
        }
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: var(--radius-full);
            margin-top: 12px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: var(--radius-full);
            transition: width 0.3s;
        }
        .docs-list-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(24px, 5vw, 40px);
            box-shadow: var(--shadow-sm);
        }
        .docs-table {
            width: 100%;
            border-collapse: collapse;
        }
        .docs-table th {
            text-align: left;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border);
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
        }
        .docs-table td {
            padding: 20px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
            color: var(--text-700);
            vertical-align: top;
        }
        .doc-meta-info {
            margin-top: 4px;
            font-size: 0.75rem;
            color: var(--text-400);
            display: flex;
            flex-direction: column;
            gap: 2px;
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
        .badge-missing { background: #f1f5f9; color: #475569; }
        
        .upload-form-box {
            background: #f8fafc;
            border: 1px dashed var(--border);
            border-radius: var(--radius-lg);
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .btn-browse {
            border: 1px solid var(--border);
            background: white;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
        }
        .file-input-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        .btn-upload-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-upload-submit:hover {
            background: var(--primary-hover);
        }
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-action-icon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid var(--border);
            background: white;
            color: var(--text-700);
            transition: all 0.2s;
        }
        .btn-action-icon:hover {
            background: #f1f5f9;
        }
        .btn-action-delete {
            border-color: #fee2e2;
            color: #b91c1c;
            background: #fff5f5;
        }
        .btn-action-delete:hover {
            background: #fee2e2;
        }
        .alert-error-box {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            font-size: 0.875rem;
        }
        .alert-success-box {
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            font-size: 0.875rem;
        }
        .rejection-reason-block {
            margin-top: 10px;
            background: #fff5f5;
            border-left: 4px solid #ef4444;
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 0.8125rem;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="docs-layout">
        <header class="docs-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch</span>
            </a>
            
            <div class="nav-links">
                <a href="/dashboard" class="nav-link">Dashboard</a>
                <a href="/profile" class="nav-link">My Profile</a>
                <a href="/documents" class="nav-link active">Documents</a>
                <a href="/applications" class="nav-link">Applications</a>
            </div>
            
            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-800);">
                Active Session
            </div>
        </header>

        <main class="docs-content">
            <div class="welcome-section">
                <h1 class="welcome-title">My Documents</h1>
                <p class="welcome-subtitle">Upload and manage required application certificates and transcripts</p>
            </div>

            <!-- Flash alerts -->
            <?php if (!empty($errors)): ?>
                <div class="alert-error-box" role="alert">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= e($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success-box" role="alert">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Document Readiness</span>
                        <div class="stat-icon">
                            <i data-lucide="file-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($readiness['readiness_percentage']) ?>%</div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: <?= e($readiness['readiness_percentage']) ?>%;"></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Approved Documents</span>
                        <div class="stat-icon success">
                            <i data-lucide="shield-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($readiness['approved_count']) ?> / <?= e($readiness['required_count']) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Pending Review</span>
                        <div class="stat-icon warning">
                            <i data-lucide="clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($readiness['uploaded_count'] - $readiness['approved_count'] - $readiness['rejected_count']) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Missing Items</span>
                        <div class="stat-icon danger">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($readiness['missing_count']) ?></div>
                </div>
            </div>

            <div class="docs-list-card">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 24px; color: var(--text-900);">Document List</h2>
                
                <div style="overflow-x: auto;">
                    <table class="docs-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Document Type</th>
                                <th style="width: 20%;">Status</th>
                                <th style="width: 50%;">Action / Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): 
                                $docId = $doc['id'];
                                $hasUpload = isset($userDocsMap[$docId]);
                                $uploaded = $hasUpload ? $userDocsMap[$docId] : null;
                                $status = $uploaded ? $uploaded['status'] : 'missing';
                                
                                $badgeClass = 'badge-missing';
                                if ($status === 'uploaded') $badgeClass = 'badge-uploaded';
                                elseif ($status === 'approved') $badgeClass = 'badge-approved';
                                elseif ($status === 'rejected') $badgeClass = 'badge-rejected';
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-800);"><?= e($doc['name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-500); margin-top: 4px;"><?= e($doc['description']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>"><?= $status === 'uploaded' ? 'Under Review' : e($status) ?></span>
                                        
                                        <?php if ($uploaded): ?>
                                            <div class="doc-meta-info">
                                                <span>File: <?= e($uploaded['original_filename']) ?></span>
                                                <span>Size: <?= formatBytes((int)$uploaded['file_size']) ?></span>
                                                <span>Date: <?= date('d M Y', strtotime($uploaded['uploaded_at'])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($status === 'rejected' && !empty($uploaded['rejection_reason'])): ?>
                                            <div class="rejection-reason-block">
                                                <strong>Reason:</strong> <?= e($uploaded['rejection_reason']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <?php if ($status === 'missing' || $status === 'rejected' || $status === 'uploaded'): ?>
                                                <!-- Upload Form -->
                                                <form action="<?= url('/documents/upload') ?>" method="POST" enctype="multipart/form-data" class="upload-form-box">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                    <input type="hidden" name="document_id" value="<?= e($docId) ?>">
                                                    
                                                    <div class="file-input-wrapper">
                                                        <button type="button" class="btn-browse">Choose File</button>
                                                        <input type="file" name="file" onchange="this.form.querySelector('.file-name-text').textContent = this.files[0].name;" required>
                                                    </div>
                                                    
                                                    <span class="file-name-text" style="font-size: 0.75rem; color: var(--text-500); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">No file selected</span>
                                                    
                                                    <button type="submit" class="btn-upload-submit">
                                                        <i data-lucide="upload" style="width: 14px; height: 14px;"></i>
                                                        <span><?= $status === 'missing' ? 'Upload' : 'Replace' ?></span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($uploaded): ?>
                                                <a href="<?= url('/documents/' . $uploaded['id'] . '/download') ?>" class="btn-action-icon" target="_blank">
                                                    <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                                                    <span>Download</span>
                                                </a>
                                                
                                                <?php if ($status !== 'approved'): ?>
                                                    <form action="<?= url('/documents/' . $uploaded['id'] . '/delete') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                        <button type="submit" class="btn-action-icon btn-action-delete">
                                                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                                            <span>Delete</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
