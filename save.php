<?php

include ("connection.php");

if (isset($_POST['agregar'])){
    $tipoTercero = $_POST['tipoTercero'];
    $tipoPersona = $_POST['tipoPersona'];
    $cedula = $_POST['cedula'];
    $nit = $_POST['nit'];
    $digito = $_POST['digito'];
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $razonSocial = $_POST['razonSocial'];
    $departamento = $_POST['departamento'];
    $ciudad = $_POST['ciudad'];
    $direccion = $_POST['direccion'];
    $razonSocial = $_POST['cedula'];
    $telefono = $_POST['telefono'];
    $tipoRegimen = $_POST['tipoRegimen'];
    $actividadEconomica = $_POST['actividadEconomica'];
    $activo = $_POST['activo'];

    $query("INSERT INTO catalogosterceros(tipoTercero,tipoPersona,cedula,nit,digito,nombres,apellidos,razonSocial,departamento,ciudad,direccion,telefono,correo,tipoRegimen,actividadEconomica,activo) 
      VALUES ('$tipoTercero', '$tipoPersona', '$cedula', '$nit', '$digito', '$nombres', '$apellidos', '$razonSocial', '$departamento', '$ciudad', '$direccion', '$telefono', '$correo', '$tipoRegimen', '$actividadEconomica', '$activo')");
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query Failed");

    }

    echo 'save';
}

?>