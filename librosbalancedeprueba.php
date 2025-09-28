<?php
// ================== CONEXIÓN ==================
include("connection_demo.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_desde = isset($_GET['cuenta_desde']) ? $_GET['cuenta_desde'] : '';
$cuenta_hasta = isset($_GET['cuenta_hasta']) ? $_GET['cuenta_hasta'] : '';

// ================== CONSULTA ==================
$sql = "SELECT 
            c.codigo,
            c.nombre AS cuenta,
            IFNULL(s.saldo_inicial,0) AS saldo_inicial,
            IFNULL(SUM(m.debe),0) AS debito,
            IFNULL(SUM(m.haber),0) AS credito,
            (IFNULL(s.saldo_inicial,0) + IFNULL(SUM(m.debe),0) - IFNULL(SUM(m.haber),0)) AS saldo_final
        FROM cuentas_contables c
        LEFT JOIN saldos_iniciales s 
            ON s.cuenta_id = c.id 
            /* puedes filtrar por periodo específico si tienes un campo periodo */
        INNER JOIN movimientos_contables m 
            ON m.cuenta_id = c.id 
            AND m.fecha BETWEEN :desde AND :hasta
        WHERE 1=1";

if ($cuenta_desde != '' && $cuenta_hasta != '') {
    $sql .= " AND c.codigo BETWEEN :cuenta_desde AND :cuenta_hasta";
}

$sql .= " GROUP BY c.id, c.codigo, c.nombre, s.saldo_inicial
          ORDER BY c.codigo";

$stmt = $pdo->prepare($sql);

// Parámetros obligatorios
$params = [
    ':desde' => $fecha_desde,
    ':hasta' => $fecha_hasta
];
// Parámetros opcionales
if ($cuenta_desde != '' && $cuenta_hasta != '') {
    $params[':cuenta_desde'] = $cuenta_desde;
    $params[':cuenta_hasta'] = $cuenta_hasta;
}

$stmt->execute($params);
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== TOTALES ==================
$totalSaldoInicial = 0;
$totalDebito = 0;
$totalCredito = 0;
$totalSaldoFinal = 0;
foreach ($datos as $fila) {
    $totalSaldoInicial += $fila['saldo_inicial'];
    $totalDebito += $fila['debito'];
    $totalCredito += $fila['credito'];
    $totalSaldoFinal += $fila['saldo_final'];
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
      <h1 class="logo"><a href="dashboard.php"> S O F I = >  Software Financiero  </a>  </h1>
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
      <div class="container" data-aos="fade-up">

        <h2 class="section-title" style="color: #054a85;">BALANCE DE PRUEBA</h2>

        <!-- ====== FORMULARIO DE FILTRO ====== -->
        <form class="row g-3 mb-4" method="get">
          <div class="col-md-4">
            <label class="form-label">Cuenta desde:</label>
            <input type="text" name="cuenta_desde" class="form-control" placeholder="Desde" value="<?= htmlspecialchars($cuenta_desde) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Cuenta hasta:</label>
            <input type="text" name="cuenta_hasta" class="form-control" placeholder="Hasta" value="<?= htmlspecialchars($cuenta_hasta) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Desde:</label>
            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Hasta:</label>
            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Consultar</button>
          </div>
        </form>

        <!-- ====== TABLA DE RESULTADOS ====== -->
        <table class="table table-bordered">
          <thead style="background-color:#f8f9fa;">
            <tr>
              <th>Código cuenta contable</th>
              <th>Nombre de la cuenta</th>
              <th class="text-end">Saldo Inicial</th>
              <th class="text-end">Movimiento Débito</th>
              <th class="text-end">Movimiento Crédito</th>
              <th class="text-end">Saldo Final</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($datos as $fila): ?>
              <tr>
                <td><?= htmlspecialchars($fila['codigo']) ?></td>
                <td><?= htmlspecialchars($fila['cuenta']) ?></td>
                <td class="text-end"><?= number_format($fila['saldo_inicial'],2) ?></td>
                <td class="text-end"><?= number_format($fila['debito'],2) ?></td>
                <td class="text-end"><?= number_format($fila['credito'],2) ?></td>
                <td class="text-end"><?= number_format($fila['saldo_final'],2) ?></td>
              </tr>
            <?php endforeach; ?>
            <tr class="fw-bold">
              <td colspan="2">TOTALES</td>
              <td class="text-end"><?= number_format($totalSaldoInicial,2) ?></td>
              <td class="text-end"><?= number_format($totalDebito,2) ?></td>
              <td class="text-end"><?= number_format($totalCredito,2) ?></td>
              <td class="text-end"><?= number_format($totalSaldoFinal,2) ?></td>
            </tr>
          </tbody>
        </table>

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