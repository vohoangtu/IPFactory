<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Services\AI;

class AiSettingValueHandler
{
    public function decode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        if ($trimmed[0] === '{' || $trimmed[0] === '[') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (in_array(strtolower($trimmed), ['true', 'false'], true)) {
            return strtolower($trimmed) === 'true';
        }

        if (is_numeric($trimmed) && ! str_contains($trimmed, ' ')) {
            return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }

        return $value;
    }

    public function mask(mixed $value): mixed
    {
        if (is_array($value)) {
            $masked = [];
            foreach ($value as $key => $item) {
                $masked[$key] = $this->isSecretField((string) $key)
                    ? '********'
                    : $this->mask($item);
            }

            return $masked;
        }

        return '********';
    }

    public function restoreMasked(array $incoming, array $existing): array
    {
        $merged = $incoming;

        foreach ($incoming as $key => $value) {
            if ($value === '********' && array_key_exists($key, $existing)) {
                $merged[$key] = $existing[$key];
                continue;
            }

            if (is_array($value)) {
                $merged[$key] = $this->restoreMasked(
                    $value,
                    is_array($existing[$key] ?? null) ? $existing[$key] : []
                );
            }
        }

        foreach ($existing as $key => $value) {
            if (! array_key_exists($key, $merged) && $this->isSecretField((string) $key)) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public function isSecretField(string $key): bool
    {
        return in_array(strtolower($key), ['key', 'api_key', 'token', 'secret', 'password'], true);
    }
}
