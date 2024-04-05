<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Imagen</title>
    <style>
        /* Importa la tipografía Montserrat */
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

  
            body {
            margin: 0;
            font-family: 'Montserrat', sans-serif; /* Usa la tipografía Montserrat */
            background-color: #f4f4f4; /* Color de fondo */
        }
        

        /* Estilos para el formulario y los resultados */
        #searchForm,
        #result {
            text-align: center; /* Centrar contenido dentro de estos elementos */
        }


        header {
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        header img {
            width: 350px; /* Ajusta el tamaño de tu logotipo */
        }

         /* Estilo para el contenedor principal */
         .container {
            max-width: 600px; /* Ancho máximo del contenido */
            margin: 0 auto; /* Centrar el contenedor horizontalmente */
            padding: 20px; /* Espaciado interior */
            background-color: white; /* Color de fondo del contenedor */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Sombra */
        }
         /* Estilo para el título */
         h2 {
            text-align: center; /* Centrar el texto */
            margin-bottom: 20px; /* Espaciado inferior */
        }



        /* Estilo para el botón */
        button {
            background-color: #5e5e5e; /* Color de fondo */
            color: white; /* Color del texto */
            border: none; /* Sin borde */
            padding: 10px 20px; /* Espaciado interior */
            cursor: pointer; /* Cambiar el cursor al pasar sobre él */
        }

        /* Estilo para la imagen */
        .result-image {
            display: block; /* Hacer que la imagen sea un bloque */
            margin: 0 auto; /* Centrar la imagen horizontalmente */
            max-width: 100%; /* Ancho máximo de la imagen */
            height: auto; /* Altura automática según el ancho */
            margin-top: 20px; /* Espaciado superior */
        }

        /* Estilo para el formulario */
        form {
            text-align: center; /* Centrar elementos del formulario */
            margin-bottom: 20px; /* Espaciado inferior */
        }

        form label {
            display: block;
            margin-bottom: 10px;
            color: #5e5e5e;
        }

        form input[type="text"],
        form input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #5e5e5e;
            box-sizing: border-box;
        }

     
        /* Estilo para el botón Subir Imagen */
        form input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            box-sizing: border-box;
            background-color: #913aff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 1rem 3rem;
        }

        .image-container {
            display: inline-block;
            margin: 10px;
            border: 1px solid #ccc;
            padding: 5px;
        }
        .image-container img {
            max-width: 570px;
            max-height: 570px;
        }
        .button-container {
    text-align: center;
}

.download-button {
    margin-top: 20px; /* Ajusta el margen superior según sea necesario */
}
    </style>
</head>
<body>
<header>
        <img src="images/logosocial.png" alt="Logo">
    </header>
    <div class="container">
        <h2>Busca tu Imagen</h2>

        <form id="searchForm">
        <label for="eventNameInput">Nombre del Evento:</label>
        <input type="text" id="eventNameInput" name="eventName" required>
        <br>
        <label for="columnInput">Columna:</label>
        <input type="text" id="columnInput" name="column" required>
        <br>
        <label for="rowInput">Fila:</label>
        <input type="text" id="rowInput" name="row" required>
        <br>
        <button type="button" id="downloadButton">Descargar</button>
    </form>

        <div id="result">
            <!-- Aquí se mostrará la imagen buscada -->
        </div>
    </div>
    <script>
document.getElementById('downloadButton').addEventListener('click', function(event) {
    event.preventDefault();

    var eventName = document.getElementById('eventNameInput').value;
    var column = document.getElementById('columnInput').value;
    var row = document.getElementById('rowInput').value;

    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                console.log('Imagen recibida correctamente.');

                // Descargar la imagen
                var downloadLink = document.createElement('a');
                downloadLink.href = 'data:image/jpeg;base64,' + xhr.responseText;
                downloadLink.download = 'imagen_descargada.jpg'; // Nombre genérico
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            } else {
                console.error('Error al recibir la imagen:', xhr.statusText);
            }
        }
    };
    xhr.open('GET', 'mifoto.php?eventName=' + eventName + '&column=' + column + '&row=' + row);
    xhr.send();
});


</script>
  <?php
            // Verificar si se han enviado los parámetros necesarios
            if(isset($_GET['column']) && isset($_GET['row']) && isset($_GET['eventName'])) {
                $column = $_GET['column'];
                $row = $_GET['row'];
                $eventName = $_GET['eventName'];

                $imageName = "sbimg_${column}_${row}.jpg";
                $imagePath = "uploads/$eventName/originales/$imageName";
                $framePath = "uploads/$eventName/frame.png";
                $configPath = "uploads/$eventName/config.json";

                // Verificar si la foto ya se ha tomado en el archivo config.json
                if(file_exists($configPath)) {
                    $configData = json_decode(file_get_contents($configPath), true);
                    if(isset($configData[$imageName]) && $configData[$imageName]['foto']) {
                        // La foto ya se ha tomado
                        echo '<div class="container">';
                        echo '<div class="image-container">';
                        echo '<img src="' . $imagePath . '" alt="Imagen">';
                        echo '</div>';
                        echo '<p>La foto ya se ha tomado.</p>';
                        echo '</div>';
                        echo '<script>document.getElementById("searchForm").style.display = "none";</script>'; // Ocultar el formulario

                        exit; // Terminar la ejecución del script
                    }
                }

                if(file_exists($imagePath) && file_exists($framePath)) {
                    // Crear recursos de imagen a partir de los archivos
                    $image = imagecreatefromjpeg($imagePath);
                    $frame = imagecreatefrompng($framePath);

                    // Obtener dimensiones de la imagen y el marco
                    $imageWidth = imagesx($image);
                    $imageHeight = imagesy($image);
                    $frameWidth = imagesx($frame);
                    $frameHeight = imagesy($frame);

                    // Calcular las coordenadas para centrar la imagen dentro del marco
                    $x = ($frameWidth - $imageWidth) / 2;
                    $y = ($frameHeight - $imageHeight) / 2;

                    // Crear una nueva imagen combinada
                    $combined = imagecreatetruecolor($frameWidth, $frameHeight);

                    // Copiar el marco en la nueva imagen
                    imagecopy($combined, $frame, 0, 0, 0, 0, $frameWidth, $frameHeight);

                    // Copiar la imagen dentro del marco, centrada
                    imagecopy($combined, $image, $x, $y, 0, 0, $imageWidth, $imageHeight);

                    // Definir el nombre del archivo de descarga
                    $downloadFileName = "mifoto_$eventName.jpg";
                    

                    // Mostrar la imagen combinada
                    ob_start();
                    imagejpeg($combined, null, 100);
                    $imageData = ob_get_clean();
                    $base64 = 'data:image/jpeg;base64,' . base64_encode($imageData);

                    echo '<div class="container">';
                    echo '<div class="image-container">';
                    echo '<img src="' . $base64 . '" alt="Imagen Combinada">';
                    echo '</div>';

                    // Agregar el botón de descarga con el nombre personalizado
                    echo '<div class="button-container">';
                    echo '<a href="' . $base64 . '" download="' . $downloadFileName . '"><button class="download-button">Descargar Imagen</button></a>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo 'No se encontró la imagen o el marco.';
                }
            }
?>

</body>
</html>
