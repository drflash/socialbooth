<?php
require_once __DIR__ . '/lib/socialbooth_helpers.php';

$errorMessage = '';
$formValues = [
    'eventName' => '',
    'photoCount' => '1296',
];

if (
    isset($_POST['eventName'], $_FILES['image'], $_FILES['frame'])
    && $_FILES['image']['error'] === 0
    && $_FILES['frame']['error'] === 0
) {
    $eventName = sb_normalize_event_name($_POST['eventName'] ?? null);
    $requestedPhotoCount = sb_normalize_positive_int($_POST['photoCount'] ?? null, 1, 10000);

    $formValues['eventName'] = trim((string) ($_POST['eventName'] ?? ''));
    $formValues['photoCount'] = trim((string) ($_POST['photoCount'] ?? ''));

    if ($eventName === null) {
        $errorMessage = 'Nombre de evento no valido. Debe contener solo letras, numeros, guiones y guiones bajos.';
    } else {
        $uploadDir = 'uploads/' . $eventName . '/';

        if (!file_exists($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            $errorMessage = 'Error al crear el directorio de destino.';
        } else {
            $uploadFileImage = $uploadDir . basename($_FILES['image']['name']);
            $uploadFileFrame = $uploadDir . 'frame.png';

            if (
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadFileImage)
                && move_uploaded_file($_FILES['frame']['tmp_name'], $uploadFileFrame)
            ) {
                $dimensions = getimagesize($uploadFileImage);

                if ($dimensions === false) {
                    $errorMessage = 'No se pudieron leer las dimensiones de la imagen del evento.';
                } else {
                    $width = (int) $dimensions[0];
                    $height = (int) $dimensions[1];

                    if ($requestedPhotoCount !== null) {
                        $grid = sb_calculate_grid_from_target($width, $height, $requestedPhotoCount);
                        $columns = $grid['columns'];
                        $rows = $grid['rows'];
                    } else {
                        $grid = sb_calculate_grid_from_cell_size($width, $height, 40);
                        $columns = $grid['columns'];
                        $rows = $grid['rows'];
                    }

                    $redirectURL = 'mostrar_imagen.php?image=' . urlencode($uploadFileImage)
                        . '&frame=' . urlencode($uploadFileFrame)
                        . '&columns=' . urlencode((string) $columns)
                        . '&rows=' . urlencode((string) $rows)
                        . '&eventName=' . urlencode($eventName);

                    if ($requestedPhotoCount !== null) {
                        $redirectURL .= '&targetSpaces=' . urlencode((string) $requestedPhotoCount);
                    }

                    header('Location: ' . $redirectURL);
                    exit();
                }
            } else {
                $errorMessage = 'Error al subir los archivos.';
            }
        }
    }
} elseif (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (!isset($_POST['eventName'], $_FILES['image'], $_FILES['frame'])
        || $_FILES['image']['error'] !== 0
        || $_FILES['frame']['error'] !== 0)
) {
    $errorMessage = 'No se han proporcionado todos los datos necesarios.';
    $formValues['eventName'] = trim((string) ($_POST['eventName'] ?? ''));
    $formValues['photoCount'] = trim((string) ($_POST['photoCount'] ?? '1296'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Socialbooth</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
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
            width: 350px;
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
        form input[type="number"],
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

        form input[type="submit"] {
            width: 100%;
            margin-bottom: 15px;
            box-sizing: border-box;
            background-color: #5e5e5e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            padding: 1rem 3rem;
        }

        form input[type="submit"]:hover {
            background-color: #913aff;
        }

        .help {
            margin-top: -8px;
            margin-bottom: 18px;
            text-align: left;
            color: #667085;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
            text-align: left;
        }
    </style>
</head>
<body>
    <header>
        <img src="images/logosocial.png" alt="Logo">
    </header>
    <div class="container">
        <?php if ($errorMessage !== ''): ?>
            <div class="error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form enctype="multipart/form-data" action="" method="post">
            <label for="eventName">Nombre del Evento:</label>
            <input type="text" id="eventName" name="eventName" required value="<?= htmlspecialchars($formValues['eventName'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="photoCount">Cantidad aproximada de fotos:</label>
            <input type="number" id="photoCount" name="photoCount" min="1" max="10000" value="<?= htmlspecialchars($formValues['photoCount'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="help">Se ajustara automaticamente la reticula para acercarse a este numero manteniendo la proporcion del mosaico.</div>

            <label for="image">Imagen del Evento (JPEG):</label>
            <input type="file" name="image" accept="image/jpeg,image/png" required>

            <label for="frame">Marco del Evento (PNG):</label>
            <input type="file" name="frame" accept="image/png" required>

            <input type="submit" value="Subir Imagenes">
        </form>
    </div>
</body>
</html>