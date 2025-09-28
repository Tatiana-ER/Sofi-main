<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$codigoDocumento=(isset($_POST['codigoDocumento']))?$_POST['codigoDocumento']:"";
$descripcionDocumento=(isset($_POST['descripcionDocumento']))?$_POST['descripcionDocumento']:"";
$resolucionDian=(isset($_POST['resolucionDian']))?$_POST['resolucionDian']:"";
$numeroResolucion=(isset($_POST['numeroResolucion']))?$_POST['numeroResolucion']:"";
$fechaInicio=(isset($_POST['fechaInicio']))?$_POST['fechaInicio']:"";
$vigencia=(isset($_POST['vigencia']))?$_POST['vigencia']:"";
$fechaFinalizacion=(isset($_POST['fechaFinalizacion']))?$_POST['fechaFinalizacion']:"";
$prefijo=(isset($_POST['prefijo']))?$_POST['prefijo']:"";
$consecutivoInicial=(isset($_POST['consecutivoInicial']))?$_POST['consecutivoInicial']:"";
$consecutivoFinal=(isset($_POST['consecutivoFinal']))?$_POST['consecutivoFinal']:"";
$retenciones=(isset($_POST['retenciones']))?$_POST['retenciones']:"";
$tipoRetencion=(isset($_POST['tipoRetencion']))?$_POST['tipoRetencion']:"";
$autoRetenciones=(isset($_POST['autoRetenciones']))?$_POST['autoRetenciones']:"";
$tipoAutoretencion=(isset($_POST['tipoAutoretencion']))?$_POST['tipoAutoretencion']:"";
$activo=(isset($_POST['activo']))?$_POST['activo']:"";

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

