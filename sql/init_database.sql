-- Создание таблицы типов событий
CREATE TABLE IF NOT EXISTS event_types (
    id VARCHAR(50) PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    default_title VARCHAR(100) NOT NULL,
    default_description TEXT,
    visible_roles JSON NOT NULL,
    color VARCHAR(7) NOT NULL
);

-- Создание таблицы событий
CREATE TABLE IF NOT EXISTS events (
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
);

-- Удаляем существующие записи типов событий
TRUNCATE TABLE event_types;

-- Добавляем типы событий
INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('public_event', 'Publisks pasākums', 'Jauns pasākums', 'Pasākuma apraksts', 
'["guest", "fan", "team_member", "coach", "admin"]', '#a8e6cf');

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('team_training', 'Komandas treniņš', 'Komandas treniņš', 'Kopējais treniņš visiem komandas dalībniekiem', 
'["team_member", "coach", "admin"]', '#ffd3b6');

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('individual_training', 'Individuālais treniņš', 'Individuālais treniņš', 'Individuālais treniņš ar treneri', 
'["team_member", "coach", "admin"]', '#ffaaa5');

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('match', 'Spēle', 'Spēle', 'Komandas spēle', 
'["guest", "fan", "team_member", "coach", "admin"]', '#ff8b94');

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('team_meeting', 'Komandas sapulce', 'Komandas sapulce', 'Sapulce visiem komandas dalībniekiem', 
'["team_member", "coach", "admin"]', '#dcd3ff');

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('coach_meeting', 'Treneru sapulce', 'Treneru sapulce', 'Sapulce treneriem', 
'["coach", "admin"]', '#c8b6ff');

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('medical_checkup', 'Medicīniskā pārbaude', 'Medicīniskā pārbaude', 'Regulārā medicīniskā pārbaude', 
'["team_member", "coach", "admin"]', '#bbd0ff');

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('team_building', 'Komandas saliedēšanas pasākums', 'Komandas pasākums', 'Komandas saliedēšanas aktivitātes', 
'["team_member", "coach", "admin"]', '#b8c0ff');

INSERT INTO event_types (id, label, default_title, default_description, visible_roles, color) 
VALUES ('tournament', 'Turnīrs', 'Turnīrs', 'Sporta turnīrs', 
'["guest", "fan", "team_member", "coach", "admin"]', '#ffc6ff');
  