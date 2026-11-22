<?php
// ===== История просмотров =====

// Сессия нужна, чтобы привязать просмотр к конкретному пользователю
session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";

// ===== Проверки доступа =====

// Без входа историю не ведём
needLogin();

// Отметку о просмотре ставим только тому, кто имеет право смотреть
if (!canWatch($db)) {
    header("Location: ../payment.php");
    exit;
}

// Без id материала нечего записывать
if (!isset($_GET['content_id'])) {
    header("Location: ../index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$contentId = (int)$_GET['content_id'];

// ===== Запись просмотра =====

$query = $db->prepare("INSERT INTO history (user_id, content_id) VALUES (:user_id, :content_id)");
$query->execute([
    "user_id" => $userId,
    "content_id" => $contentId
]);

// ===== Возврат на страницу материала =====

header("Location: ../content.php?id=" . $contentId);
exit;
?>
