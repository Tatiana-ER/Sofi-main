<?php
// ================== CONEXIÓN ==================
include("connection_demo.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');

// ================== CONSULTA ==================
$sql = "SELECT 
            comprobante,
            fecha_elaboracion,
            identificacion_tercero,
            nombre_tercero,
            factura_proveedor,
            base_gravada,
            base_exenta,
            iva,
            (base_gravada + base_exenta + iva) AS total
        FROM compras
        WHERE fecha_elaboracion BETWEEN :desde AND :hasta
        ORDER BY fecha_elaboracion ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== TOTALES ==================
$totalBaseGravada = 0;
$totalBaseExenta = 0;
$totalIVA = 0;
$totalGeneral = 0;

foreach ($compras as $c) {
    $totalBaseGravada += $c['base_gravada'];
    $totalBaseExenta += $c['base_exenta'];
    $totalIVA += $c['iva'];
    $totalGeneral += $c['total'];
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
        
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

    <!-- ======= Services Section ======= -->
 <section id="services" class="services">

  <button class="btn-ir" onclick="window.location.href='menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>

    <div class="container" data-aos="fade-up">
      <h2 class="section-title" style="color:#054a85;">LIBRO DE COMPRAS</h2>

      <!-- ====== FILTRO ====== -->
      <form class="row g-3 mb-4 justify-content-center align-items-end" method="get">
        <div class="col-md-4">
          <label class="form-label visually-hidden">Desde:</label>
          <div class="input-group">
            <span class="input-group-text">Desde:</span>
            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label visually-hidden">Hasta:</label>
          <div class="input-group">
            <span class="input-group-text">Hasta:</span>
            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
          </div>
        </div>
        <div class="col-md-2 d-grid">
          <button type="submit" class="btn btn-primary">Consultar</button>
        </div>
      </form>

      <!-- ====== TABLA DE RESULTADOS ====== -->
      <table class="table-container">
        <thead style="background-color:#f8f9fa;">
          <tr>
            <th>Comprobante</th>
            <th>Fecha de elaboración</th>
            <th>Identificación del tercero</th>
            <th>Nombre del tercero</th>
            <th>Factura proveedor</th>
            <th class="text-end">Base gravada</th>
            <th class="text-end">Base exenta</th>
            <th class="text-end">IVA</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($compras as $fila): ?>
          <tr>
            <td><?= htmlspecialchars($fila['comprobante']) ?></td>
            <td><?= htmlspecialchars($fila['fecha_elaboracion']) ?></td>
            <td><?= htmlspecialchars($fila['identificacion_tercero']) ?></td>
            <td><?= htmlspecialchars($fila['nombre_tercero']) ?></td>
            <td><?= htmlspecialchars($fila['factura_proveedor']) ?></td>
            <td class="text-end"><?= number_format($fila['base_gravada'], 2) ?></td>
            <td class="text-end"><?= number_format($fila['base_exenta'], 2) ?></td>
            <td class="text-end"><?= number_format($fila['iva'], 2) ?></td>
            <td class="text-end"><?= number_format($fila['total'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="fw-bold">
            <td colspan="5" class="text-end">TOTALES</td>
            <td class="text-end"><?= number_format($totalBaseGravada, 2) ?></td>
            <td class="text-end"><?= number_format($totalBaseExenta, 2) ?></td>
            <td class="text-end"><?= number_format($totalIVA, 2) ?></td>
            <td class="text-end"><?= number_format($totalGeneral, 2) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
<!-- End Services Section -->

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