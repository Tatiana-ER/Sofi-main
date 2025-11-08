<?php
// ================== CONEXIÓN ==================
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

// ================== INFORMACIÓN DE LA CUENTA ==================
$info_cuenta = null;
if ($cuenta_codigo != '') {
    // Buscar en catalogoscuentascontables
    $sql_cuenta = "SELECT 
                    COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1) as codigo,
                    CASE 
                        WHEN nivel6 IS NOT NULL AND nivel6 != '' THEN nivel5
                        WHEN nivel5 IS NOT NULL AND nivel5 != '' THEN nivel4
                        WHEN nivel4 IS NOT NULL AND nivel4 != '' THEN nivel3
                        WHEN nivel3 IS NOT NULL AND nivel3 != '' THEN nivel2
                        WHEN nivel2 IS NOT NULL AND nivel2 != '' THEN nivel1
                        ELSE 'Cuenta'
                    END as nombre
                   FROM catalogoscuentascontables
                   WHERE nivel2 = :codigo 
                      OR nivel3 = :codigo 
                      OR nivel4 = :codigo 
                      OR nivel5 = :codigo 
                      OR nivel6 = :codigo
                   LIMIT 1";
    
    $stmt_cuenta = $pdo->prepare($sql_cuenta);
    $stmt_cuenta->execute([':codigo' => $cuenta_codigo]);
    $info_cuenta = $stmt_cuenta->fetch(PDO::FETCH_ASSOC);
}

// ================== SALDO INICIAL ==================
$saldo_inicial = 0;
if ($cuenta_codigo != '') {
    $sql_saldo_inicial = "
    SELECT 
        COALESCE(SUM(debito - credito), 0) as saldo
    FROM libro_diario
    WHERE codigo_cuenta = :cuenta
      AND fecha < :fecha_desde
    ";
    
    $stmt_saldo = $pdo->prepare($sql_saldo_inicial);
    $stmt_saldo->execute([
        ':cuenta' => $cuenta_codigo,
        ':fecha_desde' => $fecha_desde
    ]);
    $result_saldo = $stmt_saldo->fetch(PDO::FETCH_ASSOC);
    $saldo_inicial = $result_saldo['saldo'] ?? 0;
}

// ================== CONSULTA DE MOVIMIENTOS ==================
$sql = "
SELECT 
    ld.fecha,
    ld.tipo_documento,
    ld.numero_documento,
    ld.codigo_cuenta,
    ld.nombre_cuenta,
    ld.tercero_identificacion,
    ld.tercero_nombre,
    ld.concepto,
    ld.debito,
    ld.credito
FROM libro_diario ld
WHERE ld.fecha BETWEEN :desde AND :hasta
";

if ($cuenta_codigo != '') {
    $sql .= " AND ld.codigo_cuenta = :cuenta";
}

if ($tercero != '') {
    $sql .= " AND ld.tercero_identificacion LIKE :tercero";
}

$sql .= " ORDER BY ld.fecha ASC, ld.id ASC";

$stmt = $pdo->prepare($sql);

$params = [
    ':desde' => $fecha_desde,
    ':hasta' => $fecha_hasta
];

if ($cuenta_codigo != '') {
    $params[':cuenta'] = $cuenta_codigo;
}

if ($tercero != '') {
    $params[':tercero'] = "%$tercero%";
}

$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== CALCULAR SALDOS ACUMULADOS Y TOTALES ==================
$saldo_acumulado = $saldo_inicial;
$total_debito = 0;
$total_credito = 0;

foreach ($movimientos as $key => $mov) {
    $total_debito += $mov['debito'];
    $total_credito += $mov['credito'];
    
    // Calcular saldo acumulado (naturaleza débito: suma débitos, resta créditos)
    $saldo_acumulado += ($mov['debito'] - $mov['credito']);
    
    // Agregar saldo acumulado al array
    $movimientos[$key]['saldo_acumulado'] = $saldo_acumulado;
}

$saldo_final = $saldo_acumulado;

// ================== OBTENER LISTA DE CUENTAS PARA EL SELECT ==================
$sql_cuentas = "
SELECT DISTINCT 
    codigo_cuenta,
    nombre_cuenta
