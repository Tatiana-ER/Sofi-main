<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

if (isset($_POST['codigoProducto'])) {
    $codigo = trim($_POST['codigoProducto']);
    
    $stmt = $pdo->prepare("SELECT costoUnitario, descripcionProducto FROM productoinventarios WHERE codigoProducto = :codigo LIMIT 1");
    $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
    $stmt->execute();
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($producto) {
        echo json_encode([
            "costoUnitario" => $producto['costoUnitario'] ?? 0,
            "nombreProducto" => $producto['descripcionProducto'] ?? ""
        ]);
    } else {
        echo json_encode(["costoUnitario" => 0, "nombreProducto" => ""]);
    }
    exit;
}
?>