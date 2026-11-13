<?php
// Запуск сессии, если она еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Настройка логирования критических системных сбоев (раздел 8 README)
ini_set('log_errors', '1');
ini_set('display_errors', '0'); // Скрываем технические ошибки от пользователя

// Параметры подключения к БД (измени на свои, если в OpenServer/XAMPP другой пароль)
define('DB_HOST', 'localhost');
define('DB_NAME', 'sim_rent_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // В OpenServer или XAMPP по умолчанию обычно пустой пароль
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Выбрасывать исключения при ошибках SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Возвращать ассоциативные массивы
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Использовать реальные подготовленные запросы
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Логируем реальную ошибку в системный лог сервера
    error_log("Ошибка подключения к БД: " . $e->getMessage());
    
    // Выводим пользователю понятное сообщение без технических деталей
    die("Системная ошибка: не удалось подключиться к базе данных. Попробуйте позже.");
}