FROM libro_diario
ORDER BY codigo_cuenta
";
$stmt_cuentas = $pdo->query($sql_cuentas);
$lista_cuentas = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Libro Auxiliar - SOFI</title>
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
    
    .info-cuenta-card {
      background: linear-gradient(135deg, #054a85 0%, #4c82b0ff 100%);
      color: white;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .info-cuenta-card h4 {
      margin: 0 0 10px 0;
      font-size: 1.3rem;
    }
    
    .info-cuenta-card p {
      margin: 5px 0;
      font-size: 1rem;
    }
    
    .saldo-badge {
      display: inline-block;
      padding: 8px 15px;
      background: rgba(255,255,255,0.2);
      border-radius: 20px;
      font-weight: bold;
      margin-top: 10px;
    }
    
    .table-container {
      overflow-x: auto;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border-radius: 8px;
    }
    
    .table-auxiliar {
      width: 100%;
      border-collapse: collapse;
      background: white;
    }
    
    .table-auxiliar thead {
      background-color: #054a85;
      color: white;
    }
    
    .table-auxiliar th {
      padding: 12px;
      text-align: left;
      font-weight: 600;
      border: 1px solid #dee2e6;
    }
    
    .table-auxiliar td {
      padding: 10px;
      border: 1px solid #dee2e6;
    }
    
    .table-auxiliar tbody tr:hover {
      background-color: #f8f9fa;
    }
    
    .text-end {
      text-align: right !important;
    }
    
    .text-center {
      text-align: center !important;
    }
    
    .totales-row {
      background-color: #e9ecef;
      font-weight: bold;
    }
    
    .tipo-doc-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.85rem;
      font-weight: 500;
    }
    
    .badge-fv { background-color: #28a745; color: white; }
    .badge-fc { background-color: #17a2b8; color: white; }
    .badge-rc { background-color: #007bff; color: white; }
    .badge-ce { background-color: #ffc107; color: #000; }
    .badge-cc { background-color: #6c757d; color: white; }
    
    .no-data {
      text-align: center;
      padding: 40px;
      color: #6c757d;
      font-style: italic;
    }
    
    .btn-export {
      margin-left: 10px;
    }
  </style>

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="dashboard.php">
          <img src="./Img/sofilogo5pequeño.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto active" href="dashboard.php" style="color: darkblue;">Inicio</a></li>
          <li><a class="nav-link scrollto active" href="perfil.php" style="color: darkblue;">Mi Negocio</a></li>
          <li><a class="nav-link scrollto active" href="index.php" style="color: darkblue;">Cerrar Sesión</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <!-- ======= Services Section ======= -->
  <section id="services" class="services mt-5 pt-5">

    <button class="btn-ir" onclick="window.location.href='menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>

    <div class="container" data-aos="fade-up">

      <h2 class="section-title" style="color:#054a85;">📖 LIBRO AUXILIAR</h2>

      <!-- ====== FORMULARIO DE FILTRO ====== -->
      <form class="row g-3 mb-4" method="get">
        
        <div class="col-md-4">
          <label class="form-label">Cuenta contable:</label>
          <select name="cuenta" class="form-control" required>
            <option value="">-- Seleccione una cuenta --</option>
            <?php foreach ($lista_cuentas as $cuenta): ?>
              <option value="<?= htmlspecialchars($cuenta['codigo_cuenta']) ?>" 
                      <?= $cuenta_codigo == $cuenta['codigo_cuenta'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cuenta['codigo_cuenta']) ?> - <?= htmlspecialchars($cuenta['nombre_cuenta']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="col-md-3">
          <label class="form-label">Tercero (opcional):</label>
          <input type="text" name="tercero" class="form-control" 
                 placeholder="Identificación" value="<?= htmlspecialchars($tercero) ?>">
        </div>
        
        <div class="col-md-2">
          <label class="form-label">Desde:</label>
          <input type="date" name="desde" class="form-control" 
                 value="<?= htmlspecialchars($fecha_desde) ?>" required>
        </div>
        
        <div class="col-md-2">
          <label class="form-label">Hasta:</label>
          <input type="date" name="hasta" class="form-control" 
                 value="<?= htmlspecialchars($fecha_hasta) ?>" required>
        </div>
        
        <div class="col-md-1 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fa-solid fa-search"></i> Consultar
          </button>
        </div>
      </form>

      <?php if ($cuenta_codigo != '' && $info_cuenta): ?>
        
        <!-- ====== INFORMACIÓN DE LA CUENTA ====== -->
        <div class="info-cuenta-card">
          <h4>
            <i class="fa-solid fa-file-invoice"></i> 
            Cuenta: <?= htmlspecialchars($cuenta_codigo) ?>
          </h4>
          <p>
            <strong>Nombre:</strong> <?= htmlspecialchars($info_cuenta['nombre'] ?? 'N/A') ?>
          </p>
          <p>
            <strong>Período:</strong> <?= date('d/m/Y', strtotime($fecha_desde)) ?> al <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
          </p>
          <div class="saldo-badge">
            <i class="fa-solid fa-wallet"></i> 
            Saldo Inicial: $<?= number_format($saldo_inicial, 2) ?>
          </div>
        </div>

        <!-- ====== TABLA DE MOVIMIENTOS ====== -->
        <div class="table-container">
          <table class="table-auxiliar">
            <thead>
              <tr>
                <th style="width: 100px;">Fecha</th>
                <th style="width: 120px;">Tipo Doc.</th>
                <th style="width: 100px;">No. Doc.</th>
                <th style="width: 120px;">Tercero</th>
                <th>Concepto</th>
                <th class="text-end" style="width: 120px;">Débito</th>
                <th class="text-end" style="width: 120px;">Crédito</th>
                <th class="text-end" style="width: 130px;">Saldo</th>
              </tr>
            </thead>
            <tbody>
              
              <!-- Fila de saldo inicial -->
              <?php if ($saldo_inicial != 0): ?>
              <tr style="background-color: #fff3cd;">
                <td colspan="5" class="text-center"><strong>SALDO INICIAL</strong></td>
                <td class="text-end">-</td>
                <td class="text-end">-</td>
                <td class="text-end"><strong>$<?= number_format($saldo_inicial, 2) ?></strong></td>
              </tr>
              <?php endif; ?>

              <!-- Movimientos -->
              <?php if (count($movimientos) > 0): ?>
                <?php foreach ($movimientos as $mov): ?>
                  <tr>
                    <td><?= date('d/m/Y', strtotime($mov['fecha'])) ?></td>
                    <td>
                      <?php 
                      $badges = [
                        'factura_venta' => '<span class="tipo-doc-badge badge-fv">F. Venta</span>',
                        'factura_compra' => '<span class="tipo-doc-badge badge-fc">F. Compra</span>',
                        'recibo_caja' => '<span class="tipo-doc-badge badge-rc">R. Caja</span>',
                        'comprobante_egreso' => '<span class="tipo-doc-badge badge-ce">C. Egreso</span>',
                        'comprobante_contable' => '<span class="tipo-doc-badge badge-cc">C. Contable</span>'
                      ];
                      echo $badges[$mov['tipo_documento']] ?? $mov['tipo_documento'];
                      ?>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($mov['numero_documento']) ?></td>
                    <td>
                      <?php if ($mov['tercero_identificacion']): ?>
                        <small><?= htmlspecialchars($mov['tercero_identificacion']) ?></small>
                        <?php if ($mov['tercero_nombre']): ?>
                          <br><small style="color: #6c757d;"><?= htmlspecialchars($mov['tercero_nombre']) ?></small>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($mov['concepto']) ?></td>
                    <td class="text-end" style="color: #0066cc;">
                      <?= $mov['debito'] > 0 ? '$' . number_format($mov['debito'], 2) : '-' ?>
                    </td>
                    <td class="text-end" style="color: #cc0000;">
                      <?= $mov['credito'] > 0 ? '$' . number_format($mov['credito'], 2) : '-' ?>
                    </td>
                    <td class="text-end">
                      <strong>$<?= number_format($mov['saldo_acumulado'], 2) ?></strong>
                    </td>
                  </tr>
                <?php endforeach; ?>
                
                <!-- Totales -->
                <tr class="totales-row">
                  <td colspan="5" class="text-end"><strong>TOTALES DEL PERÍODO:</strong></td>
                  <td class="text-end"><strong>$<?= number_format($total_debito, 2) ?></strong></td>
                  <td class="text-end"><strong>$<?= number_format($total_credito, 2) ?></strong></td>
                  <td class="text-end"><strong>$<?= number_format($saldo_final, 2) ?></strong></td>
                </tr>
                
              <?php else: ?>
                <tr>
                  <td colspan="8" class="no-data">
                    <i class="fa-solid fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p>No hay movimientos registrados para esta cuenta en el período seleccionado.</p>
                  </td>
                </tr>
              <?php endif; ?>
              
            </tbody>
          </table>
        </div>

        <!-- Botones de exportación -->
        <div class="mt-3">
          <button onclick="window.print()" class="btn btn-secondary">
            <i class="fa-solid fa-print"></i> Imprimir
          </button>
          <button onclick="exportarExcel()" class="btn btn-success btn-export">
            <i class="fa-solid fa-file-excel"></i> Exportar a Excel
          </button>
        </div>

      <?php elseif ($cuenta_codigo == ''): ?>
        <div class="alert alert-info text-center">
          <i class="fa-solid fa-info-circle"></i> 
          Por favor, seleccione una cuenta contable para ver su libro auxiliar.
        </div>
      <?php else: ?>
        <div class="alert alert-warning text-center">
          <i class="fa-solid fa-exclamation-triangle"></i> 
          No se encontró información para la cuenta seleccionada.
        </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer>

  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    function exportarExcel() {
      const params = new URLSearchParams(window.location.search);
      window.location.href = 'exportar_libro_auxiliar.php?' + params.toString();
    }
  </script>

</body>

</html>