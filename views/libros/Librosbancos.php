<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/auth_check.php'; ?>
<?php
// ================== CONEXIÓN ==================
require_once '../../config/database.php';

$pdo = Database::getConnection();

// ================== DATOS DEL PERFIL ==================
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

// ================== CUENTAS BANCARIAS DISPONIBLES (1110xx) ==================
$sql_cuentas_banco = "SELECT DISTINCT codigo_cuenta, nombre_cuenta 
                      FROM libro_diario 
                      WHERE codigo_cuenta LIKE '1110%' 
                      ORDER BY codigo_cuenta";
$stmt_cuentas_banco = $pdo->query($sql_cuentas_banco);
$cuentas_banco = $stmt_cuentas_banco->fetchAll(PDO::FETCH_ASSOC);

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

// "todas" es una opción válida: si el usuario no ha elegido nada todavía,
// se preselecciona "todas" por defecto (antes se forzaba la primera cuenta).
$cuenta_banco = isset($_GET['cuenta']) ? $_GET['cuenta'] : 'todas';

$nombre_cuenta_banco = '';
foreach ($cuentas_banco as $c) {
    if ($c['codigo_cuenta'] == $cuenta_banco) {
        $nombre_cuenta_banco = $c['nombre_cuenta'];
        break;
    }
}

// Lista de cuentas a consultar: una sola (si se eligió una específica) o todas las de bancos
$cuentasAConsultar = [];
if ($cuenta_banco !== 'todas' && $cuenta_banco !== '') {
    $cuentasAConsultar = [$cuenta_banco];
} else {
    foreach ($cuentas_banco as $c) {
        $cuentasAConsultar[] = $c['codigo_cuenta'];
    }
}

// ================== LISTA DE TERCEROS QUE HAN TENIDO MOVIMIENTO EN CAJA ==================
$sql_terceros = "SELECT DISTINCT tercero_identificacion, tercero_nombre 
                  FROM libro_diario 
                  WHERE codigo_cuenta LIKE '1110%' 
                    AND tercero_identificacion IS NOT NULL 
                    AND tercero_identificacion != ''
                  ORDER BY tercero_nombre";
$stmt_terceros = $pdo->query($sql_terceros);
$lista_terceros = $stmt_terceros->fetchAll(PDO::FETCH_ASSOC);

// ================== SALDO INICIAL POR CUENTA (todo lo acumulado antes de la fecha desde) ==================
// Se guarda un saldo corriente INDEPENDIENTE por cada cuenta bancaria, para no mezclar
// el dinero de una cuenta con el de otra cuando se consultan "todas" a la vez.
$saldoPorCuenta = [];

foreach ($cuentasAConsultar as $codigoCuenta) {
    $sql_saldo_inicial = "SELECT 
                            COALESCE(SUM(debito), 0) as total_debito,
                            COALESCE(SUM(credito), 0) as total_credito
                          FROM libro_diario
                          WHERE codigo_cuenta = :cuenta
                            AND fecha < :desde";
    $params_si = [':cuenta' => $codigoCuenta, ':desde' => $fecha_desde];

    if ($tercero != '') {
        $sql_saldo_inicial .= " AND tercero_identificacion = :tercero";
        $params_si[':tercero'] = $tercero;
    }

    $stmt_si = $pdo->prepare($sql_saldo_inicial);
    $stmt_si->execute($params_si);
    $mov_inicial = $stmt_si->fetch(PDO::FETCH_ASSOC);

    // Bancos es cuenta de activo (naturaleza débito)
    $saldoPorCuenta[$codigoCuenta] = floatval($mov_inicial['total_debito']) - floatval($mov_inicial['total_credito']);
}

$saldoInicialPeriodo = array_sum($saldoPorCuenta);

// ================== MOVIMIENTOS DEL PERIODO (de una cuenta o de todas) ==================
$movimientos = [];

if (count($cuentasAConsultar) > 0) {
    $placeholders = [];
    $params_mov = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

    foreach ($cuentasAConsultar as $indice => $codigoCuenta) {
        $clave = ':cta' . $indice;
        $placeholders[] = $clave;
        $params_mov[$clave] = $codigoCuenta;
    }

    $sql_mov = "SELECT * FROM libro_diario
                WHERE codigo_cuenta IN (" . implode(',', $placeholders) . ")
                  AND fecha BETWEEN :desde AND :hasta";

    if ($tercero != '') {
        $sql_mov .= " AND tercero_identificacion = :tercero";
        $params_mov[':tercero'] = $tercero;
    }

    $sql_mov .= " ORDER BY fecha ASC, id ASC";

    $stmt_mov = $pdo->prepare($sql_mov);
    $stmt_mov->execute($params_mov);
    $movimientos = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);
}

