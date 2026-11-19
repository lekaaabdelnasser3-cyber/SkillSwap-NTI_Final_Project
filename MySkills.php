<?php

session_start();

if (isset($_SESSION['user_login'])) {
    include('includes/temp/header.php');
    include('includes/db/db.php');
    $activePage = 'skills';
    include('includes/temp/sidebar.php');
    require_once __DIR__ . '/validation.php';

    // جلب بيانات المستخدم الحالي
    $stmtUser = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmtUser->execute(array($_SESSION['user_login']));
    $currentUser = $stmtUser->fetch();
    $user_id = $currentUser['user_id'];

    $errors = [];

    // معالجة حذف المهارة
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_skill'])) {
        $skill_id = (int) ($_POST['skill_id'] ?? 0);

        if ($skill_id > 0) {
            $stmtDelete = $connect->prepare("DELETE FROM skills WHERE skill_id = ? AND user_id = ?");
            $stmtDelete->execute(array($skill_id, $user_id));

            if ($stmtDelete->rowCount() > 0) {
                $_SESSION['skill_success'] = 'Skill deleted successfully!';
            } else {
                $errors[] = 'Skill not found or already deleted.';
            }
        } else {
            $errors[] = 'Invalid skill selected.';
        }

        if (empty($errors)) {
            header("Location: MySkills.php");
            exit();
        }
    }

    // معالجة إضافة المهارة
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_skill'])) {
                // نفحص السكيل والوصف
        $check = val_validate_skill($_POST);
        $skill_title = $check['data']['title'];
        $skill_desc  = $check['data']['description'];

        // لو فيه أخطاء نعرضها، ولو مفيش نكمل لفحص التكرار والحفظ
        if (!$check['valid']) {
            $errors = array_values($check['errors']);
        } else {
            // فحص إذا كانت المهارة مضافة مسبقاً لنفس المستخدم
            $stmtCheck = $connect->prepare("SELECT skill_id FROM skills WHERE user_id = ? AND title = ?");
            $stmtCheck->execute(array($user_id, $skill_title));
            if ($stmtCheck->rowCount() > 0) {
                $errors[] = 'You have already added this skill before!';
            } else {
                $stmtInsert = $connect->prepare("INSERT INTO skills (user_id, title, description) VALUES (?, ?, ?)");
                $stmtInsert->execute(array($user_id, $skill_title, $skill_desc));
                $_SESSION['skill_success'] = 'Skill added successfully!';
                header("Location: MySkills.php");
                exit();
            }
        }
    }

    // جلب مهارات المستخدم الحالية للعرض في الأسفل
    $stmtSkills = $connect->prepare("SELECT * FROM skills WHERE user_id = ? ORDER BY skill_id DESC");
    $stmtSkills->execute(array($user_id));
    $userSkills = $stmtSkills->fetchAll();
