<?php


/* بتقرأ نص وتشيل المسافات. لو القيمة مصفوفة بترجّع فاضي */
if (!function_exists('val_clean')) {
    function val_clean($value)
    {
        if (!is_scalar($value)) {
            return '';
        }
        return trim((string) $value);
    }
}

/* بتتأكد إن النص UTF-8 سليم */
if (!function_exists('val_is_utf8')) {
    function val_is_utf8($value)
    {
        return mb_check_encoding($value, 'UTF-8');
    }
}

/* بتشيل الرسائل الفاضية، فلو المصفوفة فاضية يبقى مفيش أخطاء */
if (!function_exists('val_filter_errors')) {
    function val_filter_errors(array $errors)
    {
        return array_filter($errors, function ($message) {
            return $message !== '';
        });
    }
}

/* أسماء المستخدمين المحجوزة */
if (!function_exists('val_reserved_usernames')) {
    function val_reserved_usernames()
    {
        return array('admin', 'administrator', 'root', 'superuser', 'system', 'support', 'moderator');
    }
}

/* اسم المستخدم: من 3 لـ 30 حرف، حروف (أي لغة) وأرقام و _ ، ومش محجوز */
if (!function_exists('val_username')) {
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
}

/* الإيميل: مطلوب، بحد أقصى 100 حرف، وشكله صحيح (فحص الشكل بس، من غير داتابيز) */
if (!function_exists('val_email')) {
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
}

/* تاريخ الميلاد: تاريخ حقيقي (YYYY-MM-DD)، مش في المستقبل، ومش أقدم من 120 سنة */
if (!function_exists('val_dob')) {
    function val_dob($dob, $max_age = 120)
    {
        if ($dob === '') {
            return 'Date of birth is required.';
        }
        $date = DateTime::createFromFormat('!Y-m-d', $dob);
        if (!$date || $date->format('Y-m-d') !== $dob) {
            return 'Please enter a valid date.';
        }
        $today = new DateTime('today');
        if ($date > $today) {
            return 'Date of birth cannot be in the future.';
        }
        if ($date->diff($today)->y > $max_age) {
            return 'Please enter a realistic date of birth.';
        }
        return '';
    }
}

/* ===== دوال صفحة تعديل المستخدم (Admin/users.php) ===== */

/* يحوّل الـ id لرقم صحيح موجب. لو مش صالح يرجّع 0 */
function val_int_id($value)
{
    if (!is_scalar($value)) {
        return 0;
    }
    $value = trim((string) $value);
    if (!ctype_digit($value) || strlen($value) > 10) {
        return 0;
    }
    return (int) $value;
}

/* كلمة البحث: نص عادي، بدون مسافات زيادة، وبحد أقصى 50 حرف */
function val_search($value)
{
    $value = val_clean($value);
    if (!val_is_utf8($value)) {
        return '';
    }
    return mb_substr($value, 0, 50, 'UTF-8');
}

/* بيتأكد إن القيمة واحدة من الاختيارات المسموحة (للـ select) */
function val_choice($value, array $allowed, $label)
{
    if (!in_array($value, $allowed, true)) {
        return $label . ' is not valid.';
    }
    return '';
}

/* المدينة في لوحة التحكم: زي val_city بس بتقبل أرقام (زي "10 ramdan") */
function val_city_allow_digits($city)
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
    if (!preg_match('/^[\p{L}\p{M}\p{N} \'’.\-]+$/uD', $city) || !preg_match('/\p{L}/u', $city)) {
        return 'City can contain only letters, numbers, spaces and hyphens.';
    }
    return '';
}

/* تاريخ الميلاد للأدمن: نفس val_dob + السن 18 سنة على الأقل */
function val_dob_adult($dob, $min_age = 18)
{
    $error = val_dob($dob);
    if ($error !== '') {
        return $error;
    }
    $date  = DateTime::createFromFormat('!Y-m-d', $dob);
    $today = new DateTime('today');
    if ($date->diff($today)->y < $min_age) {
        return 'User must be at least ' . $min_age . ' years old.';
    }
    return '';
}

/* بيفحص فورم تعديل المستخدم كله، وبيرجّع valid و errors و data */
function val_validate_user_update(array $input, $user_id)
{
    $data = array(
        'username' => val_clean(isset($input['username']) ? $input['username'] : ''),
        'email'    => val_clean(isset($input['email']) ? $input['email'] : ''),
        'dob'      => val_clean(isset($input['dob']) ? $input['dob'] : ''),
        'city'     => val_clean(isset($input['city']) ? $input['city'] : ''),
        'status'   => val_clean(isset($input['status']) ? $input['status'] : ''),
        'role'     => val_clean(isset($input['role']) ? $input['role'] : ''),
    );

    $errors = val_filter_errors(array(
        'id'       => ((int) $user_id > 0) ? '' : 'Invalid user id.',
        'username' => val_username($data['username']),
        'email'    => val_email($data['email']),
        'dob'      => val_dob_adult($data['dob']),
        'city'     => val_city_allow_digits($data['city']),
        'status'   => val_choice($data['status'], array('0', '1'), 'Status'),
        'role'     => val_choice($data['role'], array('user', 'admin'), 'Role'),
    ));

    $data['email'] = strtolower($data['email']);

    return array('valid' => empty($errors), 'errors' => $errors, 'data' => $data);
}

/* اسم المستخدم لازم يكون غير مكرر، ومنحسبش صاحب الحساب نفسه */
function val_username_unique($connect, $username, $user_id)
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

/*
 * الإيميل لازم يكون غير مكرر في جدول users.
 * $connect = اتصال PDO
 * $user_id = id المستخدم اللي بيتعدل (بنستثنيه عشان يقدر يحتفظ بإيميله)
 * بنقارن بحروف صغيرة عشان Test@x.com و test@x.com يتعتبروا نفس الإيميل
 */
if (!function_exists('val_email_unique')) {
    function val_email_unique($connect, $email, $user_id = 0)
    {
        try {
            $stmt = $connect->prepare('SELECT user_id FROM users WHERE LOWER(email) = ? AND user_id != ? LIMIT 1');
            $stmt->execute(array(strtolower($email), (int) $user_id));
            if ($stmt->fetch()) {
                return 'This email is already registered.';
            }
        } catch (PDOException $e) {
            return 'Could not check the email right now. Please try again.';
        }
        return '';
    }
}