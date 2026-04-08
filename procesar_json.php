<?php
require_once __DIR__ . '/lib/socialbooth_helpers.php';

if (!isset($_POST['imageData'], $_POST['eventName'])) {
    sb_send_text('No se han recibido todos los datos necesarios.', 400);
}

$imageData = json_decode((string) $_POST['imageData'], true);
$eventName = sb_normalize_event_name($_POST['eventName'] ?? null);

if (!is_array($imageData) || $eventName === null) {
    sb_send_text('Los datos del evento no son validos.', 400);
}

$image = isset($imageData['image']) ? trim((string) $imageData['image']) : '';
$columns = filter_var($imageData['columns'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$rows = filter_var($imageData['rows'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$requestedSpaces = sb_normalize_positive_int($imageData['requestedSpaces'] ?? null, 1, 10000);

if ($image === '' || $columns === false || $rows === false) {
    sb_send_text('La configuracion recibida no es valida.', 400);
}

$configData = [
    'image' => $image,
    'columns' => $columns,
    'rows' => $rows,
    'totalSpaces' => $columns * $rows,
    'eventName' => $eventName,
    'processed' => false,
    'spaces' => [],
];

if ($requestedSpaces !== null) {
    $configData['requestedSpaces'] = $requestedSpaces;
}

for ($i = 0; $i < $columns; $i++) {
    for ($j = 0; $j < $rows; $j++) {
        $configData['spaces'][] = [
            'name' => 'sbimg_' . $i . '_' . $j,
            'foto' => false,
        ];
    }
}

$eventDir = sb_event_path($eventName);
if (!sb_ensure_directory($eventDir)) {
    sb_send_text('No se pudo preparar la carpeta del evento.', 500);
}

$configFile = sb_event_path($eventName, 'config.json');
if (!sb_write_json_file($configFile, $configData)) {
    sb_send_text('No se pudo guardar la configuracion del evento.', 500);
}

$visorURL = 'visor.php?image=' . urlencode($image)
    . '&columns=' . urlencode((string) $columns)
    . '&rows=' . urlencode((string) $rows)
    . '&eventName=' . urlencode($eventName);

header('Location: ' . $visorURL);
exit;