// ================== FORMATEAR NOMBRE DE COMPROBANTE ==================
function formatearComprobante($tipo_documento, $numero_documento) {
    $etiquetas = [
        'factura_venta' => 'Factura Venta',
        'factura_compra' => 'Factura Compra',
        'recibo_caja' => 'Recibo de Caja',
        'comprobante_egreso' => 'Comprobante Egreso',
        'comprobante_contable' => 'Comprobante Contable',
        'cierre_contable' => 'Cierre Contable'
    ];
    $etiqueta = isset($etiquetas[$tipo_documento]) ? $etiquetas[$tipo_documento] : ucfirst(str_replace('_', ' ', $tipo_documento));
    return trim($etiqueta . ' ' . $numero_documento);
}

// ================== CALCULAR SALDO CORRIENTE FILA POR FILA Y TOTALES ==================
$totalDebito = 0;
$totalCredito = 0;
$filasReporte = [];

foreach ($movimientos as $mov) {
    $codigoCuentaFila = $mov['codigo_cuenta'];
    $debito = floatval($mov['debito']);
    $credito = floatval($mov['credito']);

    // Cada cuenta lleva su propio saldo corriente, independiente de las demás
    $saldoInicialFila = $saldoPorCuenta[$codigoCuentaFila];
    $saldoPorCuenta[$codigoCuentaFila] += ($debito - $credito);
    $saldoFinalFila = $saldoPorCuenta[$codigoCuentaFila];

    $totalDebito += $debito;
    $totalCredito += $credito;

    $filasReporte[] = [
        'cuenta' => $codigoCuentaFila . ' - ' . $mov['nombre_cuenta'],
        'comprobante' => formatearComprobante($mov['tipo_documento'], $mov['numero_documento']),
        'fecha' => $mov['fecha'],
        'tercero_identificacion' => $mov['tercero_identificacion'],
        'tercero_nombre' => $mov['tercero_nombre'],
        'saldo_inicial' => $saldoInicialFila,
        'debito' => $debito,
        'credito' => $credito,
        'saldo_final' => $saldoFinalFila
    ];
}

