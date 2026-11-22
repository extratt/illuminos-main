<?php
// ===== Отзывы зрителей: сохранение новой рецензии =====

// Сессия нужна, чтобы знать имя автора отзыва
session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";

// ===== Проверки доступа =====

// Оставлять отзыв могут только вошедшие пользователи
needLogin();

$contentId = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;

if ($contentId <= 0) {
    header("Location: ../index.php");
    exit;
}

// ===== Обработка формы =====

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';

    // Сохраняем отзыв, только если заполнены оценка и текст
    if ($rating >= 1 && $rating <= 10 && $text !== '' && mb_strlen($text) <= 1000) {
        // Проверяем, что такой фильм или сериал действительно есть
        $query = $db->prepare("SELECT id FROM content WHERE id = :id");
        $query->execute(["id" => $contentId]);

        if ($query->fetch()) {
            $query = $db->prepare("INSERT INTO reviews (content_id, user_name, rating, text) VALUES (:content_id, :user_name, :rating, :text)");
            $query->execute([
                "content_id" => $contentId,
                "user_name" => $_SESSION['name'],
                "rating" => $rating,
                "text" => $text
            ]);
        }
    }
}

// ===== Возврат на страницу материала =====

header("Location: ../content.php?id=" . $contentId);
exit;
?>
