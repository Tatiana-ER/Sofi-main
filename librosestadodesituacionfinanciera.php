<?php
// ================== CONEXIÓN ==================
include("connection_demo.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTRO DE FECHAS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');

// ================== CONSULTA ==================
$sql = "SELECT 
            c.tipo,
            c.codigo,
            c.nombre AS cuenta,
            SUM(m.debe - m.haber) AS saldo
        FROM movimientos_contables m
        INNER JOIN cuentas_contables c ON m.cuenta_id = c.id
        WHERE m.fecha BETWEEN :desde AND :hasta
        AND c.tipo IN ('ACTIVO','PASIVO','PATRIMONIO')
        GROUP BY c.tipo, c.codigo, c.nombre
        ORDER BY FIELD(c.tipo,'ACTIVO','PASIVO','PATRIMONIO'), c.codigo";

$stmt = $pdo->prepare($sql);
$stmt->execute([':desde'=>$fecha_desde,':hasta'=>$fecha_hasta]);
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== SEPARAR POR TIPO ==================
$activos = [];
$pasivos = [];
$patrimonios = [];
$totalActivos = 0;
$totalPasivos = 0;
$totalPatrimonios = 0;

foreach ($datos as $fila) {
    if ($fila['tipo'] == 'ACTIVO') {
        $activos[] = $fila;
        $totalActivos += $fila['saldo'];
    } elseif ($fila['tipo'] == 'PASIVO') {
        $pasivos[] = $fila;
        $totalPasivos += $fila['saldo'];
    } elseif ($fila['tipo'] == 'PATRIMONIO') {
        $patrimonios[] = $fila;
        $totalPatrimonios += $fila['saldo'];
    }
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

  <link href="assets/css/improved-style.css" rel="stylesheet">

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo"><a href="dashboard.php"> S O F I  = >  Software Financiero </a>  </h1>
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
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

    <!-- ======= Services Section ======= -->
    <section id="services" class="services">
      <div class="container">
        <h1>Estado de Situación Financiera</h1>
        <form class="form-filtros" method="get">
            Desde: <input type="date" name="desde" value="<?= $fecha_desde ?>">
            Hasta: <input type="date" name="hasta" value="<?= $fecha_hasta ?>">
            <input type="submit" value="Filtrar">
        </form>

        <div class="row">
            <!-- ACTIVO -->
            <div class="col-md-4">
                <h2>Activo</h2>
                <table class="table table-bordered">
                    <tr>
                        <th>Código</th>
                        <th>Cuenta</th>
                        <th>Saldo</th>
                    </tr>
                    <?php foreach($activos as $fila): ?>
                    <tr>
                        <td><?= $fila['codigo'] ?></td>
                        <td><?= $fila['cuenta'] ?></td>
                        <td><?= number_format($fila['saldo'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-info">
                        <td colspan="2">Total Activos</td>
                        <td><?= number_format($totalActivos,2) ?></td>
                    </tr>
                </table>
            </div>

            <!-- PASIVO -->
            <div class="col-md-4">
                <h2>Pasivo</h2>
                <table class="table table-bordered">
                    <tr>
                        <th>Código</th>
                        <th>Cuenta</th>
                        <th>Saldo</th>
                    </tr>
                    <?php foreach($pasivos as $fila): ?>
                    <tr>
                        <td><?= $fila['codigo'] ?></td>
                        <td><?= $fila['cuenta'] ?></td>
                        <td><?= number_format($fila['saldo'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-info">
                        <td colspan="2">Total Pasivos</td>
                        <td><?= number_format($totalPasivos,2) ?></td>
                    </tr>
                </table>
            </div>

            <!-- PATRIMONIO -->
            <div class="col-md-4">
                <h2>Patrimonio</h2>
                <table class="table table-bordered">
                    <tr>
                        <th>Código</th>
                        <th>Cuenta</th>
                        <th>Saldo</th>
                    </tr>
                    <?php foreach($patrimonios as $fila): ?>
                    <tr>
                        <td><?= $fila['codigo'] ?></td>
                        <td><?= $fila['cuenta'] ?></td>
                        <td><?= number_format($fila['saldo'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-info">
                        <td colspan="2">Total Patrimonio</td>
                        <td><?= number_format($totalPatrimonios,2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <p style="margin-top:40px;text-align:center;">
        ______________________________ <br>
        CONTADOR PÚBLICO
        </p>
        <p style="margin-top:20px;text-align:center;">
        ______________________________ <br>
        REPRESENTANTE LEGAL
        </p>
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