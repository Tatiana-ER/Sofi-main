<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

$metodoPago = isset($_GET['metodo']) ? strtolower(trim($_GET['metodo'])) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
$cuentas_completas = [];
$codigos_unicos = [];

// 1. Definir los prefijos válidos según método de pago
$codigos_prefijo = [];
if ($metodoPago === "efectivo") {
    $codigos_prefijo = ['1105'];
} elseif ($metodoPago === "pago electronico") {
    $codigos_prefijo = ['1110', '1120', '1295'];
} elseif ($metodoPago === "credito") {
    $codigos_prefijo = ['1305', '2205', '2335'];
}

// Si no se pasa un método válido, no devuelve nada
if (empty($codigos_prefijo)) {
    echo json_encode([]);
    exit;
}

// 2. Filtro por prefijos (nivel3) + búsqueda si aplica
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
";

if (!empty($search)) {
    $sql_maestra .= " AND LOWER(
        CONCAT_WS(' ', nivel3, nivel4, nivel5, nivel6)
    ) LIKE LOWER(:search)";
}

$sql_maestra .= " ORDER BY cuenta_final LIMIT 200";

$sentencia = $pdo->prepare($sql_maestra);
if (!empty($search)) {
    $sentencia->bindValue(':search', "%$search%");
}
$sentencia->execute();
$resultados_maestra = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Agregar resultados
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

// 3. Buscar también en el catálogo personalizado (solo dentro de los prefijos válidos)
$sql_filtros_catalogo = implode(" OR ", array_map(function($code) {
    return "c.cuenta LIKE '{$code}%'";
}, $codigos_prefijo));

$sql_catalogo = "
    SELECT DISTINCT c.auxiliar 
    FROM catalogoscuentascontables c
    WHERE ({$sql_filtros_catalogo})
      AND c.auxiliar IS NOT NULL 
      AND c.auxiliar != ''
";

if (!empty($search)) {
    $sql_catalogo .= " AND LOWER(c.auxiliar) LIKE LOWER(:search)";
}

$sql_catalogo .= " ORDER BY c.auxiliar LIMIT 100";

$sentencia_catalogo = $pdo->prepare($sql_catalogo);
if (!empty($search)) {
    $sentencia_catalogo->bindValue(':search', "%$search%");
}
$sentencia_catalogo->execute();
$resultados_catalogo = $sentencia_catalogo->fetchAll(PDO::FETCH_ASSOC);

// Agregar resultados personalizados
foreach ($resultados_catalogo as $row) {
    $aux = $row['auxiliar'];
    if (!isset($codigos_unicos[$aux])) {
        $cuentas_completas[] = [
            'valor' => $aux,
            'texto' => "(Personalizada) $aux"
        ];
        $codigos_unicos[$aux] = true;
    }
}

// 4. Devolver resultados finales
echo json_encode($cuentas_completas, JSON_UNESCAPED_UNICODE);
