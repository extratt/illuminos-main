<?php
// ===== Боковое меню админ-панели =====

// Имя текущей страницы нужно, чтобы подсветить активный раздел.
$currentAdminPage = basename($_SERVER['PHP_SELF']);

$adminSections = [
    [
        'href' => 'index.php',
        'label' => 'Каталог',
        'icon' => 'video.svg',
        'pages' => ['index.php', 'edit.php', 'delete.php'],
    ],
    [
        'href' => 'add.php',
        'label' => 'Добавить',
        'icon' => 'plus.svg',
        'pages' => ['add.php'],
    ],
    [
        'href' => 'categories.php',
        'label' => 'Категории',
        'icon' => 'th-list.svg',
        'pages' => ['categories.php'],
    ],
    [
        'href' => 'users.php',
        'label' => 'Аккаунты',
        'icon' => 'users.svg',
        'pages' => ['users.php'],
    ],
];
?>

<aside class="admin-sidebar" aria-label="Меню админ-панели">

    <!-- Логотип и подпись панели -->
    <a href="../index.php" class="admin-sidebar-brand" title="Перейти на сайт">
        <img src="../assets/img/Illuminos-2.svg" alt="Illuminos" class="admin-sidebar-logo">
        <span class="admin-sidebar-brand-text">Админ-панель</span>
    </a>

    <!-- Основные разделы -->
    <nav class="admin-sidebar-menu">
        <?php foreach ($adminSections as $section) { ?>
            <a href="<?php echo $section['href']; ?>"
               class="admin-sidebar-link <?php echo in_array($currentAdminPage, $section['pages'], true) ? 'active' : ''; ?>"
               title="<?php echo $section['label']; ?>">
                <img src="../assets/img/<?php echo $section['icon']; ?>" alt="" class="admin-sidebar-icon">
                <span class="admin-sidebar-label"><?php echo $section['label']; ?></span>
            </a>
        <?php } ?>
    </nav>

    <!-- Быстрый выход обратно на сайт и из аккаунта -->
    <div class="admin-sidebar-bottom">
        <a href="../index.php" class="admin-sidebar-link" title="На сайт">
            <img src="../assets/img/home.svg" alt="" class="admin-sidebar-icon">
            <span class="admin-sidebar-label">На сайт</span>
        </a>
        <a href="../logout.php" class="admin-sidebar-link" title="Выйти">
            <img src="../assets/img/sign-out-alt.svg" alt="" class="admin-sidebar-icon">
            <span class="admin-sidebar-label">Выйти</span>
        </a>
    </div>

</aside>
