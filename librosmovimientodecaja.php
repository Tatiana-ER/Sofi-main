<?php
// ================== CONEXIÓN ==================
include("connection_demo.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$forma_pago_desde = isset($_GET['forma_desde']) ? $_GET['forma_desde'] : '';
$forma_pago_hasta = isset($_GET['forma_hasta']) ? $_GET['forma_hasta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

// ================== CONSULTA ==================
$sql = "
SELECT 
    m.forma_pago,
    t.identificacion AS identificacion_tercero,
    t.nombre AS nombre_tercero,
    co.codigo AS comprobante,
    m.fecha AS fecha_comprobante,
    IFNULL(s.saldo_inicial, 0) AS saldo_inicial,
    m.debe AS movimiento_debito,
    m.haber AS movimiento_credito,
    (IFNULL(s.saldo_inicial,0) + m.debe - m.haber) AS saldo_final
FROM movimientos_contables m
LEFT JOIN terceros t ON t.id = m.tercero_id
LEFT JOIN comprobantes co ON co.id = m.comprobante_id
LEFT JOIN saldos_iniciales s ON s.cuenta_id = m.cuenta_id
WHERE m.fecha BETWEEN :desde AND :hasta
";

if ($forma_pago_desde != '' && $forma_pago_hasta != '') {
    $sql .= " AND m.forma_pago BETWEEN :forma_desde AND :forma_hasta";
}

if ($tercero != '') {
    $sql .= " AND t.identificacion LIKE :tercero";
}

$sql .= " ORDER BY m.forma_pago, m.fecha ASC";

$stmt = $pdo->prepare($sql);

$params = [
    ':desde' => $fecha_desde,
    ':hasta' => $fecha_hasta
];

if ($forma_pago_desde != '' && $forma_pago_hasta != '') {
    $params[':forma_desde'] = $forma_pago_desde;
    $params[':forma_hasta'] = $forma_pago_hasta;
}
if ($tercero != '') {
    $params[':tercero'] = "%$tercero%";
}

$stmt->execute($params);
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== TOTALES ==================
$totalInicial = 0;
$totalDebito = 0;
$totalCredito = 0;
$totalFinal = 0;

foreach ($datos as $fila) {
    $totalInicial += $fila['saldo_inicial'];
    $totalDebito += $fila['movimiento_debito'];
    $totalCredito += $fila['movimiento_credito'];
    $totalFinal += $fila['saldo_final'];
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link href="assets/css/improved-style.css" rel="stylesheet">
  
  <style>
    .btn-ir {
      background-color: #054a85;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 20px;
      cursor: pointer;
      font-size: 16px;
      transition: 0.3s;
      margin-left: 50px;
    }
    .btn-ir::before {
      margin-right: 8px;
      font-size: 18px;
    }

    .btn-ir:hover {
      background-color: #4c82b0ff;
    }
  </style>

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo"><a href="dashboard.php"> S O F I = > Software Financiero </a>   </h1>
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
     
    <!-- ======= CONTENIDO ======= -->
  <section id="services" class="services mt-5 pt-5">

    <button class="btn-ir" onclick="window.location.href='menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>

    <div class="container" data-aos="fade-up">

      <h2 class="section-title" style="color:#054a85;">MOVIMIENTO DE CAJA</h2>

      <!-- ====== FORMULARIO FILTROS ====== -->
      <form class="row g-3 mb-4 justify-content-center align-items-end" method="get">
        
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text">Forma de pago (de):</span>
            <input type="text" name="forma_desde" class="form-control" placeholder="Ej: Efectivo" value="<?= htmlspecialchars($forma_pago_desde) ?>">
          </div>
        </div>
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text">a:</span>
            <input type="text" name="forma_hasta" class="form-control" placeholder="Ej: Transferencia" value="<?= htmlspecialchars($forma_pago_hasta) ?>">
          </div>
        </div>

        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text">Tercero:</span>
            <input type="text" name="tercero" class="form-control" placeholder="Identificación" value="<?= htmlspecialchars($tercero) ?>">
          </div>
        </div>

        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text">Fecha desde:</span>
            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
          </div>
        </div>
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text">Hasta:</span>
            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
          </div>
        </div>

        <div class="col-md-2 d-grid">
          <button type="submit" class="btn btn-primary">Consultar</button>
        </div>
      </form>

      <!-- ====== TABLA RESULTADOS ====== -->
      <table class="table table-bordered">
        <thead style="background-color:#f8f9fa;">
          <tr>
            <th>Forma de pago</th>
            <th>Identificación del tercero</th>
            <th>Nombre del tercero</th>
            <th>Comprobante</th>
            <th>Fecha comprobante</th>
            <th class="text-end">Saldo inicial</th>
            <th class="text-end">Movimiento Débito</th>
            <th class="text-end">Movimiento Crédito</th>
            <th class="text-end">Saldo final</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($datos as $fila): ?>
            <tr>
              <td><?= htmlspecialchars($fila['forma_pago']) ?></td>
              <td><?= htmlspecialchars($fila['identificacion_tercero']) ?></td>
              <td><?= htmlspecialchars($fila['nombre_tercero']) ?></td>
              <td><?= htmlspecialchars($fila['comprobante']) ?></td>
              <td><?= htmlspecialchars($fila['fecha_comprobante']) ?></td>
              <td class="text-end"><?= number_format($fila['saldo_inicial'],2) ?></td>
              <td class="text-end"><?= number_format($fila['movimiento_debito'],2) ?></td>
              <td class="text-end"><?= number_format($fila['movimiento_credito'],2) ?></td>
              <td class="text-end"><?= number_format($fila['saldo_final'],2) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="fw-bold">
            <td colspan="5" class="text-end">TOTALES</td>
            <td class="text-end"><?= number_format($totalInicial,2) ?></td>
            <td class="text-end"><?= number_format($totalDebito,2) ?></td>
            <td class="text-end"><?= number_format($totalCredito,2) ?></td>
            <td class="text-end"><?= number_format($totalFinal,2) ?></td>
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