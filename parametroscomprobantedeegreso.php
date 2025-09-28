<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$codigoDocumento=(isset($_POST['codigoDocumento']))?$_POST['codigoDocumento']:"";
$descripcionDocumento=(isset($_POST['descripcionDocumento']))?$_POST['descripcionDocumento']:"";
$consecutivo=(isset($_POST['consecutivo']))?$_POST['consecutivo']:"";
$activo=(isset($_POST['activo']))?$_POST['activo']:"";

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

switch($accion){
  case "btnAgregar":

      $sentencia=$pdo->prepare("INSERT INTO comprobanteegreso(codigoDocumento,descripcionDocumento,consecutivo,activo) 
      VALUES (:codigoDocumento,:descripcionDocumento,:consecutivo,:activo)");

      $sentencia->bindParam(':codigoDocumento',$codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento',$descripcionDocumento);
      $sentencia->bindParam(':consecutivo',$consecutivo);
      $sentencia->bindParam(':activo',$activo);

      $sentencia->execute();

  echo "Presionaste"; 
  break;
  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE comprobanteegreso 
                                  SET codigoDocumento = :codigoDocumento,
                                      descripcionDocumento = :descripcionDocumento,
                                      consecutivo = :consecutivo,
                                      activo = :activo
                                  WHERE id = :id");

      // Enlazamos los parámetros 

      $sentencia->bindParam(':codigoDocumento', $codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento', $descripcionDocumento);
      $sentencia->bindParam(':consecutivo', $consecutivo);
      $sentencia->bindParam(':activo', $activo);
      $sentencia->bindParam(':id', $txtId);

      // Ejecutamos la sentencia
      $sentencia->execute();

      // Opcional: Redirigir o mostrar mensaje de éxito
      echo "<script>alert('Datos actualizados correctamente');</script>";

  break;

  case "btnEliminar":

    $sentencia = $pdo->prepare("DELETE FROM comprobanteegreso WHERE id = :id");
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();


  break;
}

  $sentencia= $pdo->prepare("SELECT * FROM `comprobanteegreso` WHERE 1");
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
        <h2>COMPROBANTE DE EGRESO</h2>
      </div>

      <form action="" method="post">
        <div>
          <label for="id" class="form-label">ID:</label>
          <input type="text" class="form-control" value="<?php echo $txtId;?>" id="txtId" name="txtId" readonly>
        </div>
        <div class="mb-3">
          <label for="codigoDocumento" class="form-label">Codigo de documento*</label>
          <input type="number" class="form-control" value="<?php echo $codigoDocumento;?>" id="codigoDocumento" name="codigoDocumento" placeholder="" required>
        </div>
        <div class="mb-3">
          <label for="descripcionDocumento" class="form-label">Descripción documento*</label>
          <input type="text" class="form-control" value="<?php echo $descripcionDocumento;?>" id="descripcionDocumento" name="descripcionDocumento" placeholder="" required>
        </div>
        <div class="mb-3">
          <label for="consecutivo" class="form-label">Consecutivo</label>
          <input type="text" class="form-control" value="<?php echo $consecutivo;?>" id="consecutivo" name="consecutivo" placeholder="" readonly>
        </div>
        <div class="mb-3">
          <label for="activo" class="form-label">Activo*</label>
          <input type="checkbox" class="" value="<?php echo $activo;?>" id="activo" name="activo" placeholder="" required>
        </div>
        <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Guardar</button>
        <button value="btnModificar" type="submit" class="btn btn-primary"  name="accion" >Modificar</button>
        <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
      </form><br>

      <div class="row">
          <div class="table-container">

            <table>
              <thead>
                <tr>
                  <th>Codigo Documento</th>
                  <th>Descripción Documento</th>
                  <th>Consecutivo</th>
                  <th>Activo</th>
                  <th>Acción</th>
                </tr>
              </thead>
            </table>

            <?php foreach($lista as $usuario){ ?>
              <tr>
                <td><?php echo $usuario['codigoDocumento']; ?></td>
                <td><?php echo $usuario['descripcionDocumento']; ?></td>
                <td><?php echo $usuario['consecutivo']; ?></td>
                <td><?php echo $usuario['activo']; ?></td>
                <td>

                <form action="" method="post">

                <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>"  >
                <input type="hidden" name="codigoDocumento" value="<?php echo $usuario['codigoDocumento']; ?>" >
                <input type="hidden" name="descripcionDocumento" value="<?php echo $usuario['descripcionDocumento']; ?>" >
                <input type="hidden" name="consecutivo" value="<?php echo $usuario['consecutivo']; ?>" >
                <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>" >
                <input type="submit" value="Editar" name="accion">
                <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
                </form>

                </td>

              </tr>
            <?php } ?>
          </div>  
      </div>
 
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