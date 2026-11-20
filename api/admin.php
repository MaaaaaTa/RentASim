<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Администратор', 'Менеджер'], true)) {
    $_SESSION['flash_error'] = 'Доступ запрещен.';
    header('Location: ../index.php');
    exit;

}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
   if ($action === 'unblock') {
        if ($_SESSION['user']['role'] !== 'Администратор') {
            $_SESSION['flash_error'] = 'Разблокировка доступна только Администратору.';
            header('Location: ../index.php');
            exit;
        }
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0, failed_attempts = 0 WHERE user_id = :uid");
        $stmt->execute([':uid' => $targetUserId]);
        $_SESSION['flash_success'] = "Пользователь #{$targetUserId} разблокирован.";$stmt->execute([':uid' => $targetUserId]);
        $_SESSION['flash_success'] = "Пользователь #{$targetUserId} разблокирован.";

   // Блокировка пользователя
    } elseif ($action === 'block') {
        if ($_SESSION['user']['role'] !== 'Администратор') {
            $_SESSION['flash_error'] = 'Блокировка доступна только Администратору.';
            header('Location: ../index.php');
            exit;
        }
        $targetUserId = (int) ($_POST['user_id'] ?? 0);

        if ($targetUserId === (int) $_SESSION['user']['user_id']) {
            $_SESSION['flash_error'] = 'Нельзя заблокировать самого себя.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1, failed_attempts = 3 WHERE user_id = :uid");
            $stmt->execute([':uid' => $targetUserId]);
            $_SESSION['flash_success'] = "Пользователь #{$targetUserId} заблокирован.";
        }

    // Добавление нового СИМ
    } elseif ($action === 'add_sim') {
        $type        = trim($_POST['type'] ?? '');
        $modelName   = trim($_POST['model_name'] ?? '');
        $pricePerMin = (float) ($_POST['price_per_min'] ?? 0);
        $lat         = (float) ($_POST['lat'] ?? 55.755800);
        $lng         = (float) ($_POST['lng'] ?? 37.617600);

        if ($type !== '' && $modelName !== '' && $pricePerMin > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO sims (type, model_name, price_per_min, charge_percent, lat, lng, status) 
                VALUES (:type, :model, :price, 100, :lat, :lng, 'available')
            ");
            $stmt->execute([
                ':type'  => $type,
                ':model' => $modelName,
                ':price' => $pricePerMin,
                ':lat'   => $lat,
                ':lng'   => $lng,
            ]);
            $_SESSION['flash_success'] = "Транспорт «{$type} {$modelName}» добавлен.";
        }

    // Ручное редактирование СИМ (модель, тариф, статус)
    } elseif ($action === 'edit_sim') {
        $simId       = (int) ($_POST['sim_id'] ?? 0);
        $modelName   = trim($_POST['model_name'] ?? '');
        $pricePerMin = (float) ($_POST['price_per_min'] ?? 0);
        $status      = $_POST['status'] ?? 'available';

        if ($modelName !== '' && $pricePerMin > 0 && in_array($status, ['available', 'maintenance'], true)) {
            $stmt = $pdo->prepare("
                UPDATE sims 
                SET model_name = :model, price_per_min = :price, status = :status 
                WHERE sim_id = :sid AND status != 'active_rent'
            ");
            $stmt->execute([
                ':model'  => $modelName,
                ':price'  => $pricePerMin,
                ':status' => $status,
                ':sid'    => $simId,
            ]);
            $_SESSION['flash_success'] = "Данные СИМ #{$simId} обновлены.";
        } else {
            $_SESSION['flash_error'] = "Не удалось обновить СИМ #{$simId} (проверьте поля или активную аренду).";
        }

    // Удаление СИМ из списка
    } elseif ($action === 'delete_sim') {
        $simId = (int) ($_POST['sim_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM sims WHERE sim_id = :sid AND status != 'active_rent'");
        $stmt->execute([':sid' => $simId]);
        $_SESSION['flash_success'] = "СИМ #{$simId} удален.";
    }
} catch (PDOException $e) {
    error_log("Ошибка в api/admin.php: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Ошибка базы данных (возможно, у СИМ есть история поездок).';
}

header('Location: ../index.php');
exit;