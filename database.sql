CREATE DATABASE IF NOT EXISTS sim_rent_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sim_rent_db;

-- 1. Таблица пользователей (строго по README)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'Клиент',
    balance DECIMAL(10, 2) NOT NULL DEFAULT 500.00, -- Баланс кошелька для оплаты поездок
    failed_attempts TINYINT NOT NULL DEFAULT 0,
    is_blocked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Таблица средств индивидуальной мобильности (СИМ)
CREATE TABLE sims (
    sim_id INT AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(100) NOT NULL,
    type ENUM('Самокат', 'Велосипед') NOT NULL DEFAULT 'Самокат',
    status ENUM('available', 'active_rent', 'maintenance') NOT NULL DEFAULT 'available',
    price_per_min DECIMAL(6, 2) NOT NULL DEFAULT 7.00,
    lat DECIMAL(10, 6) NOT NULL,
    lng DECIMAL(10, 6) NOT NULL,
    charge_percent TINYINT NOT NULL DEFAULT 100
) ENGINE=InnoDB;

-- 3. Таблица поездок (аренды)
CREATE TABLE rents (
    rent_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sim_id INT NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL DEFAULT NULL,
    start_lat DECIMAL(10, 6) NOT NULL,
    start_lng DECIMAL(10, 6) NOT NULL,
    end_lat DECIMAL(10, 6) NULL DEFAULT NULL,
    end_lng DECIMAL(10, 6) NULL DEFAULT NULL,
    total_cost DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('active', 'completed') NOT NULL DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sim_id) REFERENCES sims(sim_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Демо-данные: пользователи (пароль для всех: 123456)
INSERT INTO users (full_name, email, password_hash, role, balance, failed_attempts, is_blocked) VALUES
('Иванов Алексей Петрович', 'client@sim.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Клиент', 1200.00, 0, 0),
('Смирнова Елена Викторовна', 'manager@sim.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Менеджер', 0.00, 0, 0),
('Козлов Дмитрий Сергеевич', 'admin@sim.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор', 0.00, 0, 0),
('Заблокированный Тест', 'blocked@sim.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Клиент', 100.00, 3, 1);

-- Демо-данные: доступные СИМ (координаты центра Москвы для примера)
INSERT INTO sims (model_name, type, status, price_per_min, lat, lng, charge_percent) VALUES
('Ninebot Max G30 #101', 'Самокат', 'available', 7.50, 55.755814, 37.617635, 92),
('Xiaomi Pro 2 #102', 'Самокат', 'available', 6.50, 55.758021, 37.619412, 78),
('Stels Electro #201', 'Велосипед', 'available', 9.00, 55.753930, 37.620795, 100),
('Ninebot Max G30 #103', 'Самокат', 'maintenance', 7.50, 55.751244, 37.618423, 15);