<?php
session_start();

if (isset($_SESSION['user_login'])) {
    include('includes/db/db.php');
    include('validation.php');

    // 1. اليوزر الحالي
    $stmt = $connect->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(array($_SESSION['user_login']));
    $currentUser = $stmt->fetch();
    $user_id = $currentUser['user_id'];

    $with_id = isset($_GET['with']) ? (int)$_GET['with'] : 0;
    $chatError = '';
    $oldMessage = '';

    // جلب الرسائل الجديدة للجافاسكريبت من غير ريفرش الصفحة
    if (isset($_GET['poll'])) {
        header('Content-Type: application/json; charset=utf-8');

        $after_id = isset($_GET['after_id']) ? (int)$_GET['after_id'] : 0;
        $pollMessages = array();

        if ($with_id > 0 && $with_id != $user_id) {
            $stmtOk = $connect->prepare("SELECT request_id FROM requests WHERE status = 'accepted' AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
            $stmtOk->execute(array($user_id, $with_id, $with_id, $user_id));

            if ($stmtOk->fetch()) {
                $stmtMsgs = $connect->prepare(
                    "SELECT message_id, sender_id, message_content, is_system, created_at
                     FROM messages
                     WHERE message_id > ?
                       AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                     ORDER BY message_id ASC"
                );
                $stmtMsgs->execute(array($after_id, $user_id, $with_id, $with_id, $user_id));
                $rows = $stmtMsgs->fetchAll();

                foreach ($rows as $row) {
                    if (isset($row['is_system']) && $row['is_system'] == '1') {
                        $type = 'system';
                    } elseif ((int)$row['sender_id'] === (int)$user_id) {
                        $type = 'outgoing';
                    } else {
                        $type = 'incoming';
                    }

                    $pollMessages[] = array(
                        'id' => (int)$row['message_id'],
                        'type' => $type,
                        'text' => $row['message_content'],
                        'time' => date('H:i', strtotime($row['created_at'])),
                    );
                }

                if (!empty($pollMessages)) {
                    $stmtRead = $connect->prepare("UPDATE messages SET is_read = '1' WHERE sender_id = ? AND receiver_id = ? AND is_read = '0'");
                    $stmtRead->execute(array($with_id, $user_id));
                }
            }
        }

        echo json_encode(array('ok' => true, 'messages' => $pollMessages));
        exit();
    }

    // 2. إرسال رسالة (قبل الـ HTML عشان AJAX يرجع JSON من غير ريفرش)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
        $with_id = isset($_POST['with']) ? (int)$_POST['with'] : 0;
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $oldMessage = $message;
        $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

        $msgError = val_chat_message($message);

        if ($msgError != '') {
            $chatError = $msgError;
        } elseif ($with_id <= 0 || $with_id == $user_id) {
            $chatError = 'Please select a conversation.';
        } else {
            $stmtOk = $connect->prepare("SELECT request_id FROM requests WHERE status = 'accepted' AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
            $stmtOk->execute(array($user_id, $with_id, $with_id, $user_id));
            $okChat = $stmtOk->fetch();

            if ($okChat) {
                $stmtIns = $connect->prepare("INSERT INTO messages (sender_id, receiver_id, message_content, is_system, is_read) VALUES (?, ?, ?, '0', '0')");
                $stmtIns->execute(array($user_id, $with_id, $message));
                $newId = (int) $connect->lastInsertId();
                $sentMessage = array(
                    'id' => $newId,
                    'type' => 'outgoing',
                    'text' => $message,
                    'time' => date('H:i'),
                );

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(array('ok' => true, 'message' => $sentMessage));
                    exit();
                }

                header("Location: chat.php?with=" . $with_id);
                exit();
            } else {
                $chatError = 'You can only chat after a request is accepted.';
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(array('ok' => false, 'error' => $chatError !== '' ? $chatError : 'Could not send message.'));
            exit();
        }
    }

    include('includes/temp/header.php');
    $activePage = 'chat';
    include('includes/temp/sidebar.php');

    // 3. الطلبات المقبولة عشان نعمل ليستة المحادثات
    $stmtReq = $connect->prepare("SELECT * FROM requests WHERE status = 'accepted' AND (sender_id = ? OR receiver_id = ?) ORDER BY updated_at DESC");
    $stmtReq->execute(array($user_id, $user_id));
    $acceptedReqs = $stmtReq->fetchAll();

    $conversations = array();
    $seen = array();

    foreach ($acceptedReqs as $req) {
        // الطرف التاني في الشات
        if ($req['sender_id'] == $user_id) {
            $other_id = $req['receiver_id'];
        } else {
            $other_id = $req['sender_id'];
        }

        if (isset($seen[$other_id])) {
            continue;
        }
        $seen[$other_id] = true;

        $stmtUser = $connect->prepare("SELECT user_id, username, img_profile FROM users WHERE user_id = ?");
        $stmtUser->execute(array($other_id));
        $other = $stmtUser->fetch();

        $stmtSkill = $connect->prepare("SELECT title FROM skills WHERE skill_id = ?");
        $stmtSkill->execute(array($req['skill_id']));
        $skill = $stmtSkill->fetch();

        // آخر رسالة
        $stmtLast1 = $connect->prepare("SELECT message_content, created_at FROM messages WHERE sender_id = ? AND receiver_id = ? ORDER BY message_id DESC LIMIT 1");
        $stmtLast1->execute(array($user_id, $other_id));
        $last1 = $stmtLast1->fetch();

        $stmtLast2 = $connect->prepare("SELECT message_content, created_at FROM messages WHERE sender_id = ? AND receiver_id = ? ORDER BY message_id DESC LIMIT 1");
        $stmtLast2->execute(array($other_id, $user_id));
        $last2 = $stmtLast2->fetch();

        $last_message = '';
        $last_message_at = '';
        if ($last1 && $last2) {
            if (strtotime($last1['created_at']) >= strtotime($last2['created_at'])) {
                $last_message = $last1['message_content'];
                $last_message_at = $last1['created_at'];
            } else {
                $last_message = $last2['message_content'];
                $last_message_at = $last2['created_at'];
            }
        } elseif ($last1) {
            $last_message = $last1['message_content'];
            $last_message_at = $last1['created_at'];
        } elseif ($last2) {
            $last_message = $last2['message_content'];
            $last_message_at = $last2['created_at'];
        }

        $conversations[] = array(
            'other_id' => $other['user_id'],
            'other_name' => $other['username'],
            'other_img' => $other['img_profile'],
            'skill_title' => $skill ? $skill['title'] : '',
            'request_type' => $req['request_type'],
            'last_message' => $last_message,
            'last_message_at' => $last_message_at
        );
    }

    if ($with_id <= 0 && !empty($conversations)) {
        $with_id = (int)$conversations[0]['other_id'];
    }

    // 4. المحادثة المفتوحة
    $activePartner = null;
    $messages = array();

    if ($with_id > 0 && $with_id != $user_id) {
        $stmtOk = $connect->prepare("SELECT * FROM requests WHERE status = 'accepted' AND sender_id = ? AND receiver_id = ?");
        $stmtOk->execute(array($user_id, $with_id));
        $reqOpen = $stmtOk->fetch();

        if (!$reqOpen) {
            $stmtOk = $connect->prepare("SELECT * FROM requests WHERE status = 'accepted' AND sender_id = ? AND receiver_id = ?");
            $stmtOk->execute(array($with_id, $user_id));
            $reqOpen = $stmtOk->fetch();
        }

        if ($reqOpen) {
            $stmtPartner = $connect->prepare("SELECT user_id, username, img_profile, city FROM users WHERE user_id = ?");
            $stmtPartner->execute(array($with_id));
            $activePartner = $stmtPartner->fetch();

            $stmtSkill = $connect->prepare("SELECT title FROM skills WHERE skill_id = ?");
            $stmtSkill->execute(array($reqOpen['skill_id']));
            $skillOpen = $stmtSkill->fetch();

            if ($activePartner) {
                $activePartner['skill_title'] = $skillOpen ? $skillOpen['title'] : '';
                $activePartner['request_type'] = $reqOpen['request_type'];

                $stmtMsgs = $connect->prepare("SELECT * FROM messages WHERE sender_id = ? AND receiver_id = ? ORDER BY message_id ASC");
                $stmtMsgs->execute(array($user_id, $with_id));
                $msgs1 = $stmtMsgs->fetchAll();

                $stmtMsgs2 = $connect->prepare("SELECT * FROM messages WHERE sender_id = ? AND receiver_id = ? ORDER BY message_id ASC");
                $stmtMsgs2->execute(array($with_id, $user_id));
                $msgs2 = $stmtMsgs2->fetchAll();

                $messages = array_merge($msgs1, $msgs2);

                // ترتيب الرسائل حسب الوقت
                usort($messages, function ($a, $b) {
                    return $a['message_id'] - $b['message_id'];
                });

                $stmtRead = $connect->prepare("UPDATE messages SET is_read = '1' WHERE sender_id = ? AND receiver_id = ? AND is_read = '0'");
                $stmtRead->execute(array($with_id, $user_id));
            }
        }
    }
?>

<!-- ===== واجهة الشات ===== -->
<main class="dashboard-main has-home-sidebar">
    <section class="dashboard-content chat-page">
        <div class="chat-shell">

            <!-- قائمة المحادثات -->
            <aside class="chat-list" aria-label="Conversations">
                <h2 class="chat-list-title">Conversations</h2>

                <?php if (!empty($conversations)): ?>
                    <?php foreach ($conversations as $convo): ?>
                        <?php
                        $isActive = ((int)$convo['other_id'] === $with_id);
                        $preview = !empty($convo['last_message']) ? $convo['last_message'] : 'Request accepted';
                        $timeText = '';
                        if (!empty($convo['last_message_at'])) {
                            $timeText = date('H:i', strtotime($convo['last_message_at']));
                        }
                        ?>
                        <a class="chat-list-item<?php echo $isActive ? ' is-active' : ''; ?>" href="chat.php?with=<?php echo (int)$convo['other_id']; ?>">
                            <span class="chat-list-avatar" aria-hidden="true" style="overflow: hidden;">
                                <?php if (!empty($convo['other_img']) && file_exists('uploads/' . $convo['other_img'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($convo['other_img']); ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block;">
                                <?php else: ?>
                                    <i class="bi bi-person"></i>
                                <?php endif; ?>
                            </span>
                            <span class="chat-list-text">
                                <span class="chat-list-name"><?php echo htmlspecialchars($convo['other_name']); ?></span>
                                <span class="chat-list-preview"><?php echo htmlspecialchars($preview); ?></span>
                            </span>
                            <span class="chat-list-time"><?php echo htmlspecialchars($timeText); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="chat-empty-state">No conversations yet.</p>
                <?php endif; ?>
            </aside>

            <!-- نافذة الشات -->
            <section class="chat-thread" aria-label="Active chat">
                <?php if ($activePartner): ?>
                    <header class="chat-thread-header">
                        <div class="chat-thread-person">
                            <span class="chat-thread-avatar" aria-hidden="true" style="overflow: hidden;">
                                <?php if (!empty($activePartner['img_profile']) && file_exists('uploads/' . $activePartner['img_profile'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($activePartner['img_profile']); ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block;">
                                <?php else: ?>
                                    <i class="bi bi-person"></i>
                                <?php endif; ?>
                            </span>
                            <div class="chat-thread-meta">
                                <h2 class="chat-thread-name"><?php echo htmlspecialchars($activePartner['username']); ?></h2>
                                <p class="chat-thread-status">
                                    <?php echo htmlspecialchars($activePartner['skill_title']); ?>
                                    ·
                                    <?php echo ($activePartner['request_type'] == 'skill_swap') ? 'Skill Swap' : 'Learn'; ?>
                                </p>
                            </div>
                        </div>
                    </header>

                    <?php
                    $chatLastId = 0;
                    if (!empty($messages)) {
                        $chatLastMsg = end($messages);
                        $chatLastId = (int)$chatLastMsg['message_id'];
                    }
                    ?>
                    <div class="chat-messages" id="chat-messages" data-with="<?php echo (int)$with_id; ?>" data-last-id="<?php echo $chatLastId; ?>">
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $msg): ?>
                                <?php
                                if (isset($msg['is_system']) && $msg['is_system'] == '1') {
                                    $msgClass = 'chat-message is-system';
                                } elseif ($msg['sender_id'] == $user_id) {
                                    $msgClass = 'chat-message is-outgoing';
                                } else {
                                    $msgClass = 'chat-message is-incoming';
                                }
                                ?>
                                <div class="<?php echo $msgClass; ?>" data-msg-id="<?php echo (int)$msg['message_id']; ?>">
                                    <p class="chat-message-bubble"><?php echo htmlspecialchars($msg['message_content']); ?></p>
                                    <span class="chat-message-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="chat-empty-state" id="chat-empty-state">No messages yet.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($chatError != ''): ?>
                        <p class="chat-send-error"><?php echo htmlspecialchars($chatError); ?></p>
                    <?php endif; ?>

                    <form class="chat-composer" id="chat-composer" action="chat.php?with=<?php echo (int)$with_id; ?>" method="post">
                        <label class="chat-composer-label" for="chat-input">Message</label>
                        <input type="hidden" name="with" value="<?php echo (int)$with_id; ?>">
                        <input class="chat-composer-input" id="chat-input" name="message" type="text" placeholder="Type a message" maxlength="1000" autocomplete="off" value="<?php echo htmlspecialchars($oldMessage); ?>" required autofocus>
                        <button class="btn-primary chat-composer-send" type="submit" name="send_message" value="1">Send</button>
                    </form>
                <?php else: ?>
                    <div class="chat-thread-empty">
                        <p class="chat-empty-state">Select a conversation to start.</p>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </section>
</main>

<?php if ($activePartner): ?>
<script>
(function () {
    var box = document.getElementById('chat-messages');
    var form = document.getElementById('chat-composer');
    var input = document.getElementById('chat-input');
    var errorBox = document.querySelector('.chat-send-error');
    if (!box || !form || !input) {
        return;
    }

    var withId = box.getAttribute('data-with');
    var lastId = parseInt(box.getAttribute('data-last-id') || '0', 10);
    var busy = false;
    var sending = false;

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function showError(text) {
        if (!errorBox) {
            errorBox = document.createElement('p');
            errorBox.className = 'chat-send-error';
            form.parentNode.insertBefore(errorBox, form);
        }
        errorBox.textContent = text || '';
        errorBox.hidden = !text;
    }

    function appendMessage(msg) {
        if (box.querySelector('[data-msg-id="' + msg.id + '"]')) {
            return;
        }

        var empty = document.getElementById('chat-empty-state');
        if (empty) {
            empty.remove();
        }

        var wrap = document.createElement('div');
        wrap.className = 'chat-message is-' + msg.type;
        wrap.setAttribute('data-msg-id', String(msg.id));
        wrap.innerHTML =
            '<p class="chat-message-bubble">' + escapeHtml(msg.text) + '</p>' +
            '<span class="chat-message-time">' + escapeHtml(msg.time) + '</span>';
        box.appendChild(wrap);
        box.scrollTop = box.scrollHeight;

        if (msg.id > lastId) {
            lastId = msg.id;
            box.setAttribute('data-last-id', String(lastId));
        }
    }

    function focusInput() {
        input.focus({ preventScroll: true });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (sending) {
            return;
        }

        var text = input.value.trim();
        if (!text) {
            showError('Message is required.');
            focusInput();
            return;
        }

        sending = true;
        showError('');

        var body = new FormData(form);
        body.set('message', text);
        body.set('send_message', '1');
        body.set('ajax', '1');

        fetch(form.getAttribute('action') || ('chat.php?with=' + encodeURIComponent(withId)), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            body: body
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.data || !result.data.ok || !result.data.message) {
                    showError((result.data && result.data.error) ? result.data.error : 'Could not send message.');
                    return;
                }

                appendMessage(result.data.message);
                input.value = '';
            })
            .catch(function () {
                showError('Could not send message. Please try again.');
            })
            .then(function () {
                sending = false;
                focusInput();
            });
    });

    function poll() {
        if (busy || document.hidden) {
            return;
        }

        busy = true;
        fetch('chat.php?poll=1&with=' + encodeURIComponent(withId) + '&after_id=' + encodeURIComponent(lastId), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.ok || !data.messages || !data.messages.length) {
                    return;
                }

                data.messages.forEach(function (msg) {
                    if (msg.id <= lastId) {
                        return;
                    }
                    appendMessage(msg);
                });
            })
            .catch(function () {})
            .then(function () {
                busy = false;
            });
    }

    box.scrollTop = box.scrollHeight;
    focusInput();
    setInterval(poll, 2000);
})();
</script>
<?php endif; ?>

<?php
    include('includes/temp/footer.php');
} else {
    $_SESSION['error_login'] = 'You must be logged in as a user to access this page';
    header('location: login.php');
    exit();
}
?>
