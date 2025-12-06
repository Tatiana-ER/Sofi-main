<?php
// ================== CONEXIÓN ==================
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

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
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-12-31');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

// ================== FUNCIÓN PARA EXTRAER SOLO LA IDENTIFICACIÓN (NÚMEROS) ==================
function extraerIdentificacion($identificacion) {
    // Si está vacío, retornar vacío
    if (empty($identificacion)) {
        return '';
    }
    
    // Si contiene guion, extraer solo la parte numérica
    if (strpos($identificacion, '-') !== false) {
        $partes = explode('-', $identificacion, 2);
        return trim($partes[0]);
    }
    
    // Si ya es solo números, retornarlo limpio
    return trim($identificacion);
}

// ================== OBTENER CUENTAS ==================
if ($cuenta_codigo != '') {
    $sql_cuentas = "SELECT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta
                    FROM libro_diario
                    WHERE codigo_cuenta = :cuenta
                    AND fecha BETWEEN :desde AND :hasta";
    
    $params_cuentas = [
        ':cuenta' => $cuenta_codigo,
        ':desde' => $fecha_desde,
        ':hasta' => $fecha_hasta
    ];
    
    if ($tercero != '') {
        $sql_cuentas .= " AND ( 
                           tercero_identificacion = :tercero 
                           OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                         )";
        $params_cuentas[':tercero'] = $tercero;
        $params_cuentas[':tercero_con_guion'] = $tercero . ' -';
    }
    
    $sql_cuentas .= " GROUP BY codigo_cuenta ORDER BY codigo_cuenta";
    $stmt_cuentas = $pdo->prepare($sql_cuentas);
    $stmt_cuentas->execute($params_cuentas);
} else {
    $sql_cuentas = "SELECT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta
                    FROM libro_diario
                    WHERE fecha BETWEEN :desde AND :hasta";
    
    $params_cuentas = [
        ':desde' => $fecha_desde,
        ':hasta' => $fecha_hasta
    ];
    
    if ($tercero != '') {
        $sql_cuentas .= " AND ( 
                           tercero_identificacion = :tercero 
                           OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                         )";
        $params_cuentas[':tercero'] = $tercero;
        $params_cuentas[':tercero_con_guion'] = $tercero . ' -';
    }
    
    $sql_cuentas .= " GROUP BY codigo_cuenta ORDER BY codigo_cuenta";
    $stmt_cuentas = $pdo->prepare($sql_cuentas);
    $stmt_cuentas->execute($params_cuentas);
}
$cuentas = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);

// ================== FUNCIÓN PARA OBTENER MOVIMIENTOS ==================
function obtenerMovimientosCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    // Obtener el nombre correcto de la cuenta
    $sql_nombre = "SELECT nombre_cuenta 
                   FROM libro_diario 
                   WHERE codigo_cuenta = :cuenta 
                   ORDER BY fecha_registro DESC 
                   LIMIT 1";
    $stmt_nombre = $pdo->prepare($sql_nombre);
    $stmt_nombre->execute([':cuenta' => $codigo_cuenta]);
    $nombre_cuenta = $stmt_nombre->fetch(PDO::FETCH_COLUMN);
    
    if (!$nombre_cuenta) {
        if ($codigo_cuenta == '135515') {
            $nombre_cuenta = 'Retención en la Fuente por Cobrar';
        } else {
            $nombre_cuenta = 'Cuenta ' . $codigo_cuenta;
        }
    }

    // Naturaleza por primer dígito
    $naturaleza = substr($codigo_cuenta, 0, 1);

    // Saldo acumulado anterior al periodo
    $sql_saldo = "SELECT 
                    COALESCE(SUM(debito),0) as suma_debito_prev,
                    COALESCE(SUM(credito),0) as suma_credito_prev
                  FROM libro_diario
                  WHERE codigo_cuenta = :cuenta AND fecha < :desde";
    
    $params_saldo = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde];
    
    if ($tercero != '') {
        $sql_saldo .= " AND ( 
                        tercero_identificacion = :tercero 
                        OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                      )";
        $params_saldo[':tercero'] = $tercero;
        $params_saldo[':tercero_con_guion'] = $tercero . ' -';
    }
    
    $stmt_saldo = $pdo->prepare($sql_saldo);
    $stmt_saldo->execute($params_saldo);
    $row = $stmt_saldo->fetch(PDO::FETCH_ASSOC);

    $deb_prev = floatval($row['suma_debito_prev']);
    $cred_prev = floatval($row['suma_credito_prev']);

    // Saldo inicial según naturaleza
    if (in_array($naturaleza, ['1','5','6','7'])) {
        // Activo / Costos / Gastos --> debito - credito
        $saldo_inicial = $deb_prev - $cred_prev;
    } else {
        // Pasivo / Patrimonio / Ingresos --> credito - debito
        $saldo_inicial = $cred_prev - $deb_prev;
    }

    // El saldo inicial no puede ser negativo salvo cuentas IVA (contienen 2408)
    if ($saldo_inicial < 0 && strpos($codigo_cuenta, '2408') === false) {
        $saldo_inicial = 0;
    }

    // Obtener movimientos del período
    $sql_mov = "SELECT * FROM libro_diario
                WHERE codigo_cuenta = :cuenta
                  AND fecha BETWEEN :desde AND :hasta";
    
    $params = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_mov .= " AND ( 
                      tercero_identificacion = :tercero 
                      OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                    )";
        $params[':tercero'] = $tercero;
        $params[':tercero_con_guion'] = $tercero . ' -';
    }
    
    $sql_mov .= " ORDER BY fecha ASC, id ASC";
    $stmt_mov = $pdo->prepare($sql_mov);
    $stmt_mov->execute($params);
    $movimientos = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);

    // Calcular saldos acumulados fila por fila
    $saldo = $saldo_inicial;
    foreach ($movimientos as $k => $m) {
        $debito = floatval($m['debito']);
        $credito = floatval($m['credito']);

        // Guardamos el saldo ANTES del movimiento
        $movimientos[$k]['saldo_inicial_fila'] = $saldo;

        // Aplicamos el movimiento
        if (in_array($naturaleza, ['1','5','6','7'])) {
            $saldo += ($debito - $credito);
        } else {
            $saldo += ($credito - $debito);
        }

        // El saldo no puede mostrarse negativo a menos que sea IVA (2408)
        if ($saldo < 0 && strpos($codigo_cuenta, '2408') === false) {
            $saldo = 0;
        }

        $movimientos[$k]['saldo_final_fila'] = $saldo;
    }

    return [
        'nombre_cuenta_corregido' => $nombre_cuenta,
        'saldo_inicial' => $saldo_inicial,
        'movimientos' => $movimientos
    ];
}

