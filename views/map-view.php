<?php
// Получаем все СИМ из базы данных
$stmtSims = $pdo->query("SELECT * FROM sims ORDER BY status ASC, sim_id ASC");
$simsList = $stmtSims->fetchAll();

// Проверяем, есть ли у текущего клиента уже активная поездка
$stmtActive = $pdo->prepare("SELECT * FROM rents WHERE user_id = :uid AND status = 'active' LIMIT 1");
$stmtActive->execute([':uid' => $_SESSION['user']['user_id']]);
$hasActiveRent = (bool) $stmtActive->fetch();
?>

<section class="map-view">
    <h2 class="map-view__title">Доступные средства индивидуальной мобильности (СИМ)</h2>

    <?php if (empty($simsList)): ?>
        <p class="map-view__empty">Транспортных средств пока нет в базе.</p>
    <?php else: ?>
        <div class="map-view__grid">
            <?php foreach ($simsList as $sim): ?>
                <?php
                    $isAvailable = ($sim['status'] === 'available');
                    $statusText = match ($sim['status']) {
                        'available'   => 'Доступен',
                        'active_rent' => 'В аренде',
                        'maintenance' => 'На обслуживании',
                        default       => 'Неизвестно',
                    };
                    $cardModifier = ' sim-card--' . str_replace('_', '-', $sim['status']);
                ?>
                <article class="sim-card<?= $cardModifier ?>">
                    <div class="sim-card__header">
                        <span class="sim-card__type"><?= htmlspecialchars($sim['type'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="sim-card__status"><?= $statusText ?></span>
                    </div>

                    <h3 class="sim-card__model"><?= htmlspecialchars($sim['model_name'], ENT_QUOTES, 'UTF-8') ?></h3>

                    <ul class="sim-card__specs">
                        <li class="sim-card__spec">Тариф: <strong><?= number_format((float)$sim['price_per_min'], 2, '.', '') ?> ₽/мин</strong></li>
                        <li class="sim-card__spec">Заряд батареи: <strong><?= (int)$sim['charge_percent'] ?>%</strong></li>
                        <li class="sim-card__spec">Координаты: <?= $sim['lat'] ?>, <?= $sim['lng'] ?></li>
                    </ul>

                    <?php if ($isAvailable && !$hasActiveRent): ?>
                        <form class="sim-card__form" action="api/rent.php" method="POST">
                            <input type="hidden" name="action" value="start">
                            <input type="hidden" name="sim_id" value="<?= (int)$sim['sim_id'] ?>">
                            <button class="sim-card__btn" type="submit">Начать аренду</button>
                        </form>
                    <?php elseif ($hasActiveRent && $isAvailable): ?>
                        <p class="sim-card__hint">Завершите текущую поездку, чтобы взять новый СИМ</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>