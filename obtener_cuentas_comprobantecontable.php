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
        SELECT DISTINCT
            nivel_completo,
            nivel_orden
        FROM (
            SELECT 
                nivel1 as nivel_completo,
                1 as nivel_orden
            FROM cuentas_contables 
            WHERE nivel1 LIKE CONCAT(:id, '%')
            UNION
            SELECT 
                nivel2 as nivel_completo,
                2 as nivel_orden
            FROM cuentas_contables 
            WHERE nivel2 LIKE CONCAT(:id, '%')
            UNION
            SELECT 
                nivel3 as nivel_completo,
                3 as nivel_orden
            FROM cuentas_contables 
            WHERE nivel3 LIKE CONCAT(:id, '%')
            UNION
            SELECT 
                nivel4 as nivel_completo,
                4 as nivel_orden
            FROM cuentas_contables 
            WHERE nivel4 LIKE CONCAT(:id, '%')
            UNION
            SELECT 
                nivel5 as nivel_completo,
                5 as nivel_orden
            FROM cuentas_contables 
            WHERE nivel5 LIKE CONCAT(:id, '%')
            UNION
            SELECT 
                nivel6 as nivel_completo,
                6 as nivel_orden
            FROM cuentas_contables 
            WHERE nivel6 LIKE CONCAT(:id, '%')
        ) as todos_niveles
        WHERE nivel_completo IS NOT NULL AND nivel_completo <> ''
        ORDER BY nivel_orden, nivel_completo
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql_id);
    $stmt->execute([':id' => $id . '%']);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && !empty($resultado['nivel_completo'])) {
        $cuenta_completa = $resultado['nivel_completo'];
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
    // Buscar en TODOS los niveles de cuentas_contables
    $sql_busqueda = "
        SELECT DISTINCT
            nivel_completo,
            nivel_orden,
            SUBSTRING_INDEX(nivel_completo, '-', 1) as codigo,
            CASE 
                WHEN SUBSTRING_INDEX(nivel_completo, '-', 1) = :search_exact THEN 1
                WHEN SUBSTRING_INDEX(nivel_completo, '-', 1) LIKE :search_start THEN 2
                WHEN nivel_completo LIKE :search_full THEN 3
                ELSE 4
            END as prioridad
        FROM (
            SELECT 
                nivel1 as nivel_completo,
                1 as nivel_orden
            FROM cuentas_contables 
            WHERE (nivel1 LIKE :search_pattern1 AND nivel1 IS NOT NULL AND nivel1 <> '')
            UNION
            SELECT 
                nivel2 as nivel_completo,
                2 as nivel_orden
            FROM cuentas_contables 
            WHERE (nivel2 LIKE :search_pattern2 AND nivel2 IS NOT NULL AND nivel2 <> '')
            UNION
            SELECT 
                nivel3 as nivel_completo,
                3 as nivel_orden
            FROM cuentas_contables 
            WHERE (nivel3 LIKE :search_pattern3 AND nivel3 IS NOT NULL AND nivel3 <> '')
            UNION
            SELECT 
                nivel4 as nivel_completo,
                4 as nivel_orden
            FROM cuentas_contables 
            WHERE (nivel4 LIKE :search_pattern4 AND nivel4 IS NOT NULL AND nivel4 <> '')
            UNION
            SELECT 
                nivel5 as nivel_completo,
                5 as nivel_orden
            FROM cuentas_contables 
            WHERE (nivel5 LIKE :search_pattern5 AND nivel5 IS NOT NULL AND nivel5 <> '')
            UNION
            SELECT 
                nivel6 as nivel_completo,
                6 as nivel_orden
            FROM cuentas_contables 
            WHERE (nivel6 LIKE :search_pattern6 AND nivel6 IS NOT NULL AND nivel6 <> '')
        ) as todos_niveles
        WHERE nivel_completo IS NOT NULL AND nivel_completo <> ''
        ORDER BY 
            prioridad,
            nivel_orden,
            nivel_completo
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql_busqueda);
    
    // Vincular parámetros de búsqueda
    $searchPattern = "%$search%";
    $searchExact = $search;
    $searchStart = "$search%";
    $searchFull = "%$search%";
    
    for ($i = 1; $i <= 6; $i++) {
        $stmt->bindValue(":search_pattern{$i}", $searchPattern);
    }
    $stmt->bindValue(":search_exact", $searchExact);
    $stmt->bindValue(":search_start", $searchStart);
    $stmt->bindValue(":search_full", $searchFull);
    
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        $cuenta_completa = $row['nivel_completo'];
        if (!empty($cuenta_completa) && !isset($codigos_unicos[$cuenta_completa])) {
            $partes = explode('-', $cuenta_completa, 2);
            $codigo = trim($partes[0] ?? '');
            $nombre = isset($partes[1]) ? trim($partes[1]) : '';
            
            // Solo agregar si tiene código
            if (!empty($codigo)) {
                $cuentas_completas[] = [
                    'valor' => $codigo,
                    'texto' => $cuenta_completa,
                    'nombre' => $nombre,
                    'nivel' => $row['nivel_orden']
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
                    'nombre' => $nombre_catalogo,
                    'nivel' => 7 // Para diferenciar que viene de catalogoscuentascontables
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