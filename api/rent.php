<?php
require_once __DIR__ . '/../config.php';

// Проверяем, что пользователь авторизован и это Клиент
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Клиент') {
    $_SESSION['flash_error'] = 'Доступ запрещен. Авторизуйтесь как клиент.';
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$userId = (int) $_SESSION['user']['user_id'];

try {
    if ($action === 'start') {
        $simId = (int) ($_POST['sim_id'] ?? 0);

        $pdo->beginTransaction();

        // Проверяем баланс пользователя (должен быть больше 0)
        $stmtUser = $pdo->prepare("SELECT balance FROM users WHERE user_id = :uid FOR UPDATE");
        $stmtUser->execute([':uid' => $userId]);
        $balance = (float) $stmtUser->fetchColumn();

        if ($balance <= 0) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Недостаточно средств на балансе для начала поездки.';
            header('Location: ../index.php');
            exit;
        }

        // Проверяем, что СИМ существует и свободен
        $stmtSim = $pdo->prepare("SELECT * FROM sims WHERE sim_id = :sid AND status = 'available' FOR UPDATE");
        $stmtSim->execute([':sid' => $simId]);
        $sim = $stmtSim->fetch();

        if (!$sim) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Выбранный СИМ уже занят или недоступен.';
            header('Location: ../index.php');
            exit;
        }

        // Создаем запись об аренде
        $stmtRent = $pdo->prepare("
            INSERT INTO rents (user_id, sim_id, start_lat, start_lng, status) 
            VALUES (:uid, :sid, :lat, :lng, 'active')
        ");
        $stmtRent->execute([
            ':uid' => $userId,
            ':sid' => $simId,
            ':lat' => $sim['lat'],
            ':lng' => $sim['lng'],
        ]);

        // Меняем статус СИМ на active_rent
        $stmtUpdateSim = $pdo->prepare("UPDATE sims SET status = 'active_rent' WHERE sim_id = :sid");
        $stmtUpdateSim->execute([':sid' => $simId]);

        $pdo->commit();
        $_SESSION['flash_success'] = 'Аренда успешно начата! Приятной поездки.';

    } elseif ($action === 'finish') {
        $rentId = (int) ($_POST['rent_id'] ?? 0);

        $pdo->beginTransaction();

        // Ищем активную аренду этого пользователя
        $stmtRent = $pdo->prepare("
            SELECT r.*, s.price_per_min, s.lat, s.lng 
            FROM rents r 
            JOIN sims s ON r.sim_id = s.sim_id 
            WHERE r.rent_id = :rid AND r.user_id = :uid AND r.status = 'active' 
            FOR UPDATE
        ");
        $stmtRent->execute([':rid' => $rentId, ':uid' => $userId]);
        $rent = $stmtRent->fetch();

        if (!$rent) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Активная аренда не найдена.';
            header('Location: ../index.php');
            exit;
        }

        // Считаем длительность в минутах (минимум 1 минута)
        $startTime = strtotime($rent['start_time']);
        $nowTime   = time();
        $minutes   = max(1, (int) ceil(($nowTime - $startTime) / 60));
        $totalCost = round($minutes * (float) $rent['price_per_min'], 2);

        // Завершаем аренду в таблице rents
        $stmtFinishRent = $pdo->prepare("
            UPDATE rents 
            SET end_time = NOW(), end_lat = :lat, end_lng = :lng, total_cost = :cost, status = 'completed' 
            WHERE rent_id = :rid
        ");
        $stmtFinishRent->execute([
            ':lat'  => $rent['lat'],
            ':lng'  => $rent['lng'],
            ':cost' => $totalCost,
            ':rid'  => $rentId,
        ]);

        // Возвращаем СИМ в статус available и немного уменьшаем заряд
        $stmtFreeSim = $pdo->prepare("
            UPDATE sims 
            SET status = 'available', charge_percent = GREATEST(0, charge_percent - 2) 
            WHERE sim_id = :sid
        ");
        $stmtFreeSim->execute([':sid' => $rent['sim_id']]);

        // Списываем средства с баланса пользователя
        $stmtPay = $pdo->prepare("UPDATE users SET balance = balance - :cost WHERE user_id = :uid");
        $stmtPay->execute([
            ':cost' => $totalCost,
            ':uid'  => $userId,
        ]);

        $pdo->commit();
        $_SESSION['flash_success'] = "Поездка завершена! Длительность: {$minutes} мин. Списано: {$totalCost} ₽.";
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Ошибка аренды: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Произошла системная ошибка при обработке аренды.';
}

header('Location: ../index.php');
exit;