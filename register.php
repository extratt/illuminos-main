<?php
// Регистрация: проверка данных и создание нового пользователя.

// ===== Подключение и подготовка страницы =====
session_start();

require_once "includes/db.php";
require_once "includes/functions.php";
$pageTitle = 'Регистрация — Illuminos';
require_once "includes/header.php";

$error = "";

// ===== Обработка формы регистрации =====

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];

    // Сначала проверяем заполнение и формат всех полей.
    if ($name == "" || $email == "" || $password == "" || $password2 == "") {
        $error = "Заполните все поля.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Введите правильный email.";
    } elseif (strlen($password) < 6) {
        $error = "Пароль должен быть не меньше 6 символов.";
    } elseif ($password != $password2) {
        $error = "Пароли не совпадают.";
    } else {
        // Email должен быть уникальным, дубли не создаём.
        $query = $db->prepare("SELECT id FROM users WHERE email = :email");
        $query->execute(["email" => $email]);

        if ($query->fetch()) {
            $error = "Такой email уже используется.";
        } else {
            // Пароль сохраняем хешем, а не в открытом виде.
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $query = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'user')");
            $query->execute([
                "name" => $name,
                "email" => $email,
                "password" => $hash
            ]);

            // После создания аккаунта отправляем пользователя на вход.
            header("Location: login.php");
            exit;
        }
    }
}
// ===== Вывод HTML: форма регистрации и сообщение об ошибке =====
?>

<section class="auth-page">
    <div class="container">
        <div class="auth-box">
            <h1>Регистрация</h1>

            <?php if ($error != "") { ?>
                <p class="error"><?php echo $error; ?></p>
            <?php } ?>

            <form action="register.php" method="post">
                <label>Имя</label>
                <input type="text" name="name" required>

                <label>Email</label>
                <input type="email" name="email" required>

                <label>Пароль</label>
                <input type="password" name="password" required minlength="6">
                <small class="field-hint">Пароль должен состоять минимум из 6 символов.</small>

                <label>Повторите пароль</label>
                <input type="password" name="password2" required minlength="6">
                <small class="field-hint">Пароль должен состоять минимум из 6 символов.</small>

                <button type="submit" class="btn">Зарегистрироваться</button>
            </form>

            <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
        </div>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>