<?php
// Страница удаления фильма или сериала вместе со всеми связанными записями.

session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";

// ===== Проверка прав администратора =====
needAdmin();

// ===== Проверка идентификатора =====
// Без номера записи удалять нечего — возвращаемся к списку.
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// ===== Запросы к базе данных =====
// Сначала чистим связанные записи: серии, сезоны, избранное, историю и оценки.
$query = $db->prepare("DELETE FROM episodes WHERE season_id IN (SELECT id FROM seasons WHERE content_id = :id)");
$query->execute(["id" => $id]);

$query = $db->prepare("DELETE FROM seasons WHERE content_id = :id");
$query->execute(["id" => $id]);

$query = $db->prepare("DELETE FROM favorites WHERE content_id = :id");
$query->execute(["id" => $id]);

$query = $db->prepare("DELETE FROM history WHERE content_id = :id");
$query->execute(["id" => $id]);

$query = $db->prepare("DELETE FROM ratings WHERE content_id = :id");
$query->execute(["id" => $id]);

$query = $db->prepare("DELETE FROM content WHERE id = :id");
$query->execute(["id" => $id]);

// Удаление завершено — возвращаемся к списку.
header("Location: index.php");
exit;
?>