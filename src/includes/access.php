<?php
// Иерархия ролей
$roleHierarchy = [
    'guest' => 0,
    'fan' => 1,
    'team_member' => 2,
    'coach' => 3,
    'admin' => 4,
];

// Проверка доступа
function hasAccess($requiredRole, $userRole): bool
{
    global $roleHierarchy;

    // Если роли нет в иерархии, вернуть false
    if (!isset($roleHierarchy[$requiredRole]) || !isset($roleHierarchy[$userRole])) {
        return false;
    }

    return $roleHierarchy[$userRole] >= $roleHierarchy[$requiredRole];
}
