<?php
// Страница добавления фильма или сериала в каталог кинотеатра.

session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";
$pageTitle = 'Добавить контент — Illuminos';
$adminBodyClass = true;
require_once "../includes/header.php";

// ===== Проверка прав администратора =====
needAdmin();

// ===== Запросы к базе данных =====
$categories = getCategories($db);
$error = "";

// ===== Обработка формы =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Пробелы по краям полей убираем, чтобы пустые значения не попали в базу.
    $title = trim($_POST['title']);
    $type = normalizeContentType(isset($_POST['type']) ? $_POST['type'] : '');
    $description = trim($_POST['description']);
    $year = (int)$_POST['year'];
    $categoryId = (int)$_POST['category_id'];
    $director = trim($_POST['director']);
    $actors = trim($_POST['actors']);
    $poster = trim($_POST['poster']);
    // Неотмеченный флажок не приходит в запросе, поэтому ставим 0.
    $isNew = isset($_POST['is_new']) ? 1 : 0;
    $isPopular = isset($_POST['is_popular']) ? 1 : 0;

    if ($title == "" || $description == "" || $year == 0) {
        $error = "Заполните название, описание и год.";
    } elseif ($type === '') {
        $error = "Выберите тип: фильм или сериал.";
    } else {
        // Жёсткое ограничение: одинаковое название не может существовать в двух типах.
        $existing = findContentByTitle($db, $title);
        if ($existing) {
            $error = "Название «" . $title . "» уже есть в каталоге как " .
                     ($existing['type'] === 'movie' ? 'фильм' : 'сериал') .
                     ". Одно название не может быть и фильмом, и сериалом.";
        }
    }

    if ($error === "") {
        try {
            // Рейтинг новой записи пока нулевой: его поставят зрители.
            $query = $db->prepare("INSERT INTO content
                (title, type, description, year, rating, poster, category_id, director, actors, is_new, is_popular)
                VALUES
                (:title, :type, :description, :year, 0, :poster, :category_id, :director, :actors, :is_new, :is_popular)");

            $query->execute([
                "title" => $title,
                "type" => $type,
                "description" => $description,
                "year" => $year,
                "poster" => $poster,
                "category_id" => $categoryId,
                "director" => $director,
                "actors" => $actors,
                "is_new" => $isNew,
                "is_popular" => $isPopular
            ]);

            // После сохранения уходим к общему списку контента.
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            // На случай нарушения уникальности прямо в базе данных.
            $error = "Не удалось сохранить: такое название уже есть в каталоге.";
        }
    }
}

// ===== Вывод формы =====
?>

<section class="page-head">
    <div class="container">
        <h1>Добавить контент</h1>
    </div>
</section>

<?php require_once __DIR__ . '/_nav.php'; ?>

<section class="section">
    <div class="container">

        <a href="index.php" class="admin-back">← К списку контента</a>

        <?php if ($error != "") { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form action="add.php" method="post" class="admin-form">
            <label>Название</label>
            <input type="text" name="title" required>

            <label>Тип</label>
            <select name="type">
                <option value="movie">Фильм</option>
                <option value="series">Сериал</option>
            </select>

            <label>Описание</label>
            <textarea name="description" required></textarea>

            <label>Год</label>
            <input type="number" name="year" required>

            <label>Категория</label>
            <select name="category_id">
                <?php foreach ($categories as $category) { ?>
                    <option value="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php } ?>
            </select>

            <label>Режиссёр</label>
            <input type="text" name="director">

            <label>Актёры</label>
            <input type="text" name="actors">

            <label>Ссылка на постер</label>
            <input type="text" name="poster">

            <label>
                <input type="checkbox" name="is_new"> Новинка
            </label>

            <label>
                <input type="checkbox" name="is_popular"> Популярное
            </label>

            <button type="submit" class="btn">Добавить</button>
        </form>

    </div>
</section>

<?php
require_once "../includes/footer.php";
?>