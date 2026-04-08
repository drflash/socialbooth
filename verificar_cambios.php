<?php
require_once __DIR__ . '/lib/socialbooth_helpers.php';

$eventName = sb_normalize_event_name($_GET['eventName'] ?? null);

if ($eventName === null) {
    sb_send_json(['error' => 'No se proporciono un nombre de evento valido.'], 400);
}

$originalesFolder = sb_event_path($eventName, 'originales');

if (!is_dir($originalesFolder)) {
    sb_send_json(['error' => 'La carpeta del evento no existe.'], 404);
}

$files = scandir($originalesFolder);
$imageFiles = array_filter(
    $files,
    static function ($file) {
        $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        return in_array($extension, ['jpeg', 'jpg', 'png'], true);
    }
);

$newImages = [];
foreach ($imageFiles as $imageFile) {
    $newImages[] = [
        'name' => pathinfo($imageFile, PATHINFO_FILENAME),
        'foto' => true,
    ];
}

usort(
    $newImages,
    static function ($left, $right) {
        return strcmp($left['name'], $right['name']);
    }
);

sb_send_json(['spaces' => array_values($newImages)]);
