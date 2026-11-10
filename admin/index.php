<?php
session_start();

// === НАСТРОЙКИ ===
$usersFile = __DIR__ . '/users.json';
$submissionsFile = __DIR__ . '/../data/submissions.json';
$newsFile = __DIR__ . '/news.json';
$logFile = __DIR__ . '/activity.log';

// === ФУНКЦИИ ===
function getUsers($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function saveUsers($file, $users) {
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getSubmissions($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function saveSubmissions($file, $list) {
    file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getNews($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function saveNews($file, $news) {
    file_put_contents($file, json_encode($news, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function logAction($file, $action) {
    $timestamp = date('d.m.Y H:i:s');
    $username = $_SESSION['username'] ?? 'unknown';
    $logEntry = "[$timestamp] $username: $action\n";
    file_put_contents($file, $logEntry, FILE_APPEND);
}

function checkPermission($role, $action) {
    $permissions = [
        'admin' => ['view', 'edit', 'delete', 'manage_users', 'manage_news'],
        'manager' => ['view', 'edit', 'delete'],
        'doctor' => ['view'],
        'editor' => ['view', 'manage_news']
    ];
    return in_array($action, $permissions[$role] ?? []);
}

// === ОБРАБОТКА ДЕЙСТВИЙ ===

// Выход
if (isset($_GET['logout'])) {
    logAction($logFile, 'Вышел из системы');
    session_destroy();
    header('Location: index.php');
    exit;
}

// Вход
if (isset($_POST['username']) && isset($_POST['password'])) {
    $users = getUsers($usersFile);
    $inputUser = trim($_POST['username']);
    $inputPass = trim($_POST['password']);
    
    $found = false;
    foreach ($users as $user) {
        if ($user['username'] === $inputUser && $user['password'] === $inputPass) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['ok'] = true;
            $found = true;
            logAction($logFile, 'Вошёл в систему');
            header('Location: index.php');
            exit;
        }
    }
    
    if (!$found) {
        header('Location: index.php?error=1');
        exit;
    }
}

// Если не авторизован — показываем форму входа
if (!isset($_SESSION['ok'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Beauty Smile</title>
    <link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700&family=Prata&display=swap" rel="stylesheet">
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: "Golos Text", sans-serif;
        background: linear-gradient(135deg, #23627C 0%, #23BBB7 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .box {
        background: white;
        padding: 50px 40px;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 420px;
        text-align: center;
    }
    .logo-icon {
        width: 80px; height: 80px;
        background: linear-gradient(135deg, #23627C, #23BBB7);
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        font-size: 40px;
    }
    .logo { font-family: "Prata", serif; font-size: 32px; color: #23627C; margin-bottom: 8px; }
    .subtitle { color: #5A7A8C; font-size: 14px; margin-bottom: 30px; }
    .input-group { margin-bottom: 16px; text-align: left; }
    .input-group label { display: block; font-size: 13px; font-weight: 600; color: #23627C; margin-bottom: 6px; }
    input {
        width: 100%; padding: 14px 16px;
        border: 2px solid #D3EDEF; border-radius: 12px;
        font-size: 15px; font-family: inherit; outline: none;
    }
    input:focus { border-color: #23BBB7; box-shadow: 0 0 0 4px rgba(35, 187, 183, 0.1); }
    button {
        width: 100%; padding: 16px;
        background: #23BBB7; color: white; border: none; border-radius: 12px;
        font-size: 16px; font-weight: 700; cursor: pointer; font-family: inherit;
        margin-top: 8px;
    }
    button:hover { background: #1ea8a4; }
    .err { background: #ffe0e0; color: #c0392b; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
    .hint {
        margin-top: 20px; padding: 16px;
        background: #F4F9F8; border-radius: 12px;
        font-size: 12px; color: #5A7A8C; text-align: left;
    }
    .hint strong { color: #23627C; }
    </style>
    </head>
    <body>
    <div class="box">
        <div class="logo-icon">🦷</div>
        <div class="logo">Beauty Smile</div>
        <div class="subtitle">Панель управления клиникой</div>
        <?php if (isset($_GET['error'])): ?>
            <div class="err">❌ Неверный логин или пароль</div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <label>Логин</label>
                <input type="text" name="username" placeholder="Введите логин" required autofocus>
            </div>
            <div class="input-group">
                <label>Пароль</label>
                <input type="password" name="password" placeholder="Введите пароль" required>
            </div>
            <button type="submit">Войти в систему</button>
        </form>
        <div class="hint">
            <strong>Тестовые аккаунты:</strong><br>
            👑 Админ: <code>admin</code> / <code>admin2026</code><br>
            👨‍💼 Менеджер: <code>manager</code> / <code>manager2026</code><br>
            👨‍⚕️ Врач: <code>doctor</code> / <code>doctor2026</code><br>
            ✏️ Редактор: <code>editor</code> / <code>editor2026</code>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// === АВТОРИЗОВАННЫЙ ПОЛЬЗОВАТЕЛЬ ===
$currentRole = $_SESSION['role'];
$currentUser = $_SESSION['user_name'];

// === ЗАЯВКИ ===
$list = getSubmissions($submissionsFile);

// Обработка заявки (запись на приём)
if (isset($_POST['process_submission']) && checkPermission($currentRole, 'edit')) {
    $id = $_POST['process_submission'];
    $appointmentDate = $_POST['appointment_date'];
    $appointmentTime = $_POST['appointment_time'];
    $procedure = $_POST['procedure'];
    $price = $_POST['price'];
    
    foreach ($list as &$s) {
        if ($s['id'] === $id) {
            $s['read'] = true;
            $s['appointment'] = [
                'date' => $appointmentDate,
                'time' => $appointmentTime,
                'procedure' => $procedure,
                'price' => $price
            ];
            break;
        }
    }
    unset($s);
    saveSubmissions($submissionsFile, $list);
    logAction($logFile, "Обработал заявку $id: запись на $appointmentDate $appointmentTime, $procedure, $price₽");
    header('Location: index.php');
    exit;
}

// Отметить как обработанное (без деталей)
if (isset($_GET['mark_done']) && checkPermission($currentRole, 'edit')) {
    $id = $_GET['mark_done'];
    foreach ($list as &$s) {
        if ($s['id'] === $id) { $s['read'] = true; break; }
    }
    unset($s);
    saveSubmissions($submissionsFile, $list);
    logAction($logFile, "Отметил заявку $id как обработанную");
    header('Location: index.php');
    exit;
}

// Удалить заявку
if (isset($_GET['delete']) && checkPermission($currentRole, 'delete')) {
    $id = $_GET['delete'];
    $list = array_values(array_filter($list, fn($s) => $s['id'] !== $id));
    saveSubmissions($submissionsFile, $list);
    logAction($logFile, "Удалил заявку $id");
    header('Location: index.php');
    exit;
}

// Очистить все
if (isset($_GET['clear']) && $_GET['clear'] === 'yes' && checkPermission($currentRole, 'delete')) {
    saveSubmissions($submissionsFile, '[]');
    logAction($logFile, "Очистил все заявки");
    header('Location: index.php');
    exit;
}

// === НОВОСТИ ===
$news = getNews($newsFile);

// Добавить новость
if (isset($_POST['add_news']) && checkPermission($currentRole, 'manage_news')) {
    $newsImage = '';
    
    if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['news_image']['tmp_name']);
        $imageType = $_FILES['news_image']['type'];
        $newsImage = 'data:' . $imageType . ';base64,' . base64_encode($imageData);
    }
    
    $newNews = [
        'id' => 'news_' . uniqid(),
        'title' => trim($_POST['news_title']),
        'content' => trim($_POST['news_content']),
        'type' => $_POST['news_type'],
        'image' => $newsImage,
        'date' => date('d.m.Y H:i'),
        'active' => true
    ];
    $news[] = $newNews;
    saveNews($newsFile, $news);
    logAction($logFile, "Добавил новость: {$newNews['title']}");
    header('Location: index.php?tab=news');
    exit;
}

// Удалить новость
if (isset($_GET['delete_news']) && checkPermission($currentRole, 'manage_news')) {
    $newsId = $_GET['delete_news'];
    $news = array_values(array_filter($news, fn($n) => $n['id'] !== $newsId));
    saveNews($newsFile, $news);
    logAction($logFile, "Удалил новость $newsId");
    header('Location: index.php?tab=news');
    exit;
}

// Переключить активность новости
if (isset($_GET['toggle_news']) && checkPermission($currentRole, 'manage_news')) {
    $newsId = $_GET['toggle_news'];
    foreach ($news as &$n) {
        if ($n['id'] === $newsId) {
            $n['active'] = !$n['active'];
            break;
        }
    }
    unset($n);
    saveNews($newsFile, $news);
    logAction($logFile, "Переключил активность новости $newsId");
    header('Location: index.php?tab=news');
    exit;
}

// === ПОЛЬЗОВАТЕЛИ ===
$users = getUsers($usersFile);

// Добавить пользователя
if (isset($_POST['new_username']) && checkPermission($currentRole, 'manage_users')) {
    $newUser = [
        'id' => 'user_' . uniqid(),
        'username' => trim($_POST['new_username']),
        'password' => trim($_POST['new_password']),
        'name' => trim($_POST['new_name']),
        'role' => trim($_POST['new_role']),
        'created' => date('d.m.Y')
    ];
    $users[] = $newUser;
    saveUsers($usersFile, $users);
    logAction($logFile, "Создал пользователя {$newUser['username']} ({$newUser['role']})");
    header('Location: index.php?tab=users');
    exit;
}

// Удалить пользователя
if (isset($_GET['delete_user']) && checkPermission($currentRole, 'manage_users')) {
    $userId = $_GET['delete_user'];
    if ($userId !== $_SESSION['user_id']) {
        $users = array_values(array_filter($users, fn($u) => $u['id'] !== $userId));
        saveUsers($usersFile, $users);
        logAction($logFile, "Удалил пользователя $userId");
    }
    header('Location: index.php?tab=users');
    exit;
}

// === СТАТИСТИКА ===
$totalCount = count($list);
$unreadCount = count(array_filter($list, fn($s) => !($s['read'] ?? false)));
$doneCount = $totalCount - $unreadCount;
$newsCount = count($news);
$activeNewsCount = count(array_filter($news, fn($n) => $n['active'] ?? false));

// Текущая вкладка
$currentTab = $_GET['tab'] ?? 'submissions';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Админка | Beauty Smile</title>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700;800&family=Prata&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Golos Text", sans-serif; background: #F4F9F8; color: #23627C; min-height: 100vh; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
@keyframes scaleIn { from { opacity: 0; transform: scale(.9); } to { opacity: 1; transform: scale(1); } }
@keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .7; } }

.header {
    background: linear-gradient(135deg, #23627C 0%, #23BBB7 100%);
    color: white; padding: 20px 40px;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 16px;
    animation: slideDown .6s ease;
    box-shadow: 0 4px 20px rgba(35, 98, 124, .15);
}
.header-left { display: flex; align-items: center; gap: 16px; }
.header-icon {
    width: 48px; height: 48px;
    background: rgba(255,255,255,.2); border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; backdrop-filter: blur(10px);
}
.header h1 { font-family: "Prata", serif; font-size: 24px; font-weight: 400; }
.header-user { display: flex; align-items: center; gap: 12px; font-size: 14px; opacity: .95; }
.role-badge {
    padding: 4px 12px; background: rgba(255,255,255,.2);
    border-radius: 12px; font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .5px;
}
.header-actions { display: flex; gap: 12px; flex-wrap: wrap; }

.btn {
    padding: 10px 20px; border-radius: 10px;
    font-weight: 600; font-size: 14px; text-decoration: none;
    transition: all .3s ease; display: inline-flex;
    align-items: center; gap: 8px; font-family: inherit;
    cursor: pointer; border: none;
}
.btn-white { background: white; color: #23627C; }
.btn-white:hover { background: #D3EDEF; transform: translateY(-2px); }
.btn-danger { background: rgba(255,255,255,.15); color: white; border: 1px solid rgba(255,255,255,.3); }
.btn-danger:hover { background: rgba(255,255,255,.25); transform: translateY(-2px); }

.nav-tabs {
    background: white; padding: 0 40px;
    border-bottom: 1px solid #D3EDEF;
    display: flex; gap: 0;
    animation: fadeIn .6s ease .2s both;
    overflow-x: auto;
}
.nav-tab {
    padding: 16px 24px; font-weight: 600; font-size: 14px;
    color: #5A7A8C; text-decoration: none;
    border-bottom: 3px solid transparent;
    transition: all .3s ease;
    display: flex; align-items: center; gap: 8px;
    white-space: nowrap;
}
.nav-tab:hover { color: #23BBB7; }
.nav-tab.active { color: #23BBB7; border-bottom-color: #23BBB7; }
.nav-tab .count {
    background: #D3EDEF; color: #23627C;
    padding: 2px 8px; border-radius: 10px;
    font-size: 11px; font-weight: 700;
}
.nav-tab.active .count { background: #23BBB7; color: white; }

.container { max-width: 1200px; margin: 0 auto; padding: 40px 24px; animation: fadeIn .8s ease; }

.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
.stat-card {
    background: white; padding: 28px; border-radius: 16px;
    box-shadow: 0 4px 12px rgba(35, 98, 124, .06);
    transition: all .4s ease; animation: scaleIn .6s ease both;
    border: 1px solid rgba(211, 237, 239, .3);
}
.stat-card:nth-child(1) { animation-delay: .1s; }
.stat-card:nth-child(2) { animation-delay: .2s; }
.stat-card:nth-child(3) { animation-delay: .3s; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(35, 98, 124, .12); }
.stat-num { font-family: "Prata", serif; font-size: 42px; color: #23BBB7; line-height: 1; margin-bottom: 8px; }
.stat-label { font-size: 14px; color: #5A7A8C; font-weight: 500; }

.filter-bar { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.filter-btn {
    padding: 10px 20px; background: white;
    border: 2px solid #D3EDEF; border-radius: 24px;
    font-size: 14px; font-weight: 600; color: #23627C;
    cursor: pointer; font-family: inherit; transition: all .3s ease;
}
.filter-btn:hover { border-color: #23BBB7; color: #23BBB7; transform: translateY(-2px); }
.filter-btn.active { background: #23BBB7; color: white; border-color: #23BBB7; }

.submissions-list { display: flex; flex-direction: column; gap: 16px; }
.submission {
    background: white; border-radius: 16px; padding: 24px;
    box-shadow: 0 4px 12px rgba(35, 98, 124, .06);
    display: grid; grid-template-columns: 1fr auto; gap: 20px;
    transition: all .4s ease; border-left: 4px solid #D3EDEF;
    animation: slideIn .6s ease both;
}
.submission.unread { border-left-color: #23BBB7; background: linear-gradient(90deg, rgba(35,187,183,.04), white 30%); }
.submission:hover { transform: translateX(4px); box-shadow: 0 8px 24px rgba(35, 98, 124, .12); }

.sub-info h3 { font-size: 18px; color: #23627C; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; font-weight: 700; }
.badge { background: #23BBB7; color: white; font-size: 10px; padding: 3px 10px; border-radius: 12px; font-weight: 700; text-transform: uppercase; animation: pulse 2s infinite; }
.done-badge { background: #5A7A8C; color: white; font-size: 10px; padding: 3px 10px; border-radius: 12px; font-weight: 700; text-transform: uppercase; }
.sub-info .phone { font-size: 15px; color: #23BBB7; font-weight: 600; margin-bottom: 8px; }
.sub-info .phone a { color: inherit; text-decoration: none; }
.sub-info .message { font-size: 14px; color: #5A7A8C; line-height: 1.6; font-style: italic; margin-bottom: 8px; }
.sub-info .date { font-size: 12px; color: #8fa5b5; }

.appointment-info {
    background: #F4F9F8; padding: 12px 16px; border-radius: 10px;
    margin-top: 12px; border-left: 3px solid #23BBB7;
}
.appointment-info .label { font-size: 11px; color: #5A7A8C; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
.appointment-info .value { font-size: 14px; color: #23627C; font-weight: 600; }

.sub-actions { display: flex; flex-direction: column; gap: 8px; justify-content: center; }
.btn-sm {
    padding: 8px 14px; font-size: 12px; border-radius: 8px;
    text-decoration: none; font-weight: 600; transition: all .3s ease;
    display: inline-flex; align-items: center; gap: 6px;
    text-align: center; border: none; cursor: pointer; font-family: inherit;
}
.btn-process { background: #23BBB7; color: white; }
.btn-process:hover { background: #1ea8a4; transform: translateY(-2px); }
.btn-done { background: #23627C; color: white; }
.btn-done:hover { background: #1a4a5e; transform: translateY(-2px); }
.btn-wa { background: #25D366; color: white; }
.btn-wa:hover { background: #1ebe5d; transform: translateY(-2px); }
.btn-delete { background: #e74c3c; color: white; }
.btn-delete:hover { background: #c0392b; transform: translateY(-2px); }

/* Modal */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(35, 98, 124, 0.7); backdrop-filter: blur(8px);
    z-index: 2000; align-items: center; justify-content: center; padding: 20px;
}
.modal-overlay.active { display: flex; }
.modal {
    background: white; border-radius: 24px; padding: 40px;
    max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto;
    position: relative; animation: scaleIn .3s ease;
}
.modal h3 { font-size: 24px; color: #23627C; margin-bottom: 24px; font-weight: 700; }
.modal-close {
    position: absolute; top: 20px; right: 20px;
    width: 36px; height: 36px; background: #D3EDEF;
    border-radius: 50%; display: grid; place-items: center;
    color: #23627C; font-weight: 700; font-size: 18px;
    cursor: pointer; transition: all .3s ease;
}
.modal-close:hover { background: #23BBB7; color: white; transform: rotate(90deg); }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: 14px; font-weight: 600; color: #23627C; margin-bottom: 8px; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 12px 14px;
    border: 2px solid #D3EDEF; border-radius: 10px;
    font-size: 14px; font-family: inherit; outline: none;
    transition: all .3s ease;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: #23BBB7; box-shadow: 0 0 0 4px rgba(35, 187, 183, .1);
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* News */
.news-section { animation: slideUp .6s ease; }
.news-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.news-header h2 { font-family: "Prata", serif; font-size: 28px; color: #23627C; }
.btn-add { background: #23BBB7; color: white; padding: 12px 24px; }
.btn-add:hover { background: #1ea8a4; transform: translateY(-2px); }

.news-form {
    background: white; padding: 32px; border-radius: 16px;
    box-shadow: 0 4px 12px rgba(35, 98, 124, .06);
    margin-bottom: 32px; animation: slideUp .6s ease;
}
.news-form h3 { font-size: 20px; color: #23627C; margin-bottom: 20px; font-weight: 700; }

.news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
.news-card {
    background: white; padding: 24px; border-radius: 16px;
    box-shadow: 0 4px 12px rgba(35, 98, 124, .06);
    transition: all .4s ease; animation: scaleIn .6s ease both;
    border: 2px solid transparent;
}
.news-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(35, 98, 124, .12); }
.news-card.inactive { opacity: .6; }
.news-card .news-img {
    width: 100%; aspect-ratio: 16/9; border-radius: 12px;
    overflow: hidden; margin-bottom: 12px; background: #D3EDEF;
}
.news-card .news-img img { width: 100%; height: 100%; object-fit: cover; }
.news-type {
    display: inline-block; padding: 4px 12px; border-radius: 12px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    margin-bottom: 12px;
}
.type-news { background: #D3EDEF; color: #23627C; }
.type-promo { background: #FFE5E5; color: #C0392B; }
.news-card h4 { font-size: 18px; color: #23627C; margin-bottom: 8px; font-weight: 700; }
.news-card .content { font-size: 14px; color: #5A7A8C; line-height: 1.6; margin-bottom: 12px; }
.news-card .date { font-size: 12px; color: #8fa5b5; margin-bottom: 16px; }
.news-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* Users */
.users-section { animation: slideUp .6s ease; }
.users-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.users-header h2 { font-family: "Prata", serif; font-size: 28px; color: #23627C; }
.users-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.user-card {
    background: white; padding: 24px; border-radius: 16px;
    box-shadow: 0 4px 12px rgba(35, 98, 124, .06);
    transition: all .4s ease; animation: scaleIn .6s ease both;
    border: 2px solid transparent;
}
.user-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(35, 98, 124, .12); }
.user-card.current { border-color: #23BBB7; }
.user-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, #23627C, #23BBB7);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; margin-bottom: 16px; color: white; font-weight: 700;
}
.user-card h3 { font-size: 18px; color: #23627C; margin-bottom: 4px; font-weight: 700; }
.user-card .username { font-size: 13px; color: #5A7A8C; margin-bottom: 12px; }
.user-role {
    display: inline-block; padding: 4px 12px; border-radius: 12px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    margin-bottom: 16px;
}
.role-admin { background: #FFE5E5; color: #C0392B; }
.role-manager { background: #FFF4E0; color: #D97706; }
.role-doctor { background: #D3EDEF; color: #23627C; }
.role-editor { background: #E8F5E9; color: #2E7D32; }
.user-card .created { font-size: 12px; color: #8fa5b5; margin-bottom: 16px; }
.user-actions { display: flex; gap: 8px; }
.btn-user-delete {
    background: #e74c3c; color: white; padding: 8px 14px;
    font-size: 12px; border-radius: 8px; text-decoration: none;
    font-weight: 600; transition: all .3s ease;
}
.btn-user-delete:hover { background: #c0392b; transform: translateY(-2px); }
.btn-user-delete.disabled { background: #ccc; cursor: not-allowed; }
.btn-user-delete.disabled:hover { transform: none; }

/* Logs */
.log-section { animation: slideUp .6s ease; }
.log-section h2 { font-family: "Prata", serif; font-size: 28px; color: #23627C; margin-bottom: 24px; }
.log-list {
    background: white; border-radius: 16px; padding: 24px;
    box-shadow: 0 4px 12px rgba(35, 98, 124, .06);
    max-height: 500px; overflow-y: auto;
}
.log-entry {
    padding: 12px 0; border-bottom: 1px solid #D3EDEF;
    font-size: 14px; display: flex; gap: 12px;
}
.log-entry:last-child { border-bottom: none; }
.log-time { color: #8fa5b5; font-size: 12px; min-width: 140px; }
.log-user { color: #23BBB7; font-weight: 600; min-width: 120px; }
.log-action { color: #23627C; flex: 1; }

.empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 16px; color: #5A7A8C; }
.empty-state .icon { font-size: 64px; margin-bottom: 16px; opacity: .3; }
.empty-state h3 { font-family: "Prata", serif; font-size: 24px; color: #23627C; margin-bottom: 12px; }

@media (max-width: 768px) {
    .header { padding: 20px; }
    .header h1 { font-size: 18px; }
    .nav-tabs { padding: 0 20px; }
    .container { padding: 24px 16px; }
    .submission { grid-template-columns: 1fr; }
    .sub-actions { flex-direction: row; flex-wrap: wrap; }
    .form-row { grid-template-columns: 1fr; }
    .users-grid, .news-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="header-icon">🦷</div>
        <div>
            <h1>Beauty Smile</h1>
            <div class="header-user">
                <span>👤 <?= htmlspecialchars($currentUser) ?></span>
                <span class="role-badge">
                    <?php
                    $roleLabels = ['admin' => '👑 Админ', 'manager' => '👨‍💼 Менеджер', 'doctor' => '👨‍⚕️ Врач', 'editor' => '✏️ Редактор'];
                    echo $roleLabels[$currentRole] ?? $currentRole;
                    ?>
                </span>
            </div>
        </div>
    </div>
    <div class="header-actions">
        <?php if ($totalCount > 0 && checkPermission($currentRole, 'delete')): ?>
        <a href="?clear=yes" class="btn btn-danger" onclick="return confirm('Удалить все заявки?')">🗑 Очистить</a>
        <?php endif; ?>
        <a href="/" class="btn btn-white">← На сайт</a>
        <a href="?logout=1" class="btn btn-white">Выйти</a>
    </div>
</div>

<div class="nav-tabs">
    <a href="?tab=submissions" class="nav-tab <?= $currentTab === 'submissions' ? 'active' : '' ?>">
        📋 Заявки <span class="count"><?= $unreadCount ?></span>
    </a>
    <?php if (checkPermission($currentRole, 'manage_news')): ?>
    <a href="?tab=news" class="nav-tab <?= $currentTab === 'news' ? 'active' : '' ?>">
        📰 Новости <span class="count"><?= $activeNewsCount ?></span>
    </a>
    <?php endif; ?>
    <?php if (checkPermission($currentRole, 'manage_users')): ?>
    <a href="?tab=users" class="nav-tab <?= $currentTab === 'users' ? 'active' : '' ?>">
        👥 Пользователи <span class="count"><?= count($users) ?></span>
    </a>
    <a href="?tab=logs" class="nav-tab <?= $currentTab === 'logs' ? 'active' : '' ?>">
        📊 Журнал
    </a>
    <?php endif; ?>
</div>

<div class="container">
    <?php if ($currentTab === 'submissions'): ?>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-num"><?= $totalCount ?></div>
                <div class="stat-label">Всего заявок</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= $unreadCount ?></div>
                <div class="stat-label">Новых</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= $doneCount ?></div>
                <div class="stat-label">Обработано</div>
            </div>
        </div>

        <?php if (empty($list)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>Пока нет заявок</h3>
                <p>Когда кто-то заполнит форму на сайте, данные появятся здесь</p>
            </div>
        <?php else: ?>
            <div class="filter-bar">
                <button class="filter-btn active" onclick="filterSubmissions('all', this)">Все (<?= $totalCount ?>)</button>
                <button class="filter-btn" onclick="filterSubmissions('unread', this)">Новые (<?= $unreadCount ?>)</button>
                <button class="filter-btn" onclick="filterSubmissions('read', this)">Обработанные (<?= $doneCount ?>)</button>
            </div>

            <div class="submissions-list" id="submissionsList">
                <?php foreach ($list as $sub): 
                    $isUnread = !($sub['read'] ?? false);
                    $appointment = $sub['appointment'] ?? null;
                ?>
                <div class="submission <?= $isUnread ? 'unread' : '' ?>" data-status="<?= $isUnread ? 'unread' : 'read' ?>">
                    <div class="sub-info">
                        <h3>
                            <?= htmlspecialchars($sub['name']) ?>
                            <?php if ($isUnread): ?><span class="badge">NEW</span><?php else: ?><span class="done-badge">✓ Готово</span><?php endif; ?>
                        </h3>
                        <div class="phone">
                            📞 <a href="tel:<?= preg_replace('/[^0-9+]/', '', $sub['phone']) ?>"><?= htmlspecialchars($sub['phone']) ?></a>
                        </div>
                        <?php if (!empty($sub['message'])): ?>
                            <div class="message">"<?= htmlspecialchars($sub['message']) ?>"</div>
                        <?php endif; ?>
                        
                        <?php if ($appointment): ?>
                        <div class="appointment-info">
                            <div class="label">📅 Запись на приём</div>
                            <div class="value"><?= htmlspecialchars($appointment['date']) ?> в <?= htmlspecialchars($appointment['time']) ?></div>
                            <div class="value" style="margin-top:4px"><?= htmlspecialchars($appointment['procedure']) ?></div>
                            <div class="value" style="margin-top:4px; color:#23BBB7">💰 <?= htmlspecialchars($appointment['price']) ?> ₽</div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="date">📅 Заявка: <?= htmlspecialchars($sub['date']) ?></div>
                    </div>
                    <div class="sub-actions">
                        <?php if ($isUnread && checkPermission($currentRole, 'edit')): ?>
                            <button class="btn-sm btn-process" onclick="openProcessModal('<?= $sub['id'] ?>', '<?= htmlspecialchars($sub['name']) ?>')">
                                ✓ Записать на приём
                            </button>
                        <?php endif; ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $sub['phone']) ?>" target="_blank" class="btn-sm btn-wa">💬 WhatsApp</a>
                        <?php if (checkPermission($currentRole, 'delete')): ?>
                            <a href="?delete=<?= $sub['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Удалить заявку?')">🗑 Удалить</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($currentTab === 'news' && checkPermission($currentRole, 'manage_news')): ?>
        <div class="news-section">
            <div class="news-header">
                <h2>📰 Новости и акции</h2>
            </div>

            <div class="news-form">
                <h3>➕ Добавить новость или акцию</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Тип</label>
                        <select name="news_type" required>
                            <option value="news">📰 Новость</option>
                            <option value="promo">🎉 Акция</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Заголовок</label>
                        <input type="text" name="news_title" placeholder="Например: Скидка 20% на отбеливание" required>
                    </div>
                    <div class="form-group">
                        <label>Содержание</label>
                        <textarea name="news_content" rows="4" placeholder="Описание новости или акции..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>📷 Фотография (необязательно)</label>
                        <input type="file" name="news_image" accept="image/*" style="padding:10px">
                        <div style="font-size:12px;color:#5A7A8C;margin-top:6px">Поддерживаются: JPG, PNG, WebP. Максимум 5MB</div>
                    </div>
                    <button type="submit" name="add_news" class="btn btn-add">Опубликовать</button>
                </form>
            </div>

            <?php if (empty($news)): ?>
                <div class="empty-state">
                    <div class="icon">📝</div>
                    <h3>Новостей пока нет</h3>
                    <p>Добавьте первую новость или акцию выше</p>
                </div>
            <?php else: ?>
                <div class="news-grid">
                    <?php foreach ($news as $item): ?>
                    <div class="news-card <?= !($item['active'] ?? true) ? 'inactive' : '' ?>">
                        <?php if (!empty($item['image'])): ?>
                        <div class="news-img">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        </div>
                        <?php endif; ?>
                        <span class="news-type <?= $item['type'] === 'promo' ? 'type-promo' : 'type-news' ?>">
                            <?= $item['type'] === 'promo' ? '🎉 Акция' : '📰 Новость' ?>
                        </span>
                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                        <div class="content"><?= htmlspecialchars($item['content']) ?></div>
                        <div class="date">📅 <?= htmlspecialchars($item['date']) ?></div>
                        <div class="news-actions">
                            <a href="?toggle_news=<?= $item['id'] ?>" class="btn-sm" style="background:<?= ($item['active'] ?? true) ? '#23627C' : '#5A7A8C' ?>; color:white">
                                <?= ($item['active'] ?? true) ? '👁 Скрыть' : '👁 Показать' ?>
                            </a>
                            <a href="?delete_news=<?= $item['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Удалить новость?')">🗑 Удалить</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($currentTab === 'users' && checkPermission($currentRole, 'manage_users')): ?>
        <div class="users-section">
            <div class="users-header">
                <h2>Управление пользователями</h2>
            </div>

            <div style="background:white;padding:32px;border-radius:16px;box-shadow:0 4px 12px rgba(35,98,124,.06);margin-bottom:32px">
                <h3 style="font-size:20px;color:#23627C;margin-bottom:20px;font-weight:700">➕ Добавить нового пользователя</h3>
                <form method="POST">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px">
                        <div class="form-group">
                            <label>Имя</label>
                            <input type="text" name="new_name" placeholder="Иван Иванов" required>
                        </div>
                        <div class="form-group">
                            <label>Логин</label>
                            <input type="text" name="new_username" placeholder="ivanov" required>
                        </div>
                        <div class="form-group">
                            <label>Пароль</label>
                            <input type="text" name="new_password" placeholder="Минимум 6 символов" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Роль</label>
                            <select name="new_role" required>
                                <option value="doctor">👨‍⚕️ Врач (только просмотр)</option>
                                <option value="editor">✏️ Редактор (новости + просмотр)</option>
                                <option value="manager">👨‍💼 Менеджер (полный доступ к заявкам)</option>
                                <option value="admin">👑 Администратор (полный доступ)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-add">Создать пользователя</button>
                </form>
            </div>

            <div class="users-grid">
                <?php foreach ($users as $user): 
                    $isCurrent = $user['id'] === $_SESSION['user_id'];
                    $roleLabels = ['admin' => '👑 Администратор', 'manager' => '👨‍💼 Менеджер', 'doctor' => '👨‍⚕️ Врач', 'editor' => '✏️ Редактор'];
                    $roleClasses = ['admin' => 'role-admin', 'manager' => 'role-manager', 'doctor' => 'role-doctor', 'editor' => 'role-editor'];
                    $initials = mb_substr($user['name'], 0, 1);
                ?>
                <div class="user-card <?= $isCurrent ? 'current' : '' ?>">
                    <div class="user-avatar"><?= $initials ?></div>
                    <h3><?= htmlspecialchars($user['name']) ?> <?= $isCurrent ? '(Вы)' : '' ?></h3>
                    <div class="username">@<?= htmlspecialchars($user['username']) ?></div>
                    <div class="user-role <?= $roleClasses[$user['role']] ?? '' ?>"><?= $roleLabels[$user['role']] ?? $user['role'] ?></div>
                    <div class="created">Создан: <?= htmlspecialchars($user['created']) ?></div>
                    <div class="user-actions">
                        <?php if (!$isCurrent): ?>
                            <a href="?delete_user=<?= $user['id'] ?>" class="btn-user-delete" onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['username']) ?>?')">🗑 Удалить</a>
                        <?php else: ?>
                            <span class="btn-user-delete disabled">Нельзя удалить себя</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif ($currentTab === 'logs' && checkPermission($currentRole, 'manage_users')): ?>
        <div class="log-section">
            <h2>📊 Журнал активности</h2>
            <?php if (file_exists($logFile)): ?>
                <div class="log-list">
                    <?php
                    $logs = array_reverse(file($logFile, FILE_IGNORE_NEW_LINES));
                    foreach ($logs as $log):
                        if (preg_match('/\[(.*?)\] (.*?): (.*)/', $log, $matches)):
                    ?>
                    <div class="log-entry">
                        <span class="log-time"><?= $matches[1] ?></span>
                        <span class="log-user"><?= htmlspecialchars($matches[2]) ?></span>
                        <span class="log-action"><?= htmlspecialchars($matches[3]) ?></span>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📝</div>
                    <h3>Журнал пуст</h3>
                    <p>Здесь будут отображаться все действия пользователей</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal для обработки заявки -->
<div class="modal-overlay" id="processModal">
    <div class="modal">
        <button class="modal-close" onclick="closeProcessModal()">×</button>
        <h3>📅 Запись на приём</h3>
        <form method="POST" id="processForm">
            <input type="hidden" name="process_submission" id="processId">
            <div style="background:#F4F9F8;padding:16px;border-radius:10px;margin-bottom:20px">
                <div style="font-size:13px;color:#5A7A8C;margin-bottom:4px">Пациент:</div>
                <div style="font-size:18px;font-weight:700;color:#23627C" id="patientName"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>📅 Дата записи</label>
                    <input type="date" name="appointment_date" required>
                </div>
                <div class="form-group">
                    <label>🕐 Время</label>
                    <input type="time" name="appointment_time" required>
                </div>
            </div>
            <div class="form-group">
                <label>🦷 Процедура</label>
                <select name="procedure" required>
                    <option value="">Выберите процедуру...</option>
                    <option value="Консультация">Консультация</option>
                    <option value="Чистка зубов">Чистка зубов</option>
                    <option value="Отбеливание">Отбеливание</option>
                    <option value="Лечение кариеса">Лечение кариеса</option>
                    <option value="Имплантация">Имплантация</option>
                    <option value="Протезирование">Протезирование</option>
                    <option value="Удаление зуба">Удаление зуба</option>
                    <option value="Другое">Другое</option>
                </select>
            </div>
            <div class="form-group">
                <label>💰 Цена (₽)</label>
                <input type="number" name="price" placeholder="0" min="0" required>
            </div>
            <button type="submit" class="btn btn-process" style="width:100%;padding:16px;font-size:16px">
                ✓ Подтвердить запись
            </button>
        </form>
    </div>
</div>

<script>
function filterSubmissions(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const submissions = document.querySelectorAll('.submission');
    submissions.forEach((sub, index) => {
        const subStatus = sub.dataset.status;
        const shouldShow = status === 'all' || subStatus === status;
        sub.style.transition = 'all .4s ease';
        if (shouldShow) {
            sub.style.display = 'grid';
            sub.style.animation = `slideIn .5s ease ${index * .05}s both`;
        } else {
            sub.style.opacity = '0';
            sub.style.transform = 'translateX(-20px)';
            setTimeout(() => { sub.style.display = 'none'; }, 300);
        }
    });
}

function openProcessModal(id, name) {
    document.getElementById('processId').value = id;
    document.getElementById('patientName').textContent = name;
    document.getElementById('processModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeProcessModal() {
    document.getElementById('processModal').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('processModal').addEventListener('click', function(e) {
    if (e.target === this) closeProcessModal();
});
</script>

</body>
</html>