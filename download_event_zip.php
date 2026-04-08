<?php
$helperPath = __DIR__ . '/lib/socialbooth_helpers.php';

if (!is_file($helperPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Falta el archivo requerido lib/socialbooth_helpers.php en el despliegue.';
    exit;
}

require_once $helperPath;

function download_event_zip_add_directory(ZipArchive $zip, $sourceDirectory, $rootName)
{
    $sourceDirectory = rtrim($sourceDirectory, DIRECTORY_SEPARATOR);
    $rootName = trim(str_replace('\\', '/', $rootName), '/');

    $zip->addEmptyDir($rootName);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $prefixLength = strlen($sourceDirectory) + 1;

    foreach ($iterator as $item) {
        $itemPath = $item->getPathname();
        $relativePath = str_replace('\\', '/', substr($itemPath, $prefixLength));
        $zipPath = $rootName . '/' . $relativePath;

        if ($item->isDir()) {
            $zip->addEmptyDir($zipPath);
            continue;
        }

        $zip->addFile($itemPath, $zipPath);
    }
}

$eventName = sb_normalize_event_name($_GET['eventName'] ?? null);
if ($eventName === null) {
    sb_send_text('El parametro eventName no es valido.', 400);
}

if (!class_exists('ZipArchive')) {
    sb_send_text('La extension ZIP no esta disponible en el servidor.', 500);
}

$eventDirectory = sb_event_path($eventName);
if (!is_dir($eventDirectory)) {
    sb_send_text('El evento solicitado no existe.', 404);
}

$temporaryZipPath = tempnam(sys_get_temp_dir(), 'sbzip_');
if ($temporaryZipPath === false) {
    sb_send_text('No se pudo preparar el archivo temporal para la descarga.', 500);
}

$zip = new ZipArchive();
$result = $zip->open($temporaryZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

if ($result !== true) {
    @unlink($temporaryZipPath);
    sb_send_text('No se pudo crear el ZIP del evento.', 500);
}

download_event_zip_add_directory($zip, $eventDirectory, $eventName);
$zip->close();

$downloadName = $eventName . '-' . date('Ymd-His') . '.zip';
$fileSize = filesize($temporaryZipPath);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . ($fileSize !== false ? $fileSize : 0));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$handle = fopen($temporaryZipPath, 'rb');
if ($handle === false) {
    @unlink($temporaryZipPath);
    sb_send_text('No se pudo abrir el ZIP generado.', 500);
}

while (!feof($handle)) {
    echo fread($handle, 1048576);
}

fclose($handle);
@unlink($temporaryZipPath);
exit;
