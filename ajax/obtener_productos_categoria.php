<?php
require_once '../config/database.php';


$pdo = Database::getConnection();

$categoriaId = isset($_POST['categoriaId']) ? $_POST['categoriaId'] : "";

if (!empty($categoriaId)) {
    $sentencia = $pdo->prepare("SELECT id, codigoProducto, descripcionProducto 
                                FROM productoinventarios 
                                WHERE categoriaInventarios = :categoriaId 
                                AND activo = 1
                                ORDER BY descripcionProducto ASC");
    $sentencia->bindParam(':categoriaId', $categoriaId);
    $sentencia->execute();
    $productos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($productos);
} else {
    echo json_encode([]);
}
?>