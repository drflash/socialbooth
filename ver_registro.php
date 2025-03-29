<?php
$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
if (!$eventName) {
    die('Falta el parámetro ?eventName=...');
}

$registroPath = 'uploads/' . $eventName . '/registro.json';
$imgPathBase = 'uploads/' . $eventName . '/output/';

if (!file_exists($registroPath)) {
    die('No se encontró el archivo de registros.');
}

$registros = json_decode(file_get_contents($registroPath), true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registros del Evento: <?= htmlspecialchars($eventName) ?></title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 30px;
    }
    h1 {
      text-align: center;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: left;
    }
    th {
      background-color: #f5f5f5;
    }
    img {
      max-height: 100px;
      border-radius: 6px;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
  </style>
</head>
<body>
<div style="text-align: center; margin-bottom: 20px;">
  <a href="descargar_csv.php?eventName=<?= urlencode($eventName) ?>" style="text-decoration: none; background-color: #4CAF50; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold;">⬇️ Descargar CSV</a>
</div>
  <h1>Registros del Evento: <?= htmlspecialchars($eventName) ?></h1>
  <table>
    <thead>
      <tr>
        <th>Nombre</th>
        <th>WhatsApp</th>
        <th>Foto</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($registros as $registro): ?>
        <tr>
          <td><?= htmlspecialchars($registro['nombre']) ?></td>
          <td><?= htmlspecialchars($registro['whatsapp']) ?></td>
          <td>
            <?php if (!empty($registro['foto'])): ?>
              <a href="<?= $imgPathBase . $registro['foto'] ?>" target="_blank">
                <img src="<?= $imgPathBase . $registro['foto'] ?>" alt="Foto">
              </a>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($registro['timestamp']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
