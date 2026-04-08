<?php
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
if (!$eventName) {
    die('Falta el parámetro ?eventName=...');
}

$registroPath = 'uploads/' . $eventName . '/registro.json';

if (!file_exists($registroPath)) {
    die('No se encontró el archivo de registros.');
}

$registros = json_decode(file_get_contents($registroPath), true);

// Cabeceras para forzar la descarga del archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="registro_' . $eventName . '.csv"');

// Abrimos la salida estándar
$output = fopen('php://output', 'w');

// Escribimos los encabezados
fputcsv($output, ['Nombre', 'WhatsApp', 'Foto', 'Fecha']);

// Escribimos los registros
foreach ($registros as $registro) {
    fputcsv($output, [
        $registro['nombre'],
        $registro['whatsapp'],
        $registro['foto'],
        $registro['timestamp']
    ]);
}

fclose($output);
exit;
