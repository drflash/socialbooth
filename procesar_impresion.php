<?php
// Directorio de uploads
$uploadsDir = "uploads/";

// Obtener el nombre del evento
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';

// Verificar si se proporcionó el nombre del evento
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

// Directorio de las imágenes originales
$originalsDir = $uploadsDir . $eventName . "/originales/";

// Crear directorio de salida si no existe
$outputDir = $uploadsDir . $eventName . "/output/";
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Ruta del archivo JSON de estado de impresión
$printStatusFile = $outputDir . "print_status.json";

// Array para mantener el estado de impresión
$printStatus = [];

// Cargar el estado de impresión si el archivo existe
if (file_exists($printStatusFile)) {
    $printStatus = json_decode(file_get_contents($printStatusFile), true);
} else {
    // Si el archivo no existe, inicializar el estado de impresión como un array vacío
    $printStatus = [];
}

// Función para procesar una imagen y superponerla con un archivo PNG
function processImage($imageName, $overlayImage, $outputPath) {
    // Verificar si el archivo de salida ya existe
    if (file_exists($outputPath)) {
        echo "El archivo ya existe: $outputPath<br>";
        return; // No es necesario procesar la imagen nuevamente
    }

    // Cargar imágenes
    $image = imagecreatefromjpeg($imageName);
    $overlay = imagecreatefrompng($overlayImage);

    // Obtener dimensiones
    $imageWidth = imagesx($image);
    $imageHeight = imagesy($image);
    $overlayWidth = imagesx($overlay);
    $overlayHeight = imagesy($overlay);

    // Calcular las coordenadas para centrar la imagen en el marco
    $x = ($overlayWidth - $imageWidth) / 2;
    $y = ($overlayHeight - $imageHeight) / 2;

    // Superponer imágenes
    imagecopy($overlay, $image, $x, $y, 0, 0, $imageWidth, $imageHeight);

    // Guardar imagen superpuesta
    imagejpeg($overlay, $outputPath, 100); // Calidad 100 (mejor calidad)

    // Liberar memoria
    imagedestroy($image);
    imagedestroy($overlay);

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

// Guardar el estado de impresión en el archivo JSON
file_put_contents($printStatusFile, json_encode($printStatus, JSON_PRETTY_PRINT));

// Informar que se ha actualizado el estado de impresión
echo "El estado de impresión se ha actualizado.";

?>
