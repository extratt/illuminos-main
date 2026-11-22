<?php
// ===== Подключение к базе данных =====

// Параметры соединения с локальной базой illuminos
$host = "localhost";
$dbname = "illuminos";
$user = "root";
$pass = "";

// ===== Создание соединения и режим ошибок =====

try {
    // Кодировка utf8 задаётся сразу, чтобы русский текст не искажался
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

    // Ошибки запросов вызывают исключение и сразу видны при разработке
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Без базы сайт не работает, поэтому страницу дальше не загружаем
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
