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
    // Función para obtener una lista de imágenes de refill
    function obtenerImagenesRefill($ruta_refill) {
        return glob($ruta_refill . '*.{jpg,jpeg}', GLOB_BRACE);
    }

    // Verificar si se envió el formulario
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Obtener el nombre del evento desde el formulario
        $eventName = $_POST['nombre_evento'];

        // Construir la ruta al JSON
        $json_path = 'uploads/' . $eventName . '/config.json';

        // Cargar el JSON desde el archivo correspondiente
        $json_content = file_get_contents($json_path);
        if ($json_content === false) {
            echo "<p>Error: No se pudo cargar el archivo JSON.</p>";
        } else {
            $config = json_decode($json_content, true);

            // Verificar si el JSON se pudo decodificar correctamente
            if ($config === null) {
                echo "<p>Error: No se pudo decodificar el JSON.</p>";
            } else {
                // Contar las imágenes que tienen el valor de foto en false
                $contador = 0;
                foreach ($config['spaces'] as &$space) {
                    if (!$space['foto']) {
                        $contador++;
                        // Generar la nueva imagen con el nombre faltante
                        $nombre_foto = $space['name'] . '.jpg';
                        $ruta_destino = 'uploads/' . $eventName . '/originales/' . $nombre_foto;
                        // Verificar si el archivo ya existe en la carpeta de originales
                        if (!file_exists($ruta_destino)) {
                            // Obtener la lista de imágenes de refill
                            $imagenes_refill = obtenerImagenesRefill('images/refill/');
                            if (!empty($imagenes_refill)) {
                                // Seleccionar aleatoriamente una imagen de refill
                                $imagen_refill = $imagenes_refill[array_rand($imagenes_refill)];
                                // Copiar la imagen de refill con el nombre correspondiente
                                if (copy($imagen_refill, $ruta_destino)) {
                                    // Actualizar el JSON
                                    $space['foto'] = true;
                                    // Guardar el JSON actualizado
                                    file_put_contents($json_path, json_encode($config, JSON_PRETTY_PRINT));
                                } else {
                                    echo "<p>Error: No se pudo copiar la imagen.</p>";
                                }
                            } else {
                                echo "<p>Error: No hay imágenes en la carpeta refill.</p>";
                            }
                        }
                    }
                }

                // Mostrar el total de imágenes a generar
                echo "<p>Total de imágenes a generar para el evento $eventName: $contador</p>";
            }
        }
    }
    ?>

</body>
</html>
