<?php
// ===== Проверки доступа =====

// isLogged(): возвращает true, если пользователь вошёл в аккаунт.
function isLogged() {
    return isset($_SESSION['user_id']);
}

// isAdmin(): возвращает true, если вошедший пользователь — администратор.
function isAdmin() {
    return isLogged() && $_SESSION['role'] == 'admin';
}

// ===== Подписка и право просмотра =====

// hasSubscription(): возвращает true, если срок подписки в сессии ещё не истёк.
function hasSubscription() {
    if (!isLogged()) {
        return false;
    }

    // Пустая дата означает, что подписки никогда не было
    if (empty($_SESSION['subscription_until'])) {
        return false;
    }

    // Переводим дату в секунды, чтобы сравнить её с текущим временем
    return strtotime($_SESSION['subscription_until']) > time();
}

// canWatch($db): возвращает true, если контент можно смотреть (админ или подписчик).
function canWatch($db) {
    if (!isLogged()) {
        return false;
    }

    // Админ освобождается от подписки: у него доступ ко всему каталогу
    if (isAdmin()) {
        return true;
    }

    $userId = $_SESSION['user_id'];

    // Сначала ищем активную запись в новой таблице подписок
    try {
        $query = $db->prepare("SELECT id FROM subscriptions
                               WHERE user_id = :user_id AND subscription_until > CURDATE()");
        $query->execute(["user_id" => $userId]);
        if ($query->fetch()) {
            return true;
        }
    } catch (PDOException $e) {
        // В старой базе таблицы subscriptions может не быть — идём в запасной вариант
    }

    // Запасной вариант: раньше срок подписки хранился в таблице users
    $query = $db->prepare("SELECT subscription_until FROM users WHERE id = :user_id");
    $query->execute(["user_id" => $userId]);
    $user = $query->fetch();

    // Подписка активна, если дата окончания ещё не прошла
    return $user && !empty($user['subscription_until']) && strtotime($user['subscription_until']) > time();
}

// ===== Защита страниц =====

// needLogin(): уводит гостя на страницу входа; авторизованного пропускает, ничего не возвращает.
function needLogin() {
    if (!isLogged()) {
        header("Location: login.php");
        exit;
    }
}

// needAdmin(): уводит обычного пользователя на главную; админа пропускает, ничего не возвращает.
function needAdmin() {
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit;
    }
}

// ===== Запросы к данным =====

// getCategories($db): возвращает список всех категорий.
function getCategories($db) {
    $sql = "SELECT * FROM categories ORDER BY name";
    $result = $db->query($sql);

    return $result->fetchAll(PDO::FETCH_ASSOC);
}

// normalizeContentType($type): допускает только 'movie' или 'series'.
function normalizeContentType($type) {
    return ($type === 'movie' || $type === 'series') ? $type : '';
}

// findContentByTitle($db, $title, $excludeId): ищет уже существующее название.
// Одно и то же название не может быть одновременно фильмом и сериалом.
function findContentByTitle($db, $title, $excludeId = 0) {
    $sql = "SELECT id, type FROM content WHERE title = :title";
    $params = ["title" => $title];

    if ($excludeId > 0) {
        $sql .= " AND id <> :exclude_id";
        $params["exclude_id"] = $excludeId;
    }
    $sql .= " LIMIT 1";

    $query = $db->prepare($sql);
    $query->execute($params);

    return $query->fetch(PDO::FETCH_ASSOC) ?: null;
}

// getContent($db, $type, $categoryId, $search): возвращает фильмы или сериалы с учётом фильтров.
function getContent($db, $type, $categoryId = 0, $search = "") {
    // Тип жёстко ограничен: вкладка фильмов не может показать сериалы и наоборот.
    if (normalizeContentType($type) === '') {
        return [];
    }

    $sql = "SELECT content.*, categories.name AS category_name
            FROM content
            LEFT JOIN categories ON content.category_id = categories.id
            WHERE content.type = :type";

    // Значения передаются отдельно от SQL, чтобы данные не попадали в запрос напрямую
    $params = [
        "type" => $type
    ];

    if ($categoryId > 0) {
        $sql = $sql . " AND content.category_id = :category_id";
        $params["category_id"] = $categoryId;
    }

    if ($search != "") {
        $sql = $sql . " AND content.title LIKE :search";
        // %% вокруг строки означают поиск по подстроке, а не по точному совпадению
        $params["search"] = "%" . $search . "%";
    }

    // Сначала показываем самые свежие материалы
    $sql = $sql . " ORDER BY content.created_at DESC";

    $query = $db->prepare($sql);
    $query->execute($params);

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// getContentById($db, $id): возвращает один фильм или сериал по id.
function getContentById($db, $id) {
    $sql = "SELECT content.*, categories.name AS category_name
            FROM content
            LEFT JOIN categories ON content.category_id = categories.id
            WHERE content.id = :id";

    $query = $db->prepare($sql);
    $query->execute([
        "id" => $id
    ]);

    return $query->fetch(PDO::FETCH_ASSOC);
}

// getSimilarContent($db, $content): возвращает до четырёх материалов той же категории.
function getSimilarContent($db, $content) {
    $sql = "SELECT * FROM content
            WHERE type = :type
            AND id != :id
            AND category_id = :category_id
            LIMIT 4";

    // Текущий материал исключён запросом и не должен попасть в список похожих
    $query = $db->prepare($sql);
    $query->execute([
        "type" => $content["type"],
        "id" => $content["id"],
        "category_id" => $content["category_id"]
    ]);

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// ===== Избранное и оценки =====

// isFavorite($db, $userId, $contentId): возвращает true, если материал уже в избранном.
function isFavorite($db, $userId, $contentId) {
    $sql = "SELECT id FROM favorites
            WHERE user_id = :user_id AND content_id = :content_id";

    $query = $db->prepare($sql);
    $query->execute([
        "user_id" => $userId,
        "content_id" => $contentId
    ]);

    // Само наличие строки и означает, что материал добавлен
    return $query->fetch() ? true : false;
}

// getContentRating($db, $contentId): возвращает среднюю оценку или рейтинг по умолчанию.
function getContentRating($db, $contentId) {
    // AVG и COUNT считают среднюю оценку и количество голосов одним запросом
    $sql = "SELECT AVG(rating) AS average_rating, COUNT(id) AS count_ratings
            FROM ratings
            WHERE content_id = :content_id";

    $query = $db->prepare($sql);
    $query->execute([
        "content_id" => $contentId
    ]);

    $result = $query->fetch();

    // Оценки есть — вернём среднее из таблицы ratings
    if ($result["count_ratings"] > 0) {
        // Один знак после запятой достаточно для показа рейтинга
        return round($result["average_rating"], 1);
    }

    // Оценок нет — используем рейтинг из карточки контента
    $query = $db->prepare("SELECT rating FROM content WHERE id = :id");
    $query->execute(["id" => $contentId]);
    $content = $query->fetch();

    return $content ? $content["rating"] : 0;
}
?>