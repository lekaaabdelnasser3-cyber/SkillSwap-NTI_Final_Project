<?php
session_start();

// التحقق من أن المستخدم مسجل دخول
if (isset($_SESSION['user_login'])) {
    include('includes/temp/header.php');
    include('includes/db/db.php');
    $activePage = 'home';
    include('includes/temp/sidebar.php');


    $page = "All";

    if (isset($_GET["page"])) {
        $page = $_GET["page"];
    }

    if ($page == "All") {

        // عرض رسائل التنبيه والنجاح إن وُجدت
         if (isset($_SESSION["message"])) {
                echo '<span class="form-errors">' . $_SESSION["message"] . '</span>';
                unset($_SESSION["message"]);
            }


        $stmt = $connect->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute(array($_SESSION['user_login']));
        $result = $stmt->fetch();

        $skillsStmt = $connect->prepare('SELECT title FROM skills WHERE user_id = ? ');
        $skillsStmt->execute(array($result['user_id']));
        $userSkills = $skillsStmt->fetchAll();

        // 3. حساب عدد الطلبات المقبولة للمستخدم الحالي
        $stmtrequest = $connect->prepare("SELECT * FROM requests WHERE status = 'accepted' AND receiver_id = ?");
        $stmtrequest->execute(array($result['user_id']));
        $skillsCount = $stmtrequest->rowCount();

        // 4. حساب متوسط تقييم المستخدم الحالي
        $stmtUserRating = $connect->prepare("SELECT AVG(rating) AS avg_rating, COUNT(review_id) AS total_reviews FROM reviews WHERE reviewed_user_id = ?");
        $stmtUserRating->execute(array($result['user_id']));
        $userRatingData = $stmtUserRating->fetch();
        $userAvgRating = ($userRatingData && $userRatingData['total_reviews'] > 0) ? number_format($userRatingData['avg_rating'], 1) : '0';

?>

        <!-- ===== واجهة الهوم ===== -->
        <main class="home-page has-home-sidebar">

            <div class="home-top-layout">
                <div class="home-main-column">

                    <!-- كارت المستخدم الحالي -->
                    <article class="home-user-card" aria-label="Your profile">
                        <div class="home-user-card-info">

                            <span class="home-user-card-avatar" aria-hidden="true">
                                <img src="uploads/<?php echo !empty($result['img_profile']) ? htmlspecialchars($result['img_profile']) : 'default.jpg'; ?>"
                                    alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            </span>

                            <div class="home-user-card-text">
                                <h2 class="home-user-card-name"><?php echo htmlspecialchars($result['username']); ?></h2>
                                <p class="home-user-card-city"><?php echo htmlspecialchars($result['city']); ?></p>
                                <div class="home-user-card-meta">
                                    <span class="home-user-card-rating">
                                        <i class="bi bi-star-fill" aria-hidden="true"></i> <?php echo $userAvgRating; ?>
                                    </span>
                                    <span class="home-user-card-people"><?php echo $skillsCount; ?> worked with</span>
                                </div>
                                <p class="home-user-card-bio"><?php echo htmlspecialchars($result['bio']); ?></p>

                                <!-- عرض المهارات (Offer فقط) ديناميكياً -->
                                <ul class="home-user-card-skills">
                                    <?php
                                    if (!empty($userSkills)) {
                                        foreach ($userSkills as $skill) {
                                            echo '<li>' . htmlspecialchars($skill['title']) . '</li>';
                                        }
                                    } else {
                                        echo '<li>No offers added</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <a class="home-user-card-btn" href="profile.php">View Profile</a>
                    </article>

                   
                    <div class="home-explore">
                        <div class="home-skills-grid">
                            <?php
                            // جلب خيارات الفلترة والترتيب
                            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
                            $selectedSkill = isset($_GET['skill']) ? trim($_GET['skill']) : '';
                            $min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0.0;

                            // بناء استعلام المستخدمين الديناميكي مع حساب التقييم ومتوسط النجوم
                            $sql = "
                                SELECT 
                                    users.*,
                                    COALESCE(AVG(reviews.rating), 0) AS avg_rating,
                                    COUNT(DISTINCT reviews.review_id) AS total_reviews,
                                    (SELECT COUNT(*) FROM requests WHERE requests.status = 'accepted' AND requests.receiver_id = users.user_id) AS accepted_count
                                FROM users
                                LEFT JOIN reviews ON reviews.reviewed_user_id = users.user_id
                            ";

                            $params = [$result['user_id']];
                            $whereClauses = ["users.user_id != ?", "users.role != 'admin'"];

                            // فلترة بمهارة واحدة من الـ select
                            if ($selectedSkill !== '') {
                                $sql .= " INNER JOIN skills AS filter_skills ON filter_skills.user_id = users.user_id ";
                                $whereClauses[] = "filter_skills.title = ?";
                                $params[] = $selectedSkill;
                            }

                            $sql .= " WHERE " . implode(' AND ', $whereClauses);
                            $sql .= " GROUP BY users.user_id ";

                            // فلترة بالحد الأدنى للتقييم
                            if ($min_rating > 0) {
                                $sql .= " HAVING avg_rating >= ? ";
                                $params[] = $min_rating;
                            }

                            // الترتيب: من الأعلى للأقل، أو من الأقل للأعلى، أو الأحدث
                            if ($sort === 'high_low') {
                                $sql .= " ORDER BY avg_rating DESC, users.user_id DESC ";
                            } elseif ($sort === 'low_high') {
                                $sql .= " ORDER BY avg_rating ASC, users.user_id DESC ";
                            } else {
                                $sql .= " ORDER BY users.user_id DESC ";
                            }

                            $stmtUsers = $connect->prepare($sql);
                            $stmtUsers->execute($params);
                            $allusers = $stmtUsers->fetchAll();

                            if (!empty($allusers)) {
                                foreach ($allusers as $items) {
                                    $acceptedCount = $items['accepted_count'] ?? 0;

                                    // جلب كل مهارات اليوزر لعرضها مجتمعة داخل الكارت
                                    $stmtSkillList = $connect->prepare("SELECT title FROM skills WHERE user_id = ?");
                                    $stmtSkillList->execute(array($items["user_id"]));
                                    $userSkillsList = $stmtSkillList->fetchAll();

                                    $itemAvgRating = ($items['total_reviews'] > 0) ? number_format($items['avg_rating'], 1) : '0';
                                ?>

                                    <a class="home-skill-card-link" href="index.php?page=show&user_id=<?php echo $items['user_id']; ?>">
                                        <article class="home-skill-card">

                                            <div class="home-skill-owner">
                                                <span class="home-skill-avatar" aria-hidden="true">
                                                    <img src="uploads/<?php echo !empty($items['img_profile']) ? htmlspecialchars($items['img_profile']) : 'default.jpg'; ?>" alt="Profile">
                                                </span>
                                                <div class="home-skill-owner-text">
                                                    <div class="home-skill-top-row">
                                                        <span class="home-skill-owner-name"><?php echo htmlspecialchars($items['username']); ?></span>
                                                        <span class="home-skill-rating"><i class="bi bi-star-fill"></i> <?php echo $itemAvgRating; ?></span>
                                                        <span class="home-skill-people"><?php echo $acceptedCount; ?> worked with</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <ul class="home-user-card-skills">
                                                <?php
                                                if (!empty($userSkillsList)) {
                                                    foreach ($userSkillsList as $sk) {
                                                        echo '<li>' . htmlspecialchars($sk['title']) . '</li>';
                                                    }
                                                } else {
                                                    echo '<li>No skills yet</li>';
                                                }
                                                ?>
                                            </ul>

                                        </article>
                                    </a>

                                <?php
                                }
                            } else {
                                echo '<p class="profile-empty-state" style="padding: 40px; text-align: center; color: #888; width: 100%;">No members found matching your filters.</p>';
                            }
                            ?>
                        </div>
                    </div>

                </div>

                <!-- فورم الفلترة الجانبي -->
                <form class="home-filter-form" method="get" action="index.php">
                    <aside class="home-filter-panel" aria-label="Filters">
                        <!-- 1. الترتيب (Sort by) -->
                        <div class="home-filter-group">
                            <span class="home-filter-label">Sort by</span>
                            <div class="home-sort-list" role="radiogroup" aria-label="Sort by">
                                <label class="home-sort-option">
                                    <input type="radio" name="sort" value="newest" <?php echo ($sort === 'newest') ? 'checked' : ''; ?>>
                                    <span>Newest</span>
                                </label>
                                <label class="home-sort-option">
                                    <input type="radio" name="sort" value="high_low" <?php echo ($sort === 'high_low') ? 'checked' : ''; ?>>
                                    <span>Rating: High to Low</span>
                                </label>
                                <label class="home-sort-option">
                                    <input type="radio" name="sort" value="low_high" <?php echo ($sort === 'low_high') ? 'checked' : ''; ?>>
                                    <span>Rating: Low to High</span>
                                </label>
                            </div>
                        </div>

                        <!-- 2. فلتر المهارة (select عادي) -->
                        <div class="home-filter-group">
                            <label class="home-filter-label" for="home-skill-filter">Skills</label>
                            <select class="home-filter-select" id="home-skill-filter" name="skill" aria-label="Skills">
                                <option value="">All skills</option>
                                <option value="Front End" <?php echo ($selectedSkill === 'Front End') ? 'selected' : ''; ?>>Front End</option>
                                <option value="Back End" <?php echo ($selectedSkill === 'Back End') ? 'selected' : ''; ?>>Back End</option>
                                <option value="Full Stack" <?php echo ($selectedSkill === 'Full Stack') ? 'selected' : ''; ?>>Full Stack</option>
                                <option value="HTML" <?php echo ($selectedSkill === 'HTML') ? 'selected' : ''; ?>>HTML</option>
                                <option value="CSS" <?php echo ($selectedSkill === 'CSS') ? 'selected' : ''; ?>>CSS</option>
                                <option value="JavaScript" <?php echo ($selectedSkill === 'JavaScript') ? 'selected' : ''; ?>>JavaScript</option>
                                <option value="TypeScript" <?php echo ($selectedSkill === 'TypeScript') ? 'selected' : ''; ?>>TypeScript</option>
                                <option value="PHP" <?php echo ($selectedSkill === 'PHP') ? 'selected' : ''; ?>>PHP</option>
                                <option value="Python" <?php echo ($selectedSkill === 'Python') ? 'selected' : ''; ?>>Python</option>
                                <option value="Java" <?php echo ($selectedSkill === 'Java') ? 'selected' : ''; ?>>Java</option>
                                <option value="C" <?php echo ($selectedSkill === 'C') ? 'selected' : ''; ?>>C</option>
                                <option value="C++" <?php echo ($selectedSkill === 'C++') ? 'selected' : ''; ?>>C++</option>
                                <option value="C#" <?php echo ($selectedSkill === 'C#') ? 'selected' : ''; ?>>C#</option>
                                <option value="Graphic Design" <?php echo ($selectedSkill === 'Graphic Design') ? 'selected' : ''; ?>>Graphic Design</option>
                                <option value="UI/UX" <?php echo ($selectedSkill === 'UI/UX') ? 'selected' : ''; ?>>UI/UX</option>
                                <option value="Video Editing" <?php echo ($selectedSkill === 'Video Editing') ? 'selected' : ''; ?>>Video Editing</option>
                                <option value="Photography" <?php echo ($selectedSkill === 'Photography') ? 'selected' : ''; ?>>Photography</option>
                                <option value="Content Writing" <?php echo ($selectedSkill === 'Content Writing') ? 'selected' : ''; ?>>Content Writing</option>
                                <option value="Translation" <?php echo ($selectedSkill === 'Translation') ? 'selected' : ''; ?>>Translation</option>
                                <option value="Marketing" <?php echo ($selectedSkill === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Digital Marketing" <?php echo ($selectedSkill === 'Digital Marketing') ? 'selected' : ''; ?>>Digital Marketing</option>
                                <option value="Social Media" <?php echo ($selectedSkill === 'Social Media') ? 'selected' : ''; ?>>Social Media</option>
                                <option value="Accounting" <?php echo ($selectedSkill === 'Accounting') ? 'selected' : ''; ?>>Accounting</option>
                                <option value="Business" <?php echo ($selectedSkill === 'Business') ? 'selected' : ''; ?>>Business</option>
                                <option value="Project Management" <?php echo ($selectedSkill === 'Project Management') ? 'selected' : ''; ?>>Project Management</option>
                                <option value="Flutter" <?php echo ($selectedSkill === 'Flutter') ? 'selected' : ''; ?>>Flutter</option>
                                <option value="Database" <?php echo ($selectedSkill === 'Database') ? 'selected' : ''; ?>>Database</option>
                            </select>
                        </div>

                        <!-- 3. الحد الأدنى للتقييم (Rating Range) -->
                        <div class="home-filter-group">
                            <span class="home-filter-label">Minimum Rating</span>
                            <div class="home-filter-range-wrap">
                                <input class="home-filter-range" id="home-rating-range" type="range" name="min_rating" min="0" max="5" step="0.5"
                                    value="<?php echo htmlspecialchars($min_rating); ?>" aria-label="Rating"
                                    oninput="document.getElementById('home-rating-value').textContent = this.value;">
                                <div class="home-filter-range-meta">
                                    <span>0</span>
                                    <span id="home-rating-value"><?php echo htmlspecialchars($min_rating); ?></span>
                                    <span>5</span>
                                </div>
                            </div>
                        </div>

                        <!-- زر الفلترة وزر مسح الفلتر -->
                        <button class="home-toolbar-btn home-filter-submit" type="submit">
                            Filter
                        </button>
                        <?php if ($selectedSkill !== '' || $min_rating > 0 || $sort !== 'newest'): ?>
                            <a href="index.php" class="home-filter-clear">Clear Filters</a>
                        <?php endif; ?>
                    </aside>
                </form>
            </div>
        </main>
    <?php
    } elseif ($page == "show") {
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

        $stmtUsers = $connect->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmtUsers->execute(array($user_id));
        $viewUser = $stmtUsers->fetch();

        // جلب متوسط تقييم المستخدم المعروض وعدد التقييمات
        $stmtViewRating = $connect->prepare("SELECT AVG(rating) AS avg_rating, COUNT(review_id) AS total_reviews FROM reviews WHERE reviewed_user_id = ?");
        $stmtViewRating->execute(array($user_id));
        $viewRatingData = $stmtViewRating->fetch();
        $viewAvgRating = ($viewRatingData && $viewRatingData['total_reviews'] > 0) ? number_format($viewRatingData['avg_rating'], 1) : '0';
        $viewTotalReviews = ($viewRatingData && $viewRatingData['total_reviews'] > 0) ? (int)$viewRatingData['total_reviews'] : 0;
    ?>

        <main class="dashboard-main has-home-sidebar">
            <section class="dashboard-content profile-page is-visitor">

                <?php
                if (isset($_SESSION["message"]) || isset($_SESSION["massge"])) {
                    $msg = $_SESSION["message"] ?? $_SESSION["massge"];
                    $isSuccess = (stripos($msg, 'success') !== false);
                    echo '<div style="margin-bottom: 12px;">';
                    echo '<span class="' . ($isSuccess ? 'alert-success-right' : 'form-errors') . '">' . htmlspecialchars($msg) . '</span>';
                    echo '</div>';
                    unset($_SESSION["message"], $_SESSION["massge"]);
                }
                ?>

              

                <div class="dashboard-section profile-header">

                    <div class="profile-banner-wrap">
                        <img class="profile-banner" src="includes/assets/img/banner-profile.jpg" alt="">
                        <div class="profile-banner-fade"></div>
                    </div>

                    <div class="profile-summary profile-summary-visitor">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar" role="img" aria-label="Profile picture placeholder" style="overflow: hidden;">
                                <img src="uploads/<?php echo (!empty($viewUser['img_profile']) && file_exists('uploads/' . $viewUser['img_profile'])) ? htmlspecialchars($viewUser['img_profile']) : 'default.jpg'; ?>"
                                    alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            </div>
                        </div>

                        <div class="profile-info">
                            <div class="profile-name-row">
                                <h1 class="profile-name-text"><?php echo htmlspecialchars($viewUser['username'] ?? 'User'); ?></h1>
                                <span class="profile-rating">
                                    ★ <?php echo $viewAvgRating; ?>
                                    (<?php echo $viewTotalReviews; ?>)
                                </span>
                            </div>

                            <p class="profile-city-text"><?php echo htmlspecialchars($viewUser['city'] ?? ''); ?></p>

                            <!-- بايو تحت الاسم — قصير وبنفس عرض دفاية الهيدر -->


                            <p class="profile-header-bio">
                                <?php echo htmlspecialchars($viewUser['bio'] ?? 'No bio added yet.'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <?php
                // جلب كل مهارات هذا المستخدم (صاحب البروفايل المعروض)
                $stmtSkills = $connect->prepare('SELECT * FROM skills WHERE user_id = ?');
                $stmtSkills->execute(array($user_id));
                $userSkills = $stmtSkills->fetchAll();

                // استخراج قائمة بأرقام مهارات هذا المستخدم للتأكد أن المهارة تخصه فعلاً
                $userSkillIds = array_column($userSkills, 'skill_id');

                $skill_id = 0;
                // التأكد إن المهارة المبعوتة في الـ URL تخص هذا المستخدم فعلاً
                if (isset($_GET['skill_id']) && in_array((int)$_GET['skill_id'], $userSkillIds)) {
                    $skill_id = (int)$_GET['skill_id'];
                } elseif (!empty($userSkills)) {
                    // لو مش مبعوتة أو المهارة مش بتاعته، نختار أول مهارة عند هذا المستخدم أوتوماتيك
                    $skill_id = (int)$userSkills[0]['skill_id'];
                }

                // جلب تفاصيل المهارة المختارة مع التأكد من ملكيتها لنفس المستخدم
                $selectedSkill = null;
                if ($skill_id > 0) {
                    $stmtSelected = $connect->prepare('SELECT * FROM skills WHERE skill_id = ? AND user_id = ?');
                    $stmtSelected->execute(array($skill_id, $user_id));
                    $selectedSkill = $stmtSelected->fetch();
                }
                ?>

                <div class="profile-skill-pick">
                    <section class="dashboard-section profile-panel profile-skills-board" aria-label="Skills">
                        <h2>My Skills</h2>

                        <div class="profile-skills-board-inner">

                            <div class="profile-skills-rail" role="radiogroup" aria-label="Skill list">
                                <?php
                                if (!empty($userSkills)) {
                                    foreach ($userSkills as $row) {
                                ?>
                                        <a href="index.php?page=show&user_id=<?php echo $user_id; ?>&skill_id=<?php echo $row['skill_id']; ?>"
                                            class="profile-skill-rail-item"
                                            style="text-decoration: none;">

                                            <input type="radio" name="customer_skill" <?php if ($skill_id == $row['skill_id']) echo 'checked'; ?>>
                                            <span><?php echo htmlspecialchars($row['title']); ?></span>

                                        </a>
                                <?php
                                    }
                                } else {
                                    echo '<p class="profile-empty-state">No skills yet.</p>';
                                }
                                ?>
                            </div>

                            <!-- دفاية جوا الدفاية: اسم السكيل + الديسكريبشن -->
                            <!-- Nested card: skill name + description -->
                            <?php if (!empty($selectedSkill)) { ?>
                                <div class="profile-skill-detail-card">
                                    <h3 class="profile-skill-detail-title" id="customer-skill-title"><?php echo htmlspecialchars($selectedSkill['title']); ?></h3>
                                    <p class="profile-skill-detail-desc" id="customer-skill-desc">
                                        <?php echo htmlspecialchars($selectedSkill['description']); ?>
                                    </p>



                                    <div class="profile-skill-choice-panels is-open" aria-label="Skill actions">
                                        <section class="dashboard-section profile-panel profile-choice-panel">
                                            <h2>Learn Only</h2>
                                            <p class="profile-bio-text">Ask this member for freelance work on the selected skill.</p>
                                            <a href="index.php?page=send_request&receiver_id=<?php echo $user_id; ?>&skill_id=<?php echo $skill_id; ?>&request_type=learn_only" class="profile-tab is-active" style="text-decoration: none;">Send Request</a>
                                        </section>
                                        <section class="dashboard-section profile-panel profile-choice-panel">
                                            <h2>Learn Swap</h2>
                                            <p class="profile-bio-text">Offer a skill exchange and learn from this member.</p>
                                            <a href="index.php?page=send_request&receiver_id=<?php echo $user_id; ?>&skill_id=<?php echo $skill_id; ?>&request_type=skill_swap" class="profile-tab" style="text-decoration: none;">Send Request</a>
                                        </section>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="profile-skill-detail-card">
                                    <p class="profile-empty-state">No skill details available.</p>
                                </div>
                            <?php } ?>

                        </div>
                    </section>
                </div>


            </section>
        </main>
    <?php
    } elseif ($page == "send_request") {
        $stmt = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute(array($_SESSION['user_login']));
        $sender = $stmt->fetch();

        $sender_id    = $sender ? (int)$sender['user_id'] : 0;
        $receiver_id  = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
        $skill_id     = isset($_GET['skill_id']) ? (int)$_GET['skill_id'] : 0;
        $request_type = isset($_GET['request_type']) ? trim($_GET['request_type']) : 'learn_only';

        if ($sender_id > 0 && $receiver_id > 0 && $skill_id > 0 && $sender_id != $receiver_id) {

            // التأكد من أن المهارة تخص المستقبل (receiver_id) وليست ملك شخص آخر
            $stmtVerify = $connect->prepare("SELECT skill_id FROM skills WHERE skill_id = ? AND user_id = ?");
            $stmtVerify->execute(array($skill_id, $receiver_id));

            if ($stmtVerify->rowCount() > 0) {
                // فحص هل فيه ريكوست اتعمل قبل كده للمهارة دي؟
                $stmtCheck = $connect->prepare("SELECT request_id FROM requests WHERE sender_id = ? AND receiver_id = ? AND skill_id = ?");
                $stmtCheck->execute(array($sender_id, $receiver_id, $skill_id));

                // لو مفيش أي ريكوست اتعمل قبل كده خالص (العدد صفر) -> يضيفه
                if ($stmtCheck->rowCount() == 0) {
                    $stmtInsert = $connect->prepare("INSERT INTO requests (sender_id, receiver_id, skill_id, request_type, status) VALUES (?, ?, ?, ?, 'pending')");
                    $stmtInsert->execute(array($sender_id, $receiver_id, $skill_id, $request_type));

                    $_SESSION["message"] = "Request sent successfully!";
                } else {
                    // لو الريكوست اتعمل قبل كده
                    $_SESSION["message"] = "You have already sent a request for this skill!";
                }
            }
        }

        header("Location: index.php");
        exit();
    }

    // استدعاء الفوتر في النهاية
    include('includes/temp/footer.php');
} else {
    // لو مش مسجل دخول، حوله لصفحة اللوجين
    $_SESSION['error_login'] = 'You must be logged in as a user to access this page';
    header('location: login.php');
    exit();
}
?>