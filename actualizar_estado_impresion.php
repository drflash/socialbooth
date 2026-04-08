<?php
require_once __DIR__ . '/lib/socialbooth_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sb_send_json(['error' => 'Metodo no permitido.'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$eventName = sb_normalize_event_name($payload['eventName'] ?? ($_GET['eventName'] ?? null));
if ($eventName === null) {
    sb_send_json(['error' => 'El nombre del evento no es valido.'], 400);
}

$imageName = sb_normalize_image_name($payload['image'] ?? null);
if ($imageName === null) {
    sb_send_json(['error' => 'El nombre de la imagen no es valido.'], 400);
}

$jsonFilePath = sb_event_path($eventName, 'output/print_status.json');
if (!is_file($jsonFilePath)) {
    sb_send_json(['error' => 'No existe el archivo de estado de impresion.'], 404);
}

$imageNames = sb_read_json_file($jsonFilePath, []);
if (!array_key_exists($imageName, $imageNames)) {
    sb_send_json(['error' => 'La imagen no existe en el estado de impresion.'], 404);
}

$imageNames[$imageName] = true;

if (!sb_write_json_file($jsonFilePath, $imageNames)) {
    sb_send_json(['error' => 'No se pudo actualizar el estado de impresion.'], 500);
}

sb_send_json(['ok' => true, 'image' => $imageName]);