?>

    <!-- ===== واجهة My Skills — متوازية مع المنيو ===== -->
    <main class="dashboard-main has-home-sidebar">
        <section class="dashboard-content skills-page">

            <?php if (!empty($_SESSION['skill_success'])): ?>
                <div style="margin-bottom: 15px;">
                    <span class="alert-success-right"><?php echo htmlspecialchars($_SESSION['skill_success']); ?></span>
                </div>
                <?php unset($_SESSION['skill_success']); ?>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div style="margin-bottom: 15px;">
                    <?php foreach ($errors as $error): ?>
                        <span class="form-errors" style="display:block; margin-bottom:5px;"><?php echo htmlspecialchars($error); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- دفاية إنشاء سكيل جديدة -->
            <form method="POST" action="MySkills.php">
                <section class="dashboard-section profile-panel skills-create-card" aria-label="Create skill">
                    <h2 class="skills-create-heading">Create Skill</h2>

                    <div class="skills-create-grid">
                        <div class="skills-create-field">
                            <label class="skills-create-label" for="skill-title">Skill</label>
                            <select
                                class="profile-input skills-create-input skills-create-select"
                                id="skill-title"
                                name="skill_title"
                                aria-label="Skill"
                                required>
                                <option value="">Select skill</option>
                                <option value="Front End">Front End</option>
                                <option value="Back End">Back End</option>
                                <option value="Full Stack">Full Stack</option>
                                <option value="HTML">HTML</option>
                                <option value="CSS">CSS</option>
                                <option value="JavaScript">JavaScript</option>
                                <option value="TypeScript">TypeScript</option>
                                <option value="PHP">PHP</option>
                                <option value="Python">Python</option>
                                <option value="Java">Java</option>
                                <option value="C">C</option>
                                <option value="C++">C++</option>
                                <option value="C#">C#</option>
                                <option value="Flutter">Flutter</option>
                                <option value="Database">Database</option>
                                <option value="Graphic Design">Graphic Design</option>
                                <option value="UI/UX">UI/UX</option>
                                <option value="Video Editing">Video Editing</option>
                                <option value="Photography">Photography</option>
                                <option value="Drawing">Drawing</option>
                                <option value="Content Writing">Content Writing</option>
                                <option value="Translation">Translation</option>
                                <option value="English Teaching">English Teaching</option>
                                <option value="Arabic Teaching">Arabic Teaching</option>
                                <option value="Math Tutoring">Math Tutoring</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Digital Marketing">Digital Marketing</option>
                                <option value="Social Media">Social Media</option>
                                <option value="Accounting">Accounting</option>
                                <option value="Business">Business</option>
                                <option value="Project Management">Project Management</option>
                                <option value="Public Speaking">Public Speaking</option>
                                <option value="Music">Music</option>
                                <option value="Cooking">Cooking</option>
                                <option value="Fitness">Fitness</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="skills-create-field skills-create-field-wide">
                            <label class="skills-create-label" for="skill-desc">Description</label>
                            <textarea
                                class="profile-input profile-bio-input skills-create-textarea"
                                id="skill-desc"
                                name="skill_desc"
                                rows="4"
                                placeholder="Write a short description for this skill"
                                maxlength="1000"
                                required></textarea>
                        </div>
                    </div>

                    <div class="skills-create-actions">
                        <button class="btn-primary" type="submit" name="save_skill">Save Skill</button>
                        <button class="btn-cancel" type="reset">Cancel</button>
                    </div>
                </section>
            </form>

            <!-- دفاية السكيلز: كل سكيل + وصف (عرض فقط) -->
            <section class="dashboard-section profile-panel profile-skills-board" aria-label="Saved skills">
                <h2 class="skills-board-heading">Your Skills (<?php echo count($userSkills); ?>)</h2>

                <div class="profile-skills-board-list">
                    <?php if (!empty($userSkills)): ?>
                        <?php foreach ($userSkills as $sk): ?>
                            <article class="profile-skill-card">
                                <span class="profile-skill-name"><?php echo htmlspecialchars($sk['title']); ?></span>
                                <p class="profile-skill-desc"><?php echo htmlspecialchars($sk['description']); ?></p>
                                <form method="POST" action="MySkills.php" class="skill-delete-form">
                                    <input type="hidden" name="skill_id" value="<?php echo (int) $sk['skill_id']; ?>">
                                    <button
                                        class="skill-delete-link"
                                        type="submit"
                                        name="delete_skill"
                                        title="Delete skill"
                                        aria-label="Delete <?php echo htmlspecialchars($sk['title']); ?>"
                                        onclick="return confirm('Delete this skill?');">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="profile-empty-state" style="padding: 25px; text-align: center; color: #888; width: 100%;">You haven't added any skills yet. Use the form above to add your first skill.</p>
                    <?php endif; ?>
                </div>
            </section>

        </section>
    </main>

<?php
    include('includes/temp/footer.php');
} else {
    $_SESSION['error_login'] = 'You must be logged in as a user to access this page';
    header('location: login.php');
    exit();
}
?>