<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class JsonSchema implements ValidationRule
{
    public function __construct(
        private array $schema
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail("The {$attribute} must be a valid JSON object.");

            return;
        }

        $errors = $this->validateAgainstSchema($value, $this->schema);

        if (! empty($errors)) {
            $fail("The {$attribute} has invalid structure: ".implode(', ', $errors));
        }
    }

    private function validateAgainstSchema(array $data, array $schema, string $path = ''): array
    {
        $errors = [];

        // Validate required properties
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $requiredKey) {
                if (! array_key_exists($requiredKey, $data)) {
                    $errors[] = "{$path}{$requiredKey} is required";
                }
            }
        }

        // Validate properties
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($data as $key => $value) {
                $propertyPath = $path ? "{$path}.{$key}" : $key;

                if (! isset($schema['properties'][$key])) {
                    // Allow additional properties unless explicitly forbidden
                    if (isset($schema['additionalProperties']) && $schema['additionalProperties'] === false) {
                        $errors[] = "{$propertyPath} is not allowed";
                    }

                    continue;
                }

                $propertySchema = $schema['properties'][$key];
                $propertyErrors = $this->validateProperty($value, $propertySchema, $propertyPath);
                $errors = array_merge($errors, $propertyErrors);
            }
        }

        return $errors;
    }

    private function validateProperty(mixed $value, array $schema, string $path): array
    {
        $errors = [];

        // Handle nullable
        if ($value === null) {
            if (isset($schema['type']) && is_array($schema['type']) && in_array('null', $schema['type'])) {
                return []; // null is allowed
            }

            if (! isset($schema['type']) || $schema['type'] !== 'null') {
                $errors[] = "{$path} cannot be null";
            }

            return $errors;
        }

        // Validate type
        if (isset($schema['type'])) {
            $expectedType = is_array($schema['type']) ? $schema['type'] : [$schema['type']];
            $actualType = $this->getJsonType($value);

            if (! in_array($actualType, $expectedType)) {
                $errors[] = "{$path} must be of type ".implode('|', $expectedType).", got {$actualType}";

                return $errors;
            }
        }

        // Validate array items
        if (isset($schema['items']) && is_array($value)) {
            foreach ($value as $index => $item) {
                $itemPath = "{$path}[{$index}]";

                if (isset($schema['items']['type']) && $schema['items']['type'] === 'object') {
                    $itemErrors = $this->validateAgainstSchema($item, $schema['items'], $itemPath.'.');
                    $errors = array_merge($errors, $itemErrors);
                } else {
                    $itemErrors = $this->validateProperty($item, $schema['items'], $itemPath);
                    $errors = array_merge($errors, $itemErrors);
                }
            }
        }

        // Validate object properties (nested)
        if (isset($schema['properties']) && is_array($value)) {
            $nestedErrors = $this->validateAgainstSchema($value, $schema, $path.'.');
            $errors = array_merge($errors, $nestedErrors);
        }

        return $errors;
    }

    private function getJsonType(mixed $value): string
    {
        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'number';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_array($value)) {
            // Check if associative array (object) or indexed array
            if (array_keys($value) !== range(0, count($value) - 1)) {
                return 'object';
            }

            return 'array';
        }

        if ($value === null) {
            return 'null';
        }

        return 'unknown';
    }
}
