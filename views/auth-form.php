<!-- Основной блок формы. Модификатор --blocked добавится бэкендом при >=3 ошибках -->
<div class="auth-form">
    <h2 class="auth-form__title">Вход в систему проката СИМ</h2>

    <!-- Контейнер формы отправляет данные обработчику в api/ -->
    <form class="auth-form__container" action="api/login.php" method="POST">
        
        <div class="auth-form__field">
            <label class="auth-form__label" for="email">Email</label>
            <input class="auth-form__input" type="email" id="email" name="email" placeholder="example@mail.com" required>
        </div>

        <div class="auth-form__field">
            <label class="auth-form__label" for="password">Пароль</label>
            <input class="auth-form__input" type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <!-- Контейнер для капчи-пазла -->
        <div class="auth-form__captcha-block">
            <p class="auth-form__captcha-text">Соберите пазл, чтобы подтвердить, что вы не робот:</p>
            
            <!-- Интерактивная зона пазла -->
            <div class="auth-form__puzzle-zone">
                <!-- Бэкенд выведет сюда кусочки картинок из assets/img/ в случайном порядке -->
                <div class="auth-form__puzzle-piece" data-index="1" style="background-image: url('assets/img/captcha_1.jpg');"></div>
                <div class="auth-form__puzzle-piece" data-index="2" style="background-image: url('assets/img/captcha_2.jpg');"></div>
                <div class="auth-form__puzzle-piece" data-index="3" style="background-image: url('assets/img/captcha_3.jpg');"></div>
            </div>
            
            <!-- Скрытый инпут, куда запишется порядок собранного пазла для отправки на PHP -->
            <input type="hidden" name="puzzle_order" id="puzzle_order_input" value="">
        </div>

        <button class="auth-form__button" type="submit">Войти</button>
    </form>

    <!-- Элемент сообщения об ошибке. Бэкенд сделает его видимым с помощью модификатора --visible -->
    <div class="auth-form__error-message">
        <!-- Сюда PHP подставит текст: "Ошибка капчи" или "Аккаунт временно заблокирован" -->
        Неверный пароль или ошибка сборки капчи.
    </div>
</div>
