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

        <h2 class="section-title" style="color: #054a85;">BALANCE DE PRUEBA</h2>

        <!-- ====== FORMULARIO DE FILTRO ====== -->
        <form class="row g-3 mb-4 justify-content-center align-items-end" method="get">
    
          <div class="col-md-5">
              <label class="form-label visually-hidden">Cuenta desde:</label>
              <div class="input-group">
                  <span class="input-group-text">Cuenta desde:</span>
                  <input type="text" name="cuenta_desde" class="form-control" placeholder="Código" value="<?= htmlspecialchars($cuenta_desde) ?>">
              </div>
          </div>
          <div class="col-md-5">
              <label class="form-label visually-hidden">Cuenta hasta:</label>
              <div class="input-group">
                  <span class="input-group-text">Cuenta hasta:</span>
                  <input type="text" name="cuenta_hasta" class="form-control" placeholder="Código" value="<?= htmlspecialchars($cuenta_hasta) ?>">
              </div>
          </div>
          
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