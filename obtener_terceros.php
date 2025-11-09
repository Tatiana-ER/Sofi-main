<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
$id = isset($_GET['id']) ? trim($_GET['id']) : ''; 
$terceros = [];

// Si se solicita un tercero específico por cédula (para edición)
if (!empty($id)) {
    $sql_id = "
        SELECT cedula, CONCAT(nombres, ' ', apellidos) AS nombreCompleto
        FROM catalogosterceros 
        WHERE cedula = :id 
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql_id);
    $stmt->execute([':id' => $id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        $terceros[] = [
            'valor' => $resultado['cedula'],
            'texto' => $resultado['cedula'] . ' - ' . $resultado['nombreCompleto']
        ];
    }
    
    echo json_encode($terceros, JSON_UNESCAPED_UNICODE);
    exit;
}

// Búsqueda general (cuando el usuario escribe en el select)
if (!empty($search)) {
    // Buscar por cédula, nombres o apellidos
    $sql_busqueda = "
        SELECT cedula, CONCAT(nombres, ' ', apellidos) AS nombreCompleto
        FROM catalogosterceros 
        WHERE cedula LIKE :search1
           OR LOWER(nombres) LIKE LOWER(:search2)
           OR LOWER(apellidos) LIKE LOWER(:search3)
           OR LOWER(CONCAT(nombres, ' ', apellidos)) LIKE LOWER(:search4)
        ORDER BY nombres, apellidos
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql_busqueda);
    $searchParam = "%$search%";
    $stmt->execute([
        ':search1' => $searchParam,
        ':search2' => $searchParam,
        ':search3' => $searchParam,
        ':search4' => $searchParam
    ]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        $terceros[] = [
            'valor' => $row['cedula'] . ' - ' . $row['nombreCompleto'],
            'texto' => $row['cedula'] . ' - ' . $row['nombreCompleto']
        ];
    }

    echo json_encode($terceros, JSON_UNESCAPED_UNICODE);
    exit;
}

// Si no hay búsqueda ni ID, devolver vacío
echo json_encode([]);