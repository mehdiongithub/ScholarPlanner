<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: #f8fafc;
        }
        .auth-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(20px, 5vw, 40px);
            max-width: 460px;
            width: 100%;
            box-shadow: var(--shadow-xl);
        }
        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
            text-decoration: none;
            color: var(--text-900);
        }
        .auth-logo .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: var(--bg-white);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-logo .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-900);
            text-align: center;
            margin-bottom: 8px;
        }
        .auth-subtitle {
            font-size: 0.875rem;
            color: var(--text-500);
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .email-display {
            font-weight: 600;
            color: var(--text-800);
        }
        .otp-container {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin: 24px 0;
        }
        .otp-field {
            width: 100%;
            height: 52px;
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: var(--bg-white);
            color: var(--text-900);
            transition: all 0.2s ease;
        }
        .otp-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 11px;
            background: var(--primary);
            color: var(--bg-white);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: var(--primary-dark, #1d4ed8);
        }
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
        }
        .auth-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-500);
        }
        .resend-form {
            display: inline;
        }
        .resend-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        .resend-btn:hover {
            text-decoration: underline;
        }
        .resend-btn:disabled {
            color: var(--text-400);
            cursor: not-allowed;
            text-decoration: none;
        }
        @media (max-width: 280px) {
            .otp-container {
                gap: 4px;
            }
            .otp-field {
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <a href="/" class="auth-logo">
                <div class="logo-icon">
                    <i data-lucide="graduation-cap"></i>
                </div>
                <span class="logo-text">ScholarMatch</span>
            </a>

            <h1 class="auth-title">Verify your email</h1>
            <p class="auth-subtitle">
                Enter the 6-digit verification code we sent to <span class="email-display"><?= e($email) ?></span>.
            </p>

            <?php if (!empty($errors['code'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($errors['code']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['csrf'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($errors['csrf']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['resend'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($errors['resend']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?= e($success_message) ?>
                </div>
            <?php endif; ?>

            <form action="<?= url('/verify-email') ?>" method="POST" id="otp-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                <div class="otp-container">
                    <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 1" autofocus>
                    <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 2">
                    <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 3">
                    <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 4">
                    <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 5">
                    <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 6">
                </div>

                <button type="submit" class="btn-submit">Verify Email</button>
            </form>

            <div class="auth-footer">
                Didn't receive the code?
                <form action="<?= url('/verify-email/resend') ?>" method="POST" class="resend-form" id="resend-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <button type="submit" class="resend-btn" id="resend-btn">Resend code</button>
                </form>
                <span id="cooldown-timer" style="display: none; font-size: 0.875rem; color: var(--text-400);"></span>
            </div>
            
            <div class="auth-footer" style="margin-top: 16px;">
                <form action="<?= url('/logout') ?>" method="POST" class="resend-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <button type="submit" class="resend-btn" style="color: var(--text-500); font-weight: normal;">Log out</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const form = document.getElementById('otp-form');
        const inputs = Array.from(form.querySelectorAll('.otp-field'));

        inputs.forEach((input, index) => {
            // Move focus on keydown/input
            input.addEventListener('input', (e) => {
                const val = e.target.value;
                if (val.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            // Handle pasting of full 6 digit code
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').trim();
                if (/^[0-9]{6}$/.test(pasteData)) {
                    for (let i = 0; i < inputs.length; i++) {
                        inputs[i].value = pasteData[i];
                    }
                    inputs[inputs.length - 1].focus();
                }
            });
        });

        // Cooldown timer handler
        const resendBtn = document.getElementById('resend-btn');
        const cooldownTimer = document.getElementById('cooldown-timer');
        
        let cooldownTime = 0;
        
        resendBtn.addEventListener('click', () => {
            setTimeout(() => {
                resendBtn.disabled = true;
            }, 50);
        });
    </script>
</body>
</html>
