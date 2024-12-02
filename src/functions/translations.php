<?php

function getTranslations() {
    $json_file = __DIR__ . '/../translations/event_types.json';
    $translations = json_decode(file_get_contents($json_file), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Error loading translations: ' . json_last_error_msg());
        return [];
    }
    
    return $translations;
}

function getEventTypeTranslation($event_type, $field = 'label') {
    $translations = getTranslations();
    
    if (isset($translations[$event_type][$field])) {
        return $translations[$event_type][$field];
    }
    
    return "Unknown {$field} for {$event_type}";
}

// Функция для получения всех типов событий с переводами
function getAllEventTypes() {
    $translations = getTranslations();
    $event_types = [];
    
    foreach ($translations as $type_id => $data) {
        $event_types[$type_id] = [
            'id' => $type_id,
            'label' => $data['label'],
            'default_title' => $data['title'],
            'default_description' => $data['description']
        ];
    }
    
    return $event_types;
} 