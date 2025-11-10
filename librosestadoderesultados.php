<?php
// ================== CONEXIÓN ==================
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTROS ==================
$periodo_fiscal = isset($_GET['periodo_fiscal']) ? $_GET['periodo_fiscal'] : date('Y');
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

// ================== FUNCIÓN PARA CALCULAR SALDOS POR CUENTA ==================
function calcularSaldoCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $sql = "SELECT 
                COALESCE(SUM(debito), 0) as total_debito,
                COALESCE(SUM(credito), 0) as total_credito
            FROM libro_diario 
            WHERE codigo_cuenta = :cuenta 
              AND fecha BETWEEN :desde AND :hasta";
    
    $params = [
        ':cuenta' => $codigo_cuenta, 
        ':desde' => $fecha_desde, 
        ':hasta' => $fecha_hasta
    ];
    
    if ($tercero != '') {
        $sql .= " AND tercero_identificacion = :tercero";
        $params[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ================== OBTENER TODAS LAS CUENTAS DE INGRESOS (4), COSTOS (6) Y GASTOS (5) ==================
$sql_cuentas = "SELECT DISTINCT 
                    codigo_cuenta, 
                    nombre_cuenta,
                    SUBSTRING(codigo_cuenta, 1, 1) as clase
                FROM libro_diario 
                WHERE fecha BETWEEN :desde AND :hasta
                  AND SUBSTRING(codigo_cuenta, 1, 1) IN ('4', '5', '6')";

$params_cuentas = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

// Aplicar filtro de cuenta si está seleccionado
if ($cuenta_codigo != '') {
    $sql_cuentas .= " AND codigo_cuenta = :cuenta";
    $params_cuentas[':cuenta'] = $cuenta_codigo;
}

// Aplicar filtro de tercero si está seleccionado
if ($tercero != '') {
    $sql_cuentas .= " AND tercero_identificacion = :tercero";
    $params_cuentas[':tercero'] = $tercero;
}

$sql_cuentas .= " ORDER BY clase, codigo_cuenta";

$stmt = $pdo->prepare($sql_cuentas);
$stmt->execute($params_cuentas);
$todas_cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== SEPARAR Y CALCULAR POR TIPO ==================
$ingresos = [];
$costos = [];
$gastos = [];
$totalIngresos = 0;
$totalCostos = 0;
$totalGastos = 0;

// Crear estructura jerárquica y calcular saldos
$cuentas_procesadas = [];

foreach ($todas_cuentas as $cuenta) {
    $codigo = $cuenta['codigo_cuenta'];
    $clase = $cuenta['clase'];
    
    // Calcular movimientos con filtro de tercero
    $movimientos = calcularSaldoCuenta($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero);
    $debito = floatval($movimientos['total_debito']);
    $credito = floatval($movimientos['total_credito']);
    
    // Calcular saldo según la naturaleza de la cuenta
    $saldo = 0;
    if ($clase == '4') {
        // INGRESOS: naturaleza crédito (crédito - débito)
        $saldo = $credito - $debito;
    } else {
        // COSTOS Y GASTOS: naturaleza débito (débito - crédito)
        $saldo = $debito - $credito;
    }
    
    // Solo incluir cuentas con saldo diferente de cero
    if ($saldo != 0) {
        $item = [
            'codigo' => $codigo,
            'nombre' => $cuenta['nombre_cuenta'],
            'saldo' => $saldo,
            'nivel' => strlen($codigo)
        ];
        
        // Clasificar por tipo
        if ($clase == '4') {
            $ingresos[] = $item;
            $totalIngresos += $saldo;
        } elseif ($clase == '6') {
            $costos[] = $item;
            $totalCostos += $saldo;
        } elseif ($clase == '5') {
            $gastos[] = $item;
            $totalGastos += $saldo;
        }
        
        $cuentas_procesadas[] = $codigo;
    }
}

// ================== AGREGAR AGRUPACIONES SUPERIORES ==================
function agregarAgrupaciones(&$array_cuentas, $cuentas_procesadas) {
    $agrupaciones = [];
    $nombres_grupo = [
        '41' => 'Operacionales',
        '42' => 'Otros ingresos',
        '51' => 'Operacionales de administración',
        '52' => 'Operacionales de ventas',
        '53' => 'Otros gastos',
        '61' => 'Costo de ventas y de prestación de servicios'
    ];
    
    foreach ($array_cuentas as $cuenta) {
        $codigo = $cuenta['codigo'];
        
        // Generar códigos de agrupación
        $niveles = [];
        if (strlen($codigo) >= 2) $niveles[] = substr($codigo, 0, 2);
        if (strlen($codigo) >= 4) $niveles[] = substr($codigo, 0, 4);
        if (strlen($codigo) >= 6) $niveles[] = substr($codigo, 0, 6);
        
        foreach ($niveles as $grupo) {
            if (!in_array($grupo, $cuentas_procesadas)) {
                $nombre = isset($nombres_grupo[$grupo]) ? $nombres_grupo[$grupo] : 'Grupo ' . $grupo;
                if (!isset($agrupaciones[$grupo])) {
                    $agrupaciones[$grupo] = [
                        'codigo' => $grupo,
                        'nombre' => $nombre,
                        'saldo' => 0,
                        'nivel' => strlen($grupo),
                        'es_grupo' => true
                    ];
                    $cuentas_procesadas[] = $grupo;
                }
                $agrupaciones[$grupo]['saldo'] += $cuenta['saldo'];
            }
        }
    }
    
    // Fusionar agrupaciones con cuentas detalle
    $resultado = array_merge(array_values($agrupaciones), $array_cuentas);
    
    // Ordenar por código
    usort($resultado, function($a, $b) {
        return strcmp($a['codigo'], $b['codigo']);
    });
    
    return $resultado;
}

$ingresos = agregarAgrupaciones($ingresos, $cuentas_procesadas);
$costos = agregarAgrupaciones($costos, $cuentas_procesadas);
$gastos = agregarAgrupaciones($gastos, $cuentas_procesadas);

// ================== RESULTADO DEL EJERCICIO ==================
$resultado_ejercicio = $totalIngresos - $totalCostos - $totalGastos;
$utilidad_bruta = $totalIngresos - $totalCostos;
$utilidad_operacional = $utilidad_bruta - $totalGastos;

// ================== LISTA DE CUENTAS PARA EL SELECT ==================
$sql_lista = "SELECT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta 
              FROM libro_diario 
              WHERE SUBSTRING(codigo_cuenta, 1, 1) IN ('4', '5', '6')
              GROUP BY codigo_cuenta 
              ORDER BY codigo_cuenta";
$stmt_lista = $pdo->query($sql_lista);
$lista_cuentas = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

// ================== LISTA DE TERCEROS PARA EL SELECT ==================
$sql_terceros = "SELECT DISTINCT 
                    tercero_identificacion,
                    tercero_nombre
                 FROM libro_diario
                 WHERE tercero_identificacion IS NOT NULL 
                   AND tercero_identificacion != ''
                   AND SUBSTRING(codigo_cuenta, 1, 1) IN ('4', '5', '6')
                 ORDER BY tercero_nombre ASC";
$stmt_terceros = $pdo->query($sql_terceros);
$lista_terceros = $stmt_terceros->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Estado de Resultados - SOFI</title>
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

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

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
    }
    
    .table-container thead {
      background-color: #054a85;
      color: white;
    }
    
    .table-container th {
      padding: 12px;
      text-align: left;
      font-weight: 600;
    }
    
    .table-container td {
      padding: 10px 12px;
      border-bottom: 1px solid #dee2e6;
    }
    
    .table-container tbody tr:hover {
      background-color: #f8f9fa;
    }
    
    .text-end {
      text-align: right !important;
    }
    
    .fw-bold {
      font-weight: bold !important;
    }
    
    /* Estilos por nivel */
    .nivel-2 {
      background-color: #e3f2fd;
      font-weight: bold;
    }
    
    .nivel-4 {
      padding-left: 20px !important;
      font-weight: 600;
    }
    
    .nivel-6 {
      padding-left: 40px !important;
    }
    
    .nivel-8, .nivel-10 {
      padding-left: 60px !important;
      color: #495057;
    }
    
    .total-seccion {
      background-color: #f8f9fa;
      font-weight: bold;
      font-size: 1.05rem;
      border-top: 2px solid #054a85 !important;
    }
    
    .resultado-final {
      background-color: #054a85;
      color: white !important;
      font-weight: bold;
      font-size: 1.2rem;
    }
    
    .resultado-final td {
      color: white !important;
      padding: 15px 12px !important;
    }
    
    .utilidad-intermedia {
      background-color: #e8f4f8;
      font-weight: bold;
      font-style: italic;
      border-top: 1px solid #054a85 !important;
    }
    
    @media print {
      .btn-ir, form, .btn-primary, .btn-secondary, .btn-success { display: none; }
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
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>

  <!-- ======= Estado de Resultados Section ======= -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    
    <div class="container" data-aos="fade-up">
      <div class="section-title">
        <h2><i class="fa-solid fa-file-invoice-dollar"></i> Estado de Resultados</h2>
        <p>Reporte de ingresos, costos y gastos del período</p>
      </div>

      <!-- Formulario de filtros -->
      <form method="get" class="row g-3 mb-4">
        <div class="col-md-2">
          <label>Período Fiscal:</label>
          <input type="number" name="periodo_fiscal" class="form-control" placeholder="Año" value="<?= htmlspecialchars($periodo_fiscal) ?>" min="2000" max="2099">
        </div>
        <div class="col-md-2">
          <label>Desde:</label>
          <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
        </div>
        <div class="col-md-2">
          <label>Hasta:</label>
          <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
        </div>
        <div class="col-md-2">
          <label>Cuenta:</label>
          <select name="cuenta" id="selectCuenta" class="form-select">
            <option value="">-- Todas --</option>
            <?php foreach ($lista_cuentas as $c): ?>
              <option value="<?= htmlspecialchars($c['codigo_cuenta']) ?>" <?= $c['codigo_cuenta']==$cuenta_codigo?'selected':'' ?>>
                <?= htmlspecialchars($c['codigo_cuenta'] . ' - ' . $c['nombre_cuenta']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label>Tercero:</label>
          <select name="tercero" id="selectTercero" class="form-select">
            <option value="">-- Todos --</option>
            <?php foreach ($lista_terceros as $t): ?>
              <option value="<?= htmlspecialchars($t['tercero_identificacion']) ?>" 
                      <?= $t['tercero_identificacion']==$tercero?'selected':'' ?>>
                <?= htmlspecialchars($t['tercero_identificacion'] . '  ' . $t['tercero_nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fa-solid fa-search"></i> Buscar
          </button>
        </div>
      </form>

      <!-- Botones de exportación -->
      <?php if (count($ingresos) > 0 || count($costos) > 0 || count($gastos) > 0): ?>
      <div class="mb-3 text-end">
        <button onclick="exportarPDF()" class="btn btn-secondary">
          <i class="fa-solid fa-file-pdf"></i> Exportar PDF
        </button>
        <button onclick="exportarExcel()" class="btn btn-success">
          <i class="fa-solid fa-file-excel"></i> Exportar Excel
        </button>
      </div>
      <?php endif; ?>

      <!-- INGRESOS -->
      <h3 class="mt-4 mb-3" style="color: #054a85;">
        <i class="fa-solid fa-arrow-trend-up"></i> Ingresos
      </h3>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th style="width: 15%">Código</th>
              <th style="width: 60%">Nombre de la cuenta</th>
              <th style="width: 25%" class="text-end">Saldo</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($ingresos) > 0): ?>
              <?php foreach($ingresos as $fila): ?>
                <tr class="nivel-<?= $fila['nivel'] ?>">
                  <td><?= htmlspecialchars($fila['codigo']) ?></td>
                  <td><?= htmlspecialchars($fila['nombre']) ?></td>
                  <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="total-seccion">
                <td colspan="2">Total Ingresos</td>
                <td class="text-end"><?= number_format($totalIngresos, 2, ',', '.') ?></td>
              </tr>
            <?php else: ?>
              <tr>
                <td colspan="3" class="text-center text-muted">No hay ingresos en el período seleccionado</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- COSTOS DE VENTAS -->
      <h3 class="mt-4 mb-3" style="color: #054a85;">
        <i class="fa-solid fa-box"></i> Costos de Ventas
      </h3>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th style="width: 15%">Código</th>
              <th style="width: 60%">Nombre de la cuenta</th>
              <th style="width: 25%" class="text-end">Saldo</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($costos) > 0): ?>
              <?php foreach($costos as $fila): ?>
                <tr class="nivel-<?= $fila['nivel'] ?>">
                  <td><?= htmlspecialchars($fila['codigo']) ?></td>
                  <td><?= htmlspecialchars($fila['nombre']) ?></td>
                  <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="total-seccion">
                <td colspan="2">Total Costos</td>
                <td class="text-end"><?= number_format($totalCostos, 2, ',', '.') ?></td>
              </tr>
              <tr class="utilidad-intermedia">
                <td colspan="2">Utilidad Bruta (Ingresos - Costos)</td>
                <td class="text-end"><?= number_format($utilidad_bruta, 2, ',', '.') ?></td>
              </tr>
            <?php else: ?>
              <tr>
                <td colspan="3" class="text-center text-muted">No hay costos en el período seleccionado</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- GASTOS -->
      <h3 class="mt-4 mb-3" style="color: #054a85;">
        <i class="fa-solid fa-receipt"></i> Gastos
      </h3>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th style="width: 15%">Código</th>
              <th style="width: 60%">Nombre de la cuenta</th>
              <th style="width: 25%" class="text-end">Saldo</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($gastos) > 0): ?>
              <?php foreach($gastos as $fila): ?>
                <tr class="nivel-<?= $fila['nivel'] ?>">
                  <td><?= htmlspecialchars($fila['codigo']) ?></td>
                  <td><?= htmlspecialchars($fila['nombre']) ?></td>
                  <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="total-seccion">
                <td colspan="2">Total Gastos</td>
                <td class="text-end"><?= number_format($totalGastos, 2, ',', '.') ?></td>
              </tr>
              <tr class="utilidad-intermedia">
                <td colspan="2">Utilidad Operacional (Utilidad Bruta - Gastos)</td>
                <td class="text-end"><?= number_format($utilidad_operacional, 2, ',', '.') ?></td>
              </tr>
            <?php else: ?>
              <tr>
                <td colspan="3" class="text-center text-muted">No hay gastos en el período seleccionado</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- RESULTADO DEL EJERCICIO -->
      <h3 class="mt-4 mb-3" style="color: #054a85;">
        <i class="fa-solid fa-calculator"></i> Resultado del Ejercicio
      </h3>
      <div class="table-container">
        <table>
          <tbody>
            <tr class="resultado-final">
              <td style="width: 75%">
                <?= $resultado_ejercicio >= 0 ? 'UTILIDAD DEL EJERCICIO' : 'PÉRDIDA DEL EJERCICIO' ?>
              </td>
              <td class="text-end" style="width: 25%">
                <?= number_format(abs($resultado_ejercicio), 2, ',', '.') ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
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

  <!-- jQuery y Select2 -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <script src="assets/js/main.js"></script>

  <script>
    $(document).ready(function() {
      $('#selectCuenta').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Todas --',
        allowClear: true,
        width: '100%'
      });

      $('#selectTercero').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Todos --',
        allowClear: true,
        width: '100%'
      });
    });

    function exportarExcel() {
      const periodo_fiscal = document.querySelector('input[name="periodo_fiscal"]').value;
      const cuenta = document.querySelector('select[name="cuenta"]').value;
      const desde = document.querySelector('input[name="desde"]').value;
      const hasta = document.querySelector('input[name="hasta"]').value;
      const tercero = document.querySelector('select[name="tercero"]').value;

      const url = `exportar_estado_resultados_excel.php?periodo_fiscal=${encodeURIComponent(periodo_fiscal)}&cuenta=${encodeURIComponent(cuenta)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&tercero=${encodeURIComponent(tercero)}`;
      window.location.href = url;
    }

    function exportarPDF() {
      const periodo_fiscal = document.querySelector('input[name="periodo_fiscal"]').value;
      const cuenta = document.querySelector('select[name="cuenta"]').value;
      const desde = document.querySelector('input[name="desde"]').value;
      const hasta = document.querySelector('input[name="hasta"]').value;
      const tercero = document.querySelector('select[name="tercero"]').value;

      const url = `exportar_estado_resultados_pdf.php?periodo_fiscal=${encodeURIComponent(periodo_fiscal)}&cuenta=${encodeURIComponent(cuenta)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&tercero=${encodeURIComponent(tercero)}`;
      window.open(url, '_blank');
    }
  </script>
</body>
</html>