$saldoFinalPeriodo = array_sum($saldoPorCuenta);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Libro de Cuentas de Banco - SOFI</title>
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
    .cuenta-actual {
      background-color: #eef3f8;
      border-left: 4px solid #054a85;
      padding: 10px 15px;
      border-radius: 4px;
      margin-bottom: 15px;
      font-size: 0.95rem;
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
        <h2><i class="fa-solid fa-university"></i> Libro de Cuentas de Banco</h2>
        <p>Detalle cronológico de consignaciones, retiros y transferencias de una cuenta bancaria, con saldo corriente</p>

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
          <label>Cuenta Bancaria:</label>
          <select name="cuenta" class="form-select">
            <?php if (count($cuentas_banco) == 0): ?>
              <option value="">No hay cuentas bancarias registradas</option>
            <?php else: ?>
              <option value="todas" <?= $cuenta_banco == 'todas' ? 'selected' : '' ?>>-- Todas --</option>
              <?php foreach ($cuentas_banco as $c): ?>
                <option value="<?= htmlspecialchars($c['codigo_cuenta']) ?>" <?= $c['codigo_cuenta'] == $cuenta_banco ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['codigo_cuenta']) ?> - <?= htmlspecialchars($c['nombre_cuenta']) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label>Tercero:</label>
          <select name="tercero" class="form-select">
            <option value="">-- Todos --</option>
            <?php foreach ($lista_terceros as $t): ?>
              <option value="<?= htmlspecialchars($t['tercero_identificacion']) ?>" <?= $t['tercero_identificacion'] == $tercero ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['tercero_nombre']) ?> (<?= htmlspecialchars($t['tercero_identificacion']) ?>)
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
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn w-100" style="background-color: #103669; color: white;">
            <i class="fa-solid fa-search"></i> Buscar
          </button>
        </div>
        <div class="col-md-12 mt-2">
          <button type="button" class="btn-cancelar" onclick="window.location.href = window.location.pathname">Limpiar Filtros</button>
        </div>
      </form>

      <?php if ($cuenta_banco == 'todas'): ?>
      <div class="cuenta-actual">
        <strong>Cuenta consultada:</strong> Todas las cuentas bancarias (<?= count($cuentasAConsultar) ?>)
      </div>
      <?php elseif ($cuenta_banco != ''): ?>
      <div class="cuenta-actual">
        <strong>Cuenta consultada:</strong> <?= htmlspecialchars($cuenta_banco) ?> - <?= htmlspecialchars($nombre_cuenta_banco) ?>
      </div>
      <?php endif; ?>

      <?php if (count($filasReporte) > 0): ?>
      <div class="mb-3 text-end">
        <button onclick="exportarPDF()" class="btn-agregar">
          <i class="fa-solid fa-file-pdf"></i> Exportar PDF
        </button>
        <button onclick="exportarExcel()" class="btn-agregar-excel">
          <i class="fa-solid fa-file-excel"></i> Exportar Excel
        </button>
      </div>
      <?php endif; ?>

      <div class="balance-container">
        <div class="table-responsive">
          <table class="table-balance">
            <thead>
              <tr>
                <th>Cuenta</th>
                <th>Comprobante</th>
                <th>Fecha</th>
                <th>Identificación del Tercero</th>
                <th>Nombre del Tercero</th>
                <th class="text-end">Saldo Inicial</th>
                <th class="text-end">Movimiento Débito</th>
                <th class="text-end">Movimiento Crédito</th>
                <th class="text-end">Saldo Final</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($cuentasAConsultar) == 0): ?>
                <tr>
                  <td colspan="9" class="text-center text-muted py-3">No hay ninguna cuenta bancaria (1110xx) registrada todavía en el libro diario</td>
                </tr>
              <?php elseif (count($filasReporte) > 0): ?>
                <?php foreach ($filasReporte as $fila): ?>
                  <tr>
                    <td><?= htmlspecialchars($fila['cuenta']) ?></td>
                    <td><?= htmlspecialchars($fila['comprobante']) ?></td>
                    <td><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                    <td><?= htmlspecialchars($fila['tercero_identificacion']) ?></td>
                    <td><?= htmlspecialchars($fila['tercero_nombre']) ?></td>
                    <td class="text-end">$<?= number_format($fila['saldo_inicial'], 2, ',', '.') ?></td>
                    <td class="text-end">$<?= number_format($fila['debito'], 2, ',', '.') ?></td>
                    <td class="text-end">$<?= number_format($fila['credito'], 2, ',', '.') ?></td>
                    <td class="text-end"><strong>$<?= number_format($fila['saldo_final'], 2, ',', '.') ?></strong></td>
                  </tr>
                <?php endforeach; ?>
                <tr class="total-general">
                  <td colspan="5">TOTALES DEL PERÍODO<?= $cuenta_banco == 'todas' ? ' (todas las cuentas)' : '' ?></td>
                  <td class="text-end">$<?= number_format($saldoInicialPeriodo, 2, ',', '.') ?></td>
                  <td class="text-end">$<?= number_format($totalDebito, 2, ',', '.') ?></td>
                  <td class="text-end">$<?= number_format($totalCredito, 2, ',', '.') ?></td>
                  <td class="text-end">$<?= number_format($saldoFinalPeriodo, 2, ',', '.') ?></td>
                </tr>
              <?php else: ?>
                <tr>
                  <td colspan="5">Sin movimientos en el período</td>
                  <td class="text-end">$<?= number_format($saldoInicialPeriodo, 2, ',', '.') ?></td>
                  <td class="text-end">$0,00</td>
                  <td class="text-end">$0,00</td>
                  <td class="text-end">$<?= number_format($saldoFinalPeriodo, 2, ',', '.') ?></td>
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
        cuenta: document.querySelector('select[name="cuenta"]').value,
        tercero: document.querySelector('select[name="tercero"]').value,
        desde: document.querySelector('input[name="desde"]').value,
        hasta: document.querySelector('input[name="hasta"]').value
      });
      window.location.href = `exportar_bancos_excel.php?${params}`;
    }

    function exportarPDF() {
      const params = new URLSearchParams({
        cuenta: document.querySelector('select[name="cuenta"]').value,
        tercero: document.querySelector('select[name="tercero"]').value,
        desde: document.querySelector('input[name="desde"]').value,
        hasta: document.querySelector('input[name="hasta"]').value
      });
      window.open(`exportar_bancos_pdf.php?${params}`, '_blank');
    }
  </script>
</body>
</html>