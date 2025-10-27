<?php
include("connection.php");
header('Content-Type: application/json');

$conn = new connection();
$pdo = $conn->connect();

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$cuentas = [];
$codigos_unicos = [];

// Definir los filtros según el tipo solicitado
switch ($tipo) {
    case 'ventas':
        // Clase 4 - Utilizando el método de la participación, neto de impuestos
        $filtro = "nivel1 LIKE '4-%'";
        break;
    case 'inventarios':
        // Grupo 14 - Inventarios
        $filtro = "nivel2 LIKE '14-%' OR nivel3 LIKE '14-%'";
        break;
    case 'costos':
        // Clase 6 - Gastos por costo de ventas
        $filtro = "nivel1 LIKE '6-%'";
        break;
    case 'devoluciones':
        // Cuenta 4175 - Devolución en ventas (db)
        $filtro = "nivel3 LIKE '4175-%'";
        break;
    default:
        echo json_encode([]);
        exit;
}

// Consulta a la tabla de cuentas_contables
$sql = "
    SELECT DISTINCT nivel1, nivel2, nivel3, nivel4, nivel5, nivel6
    FROM cuentas_contables
    WHERE {$filtro}
    ORDER BY nivel1, nivel2, nivel3, nivel4, nivel5, nivel6
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Armar la jerarquía y extraer el nombre puro
foreach ($resultados as $row) {

    // Iterar sobre los niveles del 1 al 6
    for ($i = 1; $i <= 6; $i++) {
        $nivel_completo = $row['nivel' . $i]; // Ej: "71-Materia prima"
        
        if (!empty($nivel_completo) && !isset($codigos_unicos[$nivel_completo])) {
            
            // 1. Separar el código y el nombre
            // Buscamos el primer guion para dividir: [código, descripción]
            $partes = explode('-', $nivel_completo, 2); 
            $codigo = trim($partes[0]);
            
            // Si tiene guion, el nombre puro es la segunda parte (o vacío si no tiene guion)
            $nombre_puro = count($partes) > 1 ? trim($partes[1]) : ''; 

            // 2. Definir el texto a mostrar en el Select2
            $prefijo = str_repeat("  ", $i - 1);
            // Mostrar: [prefijo] [código] - [nombre]
            $texto_select = $prefijo . $codigo . ' - ' . $nombre_puro;

            // 3. Agregar a las cuentas (usamos el código como valor)
            $cuentas[] = [
                // Este es el valor real (ej: 71, 7105, 710501)
                'valor' => $codigo, 
                // Este es el texto con jerarquía para Select2
                'texto' => $texto_select,
                // Nuevo campo para el input de texto inferior
                'nombre_puro' => $nombre_puro
            ];
            $codigos_unicos[$nivel_completo] = true;
        }
    }
}

// Devolver en formato JSON
echo json_encode($cuentas, JSON_UNESCAPED_UNICODE);
?>
