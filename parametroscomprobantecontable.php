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

      $sentencia=$pdo->prepare("INSERT INTO comprobantecontable(codigoDocumento,descripcionDocumento,consecutivo,activo) 
      VALUES (:codigoDocumento,:descripcionDocumento,:consecutivo,:activo)");

      $sentencia->bindParam(':codigoDocumento',$codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento',$descripcionDocumento);
      $sentencia->bindParam(':consecutivo',$consecutivo);
      $sentencia->bindParam(':activo',$activo);

      $sentencia->execute();

  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE comprobantecontable 
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

    $sentencia = $pdo->prepare("DELETE FROM comprobantecontable WHERE id = :id");
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();


  break;

}

  $sentencia= $pdo->prepare("SELECT * FROM `comprobantecontable` WHERE 1");
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style> 
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
     <h1 class="logo">
        <a href="dashboard.php">
          <img src="./Img/sofilogo5pequeño.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
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
      <button class="btn-ir" onclick="window.location.href='catalogosparametrosdedocumentos.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
    <div class="container" data-aos="fade-up">

      <div class="section-title">
        <h2>COMPROBANTE CONTABLE</h2>
        <p>Para crear un nuevo tipo de documento diligencie los campos a continuación:</p>
        <p>(Los campos marcados con * son obligatorios)</p>
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
          <input type="text" class="form-control" value="<?php echo $consecutivo;?>" id="consecutivo" name="consecutivo" placeholder="">
        </div>
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

                <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
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
    <footer id="footer" class="footer-minimalista">
      <p>Universidad de Santander - Ingeniería de Software</p>
      <p>Todos los derechos reservados © 2025</p>
      <p>Creado por iniciativa del programa de Contaduría Pública</p>
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