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
            background-color: black;
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
            background-image: url('/images/frame.png'); /* Ruta de la imagen de fondo */
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
        .gridSpace {
    transition: background-color 0.3s ease;
}
#printButton {
    background-color: #007bff; /* Color de fondo azul */
    color: white; /* Color de texto blanco */
    padding: 10px 20px; /* Ajustar el relleno según sea necesario */
    border: none; /* Quitar el borde */
    border-radius: 5px; /* Agregar bordes redondeados */
    cursor: pointer; /* Cambiar el cursor al pasar por encima */
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
        $backgroundImageUrl = "uploads/$eventName/frame.png";

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
        } else {
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
        <button id="printButton">Imprimir</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var gridSpaces = document.querySelectorAll('.gridSpace');
    var overlay = document.getElementById('overlay');
    var overlayContent = document.getElementById('overlayContent');
    var overlayImage = document
    .getElementById('overlayImage');
    var eventContainer = document.getElementById('eventContainer');

     // Variable para almacenar la ruta de la imagen de fondo del overlay
     var backgroundImageUrl = `uploads/<?php echo $eventName; ?>/frame.png`;

// Seleccionar el elemento del overlay
var overlay = document.getElementById('overlay');

// Aplicar la ruta de la imagen de fondo dinámica al estilo del overlay
overlay.style.backgroundImage = `url('${backgroundImageUrl}')`;

    // Variable para almacenar el nombre del evento
    var eventName = "<?php echo isset($_GET['eventName']) ? $_GET['eventName'] : ''; ?>";

    // Objeto para almacenar el estado actual de "foto" por nombre de celda
    var currentFotoStatus = {};

        // Verificar si config.json está completo

    function checkIfConfigComplete() {
        fetch("uploads/<?php echo $eventName; ?>/config.json")
            .then(response => response.json())
            .then(data => {
                if (data && data.spaces) {
                    const allPhotosAvailable = data.spaces.every(space => space.foto === true);
                    if (allPhotosAvailable) {
                        clearInterval(interval); // Detener la verificación periódica
                    }
                }
            })
            .catch(error => console.error('Error al verificar si config.json está completo:', error));
    }

     // Verificar si config.json está completo cada 10 segundos
    const checkConfigInterval = setInterval(checkIfConfigComplete, 10000);
  
  // Event listener para el botón de imprimir
  document.getElementById('printButton').addEventListener('click', function() {
        var canvas = document.createElement('canvas');
        canvas.width = 6 * 300; // Ancho de una fotografía de 4x6 pulgadas en píxeles (300 ppi)
        canvas.height = 4 * 300; // Alto de una fotografía de 4x6 pulgadas en píxeles (300 ppi)
        var ctx = canvas.getContext('2d');

        // Dibujar el marco en el canvas
        var frameImage = new Image();
        frameImage.src = backgroundImageUrl; // Utiliza la ruta de la imagen de fondo del overlay
        frameImage.onload = function() {
            ctx.drawImage(frameImage, 0, 0, canvas.width, canvas.height);
            
            // Dibujar la imagen del evento centrada en el canvas
            var eventImage = new Image();
            eventImage.src = document.getElementById('eventImage').src; // Obtener la ruta de la imagen del evento
            eventImage.onload = function() {
                var offsetX = (canvas.width - eventImage.width) / 2;
                var offsetY = (canvas.height - eventImage.height) / 2;
                ctx.drawImage(eventImage, offsetX, offsetY);
                
                // Crear un enlace para descargar la imagen resultante
                var link = document.createElement('a');
                link.download = 'imagen_impresa.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            };
        };
    });
    console.log("¿El botón de imprimir está visible?", document.getElementById('printButton').offsetParent !== null);

    // Función para recargar la página cada x segundos
function reloadPageEvery(seconds) {
    setTimeout(function() {
        location.reload(); // Recargar la página
    }, seconds * 1000); // Convertir segundos a milisegundos
}

// Llamar a la función para recargar la página cada 60 segundos (1 minuto)
reloadPageEvery(10); // Puedes ajustar el intervalo de tiempo según tus necesidades

    function loadWithFadeIn(imageSrc) {
    console.log("Cargando nueva imagen:", imageSrc); // Registro para verificar si la función se llama correctamente
    overlayImage.style.transition = "opacity 1s ease-in-out";
    overlayImage.style.opacity = 0;
    overlayImage.onload = function() {
        console.log("Nueva imagen cargada:", imageSrc); // Registro para verificar si la imagen se carga correctamente
        overlayImage.style.opacity = 1;
    };
    overlayImage.src = imageSrc;
}

function checkForChanges() {
    fetch("verificar_cambios.php?eventName=" + eventName)
        .then(response => response.json())
        .then(data => {
            if (data && data.spaces) {
                data.spaces.forEach(function(space) {
                    var name = space.name;
                    var foto = space.foto;

                    if (foto !== currentFotoStatus[name]) {
                        currentFotoStatus[name] = foto;
                        var fotoPath = "uploads/<?php echo $eventName; ?>/originales/" + name;
                        if (foto) {
                            loadWithFadeIn(fotoPath); // Cargar la nueva imagen con animación
                            
                            // Actualizar el contenido de la celda
                            var gridSpace = document.querySelector('.gridSpace[data-name="' + name + '"]');
                            if (gridSpace) { // Verificar si el elemento existe
                                gridSpace.style.backgroundImage = "url('" + fotoPath + "')";
                                gridSpace.classList.remove('inactive');
                                gridSpace.style.backgroundColor = 'transparent'; // Quitar el fondo negro
                                console.log("Eliminando clase 'inactive' y fondo negro del gridSpace:", name);
                            }
                        } else {
                            // Si no hay imagen, mostrar fondo negro
                            var gridSpace = document.querySelector('.gridSpace[data-name="' + name + '"]');
                            if (gridSpace) { // Verificar si el elemento existe
                                gridSpace.style.backgroundImage = ''; // Quitar cualquier imagen de fondo existente
                                gridSpace.classList.remove('inactive');
                                gridSpace.style.backgroundColor = 'black'; // Establecer el fondo negro
                                console.log("Eliminando clase 'inactive' y estableciendo fondo negro del gridSpace:", name);
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error al obtener la configuración del evento:', error));
}


    var interval = setInterval(checkForChanges, 5000); // Verifica cambios cada 5 segundos
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
