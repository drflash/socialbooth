<?php

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle === '' || strpos((string) $haystack, (string) $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        $needle = (string) $needle;

        if ($needle === '') {
            return true;
        }

        return strncmp((string) $haystack, $needle, strlen($needle)) === 0;
    }
}

function sb_normalize_event_name($value)
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);

    if ($value === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
        return null;
    }

    return $value;
}

function sb_normalize_image_name($value)
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim(basename($value));

    if ($value === '' || !preg_match('/^[A-Za-z0-9_.-]+\.(?:jpe?g|png)$/i', $value)) {
        return null;
    }

    return $value;
}

function sb_normalize_positive_int($value, $min = 1, $max = 100000)
{
    $normalized = filter_var(
        $value,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => $min, 'max_range' => $max]]
    );

    return $normalized === false ? null : $normalized;
}

function sb_normalize_upload_relative_path($value)
{
    if (!is_string($value)) {
        return null;
    }

    $value = ltrim(trim(str_replace('\\', '/', $value)), '/');

    if ($value === '' || str_contains($value, '..') || !str_starts_with($value, 'uploads/')) {
        return null;
    }

    return $value;
}

function sb_root_path($relativePath = '')
{
    $root = dirname(__DIR__);

    if ($relativePath === '') {
        return $root;
    }

    $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

    return $root . DIRECTORY_SEPARATOR . $relativePath;
}

function sb_event_path($eventName, $suffix = '')
{
    $basePath = sb_root_path('uploads' . DIRECTORY_SEPARATOR . $eventName);

    if ($suffix === '') {
        return $basePath;
    }

    $suffix = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $suffix), DIRECTORY_SEPARATOR);

    return $basePath . DIRECTORY_SEPARATOR . $suffix;
}

function sb_event_public_path($eventName, $suffix = '')
{
    $basePath = 'uploads/' . $eventName;

    if ($suffix === '') {
        return $basePath;
    }

    return $basePath . '/' . ltrim(str_replace('\\', '/', $suffix), '/');
}

function sb_ensure_directory($directory)
{
    return is_dir($directory) || mkdir($directory, 0777, true);
}

function sb_calculate_grid_from_cell_size($width, $height, $cellSize)
{
    $cellSize = max(1, (int) $cellSize);
    $width = max(1, (int) $width);
    $height = max(1, (int) $height);

    return [
        'columns' => max(1, (int) floor($width / $cellSize)),
        'rows' => max(1, (int) floor($height / $cellSize)),
    ];
}

function sb_calculate_grid_from_target($width, $height, $targetSpaces)
{
    $width = max(1, (int) $width);
    $height = max(1, (int) $height);
    $targetSpaces = max(1, (int) $targetSpaces);
    $aspectRatio = $width / $height;

    $estimatedColumns = max(1, (int) round(sqrt($targetSpaces * $aspectRatio)));
    $searchLimit = max(10, (int) ceil($estimatedColumns * 2));
    $bestCandidate = null;

    for ($columns = 1; $columns <= $searchLimit; $columns++) {
        $idealRows = $targetSpaces / $columns;
        $baseRow = max(1, (int) round($idealRows));

        for ($offset = -6; $offset <= 6; $offset++) {
            $rows = $baseRow + $offset;
            if ($rows < 1) {
                continue;
            }

            $totalSpaces = $columns * $rows;
            $ratio = $columns / $rows;
            $ratioDiff = abs(($ratio / $aspectRatio) - 1);
            $spaceDiff = abs($totalSpaces - $targetSpaces) / $targetSpaces;
            $score = ($ratioDiff * 1000) + ($spaceDiff * 100);

            $candidate = [
                'columns' => $columns,
                'rows' => $rows,
                'totalSpaces' => $totalSpaces,
                'ratioDiff' => $ratioDiff,
                'spaceDiff' => $spaceDiff,
                'score' => $score,
            ];

            if ($bestCandidate === null || $candidate['score'] < $bestCandidate['score']) {
                $bestCandidate = $candidate;
                continue;
            }

            if ($candidate['score'] === $bestCandidate['score']) {
                if ($candidate['spaceDiff'] < $bestCandidate['spaceDiff']) {
                    $bestCandidate = $candidate;
                    continue;
                }

                if (
                    $candidate['spaceDiff'] === $bestCandidate['spaceDiff']
                    && $candidate['ratioDiff'] < $bestCandidate['ratioDiff']
                ) {
                    $bestCandidate = $candidate;
                }
            }
        }
    }

    return $bestCandidate === null
        ? ['columns' => 1, 'rows' => 1, 'totalSpaces' => 1]
        : $bestCandidate;
}

function sb_read_json_file($filePath, $default = [])
{
    if (!is_file($filePath)) {
        return $default;
    }

    $contents = file_get_contents($filePath);

    if ($contents === false || $contents === '') {
        return $default;
    }

    $decoded = json_decode($contents, true);

    return is_array($decoded) ? $decoded : $default;
}

function sb_write_json_file($filePath, array $data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        return false;
    }

    return file_put_contents($filePath, $json, LOCK_EX) !== false;
}

function sb_send_json(array $payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo $json === false ? '{}' : $json;
    exit;
}

function sb_send_text($message, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}
