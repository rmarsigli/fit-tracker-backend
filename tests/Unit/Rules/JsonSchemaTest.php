<?php

declare(strict_types=1);

use App\Rules\JsonSchema;

it('validates valid raw_data JSON structure', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'points' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number'],
                        'lng' => ['type' => 'number'],
                        'timestamp' => ['type' => 'string'],
                    ],
                    'required' => ['lat', 'lng', 'timestamp'],
                ],
            ],
        ],
        'required' => ['points'],
    ];

    $validData = [
        'points' => [
            ['lat' => -23.5505, 'lng' => -46.6333, 'timestamp' => '2025-11-10T12:00:00Z'],
            ['lat' => -23.5515, 'lng' => -46.6343, 'timestamp' => '2025-11-10T12:01:00Z'],
        ],
    ];

    $rule = new JsonSchema($schema);
    $passed = true;
    $rule->validate('raw_data', $validData, function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});

it('rejects invalid raw_data with missing required fields', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'points' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number'],
                        'lng' => ['type' => 'number'],
                        'timestamp' => ['type' => 'string'],
                    ],
                    'required' => ['lat', 'lng', 'timestamp'],
                ],
            ],
        ],
        'required' => ['points'],
    ];

    $invalidData = [
        'points' => [
            ['lat' => -23.5505, 'lng' => -46.6333], // missing timestamp
        ],
    ];

    $rule = new JsonSchema($schema);
    $passed = true;
    $rule->validate('raw_data', $invalidData, function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeFalse();
});

it('rejects invalid weather JSON with wrong types', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'temp' => ['type' => 'number'],
            'humidity' => ['type' => 'integer'],
        ],
    ];

    $invalidData = [
        'temp' => 'twenty', // should be number
        'humidity' => 75,
    ];

    $rule = new JsonSchema($schema);
    $passed = true;
    $rule->validate('weather', $invalidData, function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeFalse();
});

it('allows null values when specified in schema', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'temp' => ['type' => ['number', 'null']],
            'condition' => ['type' => ['string', 'null']],
        ],
    ];

    $validData = [
        'temp' => null,
        'condition' => 'Sunny',
    ];

    $rule = new JsonSchema($schema);
    $passed = true;
    $rule->validate('weather', $validData, function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});
