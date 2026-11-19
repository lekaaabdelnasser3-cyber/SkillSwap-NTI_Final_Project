<?php
session_start();
$pageTitle = 'Login - Skills';

include('includes/temp/header.php');
include('includes/db/db.php');
include('validation.php');

$emailerr = '';
$passworderr = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $check = val_validate_login($_POST);
    $email = $check['data']['email'];
    $password = $check['data']['password'];
    $emailerr = isset($check['errors']['email']) ? $check['errors']['email'] : '';
    $passworderr = isset($check['errors']['password']) ? $check['errors']['password'] : '';
    $errors = !$check['valid'];

    if (!$errors) {

        $stmt = $connect->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(array($email));

        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $result['password']) || ($password === $result['password'])) {


                if ($result['status'] == 1) {

                    if ($result['role'] == 'admin') {
                        $_SESSION['admin_login'] = $email;
                        header('location: Admin/dashboard.php');
                        exit();
                    } else {
                        $_SESSION['user_login'] = $email;
                        header('location: index.php');
                        exit();
                    }

                } else {
                    $_SESSION['error_login'] = 'Your account is not activated yet';
                }

            } else {
                $_SESSION['error_login'] = 'Incorrect email or password';
            }

        } else {
            $_SESSION['error_login'] = 'Incorrect email or password';
        }
    }
}
?>

<main class="auth-main">
    <section class="auth-card">
        <div class="auth-header">
            <h2>Welcome <span>Back!</span></h2>
            <p class="auth-subtitle">Log in to your Skills account</p>
        </div>

        <form action="" method="POST" class="auth-form">
            <?php
            if (isset($_SESSION["error_login"])) {
                echo '<span class="form-errors">' . $_SESSION["error_login"] . '</span>';
                unset($_SESSION["error_login"]);
            }
            ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="example@email.com"
                    value="<?php echo val_escape(isset($email) ? $email : ''); ?>">
                <span class="field-error"><?php echo $emailerr; ?></span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="checkbox" id="show-password" class="password-show">
                    <input type="text" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
                    <label class="password-toggle" for="show-password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                        <i class="bi bi-eye-slash"></i>
                    </label>
                </div>
                <span class="field-error"><?php echo $passworderr; ?></span>
            </div>

            <button type="submit" class="auth-btn" name="login">Log In</button>

            <p class="auth-switch">
                Don't have an account? <a href="register.php">Sign up</a>
            </p>
        </form>
    </section>
</main>
</body>
</html>