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
// Importante: el checkbox solo envía valor cuando está marcado
$mostrar_saldo_inicial = isset($_GET['mostrar_saldo_inicial']) && $_GET['mostrar_saldo_inicial'] == '1' ? '1' : '0';

// ================== FUNCIÓN PARA CALCULAR MOVIMIENTOS DE UNA CUENTA ==================
function calcularMovimientos($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $naturaleza = substr($codigo_cuenta, 0, 1);
    
    // Saldo inicial (antes del periodo)
    $sql_inicial = "SELECT 
                        COALESCE(SUM(debito), 0) as suma_debito,
                        COALESCE(SUM(credito), 0) as suma_credito
                    FROM libro_diario 
                    WHERE codigo_cuenta = :cuenta 
                      AND fecha < :desde";
    
    $params_ini = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde];
    
    if ($tercero != '') {
        $sql_inicial .= " AND tercero_identificacion = :tercero";
        $params_ini[':tercero'] = $tercero;
    }
    
    $stmt_ini = $pdo->prepare($sql_inicial);
    $stmt_ini->execute($params_ini);
    $ini = $stmt_ini->fetch(PDO::FETCH_ASSOC);
    
    $deb_ini = floatval($ini['suma_debito']);
    $cred_ini = floatval($ini['suma_credito']);
    
    // Calcular saldo inicial según naturaleza
    if (in_array($naturaleza, ['1','5','6','7'])) {
        $saldo_inicial = $deb_ini - $cred_ini;
    } else {
        $saldo_inicial = $cred_ini - $deb_ini;
    }
    
    // Movimientos del periodo
    $sql_mov = "SELECT 
                    COALESCE(SUM(debito), 0) as mov_debito,
                    COALESCE(SUM(credito), 0) as mov_credito
                FROM libro_diario 
                WHERE codigo_cuenta = :cuenta 
                  AND fecha BETWEEN :desde AND :hasta";
    
    $params_mov = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_mov .= " AND tercero_identificacion = :tercero";
        $params_mov[':tercero'] = $tercero;
    }
    
    $stmt_mov = $pdo->prepare($sql_mov);
    $stmt_mov->execute($params_mov);
    $mov = $stmt_mov->fetch(PDO::FETCH_ASSOC);
    
    $mov_debito = floatval($mov['mov_debito']);
    $mov_credito = floatval($mov['mov_credito']);
    
    // Calcular saldo final
    if (in_array($naturaleza, ['1','5','6','7'])) {
        $saldo_final = $saldo_inicial + $mov_debito - $mov_credito;
    } else {
        $saldo_final = $saldo_inicial + $mov_credito - $mov_debito;
    }
    
    return [
        'saldo_inicial' => $saldo_inicial,
        'debito' => $mov_debito,
        'credito' => $mov_credito,
        'saldo_final' => $saldo_final
    ];
}

// ================== OBTENER CUENTAS Y CONSTRUIR JERARQUÍA ==================
$sql_cuentas = "SELECT DISTINCT codigo_cuenta, nombre_cuenta 
                FROM libro_diario 
                WHERE fecha BETWEEN :desde AND :hasta";

$params = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($cuenta_codigo != '') {
    $sql_cuentas .= " AND codigo_cuenta = :cuenta";
    $params[':cuenta'] = $cuenta_codigo;
}

if ($tercero != '') {
    $sql_cuentas .= " AND tercero_identificacion = :tercero";
    $params[':tercero'] = $tercero;
}

$sql_cuentas .= " ORDER BY codigo_cuenta";

$stmt = $pdo->prepare($sql_cuentas);
$stmt->execute($params);
$cuentas_detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Array para almacenar todas las cuentas (detalle + agrupaciones)
$cuentas_completas = [];
$codigos_procesados = [];