// ================== LISTA DE CUENTAS PARA EL SELECT ==================
$sql_lista = "SELECT DISTINCT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta 
              FROM libro_diario 
              GROUP BY codigo_cuenta 
              ORDER BY codigo_cuenta";
$stmt_lista = $pdo->query($sql_lista);
$lista_cuentas = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

// ================== LISTA DE TERCEROS PARA EL SELECT (UNIFICADA) ==================
$sql_terceros = "SELECT 
                    DISTINCT ld.tercero_identificacion,
                    ld.tercero_nombre
                 FROM libro_diario ld
                 WHERE ld.tercero_identificacion IS NOT NULL 
                   AND ld.tercero_identificacion != ''
                   AND ld.fecha BETWEEN :desde AND :hasta
                 ORDER BY ld.tercero_nombre ASC";

$stmt_terceros = $pdo->prepare($sql_terceros);
$stmt_terceros->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
$terceros_db = $stmt_terceros->fetchAll(PDO::FETCH_ASSOC);

// Procesar y unificar los terceros
$terceros_unificados = [];

foreach ($terceros_db as $t) {
    $identificacion = $t['tercero_identificacion'];
    $identificacion_limpia = extraerIdentificacion($identificacion);
    
    // Buscar el nombre completo
    $nombre_completo = '';
    
    if (!empty($t['tercero_nombre'])) {
        $nombre_completo = $t['tercero_nombre'];
    } else {
        // Si no hay tercero_nombre, extraer del campo tercero_identificacion
        if (strpos($identificacion, '-') !== false) {
            $partes = explode('-', $identificacion, 2);
            if (count($partes) == 2) {
                $nombre_completo = trim($partes[1]);
            }
        }
    }
    
    // Usar la identificación limpia como clave para evitar duplicados
    if (!empty($identificacion_limpia) && !isset($terceros_unificados[$identificacion_limpia])) {
        $terceros_unificados[$identificacion_limpia] = [
            'identificacion_limpia' => $identificacion_limpia,
            'nombre' => $nombre_completo ?: 'Tercero ' . $identificacion_limpia,
            'mostrar' => $identificacion_limpia . ' - ' . ($nombre_completo ?: 'Tercero ' . $identificacion_limpia)
        ];
    }
}

