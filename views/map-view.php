<?php
$stmtSims = $pdo->query("SELECT * FROM sims ORDER BY status ASC, sim_id ASC");
$simsList = $stmtSims->fetchAll();

$stmtActive = $pdo->prepare("SELECT * FROM rents WHERE user_id = :uid AND status = 'active' LIMIT 1");
$stmtActive->execute([':uid' => $_SESSION['user']['user_id']]);
$hasActiveRent = (bool) $stmtActive->fetch();

// Вычисляем границы координат из БД, чтобы расставить маркеры по карте
$lats = array_column($simsList, 'lat');
$lngs = array_column($simsList, 'lng');
$minLat = !empty($lats) ? min($lats) - 0.002 : 55.750;
$maxLat = !empty($lats) ? max($lats) + 0.002 : 55.760;
$minLng = !empty($lngs) ? min($lngs) - 0.003 : 37.610;
$maxLng = !empty($lngs) ? max($lngs) + 0.003 : 37.625;
?>

<section class="map-view">
    <h2 class="map-view__title">Карта и доступные средства индивидуальной мобильности (СИМ)</h2>

    <!-- Автономная интерактивная карта района -->
    <div class="local-map">
        <!-- Фоновая схема улиц, кварталов и реки (SVG) -->
        <svg class="local-map__bg" viewBox="0 0 1000 380" preserveAspectRatio="none">
            <defs>
                <pattern id="city-grid" width="50" height="50" patternUnits="userSpaceOnUse">
                    <path d="M 50 0 L 0 0 0 50" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="1000" height="380" fill="url(#city-grid)" />
            <!-- Река -->
            <path d="M -50 290 Q 300 220 550 310 T 1050 270" fill="none" stroke="rgba(56, 189, 248, 0.14)" stroke-width="34" stroke-linecap="round"/>
            <!-- Главные магистрали и улицы -->
            <path d="M 120 -20 L 280 400" stroke="rgba(255,255,255,0.07)" stroke-width="12"/>
            <path d="M 680 -20 L 520 400" stroke="rgba(255,255,255,0.07)" stroke-width="14"/>
            <path d="M -20 110 L 1020 165" stroke="rgba(255,255,255,0.08)" stroke-width="16"/>
            <path d="M -20 210 L 1020 190" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
            <circle cx="480" cy="145" r="55" fill="none" stroke="rgba(74, 222, 128, 0.15)" stroke-width="10"/>
            <!-- Подписи районов -->
            <text x="45" y="45" fill="rgba(255,255,255,0.22)" font-size="12" font-family="sans-serif" font-weight="bold">ЦЕНТРАЛЬНЫЙ ОКРУГ • ЗОНА ПОКРЫТИЯ</text>
            <text x="452" y="149" fill="rgba(74, 222, 128, 0.45)" font-size="11" font-family="sans-serif" font-weight="bold">ЦЕНТР</text>
            <text x="740" y="315" fill="rgba(56, 189, 248, 0.35)" font-size="11" font-family="sans-serif">НАБЕРЕЖНАЯ</text>
        </svg>

        <!-- Легенда карты -->
        <div class="local-map__legend">
            <span><i style="background:#4ade80;"></i> Свободен</span>
            <span><i style="background:#facc15;"></i> В аренде</span>
            <span><i style="background:#f87171;"></i> На ТО</span>
        </div>

        <!-- Маркеры самокатов по координатам из БД -->
        <?php foreach ($simsList as $index => $sim): ?>
            <?php
                // Переводим координаты в проценты (от 12% до 86%)
                $latSpan = max(0.0001, $maxLat - $minLat);
                $lngSpan = max(0.0001, $maxLng - $minLng);
                $topPct  = 84 - ((($sim['lat'] - $minLat) / $latSpan) * 66);
                $leftPct = 12 + ((($sim['lng'] - $minLng) / $lngSpan) * 74);

                // Если у нескольких СИМ одинаковые координаты, слегка раздвигаем их
                $topPct  = max(14, min(84, $topPct + (($index % 3) - 1) * 6));
                $leftPct = max(10, min(88, $leftPct + (($index % 4) - 1.5) * 7));

                $pinClass = 'local-pin--available';
                $pinStatus = 'Доступен';
                if ($sim['status'] === 'active_rent') {
                    $pinClass = 'local-pin--rent';
                    $pinStatus = 'В аренде';
                } elseif ($sim['status'] === 'maintenance') {
                    $pinClass = 'local-pin--maint';
                    $pinStatus = 'На ТО';
                }
            ?>
            <div class="local-pin <?php echo $pinClass; ?>" style="top: <?php echo round($topPct, 1); ?>%; left: <?php echo round($leftPct, 1); ?>%;" tabindex="0">
                <div class="local-pin__dot"></div>
                <span class="local-pin__label">#<?php echo (int)$sim['sim_id']; ?></span>

                <!-- Всплывающая карточка при наведении или клике -->
                <div class="local-pin__popup">
                    <div class="local-pin__popup-head"><?php echo htmlspecialchars($sim['type'], ENT_QUOTES, 'UTF-8'); ?> • <?php echo $pinStatus; ?></div>
                    <div class="local-pin__popup-title"><?php echo htmlspecialchars($sim['model_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="local-pin__popup-row">Тариф: <strong><?php echo number_format((float)$sim['price_per_min'], 2, '.', ''); ?> ₽/мин</strong></div>
                    <div class="local-pin__popup-row">Заряд: <strong><?php echo (int)$sim['charge_percent']; ?>%</strong></div>

                    <?php if ($sim['status'] === 'available' && !$hasActiveRent): ?>
                        <form action="api/rent.php" method="POST" style="margin-top: 8px;">
                            <input type="hidden" name="action" value="start">
                            <input type="hidden" name="sim_id" value="<?php echo (int)$sim['sim_id']; ?>">
                            <button type="submit" class="local-pin__popup-btn">Арендовать</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Сетка карточек СИМ -->
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
                <article class="sim-card<?php echo $cardModifier; ?>">
                    <div class="sim-card__header">
                        <span class="sim-card__type"><?php echo htmlspecialchars($sim['type'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="sim-card__status"><?php echo $statusText; ?></span>
                    </div>

                    <h3 class="sim-card__model"><?php echo htmlspecialchars($sim['model_name'], ENT_QUOTES, 'UTF-8'); ?></h3>

                    <ul class="sim-card__specs">
                        <li class="sim-card__spec">Тариф: <strong><?php echo number_format((float)$sim['price_per_min'], 2, '.', ''); ?> ₽/мин</strong></li>
                        <li class="sim-card__spec">Заряд батареи: <strong><?php echo (int)$sim['charge_percent']; ?>%</strong></li>
                        <li class="sim-card__spec">Координаты: <?php echo $sim['lat']; ?>, <?php echo $sim['lng']; ?></li>
                    </ul>

                    <?php if ($isAvailable && !$hasActiveRent): ?>
                        <form class="sim-card__form" action="api/rent.php" method="POST">
                            <input type="hidden" name="action" value="start">
                            <input type="hidden" name="sim_id" value="<?php echo (int)$sim['sim_id']; ?>">
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