// Nombres de agrupación por código
$nombres_agrupacion = [
    '1' => 'Activo',
    '2' => 'Pasivo',
    '3' => 'Patrimonio',
    '4' => 'Ingresos',
    '5' => 'Gastos',
    '6' => 'Costos de ventas',
    '11' => 'Efectivo y equivalentes de efectivo',
    '13' => 'Deudores comerciales y otras cuentas por cobrar',
    '14' => 'Inventarios',
    '15' => 'Propiedad planta y equipo',
    '17' => 'Otros activos no financieros',
    '22' => 'Proveedores',
    '23' => 'Acreedores comerciales y otras cuentas por pagar',
    '24' => 'Impuestos, gravámenes y tasas',
    '25' => 'Beneficios a empleados',
    '28' => 'Pasivos no financieros',
    '31' => 'Capital social',
    '36' => 'Resultado del ejercicio',
    '41' => 'Ingresos de actividades ordinarias',
    '42' => 'Otros ingresos de actividades ordinarias',
    '51' => 'Administrativos',
    '52' => 'Ventas',
    '53' => 'Otros gastos de actividades ordinarias',
    '61' => 'Costo de ventas y de prestación de servicios'
];

// Procesar cada cuenta detalle
foreach ($cuentas_detalle as $cuenta) {
    $codigo = $cuenta['codigo_cuenta'];
    $nombre = $cuenta['nombre_cuenta'];
    
    // Agregar cuenta auxiliar (completa)
    if (!in_array($codigo, $codigos_procesados)) {
        $movs = calcularMovimientos($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero);
        $cuentas_completas[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'saldo_inicial' => $movs['saldo_inicial'],
            'debito' => $movs['debito'],
            'credito' => $movs['credito'],
            'saldo_final' => $movs['saldo_final'],
            'nivel' => strlen($codigo)
        ];
        $codigos_procesados[] = $codigo;
    }
    
    // Generar agrupaciones superiores
    $niveles = [
        substr($codigo, 0, 6),
        substr($codigo, 0, 4),
        substr($codigo, 0, 2),
        substr($codigo, 0, 1)
    ];
    
    foreach ($niveles as $nivel_codigo) {
        if ($nivel_codigo != $codigo && !in_array($nivel_codigo, $codigos_procesados)) {
            $nombre_nivel = isset($nombres_agrupacion[$nivel_codigo]) ? 
                          $nombres_agrupacion[$nivel_codigo] : 
                          'Cuenta ' . $nivel_codigo;
            
            $cuentas_completas[] = [
                'codigo' => $nivel_codigo,
                'nombre' => $nombre_nivel,
                'saldo_inicial' => 0,
                'debito' => 0,
                'credito' => 0,
                'saldo_final' => 0,
                'nivel' => strlen($nivel_codigo),
                'es_agrupacion' => true
            ];
            $codigos_procesados[] = $nivel_codigo;
        }
    }
}

// Ordenar por código
usort($cuentas_completas, function($a, $b) {
    return strcmp($a['codigo'], $b['codigo']);
});

// Calcular sumas para agrupaciones
$totales_por_codigo = [];

foreach ($cuentas_completas as &$cuenta) {
    if (!isset($cuenta['es_agrupacion'])) {
        $totales_por_codigo[$cuenta['codigo']] = [
            'saldo_inicial' => $cuenta['saldo_inicial'],
            'debito' => $cuenta['debito'],
            'credito' => $cuenta['credito'],
            'saldo_final' => $cuenta['saldo_final']
        ];
        
        // Sumar a niveles superiores
        $codigo = $cuenta['codigo'];
        $niveles_superiores = [
            substr($codigo, 0, 6),
            substr($codigo, 0, 4),
            substr($codigo, 0, 2),
            substr($codigo, 0, 1)
        ];
        
        foreach ($niveles_superiores as $sup) {
            if ($sup != $codigo) {
                if (!isset($totales_por_codigo[$sup])) {
                    $totales_por_codigo[$sup] = [
                        'saldo_inicial' => 0,
                        'debito' => 0,
                        'credito' => 0,
                        'saldo_final' => 0
                    ];
                }
                $totales_por_codigo[$sup]['saldo_inicial'] += $cuenta['saldo_inicial'];
                $totales_por_codigo[$sup]['debito'] += $cuenta['debito'];
                $totales_por_codigo[$sup]['credito'] += $cuenta['credito'];
                $totales_por_codigo[$sup]['saldo_final'] += $cuenta['saldo_final'];
            }
        }
    }
}

