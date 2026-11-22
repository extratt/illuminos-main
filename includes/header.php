<?php
// ===== Общая шапка сайта =====

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Админ-страницы лежат глубже, поэтому их пути начинаются с ../
$baseUrl = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Illuminos — онлайн-кинотеатр'; ?></title>

    <!-- Стили сайта из папки assets -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
</head>
<body<?php echo !empty($adminBodyClass) ? ' class="admin-page"' : ''; ?>>

<header class="header">
    <div class="container header-inner">

        <!-- Логотип: ссылка на главную страницу -->
        <a href="<?php echo $baseUrl; ?>index.php" class="logo">
            <img src="<?php echo $baseUrl; ?>assets/img/Illuminos-2.svg" alt="Illuminos" class="logo-img">
        </a>

        <!-- Бургер открывает меню на мобильных экранах -->
        <button class="burger" aria-label="Меню" aria-expanded="false">
            <img src="<?php echo $baseUrl; ?>assets/img/bars.svg" alt="" class="burger-icon">
        </button>

        <!-- Панель: навигация и поиск, на телефоне прячется в бургер -->
        <div class="header-panel" id="headerPanel">
            <!-- Основная навигация по сайту -->
            <nav class="nav">
                <a href="<?php echo $baseUrl; ?>index.php">Главная</a>
                <a href="<?php echo $baseUrl; ?>movies.php">Фильмы</a>
                <a href="<?php echo $baseUrl; ?>series.php">Сериалы</a>
                <a href="<?php echo $baseUrl; ?>payment.php">Подписка</a>

                <?php if (isAdmin()) { ?>
                    <a href="<?php echo $baseUrl; ?>admin/index.php">Админ-панель</a>
                <?php } ?>
            </nav>

            <!-- Поиск по фильмам и сериалам -->
            <form action="<?php echo $baseUrl; ?>search.php" method="get" class="search-form">
                <input type="text" name="q" placeholder="Поиск фильма или сериала">
                <button type="submit" aria-label="Найти">
                    <img src="<?php echo $baseUrl; ?>assets/img/search.svg" alt="" class="search-icon">
                </button>
            </form>
        </div>

        <!-- Иконки пользователя: кабинет и выход либо вход и регистрация -->
        <div class="user-menu">
            <?php if (isLogged()) { ?>
                <!-- Для вошедшего: личный кабинет и выход -->
                <a href="<?php echo $baseUrl; ?>profile.php" class="header-icon-link" aria-label="Личный кабинет" title="Личный кабинет">
                    <img src="<?php echo $baseUrl; ?>assets/img/user.svg" alt="" class="header-icon">
                </a>
                <a href="<?php echo $baseUrl; ?>logout.php" class="header-icon-link" aria-label="Выйти" title="Выйти">
                    <img src="<?php echo $baseUrl; ?>assets/img/sign-out-alt.svg" alt="" class="header-icon">
                </a>
            <?php } else { ?>
                <!-- Для гостя: вход и регистрация -->
                <a href="<?php echo $baseUrl; ?>login.php" class="header-icon-link" aria-label="Войти" title="Войти">
                    <img src="<?php echo $baseUrl; ?>assets/img/sign-in-alt.svg" alt="" class="header-icon">
                </a>
                <a href="<?php echo $baseUrl; ?>register.php" class="header-icon-link header-icon-accent" aria-label="Регистрация" title="Регистрация">
                    <img src="<?php echo $baseUrl; ?>assets/img/user-plus.svg" alt="" class="header-icon">
                </a>
            <?php } ?>
        </div>

    </div>
</header>

<main class="main">