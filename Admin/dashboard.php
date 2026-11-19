<?php

session_start();
if (isset($_SESSION["admin_login"])) {

    include("init.php");

    // 1. حساب عدد المستخدمين العاديين فقط (لكارد الإحصائيات)
    $Stmt = $connect->prepare("SELECT * FROM users WHERE role != 'admin'");
    $Stmt->execute();
    $userCount = $Stmt->rowCount();

    // 2. إحصائيات Skills
    $stmt = $connect->prepare("SELECT * FROM skills");
    $stmt->execute();
    $skillsCount = $stmt->rowCount();

    // 3. إحصائيات Requests
    $stmt = $connect->prepare("SELECT * FROM requests");
    $stmt->execute();
    $requestCount = $stmt->rowCount();
 
    // 4. جلب المستخدمين العاديين بحد أقصى 10 للجدول
    $stmt = $connect->prepare("SELECT * FROM users WHERE role != 'admin' ORDER BY user_id DESC LIMIT 10");
    $stmt->execute();
    $result = $stmt->fetchAll();

    $activePage = 'dashboard';
    include('includes/temp/sidebar.php');
    ?>

    <main class="dashboard-main has-home-sidebar">
            <section class="dashboard-content">

                <div class="content-header">
                    <h1>Admin Dashboard Overview</h1>
                    <p>Manage users, skills, and requests across the Skills community.</p>
                </div>


                <div class="stats-grid">

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $userCount; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>


                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $skillsCount ?></div>
                            <div class="stat-label">Total Skills</div>
                        </div>
                    </div>


                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M6.99 11L3 15l3.99 4v-3H14v-2H6.99v-3zM21 9l-3.99-4v3H10v2h7.01v3L21 9z" />
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php echo $requestCount ?></div>
                            <div class="stat-label">Total Requests</div>
                        </div>
                    </div>

                </div>


                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Users</h2>
                        <a href="users.php" class="view-all-link">View All</a>
                    </div>

                    <div class="table-responsive">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($result as $item) {

                                    ?>
                                    <tr>

                                        <td><?php echo $item['username']; ?></td>
                                        <td><?php echo $item['email']; ?></td>
                                        <td>
                                            <?php if ($item['status'] == 1): ?>
                                                <span class="badge badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php
                                }

                                ?>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
    </main>

    <?php
    // ===== Shared Footer =====
    include('includes/temp/footer.php');
} else {
    $_SESSION['error_login'] = 'You must be logged in as an admin to access this page';
    header("location:../login.php");
}
?>