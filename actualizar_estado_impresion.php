<?php
// Obtener el nombre del evento de la solicitud GET
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';

// Construir la ruta al archivo print_status.json
$jsonFilePath = 'uploads/' . $eventName . '/output/print_status.json';

// Punto de notificación: Verificar si se recibió una solicitud POST
echo "Punto de notificación: Verificar solicitud POST <br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Punto de notificación: Obtener el nombre de la imagen que se marcó como impresa
    echo "Punto de notificación: Obtener nombre de la imagen <br>";

    $data = json_decode(file_get_contents('php://input'), true);
    $imageName = $data['image'];

    // Verificar si el archivo JSON existe
    if (file_exists($jsonFilePath)) {
        // Punto de notificación: Archivo JSON existe
        echo "Punto de notificación: Archivo JSON existe <br>";

        // Leer el contenido del archivo JSON
        $jsonContent = file_get_contents($jsonFilePath);

        // Decodificar el contenido JSON en un array asociativo
        $imageNames = json_decode($jsonContent, true);

        // Marcar la imagen como impresa (true)
        if (isset($imageNames[$imageName])) {
            // Punto de notificación: Marcar imagen como impresa
            echo "Punto de notificación: Marcar imagen como impresa <br>";

            $imageNames[$imageName] = true;

            // Codificar el array actualizado como JSON
            $updatedJsonContent = json_encode($imageNames, JSON_PRETTY_PRINT);

            // Escribir el JSON actualizado en el archivo
            file_put_contents($jsonFilePath, $updatedJsonContent);

            // Respondemos con un código 200 para indicar que la actualización fue exitosa
            http_response_code(200);
        } else {
            // Punto de notificación: Imagen no está en el JSON
            echo "Punto de notificación: Imagen no está en el JSON <br>";

            // Si la imagen no está en el JSON, respondemos con un código 404
            http_response_code(404);
        }
    } else {
        // Punto de notificación: Archivo JSON no existe
        echo "Punto de notificación: Archivo JSON no existe <br>";

        // Si el archivo JSON no existe, respondemos con un código 500
        http_response_code(500);
    }
}

// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
