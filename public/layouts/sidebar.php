<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<?php // Determine allowed pages from header (fallback to role-based defaults)
$allowedPages = $allowedPages ?? (function_exists('getAllowedPagesForRole') ? getAllowedPagesForRole(currentUserRole()) : []);

?>


<div class="sidebar">

    <!-- Brand / logo header -->
    <div class="sidebar-brand">
        <span class="sidebar-brand-mark">B</span>
        <h1>BROWAVE AMS</h1>
        <p>Management Control</p>
    </div>

    <div class="sidebar-nav-scroll">
    <ul class="nav flex-column px-3">

        <li class="nav-item mb-1">
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" title="Dashboard">
                <i class="bi bi-speedometer2 nav-icon"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <?php $role = currentUserRole(); ?>

        <?php
            $managementPages = ['employees.php','departments.php','rooms.php','accommodations.php'];
            // only include management pages that are allowed for this user
            $managementAllowed = array_values(array_filter($managementPages, function($p) use ($allowedPages) { return in_array($p, $allowedPages, true); }));
            $isManagementActive = in_array($currentPage, $managementPages, true);
            $showManagement = count($managementAllowed) > 0;
        ?>
        <?php if ($showManagement): ?>
        <li class="nav-item mb-1">
            <a class="nav-link d-flex justify-content-between <?= $isManagementActive ? 'active' : '' ?>" data-bs-toggle="collapse" href="#managementMenu" role="button" aria-expanded="<?= $isManagementActive ? 'true' : 'false' ?>" aria-controls="managementMenu">
                <span><i class="bi bi-folder nav-icon"></i>&nbsp;&nbsp;&nbsp;&nbsp;<span>Management</span></span>
                <i class="bi bi-chevron-down chev" data-target="managementMenu"></i>
            </a>

            <div class="collapse <?= $isManagementActive ? 'show' : '' ?>" id="managementMenu">
                <ul class="nav flex-column ms-2">
                    <?php if (in_array('employees.php', $managementAllowed, true)): ?>
                    <li class="nav-item mb-1">
                        <a href="employees.php" class="nav-link <?= $currentPage === 'employees.php' ? 'active' : '' ?>" title="Employees">
                            <i class="bi bi-people nav-icon"></i>
                            <span>Employees</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (in_array('departments.php', $managementAllowed, true)): ?>
                    <li class="nav-item mb-1">
                        <a href="departments.php" class="nav-link <?= $currentPage === 'departments.php' ? 'active' : '' ?>" title="Departments">
                            <i class="bi bi-building nav-icon"></i>
                            <span>Departments</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (in_array('rooms.php', $managementAllowed, true)): ?>
                    <li class="nav-item mb-1">
                        <a href="rooms.php" class="nav-link <?= $currentPage === 'rooms.php' ? 'active' : '' ?>" title="Rooms">
                            <i class="bi bi-door-closed nav-icon"></i>
                            <span>Rooms</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (in_array('accommodations.php', $managementAllowed, true)): ?>
                    <li class="nav-item mb-1">
                        <a href="accommodations.php" class="nav-link <?= $currentPage === 'accommodations.php' ? 'active' : '' ?>" title="Accommodations">
                            <i class="bi bi-houses nav-icon"></i>
                            <span>Accommodations</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
        <?php endif; ?>

        <?php if (in_array('meals.php', $allowedPages, true)): ?>
        <li class="nav-item mb-1">
            <a href="meals.php" class="nav-link <?= $currentPage === 'meals.php' ? 'active' : '' ?>" title="Meals">
                <i class="bi bi-cup-hot nav-icon"></i>
                <span>Meals</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('users.php', $allowedPages, true)): ?>
        <li class="nav-item mb-1">
            <a href="users.php" class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" title="Users">
                <i class="bi bi-person-circle nav-icon"></i>
                <span>Users</span>
            </a>
        </li>
        <?php endif; ?>

        <?php
            $txPages = ['arrivals.php','departures.php'];
            $txAllowed = array_values(array_filter($txPages, function($p) use ($allowedPages) { return in_array($p, $allowedPages, true); }));
            $isTxActive = in_array($currentPage, $txPages, true);
        ?>

        <?php if (count($txAllowed) > 0): ?>
        <li class="nav-item mb-1">
            <a class="nav-link d-flex justify-content-between <?= $isTxActive ? 'active' : '' ?>" data-bs-toggle="collapse" href="#transactionsMenu" role="button" aria-expanded="<?= $isTxActive ? 'true' : 'false' ?>" aria-controls="transactionsMenu">
                <span><i class="bi bi-repeat nav-icon"></i>&nbsp;&nbsp;&nbsp;&nbsp;<span>Transactions</span></span>
                <i class="bi bi-chevron-down"></i>
            </a>

            <div class="collapse <?= $isTxActive ? 'show' : '' ?>" id="transactionsMenu">
                <ul class="nav flex-column ms-2">
                    <?php if (in_array('arrivals.php', $allowedPages, true)): ?>
                    <li class="nav-item mb-1">
                        <a href="arrivals.php" class="nav-link <?= $currentPage === 'arrivals.php' ? 'active' : '' ?>" title="Arrivals">
                            <i class="bi bi-box-arrow-in-right nav-icon"></i>
                            <span>Arrivals</span>
                            <span class="badge bg-secondary ms-2" id="badge-arrivals">0</span>
                        </a>
                    </li>

                    <?php endif; ?>

                    <?php if (in_array('departures.php', $allowedPages, true)): ?>
                    <li class="nav-item mb-1">
                        <a href="departures.php" class="nav-link <?= $currentPage === 'departures.php' ? 'active' : '' ?>" title="Departures">
                            <i class="bi bi-box-arrow-right nav-icon"></i>
                            <span>Departures</span>
                            <span class="badge bg-secondary ms-2" id="badge-departures">0</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
        <?php endif; ?>

        <?php if (in_array('room-assignments.php', $allowedPages, true)): ?>
        <li class="nav-item mb-1">
            <a href="room-assignments.php" class="nav-link <?= $currentPage === 'room-assignments.php' ? 'active' : '' ?>" title="Room Assignments">
                <i class="bi bi-arrow-repeat nav-icon"></i>
                <span>Room Assignments</span>
            </a>
        </li>
        <?php endif; ?>


    </ul>
    </div>
</div>
