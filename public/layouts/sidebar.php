<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<?php // Determine allowed pages from header (fallback to role-based defaults)
$allowedPages = $allowedPages ?? (function_exists('getAllowedPagesForRole') ? getAllowedPagesForRole(currentUserRole()) : []);

?>

<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">

<div class="sidebar">

    <!-- Brand / logo header -->
    <div class="sidebar-brand">
        <span class="sidebar-brand-mark">B</span>
        <h1>BROWAVE AMS</h1>
        <p>Management Control</p>
    </div>

    <div class="sidebar-nav-scroll">
        <ul class="nav flex-column px-3">

            <li class="nav-group-label">Overview</li>

            <li class="nav-item mb-1">
                <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" title="Dashboard">
                    <i class="bi bi-speedometer2 nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php $role = currentUserRole(); ?>

            <?php
            $managementPages = ['employees.php', 'departments.php', 'rooms.php', 'accommodations.php', 'company-car.php'];
            // only include management pages that are allowed for this user
            $managementAllowed = array_values(array_filter($managementPages, function ($p) use ($allowedPages) {
                return in_array($p, $allowedPages, true);
            }));
            $isManagementActive = in_array($currentPage, $managementPages, true);
            $showManagement = count($managementAllowed) > 0;
            ?>

            <li class="nav-group-label">Modules</li>

            <!-- MANAGEMENT SIDEBAR MODULE -->
            <?php if ($showManagement): ?>
                <li class="nav-item mb-1 nav-group">
                    <a class="nav-link nav-link-toggle d-flex justify-content-between <?= $isManagementActive ? 'active' : '' ?>" data-bs-toggle="collapse" href="#managementMenu" role="button" aria-expanded="<?= $isManagementActive ? 'true' : 'false' ?>" aria-controls="managementMenu">
                        <i class="bi bi-folder nav-icon"></i><span>Management</span>
                        <i class="bi bi-chevron-down chev" data-target="managementMenu"></i>
                    </a>

                    <div class="collapse <?= $isManagementActive ? 'show' : '' ?>" id="managementMenu">
                        <ul class="nav flex-column nav-subgroup">
                            <!-- EMPLOYEE SIDEBAR MODULE -->
                            <?php if (in_array('employees.php', $managementAllowed, true)): ?>
                                <li class="nav-item mb-1">
                                    <a href="employees.php" class="nav-link <?= $currentPage === 'employees.php' ? 'active' : '' ?>" title="Employees">
                                        <i class="bi bi-people nav-icon"></i>
                                        <span>Employees</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- DEPARTMENT SIDEBAR MODULE -->
                            <?php if (in_array('departments.php', $managementAllowed, true)): ?>
                                <li class="nav-item mb-1">
                                    <a href="departments.php" class="nav-link <?= $currentPage === 'departments.php' ? 'active' : '' ?>" title="Departments">
                                        <i class="bi bi-building nav-icon"></i>
                                        <span>Departments</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- ROOMS SIDEBAR MODULE -->
                            <?php if (in_array('rooms.php', $managementAllowed, true)): ?>
                                <li class="nav-item mb-1">
                                    <a href="rooms.php" class="nav-link <?= $currentPage === 'rooms.php' ? 'active' : '' ?>" title="Rooms">
                                        <i class="bi bi-door-closed nav-icon"></i>
                                        <span>Rooms</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- ACCOMMODATIONS SIDEBAR MODULE -->
                            <?php if (in_array('accommodations.php', $managementAllowed, true)): ?>
                                <li class="nav-item mb-1">
                                    <a href="accommodations.php" class="nav-link <?= $currentPage === 'accommodations.php' ? 'active' : '' ?>" title="Accommodations">
                                        <i class="bi bi-houses nav-icon"></i>
                                        <span>Accommodations</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- COMPANY CAR SIDEBAR MODULE -->
                            <?php if (in_array('company-car.php', $managementAllowed, true)): ?>
                                <li class="nav-item mb-1">
                                    <a href="company-car.php" class="nav-link <?= $currentPage === 'company-car.php' ? 'active' : '' ?>" title="Company Car">
                                        <i class="bi bi-car-front nav-icon"></i>
                                        <span>Company Car</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>

            <?php
            $opsPages = ['meals.php', 'room-assignments.php'];
            $opsAllowed = array_values(array_filter($opsPages, function ($p) use ($allowedPages) {
                return in_array($p, $allowedPages, true);
            }));
            ?>
            <?php if (count($opsAllowed) > 0): ?>
                <li class="nav-group-label">Operations</li>
            <?php endif; ?>

            <!-- MEALS SIDEBAR MODULE -->
            <?php if (in_array('meals.php', $allowedPages, true)): ?>
                <li class="nav-item mb-1">
                    <a href="meals.php" class="nav-link <?= $currentPage === 'meals.php' ? 'active' : '' ?>" title="Meals">
                        <i class="bi bi-cup-hot nav-icon"></i>
                        <span>Meals</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- ROOM ASSIGNMENTS SIDEBAR MODULE -->
            <?php if (in_array('room-assignments.php', $allowedPages, true)): ?>
                <li class="nav-item mb-1">
                    <a href="room-assignments.php" class="nav-link <?= $currentPage === 'room-assignments.php' ? 'active' : '' ?>" title="Room Assignments">
                        <i class="bi bi-arrow-repeat nav-icon"></i>
                        <span>Room Assignments</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array('trips.php', $allowedPages, true)): ?>
                <li class="nav-item mb-1">
                    <a href="trips.php" class="nav-link <?= $currentPage === 'trips.php' ? 'active' : '' ?>" title="Trips">
                        <i class="bi bi-airplane nav-icon"></i>
                        <span>Trips</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- SETTINGS SIDEBAR MODULE -->
            <?php
            $settingsPages = ['users.php', 'guide.php'];
            $settingsAllowed = array_values(array_filter($settingsPages, function ($p) use ($allowedPages, $role) {
                if ($p === 'users.php') {
                    return $role === 'Admin' && in_array($p, $allowedPages, true);
                }

                return in_array($p, $allowedPages, true);
            }));
            $isSettingsActive = in_array($currentPage, $settingsPages, true);
            $showSettings = true;
            ?>
            <?php if ($showSettings): ?>
                <li class="nav-item mb-1 nav-group">
                    <a class="nav-link nav-link-toggle d-flex justify-content-between <?= $isSettingsActive ? 'active' : '' ?>" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="<?= $isSettingsActive ? 'true' : 'false' ?>" aria-controls="settingsMenu">
                        <i class="bi bi-gear nav-icon"></i><span>System</span>
                        <i class="bi bi-chevron-down chev" data-target="settingsMenu"></i>
                    </a>

                    <div class="collapse <?= $isSettingsActive ? 'show' : '' ?>" id="settingsMenu">
                        <ul class="nav flex-column nav-subgroup">

                            <!-- USERS -->
                            <?php if ($role === 'Admin' && in_array('users.php', $settingsAllowed, true)): ?>
                                <li class="nav-item mb-1">
                                    <a href="users.php" class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" title="Users">
                                        <i class="bi bi-person-circle nav-icon"></i>
                                        <span>Users</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- GUIDE -->
                            <?php if (in_array('guide.php', $settingsAllowed, true)): ?>
                                <li class="nav-item mb-1">
                                    <a href="guide.php" class="nav-link <?= $currentPage === 'guide.php' ? 'active' : '' ?>" title="Guide">
                                        <i class="bi bi-journal-text nav-icon"></i>
                                        <span>Guide</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="nav-item mb-1">
                                <button type="button" class="nav-link sidebar-appearance-trigger" data-bs-toggle="modal" data-bs-target="#sidebarAppearanceModal" title="Sidebar Appearance">
                                    <i class="bi bi-palette nav-icon"></i>
                                    <span>Sidebar Appearance</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>

        </ul>

    </div>
    <!-- Sign Out -->
    <div class="sidebar-signout-wrap">
        <button type="button" class="sidebar-signout-btn" onclick="document.getElementById('logout-form').submit()">
            <i class="bi bi-box-arrow-right nav-icon"></i>
            <span>Sign Out</span>
        </button>
    </div>
