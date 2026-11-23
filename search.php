<?php
// Поиск фильмов и сериалов по названию.

// ===== Подключение и подготовка страницы =====
session_start();

require_once "includes/db.php";
require_once "includes/functions.php";
$pageTitle = 'Поиск — Illuminos';
require_once "includes/header.php";

// ===== Поисковый запрос =====
$search = "";

// Лишние пробелы убираем, чтобы пустой запрос не находил всё подряд.
if (isset($_GET['q'])) {
    $search = trim($_GET['q']);
}

// ===== Фильтр по типу =====
// Пустое значение означает «все», остальные значения жёстко ограничиваем.
$type = isset($_GET['type']) ? $_GET['type'] : '';
$type = normalizeContentType($type);

// ===== Запрос к базе =====
// Проценты вокруг запроса ищут совпадение в любой части названия.
$sql = "SELECT content.*, categories.name AS category_name
        FROM content
        LEFT JOIN categories ON content.category_id = categories.id
        WHERE content.title LIKE :search";

$params = [
    "search" => "%" . $search . "%"
];

if ($type !== '') {
    $sql .= " AND content.type = :type";
    $params["type"] = $type;
}

$sql .= " ORDER BY content.title";

// Подготовленный запрос не даёт подставить в условие данные из адреса.
$query = $db->prepare($sql);
$query->execute($params);

$results = $query->fetchAll();

// ===== Вывод HTML: найденные карточки или сообщение о пустом результате =====
?>

<section class="page-head">
    <div class="container">
        <h1>Результаты поиска</h1>

        <?php if ($search != "") { ?>
            <p>По запросу: «<?php echo htmlspecialchars($search); ?>»</p>
        <?php } ?>
    </div>
</section>

<section class="section">
    <div class="container">

        <form method="get" action="search.php" class="search-filter">
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">

            <label for="search-type">Тип контента:</label>
            <select id="search-type" name="type">
                <option value="" <?php if ($type === '') echo 'selected'; ?>>Все</option>
                <option value="movie" <?php if ($type === 'movie') echo 'selected'; ?>>Фильмы</option>
                <option value="series" <?php if ($type === 'series') echo 'selected'; ?>>Сериалы</option>
            </select>

            <button type="submit" class="btn">Применить</button>
        </form>

        <?php if (count($results) == 0) { ?>
            <p>Ничего не найдено. Попробуйте другой запрос.</p>
        <?php } ?>

        <div class="content-grid">
            <?php foreach ($results as $item) { ?>
                <a href="content.php?id=<?php echo $item['id']; ?>" class="card">
                    <img src="<?php echo $item['poster']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">

                    <div class="card-info">
                        <h3>
                            <?php echo htmlspecialchars($item['title']); ?>
                            <span class="card-type"><?php echo $item['type'] === 'movie' ? 'Фильм' : 'Сериал'; ?></span>
                        </h3>
                        <p><?php echo $item['year']; ?> · <?php echo htmlspecialchars($item['category_name']); ?></p>
                    </div>
                </a>
            <?php } ?>
        </div>

    </div>
</section>

<?php
require_once "includes/footer.php";
?>