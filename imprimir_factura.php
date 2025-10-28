<?php
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM facturas WHERE id = ?");
$stmt->execute([$id]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Factura #<?= $factura['id'] ?></title>
  <link rel="stylesheet" href="estilos_factura.css">
  <script>
    window.onload = function() {
      window.print(); // Abre el cuadro de impresión automáticamente
    }
  </script>
</head>
<body>
  <h2>Factura #<?= $factura['id'] ?></h2>
  <p>Cliente: <?= htmlspecialchars($factura['cliente']) ?></p>
  <p>Fecha: <?= $factura['fecha'] ?></p>
  <p>Total: $<?= number_format($factura['total'], 2) ?></p>
</body>
</html>
