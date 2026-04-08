<?php
require_once __DIR__ . '/lib/socialbooth_helpers.php';

$eventName = sb_normalize_event_name($_GET['eventName'] ?? null);

if ($eventName === null) {
    sb_send_json(['error' => 'El parametro eventName no es valido.'], 400);
}

$outputFolder = sb_event_path($eventName, 'output');

if (!is_dir($outputFolder)) {
    sb_send_json(['error' => 'La carpeta de salida no existe para el evento proporcionado.'], 404);
}

$imageUrls = [];
foreach (glob($outputFolder . DIRECTORY_SEPARATOR . '*.jpg') as $imageFile) {
    $imageUrls[] = sb_event_public_path($eventName, 'output/' . basename($imageFile));
}

sort($imageUrls);

sb_send_json($imageUrls);
