<?php
$event = isset($_GET["eventName"]) ? preg_replace("/[^a-zA-Z0-9_-]/", "", $_GET["eventName"]) : null;
if (!$event) {
    die("❌ Falta el parámetro ?eventName=...");
}

$configPath = __DIR__ . "/uploads/$event/config.json";
if (!file_exists($configPath)) {
    die("❌ No se encontró $configPath");
}
$json = json_decode(file_get_contents($configPath), true);

$cols = $json["columns"];
$rows = $json["rows"];
$spaces = array_filter($json["spaces"], fn($s) => isset($s["foto"]));
$fondoRelPath = $json["image"]; // ej: "uploads/ul85/85union.jpg"
$fondoFile = __DIR__ . "/" . $fondoRelPath;

$width = 5760;
$height = 3240;
$opacity = 0.5; // valor entre 0 y 1
$alpha = intval(127 * (1 - $opacity)); // corregido para evitar warning

$folderImgs = __DIR__ . "/uploads/$event/originales/";
$outputFile = __DIR__ . "/uploads/$event/mosaico_final_$event.jpg";

// Si ya existe, no volver a generarlo
if (file_exists($outputFile)) {
    echo "✅ Ya existe: <a href='uploads/$event/mosaico_final_$event.jpg' target='_blank'>Ver Mosaico</a>";
    exit;
}

$cellW = intval($width / $cols);
$cellH = intval($height / $rows);

// Fondo
if (file_exists($fondoFile)) {
    $fondo = imagecreatefromjpeg($fondoFile);
    $fondo = imagescale($fondo, $width, $height);
} else {
    $fondo = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($fondo, 255, 255, 255);
    imagefill($fondo, 0, 0, $white);
}

// Procesar imágenes
foreach ($spaces as $entry) {
    $parts = explode("_", $entry["name"]);
    $col = intval($parts[1]);
    $row = intval($parts[2]);
    $x = $col * $cellW;
    $y = $row * $cellH;

    $filename = $folderImgs . $entry['name'] . ".jpg";
    if (!file_exists($filename)) {
        echo "⚠️ Falta: $filename<br>";
        continue;
    }

    $img = imagecreatefromjpeg($filename);
    $resized = imagescale($img, $cellW, $cellH);

    $temp = imagecreatetruecolor($cellW, $cellH);
    imagecopy($temp, $resized, 0, 0, 0, 0, $cellW, $cellH);
    imagefilter($temp, IMG_FILTER_COLORIZE, 0, 0, 0, $alpha); // ← corregido

    imagecopymerge($fondo, $temp, $x, $y, 0, 0, $cellW, $cellH, $opacity * 100);

    imagedestroy($img);
    imagedestroy($resized);
    imagedestroy($temp);
}

// Guardar
imagejpeg($fondo, $outputFile, 95);
imagedestroy($fondo);

echo "✅ Mosaico generado: <a href='uploads/$event/mosaico_final_$event.jpg' target='_blank'>Descargar</a>";
?>
