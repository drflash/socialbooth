<?php
$eventName = isset($_POST['eventName']) ? $_POST['eventName'] : '';
$nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$whatsapp = isset($_POST['whatsapp']) ? $_POST['whatsapp'] : '';

if ($eventName === '') {
    die('No se proporcionó el nombre del evento.');
}

$eventoDir = 'uploads/' . $eventName . '/originales/';
if (!file_exists($eventoDir)) {
    mkdir($eventoDir, 0777, true);
}

if (isset($_POST['photo'])) {
    $dataURL = $_POST['photo'];
    $dataURL = str_replace('data:image/jpeg;base64,', '', $dataURL);
    $dataURL = str_replace(' ', '+', $dataURL);
    $data = base64_decode($dataURL);

    $configFile = 'uploads/' . $eventName . '/config.json';
    if (file_exists($configFile)) {
        $configData = json_decode(file_get_contents($configFile), true);
        if (isset($configData['spaces'])) {
            $availableSpaces = array_filter($configData['spaces'], function ($space) {
                return !$space['foto'];
            });

            $selectedSpace = array_rand($availableSpaces);
            $selectedSpaceName = $availableSpaces[$selectedSpace]['name'];

            foreach ($configData['spaces'] as &$space) {
                if ($space['name'] === $selectedSpaceName) {
                    $space['foto'] = true;
                }
            }
            unset($space);

            file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));

            // Guardar imagen
            $filename = $eventoDir . $selectedSpaceName . '.jpg';
            file_put_contents($filename, $data);

            // ✅ REGISTRO JSON UNIFICADO
            $registroPath = 'uploads/' . $eventName . '/registro.json';
            $registro = [
                'nombre' => $nombre,
                'whatsapp' => $whatsapp,
                'foto' => $selectedSpaceName . '.jpg',
                'timestamp' => date('Y-m-d H:i:s')
            ];

            $registros = [];
            if (file_exists($registroPath)) {
                $contenido = file_get_contents($registroPath);
                $registros = json_decode($contenido, true);
                if (!is_array($registros)) {
                    $registros = [];
                }
            }

            $registros[] = $registro;
            file_put_contents($registroPath, json_encode($registros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Ruta pública para el QR para qr 
            $publicPath = 'uploads/' . $eventName . '/output/' . $selectedSpaceName . '.jpg';
            echo $publicPath;
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
