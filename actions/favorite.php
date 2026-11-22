<?php
// ===== Избранное: добавление и удаление =====

// Сессия нужна, чтобы знать, кто именно меняет список
session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";

// ===== Проверки доступа =====

// Без входа избранное недоступно
needLogin();

// Если id материала не передан, возвращаем пользователя на главную
if (!isset($_GET['content_id'])) {
    header("Location: ../index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$contentId = (int)$_GET['content_id'];

// ===== Работа с избранным =====

// Проверяем, не добавлен ли материал уже: так одна кнопка работает как переключатель
$query = $db->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND content_id = :content_id");
$query->execute([
    "user_id" => $userId,
    "content_id" => $contentId
]);

$favorite = $query->fetch();

if ($favorite) {
    // Уже в избранном — убираем запись
    $query = $db->prepare("DELETE FROM favorites WHERE id = :id");
    $query->execute(["id" => $favorite["id"]]);
} else {
    // Ещё не в избранном — сохраняем запись
    $query = $db->prepare("INSERT INTO favorites (user_id, content_id) VALUES (:user_id, :content_id)");
    $query->execute([
        "user_id" => $userId,
        "content_id" => $contentId
    ]);
}

// ===== Возврат на страницу материала =====

header("Location: ../content.php?id=" . $contentId);
exit;
?>
