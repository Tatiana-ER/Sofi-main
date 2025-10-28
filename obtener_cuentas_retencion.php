<?php
include("connection.php");
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

$conn = new connection();
$pdo = $conn->connect();

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $cuentas = [];
    $codigos_unicos = [];
    $codigos_prefijo = ['2365', '2366'];

    /* 1) BÚSQUEDA GLOBAL (si hay search)
       Recolecta cualquier nivel (1..6) que contenga el término*/
    if ($search !== '') {
        // Usamos UNION para devolver cualquier nivel que coincida con la búsqueda
        $sql_search = "
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

        $stmt = $pdo->prepare($sql_search);
        for ($i = 1; $i <= 6; $i++) {
            $stmt->bindValue(":s{$i}", "%$search%");
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $cu = $r['cuenta_final'];
            if (!empty($cu) && !isset($codigos_unicos[$cu])) {
                $cuentas[] = ['valor' => $cu, 'texto' => " " . $cu];
                $codigos_unicos[$cu] = true;
            }
        }

        // Buscar también en catálogo personalizado (auxiliar)
        $sql_cat = "SELECT DISTINCT auxiliar FROM catalogoscuentascontables WHERE auxiliar IS NOT NULL AND auxiliar <> '' AND LOWER(auxiliar) LIKE LOWER(:s) ORDER BY auxiliar LIMIT 200";
        $st2 = $pdo->prepare($sql_cat);
        $st2->execute([':s' => "%$search%"]);
        $rows2 = $st2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows2 as $r) {
            $aux = $r['auxiliar'];
            if (!empty($aux) && !isset($codigos_unicos[$aux])) {
                $cuentas[] = ['valor' => $aux, 'texto' => " (Personalizada) " . $aux];
                $codigos_unicos[$aux] = true;
            }
        }

        echo json_encode($cuentas, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* 2) SIN BÚSQUEDA: mostrar cuentas que empiecen por los prefijos
       Recolectamos cualquier nivel (1..6) que comience con 2365/2366*/
    // Armamos parámetros para prepared statements
    $params = [];
    $likeParts = [];
    foreach ($codigos_prefijo as $i => $pref) {
        $p = ":p{$i}";
        $params[$p] = "{$pref}%";
        $likeParts[] = $p;
    }
    // query por nivel usando los parámetros del array $params (PDO)
    // Nota: construimos la porción WHERE con placeholders
    $wherePrefix = implode(" OR ", array_map(function($p) {
        return "v.cuenta_final LIKE $p";
    }, $likeParts));

    // Usamos UNION para traer cualquier nivel (1..6) que comience por los prefijos
    $sql_prefix = "
        SELECT DISTINCT v.cuenta_final
        FROM (
            SELECT nivel1 AS cuenta_final FROM cuentas_contables WHERE nivel1 IS NOT NULL AND nivel1 <> ''
            UNION
            SELECT nivel2 FROM cuentas_contables WHERE nivel2 IS NOT NULL AND nivel2 <> ''
            UNION
            SELECT nivel3 FROM cuentas_contables WHERE nivel3 IS NOT NULL AND nivel3 <> ''
            UNION
            SELECT nivel4 FROM cuentas_contables WHERE nivel4 IS NOT NULL AND nivel4 <> ''
            UNION
            SELECT nivel5 FROM cuentas_contables WHERE nivel5 IS NOT NULL AND nivel5 <> ''
            UNION
            SELECT nivel6 FROM cuentas_contables WHERE nivel6 IS NOT NULL AND nivel6 <> ''
        ) v
        WHERE ({$wherePrefix})
        ORDER BY LENGTH(v.cuenta_final), v.cuenta_final
        LIMIT 500
    ";

    $stmt = $pdo->prepare($sql_prefix);
    // bind dinámico de parámetros
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $cu = $r['cuenta_final'];
        if (!empty($cu) && !isset($codigos_unicos[$cu])) {
            $cuentas[] = ['valor' => $cu, 'texto' => $cu];
            $codigos_unicos[$cu] = true;
        }
    }

    /* 3) Añadimos auxiliares personalizados que empiecen por los prefijos*/
    $condCatParts = [];
    $catParams = [];
    foreach ($codigos_prefijo as $i => $pref) {
        $pp = ":cp{$i}";
        $catParams[$pp] = "{$pref}%";
        $condCatParts[] = "auxiliar LIKE $pp";
    }
    $sql_personalizadas = "SELECT DISTINCT auxiliar FROM catalogoscuentascontables WHERE (" . implode(" OR ", $condCatParts) . ") AND auxiliar IS NOT NULL AND auxiliar <> '' ORDER BY auxiliar LIMIT 500";
    $stC = $pdo->prepare($sql_personalizadas);
    foreach ($catParams as $k => $v) { $stC->bindValue($k, $v); }
    $stC->execute();
    $rowsC = $stC->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsC as $r) {
        $aux = $r['auxiliar'];
        if (!empty($aux) && !isset($codigos_unicos[$aux])) {
            $cuentas[] = ['valor' => $aux, 'texto' => "(Personalizada) " . $aux];
            $codigos_unicos[$aux] = true;
        }
    }

    echo json_encode($cuentas, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([['error' => true, 'msg' => 'Error interno: ' . $e->getMessage()]]);
}
?>
