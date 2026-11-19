<?php

session_start();

if (isset($_SESSION['user_login'])) {
    include('validation.php');
    include('validation_profile.php');
    include('includes/temp/header.php');
    include('includes/db/db.php');
    $activePage = 'profile';
    include('includes/temp/sidebar.php');


    $page = "All";

    if (isset($_GET["page"])) {
        $page = $_GET["page"];
    }

    if ($page == "All") {

        $stmt = $connect->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute(array($_SESSION['user_login']));
        $result = $stmt->fetch();

        $user_id = $result['user_id'];

        // حساب متوسط التقييمات وعددها للمستخدم الحالي
        $stmtRating = $connect->prepare("SELECT AVG(rating) AS avg_rating, COUNT(review_id) AS total_reviews FROM reviews WHERE reviewed_user_id = ?");
        $stmtRating->execute(array($user_id));
        $ratingData = $stmtRating->fetch();
        $avgRating = ($ratingData && $ratingData['total_reviews'] > 0) ? number_format($ratingData['avg_rating'], 1) : '0';
        $totalReviews = ($ratingData && $ratingData['total_reviews'] > 0) ? (int)$ratingData['total_reviews'] : 0;

?>


        <!-- ===== واجهة My Profile الثابتة (عرض فقط) ===== -->
    

        <main class="dashboard-main has-home-sidebar">
            <section class="dashboard-content profile-page">

                <!-- دفاية الهيدر: بانر + أفاتار + اسم + بايو قصير -->
           

                <div class="dashboard-section profile-header">

                    <div class="profile-banner-wrap">
                        <img class="profile-banner" src="includes/assets/img/banner-profile.jpg" alt="">
                        <div class="profile-banner-fade"></div>
                    </div>

                    <!-- الأفاتار نص على البانر ونص على الدفاية، والاسم جنبه -->
                    <div class="profile-summary">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar" role="img" aria-label="Profile picture">
                                <img
                                    src="uploads/<?php echo (!empty($result['img_profile']) && file_exists('uploads/' . $result['img_profile'])) ? htmlspecialchars($result['img_profile']) : 'default.jpg'; ?>"
                                    alt="Profile">
                            </div>
                        </div>

                        <div class="profile-info">
                            <div class="profile-name-row">
                                <h1 class="profile-name-text"><?php echo htmlspecialchars($result['username']); ?></h1>
                                <span class="profile-rating">
                                    ★ <?php echo $avgRating; ?>
                                    (<?php echo $totalReviews; ?>)
                                </span>
                            </div>

                            <p class="profile-city-text"><?php echo htmlspecialchars($result['city']); ?></p>

                            <p class="profile-header-bio"><?php echo htmlspecialchars($result["bio"]); ?></p>
                        </div>

                        <div class="profile-edit-actions">
                            <a class="btn-primary profile-btn-edit" href="profile.php?page=edit&user_id=<?php echo $user_id ?>">Edit Profile</a>
                        </div>
                    </div>
                </div>

                <!-- دفاية السكيلز الكبيرة: كل سكيل + وصف جوّه دفاية داخلية -->
          

                <section class="dashboard-section profile-panel profile-skills-board" aria-label="My Skills">
                    <h2>My Skills</h2>
                    <?php
                    $stmt = $connect->prepare('SELECT * FROM skills  WHERE user_id=?');
                    $stmt->execute(array($user_id));
                    $skill = $stmt->fetchall();
                    ?>
                    <div class="profile-skills-board-list">

                        <?php
                        foreach ($skill as $item) {
                        ?>
                            <article class="profile-skill-card">


                                <span class="profile-skill-name"><?php echo  $item["title"]  ?></span>
                                <p class="profile-skill-desc"><?php echo $item["description"] ?></p>
                            </article>

                        <?php  }  ?>


                    </div>
                </section>

                <!-- Work تحت دفاية السكيلز -->




            </section>
        </main>



    <?php

        } else if ($page == "edit") {
        // the id comes from the session, not from the URL, so nobody can edit another user's profile
        $stmt = $connect->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute(array($_SESSION['user_login']));
        $user_id = (int) $stmt->fetchColumn();

        $stmt = $connect->prepare('SELECT * FROM users WHERE user_id=?');
        $stmt->execute(array($user_id));
        $result = $stmt->fetch();

    ?>

        <main class="dashboard-main has-home-sidebar">
            <form method="post" action="profile.php?page=update&user_id=<?php echo $user_id; ?>" enctype="multipart/form-data">
                <section class="dashboard-content profile-page is-editing">
                                        <?php if (!empty($_SESSION['profile_errors'])): ?>
                        <!-- الأخطاء اللي رجعت من صفحة update -->
                        <div style="color: red; margin-bottom: 12px;">
                            <?php foreach ($_SESSION['profile_errors'] as $err): ?>
                                <div><?php echo htmlspecialchars($err); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php unset($_SESSION['profile_errors']); ?>
                    <?php endif; ?>

                    <div class="dashboard-section profile-header">

                        <div class="profile-banner-wrap">
                            <img class="profile-banner" id="edit-banner-preview" src="includes/assets/img/banner-profile.jpg" alt="">
                            <div class="profile-banner-fade"></div>
                            <label class="profile-banner-change" for="edit-banner-file">Change Banner</label>
                            <input class="image-change" id="edit-banner-file" type="file" accept="image/*" name="avatar">
                        </div>

                        <div class="profile-summary">
                            <div class="profile-avatar-wrap">
                                <div class="profile-avatar" role="img" aria-label="Profile picture">
                                    <img id="edit-avatar-preview" src="uploads/<?php echo (!empty($result['img_profile']) && file_exists('uploads/' . $result['img_profile'])) ? htmlspecialchars($result['img_profile']) : 'default.jpg'; ?>" alt="Profile">
                                </div>
                                <label class="profile-avatar-change" for="edit-avatar-file">Change<br>Photo</label>
                                <input class="image-change" id="edit-avatar-file" type="file" accept="image/*" name="img_profile">
                            </div>

                            <div class="profile-info">
                                <div class="profile-name-row">
                                    <input class="profile-name-input" id="edit-username" type="text" name="username" value="<?php echo htmlspecialchars($result['username']); ?>" aria-label="Username">
                                </div>

                                <div class="profile-city-edit">
                                    <?php
                                    $cities = [
                                        'Cairo', 'Giza', 'Alexandria', 'Qalyubia', 'Sharqia', 'Dakahlia',
                                        'Gharbia', 'Monufia', 'Beheira', 'Kafr El Sheikh', 'Damietta',
                                        'Port Said', 'Ismailia', 'Suez', 'North Sinai', 'South Sinai',
                                        'Fayoum', 'Beni Suef', 'Minya', 'Assiut', 'Sohag', 'Qena',
                                        'Luxor', 'Aswan', 'Red Sea', 'New Valley', 'Matrouh'
                                    ];
                                    $currentCity = $result['city'] ?? '';
                                    ?>
                                    <select class="profile-city-select" id="edit-city" aria-label="City" name="city">
                                        <option value="">Select City</option>
                                        <?php foreach ($cities as $city): ?>
                                            <?php 
                                                $isSelected = (strcasecmp(trim($currentCity), trim($city)) === 0) 
                                                              || (strcasecmp(trim($currentCity), 'Loxor') === 0 && $city === 'Luxor');
                                            ?>
                                            <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($city); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                                                <textarea class="profile-bio-input" id="edit-bio" rows="3" aria-label="Bio" placeholder="" name="bio"><?php echo htmlspecialchars($result['bio'] ?? ''); ?></textarea>
                            </div>

                            <div class="profile-edit-actions">
                                <a class="btn-cancel profile-btn-cancel" href="profile.php">Cancel</a>
                                <button type="submit" name="save_profile" class="btn-primary profile-btn-save">Save Profile</button>
                            </div>
                        </div>
                    </div>

                </section>
            </form>
        </main>

<?php

    } else if ($page == 'update') {

                // الـ id بنجيبه من الـ session مش من الرابط، عشان محدش يعدّل بروفايل حد تاني
        $stmt = $connect->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute(array($_SESSION['user_login']));
        $user_id = (int) $stmt->fetchColumn();

                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // نفحص البيانات والصورة مرة واحدة
            $check = val_validate_profile_update($_POST, $_FILES, $connect, $user_id);

            if (!$check['valid']) {
                // فيه أخطاء: نحفظها ونرجّع المستخدم لصفحة التعديل من غير ما نحفظ حاجة
                $_SESSION['profile_errors'] = array_values($check['errors']);
                header('Location: profile.php?page=edit');   // غيّريه لرابط صفحة التعديل عندك
                exit();
            }

            // القيم بعد الفحص والتنضيف
            $username = $check['data']['username'];
            $city     = $check['data']['city'];
            $bio      = $check['data']['bio'];

            try {
                if ($check['image'] !== null) {
                    // فيه صورة جديدة سليمة: اسمها عشوائي
                    $new_image_name = $check['image']['new_name'];

                    // ننقل الصورة لمجلد uploads، ولو فشل النقل نرجّع خطأ
                    $img_path = 'uploads/' . $new_image_name;
                    if (!move_uploaded_file($_FILES['img_profile']['tmp_name'], $img_path)) {
                        $_SESSION['profile_errors'] = array('Could not save the image. Please try again.');
                        header('Location: profile.php?page=edit');   // نفس رابط صفحة التعديل
                        exit();
                    }
                    @chmod($img_path, 0664);

                    $stmt = $connect->prepare("UPDATE users SET username = ?, city = ?, bio = ?, img_profile = ? WHERE user_id = ?");
                    $stmt->execute(array($username, $city, $bio, $new_image_name, $user_id));
                } else {
                    // مفيش صورة جديدة: نعدّل البيانات ونسيب الصورة القديمة
                    $stmt = $connect->prepare("UPDATE users SET username = ?, city = ?, bio = ? WHERE user_id = ?");
                    $stmt->execute(array($username, $city, $bio, $user_id));
                }
            } catch (PDOException $e) {
                // كود 23000 معناه إن الداتابيز رفضت الاسم لأنه مكرر
                if ($e->getCode() == 23000) {
                    // لو الصورة اتنقلت قبل الخطأ نمسحها عشان ماتفضلش متبعترة
                    if (isset($new_image_name)) {
                        @unlink('uploads/' . $new_image_name);
                    }
                    $_SESSION['profile_errors'] = array('This username is already taken.');
                    header('Location: profile.php?page=edit');   // نفس رابط صفحة التعديل
                    exit();
                }
                throw $e;   // أي خطأ تاني مانخبيهوش
            }

            header("Location: profile.php");
            exit();
        }
    }
    include('includes/temp/footer.php');
} else {
    // لو مش مسجل دخول، حوله لصفحة اللوجين
    $_SESSION['error_login'] = 'You must be logged in as a user to access this page';
    header('location: login.php');
    exit();
}
?>