<?php
// Админ-панель: управление аккаунтами пользователей.

session_start();

require_once "../includes/db.php";
require_once "../includes/functions.php";

// Правы проверяем до вывода шапки, чтобы редирект сработал без ошибок.
needAdmin();

$pageTitle = 'Аккаунты пользователей — Illuminos';
$adminBodyClass = true;
require_once "../includes/header.php";

// ===== Справочники =====
$roles = [
    'user' => 'Пользователь',
    'subscriber' => 'Подписчик',
    'admin' => 'Администратор',
];

$tariffs = [
    'month' => 'Месяц',
    'halfyear' => 'Полгода',
    'year' => 'Год',
];

$error = "";
$message = "";

if (isset($_GET['saved'])) {
    $message = "Изменения сохранены.";
}
if (isset($_GET['deleted'])) {
    $message = "Аккаунт удалён.";
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$currentUserId = (int)$_SESSION['user_id'];

// ===== Удаление аккаунта =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'delete') {
    $targetId = (int)(isset($_POST['user_id']) ? $_POST['user_id'] : 0);

    if ($targetId === $currentUserId) {
        $error = "Нельзя удалить собственный аккаунт.";
    } else {
        $query = $db->prepare("SELECT role FROM users WHERE id = :id");
        $query->execute(["id" => $targetId]);
        $target = $query->fetch();

        if (!$target) {
            $error = "Такого пользователя больше нет.";
        } elseif ($target['role'] == 'admin') {
            // В системе всегда должен остаться хотя бы один администратор.
            $admins = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($admins <= 1) {
                $error = "Нельзя удалить последнего администратора.";
            }
        }

        if ($error == "") {
            $query = $db->prepare("DELETE FROM users WHERE id = :id");
            $query->execute(["id" => $targetId]);
            header("Location: users.php?deleted=1");
            exit;
        }
    }
}

