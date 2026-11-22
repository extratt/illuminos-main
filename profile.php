<?php
// Личный кабинет: данные пользователя, подписка, история просмотров и избранное.

// ===== Подключение и проверка доступа =====
session_start();

require_once "includes/db.php";
require_once "includes/functions.php";
$pageTitle = 'Личный кабинет — Illuminos';
require_once "includes/header.php";

// Кабинет доступен только после входа.
needLogin();

$userId = $_SESSION['user_id'];

// ===== Запросы к базе =====
// К истории сразу берём данные контента, чтобы получить постеры и названия.
$query = $db->prepare("SELECT content.*, history.watched_at
    FROM history
    JOIN content ON history.content_id = content.id
    WHERE history.user_id = :user_id
    ORDER BY history.watched_at DESC");

$query->execute(["user_id" => $userId]);
$history = $query->fetchAll();

// Избранное выбираем так же — для тех же карточек на странице.
$query = $db->prepare("SELECT content.*
    FROM favorites
    JOIN content ON favorites.content_id = content.id
    WHERE favorites.user_id = :user_id
    ORDER BY favorites.added_at DESC");

$query->execute(["user_id" => $userId]);
$favorites = $query->fetchAll();

// ===== Вывод HTML: данные профиля, история и избранное =====
?>

<section class="page-head">
    <div class="container">
        <h1>Личный кабинет</h1>
        <p>Здравствуйте, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="profile-info">
            <p><strong>Имя:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            
            <?php 
                // Роль из сессии переводим в понятное пользователю название.
                $role = $_SESSION['role'];
                $roleName = "Пользователь";
                if ($role == 'admin') $roleName = "Администратор";
                if ($role == 'subscriber') $roleName = "Подписчик";
            ?>
            <p><strong>Статус аккаунта:</strong> <?php echo $roleName; ?></p>

            <p>
                <strong>Подписка:</strong>
                <?php if (hasSubscription()) { ?>
                    активна до <?php echo $_SESSION['subscription_until']; ?>
                <?php } else { ?>
                    не активна
                <?php } ?>
            </p>

            <!-- Блок кнопок -->
            <div class="profile-actions">
                <a href="payment.php" class="btn">Оформить подписку</a>
                
                <?php if (isAdmin()) { ?>
                    <!-- Если админ, показываем кнопку перехода в админку -->
                    <a href="admin/index.php" class="btn btn-outline" style="border-color: var(--accent-pink); color: var(--accent-pink);">
                        Перейти в Админ-панель
                    </a>
                <?php } ?>
            </div>
        </div>

    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <h2 class="section-title">История просмотров</h2>

        <?php if (count($history) == 0) { ?>
            <p>Вы пока ничего не смотрели.</p>
        <?php } ?>

        <div class="content-grid">
            <?php foreach ($history as $item) { ?>
                <a href="content.php?id=<?php echo $item['id']; ?>" class="card">
                    <img src="<?php echo $item['poster']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">

                    <div class="card-info">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><?php echo $item['watched_at']; ?></p>
                    </div>
                </a>
            <?php } ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Избранное</h2>

        <?php if (count($favorites) == 0) { ?>
            <p>В избранном пока ничего нет.</p>
        <?php } ?>

        <div class="content-grid">
            <?php foreach ($favorites as $item) { ?>
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