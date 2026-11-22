</main>

<!-- ===== Подвал сайта ===== -->
<footer class="footer">
    <div class="container footer-inner">

        <!-- Логотип и краткое описание кинотеатра -->
        <div>
            <a href="<?php echo $baseUrl; ?>index.php" class="footer-logo">
                <img src="<?php echo $baseUrl; ?>assets/img/Illuminos-2.svg" alt="Illuminos" class="footer-logo-img">
            </a>
            <p>Онлайн-кинотеатр с фильмами и сериалами</p>

            <!-- Ссылки на социальные сети -->
            <p class="footer-social-title">Мы в соцсетях</p>
            <div class="footer-socials">
                    <img src="<?php echo $baseUrl; ?>assets/img/yandex.svg" alt="Яндекс" class="social-icon">
                </a>
                    <img src="<?php echo $baseUrl; ?>assets/img/vk.svg" alt="ВКонтакте" class="social-icon">
                </a>
                    <img src="<?php echo $baseUrl; ?>assets/img/odnoklassniki-square.svg" alt="Одноклассники" class="social-icon">
                </a>
            </div>
        </div>

        <!-- Ссылки на разделы каталога -->
        <div>
            <p class="footer-title">Разделы</p>
            <a href="<?php echo $baseUrl; ?>movies.php">Фильмы</a>
            <a href="<?php echo $baseUrl; ?>series.php">Сериалы</a>
            <a href="<?php echo $baseUrl; ?>payment.php">Подписка</a>
        </div>

        <!-- Ссылки, связанные с аккаунтом -->
        <div>
            <p class="footer-title">Аккаунт</p>
            <a href="<?php echo $baseUrl; ?>login.php">Вход</a>
            <a href="<?php echo $baseUrl; ?>register.php">Регистрация</a>
            <a href="<?php echo $baseUrl; ?>profile.php">Личный кабинет</a>
        </div>

    </div>

    <!-- Копирайт -->
    <p class="footer-copy">© 2026 Illuminos</p>
</footer>

<!-- Кнопка «наверх» возвращает к началу страницы -->
<button id="scrollTop" aria-label="Наверх">
    <img src="<?php echo $baseUrl; ?>assets/img/chevron-up.svg" alt="" class="scroll-icon">
</button>

<!-- Скрипт сайта из папки assets -->
<script src="<?php echo $baseUrl; ?>assets/js/script.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/script.js'); ?>"></script>
</body>
</html>