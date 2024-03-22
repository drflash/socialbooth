    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Visor de Configuración</title>
        <style>
            body, html {
                height: 100%;
                margin: 0;
            }
            #eventContainer {
                position: relative;
                width: 100%;
                height: 100%;
                z-index: 1; /* Asegurar que el contenedor de eventos esté por encima del overlay */
            }
            #eventImage {
                display: block;
                width: 100%;
                height: 100%;
                /*object-fit: contain;  Para que la imagen mantenga su relación de aspecto */
            }
            .gridSpace {
                position: absolute;
                background-color: transparent;
                transition: background-color 0.3s ease;
            }
            .gridSpace:hover {
                background-color: rgba(255, 255, 255, 0.5);
            }
            .overlay {
                display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
                width: 800px; /* Ancho de la ventana emergente */
        height: 800px; /* Alto de la ventana emergente */
        background-image: url('/mosa/images/frame.png'); /* Ruta de la imagen de fondo */
        background-size: cover; /* Ajustar la imagen de fondo al tamaño del div */
        background-position: center; /* Centrar la imagen de fondo */
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            .overlay-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background-color: white;
                padding: 20px;
                border-radius: 5px;
                z-index: 1000;
            }
            #overlayImage {
                max-width: 100%;
                max-height: 100%;
            }
            .gridSpace.inactive {
            pointer-events: none;
        }
        @keyframes fadeInOut {
    0% {
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
}

.fadeInOut {
    animation: fadeInOut 3s ease-in-out infinite;
}
        
        </style>
    </head>
    <body>
        <div id="eventContainer">
        <?php
            if(isset($_GET['image']) && isset($_GET['columns']) && isset($_GET['rows']) && isset($_GET['eventName'])) {
                $image = $_GET['image'];
                $columns = $_GET['columns'];
                $rows = $_GET['rows'];
                $eventName = $_GET['eventName']; // Obtener el nombre del evento
                echo "<img src='$image' alt='Evento' id='eventImage'>";
                echo "<div id='gridOverlay'>";
                if (file_exists("uploads/$eventName/config.json")) {
                    $configData = json_decode(file_get_contents("uploads/$eventName/config.json"), true);
                    if (isset($configData['spaces'])) {
                        $spaceIndex = 0;
                        for ($i = 0; $i < $rows; $i++) {
                            for ($j = 0; $j < $columns; $j++) {
                                $name = isset($configData['spaces'][$spaceIndex]['name']) ? $configData['spaces'][$spaceIndex]['name'] : ''; // Obtener el nombre o dejarlo vacío si no existe
                                $foto = isset($configData['spaces'][$spaceIndex]['foto']) ? $configData['spaces'][$spaceIndex]['foto'] : false; // Obtener el valor de 'foto' para esta celda
                                $fotoPath = "uploads/$eventName/originales/$name.jpg"; // Ruta de la imagen
                                
                                // Establecer la opacidad dependiendo si hay una imagen
                                $opacity = $foto ? 'opacity: 0.3;' : ''; // 50% de opacidad si hay una imagen
                
                                // Verificar si 'foto' es verdadero y si existe la imagen
    if ($foto && file_exists($fotoPath)) {
        echo "<div class='gridSpace' data-name='$name' style='left: " . ($j * 100 / $columns) . "%; top: " . ($i * 100 / $rows) . "%; width: " . (100 / $columns) . "%; height: " . (100 / $rows) . "%; background-image: url($fotoPath); background-size: cover; $opacity'></div>";
    } else {
        echo "<div class='gridSpace inactive' data-name='$name' style='left: " . ($j * 100 / $columns) . "%; top: " . ($i * 100 / $rows) . "%; width: " . (100 / $columns) . "%; height: " . (100 / $rows) . "%; $opacity'></div>";
    }

                                $spaceIndex++;
                            }
                        }
                    }
                }
                else {
                    echo "No se encontró el archivo de configuración.";
                }
                echo "</div>";
            } else {
                echo "No se han proporcionado todos los datos necesarios.";
            }
            ?>
        </div>
        <div class="overlay" id="overlay">
            <div class="overlay-content" id="overlayContent">
                <img src="" alt="Imagen seleccionada" id="overlayImage">
            </div>
        </div>

        <script>
            
        document.addEventListener("DOMContentLoaded", function() {
        var gridSpaces = document.querySelectorAll('.gridSpace');
        var overlay = document.getElementById('overlay');
        var overlayContent = document.getElementById('overlayContent');
        var overlayImage = document.getElementById('overlayImage');
        var eventContainer = document.getElementById('eventContainer');
        var fullscreenButton = document.getElementById('fullscreenButton');

        // Objeto para almacenar el estado actual de "foto" por nombre de celda
        var currentFotoStatus = {};

        function loadWithFadeIn(imageSrc) {
        overlayImage.style.transition = "opacity 1s ease-in-out";
        overlayImage.style.opacity = 0;
        setTimeout(function() {
            overlayImage.src = imageSrc;
            overlayImage.style.opacity = 1;
        }, 100); // Ajusta este valor según sea necesario
    }

        function checkForChanges() {
            fetch("uploads/<?php echo $eventName; ?>/config.json")
                .then(response => response.json())
                .then(data => {
                    if (data && data.spaces) {
                        data.spaces.forEach(function(space) {
                            var name = space.name;
                            var foto = space.foto;

                            if (foto !== currentFotoStatus[name]) {
                                currentFotoStatus[name] = foto;
                                var fotoPath = "uploads/<?php echo $eventName; ?>/originales/" + name + ".jpg";
                                if (foto) {
                                    loadWithFadeIn(fotoPath);
                                }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error al obtener la configuración del evento:', error));
        }

        var interval = setInterval(checkForChanges, 5000);
        checkForChanges();

        gridSpaces.forEach(function(gridSpace) {
    gridSpace.addEventListener('click', function() {
        var name = gridSpace.getAttribute('data-name');
        var fotoPath = "uploads/<?php echo $eventName; ?>/originales/" + name + ".jpg";
        
        // Verificar si el nombre de la celda contiene la extensión de la imagen
        var isImage = name.toLowerCase().endsWith('.jpg') || name.toLowerCase().endsWith('.jpeg') || name.toLowerCase().endsWith('.png');
        
        if (isImage) {
            // Si es una imagen, establecer el atributo src del elemento img
            overlayImage.src = fotoPath;
            overlayImage.classList.add('fadeIn'); // Añadir clase de animación
            overlayImage.style.display = 'block'; // Mostrar la imagen
            overlayContent.innerHTML = ''; // Limpiar el contenido existente
        } else {
            // Si es texto, mostrar el nombre de la celda en overlayContent
            overlayContent.innerHTML = "<img src='" + fotoPath + "' alt='" + name + "'>"; // Crear una etiqueta img con la ruta de la imagen
            overlayImage.style.display = 'none'; // Ocultar la imagen
        }

        // Función para recargar la página
function reloadPage() {
    location.reload(); // Recargar la página
}

// Llamar a la función reloadPage cada 20 segundos (20000 milisegundos)
setInterval(reloadPage, 20000);
        
        overlay.style.display = 'block'; // Mostrar el overlay
    });
});

                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        overlay.style.display = 'none';
                    }
                });

               

                // Salir de pantalla completa
                document.addEventListener('fullscreenchange', handleFullscreenChange);
                document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
                document.addEventListener('MSFullscreenChange', handleFullscreenChange);

                function handleFullscreenChange() {
                    var fullscreenElement = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
                    if (fullscreenElement === eventContainer) {
                        overlay.style.display = 'none';
                    } else {
                        overlay.style.display = 'block';
                    }
                }
                
            });
            
        </script>
    </body>
    </html>
