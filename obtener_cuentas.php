<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$metodoPago = isset($_GET['metodo']) ? strtolower($_GET['metodo']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
$cuentas_completas = [];
$codigos_unicos = [];

// 1. Si hay búsqueda, filtramos globalmente sin importar método
if (!empty($search)) {
    // Buscar coincidencias en cualquiera de los niveles (1 a 6)
    $sql_busqueda_global = "
        SELECT DISTINCT v.cuenta_final
        FROM (
            SELECT nivel1 AS cuenta_final FROM cuentas_contables WHERE nivel1 IS NOT NULL AND nivel1 <> '' AND LOWER(nivel1) LIKE LOWER(:s1)
            UNION
            SELECT nivel2 FROM cuentas_contables WHERE nivel2 IS NOT NULL AND nivel2 <> '' AND LOWER(nivel2) LIKE LOWER(:s2)
            UNION
            SELECT nivel3 FROM cuentas_contables WHERE nivel3 IS NOT NULL AND nivel3 <> '' AND LOWER(nivel3) LIKE LOWER(:s3)
            UNION
            SELECT nivel4 FROM cuentas_contables WHERE nivel4 IS NOT NULL AND nivel4 <> '' AND LOWER(nivel4) LIKE LOWER(:s4)
            UNION
            SELECT nivel5 FROM cuentas_contables WHERE nivel5 IS NOT NULL AND nivel5 <> '' AND LOWER(nivel5) LIKE LOWER(:s5)
            UNION
            SELECT nivel6 FROM cuentas_contables WHERE nivel6 IS NOT NULL AND nivel6 <> '' AND LOWER(nivel6) LIKE LOWER(:s6)
        ) v
        ORDER BY LENGTH(v.cuenta_final), v.cuenta_final
        LIMIT 200
    ";

    $stmt = $pdo->prepare($sql_busqueda_global);
    for ($i = 1; $i <= 6; $i++) {
        $stmt->bindValue(":s{$i}", "%$search%");
    }
    $stmt->execute();
    $resultados_busqueda = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados_busqueda as $row) {
        $cuenta = $row['cuenta_final'];
        if (!empty($cuenta) && !isset($codigos_unicos[$cuenta])) {
            $cuentas_completas[] = [
                'valor' => $cuenta,
                'texto' => $cuenta
            ];
            $codigos_unicos[$cuenta] = true;
        }
    }

    // También busca en catalogoscuentascontables
    $sql_catalogo_busqueda = "
        SELECT DISTINCT auxiliar 
        FROM catalogoscuentascontables 
        WHERE LOWER(auxiliar) LIKE LOWER(:search)
        ORDER BY auxiliar
        LIMIT 50
    ";
    $stmt2 = $pdo->prepare($sql_catalogo_busqueda);
    $stmt2->execute([':search' => "%$search%"]);
    $resultados_catalogo = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados_catalogo as $row) {
        $auxiliar = $row['auxiliar'];
        if (!empty($auxiliar) && !isset($codigos_unicos[$auxiliar])) {
            $cuentas_completas[] = [
                'valor' => $auxiliar,
                'texto' => "(Personalizada) $auxiliar"
            ];
            $codigos_unicos[$auxiliar] = true;
        }
    }

    echo json_encode($cuentas_completas, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Si no hay búsqueda, filtramos según el método de pago
$codigos_prefijo = [];
if ($metodoPago === "Efectivo") {
    $codigos_prefijo = ['1105'];
} elseif ($metodoPago === "Transferencia") {
    $codigos_prefijo = ['1110', '1120'];
} elseif ($metodoPago === "Credito") {
    $codigos_prefijo = ['1305', '2205', '2335'];
}

if (empty($codigos_prefijo)) {
    echo json_encode([]);
    exit;
}

$sql_filtros_maestra = implode(" OR ", array_map(function($code) {
    return "nivel3 LIKE '{$code}%'";
}, $codigos_prefijo));

$sql_maestra = "
    SELECT DISTINCT
        CASE
            WHEN nivel6 IS NOT NULL AND nivel6 != '' THEN nivel6
            WHEN nivel5 IS NOT NULL AND nivel5 != '' THEN nivel5
            WHEN nivel4 IS NOT NULL AND nivel4 != '' THEN nivel4
            ELSE nivel3
        END AS cuenta_final
    FROM cuentas_contables
    WHERE ({$sql_filtros_maestra})
    ORDER BY cuenta_final
";
$sentencia = $pdo->prepare($sql_maestra);
$sentencia->execute();
$resultados_maestra = $sentencia->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultados_maestra as $row) {
    $cuenta = $row['cuenta_final'];
    if (!empty($cuenta) && !isset($codigos_unicos[$cuenta])) {
        $cuentas_completas[] = [
            'valor' => $cuenta,
            'texto' => $cuenta
        ];
        $codigos_unicos[$cuenta] = true;
    }
}

// 3. Cuentas personalizadas por método
$sql_filtros_catalogo = implode(" OR ", array_map(function($code) {
    return "c.cuenta LIKE '{$code}%'";
}, $codigos_prefijo));

$sql_catalogo = "
    SELECT DISTINCT c.auxiliar 
    FROM catalogoscuentascontables c
    WHERE ({$sql_filtros_catalogo})
      AND c.auxiliar IS NOT NULL 
      AND c.auxiliar != ''
    ORDER BY c.auxiliar
";
$sentencia_catalogo = $pdo->prepare($sql_catalogo);
$sentencia_catalogo->execute();
$resultados_catalogo = $sentencia_catalogo->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultados_catalogo as $row) {
    $auxiliar_completo = $row['auxiliar'];
    if (!isset($codigos_unicos[$auxiliar_completo])) {
        $cuentas_completas[] = [
            'valor' => $auxiliar_completo,
            'texto' => "(Personalizada) $auxiliar_completo"
        ];
        $codigos_unicos[$auxiliar_completo] = true;
    }
}

// 4. Devolvemos resultado final
echo json_encode($cuentas_completas, JSON_UNESCAPED_UNICODE);
