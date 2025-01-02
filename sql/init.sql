SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'fan', 'teamMember', 'coach', 'admin') DEFAULT 'fan',
    can_comment TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    KEY user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS news_comments;
CREATE TABLE news_comments (
    id INT NOT NULL AUTO_INCREMENT,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY news_id (news_id),
    KEY user_id (user_id),
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS gallery;
CREATE TABLE gallery (
    id INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    source_type VARCHAR(50) DEFAULT NULL,
    source_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY source_idx (source_type, source_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS gallery_comments;
CREATE TABLE gallery_comments (
    id INT NOT NULL AUTO_INCREMENT,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY photo_id (photo_id),
    KEY user_id (user_id),
    FOREIGN KEY (photo_id) REFERENCES gallery(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS gallery_likes;
CREATE TABLE gallery_likes (
    id INT NOT NULL AUTO_INCREMENT,
    photo_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_like (photo_id, user_id),
    KEY photo_id (photo_id),
    KEY user_id (user_id),
    FOREIGN KEY (photo_id) REFERENCES gallery(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS event_types;
CREATE TABLE event_types (
    id VARCHAR(50) PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    default_title VARCHAR(100) NOT NULL,
    default_description TEXT,
    visible_roles JSON NOT NULL,
    color VARCHAR(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    KEY event_type (event_type),
    KEY created_by (created_by),
    FOREIGN KEY (event_type) REFERENCES event_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) VALUES 
('publicEvent', 'Publisks pasākums', 'Jauns pasākums', 'Pasākuma apraksts', 
'["guest", "fan", "teamMember", "coach", "admin"]', '#a8e6cf'),

('teamTraining', 'Komandas treniņš', 'Komandas treniņš', 'Kopējais treniņš visiem komandas dalībniekiem', 
'["teamMember", "coach", "admin"]', '#ffd3b6'),

('individualTraining', 'Individuālais treniņš', 'Individuālais treniņš', 'Individuālais treniņš ar treneri', 
'["teamMember", "coach", "admin"]', '#ffaaa5'),

('match', 'Spēle', 'Spēle', 'Komandas spēle', 
'["guest", "fan", "teamMember", "coach", "admin"]', '#ff8b94'),

('teamMeeting', 'Komandas sapulce', 'Komandas sapulce', 'Sapulce visiem komandas dalībniekiem', 
'["teamMember", "coach", "admin"]', '#dcd3ff'),

('coachMeeting', 'Treneru sapulce', 'Treneru sapulce', 'Sapulce treneriem', 
'["coach", "admin"]', '#c8b6ff'),

('medicalCheckup', 'Medicīniskā pārbaude', 'Medicīniskā pārbaude', 'Regulārā medicīniskā pārbaude', 
'["teamMember", "coach", "admin"]', '#bbd0ff'),

('teamBuilding', 'Komandas saliedēšanas pasākums', 'Komandas pasākums', 'Komandas saliedēšanas aktivitātes', 
'["teamMember", "coach", "admin"]', '#b8c0ff'),

('tournament', 'Turnīrs', 'Turnīrs', 'Sporta turnīrs', 
'["guest", "fan", "teamMember", "coach", "admin"]', '#ffc6ff');