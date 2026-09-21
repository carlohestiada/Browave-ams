<?php
require_once '../app/config/csrf.php';
require_once '../app/controllers/AuthController.php';
header('Content-Type: text/html; charset=UTF-8');

$error = '';
$loginUsername = '';
$loginRole = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $loginUsername = trim((string) ($_POST['username'] ?? ''));
    $loginRole = (string) ($_POST['role'] ?? '');

    if (!csrfRequestIsValid()) {
        $error = 'Invalid security token. Please try again.';
    } else {

        $auth = new AuthController();

        if ($auth->login($loginUsername, $_POST['password'] ?? '', $loginRole)) {

            header("Location: dashboard.php");
            exit;
        }

        $error = "Invalid username, password, or role.";
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>BROWAVE AMS — Login</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "outline": "#737784",
                        "primary-fixed": "#dae2ff",
                        "surface-bright": "#f8f9ff",
                        "surface-variant": "#d9e3f4",
                        "on-background": "#121c28",
                        "surface-container-lowest": "#ffffff",
                        "error": "#ba1a1a",
                        "surface-container-highest": "#d9e3f4",
                        "background": "#f8f9ff",
                        "primary-fixed-dim": "#b1c5ff",
                        "on-surface": "#121c28",
                        "surface-container-low": "#eef4ff",
                        "on-error": "#ffffff",
                        "primary-container": "#094cb2",
                        "on-surface-variant": "#434653",
                        "surface-container": "#e5eeff",
                        "on-secondary": "#ffffff",
                        "error-container": "#ffdad6",
                        "surface": "#f8f9ff",
                        "primary": "#003686",
                        "inverse-primary": "#b1c5ff",
                        "secondary": "#00639d",
                        "on-secondary-container": "#00385c",
                        "outline-variant": "#c3c6d5",
                        "on-error-container": "#93000a",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#b0c5ff",
                        "tertiary": "#393b3c",
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px"
                    },
                    fontFamily: {
                        body: ["Inter"],
                    },
                    fontSize: {
                        "body-md": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                        "body-sm": ["13px", { lineHeight: "18px", fontWeight: "400" }],
                        "label-md": ["12px", { lineHeight: "16px", letterSpacing: "0.02em", fontWeight: "600" }],
                        "headline-md": ["24px", { lineHeight: "32px", letterSpacing: "-0.01em", fontWeight: "600" }],
                        "display-sm": ["28px", { lineHeight: "36px", letterSpacing: "-0.02em", fontWeight: "700" }],
                    }
                },
            },
        }
    </script>
    <style>
        /* ---------------------------------------------
           Login — light enterprise scene
           Scoped to this page only, no shared classes touched.
           --------------------------------------------- */
        @keyframes loginRise {
            from { opacity: 0; transform: translateY(14px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @media (prefers-reduced-motion: reduce) {
            .login-rise { animation: none !important; }
        }

        .login-scene {
            min-height: 100vh;
            position: relative;
            background:
                radial-gradient(circle at 15% 15%, rgba(177, 197, 255, 0.35), transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(0, 99, 157, 0.12), transparent 50%),
                linear-gradient(160deg, #eef4ff 0%, #f8f9ff 55%, #e5eeff 100%);
        }

        .login-topbar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            padding: 24px 32px;
        }

        .login-rise {
            animation: loginRise 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid rgba(195, 198, 213, 0.5);
            border-radius: 28px;
            box-shadow:
                0 30px 60px -20px rgba(0, 54, 134, 0.16),
                0 4px 12px rgba(18, 28, 40, 0.04);
            width: 100%;
        }

        .login-icon-badge {
            background: linear-gradient(135deg, #003686, #00639d);
            box-shadow: 0 12px 26px -8px rgba(0, 54, 134, 0.45);
        }

        .login-divider {
            border-top: 1px solid #eef1f6;
        }

        .login-field {
            width: 100%;
            background: #f8f9ff;
            border: 1px solid #c3c6d5;
            border-radius: 0.75rem;
            padding: 11px 14px 11px 42px;
            font-size: 14px;
            line-height: 20px;
            color: #121c28;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
            outline: none;
        }
        .login-field::placeholder { color: #9aa0af; }
        .login-field:focus {
            background: #ffffff;
            border-color: #003686;
            box-shadow: 0 0 0 3px rgba(0, 54, 134, 0.1);
        }

        select.login-field {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 38px;
            cursor: pointer;
        }

        .login-submit {
            background: linear-gradient(120deg, #003686, #00639d);
            box-shadow: 0 14px 30px -10px rgba(0, 54, 134, 0.45);
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        }
        .login-submit:hover {
            box-shadow: 0 18px 36px -10px rgba(0, 54, 134, 0.55);
            opacity: 0.96;
        }
        .login-submit:active { transform: scale(0.98); }
    </style>
</head>
<body>

    <div class="login-scene flex items-center justify-center px-4 py-10">

        <div class="relative w-full max-w-sm login-rise">

            <div class="login-card p-8">

                <!-- Brand header -->
                <div class="text-center mb-6">
                    <div class="login-icon-badge inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4">
                        <span class="material-symbols-outlined text-white text-[26px]">bed</span>
                    </div>
                    <h1 class="text-display-sm font-bold text-on-surface tracking-tight">BROWAVE AMS</h1>
                    <p class="text-label-md text-outline mt-1.5 uppercase tracking-widest">Management Control</p>
                </div>

                <div class="login-divider mb-6"></div>

                <h2 class="text-headline-md text-on-surface mb-1">Sign in</h2>
                <p class="text-body-sm text-on-surface-variant mb-6">Enter your credentials to continue.</p>

                <?php if ($error): ?>
                <div class="flex items-start gap-3 bg-error-container border border-red-200 rounded-xl px-4 py-3 mb-5">
                    <span class="material-symbols-outlined text-error text-[18px] mt-0.5 flex-shrink-0">error</span>
                    <p class="text-body-sm text-on-error-container font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Role -->
                    <div>
                        <label class="block text-label-md text-on-surface mb-1.5 uppercase tracking-wide">Role</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-outline">
                                <span class="material-symbols-outlined text-[18px]">badge</span>
                            </span>
                            <select name="role" class="login-field" required>
                                <option value="" disabled <?= $loginRole === '' ? 'selected' : '' ?>>Select your role</option>
                                <option value="Admin" <?= $loginRole === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="HR" <?= $loginRole === 'HR' ? 'selected' : '' ?>>HR</option>
                                <option value="Viewer" <?= $loginRole === 'Viewer' ? 'selected' : '' ?>>Viewer</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-outline">
                                <span class="material-symbols-outlined text-[18px]">expand_more</span>
                            </span>
                        </div>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-label-md text-on-surface mb-1.5 uppercase tracking-wide">Username</label>
                        <div class="relative">
                            <input type="text"
                                name="username"
                                class="login-field"
                                placeholder="Enter your username"
                                value="<?= htmlspecialchars($loginUsername, ENT_QUOTES, 'UTF-8') ?>"
                                required
                                autocomplete="username"/>
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-label-md text-on-surface mb-1.5 uppercase tracking-wide">Password</label>
                        <div class="relative">
                            <input type="password"
                                id="password-input"
                                name="password"
                                class="login-field"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"/>
                            <button type="button"
                                id="toggle-password"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-outline hover:text-primary transition-colors">
                                <span class="material-symbols-outlined text-[18px]" id="eye-icon">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                        class="login-submit w-full text-white font-semibold py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 text-body-md mt-2">
                        <span class="material-symbols-outlined text-[18px]">login</span>
                        Sign In
                    </button>

                </form>

            </div>

            <!-- Footer note -->
            <p class="text-center text-label-md text-outline mt-6">
                Authorized access only &mdash; BROWAVE AMS &copy; <?= date('Y') ?>
            </p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const toggle = document.getElementById('toggle-password');
        const pwInput = document.getElementById('password-input');
        const eyeIcon = document.getElementById('eye-icon');

        toggle.addEventListener('click', () => {
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            eyeIcon.textContent = isHidden ? 'visibility_off' : 'visibility';
        });
    </script>

</body>
</html>