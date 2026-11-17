<?php
$userId = (int) $_SESSION['user']['user_id'];

// Обновляем актуальный баланс из БД
$stmtUser = $pdo->prepare("SELECT balance FROM users WHERE user_id = :uid LIMIT 1");
$stmtUser->execute([':uid' => $userId]);
$currentBalance = (float) $stmtUser->fetchColumn();
$_SESSION['user']['balance'] = $currentBalance;

// Ищем активную аренду пользователя
$stmtActiveRent = $pdo->prepare("
    SELECT r.*, s.model_name, s.type, s.price_per_min 
    FROM rents r 
    JOIN sims s ON r.sim_id = s.sim_id 
    WHERE r.user_id = :uid AND r.status = 'active' 
    LIMIT 1
");
$stmtActiveRent->execute([':uid' => $userId]);
$activeRent = $stmtActiveRent->fetch();

// Получаем историю завершенных поездок
$stmtHistory = $pdo->prepare("
    SELECT r.*, s.model_name, s.type 
    FROM rents r 
    JOIN sims s ON r.sim_id = s.sim_id 
    WHERE r.user_id = :uid AND r.status = 'completed' 
    ORDER BY r.rent_id DESC 
    LIMIT 10
");
$stmtHistory->execute([':uid' => $userId]);
$rentHistory = $stmtHistory->fetchAll();
?>

<div class="user-panel">
    <section class="user-panel__wallet panel">
        <h2 class="panel__title">Кошелек клиента</h2>
        <p class="user-panel__balance">Текущий баланс: <strong><?= number_format($currentBalance, 2, '.', ' ') ?> ₽</strong></p>
    </section>

    <?php if ($activeRent): ?>
        <section class="active-rent panel">
            <h2 class="panel__title">Активная поездка</h2>
            <p class="active-rent__info">
                Транспорт: <strong><?= htmlspecialchars($activeRent['type'] . ' ' . $activeRent['model_name'], ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
            <p class="active-rent__info">
                Начало поездки: <?= htmlspecialchars($activeRent['start_time'], ENT_QUOTES, 'UTF-8') ?> 
                (тариф <?= number_format((float)$activeRent['price_per_min'], 2, '.', '') ?> ₽/мин)
            </p>

            <form class="active-rent__form" action="api/rent.php" method="POST">
                <input type="hidden" name="action" value="finish">
                <input type="hidden" name="rent_id" value="<?= (int)$activeRent['rent_id'] ?>">
                <button class="active-rent__btn" type="submit">Завершить аренду и оплатить</button>
            </form>
        </section>
    <?php endif; ?>

    <?php require_once __DIR__ . '/map-view.php'; ?>

    <section class="rent-history panel">
        <h2 class="panel__title">История поездок</h2>
        <?php if (empty($rentHistory)): ?>
            <p class="rent-history__empty">У вас пока нет завершенных поездок.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Транспорт</th>
                        <th>Начало</th>
                        <th>Окончание</th>
                        <th>Стоимость</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentHistory as $item): ?>
                        <tr>
                            <td>#<?= (int)$item['rent_id'] ?></td>
                            <td><?= htmlspecialchars($item['type'] . ' ' . $item['model_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($item['start_time'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($item['end_time'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= number_format((float)$item['total_cost'], 2, '.', ' ') ?> ₽</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>