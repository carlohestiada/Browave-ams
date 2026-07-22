<?php

require_once '../app/controllers/AuthController.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $auth = new AuthController();

    if ($auth->login($_POST['username'], $_POST['password'], $_POST['role'])) {

        header("Location: dashboard.php");
        exit;
    }

    $error = "Invalid username, password, or role.";
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>BROWAVE AMS — Login</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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
</head>
<body class="bg-background text-on-background min-h-screen flex items-center justify-center">
    
    <!-- Subtle grid pattern background -->
    <div class="fixed inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(circle, #003686 1px, transparent 1px); background-size: 28px 28px;"></div>

    <!-- Centered login card -->
    <div class="relative w-full max-w-sm mx-4">

        <!-- Brand header above card -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-primary mb-4 shadow-lg">
                <span class="material-symbols-outlined text-white text-[28px]">hotel</span>
            </div>
            <h1 class="text-display-sm font-bold text-primary tracking-tight">BROWAVE AMS</h1>
            <p class="text-label-md text-outline mt-1 uppercase tracking-widest">Management Control</p>
        </div>

        <!-- Card -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg overflow-hidden">

            <!-- Card top stripe -->
            <div class="h-1 bg-gradient-to-r from-primary via-secondary to-primary"></div>

            <div class="p-8">
                <h2 class="text-headline-md text-on-surface mb-1">Sign in</h2>
                <p class="text-body-sm text-on-surface-variant mb-6">Enter your credentials to access the dashboard.</p>

                <?php if ($error): ?>
                <div class="flex items-start gap-3 bg-error-container border border-red-200 rounded-lg px-4 py-3 mb-5">
                    <span class="material-symbols-outlined text-error text-[18px] mt-0.5 flex-shrink-0">error</span>
                    <p class="text-body-sm text-on-error-container font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5" autocomplete="off">

                    <!-- Role -->
                    <div>
                        <label class="block text-label-md text-on-surface mb-1.5 uppercase tracking-wide">Role</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-outline">
                                <span class="material-symbols-outlined text-[18px]">badge</span>
                            </span>
                            <select name="role" class="select-field" required>
                                <option value="Viewer">Viewer</option>
                                <option value="HR">HR</option>
                                <option value="Admin">Admin</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-outline">
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
                                class="input-field"
                                placeholder="Enter your username"
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
                                class="input-field"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"/>
                            <button type="button"
                                id="toggle-password"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-outline hover:text-primary transition-colors">
                                <span class="material-symbols-outlined text-[18px]" id="eye-icon">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                        class="w-full bg-primary text-on-primary font-semibold py-2.5 px-4 rounded-lg hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-2 text-body-md mt-2">
                        <span class="material-symbols-outlined text-[18px]">login</span>
                        Sign In
                    </button>

                </form>
            </div>
        </div>

        <!-- Footer note -->
        <p class="text-center text-label-md text-outline mt-6">
            Authorized access only &mdash; BROWAVE AMS &copy; <?= date('Y') ?>
        </p>
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