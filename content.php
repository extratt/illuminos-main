<?php
// Страница фильма или сериала: описание, трейлер, доступ к просмотру, серии, отзывы и похожее.

session_start();

require_once "includes/db.php";
require_once "includes/functions.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

// ===== Загрузка контента =====
$contentId = (int)$_GET['id'];
$content = getContentById($db, $contentId);

// Неизвестный id: показываем заглушку вместо пустой страницы.
if (!$content) {
    $pageTitle = 'Контент не найден — Illuminos';
    require_once "includes/header.php";
    echo "<div class='container'><h1>Контент не найден</h1></div>";
    require_once "includes/footer.php";
    exit;
}

$pageTitle = $content['title'] . ' — Illuminos';
require_once "includes/header.php";

// ===== Дополнительные данные для страницы =====
$similar = getSimilarContent($db, $content);

// Сезоны есть только у сериалов, у фильмов список остаётся пустым.
$seasons = [];
if ($content['type'] == 'series') {
    $query = $db->prepare("SELECT * FROM seasons WHERE content_id = :id ORDER BY season_number");
    $query->execute(["id" => $contentId]);
    $seasons = $query->fetchAll();
}

// Отзывы выводим от самых свежих к старым.
$query = $db->prepare("SELECT * FROM reviews WHERE content_id = :id ORDER BY created_at DESC");
$query->execute(["id" => $contentId]);
$reviews = $query->fetchAll();

$userRating = getContentRating($db, $contentId);
$isFavorite = false;

// Избранное проверяем только для вошедшего пользователя.
if (isLogged()) {
    $isFavorite = isFavorite($db, $_SESSION['user_id'], $contentId);
}

// Право на просмотр определяет и кнопки, и вид плеера ниже.
$canWatch = canWatch($db);

// ===== Вывод HTML: описание и кнопки действий =====
?>

<section class="content-hero">
    <div class="container content-hero-inner">

        <div class="content-poster-wrap">
            <img src="<?php echo $content['poster']; ?>" alt="<?php echo htmlspecialchars($content['title']); ?>" class="content-poster">
        </div>

        <div class="content-main-info">
            <h1><?php echo htmlspecialchars($content['title']); ?></h1>
            <p class="original-title"><?php echo htmlspecialchars($content['original_title']); ?> <span class="age-limit"><?php echo $content['age_limit']; ?></span></p>

            <div class="content-rating-block">
                <span class="big-rating"><?php echo $userRating; ?></span>
                <div class="rating-info">
                    <span class="votes"><?php echo number_format($content['votes_count'], 0, '', ' '); ?> оценок</span>
<?php 
    // Число рецензий рассчитываем из id: оно не меняется при обновлении страницы.
    $fakeReviews = (($content['id'] * 17) % 50) + 15; 
    $totalReviews = count($reviews) + $fakeReviews;
    echo $totalReviews . " рецензий"; 
