<?php
require_once '../config/database.php';
$pdo = Database::getConnection();

if (isset($_POST['agregar'])) {
    $campos = [
        'tipoTercero', 'tipoPersona', 'cedula', 'nit', 'digito',
        'nombres', 'apellidos', 'razonSocial', 'departamento',
        'ciudad', 'direccion', 'telefono', 'tipoRegimen',
        'actividadEconomica', 'activo'
    ];

    $datos = [];
    foreach ($campos as $campo) {
        $datos[$campo] = trim($_POST[$campo] ?? '');
    }

    // Nota: razonSocial se mapea a cedula en el original (posible bug heredado)
    $sql = "INSERT INTO catalogosterceros
                (tipoTercero, tipoPersona, cedula, nit, digito, nombres, apellidos,
                 razonSocial, departamento, ciudad, direccion, telefono, correo,
                 tipoRegimen, actividadEconomica, activo)
            VALUES
                (:tipoTercero, :tipoPersona, :cedula, :nit, :digito, :nombres, :apellidos,
                 :razonSocial, :departamento, :ciudad, :direccion, :telefono, '',
                 :tipoRegimen, :actividadEconomica, :activo)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($datos);
        echo 'save';
    } catch (PDOException $e) {
        error_log("Error en save.php: " . $e->getMessage());
        die("Query Failed");
    }
}
?>
