<?php
// Verificar si eventName está definido
if (isset($_GET['eventName'])) {
    // Obtener el nombre del evento dinámicamente
    $eventName = $_GET['eventName'];

    // Ruta a la carpeta output
    $outputFolder = __DIR__ . "/uploads/$eventName/output/";

    // Verificar si la carpeta output existe
    if (is_dir($outputFolder)) {
        // Array para almacenar las rutas de las imágenes
        $imageUrls = array();

        // Escanear la carpeta output y agregar las rutas de las imágenes al array
        foreach (glob($outputFolder . "*.jpg") as $imageFile) {
            $imageUrls[] = $imageFile;
        }

        // Devolver el array como JSON
        echo json_encode($imageUrls);
    } else {
        // Si la carpeta output no existe, devuelve un mensaje de error
        echo json_encode(array('error' => 'La carpeta de salida no existe para el evento proporcionado.'));
    }
} else {
    // Si eventName no está definido en la URL, devuelve un mensaje de error
    echo json_encode(array('error' => 'El parámetro "eventName" no está definido en la URL.'));
}
?>
