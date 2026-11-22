<?php
// Каталог сериалов: показывает все карточки или только выбранную категорию.

// ===== Подключение и подготовка страницы =====
session_start();

require_once "includes/db.php";
require_once "includes/functions.php";
$pageTitle = 'Сериалы — Illuminos';
require_once "includes/header.php";

// ===== Фильтрация по категории =====
$categoryId = 0;

// Значение приходит из адреса, поэтому сразу приводим его к числу.
if (isset($_GET['category'])) {
    $categoryId = (int)$_GET['category'];
}

// ===== Данные каталога =====
// Ноль означает «все сериалы», иначе вернётся только выбранная категория.
$series = getContent($db, "series", $categoryId);
$categories = getCategories($db);

// ===== Вывод HTML: фильтры и сетка сериалов =====
?>

<section class="page-head">
    <div class="container">
        <h1>Сериалы</h1>
        <p>Выберите сериал по категории или посмотрите весь каталог.</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="filter-box">
            <a href="series.php" class="filter-btn <?php if ($categoryId == 0) echo 'active'; ?>">Все</a>

            <?php foreach ($categories as $category) { ?>
                <a href="series.php?category=<?php echo $category['id']; ?>"
                   class="filter-btn <?php if ($categoryId == $category['id']) echo 'active'; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php } ?>
        </div>

        <div class="content-grid">
            <?php foreach ($series as $item) { ?>
                <a href="content.php?id=<?php echo $item['id']; ?>" class="card">
                    <img src="<?php echo $item['poster']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">

                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><?php echo $item['year']; ?> · <?php echo $item['rating']; ?></p>
                    </div>
                </a>
            <?php } ?>
        </div>

    </div>
</section>

<?php
require_once "includes/footer.php";
?>