<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Fotos</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            margin: 0;
        }

        #image-container {
            text-align: center;
            position: relative;
        }

        #current-image {
            max-width: 80%;
            max-height: 80vh;
            display: inline-block;
        }

        #logo {
            max-width: 350px;
            margin-bottom: 20px;
        }

        .navigation-button {
            background-color: rgba(255, 255, 255, 0.5);
            border: none;
            cursor: pointer;
            padding: 10px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        #prev-button {
            left: 10px;
            background-color: #04AA6D;
            border: none;
            color: white;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 56px;
            margin: 4px 2px;
        }

        #next-button {
            right: 10px;
            border: none;
            background-color: #04AA6D;
            color: white;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 56px;
            margin: 4px 2px;
       }

        #print-button-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
        }

        #print-button {
            margin-right: 10px;
        }
    </style>
</head>
<body>
<img id="logo" src="images/logosocial.png" alt="Logo">
<?php
// Obtener el nombre del evento de la solicitud GET
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';

// Construir la ruta al archivo print_status.json
$jsonFilePath = 'uploads/' . $eventName . '/output/print_status.json';

// Verificar si el archivo existe
if (file_exists($jsonFilePath)) {
    // Leer el contenido del archivo JSON
    $jsonContent = file_get_contents($jsonFilePath);

    // Decodificar el contenido JSON en un array asociativo
    $imageNames = json_decode($jsonContent, true);
} else {
    // Si el archivo no existe, inicializar el array de nombres de imágenes vacío
    $imageNames = array();
}

// Si se recibe una solicitud POST para marcar la imagen como impresa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el nombre de la imagen que se marcó como impresa
    $imageName = isset($_POST['image']) ? $_POST['image'] : '';

    // Marcar la imagen como impresa (true)
    $imageNames[$imageName] = true;

    // Codificar el array actualizado como JSON
    $updatedJsonContent = json_encode($imageNames, JSON_PRETTY_PRINT);

    // Escribir el JSON actualizado en el archivo
    file_put_contents($jsonFilePath, $updatedJsonContent);
}
?>


    <div id="image-container">
        <img id="current-image" src="">
        <button id="prev-button" class="navigation-button"><</button>
        <button id="next-button" class="navigation-button">></button>
        <div id="print-button-container">
            <button id="print-button">Imprimir</button>
            <span id="print-status"></span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const eventName = "<?php echo $eventName; ?>";
            const imageFolder = 'uploads/' + eventName + '/output/';
            let currentIndex = 0;
            let images = <?php echo json_encode(array_keys($imageNames)); ?>;
            let printStatus = <?php echo json_encode($imageNames); ?>;

            const imageElement = document.getElementById('current-image');
            const prevButton = document.getElementById('prev-button');
            const nextButton = document.getElementById('next-button');
            const printButton = document.getElementById('print-button');
            const printStatusText = document.getElementById('print-status');

            function updateImage() {
                const currentImage = images[currentIndex];
                const imagePath = imageFolder + currentImage;
                imageElement.src = imagePath;

                // Obtener el estado de impresión actual
                const status = printStatus[currentImage];
                printStatusText.textContent = status === false ? 'No impreso' : 'Ya impreso';
            }

            prevButton.addEventListener('click', function() {
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                updateImage();
            });

            nextButton.addEventListener('click', function() {
                currentIndex = (currentIndex + 1) % images.length;
                updateImage();
            });

            printButton.addEventListener('click', function() {
    const currentImage = images[currentIndex];
    const imageToPrint = imageFolder + currentImage;
    const printWindow = window.open(imageToPrint);
    printWindow.onload = function() {
        printWindow.print();
    };
    printStatus[currentImage] = true;

    // Enviar una solicitud POST al servidor para marcar la imagen como impresa
    fetch('actualizar_estado_impresion.php', {
        method: 'POST',
        body: JSON.stringify({ image: currentImage }),
        headers: {
            'Content-Type': 'application/json'
        }
    }).then(response => {
        // Si la solicitud es exitosa, actualizar el estado de impresión en la interfaz
        if (response.ok) {
            updateImage();
        }
    }).catch(error => {
        console.error('Error:', error);
    });
});

            // Inicializar la primera imagen
            updateImage();
        });
    </script>
</body>
</html>
