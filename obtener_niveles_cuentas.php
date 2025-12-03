<?php
/**
 * Obtiene TODOS los niveles de cuentas disponibles
 * Combina niveles del libro_diario Y del PUC completo
 */

include("connection.php");
header('Content-Type: application/json; charset=utf-8');

$conn = new connection();
$pdo = $conn->connect();

try {
    // Array para almacenar longitudes únicas
    $longitudes = [];

    // 1. Obtener longitudes de cuentas en libro_diario
    $sql_diario = "SELECT DISTINCT LENGTH(codigo_cuenta) as longitud FROM libro_diario";
    $stmt = $pdo->query($sql_diario);
    $resultados_diario = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($resultados_diario as $longitud) {
        if (!in_array($longitud, $longitudes)) {
            $longitudes[] = intval($longitud);
        }
    }

    // 2. Obtener longitudes de cuentas en cuentas_contables (PUC)
    $sql_puc = "SELECT nivel1, nivel2, nivel3, nivel4, nivel5, nivel6 FROM cuentas_contables LIMIT 1000";
    $stmt_puc = $pdo->query($sql_puc);
    $resultados_puc = $stmt_puc->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados_puc as $fila) {
        for ($i = 1; $i <= 6; $i++) {
            $campo = 'nivel' . $i;
            if (!empty($fila[$campo])) {
                $partes = explode('-', $fila[$campo], 2);
                if (count($partes) >= 1) {
                    $codigo = trim($partes[0]);
                    $longitud = strlen($codigo);
                    if (!in_array($longitud, $longitudes)) {
                        $longitudes[] = $longitud;
                    }
                }
            }
        }
    }

    // 3. Asegurar que existan los niveles estándar (1, 2, 4, 6, 8)
    $niveles_estandar = [1, 2, 4, 6, 8];
    foreach ($niveles_estandar as $nivel) {
        if (!in_array($nivel, $longitudes)) {
            $longitudes[] = $nivel;
        }
    }

    // Ordenar de menor a mayor
    sort($longitudes);

    // Mapear longitudes a nombres de tipos
    $mapeo_tipos = [
        1 => 'Clase (1 dígito)',
        2 => 'Grupo (2 dígitos)',
        4 => 'Cuenta (4 dígitos)',
        6 => 'Subcuenta (6 dígitos)',
        8 => 'Auxiliar (8 dígitos)',
        10 => 'Referencia (10 dígitos)'
    ];

    $tipos_disponibles = [];
    foreach ($longitudes as $longitud) {
        $label = isset($mapeo_tipos[$longitud]) ? $mapeo_tipos[$longitud] : "Nivel $longitud dígitos";
        $tipos_disponibles[] = [
            'value' => $longitud,
            'label' => $label,
            'digitos' => $longitud
        ];
    }

    echo json_encode($tipos_disponibles, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error al obtener niveles: " . $e->getMessage());
    
    // En caso de error, retornar niveles estándar
    $tipos_default = [
        ['value' => 1, 'label' => 'Clase (1 dígito)', 'digitos' => 1],
        ['value' => 2, 'label' => 'Grupo (2 dígitos)', 'digitos' => 2],
        ['value' => 4, 'label' => 'Cuenta (4 dígitos)', 'digitos' => 4],
        ['value' => 6, 'label' => 'Subcuenta (6 dígitos)', 'digitos' => 6],
        ['value' => 8, 'label' => 'Auxiliar (8 dígitos)', 'digitos' => 8]
    ];
    echo json_encode($tipos_default, JSON_UNESCAPED_UNICODE);
}
?>