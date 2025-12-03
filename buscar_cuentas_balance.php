<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
$id = isset($_GET['id']) ? trim($_GET['id']) : ''; 
$cuentas_completas = [];
$codigos_unicos = [];

// ================== BÚSQUEDA POR ID ESPECÍFICO (para edición) ==================
if (!empty($id)) {
    // 1. Buscar en libro_diario
    $sql_id_diario = "
        SELECT DISTINCT codigo_cuenta, nombre_cuenta 
        FROM libro_diario 
        WHERE codigo_cuenta = :id 
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql_id_diario);
    $stmt->execute([':id' => $id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        $cuentas_completas[] = [
            'valor' => $resultado['codigo_cuenta'],
            'texto' => $resultado['codigo_cuenta'] . ' - ' . $resultado['nombre_cuenta'],
            'nombre' => $resultado['nombre_cuenta']
        ];
    } else {
        // 2. Buscar en cuentas_contables (PUC)
        $sql_id_puc = "
            SELECT 
                COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1) as cuenta_completa
            FROM cuentas_contables 
            WHERE nivel1 = :id1 
               OR nivel2 = :id2 
               OR nivel3 = :id3 
               OR nivel4 = :id4 
               OR nivel5 = :id5 
               OR nivel6 = :id6 
            LIMIT 1
        ";
        
        $stmt2 = $pdo->prepare($sql_id_puc);
        for ($i = 1; $i <= 6; $i++) {
            $stmt2->bindValue(":id{$i}", $id);
        }
        $stmt2->execute();
        $resultado2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado2 && !empty($resultado2['cuenta_completa'])) {
            $cuenta_completa = $resultado2['cuenta_completa'];
            $partes = explode('-', $cuenta_completa, 2);
            $codigo = trim($partes[0] ?? '');
            $nombre = isset($partes[1]) ? trim($partes[1]) : '';
            
            $cuentas_completas[] = [
                'valor' => $codigo,
                'texto' => $cuenta_completa,
                'nombre' => $nombre
            ];
        } else {
            // 3. Buscar en catalogoscuentascontables (cuentas personalizadas)
            $sql_catalogo_id = "SELECT auxiliar, nombre FROM catalogoscuentascontables WHERE auxiliar = :id LIMIT 1";
            $stmt3 = $pdo->prepare($sql_catalogo_id);
            $stmt3->execute([':id' => $id]);
            $resultado3 = $stmt3->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado3) {
                $cuentas_completas[] = [
                    'valor' => $resultado3['auxiliar'],
                    'texto' => $resultado3['auxiliar'] . ($resultado3['nombre'] ? ' - ' . $resultado3['nombre'] : ''),
                    'nombre' => $resultado3['nombre'] ?? ''
                ];
            }
        }
    }
    
    echo json_encode($cuentas_completas, JSON_UNESCAPED_UNICODE);
    exit;
}

