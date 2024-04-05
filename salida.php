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
</style>
</head>
<body>
<div class="container">
    <?php
    // Evento dinámico
    $eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';

    // Ruta del archivo JSON
    $jsonFile = __DIR__ . "/uploads/$eventName/output/print_status.json";

    // Leer el archivo JSON y convertirlo a un array asociativo
    $jsonData = json_decode(file_get_contents($jsonFile), true);

    // Ruta base para las imágenes
    $imageBasePath = "/mosa/uploads/$eventName/output/";

    // Verificar si se ha enviado una solicitud de impresión
    if(isset($_POST['print_image'])) {
        // Obtener el nombre de la imagen a imprimir
        $imageName = $_POST['image_name'];

        // Marcar la imagen como impresa
        if(isset($jsonData[$imageName])) {
            $jsonData[$imageName] = true;

            // Actualizar el archivo JSON con los nuevos datos
            if (file_put_contents($jsonFile, json_encode($jsonData)) !== false) {
                echo "El estado de impresión de la imagen $imageName se ha actualizado correctamente en el archivo JSON.";
            } else {
                echo "Error al actualizar el estado de impresión de la imagen $imageName en el archivo JSON.";
            }
        } else {
            echo "<p>La imagen $imageName no existe en el archivo JSON.</p>";
        }
    }

    // Buscar la primera imagen disponible y mostrarla
    $readyToPrintCount = 0;
    foreach ($jsonData as $imageName => $printed) {
        if (!$printed) {
            // Utilizamos la ruta base para construir la ruta completa de la imagen
            echo "<img src='$imageBasePath$imageName' alt='$imageName'><br>";
            echo "<form method='post' action=''>";
            echo "<input type='hidden' name='image_name' value='$imageName'>";
            // Utilizamos JavaScript para abrir una ventana emergente con la imagen y solicitar la impresión directamente desde allí
            echo "<button type='submit' name='print_image' onclick='printImage(\"$imageBasePath$imageName\")'>Imprimir</button>";
            echo "</form>";
            $readyToPrintCount++;
            break; // Detenerse después de encontrar la primera imagen disponible
        }
    }

    if ($readyToPrintCount == 0) {
        echo "<p>No hay más imágenes disponibles para imprimir.</p>";
    } else {
        // Mostrar el contador de imágenes listas para imprimir
        echo "<p>Imágenes listas para imprimir: $readyToPrintCount</p>";
    }
    ?>
</div>

<script>
function printImage(imageUrl) {
    var printWindow = window.open('', '_blank', 'width=800,height=600');
    printWindow.document.write('<html><head><title>Imprimir Imagen</title></head><body style="margin: 0; padding: 0;"><img src="' + imageUrl + '" style="width: 100%; height: auto;">' +
                               '<img src="/mosa/images/logoestadio.png" style="position: absolute; bottom: 0; left: 50; width: 300px; height: auto;"></body></html>');
    printWindow.document.close();
    printWindow.print();

    // Cerrar la ventana después de 1 segundo (1000 milisegundos)
    setTimeout(function(){ printWindow.close(); }, 1000);
}
</script>

</body>
</html>
