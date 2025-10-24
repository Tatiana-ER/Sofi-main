<?php
include("connection.php"); // usa tu archivo de conexión

$conn = new connection();
$pdo = $conn->connect(); // <== ESTA es la conexión real (MySQLi o PDO)

// =========================
// LEER Y DECODIFICAR JSON
// =========================
$jsonFile = 'cuentas_contables.json';
$data = json_decode(file_get_contents($jsonFile), true);

if (!$data) {
    die("Error al leer el JSON");
}

// =========================
// FUNCIÓN RECURSIVA
// =========================
function recorrer($array, $niveles = [], $pdo) {
    foreach ($array as $clave => $valor) {
        $niveles_actualizados = $niveles;
        $niveles_actualizados[] = $clave;

        if (is_array($valor)) {
            if (array_keys($valor) === range(0, count($valor) - 1)) {
                // Es una lista (último nivel)
                foreach ($valor as $item) {
                    $niveles_finales = $niveles_actualizados;
                    $niveles_finales[] = $item;
                    guardarEnBD($niveles_finales, $pdo);
                }
            } else {
                // Es un subnivel (diccionario)
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
// GUARDAR EN BASE DE DATOS
// =========================
function guardarEnBD($niveles, $pdo) {
    $niveles = array_pad($niveles, 6, null); // Rellena hasta 6 niveles

    // Si tu conexión es PDO:
    if ($pdo instanceof PDO) {
        $stmt = $pdo->prepare("
            INSERT INTO cuentas_contables (nivel1, nivel2, nivel3, nivel4, nivel5, nivel6)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute($niveles);
    }

    // Si tu conexión es MySQLi:
    elseif ($pdo instanceof mysqli) {
        $stmt = $pdo->prepare("
            INSERT INTO cuentas_contables (nivel1, nivel2, nivel3, nivel4, nivel5, nivel6)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", ...$niveles);
        $stmt->execute();
    }
}

// =========================
// EJECUCIÓN
// =========================
recorrer($data, [], $pdo);
echo "✅ Importación completada con éxito";

?>
