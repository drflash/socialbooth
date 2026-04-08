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
  background-position: center;
  background-size: cover;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

/* Foto flotante que entra/sale por la pantalla */
.floatingPhoto {
  position: fixed;
  z-index: 2000;
  background-position: center;
  background-size: cover;
  border-radius: 16px;
  box-shadow: 0 0 40px rgba(0, 0, 0, 0.85);
  opacity: 0;
  transition:
    top 1.2s ease-in-out,
    left 1.2s ease-in-out,
    opacity 1.2s ease-in-out,
    transform 1.2s ease-in-out;
}

/* Contenedor QR dentro de la foto */
.qrOverlay {
  position: absolute;
  bottom: 8px;
  right: 8px;
  width: 80px;
  height: 80px;
  background: #fff;
  border-radius: 8px;
  padding: 6px;
  box-shadow: 0 0 10px rgba(0,0,0,0.4);
  opacity: 0.95;
}
.qrOverlay img {
  width: 100%;
  height: 100%;
  object-fit: contain;
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
            max-width: 50%;
            max-height: 50%;
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
#randomPhotosContainer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none; /* Para que las imágenes no interfieran con los clics en las celdas */
        }

        .randomPhoto {
            position: absolute;
            width: 100px; /* Tamaño de las imágenes aleatorias */
            height: 100px;
            pointer-events: none; /* Para que las imágenes no interfieran con los clics en las celdas */
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
    var randomPhotosContainer = document.getElementById('randomPhotosContainer');

    var gridSpaces = document.querySelectorAll('.gridSpace');
    var overlay = document.getElementById('overlay');
    var overlayContent = document.getElementById('overlayContent');
    var overlayImage = document
    .getElementById('overlayImage');
    var eventContainer = document.getElementById('eventContainer');


function startFloatingSlideshow() {
  // Solo celdas que tienen foto (las activas)
  const cells = Array.from(document.querySelectorAll('.gridSpace')).filter(cell => {
    return !cell.classList.contains('inactive');
  });

  if (!cells.length) return;

  // Base hacia la carpeta output del evento actual
  const outputBase = 'uploads/<?php echo $eventName; ?>/output/';

  // Creamos la lista de URLs de imágenes mezcladas
  const images = cells
    .map(c => {
      const name = c.dataset.name; // ej. "sbimg_25_16"
      if (!name) return null;
      return outputBase + name + '.jpg'; // ej. uploads/maestre/output/sbimg_25_16.jpg
    })
    .filter(Boolean);

  if (!images.length) return;

  const moveDuration = 1500;  // ms movimiento entrada/salida
  const stayDuration = 2500;  // ms en el centro
  const delayBetween = 1500;   // ms entre fotos

  function randomFrom(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
  }

  function showNextPhoto() {
  const imgUrl = randomFrom(images);
  const dirIn  = randomFrom(['top', 'bottom', 'left', 'right']);
  const dirOut = randomFrom(['top', 'bottom', 'left', 'right']);

  const vw = window.innerWidth;
  const vh = window.innerHeight;

  const size = Math.min(vw, vh) * 0.5;
  const centerTop  = (vh - size) / 2;
  const centerLeft = (vw - size) / 2;
  const margin = 120;

  // Crear el contenedor de la foto
  const fp = document.createElement('div');
  fp.className = 'floatingPhoto';
  fp.style.backgroundImage = `url('${imgUrl}')`;
  fp.style.width = size + 'px';
  fp.style.height = size + 'px';
  fp.style.transform = 'scale(0.9)';
  fp.style.overflow = 'hidden';
  fp.style.position = 'fixed';
// Crear contenedor para el QR
const qrDiv = document.createElement('div');
qrDiv.className = 'qrOverlay';

// Imagen del QR
const qrImg = document.createElement('img');

// Construir la URL completa del archivo (ajusta si tu ruta cambia)
const fileUrl = window.location.origin + '/' + imgUrl;

// Generar QR con un servicio confiable
qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='
             + encodeURIComponent(fileUrl);

// Ver en consola la URL generada (por depuración)
console.log('QR SRC:', qrImg.src);

qrDiv.appendChild(qrImg);
fp.appendChild(qrDiv);

  // Posición inicial aleatoria fuera de pantalla
  let startTop = centerTop;
  let startLeft = centerLeft;
  switch (dirIn) {
    case 'top':    startTop = -size - margin; startLeft = Math.random() * (vw - size); break;
    case 'bottom': startTop = vh + margin;    startLeft = Math.random() * (vw - size); break;
    case 'left':   startLeft = -size - margin; startTop = Math.random() * (vh - size); break;
    case 'right':  startLeft = vw + margin;   startTop = Math.random() * (vh - size); break;
  }
  fp.style.top = startTop + 'px';
  fp.style.left = startLeft + 'px';

  document.body.appendChild(fp);

  // Entrada al centro
  requestAnimationFrame(() => {
    fp.style.top = centerTop + 'px';
    fp.style.left = centerLeft + 'px';
    fp.style.opacity = 1;
    fp.style.transform = 'scale(1)';
  });

  // Mantener 3–4s y luego salida
  setTimeout(() => {
    let endTop = centerTop, endLeft = centerLeft;
    switch (dirOut) {
      case 'top': endTop = -size - margin; endLeft = Math.random() * (vw - size); break;
      case 'bottom': endTop = vh + margin; endLeft = Math.random() * (vw - size); break;
      case 'left': endLeft = -size - margin; endTop = Math.random() * (vh - size); break;
      case 'right': endLeft = vw + margin; endTop = Math.random() * (vh - size); break;
    }
    fp.style.top = endTop + 'px';
    fp.style.left = endLeft + 'px';
    fp.style.opacity = 0;
    fp.style.transform = 'scale(0.9)';

    setTimeout(() => {
      fp.remove();
      setTimeout(showNextPhoto, delayBetween);
    }, moveDuration);
  }, moveDuration + stayDuration);
}


  showNextPhoto();
}

startFloatingSlideshow();



    
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

    
});
</script>
</body>
</html>
