<?php
// Каталог фильмов: показывает все карточки или только выбранную категорию.

// ===== Подключение и подготовка страницы =====
session_start();

require_once "includes/db.php";
require_once "includes/functions.php";
$pageTitle = 'Фильмы — Illuminos';
require_once "includes/header.php";

// ===== Фильтрация по категории =====
$categoryId = 0;

// Значение приходит из адреса, поэтому сразу приводим его к числу.
if (isset($_GET['category'])) {
    $categoryId = (int)$_GET['category'];
}

// ===== Данные каталога =====
// Ноль означает «все фильмы», иначе вернётся только выбранная категория.
$movies = getContent($db, "movie", $categoryId);
$categories = getCategories($db);

// ===== Вывод HTML: фильтры и сетка фильмов =====
?>

<section class="page-head">
    <div class="container">
        <h1>Фильмы</h1>
        <p>Выберите фильм по категории или посмотрите весь каталог.</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="filter-box">
            <a href="movies.php" class="filter-btn <?php if ($categoryId == 0) echo 'active'; ?>">Все</a>

            <?php foreach ($categories as $category) { ?>
                <a href="movies.php?category=<?php echo $category['id']; ?>"
                   class="filter-btn <?php if ($categoryId == $category['id']) echo 'active'; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php } ?>
        </div>

        <div class="content-grid">
            <?php foreach ($movies as $movie) { ?>
                <a href="content.php?id=<?php echo $movie['id']; ?>" class="card">
                    <img src="<?php echo $movie['poster']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">

                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <p><?php echo $movie['year']; ?> · <?php echo $movie['rating']; ?></p>
                    </div>
                </a>
            <?php } ?>
        </div>

    </div>
</section>

<?php
require_once "includes/footer.php";
?>