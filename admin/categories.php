<?php
// Страница управления категориями: добавление и удаление разделов каталога.

session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";
$pageTitle = 'Категории — Illuminos';
$adminBodyClass = true;
require_once "../includes/header.php";

// ===== Проверка прав администратора =====
needAdmin();

// ===== Обработка формы =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);

    if ($name != "") {
        $query = $db->prepare("INSERT INTO categories (name) VALUES (:name)");
        $query->execute(["name" => $name]);
    }
}

// ===== Запросы к базе данных =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $query = $db->prepare("UPDATE content SET category_id = NULL WHERE category_id = :id");
    $query->execute(["id" => $id]);

    $query = $db->prepare("DELETE FROM categories WHERE id = :id");
    $query->execute(["id" => $id]);
}

$categories = getCategories($db);

// ===== Вывод формы и таблицы =====
?>

<section class="page-head">
    <div class="container">
        <h1>Категории</h1>
    </div>
</section>

<?php require_once __DIR__ . '/_nav.php'; ?>

<section class="section">
    <div class="container">

        <a href="index.php" class="admin-back">← В админ-панель</a>

        <form action="categories.php" method="post" class="admin-form">
            <label>Название категории</label>
            <input type="text" name="name" required>
            <button type="submit" class="btn">Добавить</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $category) { ?>
                    <tr>
                        <td data-label="ID"><?php echo $category['id']; ?></td>
                        <td data-label="Название"><?php echo htmlspecialchars($category['name']); ?></td>

                        <td data-label="Действия">
                            <a class="admin-action-delete" href="categories.php?delete=<?php echo $category['id']; ?>"
                               onclick="return confirm('Удалить категорию?')">Удалить</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</section>

<?php
require_once "../includes/footer.php";
?>