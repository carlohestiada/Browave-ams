<?php
session_start();

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
            'room-assignments.php',
            'meals.php',
            'reports.php',
            'users.php',
            'arrivals.php',
            'departures.php'
        ],
        'HR' => [
            'dashboard.php',
            'employees.php',
            'departments.php',
            'meals.php',
            'room-assignments.php',
            'reports.php',
            'arrivals.php',
            'departures.php'
        ],
        'Viewer' => [
            'dashboard.php',
            'reports.php'
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
    <title>BROWAVE AMS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/swal-utils.js"></script>
    <script src="assets/js/sidebar-utils.js"></script>

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="ams-topbar">
    <div class="container-fluid">
            <div class="ams-topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>
        </div>

        <?php $role = currentUserRole(); $username = $_SESSION['username'] ?? ''; ?>

        <div class="ams-topbar-right">
            <?php if (!empty($username)): ?>
                <div class="ams-user-block">
                    <p class="ams-user-role">Role: <?= htmlspecialchars($role) ?></p>
                </div>
                <div class="ams-divider"></div>
            <?php else: ?>
                <span class="badge bg-light text-primary me-2">Role: <?= htmlspecialchars($role) ?></span>
            <?php endif; ?>

            <a href="logout.php" class="ams-logout-btn">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const root = document.documentElement;

        // Capture original computed widths so we can animate back and forth
        const originalSidebarWidth = getComputedStyle(root).getPropertyValue('--sidebar-width') || '250px';
        const collapsedSidebarWidth = getComputedStyle(root).getPropertyValue('--sidebar-width-collapsed') || '76px';

        // Check localStorage for saved state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

        function updateSidebarWidth(collapsed) {
            if (collapsed) {
                root.style.setProperty('--sidebar-width', collapsedSidebarWidth.trim());
                sidebar.classList.add('collapsed');
            } else {
                root.style.setProperty('--sidebar-width', originalSidebarWidth.trim());
                sidebar.classList.remove('collapsed');
            }
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