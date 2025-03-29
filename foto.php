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
      border-radius: 10px;
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
    #qrcode {
      margin-top: 20px;
    }
    img.logo {
      max-width: 200px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

  <img src="images/logosocial.png" alt="Logo" class="logo">
  <video id="video" autoplay muted playsinline></video>
  <button id="captureButton">Tomar Foto</button>
  <input type="text" id="nombre" placeholder="Tu nombre" required style="margin-top: 10px; font-size: 18px; padding: 10px; width: 80%; max-width: 400px;">
<input type="tel" id="whatsapp" placeholder="Tu WhatsApp" required style="margin-top: 10px; font-size: 18px; padding: 10px; width: 80%; max-width: 400px;">

  <canvas id="canvas" style="display: none;"></canvas>
  <div id="qrcode"></div>

  <!-- QR code library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <script>
    const eventName = "<?= htmlspecialchars($eventName) ?>";

    function startCamera() {
      const video = document.getElementById('video');
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => video.srcObject = stream)
        .catch(err => console.error('Error al acceder a la cámara:', err));
    }

    document.getElementById('captureButton').addEventListener('click', function() {
  const video = document.getElementById('video');
  const canvas = document.getElementById('canvas');
  const ctx = canvas.getContext('2d');
  const width = video.videoWidth;
  const height = video.videoHeight;
  const size = Math.min(width, height);
  canvas.width = size;
  canvas.height = size;
  ctx.drawImage(video, (width - size) / 2, (height - size) / 2, size, size, 0, 0, size, size);
  const dataURL = canvas.toDataURL('image/jpeg');

  const nombre = document.getElementById('nombre').value.trim();
  const whatsapp = document.getElementById('whatsapp').value.trim();

  if (!nombre || !whatsapp) {
    alert('Por favor, completa tu nombre y WhatsApp.');
    return;
  }

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'save_photo.php');
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    if (xhr.status === 200) {
      const imageUrl = xhr.responseText.trim();
      mostrarQR(imageUrl);
      verificarFotosTomadas();
      if (eventName) {
        const xhrPrint = new XMLHttpRequest();
        xhrPrint.open('GET', 'procesar_impresion.php?eventName=' + encodeURIComponent(eventName), true);
        xhrPrint.send();
      }
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


    function mostrarQR(imagePath) {
      const qrDiv = document.getElementById('qrcode');
      qrDiv.innerHTML = '';
      const fullUrl = window.location.origin + '/' + imagePath;
      new QRCode(qrDiv, {
        text: fullUrl,
        width: 200,
        height: 200
      });
    }

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

    startCamera();
  </script>
</body>
</html>
