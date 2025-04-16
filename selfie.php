<?php
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tomar Selfie</title>
  <style>
    @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');
    body {
      font-family: 'Montserrat', sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px;
      margin: 0;
    }

    video {
      width: 100%;
      max-width: 480px;
      aspect-ratio: 4 / 3;
      background-color: #1e1e2f;
      border-radius: 10px;
      object-fit: cover;
      display: block;
    }

    #captureButton {
      margin: 20px 0;
      padding: 12px 24px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 22px;
      font-weight: bold;
    }

    #captureButton:hover {
      background-color: #45a049;
    }

    img.logo {
      max-width: 200px;
      margin-bottom: 20px;
    }

    #fotoFinal {
      margin-top: 20px;
      max-width: 90vw;
      max-height: 90vw;
      width: auto;
      height: auto;
      aspect-ratio: 1 / 1;
      border-radius: 10px;
      display: none;
      object-fit: contain;
    }

    #procesando {
      font-size: 20px;
      color: #555;
      margin-top: 20px;
      display: none;
    }

    #botonDescargar {
      margin-top: 10px;
      padding: 12px 24px;
      font-size: 18px;
      background-color: #2196F3;
      color: white;
      border: none;
      border-radius: 6px;
      text-decoration: none;
      display: none;
    }
  </style>
</head>
<body>

  <img src="images/logosocial.png" alt="Logo" class="logo">
  <video id="video" autoplay muted playsinline></video>
  <button id="captureButton">Tomar Selfie</button>

  <canvas id="canvas" style="display: none;"></canvas>
  <p id="procesando">Procesando selfie…</p>
  <img id="fotoFinal" src="" alt="Foto con marco" />
  <a id="botonDescargar" download="selfie.jpg">Descargar selfie</a>

  <script>
    const eventName = "<?= htmlspecialchars($eventName) ?>";

    function startCamera() {
      const video = document.getElementById('video');
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
        .then(stream => video.srcObject = stream)
        .catch(err => console.error('Error al acceder a la cámara:', err));
    }

    function verificarFotosTomadas() {
      const configUrl = `${window.location.origin}${window.location.pathname.split('/').slice(0, -1).join('/')}/uploads/${eventName}/config.json`;

      const xhr = new XMLHttpRequest();
      xhr.open('GET', configUrl);
      xhr.onload = function () {
        if (xhr.status === 200) {
          const config = JSON.parse(xhr.responseText);
          const total = config.spaces.length;
          const tomadas = config.spaces.filter(s => s.foto).length;
          console.log(`📸 Selfies tomadas: ${tomadas} de ${total}`);

          if (tomadas === total) {
            alert("🎉 Todas las selfies han sido tomadas.");
          }
        } else {
          console.warn('⚠️ No se encontró config.json. Revisa ruta:', configUrl);
        }
      };
      xhr.send();
    }

    document.getElementById('captureButton').addEventListener('click', function () {
      const video = document.getElementById('video');
      const captureButton = document.getElementById('captureButton');
      const processingMsg = document.getElementById('procesando');

      if (!video.srcObject) {
        startCamera();
        return;
      }

      // Ocultar cámara y botón
      video.style.display = 'none';
      captureButton.style.display = 'none';
      processingMsg.style.display = 'block';

      const canvas = document.getElementById('canvas');
      const ctx = canvas.getContext('2d');
      const width = video.videoWidth;
      const height = video.videoHeight;
      const size = Math.min(width, height);
      canvas.width = size;
      canvas.height = size;
      ctx.drawImage(video, (width - size) / 2, (height - size) / 2, size, size, 0, 0, size, size);
      const dataURL = canvas.toDataURL('image/jpeg');

      const nombre = "Selfie / No aplica";
      const whatsapp = "123456";

      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'save_photo.php');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        if (xhr.status === 200) {
          const imagePath = xhr.responseText.trim();
          const basePath = `${window.location.origin}${window.location.pathname.split('/').slice(0, -1).join('/')}`;
          const fullUrl = `${basePath}/${imagePath}`;

          if (eventName) {
            const xhrPrint = new XMLHttpRequest();
            xhrPrint.open('GET', 'procesar_impresion.php?eventName=' + encodeURIComponent(eventName), true);
            xhrPrint.send();
          }

          // Esperar 10 segundos y mostrar resultado
          setTimeout(() => {
            document.getElementById('fotoFinal').src = fullUrl;
            document.getElementById('fotoFinal').style.display = 'block';
            processingMsg.style.display = 'none';

            const botonDescargar = document.getElementById('botonDescargar');
            botonDescargar.href = fullUrl;
            botonDescargar.style.display = 'inline-block';

            verificarFotosTomadas();
          }, 10000);
        } else {
          console.error('Error al guardar la foto.');
        }
      };

      xhr.send(
        'photo=' + encodeURIComponent(dataURL) +
        '&eventName=' + encodeURIComponent(eventName) +
        '&nombre=' + encodeURIComponent(nombre) +
        '&whatsapp=' + encodeURIComponent(whatsapp)
      );
    });

    startCamera();
  </script>
</body>
</html>
