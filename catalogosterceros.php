<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$tipoTercero=(isset($_POST['tipoTercero']))?$_POST['tipoTercero']:"";
$tipoPersona=(isset($_POST['tipoPersona']))?$_POST['tipoPersona']:"";
$cedula=(isset($_POST['cedula']))?$_POST['cedula']:"";
$digito=(isset($_POST['digito']))?$_POST['digito']:"";
$nombres=(isset($_POST['nombres']))?$_POST['nombres']:"";
$apellidos=(isset($_POST['apellidos']))?$_POST['apellidos']:"";
$razonSocial=(isset($_POST['razonSocial']))?$_POST['razonSocial']:"";
$departamento=(isset($_POST['departamento']))?$_POST['departamento']:"";
$ciudad=(isset($_POST['ciudad']))?$_POST['ciudad']:"";
$direccion=(isset($_POST['direccion']))?$_POST['direccion']:"";
$telefono=(isset($_POST['telefono']))?$_POST['telefono']:"";
$correo=(isset($_POST['correo']))?$_POST['correo']:"";
$tipoRegimen=(isset($_POST['tipoRegimen']))?$_POST['tipoRegimen']:"";
$actividadEconomica=(isset($_POST['actividadEconomica']))?$_POST['actividadEconomica']:"";
$activo=(isset($_POST['activo']))?$_POST['activo']:"";

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

