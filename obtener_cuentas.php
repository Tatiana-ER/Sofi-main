<?php
// obtener_cuentas.php - Versión con cuentas consolidadas y corrección de mapeo

include("connection.php");
header('Content-Type: application/json');

$conn = new connection();
$pdo = $conn->connect();

$metodoPago = isset($_GET['metodo']) ? $_GET['metodo'] : '';
$cuentas_completas = [];
$codigos_unicos = [];

// 1. Definir los filtros de la cuenta principal (Códigos de Nivel 3 - SOLO EL PREFIJO NUMÉRICO)
$codigos_prefijo = [];

if ($metodoPago === "efectivo") {
    $codigos_prefijo = ['1105'];
} else if ($metodoPago === "transferencia") {
    $codigos_prefijo = ['1110', '1120'];
} else if ($metodoPago === "credito") {
    $codigos_prefijo = ['1305', '2205', '2335'];
}

if (empty($codigos_prefijo)) {
    echo json_encode([]);
    exit;
}

// Cláusula WHERE común para la tabla Maestra (usa el campo nivel3)
$sql_filtros_maestra = implode(" OR ", array_map(function($code) {
    return "nivel3 LIKE '{$code}-%'"; // Asegura que solo tome las cuentas nivel 3
}, $codigos_prefijo));

// Cláusula WHERE para la tabla Personalizada (usa el campo cuenta)
$sql_filtros_catalogo = implode(" OR ", array_map(function($code) {
    // Filtramos la tabla 'catalogoscuentascontables' por el campo 'cuenta' (que es el nivel 3)
    return "c.cuenta LIKE '{$code}-%'"; 
}, $codigos_prefijo));


// PARTE A: Consulta a `cuentas_contables` (Maestra)
// (Esta parte trae nivel 3, 4 y 5/6, sin cambios grandes, solo ajustando el filtro)

$sql_maestra = "
    SELECT DISTINCT nivel3, nivel4, nivel5, nivel6
    FROM `cuentas_contables` 
    WHERE ({$sql_filtros_maestra})
    ORDER BY nivel3, nivel4, nivel5, nivel6
";

$sentencia = $pdo->prepare($sql_maestra);
$sentencia->execute();
$resultados_maestra = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Consolidar resultados de la tabla Maestra
foreach ($resultados_maestra as $row) {
    // Nivel 3 (ej: 1105-Caja)
    if (!empty($row['nivel3']) && !isset($codigos_unicos[$row['nivel3']])) {
        $cuentas_completas[] = ['valor' => $row['nivel3'], 'texto' => $row['nivel3']];
        $codigos_unicos[$row['nivel3']] = true;
    }

    // Nivel 4 (ej: 110505-Caja general)
    if (!empty($row['nivel4']) && !isset($codigos_unicos[$row['nivel4']])) {
        $cuentas_completas[] = ['valor' => $row['nivel4'], 'texto' => " " . $row['nivel4']];
        $codigos_unicos[$row['nivel4']] = true;
    }

    // Nivel 5 (ej: 11100501-Bancolombia)
    if (!empty($row['nivel5']) && !isset($codigos_unicos[$row['nivel5']])) {
        $texto_5 = !empty($row['nivel6']) ? $row['nivel5'] . '-' . $row['nivel6'] : $row['nivel5'];
        $cuentas_completas[] = ['valor' => $row['nivel5'], 'texto' => "    " . $texto_5];
        $codigos_unicos[$row['nivel5']] = true;
    }
}

// PARTE B: Consulta a `catalogoscuentascontables` (Personalizadas Nivel 5/6)
// Corregido para usar el campo 'auxiliar'

$sql_catalogo = "
    SELECT DISTINCT c.auxiliar 
    FROM `catalogoscuentascontables` c
    WHERE ({$sql_filtros_catalogo})
    AND c.auxiliar IS NOT NULL 
    AND c.auxiliar != ''
    ORDER BY c.auxiliar
";

$sentencia_catalogo = $pdo->prepare($sql_catalogo);
$sentencia_catalogo->execute();
$resultados_catalogo = $sentencia_catalogo->fetchAll(PDO::FETCH_ASSOC);

// Consolidar resultados de la tabla de Catálogo
    foreach ($resultados_catalogo as $row) {
        $auxiliar_completo = $row['auxiliar']; // Ej: 11050502-Caja Nueva 2
        
        // Asumimos que el código (lo que se va a guardar) es la parte ANTES del guion, o el string completo si no hay guion.
        foreach ($resultados_catalogo as $row) {
        $auxiliar_completo = $row['auxiliar']; // Ej: 11050501-Caja Nueva
        
        // Obtenemos el código de 8 dígitos para evitar duplicados, pero guardamos el string completo
        $partes = explode('-', $auxiliar_completo, 2);
        $codigo_solo = $partes[0]; // 11050501
        
        // Usaremos el código solo para verificar unicidad, pero el valor a guardar será el auxiliar_completo
        if (!isset($codigos_unicos[$codigo_solo])) {
            $cuentas_completas[] = [
                'valor' => $auxiliar_completo,
                'texto' => " (Personalizada) " . $auxiliar_completo
            ];
            $codigos_unicos[$codigo_solo] = true;
        }
    }
}

// PARTE C: Devolver la lista consolidada

echo json_encode($cuentas_completas);
?>