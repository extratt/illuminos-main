<?php
// Админ-панель: показывает каталог фильмов и сериалов и ссылки на управление им.

session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";
$pageTitle = 'Админ-панель — Illuminos';
$adminBodyClass = true;
require_once "../includes/header.php";

// ===== Проверка прав администратора =====
needAdmin();

// ===== Запросы к базе данных =====
// Категории присоединяются отдельно, чтобы в таблице было название, а не номер.
$content = $db->query("SELECT content.*, categories.name AS category_name
    FROM content
    LEFT JOIN categories ON content.category_id = categories.id
    ORDER BY content.id DESC")->fetchAll();

// Сводка нужна, чтобы сразу видеть размер каталога и количество разделов.
$stats = $db->query("SELECT COUNT(*) AS total,
    SUM(type = 'movie') AS movies,
    SUM(type = 'series') AS series,
    COUNT(DISTINCT category_id) AS categories
    FROM content")->fetch();

// ===== Вывод таблицы =====
?>

<section class="page-head">
    <div class="container">
        <h1>Админ-панель</h1>
        <p>Управление фильмами, сериалами и категориями.</p>
    </div>
</section>

<?php require_once __DIR__ . '/_nav.php'; ?>

<section class="section">
    <div class="container">

        <!-- Сводка по каталогу -->
        <div class="admin-stats">
            <div class="admin-stat"><span>Всего материалов</span><strong><?php echo $stats['total']; ?></strong></div>
            <div class="admin-stat"><span>Фильмов</span><strong><?php echo $stats['movies']; ?></strong></div>
            <div class="admin-stat"><span>Сериалов</span><strong><?php echo $stats['series']; ?></strong></div>
            <div class="admin-stat"><span>Категорий</span><strong><?php echo $stats['categories']; ?></strong></div>
        </div>

        <!-- Поиск по каталогу -->
        <div class="admin-toolbar">
            <div class="admin-search">
                <label for="adminSearch">Поиск по названию</label>
                <input type="search" id="adminSearch" placeholder="Начните вводить название">
            </div>
            <span class="admin-found" id="adminFound" data-forms="материал,материала,материалов"><?php echo count($content); ?> материалов</span>
        </div>

        <p class="admin-empty" id="adminEmpty" hidden>Ничего не найдено</p>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Тип</th>
                        <th>Категория</th>
                        <th>Год</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($content as $item) { ?>
                    <tr>
                        <td data-label="ID"><?php echo $item['id']; ?></td>
                        <td data-label="Название"><?php echo htmlspecialchars($item['title']); ?></td>
                        <td data-label="Тип"><?php if ($item['type'] == 'movie') echo "Фильм"; else echo "Сериал"; ?></td>
                        <td data-label="Категория"><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td data-label="Год"><?php echo $item['year']; ?></td>

                        <td data-label="Действия">
                            <a class="admin-action-edit" href="edit.php?id=<?php echo $item['id']; ?>">Изменить</a>
                            <a class="admin-action-delete" href="delete.php?id=<?php echo $item['id']; ?>" onclick="return confirm('Удалить этот контент?')">Удалить</a>
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