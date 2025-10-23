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
      <div class="container">
          <h2 class="section-title text-center" style="color: #054a85;">ESTADO DE SITUACIÓN FINANCIERA</h2>
          
          <!-- Formulario de filtros -->
          <form class="row g-3 mb-4 justify-content-center" method="get">
              <div class="col-md-4">
                  <label class="form-label visually-hidden">Desde:</label>
                  <div class="input-group">
                      <span class="input-group-text">Desde:</span>
                      <input type="date" name="desde" class="form-control" value="<?= $fecha_desde ?>">
                  </div>
              </div>
              <div class="col-md-4">
                  <label class="form-label visually-hidden">Hasta:</label>
                  <div class="input-group">
                      <span class="input-group-text">Hasta:</span>
                      <input type="date" name="hasta" class="form-control" value="<?= $fecha_hasta ?>">
                  </div>
              </div>
              <div class="col-md-2 d-grid align-items-end">
                  <button type="submit" class="btn btn-primary">Filtrar</button>
              </div>
          </form>

          <div class="row d-flex">
              <div class="col-12">
                  <h2 class="mt-4">Activos</h2>
                  <table class="table-container">
                      <thead style="background-color:#f8f9fa;">
                          <tr>
                              <th>Código</th>
                              <th>Nombre de la cuenta</th>
                              <th class="text-end">Saldo</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach($activos as $fila): ?>
                              <tr>
                                  <td><?= $fila['codigo'] ?></td>
                                  <td><?= $fila['cuenta'] ?></td>
                                  <td class="text-end"><?= number_format($fila['saldo'],2) ?></td>
                              </tr>
                          <?php endforeach; ?>
                          <tr class="fw-bold">
                              <td colspan="2">Total Activos</td>
                              <td class="text-end"><?= number_format($totalActivos,2) ?></td>
                          </tr>
                      </tbody>
                  </table>
              </div>

              <div class="col-12">
                  <h2 class="mt-4">Pasivos</h2>
                  <table class="table-container">
                      <thead style="background-color:#f8f9fa;">
                          <tr>
                              <th>Código</th>
                              <th>Nombre de la cuenta</th>
                              <th class="text-end">Saldo</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach($pasivos as $fila): ?>
                              <tr>
                                  <td><?= $fila['codigo'] ?></td>
                                  <td><?= $fila['cuenta'] ?></td>
                                  <td class="text-end"><?= number_format($fila['saldo'],2) ?></td>
                              </tr>
                          <?php endforeach; ?>
                          <tr class="fw-bold">
                              <td colspan="2">Total Pasivos</td>
                              <td class="text-end"><?= number_format($totalPasivos,2) ?></td>
                          </tr>
                      </tbody>
                  </table>
              </div>

              <div class="col-12">
                  <h2 class="mt-4">Patrimonio</h2>
                  <table class="table-container">
                      <thead style="background-color:#f8f9fa;">
                          <tr>
                              <th>Código</th>
                              <th>Nombre de la cuenta</th>
                              <th class="text-end">Saldo</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach($patrimonios as $fila): ?>
                              <tr>
                                  <td><?= $fila['codigo'] ?></td>
                                  <td><?= $fila['cuenta'] ?></td>
                                  <td class="text-end"><?= number_format($fila['saldo'],2) ?></td>
                              </tr>
                          <?php endforeach; ?>
                          <tr class="fw-bold">
                              <td colspan="2">Total Patrimonio</td>
                              <td class="text-end"><?= number_format($totalPatrimonios,2) ?></td>
                          </tr>
                      </tbody>
                  </table>
              </div>

          </div>

          <div class="text-center mt-5 row justify-content-center">
              <br>
              <div class="col-md-5">
                  <p>______________________________ <br> CONTADOR PÚBLICO</p>
              </div>
              <div class="col-md-5">
                  <p>______________________________ <br> REPRESENTANTE LEGAL</p>
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