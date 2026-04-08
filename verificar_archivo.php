<?php
require_once __DIR__ . '/lib/socialbooth_helpers.php';

$relativeFile = sb_normalize_upload_relative_path($_GET['file'] ?? null);

if ($relativeFile === null) {
    sb_send_json(['error' => 'No se proporciono una ruta de archivo valida.'], 400);
}

$filePath = sb_root_path($relativeFile);
sb_send_json(['exists' => file_exists($filePath)]);
