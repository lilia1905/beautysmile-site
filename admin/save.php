<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $date    = date('d.m.Y H:i:s');

    if (empty($name) || empty($phone)) {
        header('Location: /index.php?error=1');
        exit;
    }

    $new = [
        'id'      => uniqid(),
        'date'    => $date,
        'name'    => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'phone'   => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
        'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        'read'    => false
    ];

    $file = __DIR__ . '/../data/submissions.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    array_unshift($data, $new);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    header('Location: /index.php?success=1');
    exit;
}
header('Location: /index.php');
exit;