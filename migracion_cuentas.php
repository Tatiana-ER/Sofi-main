<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// =========================
// LEER Y DECODIFICAR JSON
// =========================
$jsonFile = 'cuentas_contables.json';
$data = json_decode(file_get_contents($jsonFile), true);

if (!$data) {
    die("❌ Error al leer el archivo JSON");
}

// =========================
// FUNCIÓN RECURSIVA (con soporte a arrays vacíos)
// =========================
function recorrer($array, $niveles = [], $pdo) {
    foreach ($array as $clave => $valor) {
        $niveles_actualizados = $niveles;
        $niveles_actualizados[] = $clave;

        if (is_array($valor)) {
            if (empty($valor)) {
                // Si el array está vacío, igual se guarda (es una cuenta final)
                guardarEnBD($niveles_actualizados, $pdo);
            } elseif (array_keys($valor) === range(0, count($valor) - 1)) {
                // Es una lista (último nivel con subcuentas)
                foreach ($valor as $item) {
                    $niveles_finales = $niveles_actualizados;
                    $niveles_finales[] = $item;
                    guardarEnBD($niveles_finales, $pdo);
                }
            } else {
                // Tiene subniveles → seguir recorriendo
                recorrer($valor, $niveles_actualizados, $pdo);
            }
        } else {
            // Valor directo (texto)
            $niveles_finales = $niveles_actualizados;
            $niveles_finales[] = $valor;
            guardarEnBD($niveles_finales, $pdo);
        }
    }
}

// =========================
// FUNCIÓN GUARDAR EN BD
// =========================
function guardarEnBD($niveles, $pdo) {
    // Rellenar con null hasta 6 niveles
    $niveles = array_pad($niveles, 6, null);

    // Determinar el último nivel no nulo (por el que se validará duplicado)
    $ultimoNivel = null;
    for ($i = 5; $i >= 0; $i--) {
        if (!empty($niveles[$i])) {
            $ultimoNivel = $niveles[$i];
            break;
        }
    }

    if ($ultimoNivel) {
        // Comprobar si ya existe esa cuenta en cualquier nivel
        $sqlCheck = "
            SELECT COUNT(*) FROM cuentas_contables
            WHERE nivel1 = :n1 AND nivel2 = :n2 AND nivel3 = :n3
              AND nivel4 = :n4 AND nivel5 = :n5 AND nivel6 = :n6
        ";
        $stmt = $pdo->prepare($sqlCheck);
        $stmt->execute([
            ':n1' => $niveles[0],
            ':n2' => $niveles[1],
            ':n3' => $niveles[2],
            ':n4' => $niveles[3],
            ':n5' => $niveles[4],
            ':n6' => $niveles[5],
        ]);

        $existe = $stmt->fetchColumn();

        if ($existe == 0) {
            // Insertar solo si no existe
            $sqlInsert = "
                INSERT INTO cuentas_contables (nivel1, nivel2, nivel3, nivel4, nivel5, nivel6)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmt = $pdo->prepare($sqlInsert);
            $stmt->execute($niveles);
            echo "✅ Insertada: " . $ultimoNivel . "<br>";
        } else {
            echo "⚠️ Ya existe: " . $ultimoNivel . "<br>";
        }
    }
}

// =========================
// EJECUCIÓN
// =========================
recorrer($data, [], $pdo);

echo "<hr><strong>✅ Importación completada sin duplicados.</strong>";
?>