// ===== Создание и редактирование аккаунта =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'save') {
    $targetId = (int)(isset($_POST['user_id']) ? $_POST['user_id'] : 0);
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'user';
    $tariff = isset($_POST['tariff']) ? $_POST['tariff'] : '';
    $until = trim(isset($_POST['subscription_until']) ? $_POST['subscription_until'] : '');

    if ($name == "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Укажите имя и корректный email.";
    } elseif (!isset($roles[$role])) {
        $error = "Выберите правильную роль.";
    } elseif ($targetId === $currentUserId) {
        // Свою роль администратор менять не может, иначе потеряет доступ к панели.
        $role = 'admin';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = "Пароль должен содержать не менее 6 символов.";
    } elseif ($targetId === 0 && $password === '') {
        $error = "Для нового пользователя нужен пароль.";
    }

    // Проверяем дату подписки, если она указана.
    $subscriptionDate = null;
    if ($error == "" && $until !== '') {
        $date = DateTime::createFromFormat('Y-m-d', $until);
        if (!$date || $date->format('Y-m-d') !== $until || strtotime($until) <= time()) {
            $error = "Укажите будущую дату окончания подписки.";
        } elseif (!isset($tariffs[$tariff])) {
            $error = "Выберите тариф подписки.";
        } else {
            $subscriptionDate = $until;
        }
    }

    if ($error == "") {
        // Email должен быть уникальным; текущий пользователь не считается конфликтом.
        $query = $db->prepare("SELECT id FROM users WHERE email = :email AND id <> :id");
        $query->execute(["email" => $email, "id" => $targetId]);
        if ($query->fetch()) {
            $error = "Пользователь с таким email уже существует.";
        }
    }

    if ($error == "") {
        try {
            $db->beginTransaction();

            if ($targetId === 0) {
                $query = $db->prepare("INSERT INTO users (name, email, password, role, subscription_until)
                    VALUES (:name, :email, :password, :role, :until)");
                $query->execute([
                    "name" => $name,
                    "email" => $email,
                    "password" => password_hash($password, PASSWORD_DEFAULT),
                    "role" => $role,
                    "until" => $subscriptionDate,
                ]);
                $targetId = (int)$db->lastInsertId();
            } else {
                $query = $db->prepare("SELECT id FROM users WHERE id = :id");
                $query->execute(["id" => $targetId]);
                if (!$query->fetch()) {
                    throw new RuntimeException("Пользователь не найден.");
                }

                $sql = "UPDATE users SET name = :name, email = :email, role = :role,
                    subscription_until = :until";
                $params = [
                    "name" => $name,
                    "email" => $email,
                    "role" => $role,
                    "until" => $subscriptionDate,
                ];

                // Пустой пароль означает, что менять его не нужно.
                if ($password !== '') {
                    $sql .= ", password = :password";
                    $params["password"] = password_hash($password, PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id = :id";
                $params["id"] = $targetId;

                $query = $db->prepare($sql);
                $query->execute($params);
            }

            // Одна активная подписка на пользователя: старая запись заменяется новой.
            $query = $db->prepare("DELETE FROM subscriptions WHERE user_id = :id");
            $query->execute(["id" => $targetId]);

            if ($subscriptionDate !== null) {
                $query = $db->prepare("INSERT INTO subscriptions
                    (user_id, tariff, payment_method, subscription_until)
                    VALUES (:id, :tariff, 'admin', :until)");
                $query->execute([
                    "id" => $targetId,
                    "tariff" => $tariff,
                    "until" => $subscriptionDate,
                ]);
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = "Не удалось сохранить аккаунт: " . $e->getMessage();
        }

        if ($error == "") {
            // Если админ изменил свой аккаунт, обновляем и данные в сессии.
            if ($targetId === $currentUserId) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['subscription_until'] = $subscriptionDate;
            }
            header("Location: users.php?saved=1");
            exit;
        }
    }
}

// ===== Данные для формы =====
$formData = [
    "id" => 0,
    "name" => "",
    "email" => "",
    "role" => "user",
    "password" => "",
    "tariff" => "",
    "subscription_until" => "",
];

$showForm = isset($_GET['edit']) || ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'save' && $error != "");

if (isset($_GET['edit'])) {
    $editId = $_GET['edit'] === 'new' ? 0 : (int)$_GET['edit'];

    if ($editId > 0) {
        $query = $db->prepare("SELECT u.*, s.tariff, s.subscription_until AS active_until
            FROM users u
            LEFT JOIN subscriptions s ON s.user_id = u.id
            WHERE u.id = :id");
        $query->execute(["id" => $editId]);
        $user = $query->fetch();

        if ($user) {
            $formData = [
                "id" => (int)$user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role'],
                "password" => "",
                "tariff" => $user['tariff'] ? $user['tariff'] : '',
                "subscription_until" => $user['active_until'] ? $user['active_until'] : '',
            ];
        }
    }
}

// После ошибки показываем то, что вводил пользователь, а не старые данные.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'save' && $error != "") {
    $formData["id"] = (int)(isset($_POST['user_id']) ? $_POST['user_id'] : 0);
    $formData["name"] = isset($_POST['name']) ? trim($_POST['name']) : '';
    $formData["email"] = isset($_POST['email']) ? trim($_POST['email']) : '';
    $formData["role"] = isset($_POST['role']) ? $_POST['role'] : 'user';
    $formData["tariff"] = isset($_POST['tariff']) ? $_POST['tariff'] : '';
    $formData["subscription_until"] = isset($_POST['subscription_until']) ? trim($_POST['subscription_until']) : '';
}

// ===== Список аккаунтов и сводка =====
$users = $db->query("SELECT u.*, s.tariff, s.payment_method, s.subscription_until AS active_until,
    (SELECT COUNT(*) FROM favorites f WHERE f.user_id = u.id) AS favorites_count,
    (SELECT COUNT(*) FROM payments p WHERE p.user_id = u.id) AS payments_count
    FROM users u
    LEFT JOIN subscriptions s ON s.user_id = u.id
    ORDER BY u.id")->fetchAll();

$stats = $db->query("SELECT COUNT(*) AS total,
    SUM(role = 'admin') AS admins,
    SUM(role = 'subscriber') AS subscribers
    FROM users")->fetch();

$activeSubscriptions = (int)$db->query("SELECT COUNT(*) FROM subscriptions
    WHERE subscription_until > CURDATE()")->fetchColumn();

// ===== Вывод =====
?>

<section class="page-head">
    <div class="container">
        <h1>Аккаунты пользователей</h1>
        <p>Роли, пароли и подписки посетителей сайта.</p>
    </div>
</section>

<?php require_once __DIR__ . '/_nav.php'; ?>

<section class="section">
    <div class="container">

        <?php if ($error != "") { ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>

        <?php if ($message != "") { ?>
            <p class="success"><?php echo htmlspecialchars($message); ?></p>
        <?php } ?>

        <!-- Сводка по аккаунтам -->
        <div class="admin-stats">
            <div class="admin-stat"><span>Всего аккаунтов</span><strong><?php echo $stats['total']; ?></strong></div>
            <div class="admin-stat"><span>Администраторы</span><strong><?php echo $stats['admins']; ?></strong></div>
            <div class="admin-stat"><span>Подписчики</span><strong><?php echo $stats['subscribers']; ?></strong></div>
            <div class="admin-stat"><span>Активные подписки</span><strong><?php echo $activeSubscriptions; ?></strong></div>
        </div>

        <div class="admin-panel-title">
            <h2><?php echo $showForm ? 'Аккаунт пользователя' : 'Пользователи'; ?></h2>
            <?php if ($showForm) { ?>
                <a href="users.php" class="btn btn-outline">Все аккаунты</a>
            <?php } else { ?>
                <a href="users.php?edit=new" class="btn">Добавить пользователя</a>
            <?php } ?>
        </div>

        <?php if ($showForm) { ?>
            <!-- Форма создания и редактирования аккаунта -->
            <form action="users.php" method="post" class="admin-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="user_id" value="<?php echo $formData['id']; ?>">

                <div class="admin-form-grid">
                    <div class="admin-field">
                        <label for="adminName">Имя</label>
                        <input type="text" id="adminName" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                    </div>

                    <div class="admin-field">
                        <label for="adminEmail">Email</label>
                        <input type="email" id="adminEmail" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                    </div>

                    <div class="admin-field">
                        <label for="adminPassword">
                            <?php echo $formData['id'] > 0 ? 'Новый пароль (если нужно менять)' : 'Пароль'; ?>
                        </label>
                        <input type="password" id="adminPassword" name="password" minlength="6"
                               autocomplete="new-password" <?php echo $formData['id'] > 0 ? '' : 'required'; ?>>
                    </div>

                    <div class="admin-field">
                        <label for="adminRole">Роль</label>
                        <?php if ($formData['id'] === $currentUserId) { ?>
                            <input type="hidden" name="role" value="admin">
                            <select id="adminRole" disabled>
                                <option selected>Администратор</option>
                            </select>
                        <?php } else { ?>
                            <select id="adminRole" name="role">
                                <?php foreach ($roles as $key => $label) { ?>
                                    <option value="<?php echo $key; ?>"
                                        <?php echo $formData['role'] === $key ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        <?php } ?>
                    </div>

                    <div class="admin-field">
                        <label for="adminTariff">Тариф подписки</label>
                        <select id="adminTariff" name="tariff">
                            <option value="">Без подписки</option>
                            <?php foreach ($tariffs as $key => $label) { ?>
                                <option value="<?php echo $key; ?>"
                                    <?php echo $formData['tariff'] === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="admin-field">
                        <label for="adminUntil">Подписка до</label>
                        <input type="date" id="adminUntil" name="subscription_until"
                               value="<?php echo htmlspecialchars($formData['subscription_until']); ?>">
                    </div>

                    <p class="admin-form-note">
                        Если указана дата, старая подписка заменяется новой. Пустая дата убирает подписку.
                        История платежей при этом сохраняется.
                    </p>

                    <div class="admin-form-actions">
                        <button type="submit" class="btn">Сохранить</button>
                        <a href="users.php" class="btn btn-outline">Отмена</a>
                    </div>
                </div>
            </form>

        <?php } else { ?>
            <!-- Поиск по списку аккаунтов -->
            <div class="admin-toolbar">
                <div class="admin-search">
                    <label for="adminSearch">Поиск по имени или email</label>
                    <input type="search" id="adminSearch" placeholder="Начните вводить имя или email">
                </div>
                <span class="admin-found" id="adminFound" data-forms="пользователь,пользователя,пользователей"><?php echo count($users); ?> пользователей</span>
            </div>

            <p class="admin-empty" id="adminEmpty" hidden>Ничего не найдено</p>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Роль</th>
                            <th>Подписка</th>
                            <th>Регистрация</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user) { ?>
                        <?php
                        $isActive = !empty($user['active_until']) && strtotime($user['active_until']) > time();
                        $roleClass = $user['role'] == 'admin' ? 'is-admin' : ($user['role'] == 'subscriber' ? 'is-subscriber' : '');
                        $statusText = "Нет подписки";

                        if (!empty($user['active_until'])) {
                            $statusText = ($isActive ? "Активна до " : "Истекла ") . date('d.m.Y', strtotime($user['active_until']));
                        }

                        $statusDetail = "";
                        if ($isActive && isset($tariffs[$user['tariff']])) {
                            $statusDetail = "Тариф: " . $tariffs[$user['tariff']];
                        }
                        if ((int)$user['payments_count'] > 0) {
                            $statusDetail .= ($statusDetail != "" ? ", " : "") . "Платежей: " . $user['payments_count'];
                        }
                        ?>
                        <tr>
                            <td data-label="ID"><?php echo $user['id']; ?></td>

                            <td data-label="Пользователь">
                                <div class="admin-user-cell">
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </td>

                            <td data-label="Роль">
                                <span class="admin-role <?php echo $roleClass; ?>">
                                    <?php echo isset($roles[$user['role']]) ? $roles[$user['role']] : 'Пользователь'; ?>
                                </span>
                            </td>

                            <td data-label="Подписка">
                                <div class="admin-subscription <?php echo $isActive ? 'is-active' : 'is-empty'; ?>">
                                    <?php echo $statusText; ?>
                                    <?php if ($statusDetail != "") { ?>
                                        <small><?php echo htmlspecialchars($statusDetail); ?></small>
                                    <?php } ?>
                                </div>
                            </td>

                            <td data-label="Регистрация"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>

                            <td data-label="Действия">
                                <a href="users.php?edit=<?php echo $user['id']; ?>">Изменить</a>

                                <?php if ((int)$user['id'] === $currentUserId) { ?>
                                    <span class="admin-self-mark">Это вы</span>
                                <?php } else { ?>
                                    <form action="users.php" method="post" class="admin-inline-form"
                                          onsubmit="return confirm('Удалить этот аккаунт?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="admin-table-action is-danger">Удалить</button>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>

        <?php } ?>

    </div>
</section>

<?php
require_once "../includes/footer.php";
?>
