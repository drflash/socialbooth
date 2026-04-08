<?php
$helperPath = __DIR__ . '/lib/socialbooth_helpers.php';

if (!is_file($helperPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Falta el archivo requerido lib/socialbooth_helpers.php en el despliegue.';
    exit;
}

require_once $helperPath;

function admin_event_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_event_normalize_space_name($value)
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

function admin_event_normalize_filter($value)
{
    $allowed = ['occupied', 'issues', 'free', 'all'];

    return in_array($value, $allowed, true) ? $value : 'occupied';
}

function admin_event_redirect($eventName, $filter, $type, $message)
{
    $query = http_build_query([
        'eventName' => $eventName,
        'filter' => $filter,
        $type => $message,
    ]);

    header('Location: admin_event.php?' . $query);
    exit;
}

function admin_event_pick_preview_path($eventName, $spaceName)
{
    $outputPublicPath = sb_event_public_path($eventName, 'output/' . $spaceName . '.jpg');
    if (is_file(sb_root_path($outputPublicPath))) {
        return $outputPublicPath;
    }

    foreach (['jpg', 'jpeg', 'png'] as $extension) {
        $originalPublicPath = sb_event_public_path($eventName, 'originales/' . $spaceName . '.' . $extension);
        if (is_file(sb_root_path($originalPublicPath))) {
            return $originalPublicPath;
        }
    }

    return null;
}

function admin_event_build_mosaic_view_url($eventName, array $config)
{
    $image = sb_normalize_upload_relative_path($config['image'] ?? null);
    $columns = filter_var($config['columns'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $rows = filter_var($config['rows'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($image === null || $columns === false || $rows === false) {
        return null;
    }

    return 'visor.php?image=' . urlencode($image)
        . '&columns=' . urlencode((string) $columns)
        . '&rows=' . urlencode((string) $rows)
        . '&eventName=' . urlencode($eventName);
}

function admin_event_delete_matching_files($directory, $spaceName)
{
    $deletedFiles = 0;
    $leftovers = [];
    $pattern = $directory . DIRECTORY_SEPARATOR . $spaceName . '.*';
    $matches = glob($pattern);

    if (!is_array($matches)) {
        return ['deleted_files' => 0, 'leftovers' => []];
    }

    foreach ($matches as $match) {
        if (!is_file($match)) {
            continue;
        }

        if (@unlink($match)) {
            $deletedFiles++;
            continue;
        }

        $leftovers[] = $match;
    }

    return [
        'deleted_files' => $deletedFiles,
        'leftovers' => $leftovers,
    ];
}

function admin_event_reset_space($eventName, $spaceName)
{
    $configPath = sb_event_path($eventName, 'config.json');
    $config = sb_read_json_file($configPath, []);

    if (!isset($config['spaces']) || !is_array($config['spaces'])) {
        return ['error' => 'La configuracion del evento no es valida.'];
    }

    $spaceFound = false;
    $wasTaken = false;

    foreach ($config['spaces'] as &$space) {
        if (($space['name'] ?? '') !== $spaceName) {
            continue;
        }

        $spaceFound = true;
        $wasTaken = !empty($space['foto']);
        $space['foto'] = false;
        break;
    }
    unset($space);

    if (!$spaceFound) {
        return ['error' => 'El espacio solicitado no existe en este evento.'];
    }

    $targetImageName = $spaceName . '.jpg';
    $registroPath = sb_event_path($eventName, 'registro.json');
    $registros = sb_read_json_file($registroPath, []);
    if (!is_array($registros)) {
        $registros = [];
    }

    $updatedRegistros = [];
    $removedRegistros = 0;

    foreach ($registros as $registro) {
        $registroImage = sb_normalize_image_name($registro['foto'] ?? null);

        if ($registroImage === $targetImageName) {
            $removedRegistros++;
            continue;
        }

        $updatedRegistros[] = $registro;
    }

    $printStatusPath = sb_event_path($eventName, 'output/print_status.json');
    $printStatus = sb_read_json_file($printStatusPath, []);
    if (!is_array($printStatus)) {
        $printStatus = [];
    }

    $hadPrintStatusEntry = array_key_exists($targetImageName, $printStatus);
    unset($printStatus[$targetImageName]);

    if (!sb_write_json_file($configPath, $config)) {
        return ['error' => 'No se pudo actualizar config.json.'];
    }

    if ((is_file($registroPath) || $removedRegistros > 0) && !sb_write_json_file($registroPath, $updatedRegistros)) {
        return ['error' => 'No se pudo actualizar registro.json.'];
    }

    if ((is_file($printStatusPath) || $hadPrintStatusEntry) && !sb_write_json_file($printStatusPath, $printStatus)) {
        return ['error' => 'No se pudo actualizar print_status.json.'];
    }

    $deleteOriginals = admin_event_delete_matching_files(sb_event_path($eventName, 'originales'), $spaceName);
    $deleteOutputs = admin_event_delete_matching_files(sb_event_path($eventName, 'output'), $spaceName);

    return [
        'was_taken' => $wasTaken,
        'removed_registros' => $removedRegistros,
        'deleted_files' => $deleteOriginals['deleted_files'] + $deleteOutputs['deleted_files'],
        'leftovers' => array_merge($deleteOriginals['leftovers'], $deleteOutputs['leftovers']),
    ];
}

function admin_event_build_spaces($eventName, array $config)
{
    $registroPath = sb_event_path($eventName, 'registro.json');
    $registros = sb_read_json_file($registroPath, []);
    if (!is_array($registros)) {
        $registros = [];
    }

    $registroMap = [];
    foreach ($registros as $registro) {
        $foto = sb_normalize_image_name($registro['foto'] ?? null);

        if ($foto === null) {
            continue;
        }

        if (!isset($registroMap[$foto])) {
            $registroMap[$foto] = [
                'count' => 0,
                'nombre' => '',
                'whatsapp' => '',
                'timestamp' => '',
            ];
        }

        $registroMap[$foto]['count']++;
        $registroMap[$foto]['nombre'] = (string) ($registro['nombre'] ?? '');
        $registroMap[$foto]['whatsapp'] = (string) ($registro['whatsapp'] ?? '');
        $registroMap[$foto]['timestamp'] = (string) ($registro['timestamp'] ?? '');
    }

    $printStatusPath = sb_event_path($eventName, 'output/print_status.json');
    $printStatus = sb_read_json_file($printStatusPath, []);
    if (!is_array($printStatus)) {
        $printStatus = [];
    }

    $spaces = [];
    $stats = [
        'total' => 0,
        'occupied' => 0,
        'issues' => 0,
        'free' => 0,
        'resettable' => 0,
    ];

    foreach ($config['spaces'] as $space) {
        $spaceName = admin_event_normalize_space_name($space['name'] ?? null);
        if ($spaceName === null) {
            continue;
        }

        $stats['total']++;

        $taken = !empty($space['foto']);
        $previewPath = admin_event_pick_preview_path($eventName, $spaceName);
        $outputName = $spaceName . '.jpg';
        $outputPath = sb_event_path($eventName, 'output/' . $outputName);
        $hasOutput = is_file($outputPath);
        $hasOriginal = false;

        foreach (['jpg', 'jpeg', 'png'] as $extension) {
            if (is_file(sb_event_path($eventName, 'originales/' . $spaceName . '.' . $extension))) {
                $hasOriginal = true;
                break;
            }
        }

        $registroInfo = $registroMap[$outputName] ?? [
            'count' => 0,
            'nombre' => '',
            'whatsapp' => '',
            'timestamp' => '',
        ];

        $hasRecords = $registroInfo['count'] > 0;
        $hasPrintStatus = array_key_exists($outputName, $printStatus);
        $printed = $hasPrintStatus ? !empty($printStatus[$outputName]) : null;

        if (($taken && !$hasOriginal) || (!$taken && ($hasOriginal || $hasOutput || $hasRecords || $hasPrintStatus))) {
            $statusKey = 'issues';
            $statusLabel = $taken ? 'Inconsistente' : 'Residual';
            $stats['issues']++;
        } elseif ($taken) {
            $statusKey = 'occupied';
            $statusLabel = 'Tomada';
            $stats['occupied']++;
        } else {
            $statusKey = 'free';
            $statusLabel = 'Libre';
            $stats['free']++;
        }

        $canReset = $taken || $hasOriginal || $hasOutput || $hasRecords || $hasPrintStatus;
        if ($canReset) {
            $stats['resettable']++;
        }

        $spaces[] = [
            'name' => $spaceName,
            'preview_path' => $previewPath,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'taken' => $taken,
            'has_original' => $hasOriginal,
            'has_output' => $hasOutput,
            'registro_count' => $registroInfo['count'],
            'registro_nombre' => $registroInfo['nombre'],
            'registro_whatsapp' => $registroInfo['whatsapp'],
            'registro_timestamp' => $registroInfo['timestamp'],
            'printed' => $printed,
            'has_print_status' => $hasPrintStatus,
            'can_reset' => $canReset,
        ];
    }

    return [$spaces, $stats];
}

$eventName = sb_normalize_event_name($_REQUEST['eventName'] ?? null);
if ($eventName === null) {
    sb_send_text('El parametro eventName no es valido.', 400);
}

$filter = admin_event_normalize_filter($_REQUEST['filter'] ?? 'occupied');
$noticeMessage = isset($_GET['notice']) ? trim((string) $_GET['notice']) : '';
$errorMessage = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$config = [
    'image' => '',
    'columns' => 0,
    'rows' => 0,
];
$visorUrl = null;

try {
    if ($requestMethod === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $spaceName = admin_event_normalize_space_name($_POST['spaceName'] ?? null);

        if ($action !== 'reset_space' || $spaceName === null) {
            admin_event_redirect($eventName, $filter, 'error', 'La accion solicitada no es valida.');
        }

        $result = admin_event_reset_space($eventName, $spaceName);

        if (isset($result['error'])) {
            admin_event_redirect($eventName, $filter, 'error', $result['error']);
        }

        $message = 'Se restablecio ' . $spaceName . '.';
        if (($result['removed_registros'] ?? 0) > 0) {
            $message .= ' Registros eliminados: ' . $result['removed_registros'] . '.';
        }
        if (($result['deleted_files'] ?? 0) > 0) {
            $message .= ' Archivos eliminados: ' . $result['deleted_files'] . '.';
        }
        if (!empty($result['leftovers'])) {
            $message .= ' Quedaron archivos residuales por permisos.';
        }

        admin_event_redirect($eventName, $filter, 'notice', $message);
    }

    $configPath = sb_event_path($eventName, 'config.json');
    $config = sb_read_json_file($configPath, []);

    if (!isset($config['spaces']) || !is_array($config['spaces'])) {
        sb_send_text('No se encontro una configuracion valida para el evento.', 404);
    }

    $visorUrl = admin_event_build_mosaic_view_url($eventName, $config);
    [$spaces, $stats] = admin_event_build_spaces($eventName, $config);
} catch (Throwable $exception) {
    error_log(
        'admin_event.php runtime error: '
        . $exception->getMessage()
        . ' in '
        . $exception->getFile()
        . ':'
        . $exception->getLine()
    );

    $spaces = [];
    $stats = [
        'total' => 0,
        'occupied' => 0,
        'issues' => 0,
        'free' => 0,
        'resettable' => 0,
    ];
    $errorMessage = 'No se pudo cargar la gestion del evento. Revisa el runtime log del servidor.';
}

$filteredSpaces = array_values(
    array_filter(
        $spaces,
        static function ($space) use ($filter) {
            return $filter === 'all' || $space['status_key'] === $filter;
        }
    )
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fotos del evento <?= admin_event_escape($eventName) ?></title>
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
            --success-bg: #ecfdf3;
            --success-text: #166534;
            --warning-bg: #fff7ed;
            --warning-text: #9a3412;
            --danger-bg: #fef3f2;
            --danger-text: #b42318;
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

        .page {
            width: min(1380px, calc(100% - 32px));
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

        .toolbar,
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .button,
        .filter-link,
        .reset-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            border: 1px solid transparent;
            font-weight: bold;
            cursor: pointer;
        }

        .button {
            background: var(--brand);
            color: #fff;
        }

        .button.secondary,
        .filter-link {
            background: #fff;
            color: var(--text);
            border-color: var(--border);
        }

        .button:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card,
        .space-card,
        .empty-state,
        .flash {
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
            font-weight: bold;
        }

        .flash.notice {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #a7f3d0;
        }

        .flash.error {
            background: var(--danger-bg);
            color: var(--danger-text);
            border-color: #fecdca;
        }

        .filters {
            margin-bottom: 18px;
        }

        .filter-link.active {
            background: #ede9fe;
            color: var(--brand-dark);
            border-color: #c4b5fd;
        }

        .spaces-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }

        .space-card {
            overflow: hidden;
        }

        .space-preview {
            height: 220px;
            background: #e9edf5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .space-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .space-preview-placeholder {
            color: var(--muted);
            font-weight: bold;
            text-align: center;
            padding: 20px;
        }

        .space-body {
            padding: 18px;
        }

        .space-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .space-name {
            margin: 0;
            font-size: 22px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-pill.occupied {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .status-pill.issues {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .status-pill.free {
            background: #eef2ff;
            color: var(--brand-dark);
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .meta-item {
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .meta-label {
            display: block;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 6px;
        }

        .meta-value {
            font-size: 18px;
            font-weight: bold;
        }

        .meta-value.ok {
            color: var(--success-text);
        }

        .meta-value.warn {
            color: var(--warning-text);
        }

        .registro-box {
            margin-bottom: 14px;
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: var(--muted);
            line-height: 1.5;
            min-height: 88px;
        }

        .registro-box strong {
            color: var(--text);
        }

        .reset-form {
            margin: 0;
        }

        .reset-button {
            width: 100%;
            background: #fff;
            color: var(--danger-text);
            border-color: #fda29b;
        }

        .reset-button:hover {
            background: var(--danger-bg);
        }

        .reset-button[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            padding: 40px 24px;
            text-align: center;
            color: var(--muted);
        }

        @media (max-width: 720px) {
            .header {
                flex-direction: column;
            }

            .toolbar .button,
            .filters .filter-link {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div>
                <h1>Fotos del evento <?= admin_event_escape($eventName) ?></h1>
                <p>Restablece espacios individuales cuando una foto deba eliminarse, falle en subir o necesites liberar ese lugar otra vez.</p>
            </div>
            <div class="toolbar">
                <a class="button secondary" href="admin.php">Volver</a>
                <?php if ($visorUrl !== null): ?>
                    <a class="button secondary" href="<?= admin_event_escape($visorUrl) ?>" target="_blank">Visor</a>
                <?php endif; ?>
                <a class="button" href="download_event_zip.php?eventName=<?= urlencode($eventName) ?>">ZIP</a>
            </div>
        </div>

        <?php if ($noticeMessage !== '' || $errorMessage !== ''): ?>
            <div class="flash-stack">
                <?php if ($noticeMessage !== ''): ?>
                    <div class="flash notice"><?= admin_event_escape($noticeMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="flash error"><?= admin_event_escape($errorMessage) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <span class="stat-label">Espacios totales</span>
                <span class="stat-value"><?= admin_event_escape($stats['total']) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Tomadas</span>
                <span class="stat-value"><?= admin_event_escape($stats['occupied']) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Inconsistencias</span>
                <span class="stat-value"><?= admin_event_escape($stats['issues']) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Libres</span>
                <span class="stat-value"><?= admin_event_escape($stats['free']) ?></span>
            </div>
        </div>

        <div class="filters">
            <a class="filter-link <?= $filter === 'occupied' ? 'active' : '' ?>" href="admin_event.php?eventName=<?= urlencode($eventName) ?>&filter=occupied">Tomadas (<?= admin_event_escape($stats['occupied']) ?>)</a>
            <a class="filter-link <?= $filter === 'issues' ? 'active' : '' ?>" href="admin_event.php?eventName=<?= urlencode($eventName) ?>&filter=issues">Inconsistencias (<?= admin_event_escape($stats['issues']) ?>)</a>
            <a class="filter-link <?= $filter === 'free' ? 'active' : '' ?>" href="admin_event.php?eventName=<?= urlencode($eventName) ?>&filter=free">Libres (<?= admin_event_escape($stats['free']) ?>)</a>
            <a class="filter-link <?= $filter === 'all' ? 'active' : '' ?>" href="admin_event.php?eventName=<?= urlencode($eventName) ?>&filter=all">Todas (<?= admin_event_escape($stats['total']) ?>)</a>
        </div>

        <?php if ($filteredSpaces === []): ?>
            <div class="empty-state">
                No hay espacios para el filtro seleccionado.
            </div>
        <?php else: ?>
            <div class="spaces-grid">
                <?php foreach ($filteredSpaces as $space): ?>
                    <article class="space-card">
                        <div class="space-preview">
                            <?php if ($space['preview_path'] !== null): ?>
                                <img src="<?= admin_event_escape($space['preview_path']) ?>" alt="Vista previa de <?= admin_event_escape($space['name']) ?>">
                            <?php else: ?>
                                <div class="space-preview-placeholder">Sin imagen disponible</div>
                            <?php endif; ?>
                        </div>
                        <div class="space-body">
                            <div class="space-top">
                                <h2 class="space-name"><?= admin_event_escape($space['name']) ?></h2>
                                <span class="status-pill <?= admin_event_escape($space['status_key']) ?>"><?= admin_event_escape($space['status_label']) ?></span>
                            </div>

                            <div class="meta-grid">
                                <div class="meta-item">
                                    <span class="meta-label">Original</span>
                                    <span class="meta-value <?= $space['has_original'] ? 'ok' : 'warn' ?>"><?= $space['has_original'] ? 'Si' : 'No' ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Salida</span>
                                    <span class="meta-value <?= $space['has_output'] ? 'ok' : 'warn' ?>"><?= $space['has_output'] ? 'Si' : 'No' ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Registros</span>
                                    <span class="meta-value"><?= admin_event_escape($space['registro_count']) ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Impresa</span>
                                    <span class="meta-value <?= $space['printed'] ? 'ok' : 'warn' ?>">
                                        <?php if (!$space['has_print_status']): ?>
                                            N/D
                                        <?php else: ?>
                                            <?= $space['printed'] ? 'Si' : 'No' ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="registro-box">
                                <?php if ($space['registro_count'] > 0): ?>
                                    <strong><?= admin_event_escape($space['registro_nombre'] !== '' ? $space['registro_nombre'] : 'Sin nombre') ?></strong><br>
                                    WhatsApp: <?= admin_event_escape($space['registro_whatsapp'] !== '' ? $space['registro_whatsapp'] : 'Sin dato') ?><br>
                                    Fecha: <?= admin_event_escape($space['registro_timestamp'] !== '' ? $space['registro_timestamp'] : 'Sin fecha') ?>
                                <?php else: ?>
                                    Sin registro asociado para este espacio.
                                <?php endif; ?>
                            </div>

                            <form class="reset-form" method="post" data-space-name="<?= admin_event_escape($space['name']) ?>">
                                <input type="hidden" name="action" value="reset_space">
                                <input type="hidden" name="eventName" value="<?= admin_event_escape($eventName) ?>">
                                <input type="hidden" name="spaceName" value="<?= admin_event_escape($space['name']) ?>">
                                <input type="hidden" name="filter" value="<?= admin_event_escape($filter) ?>">
                                <button class="reset-button" type="submit" <?= $space['can_reset'] ? '' : 'disabled' ?>>Restablecer espacio</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.reset-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                const spaceName = form.dataset.spaceName || '';
                const confirmed = window.confirm(
                    'Se eliminara la foto del espacio ' + spaceName
                    + ' y se liberara el lugar para volver a usarlo. Deseas continuar?'
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
