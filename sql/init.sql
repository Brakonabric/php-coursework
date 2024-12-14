-- Установка кодировки
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Создание таблицы пользователей
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'fan', 'team_member', 'coach', 'admin') DEFAULT 'fan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы новостей
DROP TABLE IF EXISTS news;
CREATE TABLE news (
    id INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path_preview VARCHAR(255) DEFAULT NULL,
    image_path_extra TEXT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы комментариев к новостям
DROP TABLE IF EXISTS news_comments;
CREATE TABLE news_comments (
    id INT NOT NULL AUTO_INCREMENT,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы галереи
DROP TABLE IF EXISTS gallery;
CREATE TABLE gallery (
    id INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы комментариев к фотографиям
DROP TABLE IF EXISTS gallery_comments;
CREATE TABLE gallery_comments (
    id INT NOT NULL AUTO_INCREMENT,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (photo_id) REFERENCES gallery(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы лайков к фотографиям
DROP TABLE IF EXISTS gallery_likes;
CREATE TABLE gallery_likes (
    id INT NOT NULL AUTO_INCREMENT,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_like (photo_id, user_id),
    FOREIGN KEY (photo_id) REFERENCES gallery(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы типов событий
DROP TABLE IF EXISTS event_types;
CREATE TABLE event_types (
    id VARCHAR(50) PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    default_title VARCHAR(100) NOT NULL,
    default_description TEXT,
    visible_roles JSON NOT NULL,
    color VARCHAR(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы событий
DROP TABLE IF EXISTS events;
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    event_type VARCHAR(50) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    location VARCHAR(255),
    created_by INT NOT NULL,
    event_visibility JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_type) REFERENCES event_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы игроков
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    position ENUM('GK', 'DF', 'MF', 'FW') NOT NULL,
    height INT NOT NULL,
    weight INT NOT NULL,
    birth_date DATE NOT NULL,
    accuracy INT NOT NULL,
    photo_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_number (number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы голов
CREATE TABLE IF NOT EXISTS player_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    scored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание таблицы штрафов
CREATE TABLE IF NOT EXISTS player_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление тестовых данных для игроков
INSERT INTO players (number, name, position, height, weight, birth_date, accuracy) VALUES
(1, 'Иван Петров', 'GK', 188, 82, '1995-03-15', 85),
(7, 'Алексей Смирнов', 'FW', 180, 75, '1998-07-22', 92),
(4, 'Михаил Иванов', 'DF', 185, 78, '1997-11-30', 88),
(10, 'Сергей Козлов', 'MF', 175, 70, '1999-05-18', 90);

SET FOREIGN_KEY_CHECKS = 1;

-- Добавление типов событий
INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) VALUES 
('public_event', 'Publisks pasākums', 'Jauns pasākums', 'Pasākuma apraksts', 
'["guest", "fan", "team_member", "coach", "admin"]', '#a8e6cf'),

('team_training', 'Komandas treniņš', 'Komandas treniņš', 'Kopējais treniņš visiem komandas dalībniekiem', 
'["team_member", "coach", "admin"]', '#ffd3b6'),

('individual_training', 'Individuālais treniņš', 'Individuālais treniņš', 'Individuālais treniņš ar treneri', 
'["team_member", "coach", "admin"]', '#ffaaa5'),

('match', 'Spēle', 'Spēle', 'Komandas spēle', 
'["guest", "fan", "team_member", "coach", "admin"]', '#ff8b94'),

('team_meeting', 'Komandas sapulce', 'Komandas sapulce', 'Sapulce visiem komandas dalībniekiem', 
'["team_member", "coach", "admin"]', '#dcd3ff'),

('coach_meeting', 'Treneru sapulce', 'Treneru sapulce', 'Sapulce treneriem', 
'["coach", "admin"]', '#c8b6ff'),

('medical_checkup', 'Medicīniskā pārbaude', 'Medicīniskā pārbaude', 'Regulārā medicīniskā pārbaude', 
'["team_member", "coach", "admin"]', '#bbd0ff'),

('team_building', 'Komandas saliedēšanas pasākums', 'Komandas pasākums', 'Komandas saliedēšanas aktivitātes', 
'["team_member", "coach", "admin"]', '#b8c0ff'),

('tournament', 'Turnīrs', 'Turnīrs', 'Sporta turnīrs', 
'["guest", "fan", "team_member", "coach", "admin"]', '#ffc6ff');