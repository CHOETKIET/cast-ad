<?php

declare(strict_types=1);

function required_string(array $input, string $key): string
{
    $value = isset($input[$key]) ? trim((string) $input[$key]) : '';
    if ($value === '') {
        throw new InvalidArgumentException("กรุณาระบุ {$key}");
    }
    return $value;
}

function nullable_string(array $input, string $key): ?string
{
    if (!isset($input[$key])) {
        return null;
    }
    $value = trim((string) $input[$key]);
    return $value === '' ? null : $value;
}
