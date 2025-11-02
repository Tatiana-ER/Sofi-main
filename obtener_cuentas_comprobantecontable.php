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
    // Buscar primero en cuentas_contables
    $sql_id = "
        SELECT DISTINCT v.cuenta_final
        FROM (
            SELECT nivel1 AS cuenta_final FROM cuentas_contables WHERE nivel1 = :id1
            UNION
            SELECT nivel2 FROM cuentas_contables WHERE nivel2 = :id2
            UNION
            SELECT nivel3 FROM cuentas_contables WHERE nivel3 = :id3
            UNION
            SELECT nivel4 FROM cuentas_contables WHERE nivel4 = :id4
            UNION
            SELECT nivel5 FROM cuentas_contables WHERE nivel5 = :id5
            UNION
            SELECT nivel6 FROM cuentas_contables WHERE nivel6 = :id6
        ) v
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql_id);
    for ($i = 1; $i <= 6; $i++) {
        $stmt->bindValue(":id{$i}", $id);
    }
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        $cuentas_completas[] = [
            'valor' => $resultado['cuenta_final'],
            'texto' => $resultado['cuenta_final']
        ];
    } else {
        // Si no está en cuentas_contables, buscar en catalogoscuentascontables
        $sql_catalogo_id = "SELECT auxiliar FROM catalogoscuentascontables WHERE auxiliar = :id LIMIT 1";
        $stmt2 = $pdo->prepare($sql_catalogo_id);
        $stmt2->execute([':id' => $id]);
        $resultado2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado2) {
            $cuentas_completas[] = [
                'valor' => $resultado2['auxiliar'],
                'texto' => "(Personalizada) " . $resultado2['auxiliar']
            ];
        }
    }
    
    echo json_encode($cuentas_completas, JSON_UNESCAPED_UNICODE);
    exit;
}

// Búsqueda general (cuando el usuario escribe en el select)
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

// Si no hay búsqueda ni ID, devolver vacío
echo json_encode([]);