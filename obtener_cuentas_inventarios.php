<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$tipo = isset($_GET['tipo']) ? strtolower($_GET['tipo']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cuentas = [];
$codigos_unicos = [];

// 1. DETERMINAR EL FILTRO BASADO EN EL TIPO 
$filtro = '';
$prefijos_catalogo = []; 

switch ($tipo) {
    case 'ventas':
        $filtro = "nivel1 LIKE '4%'";
        $prefijos_catalogo = ['4'];
        break;
    case 'inventarios':
        $filtro = "(nivel2 LIKE '14%' OR nivel3 LIKE '14%')";
        $prefijos_catalogo = ['14'];
        break;
    case 'costos':
        $filtro = "nivel1 LIKE '6%'";
        $prefijos_catalogo = ['6'];
        break;
    case 'devoluciones':
        $filtro = "nivel3 LIKE '4175%'";
        $prefijos_catalogo = ['4175'];
        break;
    default:
        // Si no hay tipo válido, no hacemos nada (el flujo de código posterior manejará si hay o no búsqueda)
        break;
}

// Si no hay filtro y se espera un filtro, salimos
if (empty($filtro) && empty($search)) {
    echo json_encode([]);
    exit;
}

/* 2. SI SE RECIBE UN TÉRMINO DE BÚSQUEDA → APLICAR FILTRO DE TIPO Y BÚSQUEDA */
if (!empty($search)) {
    
    // Si no hay filtro para la búsqueda, salimos (no hay tipo válido)
    if (empty($filtro)) {
        echo json_encode([]);
        exit;
    }

    // 🔍 Buscar en cuentas_contables (niveles con el filtro aplicado)
    $sql_busqueda_global = "
        SELECT DISTINCT cuenta
        FROM (
            SELECT nivel1 AS cuenta, 1 AS nivel FROM cuentas_contables WHERE {$filtro}
            UNION ALL
            SELECT nivel2 AS cuenta, 2 AS nivel FROM cuentas_contables WHERE {$filtro}
            UNION ALL
            SELECT nivel3 AS cuenta, 3 AS nivel FROM cuentas_contables WHERE {$filtro}
            UNION ALL
            SELECT nivel4 AS cuenta, 4 AS nivel FROM cuentas_contables WHERE {$filtro}
            UNION ALL
            SELECT nivel5 AS cuenta, 5 AS nivel FROM cuentas_contables WHERE {$filtro}
            UNION ALL
            SELECT nivel6 AS cuenta, 6 AS nivel FROM cuentas_contables WHERE {$filtro}
        ) AS todas
        WHERE cuenta IS NOT NULL AND cuenta != ''
        AND LOWER(cuenta) LIKE LOWER(:search)
        ORDER BY cuenta
        LIMIT 50
    ";
    
    $stmt = $pdo->prepare($sql_busqueda_global);
    $stmt->execute([':search' => "%$search%"]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $row) {
        $cuenta = $row['cuenta'];
        if (!empty($cuenta) && !isset($codigos_unicos[$cuenta])) {
            $cuentas[] = [
                'valor' => $cuenta,
                'texto' => $cuenta,
                'nombre_puro' => (strpos($cuenta, '-') !== false) ? trim(explode('-', $cuenta, 2)[1]) : ''
            ];
            $codigos_unicos[$cuenta] = true;
        }
    }

    // Buscar también en catalogoscuentascontables (solo las que coincidan con el tipo)
    if (!empty($prefijos_catalogo)) {
        $cond_catalogo = implode(" OR ", array_map(fn($p) => "auxiliar LIKE '{$p}%'", $prefijos_catalogo));
        
        $sql_catalogo = "
            SELECT DISTINCT auxiliar
            FROM catalogoscuentascontables
            WHERE ({$cond_catalogo}) 
            AND LOWER(auxiliar) LIKE LOWER(:search)
            AND auxiliar IS NOT NULL AND auxiliar != ''
            ORDER BY auxiliar
            LIMIT 50
        ";
        $stmt2 = $pdo->prepare($sql_catalogo);
        $stmt2->execute([':search' => "%$search%"]);
        $resultados_catalogo = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados_catalogo as $row) {
            $aux = $row['auxiliar'];
            if (!empty($aux) && !isset($codigos_unicos[$aux])) {
                $cuentas[] = [
                    'valor' => $aux,
                    'texto' => " (Personalizada) " . $aux,
                    'nombre_puro' => (strpos($aux, '-') !== false) ? trim(explode('-', $aux, 2)[1]) : ''
                ];
                $codigos_unicos[$aux] = true;
            }
        }
    }


    // Devolver resultado y salir del script
    echo json_encode($cuentas, JSON_UNESCAPED_UNICODE);
    exit;
}

/* 3. SIN BÚSQUEDA → FILTRAR SOLO SEGÚN EL TIPO (ventas, inventarios…) */

// Si llegamos aquí y no hay filtro (tipo no válido), ya salimos al inicio. 
// Si hay filtro, procedemos:

/* 3.1 CONSULTAR SOLO EL ÚLTIMO NIVEL DISPONIBLE DE cuentas_contables */
$sql = "
    SELECT DISTINCT
        CASE
            WHEN nivel6 IS NOT NULL AND nivel6 != '' THEN nivel6
            WHEN nivel5 IS NOT NULL AND nivel5 != '' THEN nivel5
            WHEN nivel4 IS NOT NULL AND nivel4 != '' THEN nivel4
            ELSE nivel3
        END AS cuenta_final
    FROM cuentas_contables
    WHERE {$filtro}
    ORDER BY cuenta_final
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultados as $row) {
    $cuenta = $row['cuenta_final'];
    if (!empty($cuenta) && !isset($codigos_unicos[$cuenta])) {
        $partes = explode('-', $cuenta, 2);
        $codigo = trim($partes[0]);
        $nombre = isset($partes[1]) ? trim($partes[1]) : '';
        $cuentas[] = [
            'valor' => $cuenta,
            'texto' => $cuenta,
            'nombre_puro' => $nombre
        ];
        $codigos_unicos[$cuenta] = true;
    }
}

/* 3.2 AGREGAR TAMBIÉN CUENTAS PERSONALIZADAS DEL CATÁLOGO */
if (!empty($prefijos_catalogo)) {
    $cond = implode(" OR ", array_map(fn($p) => "auxiliar LIKE '{$p}%'", $prefijos_catalogo));
    $sql_personalizadas = "
        SELECT DISTINCT auxiliar 
        FROM catalogoscuentascontables 
        WHERE ({$cond})
        AND auxiliar IS NOT NULL AND auxiliar != ''
        ORDER BY auxiliar
    ";
    $stmt2 = $pdo->prepare($sql_personalizadas);
    $stmt2->execute();
    $res_personalizadas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($res_personalizadas as $row) {
        $aux = $row['auxiliar'];
        if (!isset($codigos_unicos[$aux])) {
            $partes = explode('-', $aux, 2);
            $nombre = isset($partes[1]) ? trim($partes[1]) : '';
            $cuentas[] = [
                'valor' => $aux,
                'texto' => " (Personalizada) " . $aux,
                'nombre_puro' => $nombre
            ];
            $codigos_unicos[$aux] = true;
        }
    }
}

/* 4. DEVOLVER RESULTADO FINAL */
echo json_encode($cuentas, JSON_UNESCAPED_UNICODE);
?>