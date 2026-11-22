<?php
// Главная страница: витрина новинок, популярного и случайных рекомендаций.

// ===== Подключение и подготовка страницы =====
session_start();

require_once "includes/db.php";
require_once "includes/functions.php";
$pageTitle = 'Illuminos — онлайн-кинотеатр';
require_once "includes/header.php";

// ===== Выборка данных для витрины =====
// Новинки идут и в верхний баннер, и в первую сетку карточек.
$newContent = $db->query("SELECT * FROM content WHERE is_new = 1 ORDER BY id DESC LIMIT 8")->fetchAll();

// Популярное сортируем по рейтингу, а не по дате добавления.
$popularContent = $db->query("SELECT * FROM content WHERE is_popular = 1 ORDER BY rating DESC LIMIT 8")->fetchAll();

// Случайный порядок делает подборку разной при каждом открытии страницы.
$recommendations = $db->query("SELECT * FROM content ORDER BY RAND() LIMIT 8")->fetchAll();

// Категории нужны для отдельного блока ссылок на каталоги.
$categories = getCategories($db);

// ===== Вывод HTML: баннер, подборки и категории =====
?>

<section class="hero">
    <?php if (!empty($newContent)) { ?>
        <div class="hero-posters" aria-hidden="true">
            <?php foreach (array_slice($newContent, 0, 8) as $index => $item) { ?>
                <img src="<?php echo htmlspecialchars($item['poster'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt=""
                     class="hero-poster"
                     style="--i: <?php echo (int)$index; ?>;">
            <?php } ?>
        </div>
    <?php } ?>

    <div class="container">
        <h1>Смотри фильмы и сериалы <span>по-новому</span></h1>
        <p>Illuminos — современный онлайн-кинотеатр с фильмами, сериалами, подборками и личными рекомендациями.</p>

        <div class="hero-buttons">
            <a href="movies.php" class="btn">Смотреть фильмы</a>
            <a href="series.php" class="btn btn-outline">Смотреть сериалы</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Топ новинки</h2>

        <div class="content-grid">
            <?php foreach ($newContent as $item) { ?>
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

<section class="section section-dark">
    <div class="container">
        <h2 class="section-title">Популярное сейчас</h2>

        <div class="content-grid">
            <?php foreach ($popularContent as $item) { ?>
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

<section class="section">
    <div class="container">
        <h2 class="section-title">Категории</h2>

        <div class="category-list">
            <?php foreach ($categories as $category) { ?>
                <a href="movies.php?category=<?php echo $category['id']; ?>" class="category-item">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php } ?>
        </div>
    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <h2 class="section-title">Рекомендации для вас</h2>

        <div class="content-grid">
            <?php foreach ($recommendations as $item) { ?>
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