<?php
// ================== CONEXIÓN ==================
require_once '../../config/database.php';

$pdo = Database::getConnection();

// ================== DATOS DEL PERFIL (para el encabezado del reporte) ==================
$sql_perfil = "SELECT persona, nombres, apellidos, razon, cedula, digito FROM perfil LIMIT 1";
$stmt_perfil = $pdo->query($sql_perfil);
$perfil = $stmt_perfil->fetch(PDO::FETCH_ASSOC);

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
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cliente_identificacion = isset($_GET['cliente']) ? $_GET['cliente'] : '';

// ================== LISTA DE CLIENTES PARA EL FILTRO ==================
$sql_clientes = "SELECT DISTINCT identificacion, nombre FROM facturav ORDER BY nombre";
$stmt_clientes = $pdo->query($sql_clientes);
$lista_clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// ================== CONSULTA PRINCIPAL: FACTURAS DEL PERIODO ==================
$sql = "SELECT * FROM facturav WHERE fecha BETWEEN :desde AND :hasta";
$params = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($cliente_identificacion != '') {
    $sql .= " AND identificacion = :cliente";
    $params[':cliente'] = $cliente_identificacion;
}

$sql .= " ORDER BY fecha ASC, consecutivo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== BASE GRAVADA / BASE EXENTA POR FACTURA ==================
// Base gravada: suma de las líneas del detalle que SÍ tienen IVA (iva > 0)
// Base exenta: suma de las líneas del detalle que NO tienen IVA (iva = 0)
$stmtBases = $pdo->prepare(
    "SELECT 
        COALESCE(SUM(CASE WHEN iva > 0 THEN (precio_unitario * cantidad) ELSE 0 END), 0) as base_gravada,
        COALESCE(SUM(CASE WHEN iva = 0 THEN (precio_unitario * cantidad) ELSE 0 END), 0) as base_exenta
     FROM factura_detalle
     WHERE id_factura = :id_factura"
);

// ================== TOTALES DEL PERIODO ==================
$totalBaseGravada = 0;
$totalBaseExenta = 0;
$totalIva = 0;
$totalGeneral = 0;

