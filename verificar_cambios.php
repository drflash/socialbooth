<?php
// Obtener el nombre del evento de la consulta GET
if(isset($_GET['eventName'])) {
    $eventName = $_GET['eventName'];

    // Ruta de la carpeta de imágenes originales
    $originalesFolder = "uploads/$eventName/originales";

    // Escanear la carpeta de imágenes originales
    $files = scandir($originalesFolder);

    // Filtrar solo los archivos de imagen (jpeg, jpg, png)
    $imageFiles = array_filter($files, function($file) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return in_array($extension, ['jpeg', 'jpg', 'png'], true);
    });

    // Construir un arreglo con los nombres de las nuevas imágenes
    $newImages = [];
    foreach ($imageFiles as $imageFile) {
        // Verificar si la imagen ya se está mostrando en la configuración actual
        // Esto podría involucrar una comparación con algún registro de imágenes actualmente mostradas
        // Si la imagen no se encuentra en la configuración actual, se considera como nueva
        $newImages[] = ['name' => $imageFile, 'foto' => true]; // Suponiendo que todas las imágenes son nuevas
    }

    // Devolver los datos de las nuevas imágenes en formato JSON
    header('Content-Type: application/json');
    echo json_encode(['spaces' => $newImages]);
} else {
    // Si no se proporciona el nombre del evento, devuelve un error
    http_response_code(400); // Código de respuesta HTTP 400: Solicitud incorrecta
    echo json_encode(['error' => 'No se proporcionó el nombre del evento']);
}
?>
