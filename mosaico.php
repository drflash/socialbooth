<?php
$event = isset($_GET['eventName']) ? preg_replace("/[^a-zA-Z0-9_-]/", "", $_GET['eventName']) : null;
if (!$event || !file_exists("uploads/$event/mosaico_final_$event.jpg")) {
    die("❌ No se encontró el mosaico para el evento <strong>$event</strong>.");
}
$mosaicoPath = "uploads/$event/mosaico_final_$event.jpg";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mosaico Interactivo - <?php echo htmlspecialchars($event); ?></title>
  <style>
    html, body {
      margin: 0;
      height: 100%;
      overflow: hidden;
      background: #111;
    }
    #container {
      width: 100%;
      height: 100%;
      touch-action: none;
      overflow: hidden;
      position: relative;
    }
    #mosaico {
      max-width: none;
      max-height: none;
      transform-origin: 0 0;
      will-change: transform;
      user-select: none;
      -webkit-user-drag: none;
      position: absolute;
      top: 0;
      left: 0;
      transition: transform 0.2s ease;
    }
    #reset {
      font-size: 16px;
      padding: 8px 12px;
      border: none;
      background: #222;
      color: #fff;
      border-radius: 6px;
      cursor: pointer;
      user-select: none;
    }
    #footer {
      user-select: none;
      position: absolute;
      bottom: 10px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(255, 255, 255, 0.95);
      padding: 8px 12px;
      border-radius: 10px;
      display: flex;
      gap: 10px;
      z-index: 10;
    }
    #footer button {
      font-size: 16px;
      padding: 8px 12px;
      border: none;
      background: #222;
      color: #fff;
      border-radius: 6px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div id="container">
    <img id="mosaico" src="<?php echo $mosaicoPath; ?>" alt="Mosaico <?php echo htmlspecialchars($event); ?>" />
  </div>
  <div id="footer">
    <button id="zoomIn">＋</button>
    <button id="zoomOut">－</button>
    <button id="fitView">Vista completa</button>
    <button id="reset">Reset</button>
    <button id="download">Descargar</button>
  </div>

  <script>
    const img = document.getElementById("mosaico");
    const container = document.getElementById("container");
    const resetBtn = document.getElementById("reset");
    const zoomInBtn = document.getElementById("zoomIn");
    const zoomOutBtn = document.getElementById("zoomOut");
    const fitViewBtn = document.getElementById("fitView");
    const downloadBtn = document.getElementById("download");

    let scale = 1;
    let originX = 0;
    let originY = 0;
    let startX = 0;
    let startY = 0;
    let isDragging = false;

    const updateTransform = () => {
      const imgWidth = img.naturalWidth * scale;
      const imgHeight = img.naturalHeight * scale;
      const containerWidth = container.clientWidth;
      const containerHeight = container.clientHeight;

      const minX = Math.min(0, containerWidth - imgWidth);
      const maxX = 0;
      originX = Math.max(minX, Math.min(originX, maxX));

      const minY = Math.min(0, containerHeight - imgHeight);
      const maxY = 0;
      originY = Math.max(minY, Math.min(originY, maxY));

      img.style.transform = `translate(${originX}px, ${originY}px) scale(${scale})`;
    };

    container.addEventListener("wheel", e => {
      e.preventDefault();
      const delta = e.deltaY > 0 ? -0.1 : 0.1;
      scale = Math.min(Math.max(0.3, scale + delta), 5);
      updateTransform();
    });

    container.addEventListener("mousedown", e => {
      isDragging = true;
      startX = e.clientX - originX;
      startY = e.clientY - originY;
    });

    container.addEventListener("mousemove", e => {
      if (!isDragging) return;
      originX = e.clientX - startX;
      originY = e.clientY - startY;
      updateTransform();
    });

    container.addEventListener("mouseup", () => { isDragging = false; });
    container.addEventListener("mouseleave", () => { isDragging = false; });

    container.addEventListener("touchstart", e => {
      if (e.touches.length === 1) {
        isDragging = true;
        startX = e.touches[0].clientX - originX;
        startY = e.touches[0].clientY - originY;
      }
    });

    container.addEventListener("touchmove", e => {
      if (!isDragging || e.touches.length !== 1) return;
      originX = e.touches[0].clientX - startX;
      originY = e.touches[0].clientY - startY;
      updateTransform();
    });

    container.addEventListener("touchend", () => { isDragging = false; });

    resetBtn.addEventListener("click", () => {
      scale = 1;
      originX = 0;
      originY = 0;
      updateTransform();
    });

    zoomInBtn.addEventListener("click", () => {
      scale = Math.min(scale + 0.2, 5);
      updateTransform();
    });

    zoomOutBtn.addEventListener("click", () => {
      scale = Math.max(scale - 0.2, 0.3);
      updateTransform();
    });

    fitViewBtn.addEventListener("click", () => {
      const containerWidth = container.clientWidth;
      scale = containerWidth / img.naturalWidth;
      originX = 0;
      originY = 0;
      updateTransform();
    });

    downloadBtn.addEventListener("click", () => {
      const link = document.createElement("a");
      link.href = img.src;
      link.download = img.src.split("/").pop();
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });

    updateTransform();
  </script>
</body>
</html>