// Asignar totales calculados a las agrupaciones
foreach ($cuentas_completas as &$cuenta) {
    if (isset($cuenta['es_agrupacion']) && isset($totales_por_codigo[$cuenta['codigo']])) {
        $cuenta['saldo_inicial'] = $totales_por_codigo[$cuenta['codigo']]['saldo_inicial'];
        $cuenta['debito'] = $totales_por_codigo[$cuenta['codigo']]['debito'];
        $cuenta['credito'] = $totales_por_codigo[$cuenta['codigo']]['credito'];
        $cuenta['saldo_final'] = $totales_por_codigo[$cuenta['codigo']]['saldo_final'];
    }
}

// Calcular totales generales
$total_saldo_inicial = 0;
$total_debito = 0;
$total_credito = 0;
$total_saldo_final = 0;

foreach ($cuentas_completas as $cuenta) {
    if ($cuenta['nivel'] == 1) {
        $total_saldo_inicial += $cuenta['saldo_inicial'];
        $total_debito += $cuenta['debito'];
        $total_credito += $cuenta['credito'];
        $total_saldo_final += $cuenta['saldo_final'];
    }
}

// ================== LISTA DE CUENTAS PARA EL SELECT ==================
$sql_lista = "SELECT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta 
              FROM libro_diario 
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
                 ORDER BY tercero_nombre ASC";
