<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de imágenes</title>
</head>
<body>
    <h1>Generador de imágenes</h1>
    
    <form method="POST" action="">
        <label for="nombre_evento">Nombre del evento:</label>
        <input type="text" id="nombre_evento" name="nombre_evento" required>
        <button type="submit">Generar imágenes</button>
    </form>

    <?php
    // Función para obtener imágenes del evento que ya están ocupadas
function obtenerFotosUsadas($config, $evento) {
    $usadas = [];
    foreach ($config['spaces'] as $space) {
        if ($space['foto']) {
            $archivo = 'uploads/' . $evento . '/originales/' . $space['name'] . '.jpg';
            if (file_exists($archivo)) {
                $usadas[] = $archivo;
            }
        }
    }
    return $usadas;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eventName = $_POST['nombre_evento'];
    $json_path = 'uploads/' . $eventName . '/config.json';

    $json_content = file_get_contents($json_path);
    if ($json_content === false) {
        echo "<p>Error: No se pudo cargar el archivo JSON.</p>";
    } else {
        $config = json_decode($json_content, true);

        if ($config === null) {
            echo "<p>Error: No se pudo decodificar el JSON.</p>";
        } else {
            $contador = 0;
            $fotos_usadas = obtenerFotosUsadas($config, $eventName);

            foreach ($config['spaces'] as &$space) {
                if (!$space['foto']) {
                    $contador++;
                    $nombre_foto = $space['name'] . '.jpg';
                    $ruta_destino = 'uploads/' . $eventName . '/originales/' . $nombre_foto;

                    // Si el archivo no existe aún
                    if (!file_exists($ruta_destino)) {
                        if (!empty($fotos_usadas)) {
                            // Selecciona aleatoriamente una ya usada
                            $foto_fuente = $fotos_usadas[array_rand($fotos_usadas)];

                            if (copy($foto_fuente, $ruta_destino)) {
                                $space['foto'] = true;
                                file_put_contents($json_path, json_encode($config, JSON_PRETTY_PRINT));
                            } else {
                                echo "<p>Error al copiar la imagen $foto_fuente.</p>";
                            }
                        } else {
                            echo "<p>No hay imágenes disponibles del evento para copiar.</p>";
                        }
                    }
                }
            }

            echo "<p>Se rellenaron $contador espacios para el evento '$eventName'.</p>";
        }
    }
}

    ?>

</body>
</html>
