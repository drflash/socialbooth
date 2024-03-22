<?php
    $eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
    
    // Ruta de la carpeta de imágenes
    $rutaCarpeta = __DIR__ . '/uploads/' . $eventName . '/originales/';

    
    // Obtener todas las imágenes de la carpeta
    $imagenes = glob($rutaCarpeta . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    
    // Contar el número de imágenes encontradas
    $numImagenes = count($imagenes);
    
    // Alerta para mostrar el número de imágenes encontradas
    echo "<script>alert('Se encontraron $numImagenes imágenes en la carpeta');</script>";
    
    // Verificar si hay imágenes disponibles
    if (!empty($imagenes)) {
        // Seleccionar una imagen aleatoria
        $imagenAleatoria = $imagenes[array_rand($imagenes)];
    } else {
        // Si no hay imágenes disponibles, establecer una imagen por defecto
        $imagenAleatoria = 'images/icoface.png';
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi página web de eventos</title>
</head>
<body>
    <!-- Botón para cambiar la imagen -->
    <button onclick="cambiarImagen()">Cambiar Imagen</button>

    <!-- Contenedor de la imagen -->
    <div id="imagenContainer">
        <img id="imagenEvento" src="<?php echo $imagenAleatoria; ?>" alt="Imagen del evento">
    </div>

    <script>
        function cambiarImagen() {
            // Obtener el nombre del evento desde PHP
            var eventName = "<?php echo $eventName; ?>";
            
            // Actualizar la página para obtener una nueva imagen aleatoria
            location.reload();
        }
    </script>
</body>
</html>
