<?php
session_start();

if (isset($_SESSION["admin_login"])) {

    include("init.php");


    $stmt = $connect->prepare("SELECT skills.*, users.username 
        FROM skills 
        INNER JOIN users ON skills.user_id = users.user_id");
    $stmt->execute();
    $result = $stmt->fetchall();

    $activePage = 'skills';
    include('includes/temp/sidebar.php');
    ?>

    <main class="dashboard-main has-home-sidebar">
            <section class="dashboard-content">


                <div class="content-header">
                    <h1>Skills Management</h1>
                    <p>Manage, review, and organize skills offered and requested by members.</p>
                </div>



                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>All Skills</h2>
                    </div>

                    <div class="table-responsive">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Skill Title</th>
                                    <th>Description</th>
                                  
                                </tr>
                            </thead>
                            <tbody>

                                <?php foreach ($result as $items) {
                                    ?>
                                    <tr>


                                        <td><strong><?php echo $items['username']; ?></strong></td>
                                        <td><?php echo $items['title']; ?></td>
                                        <td><?php echo $items['description']; ?></td>
                                      

                                    </tr>
                                <?php
                                }
                                ?>
                              

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
}
?>