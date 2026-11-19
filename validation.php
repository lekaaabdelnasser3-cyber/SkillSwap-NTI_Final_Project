<?php
/**
 * includes/validation.php
 * -----------------------------------------------------------------------
 * Reusable SERVER-SIDE validation for the Skills project.
 * -----------------------------------------------------------------------
 */

/* ======================= helpers ======================= */



function val_clean($value)
{
    if (!is_scalar($value)) {
        return '';
    }
    return trim((string) $value);
}

function val_raw($value)
{
    if (!is_scalar($value)) {
        return '';
    }
    return (string) $value;
}

function val_escape($value)
{
    if (!is_scalar($value)) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function val_is_utf8($value)
{
    return mb_check_encoding($value, 'UTF-8');
}

function val_reserved_usernames()
{
    return array('admin', 'administrator', 'root', 'superuser', 'system', 'support', 'moderator');
}

function val_filter_errors(array $errors)
{
    return array_filter($errors, function ($message) {
        return $message !== '';
    });
}

/* ===================== field validators ===================== */

function val_username($username)
{
    if ($username === '') {
        return 'Username is required.';
    }
    if (!val_is_utf8($username)) {
        return 'Username contains invalid characters.';
    }
    $length = mb_strlen($username, 'UTF-8');
    if ($length < 3 || $length > 30) {
        return 'Username must be between 3 and 30 characters.';
    }
    if (!preg_match('/^[\p{L}\p{M}\p{N}_]+$/uD', $username)) {
        return 'Username can contain only letters, numbers and underscores.';
    }
    if (in_array(mb_strtolower($username, 'UTF-8'), val_reserved_usernames(), true)) {
        return 'This username is reserved. Please choose another one.';
    }
    return '';
}

function val_email($email)
{
    if ($email === '') {
        return 'Email is required.';
    }
    if (!val_is_utf8($email)) {
        return 'Please enter a valid email address.';
    }
    if (strlen($email) > 100) {
        return 'Email must not exceed 100 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    return '';
}

function val_password($password, $strict = true)
{
    if ($password === '') {
        return 'Password is required.';
    }
    if (!$strict) {
        return '';
    }
    if (!val_is_utf8($password)) {
        return 'Password contains invalid characters.';
    }
    if (mb_strlen($password, 'UTF-8') < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (strlen($password) > 72) {
        return 'Password is too long. Please use a shorter one.';
    }
    if (!preg_match('/\p{L}/u', $password) || !preg_match('/\p{N}/u', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    return '';
}

/* تاريخ الميلاد: يتأكد من صيغة 4 أرقام للسنة، ومن أن العمر 18 سنة فأكثر */
function val_dob($dob, $max_age = 120, $min_age = 18)
{
    if ($dob === '') {
        return 'Date of birth is required.';
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        return 'Please enter a valid date format (YYYY-MM-DD).';
    }

    $year = (int) substr($dob, 0, 4);
    if ($year > 9999 || $year < 1000) {
        return 'Year must be exactly 4 digits.';
    }

    $date = DateTime::createFromFormat('!Y-m-d', $dob);
    if (!$date || $date->format('Y-m-d') !== $dob) {
        return 'Please enter a valid date.';
    }

    $today = new DateTime('today');
    if ($date > $today) {
        return 'Date of birth cannot be in the future.';
    }

    $age = $date->diff($today)->y;

    if ($age > $max_age) {
        return 'Please enter a realistic date of birth.';
    }

    if ($age < $min_age) {
        return 'User must be at least ' . $min_age . ' years old.';
    }

    return '';
}

function val_city($city)
{
    if ($city === '') {
        return 'City is required.';
    }
    if (!val_is_utf8($city)) {
        return 'City contains invalid characters.';
    }
    $length = mb_strlen($city, 'UTF-8');
    if ($length < 2 || $length > 50) {
        return 'City must be between 2 and 50 characters.';
    }
    if (!preg_match('/^[\p{L}\p{M} \'’.\-]+$/uD', $city) || !preg_match('/\p{L}/u', $city)) {
        return 'City can contain only letters, spaces and hyphens.';
    }
    return '';
}

function val_choice($value, array $allowed, $label)
{
    if (!in_array($value, $allowed, true)) {
        return $label . ' is not valid.';
    }
    return '';
}

/* ===================== Form Validators ===================== */

function val_validate_registration(array $input)
{
    $data = array(
        'username' => val_clean(isset($input['username']) ? $input['username'] : ''),
        'email'    => val_clean(isset($input['email']) ? $input['email'] : ''),
        'password' => val_raw(isset($input['password']) ? $input['password'] : ''),
        'dob'      => val_clean(isset($input['dob']) ? $input['dob'] : ''),
        'city'     => val_clean(isset($input['city']) ? $input['city'] : ''),
    );

    $errors = val_filter_errors(array(
        'username' => val_username($data['username']),
        'email'    => val_email($data['email']),
        'password' => val_password($data['password'], true),
        'dob'      => val_dob($data['dob']),
        'city'     => val_city($data['city']),
    ));

    $old = $data;
    unset($old['password']); 

    $data['email'] = strtolower($data['email']);

    return array('valid' => empty($errors), 'errors' => $errors, 'data' => $data, 'old' => $old);
}

/* دالة التحقق الخاصة بصفحة تسجيل الدخول (تم إرجاعها هنا بنجاح) */
function val_validate_login(array $input)
{
    $data = array(
        'email'    => val_clean(isset($input['email']) ? $input['email'] : ''),
        'password' => val_raw(isset($input['password']) ? $input['password'] : ''),
    );

    $errors = val_filter_errors(array(
        'email'    => val_email($data['email']),
        'password' => val_password($data['password'], false),
    ));

    return array('valid' => empty($errors), 'errors' => $errors, 'data' => $data);
}

/* ===================== Database Uniqueness Checks ===================== */

function val_username_unique($connect, $username, $user_id = 0)
{
    try {
        $stmt = $connect->prepare('SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1');
        $stmt->execute(array($username, (int) $user_id));
        if ($stmt->fetch()) {
            return 'This username is already taken.';
        }
    } catch (PDOException $e) {
        return 'Could not check the username right now. Please try again.';
    }
    return '';
}

function val_username_unique_register($connect, $username)
{
    try {
        $stmt = $connect->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute(array($username));
        if ($stmt->fetch()) {
            return 'this username is already taken';
        }
    } catch (PDOException $e) {
        return 'Could not check the username right now. Please try again.';
    }
    return '';
}

/* ===================== Chat ===================== */

function val_chat_message($message)
{
    if ($message === '') {
        return 'Message is required.';
    }
    if (mb_strlen($message, 'UTF-8') > 1000) {
        return 'Message must not exceed 1000 characters.';
    }
    return '';
}


/* ===================== Skills (MySkills.php) ===================== */

/* المهارات المسموحة (لازم تطابق خيارات الـ select في MySkills.php بالظبط) */
function val_skill_titles()
{
    return array(
        'Front End', 'Back End', 'Full Stack', 'HTML', 'CSS', 'JavaScript', 'TypeScript',
        'PHP', 'Python', 'Java', 'C', 'C++', 'C#', 'Flutter', 'Database', 'Graphic Design',
        'UI/UX', 'Video Editing', 'Photography', 'Drawing', 'Content Writing', 'Translation',
        'English Teaching', 'Arabic Teaching', 'Math Tutoring', 'Marketing', 'Digital Marketing',
        'Social Media', 'Accounting', 'Business', 'Project Management', 'Public Speaking',
        'Music', 'Cooking', 'Fitness', 'Other',
    );
}

/* اسم المهارة لازم يكون واحد من القايمة بالظبط */
function val_skill_title($title)
{
    if ($title === '') {
        return 'Please select a skill.';
    }
    if (!in_array($title, val_skill_titles(), true)) {
        return 'Please choose a skill from the list.';
    }
    return '';
}

/*
 only letters, numbers and simple punctuation no code
 */
function val_plain_text($text, $label, $min = 1, $max = 1000)
{
    if ($text === '') {
        return $label . ' is required.';
    }
    if (!val_is_utf8($text)) {                       // النص لازم يبقى سليم
        return $label . ' contains invalid characters.';
    }
    $length = mb_strlen($text, 'UTF-8');             // بنعد الحروف (العربي كمان)
    if ($length < $min) {
        return $label . ' must be at least ' . $min . ' characters.';
    }
    if ($length > $max) {
        return $label . ' must not exceed ' . $max . ' characters.';
    }
    // لازم كل حرف يكون من المسموح، وفيه حرف أو رقم واحد على الأقل
    if (!preg_match('/^[\p{L}\p{M}\p{N} \n\r\t.,!?:\'’"()\/&+#%\-_]+$/uD', $text) || !preg_match('/[\p{L}\p{N}]/u', $text)) {
        return $label . ' can contain only letters, numbers and simple punctuation (no code).';
    }
    return '';
}

/*
 * فحص فورم المهارة كله (الحقول: skill_title و skill_desc)
 */
function val_validate_skill(array $input)
{
    $data = array(
        'title'       => val_clean(isset($input['skill_title']) ? $input['skill_title'] : ''),
        'description' => val_clean(isset($input['skill_desc']) ? $input['skill_desc'] : ''),
    );

    $errors = val_filter_errors(array(
        'title'       => val_skill_title($data['title']),
        'description' => val_plain_text($data['description'], 'Description', 3, 1000),
    ));

    return array('valid' => empty($errors), 'errors' => $errors, 'data' => $data);
}