</div>
</div>

<div class="modal fade" id="sidebarAppearanceModal" tabindex="-1" aria-labelledby="sidebarAppearanceTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content sidebar-appearance-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="sidebarAppearanceTitle">Sidebar Appearance</h5>
                    <p class="sidebar-appearance-subtitle">Personalize the navigation color</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="sidebar-appearance-layout">
                    <div>
                        <label class="sidebar-appearance-section-label">Preset Palettes</label>
                        <div class="sidebar-palette-grid">
                            <button type="button" class="sidebar-palette-card" data-sidebar-color="#0B3B82" data-palette-name="Browave Blue">
                                <span class="sidebar-palette-swatch" style="--palette-color:#0B3B82"></span><span>Browave Blue<small>#0B3B82</small></span>
                            </button>
                            <button type="button" class="sidebar-palette-card" data-sidebar-color="#075985" data-palette-name="Ocean">
                                <span class="sidebar-palette-swatch" style="--palette-color:#075985"></span><span>Ocean<small>#075985</small></span>
                            </button>
                            <button type="button" class="sidebar-palette-card" data-sidebar-color="#334155" data-palette-name="Slate">
                                <span class="sidebar-palette-swatch" style="--palette-color:#334155"></span><span>Slate<small>#334155</small></span>
                            </button>
                            <button type="button" class="sidebar-palette-card" data-sidebar-color="#047857" data-palette-name="Emerald">
                                <span class="sidebar-palette-swatch" style="--palette-color:#047857"></span><span>Emerald<small>#047857</small></span>
                            </button>
                            <button type="button" class="sidebar-palette-card" data-sidebar-color="#6D28D9" data-palette-name="Purple">
                                <span class="sidebar-palette-swatch" style="--palette-color:#6D28D9"></span><span>Purple<small>#6D28D9</small></span>
                            </button>
                            <button type="button" class="sidebar-palette-card" data-sidebar-color="#4338CA" data-palette-name="Indigo">
                                <span class="sidebar-palette-swatch" style="--palette-color:#4338CA"></span><span>Indigo<small>#4338CA</small></span>
                            </button>
                            <button type="button" class="sidebar-palette-card" data-sidebar-color="#BE123C" data-palette-name="Rose">
                                <span class="sidebar-palette-swatch" style="--palette-color:#BE123C"></span><span>Rose<small>#BE123C</small></span>
                            </button>
                        </div>

                        <label class="sidebar-appearance-section-label" for="sidebarCustomColor">Custom Color</label>
                        <div class="sidebar-custom-color-row">
                            <input type="color" id="sidebarCustomColorPicker" value="#f8f9ff" aria-label="Choose sidebar color">
                            <input type="text" id="sidebarCustomColor" class="form-control" value="#F8F9FF" maxlength="7" aria-describedby="sidebarColorHelp sidebarColorError">
                        </div>
                        <div id="sidebarColorHelp" class="form-text">Use a six-digit HEX color.</div>
                        <div id="sidebarColorError" class="invalid-feedback">Enter a valid HEX color, for example #0B3B82.</div>
                    </div>

                    <div>
                        <label class="sidebar-appearance-section-label">Preview</label>
                        <div class="sidebar-appearance-preview" id="sidebarAppearancePreview">
                            <div class="sidebar-preview-brand">BROWAVE AMS</div>
                            <div class="sidebar-preview-item is-active"><i class="bi bi-speedometer2"></i>Dashboard</div>
                            <div class="sidebar-preview-item"><i class="bi bi-people"></i>Employees</div>
                            <div class="sidebar-preview-item"><i class="bi bi-door-closed"></i>Rooms</div>
                            <div class="sidebar-preview-item"><i class="bi bi-airplane"></i>Trips</div>
                            <div class="sidebar-preview-signout"><i class="bi bi-box-arrow-right"></i>Sign Out</div>
                        </div>
                        <div class="sidebar-contrast-note" id="sidebarContrastNote"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link sidebar-reset-button" id="sidebarResetButton">Reset to Default</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="sidebarCancelButton">Cancel</button>
                    <button type="button" class="btn btn-primary" id="sidebarApplyButton">Apply</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const sidebar = document.querySelector('.sidebar');
        const defaultColor = '#F8F9FF';
        const storageKey = 'browaveSidebarColor:' + <?= json_encode((string) ($_SESSION['user_id'] ?? 'guest')) ?>;
        const colorPattern = /^#[0-9A-F]{6}$/i;
        const colorPicker = document.getElementById('sidebarCustomColorPicker');
        const colorInput = document.getElementById('sidebarCustomColor');
        const preview = document.getElementById('sidebarAppearancePreview');
        const contrastNote = document.getElementById('sidebarContrastNote');
        const errorInput = document.getElementById('sidebarColorError');
        let savedColor = localStorage.getItem(storageKey) || defaultColor;

        if (!colorPattern.test(savedColor)) savedColor = defaultColor;

        function getContrastText(color) {
            const channels = [1, 3, 5].map(function(start) {
                const channel = parseInt(color.slice(start, start + 2), 16) / 255;
                return channel <= 0.03928 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
            });
            const luminance = 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
            return luminance > 0.179 ? '#121C28' : '#FFFFFF';
        }

        function applySidebarColor(color) {
            if (!sidebar || !colorPattern.test(color)) return;
            const textColor = getContrastText(color);
            const isLight = textColor === '#121C28';
            const isDefault = color.toUpperCase() === defaultColor;
            sidebar.style.setProperty('--ams-surface', color);
            sidebar.style.setProperty('--ams-primary', isLight ? '#003686' : '#FFFFFF');
            sidebar.style.setProperty('--ams-primary-container', isLight ? '#094CB2' : 'rgba(255, 255, 255, 0.2)');
            sidebar.style.setProperty('--ams-on-surface', textColor);
            sidebar.style.setProperty('--ams-on-surface-variant', isLight ? '#434653' : 'rgba(255, 255, 255, 0.78)');
            sidebar.style.setProperty('--ams-surface-container', isDefault ? '#E5EEFF' : isLight ? 'rgba(0, 54, 134, 0.08)' : 'rgba(255, 255, 255, 0.12)');
            sidebar.style.setProperty('--ams-surface-container-low', isDefault ? '#EEF4FF' : isLight ? 'rgba(0, 54, 134, 0.12)' : 'rgba(255, 255, 255, 0.18)');
            sidebar.style.setProperty('--ams-outline-variant', isLight ? '#C3C6D5' : 'rgba(255, 255, 255, 0.24)');
            sidebar.style.setProperty('--ams-brand-mark-bg', isDefault || isLight ? '#003686' : 'rgba(255, 255, 255, 0.2)');
            sidebar.style.setProperty('--ams-status-surface', isDefault ? 'rgba(9, 76, 178, 0.06)' : isLight ? 'rgba(0, 54, 134, 0.08)' : 'rgba(255, 255, 255, 0.12)');
            sidebar.style.setProperty('--ams-status-border', isDefault ? 'rgba(9, 76, 178, 0.15)' : isLight ? 'rgba(0, 54, 134, 0.18)' : 'rgba(255, 255, 255, 0.24)');
            sidebar.style.setProperty('--sidebar-signout', isLight ? '#EF6461' : '#FFFFFF');
            sidebar.style.setProperty('--sidebar-signout-hover', isLight ? 'rgba(239, 100, 97, 0.10)' : 'rgba(255, 255, 255, 0.12)');
            if (preview) {
                preview.style.backgroundColor = color;
                preview.style.color = textColor;
                preview.style.setProperty('--preview-accent', isLight ? '#003686' : '#FFFFFF');
                preview.style.setProperty('--preview-active-bg', isDefault ? '#EEF4FF' : isLight ? 'rgba(0, 54, 134, 0.12)' : 'rgba(255, 255, 255, 0.18)');
                preview.style.setProperty('--preview-signout', isLight ? '#EF6461' : '#FFFFFF');
                preview.style.setProperty('--preview-signout-hover', isLight ? 'rgba(239, 100, 97, 0.10)' : 'rgba(255, 255, 255, 0.12)');
            }
            if (contrastNote) contrastNote.textContent = isLight ? 'Dark text applied for readability.' : 'Light text applied for readability.';
        }

        function setDraftColor(color) {
            const normalizedColor = color.toUpperCase();
            if (!colorPattern.test(normalizedColor)) {
                colorInput.classList.add('is-invalid');
                errorInput.style.display = 'block';
                return;
            }
            colorInput.classList.remove('is-invalid');
            errorInput.style.display = 'none';
            colorPicker.value = normalizedColor;
            colorInput.value = normalizedColor;
            document.querySelectorAll('.sidebar-palette-card').forEach(function(card) {
                card.classList.toggle('is-selected', card.dataset.sidebarColor.toUpperCase() === normalizedColor);
            });
            applySidebarColor(normalizedColor);
        }

        function initializeAppearance() {
            setDraftColor(savedColor);
        }

        applySidebarColor(savedColor);
        initializeAppearance();

        colorPicker.addEventListener('input', function() {
            setDraftColor(colorPicker.value);
        });
        colorInput.addEventListener('input', function() {
            setDraftColor(colorInput.value.trim());
        });
        document.querySelectorAll('.sidebar-palette-card').forEach(function(card) {
            card.addEventListener('click', function() {
                setDraftColor(card.dataset.sidebarColor);
            });
        });
        document.getElementById('sidebarResetButton').addEventListener('click', function() {
            setDraftColor(defaultColor);
        });
        document.getElementById('sidebarApplyButton').addEventListener('click', function() {
            const color = colorInput.value.trim().toUpperCase();
            if (!colorPattern.test(color)) {
                setDraftColor(color);
                colorInput.focus();
                return;
            }
            savedColor = color;
            localStorage.setItem(storageKey, savedColor);
            applySidebarColor(savedColor);
            bootstrap.Modal.getInstance(document.getElementById('sidebarAppearanceModal'))?.hide();
        });
        document.getElementById('sidebarAppearanceModal').addEventListener('show.bs.modal', initializeAppearance);
        document.getElementById('sidebarAppearanceModal').addEventListener('hidden.bs.modal', function() {
            setDraftColor(savedColor);
        });
    })();
</script>