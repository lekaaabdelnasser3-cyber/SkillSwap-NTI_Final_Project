<?php


if (!isset($activePage)) {
    $activePage = '';
}

if (!isset($pageTitle) || $pageTitle === '') {
    $pageTitles = array(
        'home' => 'Home',
        'profile' => 'My Profile',
        'chat' => 'Chat',
        'requests' => 'My Requests',
        'skills' => 'My Skills',
    );
    $pageTitle = isset($pageTitles[$activePage]) ? $pageTitles[$activePage] : '';
}
?>

<!-- شريط علوي: اسم الموقع على الشمال + عنوان الصفحة جنبه -->
<header class="app-topbar" aria-label="Site header">
    <a href="/nti_project/index.php" class="home-logo">Skills.</a>
    <?php if ($pageTitle !== ''): ?>
        <h1 class="app-page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
    <?php endif; ?>
</header>

<!-- المينيو ثابت شمال تحت اللوجو -->
<div class="home-rail">
    <aside class="dashboard-sidebar home-sidebar" aria-label="Main menu">
        <nav class="sidebar-nav">
            <a
                href="/nti_project/index.php"
                class="sidebar-link<?php echo $activePage === 'home' ? ' active' : ''; ?>"
            >
                <i class="bi bi-house-door" aria-hidden="true"></i>
                <span>Home</span>
            </a>
            <a
                href="/nti_project/profile.php"
                class="sidebar-link<?php echo $activePage === 'profile' ? ' active' : ''; ?>"
            >
                <i class="bi bi-person" aria-hidden="true"></i>
                <span>MyProfile</span>
            </a>
            <a
                href="chat.php"
                class="sidebar-link<?php echo $activePage === 'chat' ? ' active' : ''; ?>"
            >
                <i class="bi bi-chat-dots" aria-hidden="true"></i>
                <span>Chat</span>
            </a>
            <a
                href="MyRequests.php"
                class="sidebar-link<?php echo $activePage === 'requests' ? ' active' : ''; ?>"
            >
                <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                <span>My Requests</span>
            </a>
            <a
                href="MySkills.php"
                class="sidebar-link<?php echo $activePage === 'skills' ? ' active' : ''; ?>"
            >
                <i class="bi bi-lightbulb" aria-hidden="true"></i>
                <span>My Skills</span>
            </a>
            
        </nav>

        <div class="sidebar-credits" aria-hidden="true"></div>

        <a href="/nti_project/logout.php" class="sidebar-logout">Logout</a>
    </aside>
</div>
