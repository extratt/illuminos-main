<?php
// Страница подписки: выбор тарифа, имитация оплаты и открытие доступа к просмотру.

// ===== Подключение и проверка доступа =====
session_start();

require_once "includes/db.php";
require_once "includes/functions.php";
$pageTitle = 'Подписка — Illuminos';
require_once "includes/header.php";

// Гостя не пускаем к оформлению — сначала вход.
needLogin();

$userId = (int)$_SESSION['user_id'];

// ===== Настройки оплаты =====
// В тарифах хранится цена и срок в днях — отсюда берётся дата окончания.
$tariffs = [
    'month' => ['name' => 'Месяц', 'price' => 299, 'days' => 30],
    'halfyear' => ['name' => 'Полгода', 'price' => 1499, 'days' => 180],
    'year' => ['name' => 'Год', 'price' => 2499, 'days' => 365],
];

// Способы оплаты нужны и для формы, и для подписи к выбранному варианту.
$methods = [
    'qr' => [
        'name' => 'QR-код',
        'hint' => 'Отсканируйте QR-код в приложении банка.',
        'icon' => 'th.svg',
    ],
    'wallet' => [
        'name' => 'Электронный кошелёк',
        'hint' => 'Подтвердите платеж в приложении кошелька.',
        'icon' => 'gem.svg',
    ],
    'card' => [
        'name' => 'Карта онлайн',
        'hint' => 'Введите данные карты на экране банка.',
        'icon' => 'ruble-sign.svg',
    ],
];

// ===== Текущая подписка пользователя =====
// Активная подписка хранится отдельной строкой: по одной на пользователя.
$query = $db->prepare("SELECT tariff, subscription_until FROM subscriptions WHERE user_id = :id");
$query->execute(["id" => $userId]);
$subscription = $query->fetch(PDO::FETCH_ASSOC);

$currentTariff = $subscription ? $subscription['tariff'] : '';
$currentUntil = $subscription ? $subscription['subscription_until'] : '';

// Старые подписки хранились только в users — подтягиваем тариф из платежей.
if ($currentTariff == '' && !empty($_SESSION['subscription_until'])) {
    $query = $db->prepare("SELECT tariff FROM payments WHERE user_id = :id AND status = 'paid' ORDER BY id DESC LIMIT 1");
    $query->execute(["id" => $userId]);
    $lastPayment = $query->fetch(PDO::FETCH_ASSOC);

    $currentTariff = $lastPayment ? $lastPayment['tariff'] : '';
    $currentUntil = $_SESSION['subscription_until'];
}

// Подписка активна, только если её срок ещё не истёк.
$hasActive = ($currentUntil != '' && strtotime($currentUntil) > time());

// Неизвестный способ оплаты из формы заменяем картой по умолчанию.
$postedMethod = isset($_POST['payment_method']) && is_string($_POST['payment_method'])
    ? $_POST['payment_method']
    : 'card';
$selectedMethod = isset($methods[$postedMethod]) ? $postedMethod : 'card';

// ===== Обработка формы оплаты =====
$message = "";
$error = "";

