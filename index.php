<?php
require_once __DIR__ . '/config.php';

$currentUser = $_SESSION['user'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автоматизированный прокат СИМ</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth-form.css">
    <link rel="stylesheet" href="assets/css/map-view.css">
    <link rel="stylesheet" href="assets/css/user-panel.css">
</head>
<body class="page">

    <?php if ($currentUser): ?>
        <header class="header">
            <div class="header__container">
                <div class="header__brand">
                    <span class="header__logo">СИМ Прокат</span>
                    <span class="header__role-badge"><?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="header__user-info">
                    <span class="header__user-name"><?= htmlspecialchars($currentUser['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <a class="header__logout-btn" href="api/login.php?logout=1">Выйти</a>
                </div>
            </div>
        </header>
    <?php endif; ?>

    <main class="main">
        <?php if ($flashSuccess !== ''): ?>
            <div class="alert alert--success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($flashError !== ''): ?>
            <div class="alert alert--error"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php
        if (!$currentUser) {
            // Неавторизованный пользователь -> форма входа
            require_once __DIR__ . '/views/auth-form.php';
        } else {
            // Роутинг по ролям из README.md
            switch ($currentUser['role']) {
                case 'Клиент':
                    require_once __DIR__ . '/views/client-panel.php';
                    break;
                case 'Менеджер':
                    // Подключаем панель управления (сделаем следующим шагом)
                    if (file_exists(__DIR__ . '/views/manager-panel.php')) {
                        require_once __DIR__ . '/views/manager-panel.php';
                    } else {
                        echo '<div class="panel"><h2 class="panel__title">Кабинет менеджера</h2><p>Модуль в разработке...</p></div>';
                    }
                    break;
                case 'Администратор':
                    require_once __DIR__ . '/views/admin-panel.php';
                    break;
                default:
                    echo '<div class="alert alert--error">Неизвестная роль пользователя.</div>';
            }
        }
        ?>
    </main>

</body>
</html>