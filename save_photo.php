<?php
require_once __DIR__ . '/lib/socialbooth_helpers.php';

$eventName = sb_normalize_event_name($_POST['eventName'] ?? null);
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$whatsapp = trim((string) ($_POST['whatsapp'] ?? ''));

if ($eventName === null) {
    sb_send_text('No se proporciono un nombre de evento valido.', 400);
}

if (!isset($_POST['photo'])) {
    sb_send_text('No se recibio ninguna foto.', 400);
}

$dataURL = trim((string) $_POST['photo']);
$encodedPhoto = null;

foreach (['data:image/jpeg;base64,', 'data:image/png;base64,'] as $prefix) {
    if (str_starts_with($dataURL, $prefix)) {
        $encodedPhoto = substr($dataURL, strlen($prefix));
        break;
    }
}

if ($encodedPhoto === null) {
    sb_send_text('El formato de la foto no es valido.', 400);
}

$encodedPhoto = str_replace(' ', '+', $encodedPhoto);
$data = base64_decode($encodedPhoto, true);

if ($data === false) {
    sb_send_text('No se pudo decodificar la imagen.', 400);
}

$eventoDir = sb_event_path($eventName, 'originales');
if (!sb_ensure_directory($eventoDir)) {
    sb_send_text('No se pudo preparar la carpeta del evento.', 500);
}

$configFile = sb_event_path($eventName, 'config.json');
if (!is_file($configFile)) {
    sb_send_text('No se encontro el archivo de configuracion.', 404);
}

$configData = sb_read_json_file($configFile);
if (!isset($configData['spaces']) || !is_array($configData['spaces'])) {
    sb_send_text('La configuracion del evento no es valida.', 500);
}

$availableSpaces = array_filter(
    $configData['spaces'],
    static function ($space) {
        return isset($space['name']) && empty($space['foto']);
    }
);

if ($availableSpaces === []) {
    sb_send_text('Ya no hay espacios disponibles para este evento.', 409);
}

$selectedSpace = $availableSpaces[array_rand($availableSpaces)];
$selectedSpaceName = $selectedSpace['name'];

foreach ($configData['spaces'] as &$space) {
    if (($space['name'] ?? '') === $selectedSpaceName) {
        $space['foto'] = true;
        break;
    }
}
unset($space);

if (!sb_write_json_file($configFile, $configData)) {
    sb_send_text('No se pudo actualizar la configuracion del evento.', 500);
}

$filename = sb_event_path($eventName, 'originales/' . $selectedSpaceName . '.jpg');
if (file_put_contents($filename, $data) === false) {
    sb_send_text('No se pudo guardar la imagen capturada.', 500);
}

$registroPath = sb_event_path($eventName, 'registro.json');
$registro = [
    'nombre' => function_exists('mb_substr') ? mb_substr($nombre, 0, 120) : substr($nombre, 0, 120),
    'whatsapp' => function_exists('mb_substr') ? mb_substr($whatsapp, 0, 40) : substr($whatsapp, 0, 40),
    'foto' => $selectedSpaceName . '.jpg',
    'timestamp' => date('Y-m-d H:i:s'),
];

$registros = sb_read_json_file($registroPath, []);
if (!is_array($registros)) {
    $registros = [];
}

$registros[] = $registro;
sb_write_json_file($registroPath, $registros);

sb_send_text(sb_event_public_path($eventName, 'output/' . $selectedSpaceName . '.jpg'));
