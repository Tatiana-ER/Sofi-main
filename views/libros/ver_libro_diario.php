<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/auth_check.php'; ?>
<?php
// ================== CONEXIÓN ==================
require_once '../../config/database.php';

$pdo = Database::getConnection();

// ================== OBTENER DATOS DEL PERFIL ==================
$sql_perfil = "SELECT persona, nombres, apellidos, razon, cedula, digito FROM perfil LIMIT 1";
$stmt_perfil = $pdo->query($sql_perfil);
$perfil = $stmt_perfil->fetch(PDO::FETCH_ASSOC);

// Determinar qué mostrar como nombre de empresa
if ($perfil) {
    if ($perfil['persona'] == 'juridica' && !empty($perfil['razon'])) {
        $nombre_empresa = $perfil['razon'];
    } else {
        $nombre_empresa = trim($perfil['nombres'] . ' ' . $perfil['apellidos']);
    }
    $nit_empresa = $perfil['cedula'] . ($perfil['digito'] > 0 ? '-' . $perfil['digito'] : '');
} else {
    $nombre_empresa = 'Nombre de la Empresa';
    $nit_empresa = 'NIT de la Empresa';
}

// ================== FILTROS ==================
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy
$tipo_documento = $_GET['tipo_documento'] ?? '';
$codigo_cuenta = $_GET['codigo_cuenta'] ?? '';

// Construir consulta con filtros
$sql = "SELECT 
            ld.id,
            ld.fecha,
            ld.tipo_documento,
            ld.numero_documento,
            ld.codigo_cuenta,
            ld.nombre_cuenta,
            ld.tercero_identificacion,
            ld.tercero_nombre,
            ld.concepto,
            FORMAT(ld.debito, 2) as debito,
            FORMAT(ld.credito, 2) as credito
        FROM libro_diario ld
        WHERE ld.fecha BETWEEN :fecha_inicio AND :fecha_fin";

if (!empty($tipo_documento)) {
    $sql .= " AND ld.tipo_documento = :tipo_documento";
}

if (!empty($codigo_cuenta)) {
    $sql .= " AND ld.codigo_cuenta LIKE :codigo_cuenta";
}

$sql .= " ORDER BY ld.fecha ASC, ld.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);

if (!empty($tipo_documento)) {
    $stmt->bindParam(':tipo_documento', $tipo_documento);
}

if (!empty($codigo_cuenta)) {
    $codigo_busqueda = $codigo_cuenta . '%';
    $stmt->bindParam(':codigo_cuenta', $codigo_busqueda);
}

