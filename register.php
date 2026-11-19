<?php
session_start();

$pageTitle = 'Register - Skills';
include('includes/temp/header.php');
include('includes/db/db.php');
include('validation.php'); // تم تصحيح المسار هنا

$errors = [];
$validated = null;
$old = ['username' => '', 'email' => '', 'dob' => '', 'city' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = val_validate_registration($_POST);
    $errors = $result['errors'];
    $old = $result['old'];

    if ($result['valid']) {
        $validated = $result['data'];

        // 1. التحقق من تكرار اسم المستخدم في الداتابيز
        $usernameError = val_username_unique_register($connect, $validated['username']);
        if ($usernameError !== '') {
            $errors['username'] = $usernameError;
        }

        // 2. التحقق من تكرار الإيميل في الداتابيز
        $stmtCheckEmail = $connect->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmtCheckEmail->execute([$validated['email']]);

        if ($stmtCheckEmail->rowCount() > 0) {
            $errors['email'] = 'This email is already registered.';
        }

        // إذا لم تكن هناك أخطاء تكرار، يتم إدخال البيانات
        if (empty($errors)) {
            $hashedPassword = password_hash($validated['password'], PASSWORD_DEFAULT);

            $stmtInsert = $connect->prepare("INSERT INTO users (username, email, password, city, birth_date, status, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

            $inserted = $stmtInsert->execute([
                $validated['username'],
                $validated['email'],
                $hashedPassword,
                $validated['city'],
                $validated['dob'],
                '1',
                'user'
            ]);

            if ($inserted) {
                $_SESSION['success_login'] = 'Account created successfully! Please log in.';
                header('location: login.php');
                exit();
            } else {
                $errors['general'] = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>

<main class="auth-main">
    <section class="auth-card">
        <div class="auth-header">
            <h2>Create <span>Account</span></h2>
            <p class="auth-subtitle">Create an account to start sharing and learning</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="form-errors">
                <?php echo isset($errors['general']) ? $errors['general'] : 'Please fix the errors below and try again.'; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username"
                    value="<?= val_escape($old['username']) ?>" required>
                <?php if (isset($errors['username'])): ?>
                    <small class="field-error"><?= val_escape($errors['username']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="example@email.com"
                    value="<?= val_escape($old['email']) ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <small class="field-error"><?= val_escape($errors['email']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="checkbox" id="show-password" class="password-show">
                    <input type="text" id="password" name="password" placeholder="Enter your password" autocomplete="new-password" required>
                    <label class="password-toggle" for="show-password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                        <i class="bi bi-eye-slash"></i>
                    </label>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <small class="field-error"><?= val_escape($errors['password']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" max="9999-12-31" value="<?= val_escape($old['dob']) ?>" required>
                <?php if (isset($errors['dob'])): ?>
                    <small class="field-error"><?= val_escape($errors['dob']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="city">City</label>
                <?php
                $cities = [
                    'Cairo', 'Giza', 'Alexandria', 'Qalyubia', 'Sharqia', 'Dakahlia',
                    'Gharbia', 'Monufia', 'Beheira', 'Kafr El Sheikh', 'Damietta',
                    'Port Said', 'Ismailia', 'Suez', 'North Sinai', 'South Sinai',
                    'Fayoum', 'Beni Suef', 'Minya', 'Assiut', 'Sohag', 'Qena',
                    'Luxor', 'Aswan', 'Red Sea', 'New Valley', 'Matrouh', '10 ramdan',
                    'Loxor', 'Cario'
                ];
                $selectedCity = $old['city'] ?? '';
                ?>
                <select name="city" id="city" required>
                    <option value="" disabled <?= empty($selectedCity) ? 'selected' : '' ?>>Select your city</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= val_escape($c) ?>" <?= (strcasecmp($selectedCity, $c) === 0) ? 'selected' : '' ?>>
                            <?= val_escape($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['city'])): ?>
                    <small class="field-error"><?= val_escape($errors['city']) ?></small>
                <?php endif; ?>
            </div>

            <button type="submit" class="auth-btn">Register</button>

            <p class="auth-switch">
                Already have an account? <a href="login.php">Log in</a>
            </p>
        </form>
    </section>
</main>
</body>
</html>