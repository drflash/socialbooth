<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imagen con Retícula</title>
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
    <h2>Imagen con Retícula</h2>
    <?php
    if(isset($_GET['image']) && isset($_GET['columns']) && isset($_GET['rows']) && isset($_GET['eventName'])) {
        $image = $_GET['image'];
        $columns = $_GET['columns'];
        $rows = $_GET['rows'];
        $eventName = $_GET['eventName']; // Obtener el nombre del evento
        echo "<img src='$image' alt='Preview' id='imagePreview'>";
        echo "<div>";
        echo "Columnas: $columns<br>";
        echo "Filas: $rows<br>";
        echo "Nombre del Evento: $eventName<br>"; // Mostrar el nombre del evento
        echo "Total de espacios: " . ($columns * $rows) . "<br>";
        echo "</div>";
        echo "<button id='acceptButton'>Aceptar</button>";
        echo "<script>
                document.getElementById('acceptButton').addEventListener('click', function() {
                    // Información para el config.json
                    var imageData = {
                        image: '$image',
                        columns: $columns,
                        rows: $rows,
                        totalSpaces: $columns * $rows,
                        processed: false
                    };

                    // Crear un formulario para enviar los datos
                    var form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'procesar_json.php';

                    // Crear un campo oculto para pasar los datos
                    var imageDataInput = document.createElement('input');
                    imageDataInput.type = 'hidden';
                    imageDataInput.name = 'imageData';
                    imageDataInput.value = JSON.stringify(imageData);
                    form.appendChild(imageDataInput);

                    // Crear un campo oculto para pasar el nombre del evento
                    var eventNameInput = document.createElement('input');
                    eventNameInput.type = 'hidden';
                    eventNameInput.name = 'eventName';
                    eventNameInput.value = '$eventName';
                    form.appendChild(eventNameInput);

                    // Agregar el formulario al cuerpo del documento y enviarlo
                    document.body.appendChild(form);
                    form.submit();
                });
              </script>";
    } else {
        echo "No se han proporcionado todos los datos necesarios.";
    }
    ?>
</body>
</html>
