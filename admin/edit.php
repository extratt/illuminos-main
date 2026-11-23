<?php
// Страница редактирования фильма или сериала, уже добавленного в каталог.

session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";
$pageTitle = 'Редактирование контента — Illuminos';
$adminBodyClass = true;
require_once "../includes/header.php";

// ===== Проверка прав администратора =====
needAdmin();

// ===== Запросы к базе данных =====
// Без номера записи редактировать нечего — возвращаемся к списку.
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

$query = $db->prepare("SELECT * FROM content WHERE id = :id");
$query->execute(["id" => $id]);

$content = $query->fetch();

// Такой записи больше нет — тоже уходим к списку.
if (!$content) {
    header("Location: index.php");
    exit;
}

$categories = getCategories($db);
$error = "";

// ===== Обработка формы =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Лишние пробелы убираем, числа приводим к числовому виду.
    $title = trim($_POST['title']);
    $type = normalizeContentType(isset($_POST['type']) ? $_POST['type'] : '');
    $description = trim($_POST['description']);
    $year = (int)$_POST['year'];
    $categoryId = (int)$_POST['category_id'];
    $director = trim($_POST['director']);
    $actors = trim($_POST['actors']);
    $poster = trim($_POST['poster']);
    // Неотмеченный флажок приравниваем к нулю.
    $isNew = isset($_POST['is_new']) ? 1 : 0;
    $isPopular = isset($_POST['is_popular']) ? 1 : 0;

    if ($title == "" || $description == "" || $year == 0) {
        $error = "Заполните название, описание и год.";
    } elseif ($type === '') {
        $error = "Выберите тип: фильм или сериал.";
    } else {
        // Жёсткое ограничение: одинаковое название не может существовать в двух типах.
        $existing = findContentByTitle($db, $title, $id);
        if ($existing) {
            $error = "Название «" . $title . "» уже есть в каталоге как " .
                     ($existing['type'] === 'movie' ? 'фильм' : 'сериал') .
                     ". Одно название не может быть и фильмом, и сериалом.";
        }
    }

    if ($error === "") {
        try {
            $query = $db->prepare("UPDATE content SET
                title = :title,
                type = :type,
                description = :description,
                year = :year,
                poster = :poster,
                category_id = :category_id,
                director = :director,
                actors = :actors,
                is_new = :is_new,
                is_popular = :is_popular
                WHERE id = :id");

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
                "is_popular" => $isPopular,
                "id" => $id
            ]);

            // Изменения сохранены — возвращаемся к списку.
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
        <h1>Изменить контент</h1>
    </div>
</section>

<?php require_once __DIR__ . '/_nav.php'; ?>

<section class="section">
    <div class="container">

        <a href="index.php" class="admin-back">← К списку контента</a>

        <?php if ($error != "") { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>

        <form action="edit.php?id=<?php echo $id; ?>" method="post" class="admin-form">
            <label>Название</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($content['title']); ?>" required>

            <label>Тип</label>
            <select name="type">
                <option value="movie" <?php if ($content['type'] == 'movie') echo 'selected'; ?>>Фильм</option>
                <option value="series" <?php if ($content['type'] == 'series') echo 'selected'; ?>>Сериал</option>
            </select>

            <label>Описание</label>
            <textarea name="description" required><?php echo htmlspecialchars($content['description']); ?></textarea>

            <label>Год</label>
            <input type="number" name="year" value="<?php echo $content['year']; ?>" required>

            <label>Категория</label>
            <select name="category_id">
                <?php foreach ($categories as $category) { ?>
                    <option value="<?php echo $category['id']; ?>"
                        <?php if ($content['category_id'] == $category['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php } ?>
            </select>

            <label>Режиссёр</label>
            <input type="text" name="director" value="<?php echo htmlspecialchars($content['director']); ?>">

            <label>Актёры</label>
            <input type="text" name="actors" value="<?php echo htmlspecialchars($content['actors']); ?>">

            <label>Ссылка на постер</label>
            <input type="text" name="poster" value="<?php echo htmlspecialchars($content['poster']); ?>">

            <label>
                <input type="checkbox" name="is_new" <?php if ($content['is_new'] == 1) echo 'checked'; ?>> Новинка
            </label>

            <label>
                <input type="checkbox" name="is_popular" <?php if ($content['is_popular'] == 1) echo 'checked'; ?>> Популярное
            </label>

            <button type="submit" class="btn">Сохранить</button>
        </form>

    </div>
</section>

<?php
require_once "../includes/footer.php";
?>