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

        // Redirigir para cargar la página nuevamente
        header("Location: {$_SERVER['REQUEST_URI']}");
        exit;
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
foreach ($jsonData as $imageName => $printed) {
    if (!$printed) {
        // Utilizamos la ruta base para construir la ruta completa de la imagen
        echo "<img src='$imageBasePath$imageName' alt='$imageName'><br>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='image_name' value='$imageName'>";
        // Utilizamos JavaScript para abrir una ventana emergente con la imagen y solicitar la impresión directamente desde allí
        echo "<button type='button' onclick='printImage(\"$imageBasePath$imageName\")'>Imprimir</button>";
        echo "</form>";
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
                               '<img src="/images/logoestadio.png" style="position: absolute; bottom: 0; left: 100; width: 400px; height: auto;"></body></html>');
    printWindow.document.close();
    printWindow.print();

    // Cerrar la ventana después de 1 segundo (1000 milisegundos)
    setTimeout(function(){ printWindow.close(); }, 1000);
}
</script>
</body>
</html>
