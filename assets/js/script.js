// ===== Интерактив и анимации =====

(function () {
    "use strict";

    // ===== Появление элементов при прокрутке =====
    // Что делает: плавно проявляет заголовки и карточки при входе в видимую область.
    function initReveal() {
        const targets = document.querySelectorAll(
            ".section-title, .page-head h1, .page-head p, .profile-info, .auth-box, .tariff-card, .admin-form, .season-box, .content-poster"
        );

        if (!("IntersectionObserver" in window) || targets.length === 0) {
            return;
        }

        // Скрываем элементы заранее, чтобы потом проявить их при прокрутке.
        targets.forEach((el) => el.classList.add("reveal"));

        // Что делает: добавляет класс видимости и снимает наблюдение после появления.
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("reveal-visible");
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
        );

        targets.forEach((el) => observer.observe(el));
    }

    // ===== Кнопка «наверх» =====
    // Что делает: показывает кнопку после прокрутки и возвращает страницу к началу.
    function initScrollTop() {
        const btn = document.getElementById("scrollTop");
        if (!btn) return;

        // Что делает: показывает кнопку после прокрутки и скрывает в начале страницы.
        const toggle = () => {
            if (window.scrollY > 400) {
                // Показываем как гибкий контейнер, чтобы стрелка встала по центру.
                btn.style.display = "flex";
                btn.style.animation = "fadeUp 0.3s ease both";
            } else {
                btn.style.display = "none";
            }
        };

        window.addEventListener("scroll", toggle, { passive: true });
        toggle();

        // Что делает: плавно возвращает к верху страницы.
        btn.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    // ===== Шапка при прокрутке =====
    // Что делает: добавляет шапке класс «прокручено», когда страница уходит вниз.
    function initHeaderScroll() {
        const header = document.querySelector(".header");
        if (!header) return;

        // Что делает: переключает класс шапки на высоте больше 10 пикселей.
        const toggle = () => {
            header.classList.toggle("scrolled", window.scrollY > 10);
        };

        window.addEventListener("scroll", toggle, { passive: true });
        toggle();
    }

    // ===== Бургер-меню =====
    // Что делает: открывает и закрывает меню в шапке.
    function initBurger() {
        const burger = document.querySelector(".burger");
        const panel = document.getElementById("headerPanel");
        if (!burger || !panel) return;

        // Что делает: закрывает меню и возвращает кнопке исходное состояние.
        function close() {
            burger.classList.remove("active");
            panel.classList.remove("open");
            burger.setAttribute("aria-expanded", "false");
        }

        // Что делает: переключает меню по клику на бургер.
        burger.addEventListener("click", () => {
            const open = !panel.classList.contains("open");
            burger.classList.toggle("active", open);
            panel.classList.toggle("open", open);
            burger.setAttribute("aria-expanded", open ? "true" : "false");
        });

        // Что делает: закрывает меню после выбора ссылки.
        panel.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", close);
        });

        // Что делает: закрывает меню кликом вне меню и кнопки.
        document.addEventListener("click", (e) => {
            if (panel.classList.contains("open") && !panel.contains(e.target) && !burger.contains(e.target)) {
                close();
            }
        });

        // Что делает: закрывает меню по нажатию клавиши выхода.
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && panel.classList.contains("open")) {
                close();
            }
        });
    }

    // ===== Админка =====
    // Что делает: спрашивает подтверждение перед удалением элемента.
    window.confirmDelete = function () {
        return confirm("Вы уверены, что хотите удалить этот элемент?");
    };

    // ===== Кнопка «Смотреть» =====
    // Что делает: заставляет кнопку пульсировать, чтобы привлечь внимание.
    function initPulse() {
        const watchBtn = document.querySelector('.content-buttons .btn:first-child');
        if (watchBtn) {
            watchBtn.style.animation = "pulseGlow 2.4s ease-in-out infinite";
        }
    }

    // ===== Параллакс баннера =====
    // Что делает: сдвигает фон баннера при прокрутке.
    function initParallax() {
        const hero = document.querySelector(".hero");
        if (!hero) return;

        // Не включаем эффект при настройке «меньше движения» и на узких экранах.
        const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        if (reduce || window.innerWidth < 768) return;

        let raf = null;

        // Что делает: ждёт следующий кадр, чтобы не перерисовывать фон слишком часто.
        window.addEventListener(
            "scroll",
            () => {
                if (raf) return;
                raf = requestAnimationFrame(() => {
                    const offset = window.scrollY * 0.25;
                    hero.style.backgroundPositionY = `calc(center + ${offset}px)`;
                    raf = null;
                });
            },
            { passive: true }
        );
    }

    // ===== Карточки: наклон и свечение =====
    // Что делает: наклоняет карточку за курсором и добавляет розовый блик.
    function initCardHover() {
        if (window.matchMedia("(hover: none)").matches) return;

        document.querySelectorAll(".card").forEach((card) => {
            // Что делает: считает наклон по положению курсора внутри карточки.
            card.addEventListener("mousemove", (e) => {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width - 0.5;
                const y = (e.clientY - rect.top) / rect.height - 0.5;
                
                // Наклон зависит от положения курсора относительно центра.
                card.style.transform = `translateY(-6px) rotateX(${-y * 6}deg) rotateY(${x * 6}deg)`;
                
                // Блик следует за курсором и подсвечивает карточку.
                card.style.setProperty('--mouse-x', `${(x + 0.5) * 100}%`);
                card.style.setProperty('--mouse-y', `${(y + 0.5) * 100}%`);
            });

            // Что делает: возвращает карточке исходное положение.
            card.addEventListener("mouseleave", () => {
                card.style.transform = "";
            });
        });
    }

    // ===== Сообщения =====
    // Что делает: скрывает уведомление об успехе через несколько секунд.
    function initFlash() {
        document.querySelectorAll(".success").forEach((el) => {
            setTimeout(() => {
                el.style.transition = "opacity 0.6s ease, transform 0.6s ease";
                el.style.opacity = "0";
                el.style.transform = "translateY(-10px)";
                setTimeout(() => el.remove(), 700);
            }, 4000);
        });
    }

    // ===== Плеер (имитация, видео не воспроизводится) =====
    // Что делает: таймер, перемотка, звук, настройки и полноэкранный режим.
    function initFakePlayer() {
        const player = document.querySelector(".player");
        if (!player) return;

        const duration = Number(player.dataset.duration) || 0;
        const currentEl = player.querySelector(".player-current");
        const progressEl = player.querySelector(".player-progress");
        const timeline = player.querySelector(".player-timeline");
        const playIcon = player.querySelector("[data-src-play]");
        const volumeIcon = player.querySelector("[data-src-volume]");
        const fullscreenIcon = player.querySelector("[data-src-expand]");
        const settingsWrap = player.querySelector(".player-settings-wrap");
        const volumeInput = player.querySelector(".player-volume");
        let current = 0;
        let timer = null;

        // Что делает: переводит секунды в вид ЧЧ:ММ:СС.
        function formatTime(seconds) {
            // Часы, минуты и секунды считаем остатками от деления.
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            return [h, m, s].map((n) => String(n).padStart(2, "0")).join(":");
        }

        // Что делает: обновляет текст времени и ширину полосы прогресса.
        function render() {
            if (currentEl) currentEl.textContent = formatTime(current);
            if (progressEl) {
                progressEl.style.width = (duration ? (current / duration) * 100 : 0) + "%";
            }
        }

        // Что делает: останавливает счётчик времени.
        function stopTimer() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        // Что делает: включает или выключает воспроизведение и меняет иконку.
        function setPlaying(playing) {
            player.classList.toggle("is-playing", playing);
            if (playIcon) {
                playIcon.setAttribute("src", playing ? playIcon.dataset.srcPause : playIcon.dataset.srcPlay);
            }
            stopTimer();

            if (playing) {
                // Каждую секунду прибавляем секунду; в конце начинаем сначала.
                timer = setInterval(() => {
                    current += 1;
                    if (current >= duration) {
                        current = 0;
                        setPlaying(false);
                    }
                    render();
                }, 1000);
            }
        }

        // Что делает: кнопки воспроизведения переключают состояние плеера.
        player.querySelectorAll(".player-big-play, .player-play").forEach((btn) => {
            btn.addEventListener("click", () => {
                setPlaying(!player.classList.contains("is-playing"));
            });
        });

        const screen = player.querySelector(".player-screen");
        if (screen) {
            // Что делает: клик по экрану переключает воспроизведение, кроме кнопки на нём.
            screen.addEventListener("click", (e) => {
                if (!e.target.closest(".player-big-play")) {
                    setPlaying(!player.classList.contains("is-playing"));
                }
            });
        }

        if (timeline) {
            // Что делает: перематывает на место клика по шкале.
            timeline.addEventListener("click", (e) => {
                const rect = timeline.getBoundingClientRect();
                // Доля шкалы от нуля до одного умножается на длительность.
                const part = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                current = Math.round(part * duration);
                render();
            });
        }

        // Что делает: выключает и включает звук, меняя иконку.
        function setMuted(muted) {
            player.classList.toggle("is-muted", muted);
            if (volumeIcon) {
                volumeIcon.setAttribute("src", muted ? volumeIcon.dataset.srcMute : volumeIcon.dataset.srcVolume);
            }
        }

        const muteBtn = player.querySelector('[data-action="mute"]');
        if (muteBtn) {
            // Что делает: переключает звук по кнопке.
            muteBtn.addEventListener("click", () => setMuted(!player.classList.contains("is-muted")));
        }

        if (volumeInput) {
            // Что делает: при нулевой громкости убирает звук.
            volumeInput.addEventListener("input", () => {
                setMuted(Number(volumeInput.value) === 0);
            });
        }

        const settingsBtn = player.querySelector('[data-action="settings"]');
        if (settingsBtn) {
            // Что делает: открывает и закрывает меню настроек.
            settingsBtn.addEventListener("click", (e) => {
                // Останавливаем всплытие, иначе клик сразу закроет меню.
                e.stopPropagation();
                if (settingsWrap) settingsWrap.classList.toggle("open");
            });
        }

        // Что делает: закрывает меню настроек кликом вне его.
        document.addEventListener("click", (e) => {
            if (settingsWrap && !settingsWrap.contains(e.target)) {
                settingsWrap.classList.remove("open");
            }
        });

        const fullscreenBtn = player.querySelector('[data-action="fullscreen"]');
        if (fullscreenBtn) {
            // Что делает: включает и выключает полноэкранный режим.
            fullscreenBtn.addEventListener("click", () => {
                if (document.fullscreenElement) {
                    if (document.exitFullscreen) document.exitFullscreen();
                } else if (player.requestFullscreen) {
                    player.requestFullscreen();
                }
            });
        }

        // Что делает: меняет иконку в зависимости от режима экрана.
        document.addEventListener("fullscreenchange", () => {
            if (fullscreenIcon) {
                fullscreenIcon.setAttribute(
                    "src",
                    document.fullscreenElement ? fullscreenIcon.dataset.srcCompress : fullscreenIcon.dataset.srcExpand
                );
            }
        });

        render();
    }

    // ===== Подписка: способы оплаты =====
    // Что делает: показывает подсказку и выделяет выбранный способ оплаты.
    function initPaymentMethods() {
        const form = document.querySelector(".payment-form");
        if (!form) return;

        const note = form.querySelector("#paymentNote");
        const hints = {
            qr: "Отсканируйте QR-код в приложении банка. Это учебная имитация, реальное списание не производится.",
            wallet: "Подтвердите платеж в приложении кошелька. Это учебная имитация, реальное списание не производится.",
            card: "Введите данные карты на экране банка. Это учебная имитация, реальное списание не производится.",
        };
        const radios = form.querySelectorAll('input[name="payment_method"]');

        // Что делает: выделяет выбранный вариант и обновляет текст подсказки.
        function update() {
            radios.forEach((radio) => {
                const option = radio.closest(".method-option");
                if (option) option.classList.toggle("selected", radio.checked);
            });

            const checked = form.querySelector('input[name="payment_method"]:checked');
            if (note && checked && hints[checked.value]) {
                note.textContent = hints[checked.value];
            }
        }

        // Что делает: реагирует на смену способа оплаты.
        radios.forEach((radio) => radio.addEventListener("change", update));
        update();
    }

    // ===== Админ-панель: поиск по таблице =====
    // Что делает: скрывает строки каталога или аккаунтов, где нет искомого текста.
    function initAdminSearch() {
        const input = document.getElementById("adminSearch");
        const table = document.querySelector(".admin-table");
        if (!input || !table) return;

        const found = document.getElementById("adminFound");
        const empty = document.getElementById("adminEmpty");
        const rows = [...table.querySelectorAll("tr")].filter((row) => row.querySelector("td"));
        const forms = found && found.dataset.forms
            ? found.dataset.forms.split(",")
            : ["материал", "материала", "материалов"];

        // Что делает: выбирает форму слова под число: 1 материал, 2 материала, 5 материалов.
        function plural(count) {
            if (count % 10 === 1 && count % 100 !== 11) return forms[0];
            if (count % 10 >= 2 && count % 10 <= 4 && (count % 100 < 12 || count % 100 > 14)) return forms[1];
            return forms[2];
        }

        // Что делает: обновляет список и подпись с количеством найденных материалов.
        function apply() {
            const query = input.value.trim().toLocaleLowerCase("ru-RU");
            let visible = 0;

            rows.forEach((row) => {
                const text = row.textContent.toLocaleLowerCase("ru-RU");
                const show = query === "" || text.includes(query);
                row.hidden = !show;
                if (show) visible++;
            });

            if (found) found.textContent = visible + " " + plural(visible);
            if (empty) empty.hidden = visible !== 0;
        }

        input.addEventListener("input", apply);
        apply();
    }

    // ===== Трейлер: локальное окно =====
    // Что делает: открывает демонстрационный блок по кнопке «Трейлер».
    function initTrailerModal() {
        const triggers = document.querySelectorAll(".trailer-trigger");
        const modal = document.getElementById("trailerModal");
        if (!triggers.length || !modal) return;

        const title = modal.querySelector("#trailerModalTitle");
        const poster = modal.querySelector(".trailer-modal-poster");

        // Что делает: подставляет название и постер выбранного фильма или сериала.
        function open(trigger) {
            if (title) title.textContent = "Трейлер: " + (trigger.dataset.title || "");
            if (poster && trigger.dataset.poster) {
                poster.setAttribute("src", trigger.dataset.poster);
            }
            modal.hidden = false;
            document.body.classList.add("trailer-modal-open");
        }

        // Что делает: закрывает окно и возвращает прокрутку страницы.
        function close() {
            modal.hidden = true;
            document.body.classList.remove("trailer-modal-open");
        }

        triggers.forEach((trigger) => {
            trigger.addEventListener("click", () => open(trigger));
        });

        modal.querySelectorAll("[data-trailer-close]").forEach((el) => {
            el.addEventListener("click", close);
        });

        // Что делает: закрывает окно клавишей Escape.
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && !modal.hidden) close();
        });
    }

    // ===== Инициализация =====
    // Что делает: запускает все модули после загрузки страницы.
    function init() {
        initReveal();
        initScrollTop();
        initHeaderScroll();
        initBurger();
        initPulse();
        initParallax();
        initCardHover();
        initFlash();
        initFakePlayer();
        initTrailerModal();
        initPaymentMethods();
        initAdminSearch();
    }

    // Ждём готовность страницы, чтобы не искать элементы до их появления.
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();