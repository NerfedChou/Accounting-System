<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Placeholder - proper routing will be implemented later

header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Accounting System API',
    'version' => '2.0.0-alpha',
    'architecture' => [
        'Domain-Driven Design',
        'Event-Driven Architecture',
        'Hexagonal Architecture',
        'Test-Driven Development'
    ],
    'database' => 'MySQL 8.0',
    'documentation' => '/docs',
    'for' => 'Students learning professional software architecture'
], JSON_PRETTY_PRINT);
