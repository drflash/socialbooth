<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión de Archivos</title>
</head>
<body>
    <?php
        // Variable para almacenar el nombre del evento
        $eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
        if ($eventName) {
            include 'procesar_impresion.php';
        } else {
            echo "Por favor, proporciona el nombre del evento.";
        }
    ?>
</body>
</html>