$stmt_terceros = $pdo->query($sql_terceros);
$lista_terceros = $stmt_terceros->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Balance de Prueba - SOFI</title>
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
    
    .balance-container {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .table-balance {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }
    
    .table-balance thead {
      background-color: #054a85;
      color: white;
    }
    
    .table-balance th {
      padding: 12px 8px;
      text-align: left;
      font-weight: 600;
      border: 1px solid #dee2e6;
    }
    
    .table-balance td {
      padding: 8px;
      border: 1px solid #dee2e6;
    }
    
    .table-balance tbody tr:hover {
      background-color: #f8f9fa;
    }
    
    .text-end {
      text-align: right !important;
    }
    
    /* Estilos según nivel de cuenta */
    .nivel-1 {
      background-color: #e3f2fd;
      font-weight: bold;
      font-size: 1.05rem;
    }
    
    .nivel-2 {
      background-color: #f1f8ff;
      font-weight: bold;
      padding-left: 15px !important;
    }
    
    .nivel-4 {
      padding-left: 30px !important;
      font-weight: 600;
    }
    
    .nivel-6 {
      padding-left: 45px !important;
    }
    
    .nivel-8, .nivel-10 {
      padding-left: 60px !important;
      color: #495057;
    }
    
    .total-general {
      background-color: #054a85 !important;
      color: white !important;
      font-weight: bold;
      font-size: 1.1rem;
    }
    
    .form-check {
      padding-top: 8px;
    }
    
    @media print {
      .btn-ir, form, .btn-primary, .btn-success { display: none; }
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
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>

  <!-- ======= Balance Section ======= -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">

      <div class="section-title">
        <h2><i class="fa-solid fa-balance-scale"></i> Balance de Prueba General</h2>
        <p>Reporte consolidado de movimientos contables</p>
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
                <?= htmlspecialchars($t['tercero_identificacion'] . ' - ' . $t['tercero_nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fa-solid fa-search"></i> Buscar
          </button>
        </div>
        <div class="col-md-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="mostrar_saldo_inicial" value="1" id="checkSaldoInicial" 
                   <?= $mostrar_saldo_inicial=='1'?'checked':'' ?>>
            <label class="form-check-label" for="checkSaldoInicial">
              Mostrar columna de Saldo Inicial
            </label>
          </div>
        </div>
      </form>

      <?php if (count($cuentas_completas) > 0): ?>
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
                <th style="width: <?= $mostrar_saldo_inicial=='1'?'15%':'20%' ?>">Código cuenta contable</th>
                <th style="width: <?= $mostrar_saldo_inicial=='1'?'35%':'40%' ?>">Nombre cuenta contable</th>
                <?php if ($mostrar_saldo_inicial == '1'): ?>
                <th style="width: 12.5%" class="text-end">Saldo inicial</th>
                <?php endif; ?>
                <th style="width: <?= $mostrar_saldo_inicial=='1'?'12.5%':'13.33%' ?>" class="text-end">Movimiento débito</th>
                <th style="width: <?= $mostrar_saldo_inicial=='1'?'12.5%':'13.33%' ?>" class="text-end">Movimiento crédito</th>
                <th style="width: <?= $mostrar_saldo_inicial=='1'?'12.5%':'13.34%' ?>" class="text-end">Saldo final</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($cuentas_completas) > 0): ?>
                <?php foreach ($cuentas_completas as $cuenta): ?>
                  <tr class="nivel-<?= $cuenta['nivel'] ?>">
                    <td><?= htmlspecialchars($cuenta['codigo']) ?></td>
                    <td><?= htmlspecialchars($cuenta['nombre']) ?></td>
                    <?php if ($mostrar_saldo_inicial == '1'): ?>
                    <td class="text-end"><?= number_format($cuenta['saldo_inicial'], 2, ',', '.') ?></td>
                    <?php endif; ?>
                    <td class="text-end"><?= number_format($cuenta['debito'], 2, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($cuenta['credito'], 2, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($cuenta['saldo_final'], 2, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr class="total-general">
                  <td colspan="2">TOTALES</td>
                  <?php if ($mostrar_saldo_inicial == '1'): ?>
                  <td class="text-end"><?= number_format($total_saldo_inicial, 2, ',', '.') ?></td>
                  <?php endif; ?>
                  <td class="text-end"><?= number_format($total_debito, 2, ',', '.') ?></td>
                  <td class="text-end"><?= number_format($total_credito, 2, ',', '.') ?></td>
                  <td class="text-end"><?= number_format($total_saldo_final, 2, ',', '.') ?></td>
                </tr>
              <?php else: ?>
                <tr>
                  <td colspan="<?= $mostrar_saldo_inicial=='1'?'6':'5' ?>" class="text-center text-muted">No hay datos en el período seleccionado</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
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
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

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
      const mostrar_saldo = document.querySelector('#checkSaldoInicial').checked ? '1' : '0';

      const url = `exportar_balance_excel.php?periodo_fiscal=${encodeURIComponent(periodo_fiscal)}&cuenta=${encodeURIComponent(cuenta)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&tercero=${encodeURIComponent(tercero)}&mostrar_saldo_inicial=${mostrar_saldo}`;
      window.location.href = url;
    }

    function exportarPDF() {
      const periodo_fiscal = document.querySelector('input[name="periodo_fiscal"]').value;
      const cuenta = document.querySelector('select[name="cuenta"]').value;
      const desde = document.querySelector('input[name="desde"]').value;
      const hasta = document.querySelector('input[name="hasta"]').value;
      const tercero = document.querySelector('select[name="tercero"]').value;
      const mostrar_saldo = document.querySelector('#checkSaldoInicial').checked ? '1' : '0';

      const url = `exportar_balance__prueba_pdf.php?periodo_fiscal=${encodeURIComponent(periodo_fiscal)}&cuenta=${encodeURIComponent(cuenta)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&tercero=${encodeURIComponent(tercero)}&mostrar_saldo_inicial=${mostrar_saldo}`;
      window.open(url, '_blank');
    }
  </script>
</body>
</html>