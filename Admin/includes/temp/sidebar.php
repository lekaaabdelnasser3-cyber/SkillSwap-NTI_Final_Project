<?php
/**
 * Admin sidebar — نفس شريط Skills. + عنوان الصفحة زي صفحات اليوزر
 */
if (!isset($activePage)) {
    $activePage = '';
}

if (!isset($pageTitle) || $pageTitle === '') {
    $pageTitles = array(
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'skills' => 'Skills',
        'requests' => 'Requests',
    );
    $pageTitle = isset($pageTitles[$activePage]) ? $pageTitles[$activePage] : '';
}
?>

<header class="app-topbar" aria-label="Admin header">
    <a href="dashboard.php" class="home-logo">Skills.</a>
    <?php if ($pageTitle !== ''): ?>
        <h1 class="app-page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
    <?php endif; ?>
</header>

<div class="home-rail">
    <aside class="dashboard-sidebar home-sidebar" aria-label="Admin menu">
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link<?php echo $activePage === 'dashboard' ? ' active' : ''; ?>">
                <i class="bi bi-speedometer2" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="sidebar-link<?php echo $activePage === 'users' ? ' active' : ''; ?>">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>Users</span>
            </a>
            <a href="skills.php" class="sidebar-link<?php echo $activePage === 'skills' ? ' active' : ''; ?>">
                <i class="bi bi-lightbulb" aria-hidden="true"></i>
                <span>Skills</span>
            </a>
            <a href="Requests.php" class="sidebar-link<?php echo $activePage === 'requests' ? ' active' : ''; ?>">
                <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                <span>Requests</span>
            </a>
        </nav>

        <div class="sidebar-credits" aria-hidden="true"></div>

        <a href="/nti_project/logout.php" class="sidebar-logout">Logout</a>
    </aside>
</div>
