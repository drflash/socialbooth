<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Fotos</title>
    <link rel="stylesheet" href="/mosa/css/output.css">
</head>
<body>
    <div id="image-container"></div>
    <button id="print-button">Imprimir</button>
    <?php
// Obtener el nombre del evento dinámicamente (suponiendo que se proporciona de alguna manera)
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';

// Luego, más adelante en tu código PHP...

?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var imageContainer = document.getElementById("image-container");
            var printButton = document.getElementById("print-button");
            
            // Función para cargar las imágenes dinámicamente
            function loadImages(eventName) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'obtener_print.php?eventName=' + encodeURIComponent(eventName), true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                // Limpiamos el contenedor de imágenes
                                imageContainer.innerHTML = '';
                                // Iteramos sobre las claves del objeto
                                for (var key in response) {
                                    if (response.hasOwnProperty(key)) {
                                        // Creamos una nueva imagen para cada clave
                                        var img = document.createElement('img');
                                        img.src = response[key]; // La clave es la URL de la imagen
                                        img.alt = key; // Usamos la clave como texto alternativo
                                        imageContainer.appendChild(img);
                                    }
                                }
                            } catch (error) {
                                console.error('Error al analizar la respuesta JSON:', error);
                            }
                        } else {
                            console.error('Error en la solicitud AJAX:', xhr.status);
                        }
                    }
                };
                xhr.send();
            }

            // Función para imprimir la imagen actual
            function printImage(imageUrl) {
                var printWindow = window.open('', '_blank');
                printWindow.document.write('<img src="' + imageUrl + '" />');
                printWindow.print();
            }

            // Cargar las imágenes al cargar la página
            loadImages(<?php echo json_encode($eventName); ?>);

            // Asignar evento de clic al botón de impresión
            printButton.addEventListener("click", function() {
                // Obtener la URL de la imagen actual
                var currentImage = document.querySelector("#image-container img");
                if (currentImage) {
                    printImage(currentImage.src);
                } else {
                    console.error('No se pudo obtener la URL de la imagen actual.');
                }
            });
        });
    </script>
</body>
</html>
