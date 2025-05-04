<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Visor Animado</title>
  <style>
    body, html { margin: 0; padding: 0; width: 100%; height: 100%; background: black; overflow: hidden; }
    #eventContainer { position: relative; width: 100%; height: 100%; overflow: hidden; }
    #eventImage { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gridSpace {
      position: absolute;
      background-color: black;
      background-size: cover;
      background-position: center;
      transition: background-color 0.3s;
    }
    #overlayLoading {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: black;
      z-index: 9999;
      pointer-events: none;
    }
  </style>
</head>
<body>
<div id="eventContainer">
<?php
if (isset($_GET['image'], $_GET['columns'], $_GET['rows'], $_GET['eventName'])) {
  $image = $_GET['image'];
  $columns = (int)$_GET['columns'];
  $rows = (int)$_GET['rows'];
  $eventName = $_GET['eventName'];

  echo '<img src="'.$image.'" id="eventImage">';
  echo "<div id='gridOverlay'>";

  if (file_exists("uploads/$eventName/config.json")) {
    $configData = json_decode(file_get_contents("uploads/$eventName/config.json"), true);
    if (isset($configData['spaces'])) {
      $spaceIndex = 0;
      for ($i = 0; $i < $rows; $i++) {
        for ($j = 0; $j < $columns; $j++) {
          $name = isset($configData['spaces'][$spaceIndex]['name']) ? $configData['spaces'][$spaceIndex]['name'] : '';
          $foto = isset($configData['spaces'][$spaceIndex]['foto']) ? $configData['spaces'][$spaceIndex]['foto'] : false;
          $fotoPath = "uploads/$eventName/originales/$name.jpg";
          $opacity = $foto ? 'opacity:0.3;' : '';

          echo "<div class='gridSpace' data-name='$name' style='left:".($j*100/$columns)."%;top:".($i*100/$rows)."%;width:".(100/$columns)."%;height:".(100/$rows)."%;$opacity";
          if ($foto && file_exists($fotoPath)) {
              echo "background-image: url($fotoPath); background-size: cover; background-position: center;'></div>";
          } else {
              echo "background-color: black;'></div>"; // <-- AQUÍ FORZAMOS QUE SEA NEGRO
          }
          
          $spaceIndex++;
        }
      }
    }
  }
  echo "</div>";
} else {
  echo "Faltan parámetros.";
}
?>
</div>
<div id="overlayLoading"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
const eventName = "<?php echo $eventName; ?>";
const gridSpaces = document.querySelectorAll('.gridSpace');
const overlayLoading = document.getElementById('overlayLoading');
let allPhotosReady = false;

function animarNuevaFoto(fotoPath, gridSpace) {
  const animImg = document.createElement('img');
  animImg.src = fotoPath;
  animImg.style.position = 'fixed';
  animImg.style.top = '50%';
  animImg.style.left = '50%';
  animImg.style.width = '400px';
  animImg.style.height = '400px';
  animImg.style.transform = 'translate(-50%, -50%)';
  animImg.style.objectFit = 'cover';
  animImg.style.borderRadius = '10px';
  animImg.style.zIndex = 10000;
  document.body.appendChild(animImg);

  const rect = gridSpace.getBoundingClientRect();

  gsap.to(animImg, {
    duration: 1.2,
    top: rect.top + 'px',
    left: rect.left + 'px',
    width: rect.width + 'px',
    height: rect.height + 'px',
    ease: "power2.inOut",
    onComplete: () => {
      gridSpace.style.backgroundImage = `url('${fotoPath}')`;
      gridSpace.style.opacity = 0.8;
      gridSpace.classList.remove('inactive');
      animImg.remove();
    }
  });
}

function checkForChanges() {
  fetch(`uploads/${eventName}/config.json?t=${Date.now()}`)
    .then(res => res.json())
    .then(data => {
      if (data && data.spaces) {
        let allComplete = true;
        data.spaces.forEach(space => {
          const name = space.name;
          const foto = space.foto;
          const gridSpace = document.querySelector(`.gridSpace[data-name='${name}']`);
          const fotoPath = `uploads/${eventName}/originales/${name}.jpg?t=${Date.now()}`;

          if (foto && gridSpace && !gridSpace.style.backgroundImage) {
            animarNuevaFoto(fotoPath, gridSpace);
          }
          if (!foto) allComplete = false;
        });

        if (allComplete && !allPhotosReady) {
          allPhotosReady = true;
          startMosaicAnimation();
          clearInterval(interval);
          gsap.to(overlayLoading, { opacity: 0, duration: 1, onComplete: () => overlayLoading.remove() });
        }
      }
    });
}
let mosaicAnimationInterval = null; // Guardar el interval aquí

function startMosaicAnimation() {
  if (mosaicAnimationInterval) clearInterval(mosaicAnimationInterval);

  mosaicAnimationInterval = setInterval(() => {
    const activeSpaces = Array.from(document.querySelectorAll('.gridSpace')).filter(gs => gs.style.backgroundImage);
    const randomSpace = activeSpaces[Math.floor(Math.random() * activeSpaces.length)];
    
    if (randomSpace) {
      flyToCenterAndBack(randomSpace);
    }
  }, 2500);
}

function flyToCenterAndBack(gridSpace) {
  // Verificar si tiene imagen
  if (!gridSpace.style.backgroundImage || gridSpace.style.backgroundImage === 'none') {
    return; // No hacer nada si no hay imagen
  }

  const rect = gridSpace.getBoundingClientRect();
  const clone = gridSpace.cloneNode(true);
  document.body.appendChild(clone);

  clone.style.position = 'fixed';
  clone.style.top = rect.top + 'px';
  clone.style.left = rect.left + 'px';
  clone.style.width = rect.width + 'px';
  clone.style.height = rect.height + 'px';
  clone.style.zIndex = 9999;
  clone.style.margin = '0';
  clone.style.transform = 'none';
  clone.style.opacity = 0.8;

  const centerX = window.innerWidth / 2 - rect.width / 2;
  const centerY = window.innerHeight / 2 - rect.height / 2;

  // Animar al centro
  gsap.to(clone, {
    duration: 0.8,
    top: centerY + 'px',
    left: centerX + 'px',
    scale: 15.7,
    opacity: 1,
    ease: "power2.out",
    onComplete: () => {
      setTimeout(() => {
        // Regresar a su lugar
        gsap.to(clone, {
          duration: 0.8,
          top: rect.top + 'px',
          left: rect.left + 'px',
          scale: 1,
          opacity: 0.8,
          ease: "power2.inOut",
          onComplete: () => clone.remove()
        });
      }, 800);
    }
  });
}

function stopMosaicAnimation() {
  if (mosaicAnimationInterval) {
    clearInterval(mosaicAnimationInterval);
    mosaicAnimationInterval = null;
  }
}

// Detectar visibilidad de la pestaña
document.addEventListener("visibilitychange", () => {
  if (document.hidden) {
    stopMosaicAnimation();
  } else if (allPhotosReady) {
    startMosaicAnimation();
  }
});

// Fade in inicial
gsap.from("#eventContainer", { opacity: 0, duration: 1.5 });

const interval = setInterval(checkForChanges, 5000);
checkForChanges();
</script>

</body>
</html>
