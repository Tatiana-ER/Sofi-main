<?php

    include("conexion.php");

    if(isset($_POST['send'])) {
        if(
            strlen($_POST['tipoPersona']) >= 1 &&
            strlen($_POST['cedula']) >= 1 &&
            strlen($_POST['nit']) >= 1 &&
            strlen($_POST['digito']) >= 1 &&
            strlen($_POST['nombres']) >= 1 &&
            strlen($_POST['apellidos']) >= 1 &&
            strlen($_POST['razonSocial']) >= 1 &&
            strlen($_POST['departamento']) >= 1 &&
            strlen($_POST['ciudad']) >= 1 &&
            strlen($_POST['direccion']) >= 1 &&
            strlen($_POST['email']) >= 1 &&
            strlen($_POST['tipoRegimen']) >= 1 &&
            strlen($_POST['actividadEconomica']) >= 1 &&
            strlen($_POST['tarifaIca']) >= 1 &&
            strlen($_POST['manejoAiu']) >= 1 &&
            strlen($_POST['seleccionadas']) >= 1
        ) {
            $tipoPersona = trim($_POST['tipoPersona']);
            $cedula = trim($_POST['cedula']);
            $nit = trim($_POST['nit']);
            $digito = trim($_POST['digito']);
            $nombres = trim($_POST['nombres']);
            $apellidos = trim($_POST['apellidos']);
            $razonSocial = trim($_POST['razonSocial']);
            $departamento = trim($_POST['departamento']);
            $ciudad = trim($_POST['ciudad']);
            $direccion = trim($_POST['direccion']);
            $email = trim($_POST['email']);
            $tipoRegimen = trim($_POST['tipoRegimen']);
            $actividadEconomica = trim($_POST['actividadEconomica']);
            $tarifaIca = trim($_POST['tarifaIca']);
            $manejoAiu = trim($_POST['manejoAiu']);
            $seleccionadas = trim($_POST['seleccionadas']);
            $consulta = "INSERT INTO perfil(persona, cedula, nit, digito, nombres, apellidos, razon, departamento, ciudad, direccion, email, regimen, actividad, tarifa, aiu, responsabilidad)
                        VALUES ( '$tipoPersona', '$cedula', '$nit', '$digito', '$nombres', '$apellidos', '$razonSocial', '$departamento', '$ciudad', 
                        '$direccion', '$email', '$tipoRegimen', '$actividadEconomica', '$tarifaIca', '$manejoAiu', '$seleccionadas')";
            $resultado = mysqli_query($conex, $consulta);
            if($resultado) {
                ?>
                    <h3 class="success">Tu registro se ha completado</h3>
                <?php
            } else {
                ?>
                <h3 class="error">Ocurrio un error</h3>
                <?php
            }
        } else {
            ?>
            <h3 class="error">Completa los campos obligatorios</h3>
            <?php
    }
}

?>