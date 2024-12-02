-- Удаляем существующие записи
TRUNCATE TABLE event_types;

-- Добавляем типы событий с минимальной информацией
INSERT INTO event_types (id, visible_roles, color) VALUES 
('public_event', '["guest", "fan", "team_member", "coach", "admin"]', '#a8e6cf'),
('team_training', '["team_member", "coach", "admin"]', '#ffd3b6'),
('individual_training', '["team_member", "coach", "admin"]', '#ffaaa5'),
('match', '["guest", "fan", "team_member", "coach", "admin"]', '#ff8b94'),
('team_meeting', '["team_member", "coach", "admin"]', '#dcd3ff'),
('coach_meeting', '["coach", "admin"]', '#c8b6ff'),
('medical_checkup', '["team_member", "coach", "admin"]', '#bbd0ff'),
('team_building', '["team_member", "coach", "admin"]', '#b8c0ff'),
('tournament', '["guest", "fan", "team_member", "coach", "admin"]', '#ffc6ff');