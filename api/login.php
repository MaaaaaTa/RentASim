<?php
require_once __DIR__ . '/../config.php';

/**
 * Авторизация пользователя по email и паролю.
 * Строго соответствует алгоритму из раздела 6 и 7 README.md.
 */
function loginUser(string $email, string $password): array
{
    global $pdo;

    $email = trim($email);

    // 1. Проверка заполнения полей
    if ($email === '' || $password === '') {
        return [
            'success' => false,
            'blocked' => false,
            'message' => 'Заполните обязательные поля: Email и пароль.'
        ];
    }

    try {
        // 2. Поиск пользователя по email
        $stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, role, balance, failed_attempts, is_blocked FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'blocked' => false,
                'message' => 'Неверный email или пароль.'
            ];
        }

        // 3. Проверка статуса блокировки (раздел 6, п. 3)
        if ((int)$user['is_blocked'] === 1) {
            return [
                'success' => false,
                'blocked' => true,
                'message' => 'Учетная запись заблокирована из-за превышения попыток входа. Обратитесь к Администратору.'
            ];
        }

        // 4. Верификация пароля через password_verify()
        if (!password_verify($password, $user['password_hash'])) {
            $attempts = (int)$user['failed_attempts'] + 1;
            $isBlocked = ($attempts >= 3) ? 1 : 0;

            // Обновляем счетчик неудачных попыток и при >= 3 блокируем аккаунт
            $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = :attempts, is_blocked = :blocked WHERE user_id = :id");
            $updateStmt->execute([
                ':attempts' => $attempts,
                ':blocked'  => $isBlocked,
                ':id'       => $user['user_id']
            ]);

            if ($isBlocked === 1) {
                return [
                    'success' => false,
                    'blocked' => true,
                    'message' => 'Превышено количество попыток (3). Ваша учетная запись заблокирована.'
                ];
            }

            $leftAttempts = 3 - $attempts;
            return [
                'success' => false,
                'blocked' => false,
                'message' => "Неверный пароль. Осталось попыток до блокировки: {$leftAttempts}."
            ];
        }

        // 5. Успешный вход: сбрасываем счетчик ошибок и записываем пользователя в сессию
        $resetStmt = $pdo->prepare("UPDATE users SET failed_attempts = 0 WHERE user_id = :id");
        $resetStmt->execute([':id' => $user['user_id']]);

        $_SESSION['user'] = [
            'user_id'   => (int)$user['user_id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
            'balance'   => (float)$user['balance']
        ];

        return [
            'success' => true,
            'blocked' => false,
            'message' => ''
        ];

    } catch (PDOException $e) {
        error_log("Ошибка при авторизации пользователя ({$email}): " . $e->getMessage());
        return [
            'success' => false,
            'blocked' => false,
            'message' => 'Внутренняя ошибка сервера. Попробуйте войти позже.'
        ];
    }
}

// Обработка выхода из системы
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// Обработка POST-запроса от формы авторизации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = loginUser($email, $password);

    if ($result['success']) {
        unset($_SESSION['auth_error'], $_SESSION['auth_blocked'],$_SESSION['old_email']);
    } else {
        $_SESSION['auth_error']   = $result['message'];
        $_SESSION['auth_blocked'] = $result['blocked'];
        $_SESSION['old_email']    = htmlspecialchars(trim($email), ENT_QUOTES, 'UTF-8');
    }

    header('Location: ../index.php');
    exit;
}