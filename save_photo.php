<?php
// Recibir el nombre del evento
$eventName = isset($_POST['eventName']) ? $_POST['eventName'] : '';
if ($eventName === '') {
    die('No se proporcionó el nombre del evento.');
}

// Directorio donde se guardarán las fotos
$eventoDir = 'uploads/' . $eventName . '/originales/';
if (!file_exists($eventoDir)) {
    mkdir($eventoDir, 0777, true);
}

// Recibir la foto y guardarla en el directorio
if (isset($_POST['photo'])) {
    $dataURL = $_POST['photo'];
    $dataURL = str_replace('data:image/jpeg;base64,', '', $dataURL);
    $dataURL = str_replace(' ', '+', $dataURL);
    $data = base64_decode($dataURL);

    // Cargar el contenido del archivo config.json
    $configFile = 'uploads/' . $eventName . '/config.json';
    if (file_exists($configFile)) {
        $configData = json_decode(file_get_contents($configFile), true);
        if (isset($configData['spaces'])) {
            $availableSpaces = array_filter($configData['spaces'], function ($space) {
                return !$space['foto'];
            });

            // Seleccionar un nombre aleatorio de entre los espacios disponibles
            $selectedSpace = array_rand($availableSpaces);
            $selectedSpaceName = $availableSpaces[$selectedSpace]['name'];

            // Cambiar el valor de 'foto' a true para el nombre seleccionado
            foreach ($configData['spaces'] as &$space) {
                if ($space['name'] === $selectedSpaceName) {
                    $space['foto'] = true;
                }
            }
            unset($space); // Después de la modificación, desreferenciar la variable para evitar efectos secundarios

            // Guardar los cambios en el archivo config.json
            file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

            // Guardar la imagen en el directorio con el nombre seleccionado
            $filename = $eventoDir . $selectedSpaceName . '.jpg';
            file_put_contents($filename, $data);
            echo 'Foto guardada correctamente con el nombre: ' . $selectedSpaceName;
        } else {
            echo 'No se encontró el nodo "spaces" en el archivo de configuración.';
        }
    } else {
        echo 'No se encontró el archivo de configuración.';
    }
} else {
    echo 'No se recibió ninguna foto.';
}
?>
