<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Imprimir Imágenes</title>
<style>
/* Estilos generales */
body {
    font-family: 'Montserrat', sans-serif;
    text-align: center;
    margin: 0;
    padding: 0;
}

.container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
}

/* Estilos para impresión */
@media print {
    /* Establecer tamaño de impresión */
    img {
        width: 100% !important; /* Ajustar al 100% de la página */
        height: auto !important; /* Mantener la proporción de la imagen */
        margin: 0 !important;
        padding: 0 !important;
        page-break-inside: avoid; /* Evitar cortes de página dentro de la imagen */
    }

    body {
        margin: 0; /* Eliminar los márgenes predeterminados */
    }

    /* Ocultar elementos */
    .logo,
    p,
    button {
        display: none !important;
    }
}
</style>
</head>
<body>
<div class="container">
    <?php
    // Evento dinámico
    $eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';

    // Ruta del archivo JSON
    $jsonFile = $_SERVER['DOCUMENT_ROOT'] . "/uploads/$eventName/output/print_status.json";

    // Leer el archivo JSON y convertirlo a un array asociativo
    $jsonData = json_decode(file_get_contents($jsonFile), true);

    // Ruta base para las imágenes
    $imageBasePath = "/uploads/$eventName/output/";

    // Contador para contar las imágenes listas para imprimir
    $readyToPrintCount = 0;

    // Verificar si se ha enviado una solicitud de impresión
    if(isset($_POST['print_image'])) {
        // Obtener el nombre de la imagen a imprimir
        $imageName = $_POST['image_name'];

        // Marcar la imagen como impresa
        if(isset($jsonData[$imageName])) {
            $jsonData[$imageName] = true;

            // Actualizar el archivo JSON con los nuevos datos
            file_put_contents($jsonFile, json_encode($jsonData));

            // Incrementar el contador si la imagen aún no estaba marcada como impresa
            if (!$jsonData[$imageName]) {
                $readyToPrintCount--;
            }

            // Ahora podríamos enviar la imagen a la impresora aquí, pero como no podemos hacerlo directamente desde PHP,
            // simplemente mostramos un mensaje de éxito en este ejemplo
           // echo "<p>La imagen $imageName se ha enviado a la impresora.</p>";
        } else {
            echo "<p>La imagen $imageName no existe en el archivo JSON.</p>";
        }
    }

    // Contar las imágenes listas para imprimir
    foreach ($jsonData as $printed) {
        if (!$printed) {
            $readyToPrintCount++;
        }
    }

    // Buscar la primera imagen disponible y mostrarla
    $imageFound = false;
    foreach ($jsonData as $imageName => $printed) {
        if (!$printed) {
            $imageFound = true;
            // Utilizamos la ruta base para construir la ruta completa de la imagen
            echo "<img src='$imageBasePath$imageName' alt='$imageName'><br>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='image_name' value='$imageName'>";
            // Utilizamos window.print() para imprimir directamente desde la ventana principal
            echo "<button type='button' onclick='window.print()'>Imprimir</button>";
            echo "</form>";
            break; // Detenerse después de encontrar la primera imagen disponible
        }
    }

    if (!$imageFound) {
       // echo "<p>No hay más imágenes disponibles para imprimir.</p>";
    }

    // Mostrar el contador de imágenes listas para imprimir
    echo "<p>Imágenes listas para imprimir: $readyToPrintCount</p>";
    ?>
</div>
</body>
</html>
