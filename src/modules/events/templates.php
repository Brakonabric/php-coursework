<?php

function getEventTemplates() {
    $json_file = __DIR__ . '/types.json';
    $templates = json_decode(file_get_contents($json_file), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Error loading event templates: ' . json_last_error_msg());
        return [];
    }
    
    return $templates;
}

function getEventTemplateField($event_type, $field, $default = '') {
    $templates = getEventTemplates();
    
    if (isset($templates[$event_type][$field])) {
        return $templates[$event_type][$field];
    }
    
    return $default;
}

function getEventTypeLabel($event_type) {
    $templates = getEventTemplates();
    
    foreach ($templates as $type_id => $data) {
        if ($type_id === $event_type && isset($data['label'])) {
            return $data['label'];
        }
    }
    
    return $event_type;
} 