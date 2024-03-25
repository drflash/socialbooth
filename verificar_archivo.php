<?php
    if(isset($_GET['file'])) {
        $file = $_GET['file'];
        if (file_exists($file)) {
            echo json_encode(array('exists' => true));
        } else {
            echo json_encode(array('exists' => false));
        }
    } else {
        echo json_encode(array('error' => 'No se proporcionó un nombre de archivo.'));
    }
?>