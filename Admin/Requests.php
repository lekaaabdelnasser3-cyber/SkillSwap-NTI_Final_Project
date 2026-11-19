<?php
session_start();

if (isset($_SESSION["admin_login"])) {

    include("init.php");

    // استعلام الـ SQL مع ربط جدول requests بـ users مرتين (للـ sender والـ receiver) وبجدول skills عشان عنوان المهارة
    $stmt = $connect->prepare("SELECT requests.*, 
               sender.username AS sender_name, 
               receiver.username AS receiver_name,
               skills.title AS skill_title
        FROM requests 
        INNER JOIN users AS sender ON requests.sender_id = sender.user_id 
        INNER JOIN users AS receiver ON requests.receiver_id = receiver.user_id 
        INNER JOIN skills ON requests.skill_id = skills.skill_id
        ORDER BY requests.request_id DESC
    ");
    $stmt->execute();
    $result = $stmt->fetchAll();

    $activePage = 'requests';
    include('includes/temp/sidebar.php');
    ?>

    <main class="dashboard-main has-home-sidebar">
            <section class="dashboard-content">

                <div class="content-header">
                    <h1>Requests Management</h1>
                    <p>Review and monitor skill exchange requests between members.</p>
                </div>

                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>All Requests</h2>
                    </div>

                    <div class="table-responsive">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Sender</th>
                                    <th>Receiver</th>
                                    <th>Skill Title</th>
                                    <th>Type Request</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($result)): ?>
                                    <?php foreach ($result as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item['sender_name']); ?></strong></td>
                                            <td><strong><?php echo htmlspecialchars($item['receiver_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item['skill_title']); ?></td>
                                            <td><?php echo htmlspecialchars($item['request_type']); ?></td>
                                            <td>
                                                <?php if ($item['status'] == 'accepted'): ?>
                                                    <span class="badge badge-accepted">Accepted</span>
                                                <?php elseif ($item['status'] == 'pending'): ?>
                                                    <span class="badge badge-pending">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge badge-rejected">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">No requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
    </main>

    <?php
    include("includes/temp/footer.php");
} else {
    $_SESSION['error_login'] = 'You must be logged in as an admin to access this page';
    header("location:../login.php");
    exit();
}
?>