?>                </div>
            </div>

            <p class="content-description">
                <?php echo htmlspecialchars($content['description']); ?>
            </p>

            <div class="content-buttons">
                <?php if ($canWatch) { ?>
                    <a href="actions/history.php?content_id=<?php echo $content['id']; ?>" class="btn">
                        <img src="<?php echo $baseUrl; ?>assets/img/play.svg" alt="" class="btn-icon"> Смотреть
                    </a>
                <?php } elseif (isLogged()) { ?>
                    <a href="payment.php" class="btn">
                        <img src="<?php echo $baseUrl; ?>assets/img/gem.svg" alt="" class="btn-icon"> Оформить подписку
                    </a>
                <?php } else { ?>
                    <a href="login.php" class="btn">
                        <img src="<?php echo $baseUrl; ?>assets/img/sign-in-alt.svg" alt="" class="btn-icon"> Войти, чтобы смотреть
                    </a>
                <?php } ?>

                <!-- Трейлер доступен всем посетителям страницы -->
                <button type="button" class="btn btn-outline trailer-trigger"
                        data-title="<?php echo htmlspecialchars($content['title'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-poster="<?php echo htmlspecialchars($content['poster'], ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo $baseUrl; ?>assets/img/video.svg" alt="" class="btn-icon"> Трейлер
                </button>

                <?php if (isLogged()) { ?>
                    <a href="actions/favorite.php?content_id=<?php echo $content['id']; ?>" class="btn btn-outline">
                        <img src="<?php echo $baseUrl; ?>assets/img/bookmark.svg" alt="" class="btn-icon">
                        <?php if ($isFavorite) echo "Убрать из избранного"; else echo "В избранное"; ?>
                    </a>
                <?php } ?>
            </div>
            
            <!-- Оценить фильм -->
            <div class="rate-block">
                <p class="rate-title">
                    <img src="<?php echo $baseUrl; ?>assets/img/star.svg" alt="" class="btn-icon"> Оценить фильм
                </p>
                <?php if (isLogged()) { ?>
                    <form action="actions/rating.php" method="post" class="stars-form">
                        <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">
                        <div class="stars">
                            <?php for($i=1; $i<=10; $i++): ?>
                                <button type="submit" name="rating" value="<?php echo $i; ?>" class="star-btn"><?php echo $i; ?></button>
                            <?php endfor; ?>
                        </div>
                    </form>
                <?php } else { ?>
                    <p class="small-text"><a href="login.php">Войдите</a>, чтобы поставить оценку.</p>
                <?php } ?>
            </div>
        </div>

    </div>
</section>

<?php
// ===== Плеер: подготовка данных =====
// Постер и название экранируются перед выводом в атрибуты.
$playerPoster = htmlspecialchars($content['poster'], ENT_QUOTES, 'UTF-8');
$playerTitle = htmlspecialchars($content['title'], ENT_QUOTES, 'UTF-8');
// Длительность из базы указана в минутах — переводим её в секунды.
$playerSeconds = max(1, (int)$content['duration']) * 60;
$playerTotal = gmdate('H:i:s', $playerSeconds);
// У сериала подпись указывает сезон и серию, у фильма — просто «Просмотр».
$playerEpisode = ($content['type'] == 'series') ? '1 сезон · 1 серия' : 'Просмотр';
$imgPath = $baseUrl . 'assets/img/';
// Ниже две версии блока: активный плеер для подписчиков и заглушка для остальных.
?>

<?php if ($canWatch) { ?>
<!-- Имитация плеера -->
<section class="player-section">
    <div class="container">
        <div class="player-head">
            <h2 class="section-title">Смотреть</h2>
        </div>

        <div class="player" data-duration="<?php echo $playerSeconds; ?>">
            <div class="player-screen">
                <img src="<?php echo $playerPoster; ?>" alt="" class="player-backdrop">
                <div class="player-shade"></div>
                <div class="player-caption">
                    <span class="player-label"><?php echo $playerEpisode; ?></span>
                    <strong><?php echo $playerTitle; ?></strong>
                </div>
                <button type="button" class="player-big-play" aria-label="Начать имитацию просмотра">
                    <img src="<?php echo $imgPath; ?>play.svg" alt="" class="player-big-play-icon">
                </button>
            </div>

            <div class="player-controls">
                <button type="button" class="player-control player-play" data-action="play" aria-label="Воспроизведение">
                    <img src="<?php echo $imgPath; ?>play.svg" alt="" class="player-icon"
                         data-src-play="<?php echo $imgPath; ?>play.svg"
                         data-src-pause="<?php echo $imgPath; ?>pause.svg">
                </button>
                <span class="player-time"><span class="player-current">00:00:00</span> / <?php echo $playerTotal; ?></span>

                <div class="player-timeline" aria-label="Шкала просмотра">
                    <div class="player-progress"><span class="player-knob"></span></div>
                </div>

                <div class="player-volume-wrap">
                    <button type="button" class="player-control" data-action="mute" aria-label="Звук">
                        <img src="<?php echo $imgPath; ?>volume-up.svg" alt="" class="player-icon volume-icon"
                             data-src-volume="<?php echo $imgPath; ?>volume-up.svg"
                             data-src-mute="<?php echo $imgPath; ?>volume-mute.svg">
                    </button>
                    <input type="range" class="player-volume" min="0" max="100" value="70" aria-label="Громкость">
                </div>

                <div class="player-settings-wrap">
                    <button type="button" class="player-control" data-action="settings" aria-label="Настройки">
                        <img src="<?php echo $imgPath; ?>cog.svg" alt="" class="player-icon">
                    </button>
                    <div class="player-settings-menu">
                        <span>Качество: 1080p</span>
                        <span>Скорость: 1×</span>
                    </div>
                </div>

                <button type="button" class="player-control" data-action="fullscreen" aria-label="На весь экран">
                    <img src="<?php echo $imgPath; ?>expand.svg" alt="" class="player-icon fullscreen-icon"
                         data-src-expand="<?php echo $imgPath; ?>expand.svg"
                         data-src-compress="<?php echo $imgPath; ?>compress.svg">
                </button>
            </div>
        </div>
    </div>
</section>
<?php } else { ?>
<!-- Плеер заблокирован для гостей и пользователей без подписки -->
<section class="player-section">
    <div class="container">
        <div class="player-head">
            <h2 class="section-title">Смотреть</h2>
            <span class="player-hint">Доступ только для подписчиков</span>
        </div>

        <div class="player player-locked">
            <div class="player-screen">
                <img src="<?php echo $playerPoster; ?>" alt="" class="player-backdrop">
                <div class="player-shade"></div>
                <div class="player-lock">
                    <img src="<?php echo $imgPath; ?>lock.svg" alt="" class="player-lock-icon">
                    <strong>Просмотр доступен подписчикам</strong>
                    <p>
                        <?php if (!isLogged()) { ?>
                            Войдите в аккаунт и оформите подписку, чтобы смотреть фильмы и сериалы.
                        <?php } else { ?>
                            Оформите подписку Illuminos, чтобы открыть плеер.
                        <?php } ?>
                    </p>
                    <div class="player-lock-buttons">
                        <?php if (!isLogged()) { ?>
                            <a href="login.php" class="btn">
                                <img src="<?php echo $imgPath; ?>sign-in-alt.svg" alt="" class="btn-icon"> Войти
                            </a>
                            <a href="register.php" class="btn btn-outline">
                                <img src="<?php echo $imgPath; ?>user-plus.svg" alt="" class="btn-icon"> Регистрация
                            </a>
                        <?php } else { ?>
                            <a href="payment.php" class="btn">
                                <img src="<?php echo $imgPath; ?>gem.svg" alt="" class="btn-icon"> Оформить подписку
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php } ?>

<!-- Блок "О фильме" -->
<section class="section section-dark">
    <div class="container">
        <div class="about-grid">
            <div class="about-details">
                <h2 class="section-title">О фильме</h2>
                <table class="details-table">
                    <tr><td>Год производства</td><td><?php echo $content['year']; ?></td></tr>
                    <tr><td>Страна</td><td><?php echo htmlspecialchars($content['country']); ?></td></tr>
                    <tr><td>Жанр</td><td><?php echo htmlspecialchars($content['category_name']); ?></td></tr>
                    <tr><td>Слоган</td><td>«<?php echo htmlspecialchars($content['slogan']); ?>»</td></tr>
                    <tr><td>Режиссер</td><td><?php echo htmlspecialchars($content['director']); ?></td></tr>
                    <tr><td>Время</td><td><?php echo $content['duration']; ?> мин.</td></tr>
                    <tr><td>Бюджет</td><td><?php echo $content['budget']; ?></td></tr>
                    <tr><td>Сборы</td><td><?php echo $content['fees']; ?></td></tr>
                </table>
            </div>
            <div class="about-actors">
                <h2 class="section-title">В главных ролях</h2>
                <ul class="actors-list">
                    <?php 
                    // Актёры хранятся одной строкой через запятую.
                    $actors = explode(',', $content['actors']);
                    foreach ($actors as $actor) {
                        echo "<li>" . htmlspecialchars(trim($actor)) . "</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Сезоны -->
<?php if ($content['type'] == 'series' && count($seasons) > 0) { ?>
    <section class="section">
        <div class="container">
            <h2 class="section-title">Сезоны и эпизоды</h2>
            <?php foreach ($seasons as $season) { ?>
                <div class="season-box">
                    <h3><?php echo htmlspecialchars($season['title']); ?></h3>
                    <ul class="episode-list">
                        <?php
                        // Для каждого сезона отдельно выбираем эпизоды по номеру.
                        $query = $db->prepare("SELECT * FROM episodes WHERE season_id = :season_id ORDER BY episode_number");
                        $query->execute(["season_id" => $season["id"]]);
                        $seasonEpisodes = $query->fetchAll();
                        foreach ($seasonEpisodes as $episode) {
                        ?>
                            <li>
                                <span><?php echo $episode['episode_number']; ?>. <?php echo htmlspecialchars($episode['title']); ?></span>
                                <span><?php echo $episode['duration']; ?> мин</span>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>
        </div>
    </section>
<?php } ?>

<!-- Отзывы -->
<section class="section section-dark">
    <div class="container">
        <h2 class="section-title">Рецензии зрителей</h2>

        <!-- Форма отзыва: гостя просим войти, пользователю показываем поля -->
        <?php if (isLogged()) { ?>
            <form action="actions/review.php" method="post" class="review-form">
                <input type="hidden" name="content_id" value="<?php echo $content['id']; ?>">

                <div>
                    <label for="reviewRating">Оценка</label>
                    <select id="reviewRating" name="rating" required>
                        <?php for ($score = 10; $score >= 1; $score--) { ?>
                            <option value="<?php echo $score; ?>"><?php echo $score; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <label for="reviewText">Ваш отзыв</label>
                    <textarea id="reviewText" name="text" maxlength="1000" placeholder="Поделитесь впечатлением о фильме..." required></textarea>
                </div>

                <button type="submit" class="btn">Опубликовать</button>
            </form>
        <?php } else { ?>
            <p class="review-form-hint"><a href="login.php">Войдите</a>, чтобы оставить отзыв.</p>
        <?php } ?>

        <div class="reviews-list">
            <?php if(count($reviews) == 0): ?>
                <p>Пока нет отзывов. Будьте первым!</p>
            <?php endif; ?>
            <?php foreach ($reviews as $review) { ?>
                <div class="review-card">
                    <div class="review-header">
                        <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                        <span class="review-rating"><?php echo $review['rating']; ?>/10</span>
                    </div>
                    <p><?php echo htmlspecialchars($review['text']); ?></p>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

<!-- Похожее -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Похожее</h2>
        <div class="content-grid">
            <?php foreach ($similar as $item) { ?>
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

<!-- Трейлер: локальное окно без внешнего видео и сторонних ссылок -->
<div class="trailer-modal" id="trailerModal" hidden>
    <div class="trailer-modal-backdrop" data-trailer-close></div>
    <div class="trailer-modal-box" role="dialog" aria-modal="true" aria-labelledby="trailerModalTitle">
        <button type="button" class="trailer-modal-close" data-trailer-close aria-label="Закрыть">
            <img src="<?php echo $baseUrl; ?>assets/img/times.svg" alt="">
        </button>
        <h2 id="trailerModalTitle">Трейлер</h2>
        <img src="<?php echo $playerPoster; ?>" alt="" class="trailer-modal-poster">
        <p>Демонстрационный блок трейлера</p>
        <button type="button" class="btn" data-trailer-close>Закрыть</button>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>