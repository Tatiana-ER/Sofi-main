<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
$id = isset($_GET['id']) ? trim($_GET['id']) : ''; 
$cuentas_completas = [];
$codigos_unicos = [];

// Si se solicita una cuenta específica por ID (para edición)
if (!empty($id)) {
    // Buscar en cuentas_contables en cualquier nivel
    $sql_id = "
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
    
    $stmt = $pdo->prepare($sql_id);
    for ($i = 1; $i <= 6; $i++) {
        $stmt->bindValue(":id{$i}", $id);
    }
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && !empty($resultado['cuenta_completa'])) {
        $cuenta_completa = $resultado['cuenta_completa'];
        $partes = explode('-', $cuenta_completa, 2);
        $codigo = trim($partes[0] ?? '');
        $nombre = isset($partes[1]) ? trim($partes[1]) : '';
        
        $cuentas_completas[] = [
            'valor' => $codigo,
            'texto' => $cuenta_completa,
            'nombre' => $nombre
        ];
    } else {
        // Si no está en cuentas_contables, buscar en catalogoscuentascontables
        $sql_catalogo_id = "SELECT auxiliar, nombre FROM catalogoscuentascontables WHERE auxiliar = :id LIMIT 1";
        $stmt2 = $pdo->prepare($sql_catalogo_id);
        $stmt2->execute([':id' => $id]);
        $resultado2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado2) {
            $cuentas_completas[] = [
                'valor' => $resultado2['auxiliar'],
                'texto' => $resultado2['auxiliar'] . ($resultado2['nombre'] ? ' - ' . $resultado2['nombre'] : ''),
                'nombre' => $resultado2['nombre'] ?? ''
            ];
        }
    }
    
    echo json_encode($cuentas_completas, JSON_UNESCAPED_UNICODE);
    exit;
}

// Búsqueda general (cuando el usuario escribe en el select)
if (!empty($search)) {
    // Primero, buscar por código (antes del guión)
    $sql_busqueda = "
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
            -- Buscar en texto completo (incluyendo guión)
            OR (nivel1 LIKE :search13 AND nivel1 IS NOT NULL AND nivel1 <> '')
            OR (nivel2 LIKE :search14 AND nivel2 IS NOT NULL AND nivel2 <> '')
            OR (nivel3 LIKE :search15 AND nivel3 IS NOT NULL AND nivel3 <> '')
            OR (nivel4 LIKE :search16 AND nivel4 IS NOT NULL AND nivel4 <> '')
            OR (nivel5 LIKE :search17 AND nivel5 IS NOT NULL AND nivel5 <> '')
            OR (nivel6 LIKE :search18 AND nivel6 IS NOT NULL AND nivel6 <> '')
        ORDER BY 
            -- Ordenar primero por coincidencias exactas en código
            CASE 
                WHEN SUBSTRING_INDEX(COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1), '-', 1) = :search_exact THEN 1
                WHEN SUBSTRING_INDEX(COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1), '-', 1) LIKE :search_start THEN 2
                ELSE 3
            END,
            LENGTH(COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1)),
            COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1)
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql_busqueda);
    
    // Vincular parámetros de búsqueda
    $searchPattern = "%$search%";
    $searchExact = $search;
    $searchStart = "$search%";
    
    for ($i = 1; $i <= 6; $i++) {
        $stmt->bindValue(":search{$i}", $searchPattern);
    }
    for ($i = 7; $i <= 12; $i++) {
        $stmt->bindValue(":search{$i}", $searchPattern);
    }
    for ($i = 13; $i <= 18; $i++) {
        $stmt->bindValue(":search{$i}", $searchPattern);
    }
    $stmt->bindValue(":search_exact", $searchExact);
    $stmt->bindValue(":search_start", $searchStart);
    
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        $cuenta_completa = $row['cuenta_completa'];
        if (!empty($cuenta_completa) && !isset($codigos_unicos[$cuenta_completa])) {
            $partes = explode('-', $cuenta_completa, 2);
            $codigo = trim($partes[0] ?? '');
            $nombre = isset($partes[1]) ? trim($partes[1]) : '';
            
            // Solo agregar si tiene código
            if (!empty($codigo)) {
                $cuentas_completas[] = [
                    'valor' => $codigo,
                    'texto' => $cuenta_completa,
                    'nombre' => $nombre
                ];
                $codigos_unicos[$cuenta_completa] = true;
            }
        }
    }

    // También busca en catalogoscuentascontables
    $sql_catalogo_busqueda = "
        SELECT DISTINCT auxiliar, nombre 
        FROM catalogoscuentascontables 
        WHERE auxiliar LIKE :search 
           OR nombre LIKE :search2
           OR CONCAT(auxiliar, ' - ', nombre) LIKE :search3
        ORDER BY auxiliar
        LIMIT 20
    ";
    
    try {
        $stmt2 = $pdo->prepare($sql_catalogo_busqueda);
        $stmt2->execute([
            ':search' => $searchPattern,
            ':search2' => $searchPattern,
            ':search3' => $searchPattern
        ]);
        $resultados_catalogo = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados_catalogo as $row) {
            $auxiliar = $row['auxiliar'] ?? '';
            $nombre_catalogo = $row['nombre'] ?? '';
            
            if (!empty($auxiliar) && !isset($codigos_unicos[$auxiliar])) {
                $texto_completo = $auxiliar . (!empty($nombre_catalogo) ? ' - ' . $nombre_catalogo : '');
                
                $cuentas_completas[] = [
                    'valor' => $auxiliar,
                    'texto' => $texto_completo,
                    'nombre' => $nombre_catalogo
                ];
                $codigos_unicos[$auxiliar] = true;
            }
        }
    } catch (Exception $e) {
        // Si hay error con catalogoscuentascontables, continuar sin esas cuentas
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

// Si no hay búsqueda ni ID, devolver vacío
echo json_encode([]);