switch($accion){
  case "btnAgregar":

      $sentencia=$pdo->prepare("INSERT INTO facturadeventa(codigoDocumento,descripcionDocumento,resolucionDian,numeroResolucion,fechaInicio,vigencia,fechaFinalizacion,prefijo,consecutivoInicial,consecutivoFinal,retenciones,tipoRetencion,autoRetenciones,tipoAutoretencion,activo) 
      VALUES (:codigoDocumento,:descripcionDocumento,:resolucionDian,:numeroResolucion,:fechaInicio,:vigencia,:fechaFinalizacion,:prefijo,:consecutivoInicial,:consecutivoFinal,:retenciones,:tipoRetencion,:autoRetenciones,:tipoAutoretencion,:activo)");
      

      $sentencia->bindParam(':codigoDocumento',$codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento',$descripcionDocumento);
      $sentencia->bindParam(':resolucionDian',$resolucionDian);
      $sentencia->bindParam(':numeroResolucion',$numeroResolucion);
      $sentencia->bindParam(':fechaInicio',$fechaInicio);
      $sentencia->bindParam(':vigencia',$vigencia);
      $sentencia->bindParam(':fechaFinalizacion',$fechaFinalizacion);
      $sentencia->bindParam(':prefijo',$prefijo);
      $sentencia->bindParam(':consecutivoInicial',$consecutivoInicial);
      $sentencia->bindParam(':consecutivoFinal',$consecutivoFinal);
      $sentencia->bindParam(':retenciones',$retenciones);
      $sentencia->bindParam(':tipoRetencion',$tipoRetencion);
      $sentencia->bindParam(':autoRetenciones',$autoRetenciones);
      $sentencia->bindParam(':tipoAutoretencion',$tipoAutoretencion);
      $sentencia->bindParam(':activo',$activo);
      $sentencia->execute();

  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE facturadeventa 
                                  SET codigoDocumento = :codigoDocumento,
                                      descripcionDocumento = :descripcionDocumento,
                                      resolucionDian = :resolucionDian,
                                      numeroResolucion = :numeroResolucion,
                                      fechaInicio = :fechaInicio,
                                      vigencia = :vigencia,
                                      fechaFinalizacion = :fechaFinalizacion,
                                      prefijo = :prefijo,
                                      consecutivoInicial = :consecutivoInicial,
                                      consecutivoFinal = :consecutivoFinal,
                                      retenciones = :retenciones,
                                      tipoRetencion = :tipoRetencion,
                                      autoRetenciones = :autoRetenciones,
                                      tipoAutoretencion = :tipoAutoretencion,
                                      activo = :activo
                                  WHERE id = :id");

      // Enlazamos los parámetros 

      $sentencia->bindParam(':codigoDocumento', $codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento', $descripcionDocumento);
      $sentencia->bindParam(':resolucionDian', $resolucionDian);
      $sentencia->bindParam(':numeroResolucion', $numeroResolucion);
      $sentencia->bindParam(':fechaInicio', $fechaInicio);
      $sentencia->bindParam(':vigencia', $vigencia);
      $sentencia->bindParam(':fechaFinalizacion', $fechaFinalizacion);
      $sentencia->bindParam(':prefijo', $prefijo);
      $sentencia->bindParam(':consecutivoInicial', $consecutivoInicial);
      $sentencia->bindParam(':consecutivoFinal', $consecutivoFinal);
      $sentencia->bindParam(':retenciones', $retenciones);
      $sentencia->bindParam(':tipoRetencion', $tipoRetencion);
      $sentencia->bindParam(':autoRetenciones', $autoRetenciones);
      $sentencia->bindParam(':tipoAutoretencion', $tipoAutoretencion);
      $sentencia->bindParam(':activo', $activo);
      $sentencia->bindParam(':id', $txtId);

      // Ejecutamos la sentencia
      $sentencia->execute();

      // Opcional: Redirigir o mostrar mensaje de éxito
      echo "<script>alert('Datos actualizados correctamente');</script>";

  break;

  case "btnEliminar":

    $sentencia = $pdo->prepare("DELETE FROM facturadeventa WHERE id = :id");
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();


  break;
}

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
      margin: 0 auto; /* Centra horizontalmente */
      padding: 20px; /* Espacio alrededor de la tabla */
      max-width: 95%; /* Ancho máximo del contenedor */
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Opcional: sombra para estética */
      background-color: #ffffff; /* Fondo blanco opcional */
      border-radius: 5px; /* Bordes redondeados opcionales */
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid black;
        padding: 10px;
        text-align: center;
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
        <h2>FACTURA DE VENTA</h2>
      </div>

      <form action="" method="post">
        <div>
          <label for="id" class="form-label">ID:</label>
          <input type="text" class="form-control" value="<?php echo $txtId;?>" id="txtId" name="txtId" readonly> 
        </div>
        <div class="mb-3">
          <label for="codigoDocumento" class="form-label">Codigo de documento*</label>
          <input type="number" class="form-control" value="<?php echo $codigoDocumento;?>" id="codigoDocumento" name="codigoDocumento" placeholder="">
        </div>
        <div class="mb-3">
          <label for="descripcionDocumento" class="form-label">Descripción documento*</label>
          <input type="text" class="form-control" value="<?php echo $descripcionDocumento;?>" id="descripcionDocumento" name="descripcionDocumento" placeholder="" required>
        </div>
        <div class="mb-3">
          <label for="resolucionDian" class="form-label">Resolución DIAN?*</label>
          <input type="checkbox" class="" value="<?php echo $resolucionDian;?>" id="resolucionDian" name="resolucionDian" placeholder="" required>
        </div>
        <div class="mb-3">
            <label for="numeroResolucion" class="form-label">Numero de resolución</label>
          <input type="text" class="form-control" value="<?php echo $numeroResolucion;?>" id="numeroResolucion" name="numeroResolucion" placeholder="">
        </div>
        <div class="mb-3">
          <label for="fechaInicio" class="form-label">Fecha inicio</label>
          <input type="date" class="form-control" value="<?php echo $fechaInicio;?>" id="fechaInicio" name="fechaInicio" placeholder="">
        </div>
        <div class="mb-3">
          <label for="vigencia" class="form-label">Vigencia(meses)</label>
          <input type="number" class="form-control" value="<?php echo $vigencia;?>" id="vigencia" name="vigencia" placeholder="">
        </div>
        <div class="mb-3">
          <label for="fechaFinalizacion" class="form-label">Fecha finalización</label>
          <input type="date" class="form-control" value="<?php echo $fechaFinalizacion;?>" id="fechaFinalizacion" name="fechaFinalizacion" placeholder="" readonly>
        </div>

        <div class="mb-3">
          <label for="prefijo" class="form-label">Prefijo</label>
          <input type="text" class="form-control" value="<?php echo $prefijo;?>" id="prefijo" name="prefijo" placeholder="">
        </div>

        <div class="mb-3">
          <label for="consecutivoInicial" class="form-label">Consecutivo inicial</label>
          <input type="text" class="form-control" value="<?php echo $consecutivoInicial;?>" id="consecutivoInicial" placeholder="">
        </div>

        <div class="mb-3">
          <label for="consecutivoFinal" class="form-label">Consecutivo final</label>
          <input type="text" class="form-control" value="<?php echo $consecutivoFinal;?>" id="consecutivoFinal" name="consecutivoFinal" placeholder="">
        </div>

        <div class="mb-3">
          <label for="retenciones" class="form-label">Retenciones</label>
          <input type="checkbox" class="" value="<?php echo $retenciones;?>" id="retenciones" name="retenciones" placeholder="">
          <select class="form-select" value="<?php echo $tipoRetencion;?>" id="tipoRetencion" name="tipoRetencion" aria-label="Default select example">
            <option selected>Seleccione el tipo de retención</option>
            <option value="1">Retención a la Renta</option>
            <option value="2">Retención de IVA</option>
            <option value="3">Retención de ICA</option>
          </select>
        </div>

        <div class="mb-3">
            <label for="autoRetenciones" class="form-label">Autoretenciones</label>
            <input type="checkbox" class="" value="<?php echo $autoRetenciones;?>" id="autoRetenciones" name="autoRetenciones" placeholder="">
            <select class="form-select" value="<?php echo $tipoAutoretencion;?>" id="tipoAutoretencion" name="tipoAutoretencion" aria-label="Default select example">
              <option selected>Seleccione el tipo de autoretención</option>
              <option value="1">Autorretención a la Renta</option>
              <option value="2">Autorretención de IVA</option>
              <option value="3">Autorretención de ICA</option>
            </select>
        </div>

        <!--<div class="mb-3">
          <label for="exampleFormControlInput1" class="form-label">Tipo de impresión</label>
          <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="">
        </div> -->

        <div class="mb-3">
          <label for="activo" class="form-label">Activo*</label>
          <input type="checkbox" class="" value="<?php echo $activo;?>" id="activo" name="activo" placeholder="" required>
        </div>
        <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Guardar</button>
        <button value="btnModificar" type="submit" class="btn btn-primary"  name="accion" >Modificar</button>
        <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
      </form>

      <div class="row">
          <div class="table-container">

            <table>
              <thead>
                <tr>
                  <th>Codigo Documento</th>
                  <th>Descripción Documento</th>
                  <th>Resolucion Dian?</th>
                  <th>Numero de Resolucion</th>
                  <th>Fecha Inicio</th>
                  <th>Vigencia</th>
                  <th>Fecha Finalizacion</th>
                  <th>Prefijo</th>
                  <th>Consecutivo Inicial</th>
                  <th>Consecutivo Final</th>
                  <th>Retenciones</th>
                  <th>Tipo Retencion</th>
                  <th>Autoretenciones</th>
                  <th>Tipo de regimen</th>
                  <th>Activo</th>
                  <th>Acción</th>
                </tr>
              </thead>
            </table>

            <?php foreach($lista as $usuario){ ?>
              <tr>
                <td><?php echo $usuario['codigoDocumento']; ?></td>
                <td><?php echo $usuario['descripcionDocumento']; ?></td>
                <td><?php echo $usuario['resolucionDian']; ?></td>
                <td><?php echo $usuario['numeroResolucion']; ?></td>
                <td><?php echo $usuario['fechaInicio']; ?></td>
                <td><?php echo $usuario['vigencia']; ?></td>
                <td><?php echo $usuario['fechaFinalizacion']; ?></td>
                <td><?php echo $usuario['prefijo']; ?></td>
                <td><?php echo $usuario['consecutivoInicial']; ?></td>
                <td><?php echo $usuario['consecutivoFinal']; ?></td>
                <td><?php echo $usuario['retenciones']; ?></td>
                <td><?php echo $usuario['tipoRetencion']; ?></td>
                <td><?php echo $usuario['autoRetenciones']; ?></td>
                <td><?php echo $usuario['correo']; ?></td>
                <td><?php echo $usuario['activo']; ?></td>
                <td>

                <form action="" method="post">

                <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
                <input type="hidden" name="codigoDocumento" value="<?php echo $usuario['codigoDocumento']; ?>" >
                <input type="hidden" name="descripcionDocumento" value="<?php echo $usuario['descripcionDocumento']; ?>" >
                <input type="hidden" name="resolucionDian" value="<?php echo $usuario['resolucionDian']; ?>" >
                <input type="hidden" name="numeroResolucion" value="<?php echo $usuario['numeroResolucion']; ?>" >
                <input type="hidden" name="fechaInicio" value="<?php echo $usuario['fechaInicio']; ?>" >
                <input type="hidden" name="vigencia" value="<?php echo $usuario['vigencia']; ?>" >
                <input type="hidden" name="fechaFinalizacion" value="<?php echo $usuario['fechaFinalizacion']; ?>" >
                <input type="hidden" name="prefijo" value="<?php echo $usuario['prefijo']; ?>" >
                <input type="hidden" name="consecutivoInicial" value="<?php echo $usuario['consecutivoInicial']; ?>" >
                <input type="hidden" name="consecutivoFinal" value="<?php echo $usuario['consecutivoFinal']; ?>" >
                <input type="hidden" name="retenciones" value="<?php echo $usuario['retenciones']; ?>" >
                <input type="hidden" name="tipoRetencion" value="<?php echo $usuario['tipoRetencion']; ?>" >
                <input type="hidden" name="autoRetenciones" value="<?php echo $usuario['autoRetenciones']; ?>" >
                <input type="hidden" name="autoRetenciones" value="<?php echo $usuario['autoRetenciones']; ?>" >
                <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>" >
                <input type="submit" value="Editar" name="accion">
                <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
                </form>

                </td>

              </tr>
            <?php } ?>
          </div>

      <script>
        /* El campo fechaFinalizacion se calcula automáticamente a partir de fechaInicio y vigencia */
        document.addEventListener('DOMContentLoaded', function () {
          const fechaInicioInput = document.getElementById('fechaInicio');
          const vigenciaInput = document.getElementById('vigencia');
          const fechaFinalInput = document.getElementById('fechaFinalizacion');

          function calcularFechaFinalizacion() {
            const fechaInicio = new Date(fechaInicioInput.value);
            const vigencia = parseInt(vigenciaInput.value);

            if (!isNaN(fechaInicio.getTime()) && !isNaN(vigencia)) {
              const nuevaFecha = new Date(fechaInicio);
              nuevaFecha.setMonth(nuevaFecha.getMonth() + vigencia);
              const yyyy = nuevaFecha.getFullYear();
              const mm = String(nuevaFecha.getMonth() + 1).padStart(2, '0');
              const dd = String(nuevaFecha.getDate()).padStart(2, '0');
              fechaFinalInput.value = `${yyyy}-${mm}-${dd}`;
            } else {
              fechaFinalInput.value = '';
            }
          }

          fechaInicioInput.addEventListener('change', calcularFechaFinalizacion);
          vigenciaInput.addEventListener('input', calcularFechaFinalizacion);
        });
      </script>    
    </div>
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