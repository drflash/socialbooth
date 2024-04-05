<?php
if(isset($_POST['imageData']) && isset($_POST['eventName'])) {
    // Decodificar el JSON data
    $imageData = json_decode($_POST['imageData'], true);
    $eventName = $_POST['eventName']; // Obtener el nombre del evento

    // Prepare the data for the config.json
    $configData = [
        "image" => $imageData['image'],
        "columns" => $imageData['columns'],
        "rows" => $imageData['rows'],
        "totalSpaces" => $imageData['totalSpaces'],
        "eventName" => $eventName, // Agregar el nombre del evento al config
        "processed" => false,
        "spaces" => []
    ];

    // Generar el array de los espacios
    for ($i = 0; $i < $imageData['columns']; $i++) {
        for ($j = 0; $j < $imageData['rows']; $j++) {
            $configData['spaces'][] = [
                "name" => "sbimg_" . $i . "_" . $j, // Concatenar adecuadamente 
                "foto" => false
            ];
        }
    }

    // Convertir los datos del config a JSON
    $jsonConfig = json_encode($configData, JSON_PRETTY_PRINT);

    // Obtener el path del directorio del evento
    $eventDir = 'uploads/' . $eventName . '/';

    // Ensure the event directory exists
    if (!file_exists($eventDir)) {
        mkdir($eventDir, 0777, true);
    }

    // Escrobir los datos en el JSON config 
    $configFile = $eventDir . 'config.json';
    file_put_contents($configFile, $jsonConfig);

    // Redirigir a la página visor.php
    $visorURL = "visor.php?image=" . urlencode($imageData['image']) . "&columns=" . urlencode($imageData['columns']) . "&rows=" . urlencode($imageData['rows']) . "&eventName=" . urlencode($eventName); // Incluimos el nombre del evento en la URL
    header("Location: $visorURL");

    $visorURL = "foto.php?eventName=" . urlencode($eventName); // Incluimos el nombre del evento en la URL

    echo "<script>window.open('$visorURL', '_blank');</script>";

    exit();
} else {
    echo "No se han recibido todos los datos necesarios.";
}
?>