// Изменения в базе делаем только после отправки формы.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tariff = isset($_POST['tariff']) && is_string($_POST['tariff']) ? $_POST['tariff'] : '';
    $method = $postedMethod;

    if (!isset($tariffs[$tariff])) {
        $error = "Выберите тариф подписки.";
    } elseif (!isset($methods[$method])) {
        $error = "Выберите способ оплаты.";
    } elseif ($hasActive && $currentTariff === $tariff) {
        $error = "Подписка «" . $tariffs[$tariff]['name'] . "» уже активна. Нельзя купить одну подписку дважды. Выберите другой тариф, чтобы заменить её.";
    } else {
        $plan = $tariffs[$tariff];
        $newDate = date("Y-m-d", strtotime("+" . $plan['days'] . " days"));

        // Все изменения применяются вместе: при ошибке покупка не сохранится.
        try {
            $db->beginTransaction();

            // Блокируем строку, чтобы две покупки одновременно не записались.
            $query = $db->prepare("SELECT tariff, subscription_until FROM subscriptions WHERE user_id = :id FOR UPDATE");
            $query->execute(["id" => $userId]);
            $row = $query->fetch(PDO::FETCH_ASSOC);

            $alreadyActive = $row
                && $row['tariff'] === $tariff
                && strtotime($row['subscription_until']) > time();

            if ($alreadyActive) {
                throw new Exception("duplicate");
            }

            // Старую запись удаляем: доступна будет только новая подписка.
            $query = $db->prepare("DELETE FROM subscriptions WHERE user_id = :id");
            $query->execute(["id" => $userId]);

            $query = $db->prepare("INSERT INTO subscriptions (user_id, tariff, payment_method, subscription_until)
                                   VALUES (:user_id, :tariff, :method, :date)");
            $query->execute([
                "user_id" => $userId,
                "tariff" => $tariff,
                "method" => $method,
                "date" => $newDate,
            ]);

            // Обычного пользователя делаем подписчиком, роль администратора не трогаем.
            $query = $db->prepare("UPDATE users
                                   SET subscription_until = :date,
                                       role = CASE WHEN role = 'admin' THEN 'admin' ELSE 'subscriber' END
                                   WHERE id = :id");
            $query->execute([
                "date" => $newDate,
                "id" => $userId,
            ]);

            $query = $db->prepare("INSERT INTO payments (user_id, tariff, price, payment_method, status)
                                   VALUES (:user_id, :tariff, :price, :method, 'paid')");
            $query->execute([
                "user_id" => $userId,
                "tariff" => $tariff,
                "price" => $plan['price'],
                "method" => $method,
            ]);

            // Фиксируем все изменения разом.
            $db->commit();

            // Сессию обновляем сразу, чтобы кабинет видел новую подписку.
            $_SESSION['subscription_until'] = $newDate;
            $_SESSION['role'] = ($_SESSION['role'] == 'admin') ? 'admin' : 'subscriber';

            $currentTariff = $tariff;
            $currentUntil = $newDate;
            $hasActive = true;

            $message = "Оплата через " . $methods[$method]['name'] . " прошла успешно. Подписка «" . $plan['name'] . "» активна до " . $newDate . ". Старый тариф заменён.";
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            if ($e->getMessage() === "duplicate") {
                $error = "Подписка «" . $tariffs[$tariff]['name'] . "» уже активна. Нельзя купить одну подписку дважды.";
            } else {
                $error = "Не удалось оформить подписку. Попробуйте ещё раз.";
            }
        }
    }
}
// ===== Вывод HTML: статус подписки, способ оплаты и тарифы =====
?>

<section class="page-head">
    <div class="container">
        <h1>Подписка Illuminos</h1>
        <p>Выберите тариф и удобный способ оплаты. В учебном проекте списание денег имитируется.</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if ($error != "") { ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>

        <?php if ($message != "") { ?>
            <p class="success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>

        <?php if ($currentTariff != "" && isset($tariffs[$currentTariff])) { ?>
            <div class="current-subscription">
                <img src="<?php echo $baseUrl; ?>assets/img/check.svg" alt="" class="btn-icon">
                <strong>Ваша подписка:</strong>
                <?php echo htmlspecialchars($tariffs[$currentTariff]['name'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($hasActive) { ?>
                    — активна до <?php echo htmlspecialchars($currentUntil, ENT_QUOTES, 'UTF-8'); ?>.
                    <span>Повторная покупка этого тарифа недоступна. Выберите другой тариф, чтобы заменить подписку.</span>
                <?php } else { ?>
                    — срок истёк <?php echo htmlspecialchars($currentUntil, ENT_QUOTES, 'UTF-8'); ?>.
                    <span>Вы можете оформить подписку повторно или выбрать другой тариф.</span>
                <?php } ?>
            </div>
        <?php } ?>

        <form action="payment.php" method="post" class="payment-form">

            <h2 class="section-title">Способ оплаты</h2>

            <div class="payment-methods">
                <?php foreach ($methods as $key => $method) { ?>
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="<?php echo $key; ?>"
                               <?php if ($selectedMethod === $key) echo "checked"; ?>>
                        <span class="method-icon">
                            <img src="<?php echo $baseUrl; ?>assets/img/<?php echo $method['icon']; ?>" alt="" class="method-icon-img">
                        </span>
                        <span class="method-name"><?php echo htmlspecialchars($method['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>
                <?php } ?>
            </div>

            <p class="payment-note" id="paymentNote"><?php echo htmlspecialchars($methods[$selectedMethod]['hint'], ENT_QUOTES, 'UTF-8'); ?></p>

            <h2 class="section-title">Тариф</h2>

            <div class="tariff-grid">

                <div class="tariff-card <?php if ($hasActive && $currentTariff === 'month') echo 'is-current'; ?>">
                    <h3>Месяц</h3>
                    <p class="price">299 ₽</p>

                    <ul>
                        <li>Фильмы и сериалы</li>
                        <li>Без рекламы</li>
                        <li>Один экран</li>
                    </ul>

                    <button type="submit" name="tariff" value="month" class="btn"
                            <?php if ($hasActive && $currentTariff === 'month') echo 'disabled'; ?>>
                        <?php echo ($hasActive && $currentTariff === 'month') ? 'Уже активна' : 'Оплатить'; ?>
                    </button>
                </div>

                <div class="tariff-card active <?php if ($hasActive && $currentTariff === 'halfyear') echo 'is-current'; ?>">
                    <h3>Полгода</h3>
                    <p class="price">1 499 ₽</p>

                    <ul>
                        <li>Фильмы и сериалы</li>
                        <li>Без рекламы</li>
                        <li>Два экрана</li>
                        <li>Скачивание</li>
                    </ul>

                    <button type="submit" name="tariff" value="halfyear" class="btn"
                            <?php if ($hasActive && $currentTariff === 'halfyear') echo 'disabled'; ?>>
                        <?php echo ($hasActive && $currentTariff === 'halfyear') ? 'Уже активна' : 'Оплатить'; ?>
                    </button>
                </div>

                <div class="tariff-card <?php if ($hasActive && $currentTariff === 'year') echo 'is-current'; ?>">
                    <h3>Год</h3>
                    <p class="price">2 499 ₽</p>

                    <ul>
                        <li>Фильмы и сериалы</li>
                        <li>Без рекламы</li>
                        <li>Четыре экрана</li>
                        <li>Скачивание</li>
                        <li>4K</li>
                    </ul>

                    <button type="submit" name="tariff" value="year" class="btn"
                            <?php if ($hasActive && $currentTariff === 'year') echo 'disabled'; ?>>
                        <?php echo ($hasActive && $currentTariff === 'year') ? 'Уже активна' : 'Оплатить'; ?>
                    </button>
                </div>

            </div>

        </form>

    </div>
</section>

<?php
require_once "includes/footer.php";
?>
