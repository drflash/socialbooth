<?php
$helperPath = __DIR__ . '/lib/socialbooth_helpers.php';

if (!is_file($helperPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Falta el archivo requerido lib/socialbooth_helpers.php en el despliegue.';
    exit;
}

require_once $helperPath;

function admin_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_redirect_with_message($type, $message)
{
    $location = 'admin.php?' . rawurlencode($type) . '=' . rawurlencode($message);
    header('Location: ' . $location);
    exit;
}

function admin_icon($name)
{
    $icons = [
        'create' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
        'visor' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>',
        'animado' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7-11-7Z"/><path d="M5 5l1.5 1.5M5 19l1.5-1.5"/></svg>',
        'slider' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/><circle cx="9" cy="7" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="11" cy="17" r="2"/></svg>',
        'registros' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h8l3 3v13H5V4h3Z"/><path d="M9 10h6M9 14h6M9 18h4"/></svg>',
        'captura' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h4l2-2h4l2 2h4v10H4V8Z"/><circle cx="12" cy="13" r="3.5"/></svg>',
        'rellenar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4z"/><path d="M16.5 13v7M13 16.5h7"/></svg>',
        'eliminar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M8 10v8M12 10v8M16 10v8"/><path d="M6 7l1 13h10l1-13"/></svg>',
    ];

    return $icons[$name] ?? '';
}

function admin_count_taken_spaces(array $spaces)
{
    $taken = 0;

    foreach ($spaces as $space) {
        if (!empty($space['foto'])) {
            $taken++;
        }
    }

    return $taken;
}

function admin_count_json_rows($filePath)
{
    $rows = sb_read_json_file($filePath, []);

    return is_array($rows) ? count($rows) : 0;
}

function admin_count_output_images($eventName)
{
    $pattern = sb_event_path($eventName, 'output') . DIRECTORY_SEPARATOR . '*.jpg';
    $matches = glob($pattern);

    return is_array($matches) ? count($matches) : 0;
}

function admin_pick_preview_path($eventName, array $config)
{
    $candidates = [];

    if (isset($config['image'])) {
        $candidates[] = $config['image'];
    }

    $candidates[] = sb_event_public_path($eventName, 'frame.png');

    $outputMatches = glob(sb_event_path($eventName, 'output') . DIRECTORY_SEPARATOR . '*.jpg');
    if (is_array($outputMatches) && $outputMatches !== []) {
        $candidates[] = sb_event_public_path($eventName, 'output/' . basename($outputMatches[0]));
    }

    $originalMatches = glob(sb_event_path($eventName, 'originales') . DIRECTORY_SEPARATOR . '*.jpg');
    if (is_array($originalMatches) && $originalMatches !== []) {
        $candidates[] = sb_event_public_path($eventName, 'originales/' . basename($originalMatches[0]));
    }

    foreach ($candidates as $candidate) {
        $normalized = sb_normalize_upload_relative_path($candidate);

        if ($normalized !== null && is_file(sb_root_path($normalized))) {
            return $normalized;
        }
    }

    return null;
}

function admin_build_mosaic_view_url($scriptName, $eventName, array $config)
{
    $image = sb_normalize_upload_relative_path($config['image'] ?? null);
    $columns = filter_var($config['columns'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $rows = filter_var($config['rows'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($image === null || $columns === false || $rows === false) {
        return null;
    }

    return $scriptName . '?image=' . urlencode($image)
        . '&columns=' . urlencode((string) $columns)
        . '&rows=' . urlencode((string) $rows)
        . '&eventName=' . urlencode($eventName);
}

function admin_delete_directory_tree($directory, $allowedRoot)
{
    $rootPath = realpath($allowedRoot);
    $directoryPath = realpath($directory);

    if ($rootPath === false || $directoryPath === false) {
        return false;
    }

    $normalize = static function ($path) {
        return strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));
    };

    $rootPath = $normalize($rootPath);
    $directoryPath = $normalize($directoryPath);

    if ($directoryPath === $rootPath || !str_starts_with($directoryPath, $rootPath . DIRECTORY_SEPARATOR)) {
        return false;
    }

    $items = scandir($directory);
    if ($items === false) {
        return false;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $directory . DIRECTORY_SEPARATOR . $item;

        if (is_dir($itemPath) && !is_link($itemPath)) {
            if (!admin_delete_directory_tree($itemPath, $allowedRoot)) {
                return false;
            }

            continue;
        }

        if (!unlink($itemPath)) {
            return false;
        }
    }

    return rmdir($directory);
}

function admin_create_output_image($imageName, $overlayImage, $outputPath)
{
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
        return false;
    }

    if (!is_file($imageName) || !is_file($overlayImage)) {
        return false;
    }

    $image = imagecreatefromjpeg($imageName);
    $overlay = imagecreatefrompng($overlayImage);

    if (!$image || !$overlay) {
        if ($image) {
            imagedestroy($image);
        }
        if ($overlay) {
            imagedestroy($overlay);
        }
        return false;
    }

    $imageWidth = imagesx($image);
    $imageHeight = imagesy($image);
    $overlayWidth = imagesx($overlay);
    $overlayHeight = imagesy($overlay);

    $final = imagecreatetruecolor($overlayWidth, $overlayHeight);
    if (!$final) {
        imagedestroy($image);
        imagedestroy($overlay);
        return false;
    }

    $scale = max($overlayWidth / $imageWidth, $overlayHeight / $imageHeight);
    $newWidth = (int) round($imageWidth * $scale);
    $newHeight = (int) round($imageHeight * $scale);
    $dstX = (int) round(($overlayWidth - $newWidth) / 2);
    $dstY = (int) round(($overlayHeight - $newHeight) / 2);

    imagecopyresampled(
        $final,
        $image,
        $dstX,
        $dstY,
        0,
        0,
        $newWidth,
        $newHeight,
        $imageWidth,
        $imageHeight
    );

    imagealphablending($overlay, true);
    imagesavealpha($overlay, true);
    imagecopy($final, $overlay, 0, 0, 0, 0, $overlayWidth, $overlayHeight);

    $saved = imagejpeg($final, $outputPath, 100);

    imagedestroy($image);
    imagedestroy($overlay);
    imagedestroy($final);

    return $saved;
}

function admin_generate_outputs_for_spaces($eventName, array $spaceNames)
{
    if ($spaceNames === []) {
        return ['generated' => 0];
    }

    $framePath = sb_event_path($eventName, 'frame.png');
    if (!is_file($framePath)) {
        return ['error' => 'No se encontro el frame del evento.'];
    }

    $outputDir = sb_event_path($eventName, 'output');
    if (!sb_ensure_directory($outputDir)) {
        return ['error' => 'No se pudo preparar la carpeta output del evento.'];
    }

    $printStatusPath = sb_event_path($eventName, 'output/print_status.json');
    $printStatus = sb_read_json_file($printStatusPath, []);
    if (!is_array($printStatus)) {
        $printStatus = [];
    }

    $generated = 0;

    foreach (array_unique($spaceNames) as $spaceName) {
        $originalPath = sb_event_path($eventName, 'originales/' . $spaceName . '.jpg');
        $outputPath = sb_event_path($eventName, 'output/' . $spaceName . '.jpg');

        if (!admin_create_output_image($originalPath, $framePath, $outputPath)) {
            return ['error' => 'No se pudo generar la imagen final para ' . $spaceName . '.'];
        }

        $printStatus[basename($outputPath)] = false;
        $generated++;
    }

    if (!sb_write_json_file($printStatusPath, $printStatus)) {
        return ['error' => 'No se pudo actualizar el estado de impresion.'];
    }

    return ['generated' => $generated];
}

function admin_fill_event($eventName, $fillCount)
{
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
        return ['error' => 'La extension GD de PHP no esta habilitada en el servidor.'];
    }

    $configPath = sb_event_path($eventName, 'config.json');
    $config = sb_read_json_file($configPath, []);

    if (!isset($config['spaces']) || !is_array($config['spaces'])) {
        return ['error' => 'La configuracion del evento no es valida.'];
    }

    $usedPhotos = [];
    $availableIndexes = [];

    foreach ($config['spaces'] as $index => $space) {
        $spaceName = $space['name'] ?? '';

        if ($spaceName === '') {
            continue;
        }

        if (!empty($space['foto'])) {
            $existingPhoto = sb_event_path($eventName, 'originales/' . $spaceName . '.jpg');
            if (is_file($existingPhoto)) {
                $usedPhotos[] = $existingPhoto;
            }
        } else {
            $availableIndexes[] = $index;
        }
    }

    if ($availableIndexes === []) {
        return ['notice' => 'El mosaico ya esta completo.', 'filled' => 0];
    }

    if ($usedPhotos === []) {
        return ['error' => 'No hay fotos tomadas para rellenar este mosaico.'];
    }

    $fillCount = min(max(1, (int) $fillCount), count($availableIndexes));
    $filledSpaceNames = [];

    for ($i = 0; $i < $fillCount; $i++) {
        $spaceIndex = $availableIndexes[$i];
        $spaceName = $config['spaces'][$spaceIndex]['name'];
        $sourcePhoto = $usedPhotos[array_rand($usedPhotos)];
        $destinationPhoto = sb_event_path($eventName, 'originales/' . $spaceName . '.jpg');

        if (!copy($sourcePhoto, $destinationPhoto)) {
            continue;
        }

        $config['spaces'][$spaceIndex]['foto'] = true;
        $filledSpaceNames[] = $spaceName;
        $usedPhotos[] = $destinationPhoto;
    }

    if ($filledSpaceNames === []) {
        return ['error' => 'No se pudieron copiar fotos para rellenar el mosaico.'];
    }

    if (!sb_write_json_file($configPath, $config)) {
        return ['error' => 'No se pudo actualizar la configuracion del evento.'];
    }

    $outputResult = admin_generate_outputs_for_spaces($eventName, $filledSpaceNames);
    if (isset($outputResult['error'])) {
        return ['error' => 'Se rellenaron ' . count($filledSpaceNames) . ' espacios, pero fallo la generacion de imagenes finales.'];
    }

    return [
        'filled' => count($filledSpaceNames),
        'generated' => $outputResult['generated'] ?? 0,
    ];
}

function admin_load_events()
{
    $events = [];
    $uploadDirs = glob(sb_root_path('uploads') . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

    if (!is_array($uploadDirs)) {
        return $events;
    }

    foreach ($uploadDirs as $uploadDir) {
        $eventName = basename($uploadDir);

        if (sb_normalize_event_name($eventName) === null) {
            continue;
        }

        $configPath = sb_event_path($eventName, 'config.json');
        $config = sb_read_json_file($configPath, []);

        if (!isset($config['spaces']) || !is_array($config['spaces'])) {
            continue;
        }

        $totalSpaces = count($config['spaces']);
        $takenSpaces = admin_count_taken_spaces($config['spaces']);
        $remainingSpaces = max($totalSpaces - $takenSpaces, 0);
        $progress = $totalSpaces > 0 ? round(($takenSpaces / $totalSpaces) * 100, 1) : 0;
        $registroCount = admin_count_json_rows(sb_event_path($eventName, 'registro.json'));
        $outputCount = admin_count_output_images($eventName);
        $previewPath = admin_pick_preview_path($eventName, $config);
        $lastUpdated = @filemtime($configPath) ?: 0;

        $registroPath = sb_event_path($eventName, 'registro.json');
        $registroUrl = is_file($registroPath)
            ? 'ver_registro.php?eventName=' . urlencode($eventName)
            : null;

        if (is_file($registroPath)) {
            $lastUpdated = max($lastUpdated, (int) filemtime($registroPath));
        }

        $events[] = [
            'name' => $eventName,
            'image' => $previewPath,
            'columns' => (int) ($config['columns'] ?? 0),
            'rows' => (int) ($config['rows'] ?? 0),
            'total_spaces' => $totalSpaces,
            'taken_spaces' => $takenSpaces,
            'remaining_spaces' => $remainingSpaces,
            'progress' => $progress,
            'registro_count' => $registroCount,
            'output_count' => $outputCount,
            'last_updated' => $lastUpdated,
            'visor_url' => admin_build_mosaic_view_url('visor.php', $eventName, $config),
            'anima_url' => admin_build_mosaic_view_url('anima.php', $eventName, $config),
            'slider_url' => 'slider.php?eventName=' . urlencode($eventName),
            'registro_url' => $registroUrl,
            'capture_url' => 'foto.php?eventName=' . urlencode($eventName),
        ];
    }

    usort(
        $events,
        static function ($left, $right) {
            if ($left['last_updated'] === $right['last_updated']) {
                return strcmp($left['name'], $right['name']);
            }

            return $right['last_updated'] <=> $left['last_updated'];
        }
    );

    return $events;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$noticeMessage = isset($_GET['notice']) ? trim((string) $_GET['notice']) : '';
$errorMessage = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$events = [];
$totalEvents = 0;
$totalSpaces = 0;
$totalTaken = 0;
$totalRemaining = 0;
$overallProgress = 0;

try {
    if ($requestMethod === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $eventName = sb_normalize_event_name($_POST['eventName'] ?? null);

        if ($eventName === null) {
            admin_redirect_with_message('error', 'El nombre del evento no es valido.');
        }

        if ($action === 'fill_event') {
            $fillCount = sb_normalize_positive_int($_POST['fill_count'] ?? null, 1, 10000);

            if ($fillCount === null) {
                admin_redirect_with_message('error', 'La cantidad a rellenar no es valida.');
            }

            $result = admin_fill_event($eventName, $fillCount);

            if (isset($result['error'])) {
                admin_redirect_with_message('error', $result['error']);
            }

            if (isset($result['notice'])) {
                admin_redirect_with_message('notice', $result['notice']);
            }

            admin_redirect_with_message('notice', 'Se rellenaron ' . $result['filled'] . ' espacios en ' . $eventName . '.');
        }

        if ($action === 'delete_event') {
            $confirmName = trim((string) ($_POST['confirm_name'] ?? ''));

            if ($confirmName !== $eventName) {
                admin_redirect_with_message('error', 'La confirmacion no coincide con el nombre del evento.');
            }

            $eventDir = sb_event_path($eventName);
            if (!is_dir($eventDir)) {
                admin_redirect_with_message('error', 'El evento no existe o ya fue eliminado.');
            }

            if (!admin_delete_directory_tree($eventDir, sb_root_path('uploads'))) {
                admin_redirect_with_message('error', 'No se pudo eliminar el evento.');
            }

            admin_redirect_with_message('notice', 'El evento ' . $eventName . ' fue eliminado correctamente.');
        }

        admin_redirect_with_message('error', 'La accion solicitada no es valida.');
    }

    $events = admin_load_events();
    $totalEvents = count($events);
    $totalSpaces = array_sum(array_column($events, 'total_spaces'));
    $totalTaken = array_sum(array_column($events, 'taken_spaces'));
    $totalRemaining = array_sum(array_column($events, 'remaining_spaces'));
    $overallProgress = $totalSpaces > 0 ? round(($totalTaken / $totalSpaces) * 100, 1) : 0;
} catch (Throwable $exception) {
    error_log(
        'admin.php runtime error: '
        . $exception->getMessage()
        . ' in '
        . $exception->getFile()
        . ':'
        . $exception->getLine()
    );

    $errorMessage = 'El panel no se pudo cargar completamente. Revisa el runtime log del servidor para ver el detalle del error.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Mosaicos</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f7fb;
            --card: #ffffff;
            --text: #18212f;
            --muted: #64748b;
            --border: #d9e2ec;
            --brand: #6c3df4;
            --brand-dark: #5429d8;
            --success: #17803d;
            --warning: #c77a00;
            --danger: #b42318;
            --danger-bg: #fef3f2;
            --notice-bg: #ecfdf3;
            --overlay: rgba(15, 23, 42, 0.52);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex: 0 0 16px;
        }

        .page {
            width: min(1280px, calc(100% - 32px));
            margin: 0 auto;
            padding: 24px 0 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 32px;
        }

        .header p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }

        .toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .refresh-note {
            color: var(--muted);
            font-size: 14px;
        }

        .button,
        .action-button,
        .modal-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            border: 1px solid transparent;
            cursor: pointer;
            background: #fff;
            color: var(--text);
        }

        .button {
            background: var(--brand);
            color: #fff;
        }

        .button:hover,
        .action-button.primary:hover,
        .modal-button.primary:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card,
        .event-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .stat-card {
            padding: 18px;
        }

        .stat-label {
            display: block;
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
        }

        .flash-stack {
            display: grid;
            gap: 12px;
            margin-bottom: 20px;
        }

        .flash {
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--border);
            font-weight: bold;
        }

        .flash.notice {
            background: var(--notice-bg);
            color: #0f5132;
            border-color: #a7f3d0;
        }

        .flash.error {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: #fecdca;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }

        .event-card {
            overflow: hidden;
        }

        .event-preview {
            height: 190px;
            background: #e9edf5;
            display: block;
            overflow: hidden;
        }

        .event-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .event-preview-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-weight: bold;
            background: linear-gradient(135deg, #eef2ff, #e2e8f0);
        }

        .event-body {
            padding: 18px;
        }

        .event-top {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .event-title {
            margin: 0;
            font-size: 24px;
        }

        .event-meta {
            margin-top: 6px;
            color: var(--muted);
            font-size: 14px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
            background: #ede9fe;
            color: var(--brand-dark);
            white-space: nowrap;
        }

        .progress {
            margin-bottom: 16px;
        }

        .progress-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--brand), #8b5cf6);
            border-radius: 999px;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .metric {
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .metric-label {
            display: block;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 6px;
        }

        .metric-value {
            font-size: 22px;
            font-weight: bold;
        }

        .metric-value.success {
            color: var(--success);
        }

        .metric-value.warning {
            color: var(--warning);
        }

        .event-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .action-button,
        .modal-button {
            border: 1px solid var(--border);
            color: var(--text);
            background: #fff;
            min-height: 40px;
        }

        .action-button.primary,
        .modal-button.primary {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .action-button.danger,
        .modal-button.danger {
            color: var(--danger);
            border-color: #fda29b;
            background: #fff;
        }

        .action-button.danger:hover,
        .modal-button.danger:hover {
            background: var(--danger-bg);
        }

        .action-button[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            padding: 40px 24px;
            text-align: center;
            color: var(--muted);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
        }

        .modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 50;
        }

        .modal.is-open {
            display: flex;
        }

        .modal-backdrop {
            position: absolute;
            inset: 0;
            background: var(--overlay);
        }

        .modal-dialog {
            position: relative;
            width: min(480px, 100%);
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            z-index: 1;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .modal-title {
            margin: 0;
            font-size: 24px;
        }

        .modal-close {
            border: 0;
            background: transparent;
            color: var(--muted);
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        .modal-description {
            margin: 0 0 16px;
            color: var(--muted);
            line-height: 1.5;
        }

        .modal-section {
            margin-bottom: 18px;
        }

        .modal-section.hidden {
            display: none;
        }

        .modal-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .modal-input {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 16px;
        }

        .modal-help {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        @media (max-width: 720px) {
            .header,
            .event-top,
            .modal-actions {
                flex-direction: column;
            }

            .modal-actions .modal-button,
            .toolbar .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div>
                <h1>Administrador de mosaicos</h1>
                <p>Panel para ver los eventos disponibles, su avance y ejecutar acciones de soporte por mosaico.</p>
            </div>
            <div class="toolbar">
                <a class="button" href="booth.php"><?= admin_icon('create') ?><span>Crear</span></a>
                <span class="refresh-note">Actualizacion automatica cada 30 segundos</span>
            </div>
        </div>

        <?php if ($noticeMessage !== '' || $errorMessage !== ''): ?>
            <div class="flash-stack">
                <?php if ($noticeMessage !== ''): ?>
                    <div class="flash notice"><?= admin_escape($noticeMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="flash error"><?= admin_escape($errorMessage) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <span class="stat-label">Mosaicos activos</span>
                <span class="stat-value"><?= admin_escape($totalEvents) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Fotos tomadas</span>
                <span class="stat-value"><?= admin_escape($totalTaken) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Espacios disponibles</span>
                <span class="stat-value"><?= admin_escape($totalRemaining) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Avance global</span>
                <span class="stat-value"><?= admin_escape($overallProgress) ?>%</span>
            </div>
        </div>

        <?php if ($events === []): ?>
            <div class="empty-state">
                No se encontraron mosaicos con configuracion valida en la carpeta <strong>uploads</strong>.
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): ?>
                    <article class="event-card">
                        <div class="event-preview">
                            <?php if ($event['image'] !== null): ?>
                                <img src="<?= admin_escape($event['image']) ?>" alt="Vista previa de <?= admin_escape($event['name']) ?>">
                            <?php else: ?>
                                <div class="event-preview-placeholder">Sin vista previa</div>
                            <?php endif; ?>
                        </div>
                        <div class="event-body">
                            <div class="event-top">
                                <div>
                                    <h2 class="event-title"><?= admin_escape($event['name']) ?></h2>
                                    <div class="event-meta">
                                        <?= admin_escape($event['columns']) ?> columnas x <?= admin_escape($event['rows']) ?> filas
                                    </div>
                                </div>
                                <span class="pill"><?= admin_escape($event['taken_spaces']) ?>/<?= admin_escape($event['total_spaces']) ?> tomadas</span>
                            </div>

                            <div class="progress">
                                <div class="progress-row">
                                    <span>Progreso</span>
                                    <strong><?= admin_escape($event['progress']) ?>%</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= admin_escape($event['progress']) ?>%;"></div>
                                </div>
                            </div>

                            <div class="metrics">
                                <div class="metric">
                                    <span class="metric-label">Tomadas</span>
                                    <span class="metric-value success"><?= admin_escape($event['taken_spaces']) ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Disponibles</span>
                                    <span class="metric-value warning"><?= admin_escape($event['remaining_spaces']) ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Registros</span>
                                    <span class="metric-value"><?= admin_escape($event['registro_count']) ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Procesadas</span>
                                    <span class="metric-value"><?= admin_escape($event['output_count']) ?></span>
                                </div>
                            </div>

                            <div class="event-actions">
                                <?php if ($event['visor_url'] !== null): ?>
                                    <a class="action-button primary" href="<?= admin_escape($event['visor_url']) ?>" target="_blank"><?= admin_icon('visor') ?><span>Visor</span></a>
                                <?php endif; ?>
                                <?php if ($event['anima_url'] !== null): ?>
                                    <a class="action-button" href="<?= admin_escape($event['anima_url']) ?>" target="_blank"><?= admin_icon('animado') ?><span>Animado</span></a>
                                <?php endif; ?>
                                <a class="action-button" href="<?= admin_escape($event['slider_url']) ?>" target="_blank"><?= admin_icon('slider') ?><span>Slider</span></a>
                                <?php if ($event['registro_url'] !== null): ?>
                                    <a class="action-button" href="<?= admin_escape($event['registro_url']) ?>" target="_blank"><?= admin_icon('registros') ?><span>Registros</span></a>
                                <?php endif; ?>
                                <a class="action-button" href="<?= admin_escape($event['capture_url']) ?>" target="_blank"><?= admin_icon('captura') ?><span>Captura</span></a>
                                <button
                                    type="button"
                                    class="action-button"
                                    data-modal-action="fill"
                                    data-event-name="<?= admin_escape($event['name']) ?>"
                                    data-remaining="<?= admin_escape($event['remaining_spaces']) ?>"
                                    <?= $event['remaining_spaces'] <= 0 ? 'disabled' : '' ?>
                                ><?= admin_icon('rellenar') ?><span>Rellenar</span></button>
                                <button
                                    type="button"
                                    class="action-button danger"
                                    data-modal-action="delete"
                                    data-event-name="<?= admin_escape($event['name']) ?>"
                                ><?= admin_icon('eliminar') ?><span>Eliminar</span></button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal" id="eventModal" aria-hidden="true">
        <div class="modal-backdrop" data-close-modal></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Accion</h3>
                <button type="button" class="modal-close" data-close-modal>&times;</button>
            </div>
            <p class="modal-description" id="modalDescription"></p>
            <form method="post" id="eventActionForm">
                <input type="hidden" name="action" id="modalAction" value="">
                <input type="hidden" name="eventName" id="modalEventName" value="">

                <div class="modal-section hidden" id="fillSection">
                    <label class="modal-label" for="fillCount">Cantidad a rellenar</label>
                    <input class="modal-input" type="number" id="fillCount" name="fill_count" min="1" max="1" value="1">
                    <p class="modal-help" id="fillHelp"></p>
                </div>

                <div class="modal-section hidden" id="deleteSection">
                    <label class="modal-label" for="confirmName">Escribe el nombre del evento para confirmar</label>
                    <input class="modal-input" type="text" id="confirmName" name="confirm_name" autocomplete="off">
                    <p class="modal-help">Esta accion elimina la carpeta completa del evento, incluidas fotos, registros y archivos generados.</p>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-button" data-close-modal>Cancelar</button>
                    <button type="submit" class="modal-button primary" id="modalSubmit">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('eventModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalDescription = document.getElementById('modalDescription');
            const modalAction = document.getElementById('modalAction');
            const modalEventName = document.getElementById('modalEventName');
            const modalSubmit = document.getElementById('modalSubmit');
            const fillSection = document.getElementById('fillSection');
            const deleteSection = document.getElementById('deleteSection');
            const fillCount = document.getElementById('fillCount');
            const fillHelp = document.getElementById('fillHelp');
            const confirmName = document.getElementById('confirmName');
            const eventActionForm = document.getElementById('eventActionForm');

            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                fillSection.classList.add('hidden');
                deleteSection.classList.add('hidden');
                confirmName.value = '';
                eventActionForm.dataset.expectedName = '';
            }

            function openFillModal(button) {
                const eventName = button.dataset.eventName || '';
                const remaining = parseInt(button.dataset.remaining || '0', 10);

                modalTitle.textContent = 'Rellenar';
                modalDescription.textContent = 'Completa espacios vacios en ' + eventName + ' copiando fotos ya tomadas del mismo evento.';
                modalAction.value = 'fill_event';
                modalEventName.value = eventName;
                fillSection.classList.remove('hidden');
                deleteSection.classList.add('hidden');
                modalSubmit.textContent = 'Rellenar';
                modalSubmit.className = 'modal-button primary';
                fillCount.max = String(Math.max(remaining, 1));
                fillCount.value = String(Math.max(remaining, 1));
                fillHelp.textContent = remaining > 0
                    ? 'Quedan ' + remaining + ' espacios disponibles en este mosaico.'
                    : 'Este mosaico ya no tiene espacios pendientes.';
                modalSubmit.disabled = remaining <= 0;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }

            function openDeleteModal(button) {
                const eventName = button.dataset.eventName || '';

                modalTitle.textContent = 'Eliminar';
                modalDescription.textContent = 'Elimina completamente el evento ' + eventName + ' y todos sus archivos asociados.';
                modalAction.value = 'delete_event';
                modalEventName.value = eventName;
                fillSection.classList.add('hidden');
                deleteSection.classList.remove('hidden');
                modalSubmit.textContent = 'Eliminar';
                modalSubmit.className = 'modal-button danger';
                modalSubmit.disabled = false;
                eventActionForm.dataset.expectedName = eventName;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                confirmName.focus();
            }

            document.querySelectorAll('[data-modal-action]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (button.disabled) {
                        return;
                    }

                    if (button.dataset.modalAction === 'fill') {
                        openFillModal(button);
                        return;
                    }

                    if (button.dataset.modalAction === 'delete') {
                        openDeleteModal(button);
                    }
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });

            eventActionForm.addEventListener('submit', function (event) {
                if (modalAction.value === 'delete_event') {
                    const expectedName = eventActionForm.dataset.expectedName || '';
                    if (confirmName.value.trim() !== expectedName) {
                        event.preventDefault();
                        alert('Escribe el nombre exacto del evento para continuar.');
                    }
                }
            });

            function scheduleRefresh() {
                window.setTimeout(function () {
                    if (modal.classList.contains('is-open')) {
                        scheduleRefresh();
                        return;
                    }

                    window.location.reload();
                }, 30000);
            }

            scheduleRefresh();
        }());
    </script>
</body>
</html>
