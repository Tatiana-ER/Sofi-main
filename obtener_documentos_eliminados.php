<?php
session_start();
header('Content-Type: application/json');

// Conexión a la base de datos (igual que en documentos_eliminados.php)
$host = 'localhost';
$dbname = 'sofi';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener parámetros del POST
    $tipo_documento = isset($_POST['tipo_documento']) ? $_POST['tipo_documento'] : '';
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $registros_por_pagina = isset($_POST['registros_por_pagina']) ? (int)$_POST['registros_por_pagina'] : 5;
    
    // Calcular offset
    $offset = ($pagina - 1) * $registros_por_pagina;
    
    // Construir consulta
    $sql = "SELECT 
                id,
                nombre_usuario,
                tipo_documento,
                numero_documento,
                nombre_documento,
                tercero,
                total,
                fecha_documento,
                fecha_eliminacion,
                hora_eliminacion,
                detalles
            FROM documentos_eliminados 
            WHERE 1=1";
    
    $params = array();
    
    // Filtrar por tipo de documento si se especifica
    if (!empty($tipo_documento)) {
        $sql .= " AND tipo_documento = :tipo_documento";
        $params[':tipo_documento'] = $tipo_documento;
    }
    
    $sql .= " ORDER BY fecha_eliminacion DESC, hora_eliminacion DESC";
    
    // Contar total de registros
    $sqlCount = "SELECT COUNT(*) as total FROM documentos_eliminados WHERE 1=1";
    if (!empty($tipo_documento)) {
        $sqlCount .= " AND tipo_documento = :tipo_documento";
    }
    
    $stmtCount = $conn->prepare($sqlCount);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRegistros = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Agregar límite y offset
    $sql .= " LIMIT :limit OFFSET :offset";
    
    // Ejecutar consulta
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar documentos para extraer prefijo
    $documentosProcesados = array();
    
    foreach ($documentos as $doc) {
        $prefijo = 'Sin Prefijo';
        $numero = $doc['numero_documento'] ?? '';
        
        // Extraer prefijo del nombre_documento si existe
        if (!empty($doc['nombre_documento'])) {
            if (preg_match('/#([A-Za-z0-9-]+)/', $doc['nombre_documento'], $matches)) {
                $numeroCompleto = $matches[1];
                if (strpos($numeroCompleto, '-') !== false) {
                    $partes = explode('-', $numeroCompleto, 2);
                    $prefijo = $partes[0];
                } else {
                    $prefijo = $doc['tipo_documento'];
                }
            }
        } elseif (!empty($numero) && strpos($numero, '-') !== false) {
            $partes = explode('-', $numero, 2);
            $prefijo = $partes[0];
        }
        
        $documentosProcesados[] = array(
            'id' => $doc['id'],
            'nombre_usuario' => $doc['nombre_usuario'],
            'tipo_documento' => $doc['tipo_documento'],
            'prefijo' => $prefijo,
            'numero_documento' => $numero,
            'nombre_documento' => $doc['nombre_documento'] ?? '',
            'tercero' => $doc['tercero'] ?? '',
            'total' => $doc['total'] ?? 0,
            'fecha_documento' => $doc['fecha_documento'] ?? '',
            'fecha_eliminacion' => $doc['fecha_eliminacion'],
            'hora_eliminacion' => $doc['hora_eliminacion'],
            'detalles' => $doc['detalles'] ?? ''
        );
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'documentos' => $documentosProcesados,
        'total_registros' => (int)$totalRegistros,
        'pagina_actual' => $pagina,
        'total_paginas' => ceil($totalRegistros / $registros_por_pagina)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // Error en la base de datos
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage(),
        'documentos' => [],
        'total_registros' => 0
    ], JSON_UNESCAPED_UNICODE);
}
?>