// Convertir a array para usar en el select
$lista_terceros = array_values($terceros_unificados);
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
  
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

  <link href="assets/css/improved-style.css" rel="stylesheet">
  
  <style>
    .empresa-info {
        border-radius: 5px;
        font-size: 0.95rem;
    }

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
    
    .cuenta-card {
      background: white;
      border-left: 5px solid #054a85;
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border-radius: 8px;
      page-break-inside: avoid;
    }
    
    .cuenta-header {
      background: linear-gradient(135deg, #054a85 0%, #4c82b0ff 100%);
      color: white;
      padding: 15px 20px;
      border-radius: 8px 8px 0 0;
      margin: -20px -20px 20px -20px;
    }
    
    .cuenta-header h4 {
      margin: 0;
      font-size: 1.3rem;
    }
    
    .cuenta-header p {
      margin: 5px 0 0 0;
      font-size: 0.95rem;
      opacity: 0.9;
    }
    
    .saldo-inicial-row {
      background-color: #fff3cd;
      font-weight: bold;
    }
    
    .table-container {
      overflow-x: auto;
    }
    
    .table-auxiliar {
      width: 100%;
      border-collapse: collapse;
      background: white;
      font-size: 0.9rem;
    }
    
    .table-auxiliar thead {
      background-color: #054a85;
      color: white;
    }
    
    .table-auxiliar th {
      padding: 10px 8px;
      text-align: left;
      font-weight: 600;
      border: 1px solid #dee2e6;
      font-size: 0.85rem;
    }
    
    .table-auxiliar td {
      padding: 8px;
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
      padding: 3px 6px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 500;
    }
    
    .badge-fv { background-color: #28a745; color: white; }
    .badge-fc { background-color: #17a2b8; color: white; }
    .badge-rc { background-color: #007bff; color: white; }
    .badge-ce { background-color: #ffc107; color: #000; }
    .badge-cc { background-color: #6c757d; color: white; }
    
    .no-movimientos {
      text-align: center;
      padding: 20px;
      color: #6c757d;
      font-style: italic;
    }
    
    .btn-limpiar {
      background-color: #6c757d;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
    }
    
    .btn-limpiar:hover {
      background-color: #5a6268;
      color: white;
    }
    
    @media print {
      .btn-ir, form, .btn-secondary, .btn-success { display: none; }
      .cuenta-card { page-break-inside: avoid; }
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
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

  <!-- ======= Services Section ======= -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">

      <div class="section-title">
          <h2><i class="fa-solid fa-book"></i> Libro Auxiliar</h2>
          
          <!-- Información de la empresa centrada -->
          <div class="text-center empresa-info mt-3 p-3" style="border-radius: 5px;">
              <div style="margin-bottom: 10px;">
                  <strong>NOMBRE DE LA EMPRESA:</strong><br>
                  <?= htmlspecialchars($nombre_empresa) ?>
              </div>
              
              <div style="margin-bottom: 10px;">
                  <strong>NIT DE LA EMPRESA:</strong><br>
                  <?= htmlspecialchars($nit_empresa) ?>
              </div>
              
              <div style="margin-bottom: 5px;">
                  <strong>PERIODO:</strong> <?= date('d/m/Y', strtotime($fecha_desde)) ?> A <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
              </div>
          </div>
      </div>

      <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
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
          <label>Desde:</label>
          <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
        </div>
        <div class="col-md-2">
          <label>Hasta:</label>
          <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
        </div>
        <div class="col-md-3">
          <label>Tercero:</label>
          <select name="tercero" id="selectTercero" class="form-select">
            <option value="">-- Todos --</option>
            <?php foreach ($lista_terceros as $t): ?>
              <option value="<?= htmlspecialchars($t['identificacion_limpia']) ?>" 
                      <?= $t['identificacion_limpia']==$tercero?'selected':'' ?>>
                <?= htmlspecialchars($t['mostrar']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-search"></i> Buscar</button>
        </div>
        <!-- Botón para limpiar filtros -->
        <div class="col-md-12 mt-3">
          <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">
            Limpiar Filtros
          </button>
        </div>
      </form>

      <?php if (count($cuentas) > 0): ?>
      <div class="mb-3 text-end">
        <button onclick="exportarExcel()" class="btn btn-success">
          <i class="fa-solid fa-file-excel"></i> Exportar a Excel
        </button>
        <button onclick="exportarPDF()" class="btn btn-secondary">
          <i class="fa-solid fa-file-pdf"></i> Exportar PDF
        </button>
      </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-bordered table-auxiliar bg-white">
          <thead>
            <tr>
              <th style="width:110px">CÓDIGO CONTABLE</th>
              <th style="width:140px">NOMBRE DE LA CUENTA</th>
              <th style="width:110px">IDENTIFICACIÓN TERCERO</th>
              <th style="width:140px">NOMBRE TERCERO</th>
              <th style="width:100px">FECHA</th>
              <th style="width:140px">COMPROBANTE</th>
              <th>CONCEPTO</th>
              <th style="width:110px" class="text-end">SALDO INICIAL</th>
              <th style="width:110px" class="text-end">DÉBITO</th>
              <th style="width:110px" class="text-end">CRÉDITO</th>
              <th style="width:110px" class="text-end">SALDO FINAL</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (count($cuentas) == 0) {
                echo "<tr><td colspan='11' class='no-mov'>No se encontraron cuentas con movimientos en el período seleccionado.</td></tr>";
            } else {
                foreach ($cuentas as $cuenta) {
                    $datos = obtenerMovimientosCuenta($pdo, $cuenta['codigo_cuenta'], $fecha_desde, $fecha_hasta, $tercero);

                    if (count($datos['movimientos']) > 0) {
                        foreach ($datos['movimientos'] as $mov) {
                            // Formato del comprobante
                            $tipo_comp = '';
                            switch ($mov['tipo_documento']) {
                                case 'factura_venta': $tipo_comp = 'FAC.VTA.No.'; break;
                                case 'factura_compra': $tipo_comp = 'FRA.COMPRA No.'; break;
                                case 'recibo_caja': $tipo_comp = 'REC.CAJA No.'; break;
                                case 'comprobante_egreso': $tipo_comp = 'COMP.EGRES.No.'; break;
                                case 'comprobante_contable': $tipo_comp = 'COMP.CONTAB.No.'; break;
                                default: $tipo_comp = strtoupper($mov['tipo_documento']);
                            }
                            $comprobante = $tipo_comp . ' ' . $mov['numero_documento'];

                            // Separar identificación y nombre si están concatenados
                            $tercero_id = $mov['tercero_identificacion'] ?? '';
                            $tercero_nombre = $mov['tercero_nombre'] ?? '';
                            
                            // Usar la función extraerIdentificacion para limpiar la identificación
                            $identificacion_limpia = extraerIdentificacion($tercero_id);
                            
                            // Si el nombre está vacío, intentar extraerlo del campo identificacion
                            if (empty($tercero_nombre) && strpos($tercero_id, '-') !== false) {
                                $partes = explode('-', $tercero_id, 2);
                                if (count($partes) == 2) {
                                    $tercero_nombre = trim($partes[1]);
                                }
                            }

                            echo "<tr>
                                    <td>" . htmlspecialchars($cuenta['codigo_cuenta']) . "</td>
                                    <td>" . htmlspecialchars($datos['nombre_cuenta_corregido']) . "</td>
                                    <td>" . htmlspecialchars($identificacion_limpia) . "</td>
                                    <td>" . htmlspecialchars($tercero_nombre) . "</td>
                                    <td>" . date('d/m/Y', strtotime($mov['fecha'])) . "</td>
                                    <td>" . htmlspecialchars($comprobante) . "</td>
                                    <td>" . htmlspecialchars($mov['concepto']) . "</td>
                                    <td class='text-end'>" . number_format($mov['saldo_inicial_fila'], 0, ',', '.') . "</td>
                                    <td class='text-end'>" . ($mov['debito'] > 0 ? number_format($mov['debito'], 0, ',', '.') : '') . "</td>
                                    <td class='text-end'>" . ($mov['credito'] > 0 ? number_format($mov['credito'], 0, ',', '.') : '') . "</td>
                                    <td class='text-end'>" . number_format($mov['saldo_final_fila'], 0, ',', '.') . "</td>
                                  </tr>";
                        }
                    }
                }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </section><!-- End Services Section -->

  <script>
  function exportarExcel() {
      const cuenta = document.querySelector('select[name="cuenta"]').value;
      const desde = document.querySelector('input[name="desde"]').value;
      const hasta = document.querySelector('input[name="hasta"]').value;
      const tercero = document.querySelector('select[name="tercero"]').value;

      const url = `exportar_libro_auxiliar.php?cuenta=${encodeURIComponent(cuenta)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&tercero=${encodeURIComponent(tercero)}`;
      
      window.location.href = url;
  }

  function exportarPDF() {
      const cuenta = document.querySelector('select[name="cuenta"]').value;
      const desde = document.querySelector('input[name="desde"]').value;
      const hasta = document.querySelector('input[name="hasta"]').value;
      const tercero = document.querySelector('select[name="tercero"]').value;

      const url = `exportar_libro_auxiliar_pdf.php?cuenta=${encodeURIComponent(cuenta)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&tercero=${encodeURIComponent(tercero)}`;
      
      window.location.href = url;
  }
  </script>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer><!-- End Footer -->

  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- jQuery y Select2 JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  <!-- Vendor JS Files -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
  
  <script>
    $(document).ready(function() {
      // Inicializar Select2 en el select de cuentas
      $('#selectCuenta').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Todas --',
        allowClear: true,
        width: '100%'
      });

      // Inicializar Select2 en el select de terceros
      $('#selectTercero').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Todos --',
        allowClear: true,
        width: '100%'
      });
    });

    // Función para limpiar filtros
    function limpiarFiltros() {
        // Redirigir a la misma página sin parámetros
        window.location.href = window.location.pathname;
    }
  </script>
</body>
</html>