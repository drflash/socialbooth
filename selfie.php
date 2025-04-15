<?php
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tomar Foto</title>
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
      max-width: 90%;
      border-radius: 10px;
      display: none;
    }

    #mensajeCamara {
      color: gray;
      text-align: center;
      margin-top: 10px;
    }
  </style>
</head>
<body>

  <img src="images/logosocial.png" alt="Logo" class="logo">
  <video id="video" autoplay muted playsinline></video>
  <p id="mensajeCamara">Esperando permiso para usar la cámara...</p>
  <button id="captureButton">Tomar Foto</button>

  <canvas id="canvas" style="display: none;"></canvas>
  <img id="fotoFinal" src="" alt="Foto final" />

  <script>
    const eventName = "<?= htmlspecialchars($eventName) ?>";

    function startCamera() {
      const video = document.getElementById('video');
      const mensaje = document.getElementById('mensajeCamara');

      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
          video.srcObject = stream;
          mensaje.style.display = 'none';
        })
        .catch(err => {
          console.error('Error al acceder a la cámara:', err);
          mensaje.innerText = 'No se pudo acceder a la cámara. Asegúrate de dar permiso.';
        });
    }

    document.getElementById('captureButton').addEventListener('click', function () {
      const video = document.getElementById('video');

      // Primer clic: activa la cámara
      if (!video.srcObject) {
        startCamera();
        return;
      }

      // Segundo clic: toma la foto
      const canvas = document.getElementById('canvas');
      const ctx = canvas.getContext('2d');
      const width = video.videoWidth;
      const height = video.videoHeight;
      const size = Math.min(width, height);
      canvas.width = size;
      canvas.height = size;
      ctx.drawImage(video, (width - size) / 2, (height - size) / 2, size, size, 0, 0, size, size);
      const dataURL = canvas.toDataURL('image/jpeg');

      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'save_photo.php');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        if (xhr.status === 200) {
          const imagePath = xhr.responseText.trim();
          const fullUrl = '/' + imagePath;

          document.getElementById('fotoFinal').src = fullUrl;
          document.getElementById('fotoFinal').style.display = 'block';
          video.style.display = 'none';
          document.getElementById('captureButton').style.display = 'none';

          verificarFotosTomadas();
        } else {
          console.error('Error al guardar la foto.');
        }
      };
      xhr.send(
        'photo=' + encodeURIComponent(dataURL) +
        '&eventName=' + encodeURIComponent(eventName) +
        '&nombre=' +
        '&whatsapp='
      );
    });

    function verificarFotosTomadas() {
      const xhr = new XMLHttpRequest();
      xhr.open('GET', `/mosa/uploads/${eventName}/config.json`);
      xhr.onload = function () {
        if (xhr.status === 200) {
          const config = JSON.parse(xhr.responseText);
          const todas = config.spaces.every(s => s.foto);
          if (todas) window.location.href = 'gracias.html';
        } else {
          console.error('Error al cargar config.json');
        }
      };
      xhr.send();
    }
  </script>
</body>
</html>
