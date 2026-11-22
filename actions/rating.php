<?php
// ===== Оценки пользователей =====

// Сессия нужна, чтобы знать, кто поставил оценку
session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";

// ===== Проверки доступа =====

// Оценивать могут только вошедшие пользователи
needLogin();

// ===== Обработка действия =====

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    $contentId = (int)$_POST['content_id'];
    $rating = (int)$_POST['rating'];

    // Ограничиваем оценку, чтобы в базу не попало случайное число
    if ($rating < 1) {
        $rating = 1;
    }

    if ($rating > 10) {
        $rating = 10;
    }

    // Сначала убираем прежнюю оценку, чтобы не было дублей
    $query = $db->prepare("DELETE FROM ratings WHERE user_id = :user_id AND content_id = :content_id");
    $query->execute([
        "user_id" => $userId,
        "content_id" => $contentId
    ]);

    // Затем сохраняем новую оценку
    $query = $db->prepare("INSERT INTO ratings (user_id, content_id, rating) VALUES (:user_id, :content_id, :rating)");
    $query->execute([
        "user_id" => $userId,
        "content_id" => $contentId,
        "rating" => $rating
    ]);
}

// ===== Возврат на страницу материала =====

header("Location: ../content.php?id=" . $contentId);
exit;
?>