// ================== BÚSQUEDA GENERAL ==================
if (!empty($search)) {
    $searchPattern = "%$search%";
    $searchExact = $search;
    $searchStart = "$search%";
    
    // 1. BUSCAR EN LIBRO_DIARIO (cuentas con movimientos)
    $sql_diario = "
        SELECT DISTINCT 
            codigo_cuenta,
            nombre_cuenta
        FROM libro_diario 
        WHERE 
            codigo_cuenta LIKE :search1
            OR nombre_cuenta LIKE :search2
            OR CONCAT(codigo_cuenta, ' - ', nombre_cuenta) LIKE :search3
        ORDER BY 
            CASE 
                WHEN codigo_cuenta = :search_exact THEN 1
                WHEN codigo_cuenta LIKE :search_start THEN 2
                ELSE 3
            END,
            LENGTH(codigo_cuenta),
            codigo_cuenta
        LIMIT 25
    ";
    
    $stmt = $pdo->prepare($sql_diario);
    $stmt->execute([
        ':search1' => $searchPattern,
        ':search2' => $searchPattern,
        ':search3' => $searchPattern,
        ':search_exact' => $searchExact,
        ':search_start' => $searchStart
    ]);
    $resultados_diario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($resultados_diario as $row) {
        $codigo = $row['codigo_cuenta'];
        $nombre = $row['nombre_cuenta'];
        
        if (!empty($codigo) && !isset($codigos_unicos[$codigo])) {
            $cuentas_completas[] = [
                'valor' => $codigo,
                'texto' => $codigo . ' - ' . $nombre,
                'nombre' => $nombre,
                'fuente' => 'libro_diario'
            ];
            $codigos_unicos[$codigo] = true;
        }
    }
    
    // 2. BUSCAR EN CUENTAS_CONTABLES (PUC completo)
    $sql_puc = "
        SELECT DISTINCT
            COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1) as cuenta_completa
        FROM cuentas_contables 
        WHERE 
            -- Buscar en el código (parte antes del guión)
            (SUBSTRING_INDEX(nivel1, '-', 1) LIKE :search1 AND nivel1 IS NOT NULL AND nivel1 <> '')
            OR (SUBSTRING_INDEX(nivel2, '-', 1) LIKE :search2 AND nivel2 IS NOT NULL AND nivel2 <> '')
            OR (SUBSTRING_INDEX(nivel3, '-', 1) LIKE :search3 AND nivel3 IS NOT NULL AND nivel3 <> '')
            OR (SUBSTRING_INDEX(nivel4, '-', 1) LIKE :search4 AND nivel4 IS NOT NULL AND nivel4 <> '')
            OR (SUBSTRING_INDEX(nivel5, '-', 1) LIKE :search5 AND nivel5 IS NOT NULL AND nivel5 <> '')
            OR (SUBSTRING_INDEX(nivel6, '-', 1) LIKE :search6 AND nivel6 IS NOT NULL AND nivel6 <> '')
            -- Buscar en el nombre (parte después del guión)
            OR (SUBSTRING_INDEX(nivel1, '-', -1) LIKE :search7 AND nivel1 IS NOT NULL AND nivel1 <> '')
            OR (SUBSTRING_INDEX(nivel2, '-', -1) LIKE :search8 AND nivel2 IS NOT NULL AND nivel2 <> '')
            OR (SUBSTRING_INDEX(nivel3, '-', -1) LIKE :search9 AND nivel3 IS NOT NULL AND nivel3 <> '')
            OR (SUBSTRING_INDEX(nivel4, '-', -1) LIKE :search10 AND nivel4 IS NOT NULL AND nivel4 <> '')
            OR (SUBSTRING_INDEX(nivel5, '-', -1) LIKE :search11 AND nivel5 IS NOT NULL AND nivel5 <> '')
            OR (SUBSTRING_INDEX(nivel6, '-', -1) LIKE :search12 AND nivel6 IS NOT NULL AND nivel6 <> '')
            -- Buscar en texto completo
            OR (nivel1 LIKE :search13 AND nivel1 IS NOT NULL AND nivel1 <> '')
            OR (nivel2 LIKE :search14 AND nivel2 IS NOT NULL AND nivel2 <> '')
            OR (nivel3 LIKE :search15 AND nivel3 IS NOT NULL AND nivel3 <> '')
            OR (nivel4 LIKE :search16 AND nivel4 IS NOT NULL AND nivel4 <> '')
            OR (nivel5 LIKE :search17 AND nivel5 IS NOT NULL AND nivel5 <> '')
            OR (nivel6 LIKE :search18 AND nivel6 IS NOT NULL AND nivel6 <> '')
        ORDER BY 
            CASE 
                WHEN SUBSTRING_INDEX(COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1), '-', 1) = :search_exact THEN 1
                WHEN SUBSTRING_INDEX(COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1), '-', 1) LIKE :search_start THEN 2
                ELSE 3
            END,
            LENGTH(COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1)),
            COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1)
        LIMIT 25
    ";
    
    $stmt2 = $pdo->prepare($sql_puc);
    for ($i = 1; $i <= 6; $i++) {
        $stmt2->bindValue(":search{$i}", $searchPattern);
    }
    for ($i = 7; $i <= 12; $i++) {
        $stmt2->bindValue(":search{$i}", $searchPattern);
    }
    for ($i = 13; $i <= 18; $i++) {
        $stmt2->bindValue(":search{$i}", $searchPattern);
    }
    $stmt2->bindValue(":search_exact", $searchExact);
    $stmt2->bindValue(":search_start", $searchStart);
    
    $stmt2->execute();
    $resultados_puc = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($resultados_puc as $row) {
        $cuenta_completa = $row['cuenta_completa'];
        if (!empty($cuenta_completa) && !isset($codigos_unicos[$cuenta_completa])) {
            $partes = explode('-', $cuenta_completa, 2);
            $codigo = trim($partes[0] ?? '');
            $nombre = isset($partes[1]) ? trim($partes[1]) : '';
            
            if (!empty($codigo) && !isset($codigos_unicos[$codigo])) {
                $cuentas_completas[] = [
                    'valor' => $codigo,
                    'texto' => $cuenta_completa,
                    'nombre' => $nombre,
                    'fuente' => 'puc'
                ];
                $codigos_unicos[$codigo] = true;
            }
        }
    }
    
    // 3. BUSCAR EN CATALOGOSCUENTASCONTABLES (cuentas personalizadas)
    $sql_catalogo = "
        SELECT DISTINCT auxiliar, nombre 
        FROM catalogoscuentascontables 
        WHERE auxiliar LIKE :search 
           OR nombre LIKE :search2
           OR CONCAT(auxiliar, ' - ', nombre) LIKE :search3
        ORDER BY 
            CASE 
                WHEN auxiliar = :search_exact THEN 1
                WHEN auxiliar LIKE :search_start THEN 2
                ELSE 3
            END,
            auxiliar
        LIMIT 20
    ";
    
    try {
        $stmt3 = $pdo->prepare($sql_catalogo);
        $stmt3->execute([
            ':search' => $searchPattern,
            ':search2' => $searchPattern,
            ':search3' => $searchPattern,
            ':search_exact' => $searchExact,
            ':search_start' => $searchStart
        ]);
        $resultados_catalogo = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultados_catalogo as $row) {
            $auxiliar = $row['auxiliar'] ?? '';
            $nombre_catalogo = $row['nombre'] ?? '';
            
            if (!empty($auxiliar) && !isset($codigos_unicos[$auxiliar])) {
                $texto_completo = $auxiliar . (!empty($nombre_catalogo) ? ' - ' . $nombre_catalogo : '');
                
                $cuentas_completas[] = [
                    'valor' => $auxiliar,
                    'texto' => $texto_completo,
                    'nombre' => $nombre_catalogo,
                    'fuente' => 'catalogo'
                ];
                $codigos_unicos[$auxiliar] = true;
            }
        }
    } catch (Exception $e) {
        error_log("Error al buscar en catalogoscuentascontables: " . $e->getMessage());
    }
    
    // Si no hay resultados, devolver array vacío
    if (empty($cuentas_completas)) {
        echo json_encode([]);
        exit;
    }
    
    echo json_encode($cuentas_completas, JSON_UNESCAPED_UNICODE);
    exit;
}

// ================== SIN BÚSQUEDA: Cargar cuentas del libro_diario por defecto ==================
$sql_default = "
    SELECT DISTINCT codigo_cuenta, nombre_cuenta
    FROM libro_diario
    ORDER BY codigo_cuenta
    LIMIT 50
";

$stmt_default = $pdo->query($sql_default);
$resultados_default = $stmt_default->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultados_default as $row) {
    $cuentas_completas[] = [
        'valor' => $row['codigo_cuenta'],
        'texto' => $row['codigo_cuenta'] . ' - ' . $row['nombre_cuenta'],
        'nombre' => $row['nombre_cuenta'],
        'fuente' => 'libro_diario'
    ];
}

echo json_encode($cuentas_completas, JSON_UNESCAPED_UNICODE);
?>