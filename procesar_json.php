<?php
if(isset($_POST['imageData']) && isset($_POST['eventName'])) {
    // Decode the JSON data
    $imageData = json_decode($_POST['imageData'], true);
    $eventName = $_POST['eventName']; // Get the event name

    // Prepare the data for the config.json
    $configData = [
        "image" => $imageData['image'],
        "columns" => $imageData['columns'],
        "rows" => $imageData['rows'],
        "totalSpaces" => $imageData['totalSpaces'],
        "eventName" => $eventName, // Add the event name to the config data
        "processed" => false,
        "spaces" => []
    ];

    // Generate the spaces array
    for ($i = 0; $i < $imageData['columns']; $i++) {
        for ($j = 0; $j < $imageData['rows']; $j++) {
            $configData['spaces'][] = [
                "name" => "sbimg_" . $i . "_" . $j, // Concatenate properly
                "foto" => false
            ];
        }
    }

    // Convert the config data to JSON
    $jsonConfig = json_encode($configData, JSON_PRETTY_PRINT);

    // Get the directory path for the event
    $eventDir = 'uploads/' . $eventName . '/';

    // Ensure the event directory exists
    if (!file_exists($eventDir)) {
        mkdir($eventDir, 0777, true);
    }

    // Write the JSON data to the config file
    $configFile = $eventDir . 'config.json';
    file_put_contents($configFile, $jsonConfig);

    // Redirigir a la página visor.php
    $visorURL = "visor.php?image=" . urlencode($imageData['image']) . "&columns=" . urlencode($imageData['columns']) . "&rows=" . urlencode($imageData['rows']) . "&eventName=" . urlencode($eventName); // Incluimos el nombre del evento en la URL
    header("Location: $visorURL");
    exit();
} else {
    echo "No se han recibido todos los datos necesarios.";
}
?>
