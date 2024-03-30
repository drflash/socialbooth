<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tomar Foto</title>
    <style>
         /* Importa la tipografía Montserrat */
         @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            position: relative;
        }
        h2 {
            margin-top: 20px;
        }
        #video {
            width: 50%;
            height: auto;
            border-radius: 1%;
            overflow: hidden; 
        }
        #captureButton {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 26px;
            font-weight: bold;
        }
        #captureButton:hover {
            background-color: #45a049;
        }
        #counter {
            font-size: 124px;
            font-weight: bold;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
        }
    </style>
</head>
<body>
    <img src="images/logosocial.png" alt="Imagen" width="200">
    <br>
    <video id="video" autoplay muted playsinline></video>
    <button id="captureButton">Tomar Foto</button>
    <canvas style="display: none;" id="canvas" width="300" height="300"></canvas>
    <div id="counter"></div>

    <script>
    // Obtener el nombre del evento desde PHP
    <?php
    $eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
    ?>

    // Obtener acceso a la cámara trasera del usuario
    function startCamera() {
        var video = document.getElementById('video');
        var constraints = {
            video: {
                facingMode: 'environment' // Utiliza la cámara trasera
            }
        };
        navigator.mediaDevices.getUserMedia(constraints)
        .then(function(stream) {
            video.srcObject = stream;
        })
        .catch(function(err) {
            console.error('Error al acceder a la cámara:', err);
        });
    }

    // Función para capturar la foto
    document.getElementById('captureButton').addEventListener('click', function() {
        var video = document.getElementById('video');
        var canvas = document.getElementById('canvas');
        var context = canvas.getContext('2d');
        var aspectRatio = 1; // Aspect ratio 1:1
        var videoWidth = video.videoWidth;
        var videoHeight = video.videoHeight;
        var size = Math.min(videoWidth, videoHeight);
        canvas.width = size;
        canvas.height = size;
        context.drawImage(video, (videoWidth - size) / 2, (videoHeight - size) / 2, size, size, 0, 0, canvas.width, canvas.height);
        var dataURL = canvas.toDataURL('image/jpeg'); // Convertir la imagen a base64

        // Enviar la imagen a PHP para guardarla
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'save_photo.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                console.log(xhr.responseText);

                // Después de guardar la foto, llamar al script para procesar la impresión
                var eventName = "<?php echo isset($_GET['eventName']) ? $_GET['eventName'] : ''; ?>";
                if (eventName) {
                    var xhrPrint = new XMLHttpRequest();
                    xhrPrint.open('GET', 'procesar_impresion.php?eventName=' + encodeURIComponent(eventName), true);
                    xhrPrint.onload = function() {
                        if (xhrPrint.status === 200) {
                            console.log(xhrPrint.responseText);
                        } else {
                            console.error('Error al procesar la impresión.');
                        }
                    };
                    xhrPrint.send();
                } else {
                    console.error('Por favor, proporciona el nombre del evento.');
                }

                // Verificar si todas las fotos están tomadas después de guardar la foto
                verificarFotosTomadas();

            } else {
                console.error('Error al guardar la foto.');
            }
        };
        xhr.send('photo=' + encodeURIComponent(dataURL) + '&eventName=<?php echo $eventName; ?>');
    });

    // Función para activar el contador al presionar el botón de tomar foto
    document.getElementById('captureButton').addEventListener('click', function() {
        var counter = document.getElementById('counter');
        var count = 3; // Iniciar el contador en 3 segundos
        counter.innerHTML = count;
        document.getElementById('captureButton').disabled = true; // Desactivar el botón durante el contador
        var countdown = setInterval(function() {
            count--;
            if (count <= 0) {
                clearInterval(countdown);
                counter.innerHTML = '';
                document.getElementById('captureButton').disabled = false; // Activar el botón después del contador
            } else {
                counter.innerHTML = count;
            }
        }, 1000);
    });

    // Iniciar la cámara al cargar la página
    startCamera();

   // Función para verificar si todas las fotos están tomadas
function verificarFotosTomadas() {
    // Realizar una solicitud GET para obtener el estado de los espacios en config.json
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/mosa/uploads/<?php echo $eventName; ?>/config.json');
    xhr.onload = function() {
        if (xhr.status === 200) {
            var configData = JSON.parse(xhr.responseText);
            var espacios = configData.spaces;
            var todasTomadas = true;
            
            // Verificar si todas las fotos están tomadas
            for (var i = 0; i < espacios.length; i++) {
                if (!espacios[i].foto) {
                    todasTomadas = false;
                    break;
                }
            }
            
            // Realizar acciones basadas en el resultado
            if (todasTomadas) {
                console.log('¡Todas las fotografías han sido tomadas!');
                // Redireccionar a gracias.php si todas las fotos están tomadas
                window.location.href = 'gracias.html';
            } else {
                console.log('Aún quedan fotografías por tomar.');
            }
        } else {
            console.error('Error al cargar el archivo config.json');
        }
    };
    xhr.send();
}
</script>

</body>
</html>
