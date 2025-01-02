<?php
$roleHierarchy = [
    'admin' => ['admin', 'coach', 'trainer', 'teamMember', 'fan', 'guest'],
    'coach' => ['coach', 'trainer', 'teamMember', 'fan', 'guest'],
    'trainer' => ['trainer', 'teamMember', 'fan', 'guest'],
    'teamMember' => ['teamMember', 'fan', 'guest'],
    'fan' => ['fan', 'guest'],
    'guest' => ['guest']
];

function hasAccess($requiredRole, $userRole) {
    global $roleHierarchy;
    
    if ($requiredRole === 'trainer') {
        $requiredRole = 'coach';
    }
    
    if (!isset($roleHierarchy[$userRole])) {
        return false;
    }
    
    return in_array($requiredRole, $roleHierarchy[$userRole]);
}
