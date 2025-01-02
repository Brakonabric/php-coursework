<?php

function getEventTemplates() {
    $jsonFile = __DIR__ . '/types.json';
    $templates = json_decode(file_get_contents($jsonFile), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Error loading event templates: ' . json_last_error_msg());
        return [];
    }
    
    return $templates;
}

function getEventTemplateField($eventType, $field, $default = '') {
    $templates = getEventTemplates();
    
    if (isset($templates[$eventType][$field])) {
        return $templates[$eventType][$field];
    }
    
    return $default;
}

function getEventTypeLabel($eventType) {
    $templates = getEventTemplates();
    
    foreach ($templates as $typeId => $data) {
        if ($typeId === $eventType && isset($data['label'])) {
            return $data['label'];
        }
    }
    
    return $eventType;
} 