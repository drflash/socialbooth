<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imagen con Reticula</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        #imagePreview {
            max-width: 60%;
            height: auto;
        }

        button {
            border-radius: 20px;
            border: 1px solid #FF4B2B;
            background-color: #FF4B2B;
            color: #FFFFFF;
            font-size: 12px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in;
        }
    </style>
</head>
<body>
    <?php
    if (isset($_GET['image'], $_GET['columns'], $_GET['rows'], $_GET['eventName'])) {
        $image = $_GET['image'];
        $columns = (int) $_GET['columns'];
        $rows = (int) $_GET['rows'];
        $eventName = $_GET['eventName'];
        $targetSpaces = isset($_GET['targetSpaces']) ? (int) $_GET['targetSpaces'] : null;
        $visorURL = 'foto.php?eventName=' . urlencode($eventName);

        echo "<img src='$image' alt='Preview' id='imagePreview'>";
        echo '<div>';
        echo 'Columnas: ' . $columns . '<br>';
        echo 'Filas: ' . $rows . '<br>';
        echo 'Nombre del Evento: ' . htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8') . '<br>';
        if ($targetSpaces !== null && $targetSpaces > 0) {
            echo 'Cantidad solicitada: ' . $targetSpaces . '<br>';
        }
        echo 'Total de espacios: ' . ($columns * $rows) . '<br>';
        echo '</div>';
        echo "<button id='acceptButton'>Aceptar</button>";
        echo "<script>
                document.getElementById('acceptButton').addEventListener('click', function() {
                    var imageData = {
                        image: '$image',
                        columns: $columns,
                        rows: $rows,
                        totalSpaces: $columns * $rows,
                        requestedSpaces: " . ($targetSpaces !== null && $targetSpaces > 0 ? $targetSpaces : 'null') . ",
                        processed: false
                    };
                    window.open('$visorURL', '_blank');

                    var form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'procesar_json.php';

                    var imageDataInput = document.createElement('input');
                    imageDataInput.type = 'hidden';
                    imageDataInput.name = 'imageData';
                    imageDataInput.value = JSON.stringify(imageData);
                    form.appendChild(imageDataInput);

                    var eventNameInput = document.createElement('input');
                    eventNameInput.type = 'hidden';
                    eventNameInput.name = 'eventName';
                    eventNameInput.value = '$eventName';
                    form.appendChild(eventNameInput);

                    document.body.appendChild(form);
                    form.submit();
                });
              </script>";
    } else {
        echo 'No se han proporcionado todos los datos necesarios.';
    }
    ?>
</body>
</html>