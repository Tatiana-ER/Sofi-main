<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$resultados = [];

if (!empty($search)) {
    // Preparar el término de búsqueda
    $searchTerm = '%' . $search . '%';
    
    $sql = "SELECT cedula, nombres, apellidos, razonSocial, tipoPersona
            FROM catalogosterceros 
            WHERE (cedula LIKE ? OR 
                  nombres LIKE ? OR 
                  apellidos LIKE ? OR 
                  razonSocial LIKE ?)
            AND activo = '1'
            ORDER BY cedula
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    
    // Ejecutar con parámetros posicionales
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    
    $terceros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($terceros as $tercero) {
        // Determinar el nombre completo según el tipo de persona
        if ($tercero['tipoPersona'] === 'Natural') {
            $nombreCompleto = trim($tercero['nombres'] . ' ' . $tercero['apellidos']);
        } else {
            $nombreCompleto = trim($tercero['razonSocial']);
        }
        
        $resultados[] = [
            'id' => $tercero['cedula'],
            'text' => $tercero['cedula'] . ' - ' . $nombreCompleto,
            'cedula' => $tercero['cedula'],
            'nombre' => $nombreCompleto
        ];
    }
}

echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
?>