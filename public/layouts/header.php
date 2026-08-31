<?php
require_once dirname(__DIR__) . '/../app/config/csrf.php';
header('Content-Type: text/html; charset=UTF-8');

function isLoggedIn()
{
    return !empty($_SESSION['user_id']);
}

function currentUserRole()
{
    return $_SESSION['role'] ?? 'Viewer';
}

function getAllowedPagesForRole($role)
{
    $pages = [
        'Admin' => [
            'dashboard.php',
            'employees.php',
            'departments.php',
            'rooms.php',
            'accommodations.php',
            'company-car.php',
            'room-assignments.php',
            'meals.php',
            'users.php',
            'guide.php',
            'trips.php'
        ],
        'HR' => [
            'dashboard.php',
            'employees.php',
            'departments.php',
            'rooms.php',
            'accommodations.php',
            'company-car.php',
            'room-assignments.php',
            'meals.php',
            'guide.php',
            'trips.php'
        ],
        'Viewer' => [
            'dashboard.php'
        ]
    ];

    return $pages[$role] ?? $pages['Viewer'];
}

$currentPage = basename($_SERVER['PHP_SELF']);

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$role = currentUserRole();
$allowedPages = getAllowedPagesForRole($role);

if (!in_array($currentPage, $allowedPages, true)) {
    http_response_code(403);
    echo "<!DOCTYPE html>\n<html>\n<head>\n    <title>403 Forbidden</title>\n    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n</head>\n<body class=\"bg-light\">\n<div class=\"container mt-5\">\n    <div class=\"alert alert-danger\">Access denied: your role does not permit viewing this page.</div>\n    <a href=\"dashboard.php\" class=\"btn btn-primary\">Go to Dashboard</a>\n</div>\n</body>\n</html>";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BROWAVE AMS</title>
    <link rel="icon" type="image/png" href="assets/img/browave-logo.png">

    <link rel="stylesheet" href="assets/css/bootstrap.min.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $.ajaxSetup({
            beforeSend: function (xhr, settings) {
                if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type || 'GET')) {
                    xhr.setRequestHeader('X-CSRF-Token', <?= json_encode(csrfToken()) ?>);
                }
            }
        });
    </script>
    <script src="assets/js/bootstrap.bundle.min.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/swal-utils.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/swal-utils.js') ?>"></script>
    <script src="assets/js/sidebar-utils.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/sidebar-utils.js') ?>"></script>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/style.css') ?>">
</head>
<body>

<nav class="ams-topbar">
    <div class="container-fluid">
            <div class="ams-topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>
        </div>

        <?php
        $role = currentUserRole();
        $username = $_SESSION['username'] ?? '';
        $displayUsername = $username !== '' ? $username : 'User';
        ?>

        <div class="ams-topbar-right">
            <div class="dropdown ams-user-dropdown">
                <button class="ams-user-trigger" type="button" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="ams-user-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                    <span class="ams-user-summary">
                        <span class="ams-user-name"><?= htmlspecialchars($displayUsername) ?></span>
                        <span class="ams-user-role"><?= htmlspecialchars($role) ?></span>
                    </span>
                    <i class="bi bi-chevron-down ams-user-chevron" aria-hidden="true"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end ams-user-menu" aria-labelledby="userProfileDropdown">
                    <div class="ams-user-menu-details">
                        <span class="ams-user-menu-label">Username</span>
                        <span class="ams-user-menu-value"><?= htmlspecialchars($displayUsername) ?></span>
                    </div>
                    <div class="ams-user-menu-details">
                        <span class="ams-user-menu-label">Role</span>
                        <span class="ams-user-menu-value"><?= htmlspecialchars($role) ?></span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <form id="logout-form" method="POST" action="logout.php" class="m-0">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="dropdown-item ams-user-logout">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const root = document.documentElement;

        // Capture original computed widths so we can animate back and forth
        const defaultSidebarWidth = '250px';
        const defaultCollapsedWidth = '0px';
        const computedStyles = getComputedStyle(root);
        const originalSidebarWidth = (computedStyles.getPropertyValue('--sidebar-width') || defaultSidebarWidth).trim() || defaultSidebarWidth;
        const collapsedSidebarWidth = (computedStyles.getPropertyValue('--sidebar-width-collapsed') || defaultCollapsedWidth).trim() || defaultCollapsedWidth;

        // Ensure the root CSS variables are set and valid
        root.style.setProperty('--sidebar-width', originalSidebarWidth);
        root.style.setProperty('--sidebar-width-collapsed', collapsedSidebarWidth);

        // Check localStorage for saved state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

        const topbar = document.querySelector('.ams-topbar');
        const contentWrapper = document.querySelector('.content-wrapper');
        const footer = document.querySelector('.ams-footer');

        function updateSidebarWidth(collapsed) {
            const widthValue = collapsed ? collapsedSidebarWidth : originalSidebarWidth;
            root.style.setProperty('--sidebar-width', widthValue);
            sidebar.classList.toggle('collapsed', collapsed);
            if (topbar) topbar.classList.toggle('collapsed', collapsed);
            if (contentWrapper) contentWrapper.classList.toggle('collapsed', collapsed);
            if (footer) footer.classList.toggle('collapsed', collapsed);
        }

        // Apply saved state
        updateSidebarWidth(isCollapsed);

        // Toggle sidebar on button click
        sidebarToggle.addEventListener('click', function() {
            const collapsed = sidebar.classList.contains('collapsed');
            updateSidebarWidth(!collapsed);

            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', !collapsed);
        });
    });
</script>
