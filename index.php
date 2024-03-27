<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Socialbooth</title>
    <style>
        /* Importa la tipografía Montserrat */
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif; /* Usa la tipografía Montserrat */
        }
        input[type=file] {
  border: 1px solid #5e5e5e;
  color: #5e5e5e;
  padding: 1rem;
  border-radius: 5px;
  font-family: 'Montserrat', sans-serif;
  font-size: 80%;
  text-transform: lowercase;
}
::file-selector-button {
  background: #5e5e5e;
  color: white;
  border: 1px solid #5e5e5e;
  border-radius: 5px;
  padding: 1rem 3rem;
}

        header {
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        header img {
            width: 350px; /* Ajusta el tamaño de tu logotipo */
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        form {
            text-align: center;
        }

        form label {
            display: block;
            margin-bottom: 10px;
            color: #5e5e5e;
        }

        form input[type="text"],
        form input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #5e5e5e;
            box-sizing: border-box;
        }

        form input[type="file"] {
            cursor: pointer;
        }

        /* Estilo para el botón Subir Imagen */
        form input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            box-sizing: border-box;
            background-color: #5e5e5e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 1rem 3rem;
        }

        /* Estilo para el botón Subir Imagen al pasar el cursor */
        form input[type="submit"]:hover {
            background-color: #913aff;
        }

        .result {
            margin-top: 20px;
        }
  
    </style>
</head>
<body>
    <header>
        <img src="images/logosocial.png" alt="Logo">
    </header>
    <div class="container">
    <?php
if(isset($_POST['eventName']) && isset($_FILES['image']) && isset($_FILES['frame']) && $_FILES['image']['error'] == 0 && $_FILES['frame']['error'] == 0) {
    $eventName = $_POST['eventName'];

    // Directorio donde se guardarán las imágenes subidas
    $uploadDir = 'uploads/' . $eventName . '/';

    // Comprobamos si el nombre del evento es válido
    if(!preg_match('/^[a-zA-Z0-9_\-]+$/', $eventName)) {
        echo "Nombre de evento no válido. Debe contener solo letras, números, guiones y guiones bajos.";
    } else {
        // Crear el directorio si no existe
        if (!file_exists($uploadDir)) {
            if(!mkdir($uploadDir, 0777, true)) {
                echo "Error al crear el directorio de destino.";
            }
        }

        // Rutas de los archivos subidos
        $uploadFileImage = $uploadDir . basename($_FILES['image']['name']);
        $uploadFileFrame = $uploadDir . "frame.png"; // Nombre fijo para el marco

        // Mover las imágenes a sus ubicaciones deseadas
        if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadFileImage) && move_uploaded_file($_FILES['frame']['tmp_name'], $uploadFileFrame)) {
            // Obtener las dimensiones de la imagen subida
            $dimensions = getimagesize($uploadFileImage);
            $width = $dimensions[0];
            $height = $dimensions[1];

            // Calcular número de columnas y filas de cubos
            $columns = floor($width / 80);
            $rows = floor($height / 80);

            // Redirigir al usuario a la página de visualización de la imagen con la retícula
            $redirectURL = "mostrar_imagen.php?image=" . urlencode($uploadFileImage) . "&frame=" . urlencode($uploadFileFrame) . "&columns=$columns&rows=$rows&eventName=" . urlencode($eventName);
            header("Location: $redirectURL");
            exit();
        } else {
            echo "Error al subir los archivos.";
        }
    }
} else {
    // Verificar si el formulario ha sido enviado y se han proporcionado todos los datos necesarios
    if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['eventName']) || !isset($_FILES['image']) || !isset($_FILES['frame']) || $_FILES['image']['error'] != 0 || $_FILES['frame']['error'] != 0)) {
        echo "No se han proporcionado todos los datos necesarios.";
        exit; // Detener la ejecución del script
    }
}
?>

        <form enctype="multipart/form-data" action="" method="post">
    <label for="eventName">Nombre del Evento:</label>
    <input type="text" id="eventName" name="eventName" required>
    <br><br>
    <label for="image">Imagen del Evento (JPEG):</label>
    <input type="file" name="image" accept="image/jpeg,image/png" required>
    <br><br>
    <label for="frame">Marco del Evento (PNG):</label>
    <input type="file" name="frame" accept="image/png" required>
    <br><br>
    <input type="submit" value="Subir Imágenes">
</form>
    </div>
</body>
</html>
