<?php

require_once __DIR__ . '/../app/config/csrf.php';
require_once __DIR__ . '/../app/config/database.php';

header('Content-Type: text/html; charset=UTF-8');

$roles = ['Admin', 'HR', 'Viewer'];
$username = '';
$selectedRole = 'Viewer';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $selectedRole = (string) ($_POST['role'] ?? '');

    if (!csrfRequestIsValid()) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } elseif ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (mb_strlen($username) > 50) {
        $errors[] = 'Username must be 50 characters or fewer.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($selectedRole, $roles, true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (!$errors) {
        try {
            $pdo = (new Database())->connect();
            $existingUser = $pdo->prepare('SELECT 1 FROM browave_ams.users WHERE username = ? LIMIT 1');
            $existingUser->execute([$username]);

            if ($existingUser->fetchColumn() !== false) {
                $errors[] = 'Username already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $insert = $pdo->prepare(
                    "INSERT INTO browave_ams.users
                    (username, password_hash, role, status)
                    VALUES (?, ?, ?, 'Active')"
                );
                $insert->execute([$username, $passwordHash, $selectedRole]);
                $success = true;
                $username = '';
                $selectedRole = 'Viewer';
            }
        } catch (PDOException $exception) {
            error_log('Registration database error: ' . $exception->getMessage());
            $errors[] = 'Registration could not be completed right now. Please try again later.';
        } catch (RuntimeException $exception) {
            error_log('Registration configuration error: ' . $exception->getMessage());
            $errors[] = 'Registration is temporarily unavailable. Please try again later.';
        }
    }
}

$loginUrl = '../public/login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BROWAVE AMS - Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="registration-shell">
        <section class="brand-panel" aria-label="BROWAVE AMS">
            <div class="brand-header">
                <div class="brand-mark" aria-hidden="true">B</div>
                <span>Browave / Operations platform</span>
            </div>
            <p class="eyebrow">Accommodation Management System</p>
            <h1>BROWAVE AMS</h1>
            <p class="brand-copy">Create access for the people who keep every stay organized.</p>
            <div class="brand-footer">
                <span class="status-dot" aria-hidden="true"></span>
                <span>Secure account provisioning</span>
            </div>
        </section>

        <section class="form-panel" aria-labelledby="registration-title">
            <div class="form-card">
                <div class="form-topline">
                    <span class="section-index">01</span>
                    <span class="form-topline-rule"></span>
                    <span>New account</span>
                </div>
                <div class="form-heading">
                <p class="eyebrow">Account access</p>
                <h2 id="registration-title">Register a user</h2>
                <p>Complete the details below to create an active AMS account.</p>
                </div>

            <?php if ($success): ?>
                <div class="alert alert-success" role="status">
                    Registration successful. You can now log in using your new account.
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="post" action="" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" maxlength="50" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required>

                    <label for="password">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="toggle-password" data-target="password" aria-label="Show password">Show</button>
                    </div>
                    <span class="field-hint">Use at least 8 characters.</span>

                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-field">
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">Show</button>
                    </div>

                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="" disabled <?= $selectedRole === '' ? 'selected' : '' ?>>Select a role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedRole === $role ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="field-hint">New accounts are automatically set to Active.</span>

                    <button class="primary-button" type="submit">Register user</button>
                </form>
            <?php endif; ?>

                <a class="back-link button-link" href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Back to Login</a>
            </div>
        </section>
    </main>
    <script src="js/registration.js"></script>
</body>
</html>
