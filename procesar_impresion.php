
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Directorio de uploads
$uploadsDir = "uploads/";

// Obtener el nombre del evento
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';

// Verificar si se proporcionÃ³ el nombre del evento
if (!$eventName) {
    die("Nombre de evento no proporcionado.");
}

// Ruta del archivo config.json
$configFile = $uploadsDir . $eventName . "/config.json";

// Verificar si existe el archivo config.json
if (!file_exists($configFile)) {
    die("El archivo config.json no existe para este evento.");
}

// Cargar el contenido de config.json
$jsonString = file_get_contents($configFile);
$data = json_decode($jsonString, true);

// Directorio de las imÃ¡genes originales
$originalsDir = $uploadsDir . $eventName . "/originales/";

// Crear directorio de salida si no existe
$outputDir = $uploadsDir . $eventName . "/output/";
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Ruta del archivo JSON de estado de impresiÃ³n
$printStatusFile = $outputDir . "print_status.json";

// Array para mantener el estado de impresiÃ³n
$printStatus = [];

// Cargar el estado de impresiÃ³n si el archivo existe
if (file_exists($printStatusFile)) {
    $printStatus = json_decode(file_get_contents($printStatusFile), true);
} else {
    // Si el archivo no existe, inicializar el estado de impresiÃ³n como un array vacÃ­o
    $printStatus = [];
}

// FunciÃ³n para procesar una imagen y superponerla con un archivo PNG
function processImage($imageName, $overlayImage, $outputPath) {
    // Verificar si el archivo de salida ya existe
    if (file_exists($outputPath)) {
        echo "El archivo ya existe: $outputPath<br>";
        return; // No es necesario procesar la imagen nuevamente
    }

    // Cargar imágenes
    $image = imagecreatefromjpeg($imageName);
    $overlay = imagecreatefrompng($overlayImage);

    if (!$image) {
        echo "❌ Error al cargar imagen original: $imageName<br>";
        return;
    }
    if (!$overlay) {
        echo "❌ Error al cargar frame PNG: $overlayImage<br>";
        imagedestroy($image);
        return;
    }

    // Obtener dimensiones
    $imageWidth = imagesx($image);
    $imageHeight = imagesy($image);
    $overlayWidth = imagesx($overlay);
    $overlayHeight = imagesy($overlay);

    // Lienzo final: foto como fondo + frame PNG encima
    $final = imagecreatetruecolor($overlayWidth, $overlayHeight);
    if (!$final) {
        echo "❌ Error al crear lienzo final<br>";
        imagedestroy($image);
        imagedestroy($overlay);
        return;
    }

    // Escalar la foto en modo "cover" para llenar todo el frame
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

    // Dibujar el frame PNG encima respetando el canal alpha
    imagealphablending($overlay, true);
    imagesavealpha($overlay, true);
    imagecopy($final, $overlay, 0, 0, 0, 0, $overlayWidth, $overlayHeight);

    echo "✅ Generando: $outputPath<br>";

    // Guardar imagen final (JPG para mantener el flujo actual de impresión)
    imagejpeg($final, $outputPath, 100); // Calidad 100 (mejor calidad)

    if (!file_exists($outputPath)) {
        echo "❌ No se guardó correctamente la imagen final<br>";
    } else {
        echo "✅ Imagen generada correctamente<br>";
    }

    // Liberar memoria
    imagedestroy($image);
    imagedestroy($overlay);
    imagedestroy($final);

    global $printStatus;
    $printStatus[basename($outputPath)] = false;
}

// Procesar cada espacio en el JSON
foreach ($data['spaces'] as $space) {
    if ($space['foto']) {
        // Archivo de imagen original
        $originalImage = $originalsDir . $space['name'] . ".jpg";

        // Archivo de imagen superpuesta
        $overlayImage = $uploadsDir . $eventName . "/frame.png";

        // Ruta de salida
        $outputPath = $outputDir . $space['name'] . ".jpg";

        // Procesar imagen
        processImage($originalImage, $overlayImage, $outputPath);
    }
}

// Guardar el estado de impresiÃ³n en el archivo JSON
file_put_contents($printStatusFile, json_encode($printStatus, JSON_PRETTY_PRINT));

// Informar que se ha actualizado el estado de impresiÃ³n
echo "El estado de impresiÃ³n se ha actualizado.";

?>

