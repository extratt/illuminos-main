<?php
// Вход в аккаунт: проверка пароля и заполнение данных сессии.

// ===== Подключение и подготовка страницы =====
session_start();

require_once "includes/db.php";
require_once "includes/functions.php";
$pageTitle = 'Вход — Illuminos';
require_once "includes/header.php";

$error = "";

// ===== Обработка формы входа =====

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Пустые поля отклоняем сразу, без обращения к базе.
    if ($email == "" || $password == "") {
        $error = "Заполните все поля.";
    } else {
        $query = $db->prepare("SELECT * FROM users WHERE email = :email");
        $query->execute(["email" => $email]);

        $user = $query->fetch();

        // Пароль хранится хешем, поэтому сравниваем его специальной функцией.
        if ($user && password_verify($password, $user['password'])) {
            // Данные пользователя нужны на других страницах — кладём их в сессию.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['subscription_until'] = $user['subscription_until'];

            // Администратора отправляем в админку, остальных — в кабинет.
            if ($user['role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: profile.php");
            }

            exit;
        } else {
            $error = "Неправильный email или пароль.";
        }
    }
}
// ===== Вывод HTML: форма входа и сообщение об ошибке =====
?>

<section class="auth-page">
    <div class="container">
        <div class="auth-box">
            <h1>Вход</h1>

            <?php if ($error != "") { ?>
                <p class="error"><?php echo $error; ?></p>
            <?php } ?>

            <form action="login.php" method="post">
                <label>Email</label>
                <input type="email" name="email" required>

                <label>Пароль</label>
                <input type="password" name="password" required minlength="6">
                <small class="field-hint">Пароль должен состоять минимум из 6 символов.</small>

                <button type="submit" class="btn">Войти</button>
            </form>

            <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
        </div>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>