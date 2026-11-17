<?php
$stmtUsers = $pdo->query("SELECT user_id, full_name, email, role, failed_attempts, is_blocked FROM users ORDER BY user_id ASC");
$usersList = $stmtUsers->fetchAll();

$stmtSims = $pdo->query("SELECT * FROM sims ORDER BY sim_id ASC");
$simsList = $stmtSims->fetchAll();
?>

<div class="admin-panel">
    <?php if ($_SESSION['user']['role'] === 'Администратор'): ?>
    <!-- 1. Таблица пользователей -->
    <section class="panel">
        <h2 class="panel__title">Управление учетными записями пользователей</h2>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Попытки входа</th>
                    <th>Статус</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usersList as $u): ?>
                    <tr>
                        <td>#<?php echo (int)$u['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$u['failed_attempts']; ?> / 3</td>
                        <td>
                            <?php if ((int)$u['is_blocked'] === 1): ?>
                                Заблокирован
                            <?php else: ?>
                                Активен
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$u['is_blocked'] === 1): ?>
                                <form action="api/admin.php" method="POST">
                                    <input type="hidden" name="action" value="unblock">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                    <button class="btn-small" type="submit">Разблокировать</button>
                                </form>
                            <?php elseif ((int)$u['user_id'] !== (int)$_SESSION['user']['user_id']): ?>
                                <form action="api/admin.php" method="POST">
                                    <input type="hidden" name="action" value="block">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                    <button class="btn-small" style="background-color: #dc3545;" type="submit">Заблокировать</button>
                                </form>
                            <?php else: ?>
                                -
                        
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>
    <!-- 2. Редактируемый список самокатов и тарифов -->
    <section class="panel">
        <h2 class="panel__title">Список самокатов и редактирование тарифов</h2>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Тип</th>
                    <th>Модель</th>
                    <th>Тариф (₽/мин)</th>
                    <th>Статус</th>
                    <th>Сохранить</th>
                    <th>Удалить</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($simsList as $sim): ?>
                    <tr>
                        <td>#<?php echo (int)$sim['sim_id']; ?></td>
                        <td><?php echo htmlspecialchars($sim['type'], ENT_QUOTES, 'UTF-8'); ?></td>

                        <?php if ($sim['status'] === 'active_rent'): ?>
                            <td><?php echo htmlspecialchars($sim['model_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo number_format((float)$sim['price_per_min'], 2, '.', ''); ?></td>
                            <td>В аренде</td>
                            <td>—</td>
                            <td>—</td>
                        <?php else: ?>
                            <form action="api/admin.php" method="POST">
                                <input type="hidden" name="action" value="edit_sim">
                                <input type="hidden" name="sim_id" value="<?php echo (int)$sim['sim_id']; ?>">
<td>
                                    <input type="text" name="model_name" value="<?php echo htmlspecialchars($sim['model_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="price_per_min" step="0.50" min="1" value="<?php echo number_format((float)$sim['price_per_min'], 2, '.', ''); ?>" required>
                                </td>
                                <td>
                                    <select name="status">
                                        <option value="available" <?php echo $sim['status'] === 'available' ? 'selected' : ''; ?>>Доступен</option>
                                        <option value="maintenance" <?php echo $sim['status'] === 'maintenance' ? 'selected' : ''; ?>>На ТО</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn-small" type="submit">Сохранить</button>
                                </td>
                            </form>
                            <td>
                                <form action="api/admin.php" method="POST" onsubmit="return confirm('Удалить этот транспорт?');">
                                    <input type="hidden" name="action" value="delete_sim">
                                    <input type="hidden" name="sim_id" value="<?php echo (int)$sim['sim_id']; ?>">
                                    <button class="btn-small" type="submit">Удалить</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 3. Добавление нового СИМ -->
    <section class="panel">
        <h2 class="panel__title">Добавить новый СИМ в автопарк</h2>

        <form action="api/admin.php" method="POST">
            <input type="hidden" name="action" value="add_sim">

            <div>
                <label>Тип транспорта</label>
                <select name="type">
                    <option value="Самокат">Самокат</option>
                    <option value="Велосипед">Велосипед</option>
                    <option value="Гироскутер">Гироскутер</option>
                </select>
            </div>

            <div>
                <label>Модель и номер</label>
                <input type="text" name="model_name" placeholder="Ninebot Max #104" required>
            </div>

            <div>
                <label>Тариф (₽/мин)</label>
                <input type="number" name="price_per_min" value="7.50" step="0.50" min="1" required>
            </div>

            <div>
                <label>Широта (lat)</label>
                <input type="text" name="lat" value="55.755800" required>
            </div>

            <div>
                <label>Долгота (lng)</label>
                <input type="text" name="lng" value="37.617600" required>
            </div>

            <button class="btn-small" type="submit">Добавить СИМ</button>
        </form>
    </section>
</div>