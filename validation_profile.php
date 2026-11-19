<?php


/* البايو: اختيارية، وأقصى حاجة 500 حرف */
function val_bio($bio, $max_length = 500)
{
    if ($bio === '') {
        return '';                                   // فاضية؟ عادي
    }
    if (!val_is_utf8($bio)) {                        // النص لازم يبقى سليم
        return 'Bio contains invalid characters.';
    }
    if (mb_strlen($bio, 'UTF-8') > $max_length) {    // بنعد الحروف (عربي وانجلش)
        return 'Bio must not exceed ' . $max_length . ' characters.';
    }
    if (preg_match('/[<>]/', $bio)) {                // نمنع < و > عشان مفيش HTML ولا سكريبت ولا PHP يتكتب
        return 'Bio can contain text only. The characters < and > are not allowed.';
    }
    if (preg_match('/[^\P{Cc}\n\r\t]/u', $bio)) {    // نمنع الرموز الخفية، ونسمح بالسطر الجديد
        return 'Bio contains invalid characters.';
    }
    return '';
}

/* الاسم مايكونش واخده مستخدم تاني (بنستثني صاحب الحساب نفسه) */
if (!function_exists('val_username_unique')) {       // لو الدالة موجودة قبل كده منعيدهاش
    function val_username_unique($connect, $username, $user_id)
    {
        try {
            $stmt = $connect->prepare('SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1');
            $stmt->execute(array($username, (int) $user_id));
            if ($stmt->fetch()) {                    // لقينا حد تاني بنفس الاسم
                return 'This username is already taken.';
            }
        } catch (PDOException $e) {
            // لو الداتابيز فيها مشكلة، نمنع الحفظ أأمن
            return 'Could not check the username right now. Please try again.';
        }
        return '';
    }
}


function val_profile_image($file, $max_bytes = 2097152)   // 2 ميجا
{
    $result = array('error' => '', 'ext' => '');

    // مفيش صورة ؟ عادي، الصورة القديمة تفضل
    if (!is_array($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $result;
    }

    // لو اتبعتت مصفوفة بدل ملف واحد نرفض
    if (!is_int($file['error']) || !isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
        $result['error'] = 'Invalid image upload.';
        return $result;
    }

    // الملف أكبر من المسموح في إعدادات PHP
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $result['error'] = 'Image is too large (maximum 2 MB).';
        return $result;
    }

    // أي مشكلة تانية في الرفع
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Image upload failed. Please try again.';
        return $result;
    }

    // الملف المؤقت لازم يكون موجود
    if (!is_file($file['tmp_name'])) {
        $result['error'] = 'Invalid image upload.';
        return $result;
    }

    // الحجم: مش فاضي ومش كبير
    $size = filesize($file['tmp_name']);
    if ($size === false || $size === 0) {
        $result['error'] = 'Image file is empty.';
        return $result;
    }
    if ($size > $max_bytes) {
        $result['error'] = 'Image is too large (maximum 2 MB).';
        return $result;
    }

    // getimagesize بتقرا الملف من جوه: لو مش صورة حقيقية بترفضه
    $info  = @getimagesize($file['tmp_name']);
       $types = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
    if ($info === false || !isset($info['mime']) || !isset($types[$info['mime']])) {
                $result['error'] = 'Only JPG, JPEG, PNG or WEBP images are allowed.';
        return $result;
    }

    // الصورة سليمة: نرجّع الامتداد الحقيقي
    $result['ext'] = $types[$info['mime']];
    return $result;
}


function val_validate_profile_update(array $input, array $files, $connect, $user_id)
{
    // ننضف القيم (بنشيل المسافات)
    $data = array(
        'username' => val_clean(isset($input['username']) ? $input['username'] : ''),
        'city'     => val_clean(isset($input['city']) ? $input['city'] : ''),
        'bio'      => val_clean(isset($input['bio']) ? $input['bio'] : ''),
    );

    // نفحص كل حقل
    $errors = array(
        'id'       => ((int) $user_id > 0) ? '' : 'Invalid user.',
        'username' => val_username($data['username']),
        'city'     => val_city($data['city']),
        'bio'      => val_bio($data['bio']),
    );

    // نسأل الداتابيز عن تكرار الاسم، بس لو الاسم سليم أصلاً
    if ($errors['username'] === '' && (int) $user_id > 0) {
        $errors['username'] = val_username_unique($connect, $data['username'], $user_id);
    }

    // نفحص الصورة
    $image = val_profile_image(isset($files['img_profile']) ? $files['img_profile'] : null);
    $errors['img_profile'] = $image['error'];

    $errors = val_filter_errors($errors);            // نشيل الرسائل الفاضية

    // لو فيه صورة سليمة نطلع لها اسم عشوائي بالامتداد الحقيقي
    $new_image = null;
    if ($image['error'] === '' && $image['ext'] !== '') {
        $new_image = array('ext' => $image['ext'], 'new_name' => bin2hex(random_bytes(10)) . '.' . $image['ext']);
    }

    return array('valid' => empty($errors), 'errors' => $errors, 'data' => $data, 'image' => $new_image);
}