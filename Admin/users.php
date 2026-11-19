<?php
session_start();
if (isset($_SESSION["admin_login"])) {
    include("init.php");
    include("validation/admin.php");

    $page = "All";

    if (isset($_GET["page"])) {
        $page = $_GET["page"];
    }

    if ($page == "All") {

        $search = val_search($_GET['search'] ?? '');  

        if (!empty($search)) {
            $searchWildcard = '%' . $search . '%';
            $stmt = $connect->prepare("SELECT * FROM users WHERE role != 'admin' AND (username LIKE ? OR email LIKE ? OR city LIKE ?) ORDER BY user_id DESC");
            $stmt->execute([$searchWildcard, $searchWildcard, $searchWildcard]);
        } else {
            $stmt = $connect->prepare("SELECT * FROM users WHERE role != 'admin' ");
            $stmt->execute();
        }
        $result = $stmt->fetchAll();

        $activePage = 'users';
        include('includes/temp/sidebar.php');
          ?>

        <main class="dashboard-main has-home-sidebar">
                <section class="dashboard-content">

                    <div class="content-header">
                        <h1>Users Management</h1>
                        <p>View, manage, and update members across the Skills community.</p>
                    </div>

                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>All Users</h2>
                            <?php
                            if (isset($_SESSION['success_message'])) {
                                echo '<div class="alert-success-right"><i class="bi bi-check-circle-fill"></i> <span>' . htmlspecialchars($_SESSION['success_message']) . '</span></div>';
                                unset($_SESSION['success_message']);
                            }
                            ?>
                        </div>

                        <!-- سيرش -->
                        <form method="GET" action="users.php" class="users-search-bar-form">
                            <div class="users-search-wrapper">
                                <input type="text" name="search" class="users-search-input" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                                <?php if (!empty($search)): ?>
                                    <a href="users.php" class="search-reset-btn" title="Clear search">&times;</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>City</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($result)): ?>
                                        <tr>
                                            <td colspan="5" class="table-empty-message">
                                                <?php if (!empty($search)): ?>
                                                    No users found matching "<strong><?php echo htmlspecialchars($search); ?></strong>". <a href="users.php" class="users-clear-search">Clear search</a>
                                                <?php else: ?>
                                                    No users found.
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        foreach ($result as $item) {
                                        ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($item['username']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($item['email']); ?></td>
                                                <td><?php echo htmlspecialchars($item['city']); ?></td>
                                                <td>
                                                    <?php if ($item['status'] == 1): ?>
                                                        <span class="badge badge-active">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-inactive">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <!-- زرار العرض -->
                                                        <a href="users.php?page=show&id=<?php echo $item['user_id']; ?>"
                                                            class="action-btn action-view" title="View">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <!-- زرار التعديل -->
                                                        <a href="users.php?page=edit&id=<?php echo $item['user_id']; ?>"
                                                            class="action-btn action-edit" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <!-- زرار الحذف -->
                                                        <a href="users.php?page=delete&id=<?php echo $item['user_id']; ?>"
                                                            class="action-btn action-delete" title="Delete"
                                                            onclick="return confirm('Are you sure you want to delete this user?');">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>
        </main>

         <?php

    } elseif ($page == 'show') {
        $user_id = val_int_id($_GET['id'] ?? 0);   // الـ id لازم يبقى رقم صحيح، غير كده بيبقى 0

        $stmt = $connect->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();

        $activePage = 'users';
        include('includes/temp/sidebar.php');
        ?>
        <main class="dashboard-main has-home-sidebar">
                <section class="dashboard-content">

                    <div class="content-header">
                        <h1>Users Management</h1>
                        <p>View, manage, and update members across the Skills community.</p>
                    </div>

                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>User Profile Details</h2>
                        </div>

                        <div class="table-responsive">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>User_id</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>City</th>
                                        <th>Birth_Date</th>
                                        <th>Status</th>
                                        <th>Created_at</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result): ?>
                                        <tr>
                                            <td><?php echo $result['user_id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($result['username']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($result['email']); ?></td>
                                            <td><?php echo htmlspecialchars($result['city']); ?></td>
                                            <td><?php echo htmlspecialchars($result['birth_date']); ?></td>
                                            <td>
                                                <?php if ($result['status'] == 1): ?>
                                                    <span class="badge badge-active">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($result['created_at']); ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="users.php" class="action-btn action-view" title="Back to Users">
                                                        <i class="fa fa-house"></i> Back
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="users-empty-cell">No user found with this ID.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>
        </main>
            <?php

    } elseif ($page == 'delete') {
        $user_id = val_int_id($_GET['id'] ?? 0);   
        $stmt = $connect->prepare('DELETE FROM users WHERE user_id = ?');
        $stmt->execute([$user_id]);

        $_SESSION['success_message'] = 'User deleted successfully.';
        header('location: users.php');
        exit();
    } elseif ($page == 'edit') {
                $user_id = val_int_id($_GET['id'] ?? 0);  

        $stmt = $connect->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute(array($user_id));
        $result = $stmt->fetch();

        if (!$result) {
            header('location: users.php');
            exit();
        }

        $cities = [

            'Cairo',
            'Giza',
            'Alexandria',
            'Qalyubia',
            'Sharqia',
            'Dakahlia',
            'Gharbia',
            'Monufia',
            'Beheira',
            'Kafr El Sheikh',
            'Damietta',
            'Port Said',
            'Ismailia',
            'Suez',
            'North Sinai',
            'South Sinai',
            'Fayoum',
            'Beni Suef',
            'Minya',
            'Assiut',
            'Sohag',
            'Qena',
            'Luxor',
            'Aswan',
            'Red Sea',
            'New Valley',
            'Matrouh',
            '10 ramdan',
            'Loxor',
            'Cario'

        ];
        $currentCity = $result['city'] ?? '';
        if (!empty($currentCity) && !in_array($currentCity, $cities)) {
            array_unshift($cities, $currentCity);
        }
        $activePage = 'users';
        include('includes/temp/sidebar.php');
         ?>

        <main class="dashboard-main has-home-sidebar">
                <section class="dashboard-content">

                    <div class="content-header">
                        <h1>Users Management</h1>
                        <p>View, manage, and update members across the Skills community.</p>
                    </div>

                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Edit User</h2>
                            <a href="users.php" class="back-to-users-link">
                                <i class="bi bi-arrow-left"></i> Back to Users
                            </a>
                        </div>

                        <?php if (!empty($_SESSION['edit_errors'])): ?>
                            <!-- عرض أخطاء الفحص اللي رجعت من صفحة update -->
                            <div style="color: red; margin-bottom: 12px;">
                                <?php foreach ($_SESSION['edit_errors'] as $err): ?>
                                    <div><?php echo htmlspecialchars($err); ?></div>
                                <?php endforeach; ?>
                            </div>
                            <?php unset($_SESSION['edit_errors']); ?> 
                        <?php endif; ?>
                        <form action="users.php?page=update&id=<?php echo $user_id; ?>" method="POST" class="edit-user-form">
                            <div class="edit-user-grid">
                            
                                <div class="edit-form-group">
                                    <label for="username"><i class="bi bi-person"></i> Username</label>
                                    <input type="text" id="username" name="username" class="edit-form-control"
                                        placeholder="Enter your username"
                                        value="<?php echo htmlspecialchars($result['username']); ?>" required>
                                </div>

                              
                                <div class="edit-form-group">
                                    <label for="email"><i class="bi bi-envelope"></i> Email</label>
                                    <input type="email" id="email" name="email" class="edit-form-control"
                                        placeholder="example@email.com"
                                        value="<?php echo htmlspecialchars($result['email']); ?>" required>
                                </div>

                                
                                <div class="edit-form-group">
                                    <label for="dob"><i class="bi bi-calendar3"></i> Date of Birth</label>
                                    <input type="date" id="dob" name="dob" class="edit-form-control"
                                        value="<?php echo htmlspecialchars($result['birth_date']); ?>">
                                </div>

                               
                                <div class="edit-form-group">
                                    <label for="city"><i class="bi bi-geo-alt"></i> City</label>
                                    <select name="city" id="city" class="edit-form-control edit-form-select">
                                        <?php foreach ($cities as $c): ?>
                                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo (strcasecmp($result['city'], $c) === 0) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($c); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            
                                <div class="edit-form-group">
                                    <label for="status"><i class="bi bi-toggle-on"></i> Status</label>
                                    <select name="status" id="status" class="edit-form-control edit-form-select">
                                        <option value="1" <?php echo ($result['status'] == 1) ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo ($result['status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>

                              
                                <div class="edit-form-group">
                                    <label for="role"><i class="bi bi-shield"></i> Role</label>
                                    <select name="role" id="role" class="edit-form-control edit-form-select">
                                        <option value="user" <?php echo (strtolower($result['role']) == 'user') ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo (strtolower($result['role']) == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>

                            <div class="edit-user-buttons">
                                <a href="users.php" class="btn-cancel">Cancel</a>
                                <button type="submit" class="btn-update-user">
                                    <i class="bi bi-check2"></i> Update User
                                </button>
                            </div>
                        </form>
                    </div>

                </section>
        </main>
                   <?php

    } elseif ($page == 'update') {
        $user_id = val_int_id($_GET['id'] ?? 0); 

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // بنفحص كل الحقول المبعوتة من فورم التعديل
            $check = val_validate_user_update($_POST, $user_id);


            // فحص إن اسم المستخدم مش مستخدم عند مستخدم تاني (بس لو الاسم نفسه سليم)
            if (!isset($check['errors']['username'])) {
                $dupError = val_username_unique($connect, $check['data']['username'], $user_id);
                if ($dupError !== '') {
                    $check['errors']['username'] = $dupError;   // بنضيف الخطأ لباقي الأخطاء
                    $check['valid'] = false;                    // ونخلي الفحص يفشل
                }
            }
            // فحص إن الإيميل مش مستخدم عند مستخدم تاني (بس لو الإيميل سليم)
            if (!isset($check['errors']['email'])) {
                $emailDup = val_email_unique($connect, $check['data']['email'], $user_id);
                if ($emailDup !== '') {
                    $check['errors']['email'] = $emailDup;
                    $check['valid'] = false;
                }
            }
            if (!$check['valid']) {
               
                $_SESSION['edit_errors'] = array_values($check['errors']);
           
                header('location: users.php?page=edit&id=' . $user_id);
                exit();
            }

            $username   = $check['data']['username'];
            $email      = $check['data']['email'];
            $birth_date = $check['data']['dob'];
            $status     = $check['data']['status'];
            $role       = $check['data']['role'];
            $city       = $check['data']['city'];

            $stmt = $connect->prepare("UPDATE users SET username = ?, email = ?, birth_date = ?, status = ?, role = ?, city = ? WHERE user_id = ?");
            $stmt->execute(array($username, $email, $birth_date, $status, $role, $city, $user_id));

            $_SESSION['success_message'] = 'User updated successfully.';
            header('location: users.php');
            exit();
        }
    }

    include('includes/temp/footer.php');
} else {
    $_SESSION['error_login'] = 'You must be logged in as an admin to access this page';
    header("location:../login.php");
    exit();
}
?>