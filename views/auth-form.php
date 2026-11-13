<?php
$authError   = $_SESSION['auth_error'] ?? '';
$authBlocked = !empty($_SESSION['auth_blocked']);
$oldEmail    = $_SESSION['old_email'] ?? '';

// Очищаем одноразовые сообщения после чтения
unset($_SESSION['auth_error'], $_SESSION['auth_blocked'], $_SESSION['old_email']);

// Формируем БЭМ-модификаторы формы и сообщения об ошибке
$formModifier  = $authBlocked ? ' auth-form--blocked' : '';
$errorModifier = ($authError !== '') ? ' auth-form__error-message--visible' : '';
?>

<section class="auth-section">
    <form class="auth-form<?= $formModifier ?>" action="api/login.php" method="POST">
        <input type="hidden" name="action" value="login">

        <h1 class="auth-form__title">Автоматизированный прокат СИМ</h1>
        <p class="auth-form__subtitle">Вход в личный кабинет</p>

        <div class="auth-form__error-message<?= $errorModifier ?>">
            <?= htmlspecialchars($authError, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label" for="email">Электронная почта (Email)</label>
            <input 
                class="auth-form__input" 
                type="email" 
                id="email" 
                name="email" 
                value="<?= $oldEmail ?>" 
                placeholder="Например: client@sim.ru" 
                required
            >
        </div>

        <div class="auth-form__group">
            <label class="auth-form__label" for="password">Пароль</label>
            <input 
                class="auth-form__input" 
                type="password" 
                id="password" 
                name="password" 
                placeholder="Введите пароль" 
                required
            >
        </div>

        <button class="auth-form__button" type="submit">Войти в систему</button>

        <div class="auth-form__hint">
            <p class="auth-form__hint-title">Тестовые аккаунты (пароль: <strong>123456</strong>):</p>
            <ul class="auth-form__hint-list">
                <li>Клиент: <code>client@sim.ru</code></li>
                <li>Менеджер: <code>manager@sim.ru</code></li>
                <li>Администратор: <code>admin@sim.ru</code></li>
                <li>Заблокированный: <code>blocked@sim.ru</code></li>
            </ul>
        </div>
    </form>
</section>