// Se calcula en el mismo bucle en que se pintan las filas más abajo,
// para no consultar factura_detalle dos veces por factura.
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Libro de Ventas - SOFI</title>
  <link href="../../assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Raleway:300,400,500,600,700|Poppins:300,400,500,600,700" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
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
    .btn-ir:hover { background-color: #4c82b0ff; }
    .balance-container {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .table-balance {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }
    .table-balance thead {
      background-color: #054a85;
      color: white;
    }
    .table-balance th {
      padding: 10px 8px;
      text-align: left;
      font-weight: 600;
      border: 1px solid #dee2e6;
      white-space: nowrap;
    }
    .table-balance td {
      padding: 8px;
      border: 1px solid #dee2e6;
      vertical-align: middle;
    }
    .table-balance tbody tr:hover { background-color: #f8f9fa; }
    .text-end { text-align: right !important; }
    .total-general {
      background-color: #054a85 !important;
      color: white !important;
      font-weight: bold;
      font-size: 1rem;
    }
    @media print {
      .btn-ir, form, .btn-primary, .btn-success, .btn-secondary, .btn-limpiar { display: none; }
    }
  </style>
</head>
<body>
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

  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='../menus/menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">
      <div class="section-title">
        <h2><i class="fa-solid fa-file-invoice-dollar"></i> Libro de Ventas</h2>
        <p>Detalle de todas las facturas de venta registradas en el período</p>

        <div class="text-center empresa-info mt-3 p-3" style="border-radius: 5px;">
          <div style="margin-bottom: 10px;"><strong><?= htmlspecialchars($nombre_empresa) ?></strong></div>
          <div style="margin-bottom: 10px;"><strong><?= htmlspecialchars($nit_empresa) ?></strong></div>
          <div style="margin-bottom: 5px;">
            <strong>PERIODO:</strong> <?= date('d/m/Y', strtotime($fecha_desde)) ?> A <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
          </div>
        </div>
      </div>

      <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
          <label>Desde:</label>
          <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
        </div>
        <div class="col-md-3">
          <label>Hasta:</label>
          <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
        </div>
        <div class="col-md-4">
          <label>Cliente:</label>
          <select name="cliente" class="form-select">
            <option value="">-- Todos --</option>
            <?php foreach ($lista_clientes as $c): ?>
              <option value="<?= htmlspecialchars($c['identificacion']) ?>" <?= $c['identificacion'] == $cliente_identificacion ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['identificacion']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fa-solid fa-search"></i> Buscar
          </button>
        </div>
        <div class="col-md-12 mt-2">
          <button type="button" class="btn-limpiar" onclick="window.location.href = window.location.pathname">Limpiar Filtros</button>
        </div>
      </form>

      <?php if (count($facturas) > 0): ?>
      <div class="mb-3 text-end">
        <button onclick="exportarPDF()" class="btn btn-secondary">
          <i class="fa-solid fa-file-pdf"></i> Exportar PDF
        </button>
        <button onclick="exportarExcel()" class="btn btn-success">
          <i class="fa-solid fa-file-excel"></i> Exportar Excel
        </button>
      </div>
      <?php endif; ?>

      <div class="balance-container">
        <div class="table-responsive">
          <table class="table-balance">
            <thead>
              <tr>
                <th>Comprobante</th>
                <th>Fecha de Elaboración</th>
                <th>Identificación del Tercero</th>
                <th>Nombre del Tercero</th>
                <th class="text-end">Base Gravada</th>
                <th class="text-end">Base Exenta</th>
                <th class="text-end">IVA</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($facturas) > 0): ?>
                <?php foreach ($facturas as $factura): ?>
                  <?php
                    $stmtBases->execute([':id_factura' => $factura['id']]);
                    $bases = $stmtBases->fetch(PDO::FETCH_ASSOC);
                    $baseGravada = floatval($bases['base_gravada']);
                    $baseExenta = floatval($bases['base_exenta']);

                    $totalBaseGravada += $baseGravada;
                    $totalBaseExenta += $baseExenta;
                    $totalIva += floatval($factura['ivaTotal']);
                    $totalGeneral += floatval($factura['valorTotal']);

                    $comprobante = 'FV-' . (!empty($factura['numero_factura']) ? $factura['numero_factura'] : $factura['consecutivo']);
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($comprobante) ?></td>
                    <td><?= date('d/m/Y', strtotime($factura['fecha'])) ?></td>
                    <td><?= htmlspecialchars($factura['identificacion']) ?></td>
                    <td><?= htmlspecialchars($factura['nombre']) ?></td>
                    <td class="text-end">$<?= number_format($baseGravada, 2, ',', '.') ?></td>
                    <td class="text-end">$<?= number_format($baseExenta, 2, ',', '.') ?></td>
                    <td class="text-end">$<?= number_format($factura['ivaTotal'], 2, ',', '.') ?></td>
                    <td class="text-end"><strong>$<?= number_format($factura['valorTotal'], 2, ',', '.') ?></strong></td>
                  </tr>
                <?php endforeach; ?>
                <tr class="total-general">
                  <td colspan="4">TOTALES (<?= count($facturas) ?> factura<?= count($facturas) != 1 ? 's' : '' ?>)</td>
                  <td class="text-end">$<?= number_format($totalBaseGravada, 2, ',', '.') ?></td>
                  <td class="text-end">$<?= number_format($totalBaseExenta, 2, ',', '.') ?></td>
                  <td class="text-end">$<?= number_format($totalIva, 2, ',', '.') ?></td>
                  <td class="text-end">$<?= number_format($totalGeneral, 2, ',', '.') ?></td>
                </tr>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-3">No hay facturas de venta en el período seleccionado</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>

  <script src="../../assets/vendor/aos/aos.js"></script>
  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    AOS.init();

    function exportarExcel() {
      const params = new URLSearchParams({
        desde: document.querySelector('input[name="desde"]').value,
        hasta: document.querySelector('input[name="hasta"]').value,
        cliente: document.querySelector('select[name="cliente"]').value
      });
      window.location.href = `exportar_libroventas_excel.php?${params}`;
    }

    function exportarPDF() {
      const params = new URLSearchParams({
        desde: document.querySelector('input[name="desde"]').value,
        hasta: document.querySelector('input[name="hasta"]').value,
        cliente: document.querySelector('select[name="cliente"]').value
      });
      window.open(`exportar_libroventas_pdf.php?${params}`, '_blank');
    }
  </script>
</body>
</html>