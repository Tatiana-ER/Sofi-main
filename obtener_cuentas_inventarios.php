<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$tipo = isset($_GET['tipo']) ? strtolower($_GET['tipo']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
$cuentas = [];
$codigos_unicos = [];

/* 1 SI SE RECIBE UN TÉRMINO DE BÚSQUEDA → BUSQUEDA GLOBAL */
if (!empty($search)) {
    // 🔍 Buscar en cuentas_contables (todos los niveles)
    $sql_busqueda_global = "
        SELECT DISTINCT cuenta
        FROM (
            SELECT nivel1 AS cuenta FROM cuentas_contables
            UNION ALL
            SELECT nivel2 FROM cuentas_contables
            UNION ALL
            SELECT nivel3 FROM cuentas_contables
            UNION ALL
            SELECT nivel4 FROM cuentas_contables
            UNION ALL
            SELECT nivel5 FROM cuentas_contables
            UNION ALL
            SELECT nivel6 FROM cuentas_contables
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

    // Buscar también en catalogoscuentascontables
    $sql_catalogo = "
        SELECT DISTINCT auxiliar
        FROM catalogoscuentascontables
        WHERE LOWER(auxiliar) LIKE LOWER(:search)
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

    echo json_encode($cuentas, JSON_UNESCAPED_UNICODE);
    exit;
}

/* 2 SIN BÚSQUEDA → FILTRAR SEGÚN EL TIPO (ventas, inventarios…) */
switch ($tipo) {
    case 'ventas':
        $filtro = "nivel1 LIKE '4%'";
        break;
    case 'inventarios':
        $filtro = "nivel2 LIKE '14%' OR nivel3 LIKE '14%'";
        break;
    case 'costos':
        $filtro = "nivel1 LIKE '6%'";
        break;
    case 'devoluciones':
        $filtro = "nivel3 LIKE '4175%'";
        break;
    default:
        echo json_encode([]);
        exit;
}

/* 3 CONSULTAR SOLO EL ÚLTIMO NIVEL DISPONIBLE */
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

/* 4 AGREGAR TAMBIÉN CUENTAS PERSONALIZADAS DEL CATÁLOGO */
$prefijos = [];
if ($tipo === 'inventarios') {
    $prefijos = ['14'];
} elseif ($tipo === 'ventas') {
    $prefijos = ['4'];
} elseif ($tipo === 'costos') {
    $prefijos = ['6'];
} elseif ($tipo === 'devoluciones') {
    $prefijos = ['4175'];
}

if (!empty($prefijos)) {
    $cond = implode(" OR ", array_map(fn($p) => "auxiliar LIKE '{$p}%'", $prefijos));
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

/*5 DEVOLVER RESULTADO FINAL*/
echo json_encode($cuentas, JSON_UNESCAPED_UNICODE);
?>
