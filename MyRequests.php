<?php
session_start();
if (isset($_SESSION['user_login'])) {
    include('includes/temp/header.php');
    include('includes/db/db.php');
    $activePage = 'requests';
    include('includes/temp/sidebar.php');

    $page = "All";
    if (isset($_GET["page"])) {
        $page = $_GET["page"];
    }

    if ($page == "All") {

        $tab = isset($_GET['tab']) && $_GET['tab'] === 'sent' ? 'sent' : 'incoming';

        // 1. جلب بيانات المستخدم الحالي
        $stmt = $connect->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute(array($_SESSION['user_login']));
        $currentUser = $stmt->fetch();
        $user_id = $currentUser['user_id'];

        // 2. جلب الطلبات الواردة (المستخدم الحالي هو المستقبل receiver_id)
        // الترتيب: الطلبات المعلقة (pending) أولاً، ثم المقبولة، ثم المرفوضة، والأحدث دائماً في الأول
        $stmtIncoming = $connect->prepare("
            SELECT requests.*, 
                   users.username, 
                   users.city, 
                   users.img_profile, 
                   skills.title AS skill_title
            FROM requests 
            INNER JOIN users ON requests.sender_id = users.user_id 
            INNER JOIN skills ON requests.skill_id = skills.skill_id 
            WHERE requests.receiver_id = ? 
            ORDER BY FIELD(requests.status, 'pending', 'accepted', 'rejected') ASC, requests.request_id DESC
        ");
        $stmtIncoming->execute(array($user_id));
        $incomingRequests = $stmtIncoming->fetchAll();

        // 3. جلب الطلبات المرسلة (المستخدم الحالي هو الراسل sender_id)
        $stmtSent = $connect->prepare(" SELECT requests.*, 
           users.username, 
           users.city, 
           users.img_profile, 
           skills.title AS skill_title,
           reviews.review_id AS already_rated
    FROM requests 
    INNER JOIN users ON requests.receiver_id = users.user_id 
    INNER JOIN skills ON requests.skill_id = skills.skill_id 
    LEFT JOIN reviews ON reviews.reviewer_id = ? AND reviews.reviewed_user_id = requests.receiver_id
    WHERE requests.sender_id = ? 
    ORDER BY FIELD(requests.status, 'pending', 'accepted', 'rejected') ASC, requests.request_id DESC
");
        $stmtSent->execute(array($user_id, $user_id));
        $sentRequests = $stmtSent->fetchAll();
?>

        <!-- ===== واجهة My Requests ===== -->
        <main class="dashboard-main has-home-sidebar">
            <section class="dashboard-content requests-page">

                <!-- تبويبات Incoming / Sent -->
                <div class="requests-tabs" role="tablist" aria-label="Request filters">
                    <button class="requests-tab <?php echo $tab === 'incoming' ? 'is-active' : ''; ?>" type="button" role="tab" aria-selected="<?php echo $tab === 'incoming' ? 'true' : 'false'; ?>" data-requests-tab="incoming">
                        Incoming (<?php echo count($incomingRequests); ?>)
                    </button>
                    <button class="requests-tab <?php echo $tab === 'sent' ? 'is-active' : ''; ?>" type="button" role="tab" aria-selected="<?php echo $tab === 'sent' ? 'true' : 'false'; ?>" data-requests-tab="sent">
                        Sent (<?php echo count($sentRequests); ?>)
                    </button>
                </div>

                <!-- 1. تبويب الطلبات الواردة (Incoming) -->
                <div class="requests-panel <?php echo $tab === 'incoming' ? 'is-active' : ''; ?>" data-requests-panel="incoming" <?php echo $tab !== 'incoming' ? 'hidden' : ''; ?>>
                    <?php if (!empty($incomingRequests)): ?>
                        <?php foreach ($incomingRequests as $inc): ?>
                            <?php
                            $typeLabel = ucwords(str_replace('_', ' ', (string) $inc['request_type']));
                            $statusClass = strtolower((string) $inc['status']);
                            ?>
                            <article class="requests-card">
                                <div class="requests-card-top">
                                    <div class="requests-card-person">
                                        <span class="requests-card-avatar" aria-hidden="true">
                                            <?php if (!empty($inc['img_profile']) && file_exists('uploads/' . $inc['img_profile'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($inc['img_profile']); ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-person"></i>
                                            <?php endif; ?>
                                        </span>
                                        <div class="requests-card-meta">
                                            <h2 class="requests-card-name"><?php echo htmlspecialchars($inc['username']); ?></h2>
                                            <p class="requests-card-city"><?php echo htmlspecialchars($inc['city'] ?? ''); ?></p>
                                        </div>
                                    </div>

                                    <span class="requests-badge requests-badge-<?php echo htmlspecialchars($statusClass); ?>">
                                        <?php echo ucfirst($inc['status']); ?>
                                    </span>
                                </div>

                                <div class="requests-card-line">
                                    <p class="requests-card-skill"><i class="bi bi-lightning-charge"></i><span>Skill</span> <?php echo htmlspecialchars($inc['skill_title']); ?></p>
                                    <p class="requests-card-type"><i class="bi bi-arrow-left-right"></i><span>Type</span> <?php echo htmlspecialchars($typeLabel); ?></p>
                                </div>

                                <?php if ($inc['status'] == 'pending' || $inc['status'] == 'accepted'): ?>
                                    <div class="requests-card-footer">
                                        <div class="requests-card-actions">
                                            <?php if ($inc['status'] == 'pending'): ?>
                                                <a href="MyRequests.php?page=Accept&request_id=<?php echo $inc['request_id']; ?>" class="btn-primary">Accept</a>
                                                <a href="MyRequests.php?page=Reject&request_id=<?php echo $inc['request_id']; ?>" class="btn-cancel">Reject</a>
                                            <?php else: ?>
                                                <a href="chat.php?with=<?php echo $inc['sender_id']; ?>" class="btn-primary"><i class="bi bi-chat-dots"></i> Chat</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="requests-empty">No incoming requests yet.</p>
                    <?php endif; ?>
                </div>

                <!-- 2. تبويب الطلبات المرسلة (Sent) -->
                <div class="requests-panel <?php echo $tab === 'sent' ? 'is-active' : ''; ?>" data-requests-panel="sent" <?php echo $tab !== 'sent' ? 'hidden' : ''; ?>>
                    <?php if (!empty($sentRequests)): ?>
                        <?php foreach ($sentRequests as $sent): ?>
                            <?php
                            $typeLabel = ucwords(str_replace('_', ' ', (string) $sent['request_type']));
                            $statusClass = strtolower((string) $sent['status']);
                            ?>
                            <article class="requests-card">
                                <div class="requests-card-top">
                                    <div class="requests-card-person">
                                        <span class="requests-card-avatar" aria-hidden="true">
                                            <?php if (!empty($sent['img_profile']) && file_exists('uploads/' . $sent['img_profile'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($sent['img_profile']); ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-person"></i>
                                            <?php endif; ?>
                                        </span>
                                        <div class="requests-card-meta">
                                            <h2 class="requests-card-name"><?php echo htmlspecialchars($sent['username']); ?></h2>
                                            <p class="requests-card-city"><?php echo htmlspecialchars($sent['city'] ?? ''); ?></p>
                                        </div>
                                    </div>

                                    <span class="requests-badge requests-badge-<?php echo htmlspecialchars($statusClass); ?>">
                                        <?php echo ucfirst($sent['status']); ?>
                                    </span>
                                </div>

                                <div class="requests-card-line">
                                    <p class="requests-card-skill"><i class="bi bi-lightning-charge"></i><span>Skill</span> <?php echo htmlspecialchars($sent['skill_title']); ?></p>
                                    <p class="requests-card-type"><i class="bi bi-arrow-left-right"></i><span>Type</span> <?php echo htmlspecialchars($typeLabel); ?></p>
                                </div>

                                <div class="requests-card-footer">
                                    <?php if ($sent['status'] == 'accepted'): ?>
                                        <p class="requests-card-note is-accepted">Your request has been accepted.</p>
                                    <?php elseif ($sent['status'] == 'rejected'): ?>
                                        <p class="requests-card-note is-rejected">Your request was declined.</p>
                                    <?php else: ?>
                                        <p class="requests-card-note is-pending">Waiting for a response.</p>
                                    <?php endif; ?>

                                    <?php if ($sent['status'] == 'accepted'): ?>
                                        <div class="requests-card-actions">
                                            <a href="chat.php?with=<?php echo $sent['receiver_id']; ?>" class="btn-primary"><i class="bi bi-chat-dots"></i> Chat</a>
                                            <?php if (empty($sent['already_rated'])): ?>
                                                <a href="MyRequests.php?page=rate&request_id=<?php echo $sent['request_id']; ?>" class="btn-rate"><i class="bi bi-star-fill"></i> Rate</a>
                                            <?php else: ?>
                                                <span class="requests-rated"><i class="bi bi-check2"></i> Rated</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="requests-empty">No sent requests yet.</p>
                    <?php endif; ?>
                </div>

            </section>
        </main>

        <!-- كود الـ JavaScript لتبديل التابات Incoming / Sent فوراً عند الضغط -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var tabs = document.querySelectorAll('[data-requests-tab]');
                var panels = document.querySelectorAll('[data-requests-panel]');

                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        var target = tab.getAttribute('data-requests-tab');

                        tabs.forEach(function(item) {
                            var isActive = (item === tab);
                            item.classList.toggle('is-active', isActive);
                            item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        });

                        panels.forEach(function(panel) {
                            var match = (panel.getAttribute('data-requests-panel') === target);
                            panel.classList.toggle('is-active', match);
                            if (match) {
                                panel.removeAttribute('hidden');
                            } else {
                                panel.setAttribute('hidden', '');
                            }
                        });
                    });
                });
            });
        </script>

<?php
    } elseif ($page === 'Accept') {
        $request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;

        $stmt = $connect->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute(array($_SESSION['user_login']));
        $currentUser = $stmt->fetch();
        $user_id = $currentUser ? (int) $currentUser['user_id'] : 0;

        $stmtReq = $connect->prepare("
            SELECT requests.*, skills.title AS skill_title
            FROM requests
            INNER JOIN skills ON requests.skill_id = skills.skill_id
            WHERE requests.request_id = ?
              AND requests.receiver_id = ?
              AND requests.status = 'pending'
            LIMIT 1
        ");
        $stmtReq->execute(array($request_id, $user_id));
        $req = $stmtReq->fetch();

        if ($req) {
            $stmt = $connect->prepare("UPDATE requests SET status = 'accepted' WHERE request_id = ? AND receiver_id = ?");
            $stmt->execute(array($request_id, $user_id));

            $typeLabel = ($req['request_type'] === 'skill_swap') ? 'skill swap' : 'learn';
            $systemText = 'Your ' . $typeLabel . ' request for "' . $req['skill_title'] . '" has been accepted.';

            $stmtMsg = $connect->prepare("
                INSERT INTO messages (sender_id, receiver_id, message_content, is_system, is_read)
                VALUES (?, ?, ?, '1', '0')
            ");
            $stmtMsg->execute(array($user_id, (int) $req['sender_id'], $systemText));
        }

        header("Location: MyRequests.php");
        exit();
    } elseif ($page === 'Reject') {
        $request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;

        $stmt = $connect->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute(array($_SESSION['user_login']));
        $currentUser = $stmt->fetch();
        $user_id = $currentUser ? (int) $currentUser['user_id'] : 0;

        $stmt = $connect->prepare("UPDATE requests SET status = 'rejected' WHERE request_id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute(array($request_id, $user_id));

        header("Location: MyRequests.php");
        exit();
    } elseif ($page === 'rate') {
        $request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;

        // جلب بيانات الطلب وتأكد إن السيندر هو المستخدم الحالي وإن الستاتس accepted
        $stmt = $connect->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(array($_SESSION['user_login']));
        $currentUser = $stmt->fetch();
        $user_id = $currentUser['user_id'];

        $stmtReq = $connect->prepare("SELECT * FROM requests WHERE request_id = ? AND sender_id = ? AND status = 'accepted'");
        $stmtReq->execute(array($request_id, $user_id));
        $req = $stmtReq->fetch();

        if ($req) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
                $rating = (int)$_POST['rating'];
                if ($rating >= 1 && $rating <= 5) {
                    // فحص لو كان المستخدم قيّم هذا الشخص مسبقاً، نحدث التقييم بدلاً من تكراره
                    $stmtCheckReview = $connect->prepare("SELECT review_id FROM reviews WHERE reviewed_user_id = ? AND reviewer_id = ?");
                    $stmtCheckReview->execute(array($req['receiver_id'], $user_id));
                    $existingReview = $stmtCheckReview->fetch();

                    if ($existingReview) {
                        $stmtUp = $connect->prepare("UPDATE reviews SET rating = ? WHERE review_id = ?");
                        $stmtUp->execute(array($rating, $existingReview['review_id']));
                    } else {
                        $stmtIns = $connect->prepare("INSERT INTO reviews (reviewed_user_id, reviewer_id, rating) VALUES (?, ?, ?)");
                        $stmtIns->execute(array($req['receiver_id'], $user_id, $rating));
                    }
                }
                header("Location: MyRequests.php?tab=sent");
                exit();
            }
            // عرض فورم التقييم
            ?>
            <main class="dashboard-main has-home-sidebar">
                <section class="dashboard-content" style="max-width:500px; margin:40px auto; text-align:center; background:#1c1012; padding:30px; border-radius:12px; border:1px solid #3d1a1f;">
                    <h2 style="color:#fff; margin-bottom:15px;">Rate User</h2>
                    <p style="color:#aaa; margin-bottom:25px; font-size:14px;">How was your experience with this member?</p>
                    <form method="POST">
                        <div class="rating-stars" style="display: flex; justify-content: center; gap: 15px; margin-bottom: 25px;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px; color: #f59e0b;">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?> required>
                                    <span style="font-size: 15px; color: #ddd;"><?php echo $i; ?> ⭐</span>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div style="display:flex; justify-content:center; gap:10px;">
                            <button type="submit" class="btn-primary" style="padding:10px 25px;">Submit Rating</button>
                            <a href="MyRequests.php?tab=sent" class="btn-cancel" style="padding:10px 20px;">Cancel</a>
                        </div>
                    </form>
                </section>
            </main>
            <?php
        } else {
            header("Location: MyRequests.php");
            exit();
        }
    }

    include('includes/temp/footer.php');
} else {
    $_SESSION['error_login'] = 'You must be logged in as a user to access this page';
    header('location: login.php');
    exit();
}
?>