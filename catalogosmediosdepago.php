<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$metodoPago=(isset($_POST['metodoPago']))?$_POST['metodoPago']:"";
$cuentaContable=(isset($_POST['cuentaContable']))?$_POST['cuentaContable']:"";

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

switch($accion){
  case "btnAgregar":

      $sentencia=$pdo->prepare("INSERT INTO mediosdepago(metodoPago,cuentaContable) 
      VALUES (:metodoPago,:cuentaContable)");

      $sentencia->bindParam(':metodoPago',$metodoPago);
      $sentencia->bindParam(':cuentaContable',$cuentaContable);

      $sentencia->execute();

  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE mediosdepago 
                                  SET metodoPago = :metodoPago,
                                      cuentaContable = :cuentaContable,
                                  WHERE id = :id");

      // Enlazamos los parámetros 

      $sentencia->bindParam(':metodoPago', $metodoPago);
      $sentencia->bindParam(':cuentaContable', $cuentaContable);
      $sentencia->bindParam(':id', $txtId);

      // Ejecutamos la sentencia
      $sentencia->execute();

      // Opcional: Redirigir o mostrar mensaje de éxito
      echo "<script>alert('Datos actualizados correctamente');</script>";

  break;

  case "btnEliminar":

    $sentencia = $pdo->prepare("DELETE FROM mediosdepago WHERE id = :id");
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();


  break;

}

  $sentencia= $pdo->prepare("SELECT * FROM `mediosdepago` WHERE 1");
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
          <h2>CATALOGO MEDIOS DE PAGO</h2>
          <p>Para crear nueva forma de pago diligencie los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>

        <form action="" method="post">
          <div>
            <label for="id" class="form-label">ID:</label>
            <input type="text" class="form-control" value="<?php echo $txtId;?>" id="txtId" name="txtId">
          </div>
          <div class="container">
            <label for="metodoPago" class="form-label">Método de Pago:</label>
            <select id="metodoPago" value="<?php echo $metodoPago;?>" name="metodoPago" onchange="mostrarCuentaContable()" class="form-control">
                <option value="">Selecciona un método de pago</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="credito">Crédito</option>
            </select>
            <br>  
            <label for="cuentaContable" class="form-label">Cuenta Contable:</label>
            <input type="text" value="<?php echo $cuentaContable;?>" id="cuentaContable" name="cuentaContable" class="form-control">
          </div>
          <br>
          <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Guardar</button>
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
                  <th>Acción</th>
                </tr>
              </thead>
              <?php foreach($lista as $usuario){ ?>
                <tr>
                  <td><?php echo $usuario['metodoPago']; ?></td>
                  <td><?php echo $usuario['cuentaContable']; ?></td>
                  <td>

                  <form action="" method="post">

                  <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
                  <input type="hidden" name="metodoPago" value="<?php echo $usuario['metodoPago']; ?>" >
                  <input type="hidden" name="cuentaContable" value="<?php echo $usuario['cuentaContable']; ?>" >
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
            function mostrarCuentaContable() {
                // Obtener el valor seleccionado en el select de método de pago
                var metodoPago = document.getElementById("metodoPago").value;
    
                // Referencia al campo de cuenta contable
                var cuentaContable = document.getElementById("cuentaContable");
    
                // Determinar la cuenta contable según el método de pago seleccionado
                if (metodoPago === "efectivo") {
                    cuentaContable.value = "Caja";
                } else if (metodoPago === "transferencia") {
                    cuentaContable.value = "Cuenta de Ahorros";
                } else if (metodoPago === "credito") {
                    cuentaContable.value = "Otros";
                } else {
                    cuentaContable.value = ""; // Limpiar si no se selecciona nada
                }
            }
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