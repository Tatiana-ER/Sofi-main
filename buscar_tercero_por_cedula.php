<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';

if (!empty($cedula)) {
    $sql = "SELECT cedula, nombres, apellidos, razonSocial, tipoPersona
            FROM catalogosterceros 
            WHERE cedula = :cedula 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cedula' => $cedula]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        // Si es persona natural, concatenar nombres y apellidos
        // Si es persona jurídica, usar razón social
        $nombreCompleto = '';
        if ($resultado['tipoPersona'] === 'Natural') {
            $nombreCompleto = trim($resultado['nombres'] . ' ' . $resultado['apellidos']);
        } else {
            $nombreCompleto = trim($resultado['razonSocial']);
        }
        
        echo json_encode([
            'success' => true,
            'cedula' => $resultado['cedula'],
            'nombre' => $nombreCompleto
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Tercero no encontrado'
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Cédula no proporcionada'
    ], JSON_UNESCAPED_UNICODE);
}
?>