$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_debito = 0;
$total_credito = 0;
foreach ($movimientos as $mov) {
    $total_debito += floatval(str_replace(',', '', $mov['debito']));
    $total_credito += floatval(str_replace(',', '', $mov['credito']));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Libro Diario - SOFI</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="../../assets/img/favicon.png" rel="icon">
  <link href="../../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../../assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="../../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="../../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link href="../../assets/css/improved-style.css" rel="stylesheet">

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
    .btn-ir:hover {
      background-color: #4c82b0ff;
    }

    .table-container {
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }

    .table-container table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }

    .table-container thead {
      background-color: #054a85;
      color: white;
    }

    .table-container th {
      padding: 12px 8px;
      text-align: left;
      font-weight: 600;
    }

    .table-container td {
      padding: 10px 8px;
      border-bottom: 1px solid #dee2e6;
      vertical-align: middle;
    }

    .table-container tbody tr:hover {
      background-color: #f8f9fa;
    }

    .text-end {
      text-align: right !important;
    }

    .total-row td {
      font-weight: bold;
      background-color: #f0f0f0;
      border-top: 2px solid #054a85 !important;
    }

    .debito {
      text-align: right;
      color: #0066cc;
    }

    .credito {
      text-align: right;
      color: #cc0000;
    }

    .fila-cuadrado td {
      background-color: #d4edda !important;
      color: #155724;
      font-weight: bold;
    }

    .fila-descuadrado td {
      background-color: #f8d7da !important;
      color: #721c24;
      font-weight: bold;
    }

    .no-mov {
      text-align: center;
      padding: 30px;
      color: #6c757d;
      font-style: italic;
    }

    @media print {
      .btn-ir, form, .btn-cancelar, .btn-agregar, .btn-agregar-excel { display: none; }
    }
  </style>
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="../../dashboard.php">
          <img src="../../Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto active" href="../../dashboard.php" style="color: darkblue;">Inicio</a></li>
          <li><a class="nav-link scrollto active" href="../../perfil.php" style="color: darkblue;">Mi Negocio</a></li>
          <li><a class="nav-link scrollto active" href="../../index.php" style="color: darkblue;">Cerrar Sesión</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>

  <!-- ======= Libro Diario Section ======= -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='../menus/menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>

    <div class="container-fluid px-4" data-aos="fade-up">
      <div class="section-title">
        <h2><i class="fa-solid fa-book-open"></i> LIBRO DIARIO</h2>

        <!-- Información de la empresa centrada -->
        <div class="text-center empresa-info mt-3 p-3" style="border-radius: 5px;">
            <div style="margin-bottom: 10px;">
                <strong><?= htmlspecialchars($nombre_empresa) ?></strong><br>
            </div>

            <div style="margin-bottom: 10px;">
                <strong><?= htmlspecialchars($nit_empresa) ?></strong><br>
            </div>

            <div style="margin-bottom: 5px;">
                <strong>PERIODO:</strong> <?= date('d/m/Y', strtotime($fecha_inicio)) ?> A <?= date('d/m/Y', strtotime($fecha_fin)) ?>
            </div>
        </div>
      </div>

      <!-- Formulario de filtros -->
      <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
          <label class="form-label">Fecha Inicio:</label>
          <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Fecha Fin:</label>
          <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo Documento:</label>
          <select name="tipo_documento" class="form-select">
            <option value="">Todos</option>
            <option value="factura_venta" <?= $tipo_documento == 'factura_venta' ? 'selected' : '' ?>>Factura Venta</option>
            <option value="factura_compra" <?= $tipo_documento == 'factura_compra' ? 'selected' : '' ?>>Factura Compra</option>
            <option value="recibo_caja" <?= $tipo_documento == 'recibo_caja' ? 'selected' : '' ?>>Recibo de Caja</option>
            <option value="comprobante_egreso" <?= $tipo_documento == 'comprobante_egreso' ? 'selected' : '' ?>>Comprobante Egreso</option>
            <option value="comprobante_contable" <?= $tipo_documento == 'comprobante_contable' ? 'selected' : '' ?>>Comprobante Contable</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Cuenta Contable:</label>
          <input type="text" name="codigo_cuenta" class="form-control" value="<?= htmlspecialchars($codigo_cuenta) ?>" placeholder="Ej: 1105">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="submit" class="btn w-100" style="background-color: #103669; color: white;">
            <i class="fa-solid fa-search"></i>
          </button>
        </div>
        <div class="col-md-12 mt-3">
          <button type="button" class="btn-cancelar" onclick="limpiarFiltros()">
            Limpiar Filtros
          </button>
        </div>
      </form>

      <?php if (count($movimientos) > 0): ?>
      <div class="mb-3 text-end">
        <button onclick="window.open('../../exports/pdf/exportar_libro_diario_pdf.php?id=123', '_blank')" class="btn-agregar">
            <i class="fa-solid fa-print"></i> Exportar a PDF
        </button>
        <a href="../../exports/excel/exportar_excel_libro_diario.php?fecha_inicio=<?= htmlspecialchars($fecha_inicio) ?>&fecha_fin=<?= htmlspecialchars($fecha_fin) ?>" class="btn-agregar-excel">
          <i class="fa-solid fa-file-excel"></i> Exportar a Excel
        </a>
      </div>
      <?php endif; ?>

      <div class="table-container">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th style="width: 80px;">Fecha</th>
                <th style="width: 100px;">Tipo Doc.</th>
                <th style="width: 80px;">No. Doc.</th>
                <th style="width: 100px;">Código Cuenta</th>
                <th style="width: 200px;">Nombre Cuenta</th>
                <th style="width: 100px;">Tercero</th>
                <th>Concepto</th>
                <th style="width: 120px;" class="text-end">Débito</th>
                <th style="width: 120px;" class="text-end">Crédito</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($movimientos) > 0): ?>
                <?php foreach ($movimientos as $mov): ?>
                  <tr>
                    <td><?= date('d/m/Y', strtotime($mov['fecha'])) ?></td>
                    <td>
                      <?php
                      $tipo_etiquetas = [
                          'factura_venta' => '<span class="badge bg-success">F. Venta</span>',
                          'factura_compra' => '<span class="badge bg-info">F. Compra</span>',
                          'recibo_caja' => '<span class="badge bg-primary">R. Caja</span>',
                          'comprobante_egreso' => '<span class="badge bg-warning">C. Egreso</span>',
                          'comprobante_contable' => '<span class="badge bg-secondary">C. Contable</span>'
                      ];
                      echo $tipo_etiquetas[$mov['tipo_documento']] ?? $mov['tipo_documento'];
                      ?>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($mov['numero_documento']) ?></td>
                    <td><?= htmlspecialchars($mov['codigo_cuenta']) ?></td>
                    <td><?= htmlspecialchars($mov['nombre_cuenta']) ?></td>
                    <td>
                      <?php
                      if ($mov['tercero_identificacion']) {
                          echo htmlspecialchars($mov['tercero_identificacion']);
                          if ($mov['tercero_nombre']) {
                              echo '<br><small>' . htmlspecialchars($mov['tercero_nombre']) . '</small>';
                          }
                      }
                      ?>
                    </td>
                    <td><?= htmlspecialchars($mov['concepto']) ?></td>
                    <td class="debito">
                      <?php
                      $debito = floatval(str_replace(',', '', $mov['debito']));
                      echo $debito > 0 ? '$' . number_format($debito, 2) : '';
                      ?>
                    </td>
                    <td class="credito">
                      <?php
                      $credito = floatval(str_replace(',', '', $mov['credito']));
                      echo $credito > 0 ? '$' . number_format($credito, 2) : '';
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                  <td colspan="7" class="text-end">TOTALES:</td>
                  <td class="text-end">$<?= number_format($total_debito, 2) ?></td>
                  <td class="text-end">$<?= number_format($total_credito, 2) ?></td>
                </tr>
                <tr class="<?= abs($total_debito - $total_credito) > 0.01 ? 'fila-descuadrado' : 'fila-cuadrado' ?>">
                  <td colspan="7" class="text-end">DIFERENCIA:</td>
                  <td colspan="2" class="text-center">
                    $<?= number_format(abs($total_debito - $total_credito), 2) ?>
                    <?php if (abs($total_debito - $total_credito) < 0.01): ?>
                      <i class="fa-solid fa-circle-check"></i> Cuadrado
                    <?php else: ?>
                      <i class="fa-solid fa-triangle-exclamation"></i> Descuadrado
                    <?php endif; ?>
                  </td>
                </tr>
              <?php else: ?>
                <tr>
                  <td colspan="9" class="no-mov">No hay movimientos registrados en el período seleccionado.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../../assets/vendor/aos/aos.js"></script>
  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="../../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="../../assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="../../assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="../../assets/js/main.js"></script>

  <script>
    // Función para limpiar filtros
    function limpiarFiltros() {
        window.location.href = window.location.pathname;
    }
  </script>

  <?php include $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/assets/asistente/asistente-widget.php'; ?>
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/assets/notificaciones/notificaciones-widget.php'; ?>

</body>
</html>