switch($accion){
  case "btnAgregar":

      $sentencia=$pdo->prepare("INSERT INTO catalogosterceros(tipoTercero,tipoPersona,cedula,digito,nombres,apellidos,razonSocial,departamento,ciudad,direccion,telefono,correo,
      tipoRegimen,actividadEconomica,activo) 
      VALUES (:tipoTercero,:tipoPersona,:cedula,:digito,:nombres,:apellidos,:razonSocial,:departamento,:ciudad,:direccion,:telefono,:correo,:tipoRegimen,:actividadEconomica,:activo)");
      

      $sentencia->bindParam(':tipoTercero',$tipoTercero);
      $sentencia->bindParam(':tipoPersona',$tipoPersona);
      $sentencia->bindParam(':cedula',$cedula);
      $sentencia->bindParam(':digito',$digito);
      $sentencia->bindParam(':nombres',$nombres);
      $sentencia->bindParam(':apellidos',$apellidos);
      $sentencia->bindParam(':razonSocial',$razonSocial);
      $sentencia->bindParam(':departamento',$departamento);
      $sentencia->bindParam(':ciudad',$ciudad);
      $sentencia->bindParam(':direccion',$direccion);
      $sentencia->bindParam(':telefono',$telefono);
      $sentencia->bindParam(':correo',$correo);
      $sentencia->bindParam(':tipoRegimen',$tipoRegimen);
      $sentencia->bindParam(':actividadEconomica',$actividadEconomica);
      $sentencia->bindParam(':activo',$activo);
      $sentencia->execute();

  break;

  case "btnModificar":
        $sentencia = $pdo->prepare("UPDATE catalogosterceros 
                                    SET tipoTercero = :tipoTercero,
                                        tipoPersona = :tipoPersona,
                                        cedula = :cedula,
                                        digito = :digito,
                                        nombres = :nombres,
                                        apellidos = :apellidos,
                                        razonSocial = :razonSocial,
                                        departamento = :departamento,
                                        ciudad = :ciudad,
                                        direccion = :direccion,
                                        telefono = :telefono,
                                        correo = :correo,
                                        tipoRegimen = :tipoRegimen,
                                        actividadEconomica = :actividadEconomica,
                                        activo = :activo
                                    WHERE id = :id");

        // Enlazamos los parámetros 

        $sentencia->bindParam(':tipoTercero', $tipoTercero);
        $sentencia->bindParam(':tipoPersona', $tipoPersona);
        $sentencia->bindParam(':cedula', $cedula);
        $sentencia->bindParam(':digito', $digito);
        $sentencia->bindParam(':nombres', $nombres);
        $sentencia->bindParam(':apellidos', $apellidos);
        $sentencia->bindParam(':razonSocial', $razonSocial);
        $sentencia->bindParam(':departamento', $departamento);
        $sentencia->bindParam(':ciudad', $ciudad);
        $sentencia->bindParam(':direccion', $direccion);
        $sentencia->bindParam(':telefono', $telefono);
        $sentencia->bindParam(':correo', $correo);
        $sentencia->bindParam(':tipoRegimen', $tipoRegimen);
        $sentencia->bindParam(':actividadEconomica', $actividadEconomica);
        $sentencia->bindParam(':activo', $activo);
        $sentencia->bindParam(':id', $txtId);

        // Ejecutamos la sentencia
        $sentencia->execute();

        // Opcional: Redirigir o mostrar mensaje de éxito
        echo "<script>alert('Datos actualizados correctamente');</script>";

    break;

    case "btnEliminar":

      $sentencia = $pdo->prepare("DELETE FROM catalogosterceros WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();


    break;

}

  $sentencia= $pdo->prepare("SELECT * FROM `catalogosterceros` WHERE 1");
  $sentencia->execute();
  $lista=$sentencia->fetchALL(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SOFI - UDES</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <link href="assets/css/style.css" rel="stylesheet">

  <style> 
    .table-container {
      margin: 0 auto;
      padding: 20px;
      max-width: 95%;
      width: 100%;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
      background-color: #ffffff;
      border-radius: 5px;
      overflow-x: auto; /* Importante: activa el scroll horizontal */
    }

    table {
      min-width: 1000px; /* O el valor mínimo que quieras para permitir el scroll */
      width: max-content; /* Se adapta al contenido */
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid black;
      padding: 10px;
      text-align: center;
      white-space: nowrap; /* Evita que el texto se rompa en varias líneas */
    }

    th {
      background-color: #f2f2f2;
    }

    input[type="text"] {
      width: 100%;
      box-sizing: border-box;
      padding: 5px;
    }

    .add-row-btn {
      cursor: pointer;
      background-color: #0d6efd;
      color: white;
      border: none;
      padding: 10px;
      font-size: 18px;
      margin-top: 20px;
    }
  </style>

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo"><a href="dashboard.php"> S O F I </a>  = >  Software Financiero </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li>
            <a class="nav-link scrollto active" href="dashboard.php" style="color: darkblue;">Inicio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="perfil.php" style="color: darkblue;">Mi Negocio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="index.php" style="color: darkblue;">Cerrar Sesión</a>
          </li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

    <!-- ======= Services Section ======= -->
    <section id="services" class="services">
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <br><br><br><br><br>
          <h2>CATÁLOGO DE TERCEROS </h2>
          <p>A continuación puede ingresar a los catálogos configurados para su usuario en el sistema.</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>

        <form id="formularioTercero" action="" method="post">
          <div>
            <label for="id" class="form-label">ID:</label>
            <input type="text" class="form-control" value="<?php echo $txtId;?>" id="txtId" name="txtId" readonly>
          </div><br>
          <div>
            <label for="label3" class="form-label">Tipo de tercero*</label>
            <br>
            <label>
              <input type="radio" value="<?php echo $tipoTercero;?>" name="tipoTercero" value="cliente" onclick="toggleTipoTercero()">
              Cliente
            </label>
            <label>
              <input type="radio" value="<?php echo $tipoTercero;?>" name="tipoTercero" value="proveedor" onclick="toggleTipoTercero()">
              Proveedor
            </label>
            <label>
              <input type="radio" value="<?php echo $tipoTercero;?>" name="tipoTercero" value="otro" onclick="toggleTipoTercero()">
              Otro
            </label>
          </div>
          <br>
          <div>
            <label for="label3" class="form-label">Tipo de persona*</label>
            <br>
            <label>
              <input type="radio" value="<?php echo $tipoPersona;?>" id="personaNatural" name="tipoPersona" value="natural" onclick="toggleFields()">
              Persona Natural
            </label>
            <label>
              <input type="radio" value="<?php echo $tipoPersona;?>" id="personaJuridica" name="tipoPersona" value="juridica" onclick="toggleFields()">
              Persona Jurídica
            </label>
          </div>
          <br> 
          <div>
            <label for="cedula" class="form-label">Cédula de Ciudadanía o NIT*</label>
            <input type="number" class="form-control" value="<?php echo $cedula;?>" id="cedula" name="cedula" required>
          </div>
          <br>
          <div>
          <label for="digito" class="form-label">Digito de verificación</label>
          <input type="text"class="form-control" value="<?php echo $digito;?>" id="digito" name="digito" maxlength="1" pattern="[1-9]" title="Solo se permite un dígito entre 1 y 9" placeholder="Dígito entre 1 y 9">
          <br>
          </div>
          <div>
            <label for="nombres" class="form-label">Nombres</label>
            <input type="text" class="form-control" value="<?php echo $nombres;?>" id="nombres"  name="nombres" disabled>
          </div>
          <div>
            <label for="apellidos" class="form-label">Apellidos</label>
            <input type="text" class="form-control" value="<?php echo $apellidos;?>" id="apellidos" name="apellidos" disabled>
          </div>
          <div>
            <label for="razonSocial" class="form-label">Razón Social</label>
            <input type="text" class="form-control" value="<?php echo $razonSocial;?>" id="razonSocial" name="razonSocial" disabled>       
          </div>
          <br>
          <div class="form-group">
            <label for="departamento" class="form-label">Departamento*</label>
            <input type="text" value="<?php echo $departamento;?>" id="departamento" name="departamento" class="form-control" placeholder="Buscar departamento..." autocomplete="off" required>
          </div>
          <div class="form-group mt-3">
            <label for="ciudad" class="form-label">Ciudad*</label>
            <select value="<?php echo $ciudad;?>" id="ciudad" name="ciudad" class="form-control" disabled>
                <option value="">Selecciona una ciudad...</option>
            </select>
          </div>
          <br>
          <div class="mb-3">
            <label for="direccion" class="form-label">Dirección*</label>
            <input type="text" class="form-control" value="<?php echo $direccion;?>" id="direccion"  name="direccion" placeholder="ej: Cll 12 #52-16" required>
          </div>
          <div class="mb-3">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="number" class="form-control" value="<?php echo $telefono;?>" id="telefono" name="telefono" placeholder="">
          </div>
          <div class="mb-3">
            <label for="correo" class="form-label">Correo Electronico*</label>
            <input type="email" class="form-control" value="<?php echo $correo;?>" id="correo" name="correo" placeholder="example@correo.com" required>
          </div>
          <div>
            <label for="exampleFormControlInput1" class="form-label">Tipo de regimen*</label>
            <select class="form-select" value="<?php echo $tipoRegimen;?>" id=tipoRegimen name="tipoRegimen" aria-label="Default select example" required>
              <option selected>Seleccione un tipo de regimen</option>
              <option value="Responsable de IVA">Responsable de IVA</option>
              <option value="No responsable de IVA">No responsable de IVA</option>
              <option value="Regimen simple de tributación">Regimen simple de tributación</option>
              <option value="Regimen simple de tributación">Regimen especial</option>
            </select>
          </div>
          <br>
          <div>
            <label for="actividadEconomica" class="form-label">Actividad Económica</label>
            <input type="text" class="form-control" value="<?php echo $actividadEconomica;?>" id="actividadEconomica" name="actividadEconomica" disabled>
          </div>
          <br>
          <div class="mb-3">
            <label for="activo" class="form-label">Activo</label>
            <input type="checkbox" class="" value="<?php echo $activo;?>" id="activo" name="activo" placeholder="">
          </div>
          <br>
          <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Agregar</button>
          <button value="btnModificar" type="submit" class="btn btn-primary"  name="accion" >Modificar</button>
          <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
        </form>

        <div class="row">
          <div class="table-container">

            <table>
              <thead>
                <tr>
                  <th>Tipo tercero</th>
                  <th>Tipo persona</th>
                  <th>Cedula o NIT</th>
                  <th>Digito</th>
                  <th>Nombres</th>
                  <th>Apellidos</th>
                  <th>Razon social</th>
                  <th>Departamento</th>
                  <th>Ciudad</th>
                  <th>Dirección</th>
                  <th>Telefono</th>
                  <th>Correo</th>
                  <th>Tipo de regimen</th>
                  <th>Actividad economica</th>
                  <th>Activo</th>
                  <th>Acción</th>
                </tr>
              </thead>

              <?php foreach($lista as $usuario){ ?>
              <tr>
                <td><?php echo $usuario['tipoTercero']; ?></td>
                <td><?php echo $usuario['tipoPersona']; ?></td>
                <td><?php echo $usuario['cedula']; ?></td>
                <td><?php echo $usuario['digito']; ?></td>
                <td><?php echo $usuario['nombres']; ?></td>
                <td><?php echo $usuario['apellidos']; ?></td>
                <td><?php echo $usuario['razonSocial']; ?></td>
                <td><?php echo $usuario['departamento']; ?></td>
                <td><?php echo $usuario['ciudad']; ?></td>
                <td><?php echo $usuario['direccion']; ?></td>
                <td><?php echo $usuario['telefono']; ?></td>
                <td><?php echo $usuario['tipoRegimen']; ?></td>
                <td><?php echo $usuario['correo']; ?></td>
                <td><?php echo $usuario['actividadEconomica']; ?></td>
                <td><?php echo $usuario['activo']; ?></td>
                <td>

                <form action="" method="post">

                <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
                <input type="hidden" name="tipoTercero" value="<?php echo $usuario['tipoTercero']; ?>" >
                <input type="hidden" name="tipoPersona" value="<?php echo $usuario['tipoPersona']; ?>" >
                <input type="hidden" name="cedula" value="<?php echo $usuario['cedula']; ?>" >
                <input type="hidden" name="digito" value="<?php echo $usuario['digito']; ?>" >
                <input type="hidden" name="nombres" value="<?php echo $usuario['nombres']; ?>" >
                <input type="hidden" name="apellidos" value="<?php echo $usuario['apellidos']; ?>" >
                <input type="hidden" name="razonSocial" value="<?php echo $usuario['razonSocial']; ?>" >
                <input type="hidden" name="departamento" value="<?php echo $usuario['departamento']; ?>" >
                <input type="hidden" name="ciudad" value="<?php echo $usuario['ciudad']; ?>" >
                <input type="hidden" name="direccion" value="<?php echo $usuario['direccion']; ?>" >
                <input type="hidden" name="telefono" value="<?php echo $usuario['telefono']; ?>" >
                <input type="hidden" name="correo" value="<?php echo $usuario['correo']; ?>" >
                <input type="hidden" name="tipoRegimen" value="<?php echo $usuario['tipoRegimen']; ?>" >
                <input type="hidden" name="actividadEconomica" value="<?php echo $usuario['actividadEconomica']; ?>" >
                <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>" >
                <input type="submit" value="Editar" name="accion">
                <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
                </form>

                </td>

              </tr>
            <?php } ?>
            </table>

          
          </div>
          
        </div>



      <script>


        document.addEventListener('DOMContentLoaded', function () {
            const departamentos = {
              'Amazonas': [
                          'Leticia', 'El Encanto', 'La Chorrera', 'La Pedrera', 
                          'La Victoria', 'Mirití - Paraná', 'Puerto Alegría', 
                          'Puerto Arica', 'Puerto Nariño', 'Puerto Santander', 
                          'Tarapacá'
                    ],
                    'Antioquia': [
                        'Medellín', 'Abejorral', 'Abriaqui', 'Alejandria', 'Amaga', 'Amalfi', 'Andes', 
                        'Angelopolis', 'Angostura', 'Anori', 'Santafe de Antioquia', 'Anza', 'Apartado', 
                        'Arboletes', 'Argelia', 'Armenia', 'Barbosa', 'Belmira', 'Bello', 'Betania', 'Betulia', 
                        'Ciudad Bolivar', 'Briceño', 'Buritica', 'Caceres', 'Caicedo', 'Caldas', 'Campamento', 
                        'Cañasgordas', 'Caracoli', 'Caramanta', 'Carepa', 'El Carmen de Viboral', 'Carolina', 
                        'Caucasia', 'Chigorodo', 'Cisneros', 'Cocorna', 'Concepcion', 'Concordia', 'Copacabana', 
                        'Dabeiba', 'Don Matias', 'Ebejico', 'El Bagre', 'Entrerrios', 'Envigado', 'Fredonia', 
                        'Frontino', 'Giraldo', 'Girardota', 'Gomez Plata', 'Granada', 'Guadalupe', 'Guarne', 
                        'Guatape', 'Heliconia', 'Hispania', 'Itagui', 'Ituango', 'Jardin', 'Jerico', 'La Ceja', 
                        'La Estrella', 'La Pintada', 'La Union', 'Liborina', 'Maceo', 'Marinilla', 'Montebello', 
                        'Murindo', 'Mutata', 'Nariño', 'Necocli', 'Nechi', 'Olaya', 'Peñol', 'Peque', 'Pueblorrico', 
                        'Puerto Berrio', 'Puerto Nare', 'Puerto Triunfo', 'Remedios', 'Retiro', 'Rionegro', 
                        'Sabanalarga', 'Sabaneta', 'Salgar', 'San Andres de Cuerquia', 'San Carlos', 'San Francisco', 
                        'San Jeronimo', 'San Jose de la Montaña', 'San Juan de Uraba', 'San Luis', 'San Pedro', 
                        'San Pedro de Uraba', 'San Rafael', 'San Roque', 'San Vicente', 'Santa Barbara', 
                        'Santa Rosa de Osos', 'Santo Domingo', 'El Santuario', 'Segovia', 'Sonson', 'Sopetran', 
                        'Tamesis', 'Taraza', 'Tarso', 'Titiribi', 'Toledo', 'Turbo', 'Uramita', 'Urrao', 'Valdivia', 
                        'Valparaiso', 'Vegachi', 'Venecia', 'Vigia del Fuerte', 'Yali', 'Yarumal', 'Yolombo', 'Yondo', 
                        'Zaragoza' 
                    ],
                    'Arauca': [
                          'Arauca', 'Arauquita', 'Cravo Norte', 'Fortul', 
                          'Puerto Rondon', 'Saravena', 'Tame'
                    ],
                    'Atlántico': [
                        'Barranquilla', 'Baranoa', 'Campo de la Cruz', 
                        'Candelaria', 'Galapa', 'Juan de Acosta', 
                        'Luruaco', 'Malambo', 'Manatí', 
                        'Palmar de Varela', 'Piojo', 'Polonuevo', 
                        'Ponedera', 'Puerto Colombia', 'Repelón', 
                        'Sabanagrande', 'Sabanalarga', 'Santa Lucía', 
                        'Santo Tomás', 'Soledad', 'Suan', 
                        'Tubará', 'Usiacurí'
                    ],
                    'Bolivar': [
                        'Cartagena', 'Achi', 'Altos del Rosario', 'Arenal', 'Arjona', 'Arroyohondo', 'Barranco de Loba', 
                        'Calamar', 'Cantagallo', 'Cicuco', 'Cordoba', 'Clemencia', 'El Carmen de Bolivar', 'El Guamo', 
                        'El Peñon', 'Hatillo de Loba', 'Magangue', 'Mahates', 'Margarita', 'Maria la Baja', 
                        'Montecristo', 'Mompos', 'Norosi', 'Morales', 'Pinillos', 'Regidor', 'Rio Viejo', 
                        'San Cristobal', 'San Estanislao', 'San Fernando', 'San Jacinto', 'San Jacinto del Cauca', 
                        'San Juan Nepomuceno', 'San Martin de Loba', 'San Pablo', 'Santa Catalina', 'Santa Rosa', 
                        'Santa Rosa del Sur', 'Simiti', 'Soplaviento', 'Talaigua Nuevo', 'Tiquisio', 'Turbaco', 
                        'Turbana', 'Villanueva', 'Zambrano'
                    ],
                    'Boyaca': [
                          'Tunja', 'Almeida', 'Aquitania', 'Arcabuco', 'Belen', 'Berbeo', 'Beteitiva', 
                          'Boavita', 'Boyaca', 'Briceño', 'Buenavista', 'Busbanza', 'Caldas', 'Campohermoso', 
                          'Cerinza', 'Chinavita', 'Chiquinquira', 'Chiscas', 'Chita', 'Chitaraque', 
                          'Chivata', 'Cienega', 'Combita', 'Coper', 'Corrales', 'Covarachia', 'Cubara', 
                          'Cucaita', 'Cuitiva', 'Chiquiza', 'Chivor', 'Duitama', 'El Cocuy', 'El Espino', 
                          'Firavitoba', 'Floresta', 'Gachantiva', 'Gameza', 'Garagoa', 'Guacamayas', 
                          'Guateque', 'Guayata', 'Gsican', 'Iza', 'Jenesano', 'Jerico', 'Labranzagrande', 
                          'La Capilla', 'La Victoria', 'La Uvita', 'Villa de Leyva', 'Macanal', 'Maripi', 
                          'Miraflores', 'Mongua', 'Mongui', 'Moniquira', 'Motavita', 'Muzo', 'Nobsa', 
                          'Nuevo Colon', 'Oicata', 'Otanche', 'Pachavita', 'Paez', 'Paipa', 'Pajarito', 
                          'Panqueba', 'Pauna', 'Paya', 'Paz de Rio', 'Pesca', 'Pisba', 'Puerto Boyaca', 
                          'Quipama', 'Ramiriqui', 'Raquira', 'Rondon', 'Saboya', 'Sachica', 'Samaca', 
                          'San Eduardo', 'San Jose de Pare', 'San Luis de Gaceno', 'San Mateo', 
                          'San Miguel de Sema', 'San Pablo de Borbur', 'Santana', 'Santa Maria', 
                          'Santa Rosa de Viterbo', 'Santa Sofia', 'Sativanorte', 'Sativasur', 
                          'Siachoque', 'Soata', 'Socota', 'Socha', 'Sogamoso', 'Somondoco', 'Sora', 
                          'Sotaquira', 'Soraca', 'Susacon', 'Sutamarchan', 'Sutatenza', 'Tasco', 
                          'Tenza', 'Tibana', 'Tibasosa', 'Tinjaca', 'Tipacoque', 'Toca', 'TogsI', 
                          'Topaga', 'Tota', 'Tunungua', 'Turmeque', 'Tuta', 'Tutaza', 'Umbita', 
                          'Ventaquemada', 'Viracacha', 'Zetaquira'
                        ],
                      'Caldas': [
                          'Manizales', 'Aguadas', 'Anserma', 'Aranzazu', 'Belalcazar', 'Chinchina', 
                          'Filadelfia', 'La Dorada', 'La Merced', 'Manzanares', 'Marmato', 
                          'Marquetalia', 'Marulanda', 'Neira', 'Norcasia', 'Pacora', 'Palestina', 
                          'Pensilvania', 'Riosucio', 'Risaralda', 'Salamina', 'Samana', 'San Jose', 
                          'Supia', 'Victoria', 'Villamaria', 'Viterbo'
                      ],
                      'Caquetá': [
                          'Florencia', 'Albania', 'Belen de los Andaquies', 'Cartagena del Chaira', 
                          'Curillo', 'El Doncello', 'El Paujil', 'La Montañita', 'Milan', 
                          'Morelia', 'Puerto Rico', 'San Jose del Fragua', 'San Vicente del Caguán', 
                          'Solano', 'Solita', 'Valparaiso'
                      ],
                      'Casanare': [
                          'Yopal', 'Aguazul', 'Chameza', 'Hato Corozal', 
                          'La Salina', 'Maní', 'Monterrey', 'Nunchía', 
                          'Orocue', 'Paz de Ariporo', 'Pore', 'Recetor', 
                          'Sabanalarga', 'Sacama', 'San Luis de Palenque', 
                          'Tamara', 'Tauramena', 'Trinidad', 'Villanueva'
                      ],    
                      'Cauca': [
                          'Popayán', 'Almaguer', 'Argelia', 'Balboa', 'Bolívar', 'Buenos Aires', 
                          'Cajibio', 'Caldono', 'Caloto', 'Corinto', 'El Tambo', 'Florencia', 
                          'Guachené', 'Guapi', 'Inza', 'Jambaló', 'La Sierra', 'La Vega', 'López', 
                          'Mercaderes', 'Miranda', 'Morales', 'Padilla', 'Páez', 'Patía', 
                          'Piamonte', 'Piendamo', 'Puerto Tejada', 'Puracé', 'Rosas', 'San Sebastián', 
                          'Santander de Quilichao', 'Santa Rosa', 'Silvia', 'Sotará', 'Suárez', 
                          'Sucre', 'Timbío', 'Timbiquí', 'Toribio', 'Totoro', 'Villa Rica'
                      ],
                      'Cesar': [
                          'Valledupar', 'Aguachica', 'Agustín Codazzi', 'Astrea', 'Becerril', 
                          'Bosconia', 'Chimichagua', 'Chiriguana', 'Curumaní', 'El Copey', 
                          'El Paso', 'Gamarra', 'Gonzalez', 'La Gloria', 'La Jagua de Ibirico', 
                          'Manaure', 'Pailitas', 'Pelaya', 'Pueblo Bello', 'Rio de Oro', 
                          'La Paz', 'San Alberto', 'San Diego', 'San Martin', 'Tamalameque'
                      ],
                      'Córdoba': [
                          'Montería', 'Ayapel', 'Buenavista', 'Canalete', 'Cereté', 'Chima', 
                          'Chinú', 'Ciénaga de Oro', 'Cotorra', 'La Apartada', 'Lorica', 
                          'Los Cordobas', 'Momil', 'Montelíbano', 'Moñitos', 'Planeta Rica', 
                          'Pueblo Nuevo', 'Puerto Escondido', 'Puerto Libertador', 'Purísima', 
                          'Sahagún', 'San Andrés Sotavento', 'San Antero', 'San Bernardo del Viento', 
                          'San Carlos', 'San Pelayo', 'Tierralta', 'Valencia'
                      ],
                      'Cundinamarca': [
                          'Agua de Dios', 'Alban', 'Anapoima', 'Anolaima', 'Arbeláez', 'Beltrán', 
                          'Bituima', 'Bojacá', 'Cabrera', 'Cachipay', 'Cajicá', 'Caparrapí', 
                          'Caqueza', 'Carmen de Carupa', 'Chaguani', 'Chía', 'Chipaque', 
                          'Choachí', 'Chocontá', 'Cogua', 'Cota', 'Cucunubá', 'El Colegio', 
                          'El Peñón', 'El Rosal', 'Facatativá', 'Fomeque', 'Fosca', 'Funza', 
                          'Fuquene', 'Fusagasugá', 'Gachala', 'Gachancipá', 'Gacheta', 'Gama', 
                          'Girardot', 'Granada', 'Guacheta', 'Guaduas', 'Guasca', 'Guataquí', 
                          'Guatavita', 'Guayabal de Siquima', 'Guayabetal', 'Gutiérrez', 
                          'Jerusalén', 'Junín', 'La Calera', 'La Mesa', 'La Palma', 'La Peña', 
                          'La Vega', 'Lenguazaque', 'Macheta', 'Madrid', 'Manta', 'Medina', 
                          'Mosquera', 'Nariño', 'Nemocón', 'Nilo', 'Nimaima', 'Nocaima', 
                          'Venecia', 'Pacho', 'Paime', 'Pandi', 'Paratebueno', 'Pasca', 
                          'Puerto Salgar', 'Puli', 'Quebradanegra', 'Quetame', 'Quipile', 
                          'Apulo', 'Ricaurte', 'San Antonio del Tequendama', 'San Bernardo', 
                          'San Cayetano', 'San Francisco', 'San Juan de Río Seco', 'Sasaima', 
                          'Sesquilé', 'Sibaté', 'Silvania', 'Simijaca', 'Soacha', 'Sopo', 
                          'Subachoque', 'Suesca', 'Supatá', 'Susa', 'Sutatausa', 'Tabio', 
                          'Tausa', 'Tena', 'Tenjo', 'Tibacuy', 'Tibirita', 'Tocaima', 
                          'Tocancipá', 'Topaipí', 'Ubala', 'Ubaque', 'Villa de San Diego de Ubaté', 
                          'Une', 'Utica', 'Vergara', 'Viani', 'Villagómez', 'Villapinzón', 
                          'Villeta', 'Viotá', 'Yacopí', 'Zipacón', 'Zipaquirá'
                      ],
                      'Chocó': [
                          'Quibdó', 'Acandí', 'Alto Baudó', 'Atrato', 'Bagadó', 'Bahía Solano', 
                          'Bajo Baudó', 'Bojayá', 'El Cantón del San Pablo', 'Carmen del Darién', 
                          'Certeguí', 'Condoto', 'El Carmen de Atrato', 'El Litoral del San Juan', 
                          'Istmina', 'Jurado', 'Lloró', 'Medio Atrato', 'Medio Baudó', 
                          'Medio San Juan', 'Novita', 'Nuquí', 'Río Iro', 'Río Quito', 
                          'Riosucio', 'San José del Palmar', 'Sipi', 'Tadó', 'Ungía', 
                          'Unión Panamericana'
                      ],
                      'Guainía': [
                          'Inírida', 'Barranco Minas', 'Mapiripana', 'San Felipe', 
                          'Puerto Colombia', 'La Guadalupe', 'Cacahual', 
                          'Pana Pana', 'Morichal'
                      ],
                      'Guaviare': [
                          'San José del Guaviare', 'Calamar', 'El Retorno', 
                          'Miraflores'
                      ],    
                      'Huila': [
                          'Neiva', 'Acevedo', 'Agrado', 'Aipe', 'Algeciras', 'Altamira', 
                          'Baraya', 'Campoalegre', 'Colombia', 'Elías', 'Garzón', 'Gigante', 
                          'Guadalupe', 'Hobo', 'Iquira', 'Isnos', 'La Argentina', 'La Plata', 
                          'Nataga', 'Oporapa', 'Paicol', 'Palermo', 'Palestina', 'Pital', 
                          'Pitalito', 'Rivera', 'Saladoblanco', 'San Agustín', 'Santa María', 
                          'Suaza', 'Tarqui', 'Tesalia', 'Tello', 'Teruel', 'Timaná', 
                          'Villavieja', 'Yaguará'
                      ],
                      'La Guajira': [
                          'Riohacha', 'Albania', 'Barrancas', 'Dibulla', 'Distracción', 
                          'El Molino', 'Fonseca', 'Hatonuevo', 'La Jagua del Pilar', 
                          'Maicao', 'Manaure', 'San Juan del Cesar', 'Uribia', 
                          'Urumita', 'Villanueva'
                      ],
                      'Magdalena': [
                          'Santa Marta', 'Algarrobo', 'Aracataca', 'Ariguani', 
                          'Cerro San Antonio', 'Chibolo', 'Ciénaga', 'Concordia', 
                          'El Banco', 'El Piñón', 'El Retén', 'Fundación', 
                          'Guamal', 'Nueva Granada', 'Pedraza', 'Pijiño del Carmen', 
                          'Pivijay', 'Plato', 'Pueblo Viejo', 'Remolino', 
                          'Sabanas de San Ángel', 'Salamina', 'San Sebastián de Buenavís', 
                          'San Zenón', 'Santa Ana', 'Santa Bárbara de Pinto', 
                          'Sitionuevo', 'Tenerife', 'Zapayán', 'Zona Bananera'
                      ],
                      'Meta': [
                          'Villavicencio', 'Acacías', 'Barranca de Upía', 'Cabuyaro', 
                          'Castilla la Nueva', 'Cubarral', 'Cumaral', 'El Calvario', 
                          'El Castillo', 'El Dorado', 'Fuente de Oro', 'Granada', 
                          'Guamal', 'Mapiripán', 'Mesetas', 'La Macarena', 
                          'Uribe', 'Lejanías', 'Puerto Concordia', 'Puerto Gaitán', 
                          'Puerto López', 'Puerto Lleras', 'Puerto Rico', 'Restrepo', 
                          'San Carlos de Guaroa', 'San Juan de Arama', 'San Juanito', 
                          'San Martín', 'Vistahermosa'
                      ],
                      'Nariño': [
                          'Pasto', 'Alban', 'Aldana', 'Ancuya', 'Arboleda', 
                          'Barbacoas', 'Belen', 'Buesaco', 'Colon', 'Consaca', 
                          'Contadero', 'Cordoba', 'Cuaspud', 'Cumbal', 'Cumbitara', 
                          'Chachagüí', 'El Charco', 'El Peñol', 'El Rosario', 
                          'El Tablón de Gómez', 'El Tambo', 'Funes', 'Guachucal', 
                          'Guitarrilla', 'Gualmatán', 'Iles', 'Imues', 'Ipiales', 
                          'La Cruz', 'La Florida', 'La Llanada', 'La Tola', 
                          'La Union', 'Leiva', 'Linares', 'Los Andes', 'Magüí', 
                          'Mallama', 'Mosquera', 'Nariño', 'Olaya Herrera', 
                          'Ospina', 'Francisco Pizarro', 'Policarpa', 'Potosí', 
                          'Providencia', 'Puerres', 'Pupiales', 'Ricaurte', 
                          'Roberto Payán', 'Samaniego', 'Sandona', 'San Bernardo', 
                          'San Lorenzo', 'San Pablo', 'San Pedro de Cartago', 
                          'Santa Barbara', 'Santacruz', 'Sapuyes', 'Taminango', 
                          'Tangua', 'San Andres de Tumaco', 'Tuquerres', 
                          'Yacuanquer'
                      ],
                      'Norte de Santander': [
                          'Cúcuta', 'Abrego', 'Arboledas', 'Bochalema', 
                          'Bucarasica', 'Cacota', 'Cachira', 'Chinacota', 
                          'Chitagá', 'Convención', 'Cucutilla', 'Durania', 
                          'El Carmen', 'El Tarra', 'El Zulia', 'Gramalote', 
                          'Hacarí', 'Herrán', 'Labateca', 'La Esperanza', 
                          'La Playa', 'Los Patios', 'Lourdes', 'Mutiscua', 
                          'Ocaña', 'Pamplona', 'Pamplonita', 'Puerto Santander', 
                          'Ragonvalia', 'Salazar', 'San Calixto', 'San Cayetano', 
                          'Santiago', 'Sardinata', 'Silos', 'Teorama', 'Tibú', 
                          'Toledo', 'Villa Caro', 'Villa del Rosario'
                      ],
                      'Quindío': [
                          'Armenia', 'Buenavista', 'Calarcá', 'Circasia', 
                          'Córdoba', 'Filandia', 'Génova', 'La Tebaida', 
                          'Montenegro', 'Pijao', 'Quimbaya', 'Salento'
                      ],
                      'Risaralda': [
                          'Pereira', 'Apía', 'Balboa', 'Belen de Umbria', 
                          'Dosquebradás', 'Guática', 'La Celia', 'La Virginia', 
                          'Marsella', 'Mistrató', 'Pueblo Rico', 'Quinchía', 
                          'Santa Rosa de Cabal', 'Santuario'
                      ],
                      'San Andrés, Providencia y Santa Catalina': [
                          'San Andrés', 'Providencia'
                      ],
                      'Santander': [
                          'Bucaramanga', 'Aguada', 'Albania', 'Aratoca', 
                          'Barbosa', 'Barichara', 'Barrancabermeja', 'Betulia', 
                          'Bolívar', 'Cabrera', 'California', 'Capitanejo', 
                          'Carcasi', 'Cepita', 'Cerrito', 'Charalá', 
                          'Charta', 'Chima', 'Chipatá', 'Cimitarrá', 
                          'Concepción', 'Confines', 'Contratación', 'Coromoro', 
                          'Curití', 'El Carmen de Chucurí', 'El Guacamayo', 
                          'El Peñón', 'El Playón', 'Encino', 'Enciso', 
                          'Florian', 'Floridablanca', 'Galán', 'Gambita', 
                          'Girón', 'Guaca', 'Guadalupe', 'Guapota', 
                          'Guavata', 'G$EPSA', 'Hato', 'Jesús María', 
                          'Jordán', 'La Belleza', 'Landazuri', 'La Paz', 
                          'Lebrija', 'Los Santos', 'Macaravita', 'Málaga', 
                          'Matanza', 'Mogotes', 'Molagavita', 'Ocamonte', 
                          'Oiba', 'Onzaga', 'Palmar', 'Palmas del Socorro', 
                          'Páramo', 'Piedecuesta', 'Pinchote', 'Puente Nacional', 
                          'Puerto Parra', 'Puerto Wilches', 'Rionegro', 
                          'Sabana de Torres', 'San Andrés', 'San Benito', 
                          'San Gil', 'San Joaquín', 'San José de Miranda', 
                          'San Miguel', 'San Vicente de Chucurí', 'Santa Bárbara', 
                          'Santa Helena del Opon', 'Simacota', 'Socorro', 
                          'Suita', 'Sucre', 'Surata', 'Tona', 
                          'Valle de San José', 'Vélez', 'Vetas', 
                          'Villanueva', 'Zapatoca'
                      ],
                      'Sucre': [
                          'Sincelejo', 'Buenavista', 'Caimito', 'Coloso', 
                          'Corozal', 'Coveñas', 'Chalán', 'El Roble', 
                          'Galeras', 'Guaranda', 'La Unión', 'Los Palmitos', 
                          'Majagual', 'Morroa', 'Ovejas', 'Palmito', 
                          'Sampués', 'San Benito Abad', 'San Juan de Betulia', 
                          'San Marcos', 'San Onofre', 'San Pedro', 'San Luis de Since', 
                          'Sucre', 'Santiago de Tolu', 'Tolu Viejo'
                      ],
                      'Tolima': [
                          'Ibagué', 'Alpujarra', 'Alvarado', 'Ambalema', 
                          'Anzoátegui', 'Armero', 'Ataco', 'Cajamarca', 
                          'Carmen de Apicalá', 'Casabianca', 'Chaparral', 
                          'Coello', 'Coyaima', 'Cunday', 'Dolores', 
                          'Espinal', 'Falan', 'Flandes', 'Fresno', 
                          'Guamo', 'Herveo', 'Honda', 'Icononzo', 
                          'Lérida', 'Líbano', 'Mariquita', 'Melgar', 
                          'Murillo', 'Natagaima', 'Ortega', 'Palocabildo', 
                          'Piedras', 'Planadas', 'Prado', 'Purificación', 
                          'Rioblanco', 'Roncesvalles', 'Rovira', 'Saldaña', 
                          'San Antonio', 'San Luis', 'Santa Isabel', 
                          'Suárez', 'Valle de San Juan', 'Venadillo', 
                          'Villahermosa', 'Villarrica'
                      ],
                      'Valle del Cauca': [
                          'Cali', 'Alcalá', 'Andalucía', 'Ansermanuevo', 
                          'Argelia', 'Bolívar', 'Buenaventura', 'Guadalajara de Buga', 
                          'Bugalagrande', 'Caicedonia', 'Calima', 'Candelaria', 
                          'Cartago', 'Dagua', 'El Águila', 'El Cairo', 
                          'El Cerrito', 'El Dovio', 'Florida', 'Ginebra', 
                          'Guacarí', 'Jamundí', 'La Cumbre', 'La Unión', 
                          'La Victoria', 'Obando', 'Palmira', 'Pradera', 
                          'Restrepo', 'Riofrío', 'Roldanillo', 'San Pedro', 
                          'Sevilla', 'Toro', 'Trujillo', 'Tuluá', 
                          'Ulloa', 'Versalles', 'Vijes', 'Yotoco', 
                          'Yumbo', 'Zarzal'
                      ],
                      'Putumayo': [
                          'Mocoa', 'Colón', 'Orito', 'Puerto Asís', 
                          'Puerto Caicedo', 'Puerto Guzmán', 'Leguízamo', 
                          'Sibundoy', 'San Francisco', 'San Miguel', 
                          'Santiago', 'Valle del Guamuez', 'Villagarzón'
                      ],
                      'Vaupés': [
                          'Mitú', 'Carurú', 'Pacoa', 'Taraíra', 
                          'Papunaúa', 'Yavarate'
                      ],
                      'Vichada': [
                          'Puerto Carreño', 'La Primavera', 'Santa Rosalía', 
                          'Cumaribo'
                      ]     
            };
    
            const input = document.getElementById('departamento');
            const ciudadSelect = document.getElementById('ciudad');
    
            input.addEventListener('input', function () {
                const searchTerm = input.value.toLowerCase();
                const filteredDepartamentos = Object.keys(departamentos).filter(dpto =>
                    dpto.toLowerCase().includes(searchTerm)
                );
                mostrarSugerencias(filteredDepartamentos);
            });
    
            function mostrarSugerencias(departamentosFiltrados) {
                const listaSugerencias = document.createElement('ul');
                listaSugerencias.classList.add('list-group');
                
                departamentosFiltrados.forEach(departamento => {
                    const item = document.createElement('li');
                    item.textContent = departamento;
                    item.classList.add('list-group-item');
                    item.addEventListener('click', function () {
                        input.value = departamento;
                        limpiarSugerencias();
                        activarCiudadSelect(departamento);
                    });
                    listaSugerencias.appendChild(item);
                });
    
                limpiarSugerencias();
                input.parentNode.appendChild(listaSugerencias);
            }
    
            function limpiarSugerencias() {
                const listaAnterior = document.querySelector('.list-group');
                if (listaAnterior) {
                    listaAnterior.remove();
                }
            }
    
            function activarCiudadSelect(departamento) {
                // Limpia las opciones anteriores
                ciudadSelect.innerHTML = '<option value="">Selecciona una ciudad...</option>';
                ciudadSelect.disabled = false; // Activa el campo
    
                // Rellena las opciones según el departamento seleccionado
                departamentos[departamento].forEach(ciudad => {
                    const option = document.createElement('option');
                    option.value = ciudad;
                    option.textContent = ciudad;
                    ciudadSelect.appendChild(option);
                });
            }
        });
    </script>

    <script>
      function toggleFields() {
          const personaNatural = document.getElementById("personaNatural").checked;
          const personaJuridica = document.getElementById("personaJuridica").checked;
          const tipoTercero = document.querySelector('input[name="tipoTercero"]:checked');

          // Habilitar campos
          document.getElementById("nombres").disabled = !personaNatural;
          document.getElementById("apellidos").disabled = !personaNatural;
          document.getElementById("razonSocial").disabled = !personaJuridica;
          

          // Limpiar campos no habilitados
          if (personaNatural) {
              document.getElementById("nit").value = "";
          } else if (personaJuridica) {
              document.getElementById("cedula").value = "";
          }

          // Habilitar campo de actividad económica si es Cliente o Proveedor
          if (tipoTercero) {
              const isCliente = tipoTercero.value === "cliente";
              const isProveedor = tipoTercero.value === "proveedor";

              document.getElementById("actividadEconomica").disabled = !(isCliente || isProveedor);
          }
      }

      function toggleTipoTercero() {
          const tipoTercero = document.querySelector('input[name="tipoTercero"]:checked');
          document.getElementById("actividadEconomica").disabled = !(tipoTercero && (tipoTercero.value === "cliente" || tipoTercero.value === "proveedor"));
      }
    </script>


    </section><!-- End Services Section -->

  <!-- ======= Footer ======= -->
  <footer id="footer">
    <div class="footer-top">
      <div class="container">
        <div class="row">

          <div class="col-lg-3 col-md-6 footer-links">
            <h4>Useful Links</h4>
            <ul>
              <li><i class="bx bx-chevron-right"></i> <a target="_blank" href="https://udes.edu.co">UDES</a></li>
              <li><i class="bx bx-chevron-right"></i> <a target="_blank" href="https://bucaramanga.udes.edu.co/estudia/pregrados/contaduria-publica">CONTADURIA PUBLICA</a></li>
            </ul>
          </div>

          <div class="col-lg-3 col-md-6 footer-links">
            <h4>Ubicación</h4>
            <p>
              Calle 70 N° 55-210, <br>
              Bucaramanga, <br>
              Santander <br><br>
            </p>
          </div>

          <div class="col-lg-3 col-md-6 footer-contact">
            <h4>Contactenos</h4>
            <p>
              <strong>Teléfono:</strong> (607) 6516500 <br>
              <strong>Email:</strong> notificacionesudes@udes.edu.co <br>
            </p>
          </div>

          <div class="col-lg-3 col-md-6 footer-info">
            <h3>Redes Sociales</h3>
            <p>A través de los siguientes link´s puedes seguirnos.</p>
            <div class="social-links mt-3">
              <a href="#" class="twitter"><i class="bx bxl-twitter"></i></a>
              <a href="#" class="facebook"><i class="bx bxl-facebook"></i></a>
              <a href="#" class="instagram"><i class="bx bxl-instagram"></i></a>
              <a href="#" class="google-plus"><i class="bx bxl-skype"></i></a>
              <a href="#" class="linkedin"><i class="bx bxl-linkedin"></i></a>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="container">
      <div class="copyright">
        &copy; Copyright 2023 <strong><span> UNIVERSIDAD DE SANTANDER </span></strong>. All Rights Reserved
      </div>
      <div class="credits">
        Creado por iniciativa del programa de <a href="https://bucaramanga.udes.edu.co/estudia/pregrados/contaduria-publica">Contaduría Pública</a>
      </div>
    </div>
  </footer><!-- End Footer -->


  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>