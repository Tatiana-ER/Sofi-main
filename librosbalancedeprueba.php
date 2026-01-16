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
$periodo_fiscal = isset($_GET['periodo_fiscal']) ? $_GET['periodo_fiscal'] : date('Y');
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';
$tipo_cuenta = isset($_GET['tipo_cuenta']) ? $_GET['tipo_cuenta'] : '';
$mostrar_saldo_inicial = isset($_GET['mostrar_saldo_inicial']) && $_GET['mostrar_saldo_inicial'] == '1' ? '1' : '0';

// ================== FUNCIÓN PARA EXTRAER IDENTIFICACIÓN ==================
function extraerIdentificacion($identificacion) {
    if (empty($identificacion)) {
        return '';
    }
    if (strpos($identificacion, '-') !== false) {
        $partes = explode('-', $identificacion, 2);
        return trim($partes[0]);
    }
    return trim($identificacion);
}

// ================== OBTENER NOMBRES DE CUENTAS ==================
function obtenerNombresCuentas($pdo) {
    $sql = "SELECT nivel1, nivel2, nivel3, nivel4, nivel5, nivel6 FROM cuentas_contables";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nombres = [];
    foreach ($resultados as $fila) {
        for ($i = 1; $i <= 6; $i++) {
            $campo = 'nivel' . $i;
            if (!empty($fila[$campo])) {
                $partes = explode('-', $fila[$campo], 2);
                if (count($partes) == 2) {
                    $codigo = trim($partes[0]);
                    $nombre = trim($partes[1]);
                    $nombres[$codigo] = $nombre;
                }
            }
        }
    }
    return $nombres;
}

$nombres_cuentas = obtenerNombresCuentas($pdo);

// ================== FUNCIÓN PARA CALCULAR MOVIMIENTOS ==================
function calcularMovimientos($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $naturaleza = substr($codigo_cuenta, 0, 1);

    // Saldo inicial (antes del periodo)
    $sql_inicial = "
        SELECT 
            COALESCE(SUM(debito), 0) as suma_debito,
            COALESCE(SUM(credito), 0) as suma_credito
        FROM libro_diario 
        WHERE codigo_cuenta = :cuenta
          AND fecha < :desde
    ";

    $params_ini = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde];

    if ($tercero != '') {
        $sql_inicial .= " AND (
            tercero_identificacion = :tercero
            OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
        )";
        $params_ini[':tercero'] = $tercero;
        $params_ini[':tercero_con_guion'] = $tercero . ' -';
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
    $sql_mov = "
        SELECT 
            COALESCE(SUM(debito), 0) as mov_debito,
            COALESCE(SUM(credito), 0) as mov_credito
        FROM libro_diario 
        WHERE codigo_cuenta = :cuenta
          AND fecha BETWEEN :desde AND :hasta
    ";

    $params_mov = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

    if ($tercero != '') {
        $sql_mov .= " AND (
            tercero_identificacion = :tercero
            OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
        )";
        $params_mov[':tercero'] = $tercero;
        $params_mov[':tercero_con_guion'] = $tercero . ' -';
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

// ================== FUNCIÓN PARA CALCULAR MOVIMIENTOS DE SUBCUENTAS ==================
function calcularMovimientosConSubcuentas($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $naturaleza = substr($codigo_cuenta, 0, 1);
    $longitud_cuenta = strlen($codigo_cuenta);

    // Construir patrón para buscar subcuentas
    // Si la cuenta es "11", buscar todas las que empiecen con "11" (11, 1105, 110505, etc.)
    $patron_subcuentas = $codigo_cuenta . '%';

    // Saldo inicial (antes del periodo) - INCLUYE SUBCUENTAS
    $sql_inicial = "
        SELECT 
            COALESCE(SUM(debito), 0) as suma_debito,
            COALESCE(SUM(credito), 0) as suma_credito
        FROM libro_diario 
        WHERE codigo_cuenta LIKE :patron
          AND fecha < :desde
    ";

    $params_ini = [':patron' => $patron_subcuentas, ':desde' => $fecha_desde];

    if ($tercero != '') {
        $sql_inicial .= " AND (
            tercero_identificacion = :tercero
            OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
        )";
        $params_ini[':tercero'] = $tercero;
        $params_ini[':tercero_con_guion'] = $tercero . ' -';
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

    // Movimientos del periodo - INCLUYE SUBCUENTAS
    $sql_mov = "
        SELECT 
            COALESCE(SUM(debito), 0) as mov_debito,
            COALESCE(SUM(credito), 0) as mov_credito
        FROM libro_diario 
        WHERE codigo_cuenta LIKE :patron
          AND fecha BETWEEN :desde AND :hasta
    ";

    $params_mov = [':patron' => $patron_subcuentas, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

    if ($tercero != '') {
        $sql_mov .= " AND (
            tercero_identificacion = :tercero
            OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
        )";
        $params_mov[':tercero'] = $tercero;
        $params_mov[':tercero_con_guion'] = $tercero . ' -';
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

// ================== OBTENER TODAS LAS CUENTAS CON MOVIMIENTOS ==================
$sql_cuentas_base = "
    SELECT DISTINCT codigo_cuenta, nombre_cuenta
    FROM libro_diario
    WHERE fecha BETWEEN :desde AND :hasta
";

$params_base = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($cuenta_codigo != '') {
    $sql_cuentas_base .= " AND codigo_cuenta = :cuenta";
    $params_base[':cuenta'] = $cuenta_codigo;
}

if ($tercero != '') {
    $sql_cuentas_base .= " AND (
        tercero_identificacion = :tercero
        OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
    )";
    $params_base[':tercero'] = $tercero;
    $params_base[':tercero_con_guion'] = $tercero . ' -';
}

$sql_cuentas_base .= " ORDER BY codigo_cuenta";
$stmt_base = $pdo->prepare($sql_cuentas_base);
$stmt_base->execute($params_base);
$cuentas_con_movimientos = $stmt_base->fetchAll(PDO::FETCH_ASSOC);

// ================== CONSTRUIR ESTRUCTURA SEGÚN FILTRO ==================
$cuentas_completas = [];
$codigos_procesados = [];

if (!empty($tipo_cuenta)) {
    // ====== MODO: FILTRADO POR TIPO DE CUENTA ======
    $tipo_cuenta_int = intval($tipo_cuenta);
    
    // Generar TODAS las cuentas padre del nivel solicitado
    $cuentas_padre = [];
    
    foreach ($cuentas_con_movimientos as $cuenta) {
        $codigo_completo = $cuenta['codigo_cuenta'];
        
        // Extraer el código del nivel solicitado
        if (strlen($codigo_completo) >= $tipo_cuenta_int) {
            $codigo_nivel = substr($codigo_completo, 0, $tipo_cuenta_int);
            
            if (!isset($cuentas_padre[$codigo_nivel])) {
                $cuentas_padre[$codigo_nivel] = true;
            }
        }
    }
    
    // Calcular movimientos para cada cuenta padre (incluyendo subcuentas)
    foreach ($cuentas_padre as $codigo_padre => $dummy) {
        $nombre = isset($nombres_cuentas[$codigo_padre]) ? 
                  $nombres_cuentas[$codigo_padre] : 
                  'Cuenta ' . $codigo_padre;
        
        $movs = calcularMovimientosConSubcuentas($pdo, $codigo_padre, $fecha_desde, $fecha_hasta, $tercero);
        
        // Solo agregar si tiene movimientos
        if ($movs['debito'] != 0 || $movs['credito'] != 0 || $movs['saldo_inicial'] != 0) {
            $cuentas_completas[] = [
                'codigo' => $codigo_padre,
                'nombre' => $nombre,
                'saldo_inicial' => $movs['saldo_inicial'],
                'debito' => $movs['debito'],
                'credito' => $movs['credito'],
                'saldo_final' => $movs['saldo_final'],
                'nivel' => strlen($codigo_padre)
            ];
        }
    }
    
    // Ordenar por código
    usort($cuentas_completas, function($a, $b) {
        return strcmp($a['codigo'], $b['codigo']);
    });
    
} else {
    // ====== MODO: SIN FILTRO (MOSTRAR JERARQUÍA COMPLETA) ======
    
    foreach ($cuentas_con_movimientos as $cuenta) {
        $codigo = $cuenta['codigo_cuenta'];

        // Obtener nombre real
        $nombre = isset($nombres_cuentas[$codigo]) ? $nombres_cuentas[$codigo] : $cuenta['nombre_cuenta'];

        // Agregar cuenta auxiliar
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
                $nombre_nivel = isset($nombres_cuentas[$nivel_codigo]) ?
                               $nombres_cuentas[$nivel_codigo] :
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

    // Asignar totales
    foreach ($cuentas_completas as &$cuenta) {
        if (isset($cuenta['es_agrupacion']) && isset($totales_por_codigo[$cuenta['codigo']])) {
            $cuenta['saldo_inicial'] = $totales_por_codigo[$cuenta['codigo']]['saldo_inicial'];
            $cuenta['debito'] = $totales_por_codigo[$cuenta['codigo']]['debito'];
            $cuenta['credito'] = $totales_por_codigo[$cuenta['codigo']]['credito'];
            $cuenta['saldo_final'] = $totales_por_codigo[$cuenta['codigo']]['saldo_final'];
        }
    }
}

// ================== CALCULAR TOTALES GENERALES ==================
$total_saldo_inicial = 0;
$total_debito = 0;
$total_credito = 0;
$total_saldo_final = 0;

foreach ($cuentas_completas as $cuenta) {
    if ($cuenta['nivel'] == 1 || !empty($tipo_cuenta)) {
        $total_saldo_inicial += $cuenta['saldo_inicial'];
        $total_debito += $cuenta['debito'];
        $total_credito += $cuenta['credito'];
        $total_saldo_final += $cuenta['saldo_final'];
    }
}

// ================== LISTA DE TERCEROS ==================
$sql_terceros = "
    SELECT DISTINCT ld.tercero_identificacion, ld.tercero_nombre
    FROM libro_diario ld
    WHERE ld.tercero_identificacion IS NOT NULL
       AND ld.tercero_identificacion != ''
    ORDER BY ld.tercero_nombre ASC
";
$stmt_terceros = $pdo->query($sql_terceros);
$terceros_db = $stmt_terceros->fetchAll(PDO::FETCH_ASSOC);

$terceros_unificados = [];
foreach ($terceros_db as $t) {
    $identificacion = $t['tercero_identificacion'];
    $identificacion_limpia = extraerIdentificacion($identificacion);

    $nombre_completo = '';
    if (!empty($t['tercero_nombre'])) {
        $nombre_completo = $t['tercero_nombre'];
    } else {
        if (strpos($identificacion, '-') !== false) {
            $partes = explode('-', $identificacion, 2);
            if (count($partes) == 2) {
                $nombre_completo = trim($partes[1]);
            }
        }
    }

    if (!empty($identificacion_limpia) && !isset($terceros_unificados[$identificacion_limpia])) {
        $terceros_unificados[$identificacion_limpia] = [
            'identificacion_limpia' => $identificacion_limpia,
            'nombre' => $nombre_completo ?: 'Tercero ' . $identificacion_limpia,
            'mostrar' => $identificacion_limpia . ' - ' . ($nombre_completo ?: 'Tercero ' . $identificacion_limpia)
        ];
    }
}

$lista_terceros = array_values($terceros_unificados);

// ================== OBTENER NIVELES DISPONIBLES ==================
$niveles_disponibles = [];
$sql_niveles = "SELECT DISTINCT LENGTH(codigo_cuenta) as longitud FROM libro_diario ORDER BY longitud";
$stmt_niveles = $pdo->query($sql_niveles);
$resultados_niveles = $stmt_niveles->fetchAll(PDO::FETCH_ASSOC);

$mapeo_tipos = [
    1 => 'Clase (1 dígito)',
    2 => 'Grupo (2 dígitos)',
    4 => 'Cuenta (4 dígitos)',
    6 => 'Subcuenta (6 dígitos)',
    8 => 'Auxiliar (8 dígitos)',
    10 => 'Referencia (10 dígitos)'
];

// Asegurar que existan los niveles estándar
$niveles_estandar = [1, 2, 4, 6, 8];
$longitudes_existentes = array_column($resultados_niveles, 'longitud');

foreach ($niveles_estandar as $nivel) {
    if (!in_array($nivel, $longitudes_existentes)) {
        $longitudes_existentes[] = $nivel;
    }
}

sort($longitudes_existentes);

foreach ($longitudes_existentes as $longitud) {
    $longitud = intval($longitud);
    $label = isset($mapeo_tipos[$longitud]) ? $mapeo_tipos[$longitud] : "Nivel $longitud dígitos";
    $niveles_disponibles[] = [
        'value' => $longitud,
        'label' => $label
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Balance de Prueba - SOFI</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Raleway:300,400,500,600,700|Poppins:300,400,500,600,700" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
    .table-balance tbody tr:hover { background-color: #f8f9fa; }
    .text-end { text-align: right !important; }
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
    .nivel-6 { padding-left: 45px !important; }
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
    .form-check { padding-top: 8px; }
    @media print {
      .btn-ir, form, .btn-primary, .btn-success, .btn-limpiar { display: none; }
    }
  </style>
</head>
<body>
  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="dashboard.php">
          <img src="./Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
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

  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">
      <div class="section-title">
        <h2><i class="fa-solid fa-balance-scale"></i> Balance de Prueba General</h2>
        <p>Reporte consolidado de movimientos contables</p>

          <!-- Información de la empresa centrada -->
          <div class="text-center empresa-info mt-3 p-3" style="border-radius: 5px;">
              <div style="margin-bottom: 10px;">
                  <strong><?= htmlspecialchars($nombre_empresa) ?></strong><br>
              </div>
              
              <div style="margin-bottom: 10px;">
                  <strong><?= htmlspecialchars($nit_empresa) ?></strong><br>
              </div>
              
              <div style="margin-bottom: 5px;">
                  <strong>PERIODO:</strong> <?= date('d/m/Y', strtotime($fecha_desde)) ?> A <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
              </div>
          </div>
      </div>

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
          <label>Tipo de Cuenta:</label>
          <select name="tipo_cuenta" id="selectTipoCuenta" class="form-select">
            <option value="">-- Todos --</option>
            <?php foreach ($niveles_disponibles as $nivel): ?>
              <option value="<?= htmlspecialchars($nivel['value']) ?>" <?= $nivel['value']==$tipo_cuenta?'selected':'' ?>>
                <?= htmlspecialchars($nivel['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label>Cuenta:</label>
          <select name="cuenta" id="selectCuenta" class="form-select">
            <option value="">-- Todas --</option>
          </select>
        </div>
        <div class="col-md-2">
          <label>Tercero:</label>
          <select name="tercero" id="selectTercero" class="form-select">
            <option value="">-- Todos --</option>
            <?php foreach ($lista_terceros as $t): ?>
              <option value="<?= htmlspecialchars($t['identificacion_limpia']) ?>" <?= $t['identificacion_limpia']==$tercero?'selected':'' ?>>
                <?= htmlspecialchars($t['mostrar']) ?>
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
            <input class="form-check-input" type="checkbox" name="mostrar_saldo_inicial" value="1" id="checkSaldoInicial" <?= $mostrar_saldo_inicial=='1'?'checked':'' ?>>
            <label class="form-check-label" for="checkSaldoInicial">
              Mostrar columna de Saldo Inicial
            </label>
          </div>
        </div>
        <div class="col-md-12 mt-3">
          <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">Limpiar Filtros</button>
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

  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>

  <script>
$(document).ready(function() {
  $('#selectTipoCuenta').select2({
    theme: 'bootstrap-5',
    placeholder: '-- Todos --',
    allowClear: true,
    width: '100%'
  });

  $('#selectCuenta').select2({
    theme: 'bootstrap-5',
    placeholder: '-- Todas --',
    allowClear: true,
    width: '100%',
    ajax: {
      url: 'buscar_cuentas_balance.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { search: params.term || '' };
      },
      processResults: function (data) {
        var grupos = {
          'libro_diario': [],
          'puc': [],
          'catalogo': []
        };
        
        data.forEach(function(item) {
          var fuente = item.fuente || 'libro_diario';
          grupos[fuente].push({
            id: item.valor,
            text: item.texto
          });
        });
        
        var results = [];
        
        if (grupos.libro_diario.length > 0) {
          results = results.concat(grupos.libro_diario);
        }
        
        if (grupos.puc.length > 0) {
          if (results.length > 0) {
            results.push({
              id: '',
              text: '─────── Plan Único de Cuentas (PUC) ───────',
              disabled: true
            });
          }
          results = results.concat(grupos.puc);
        }
        
        if (grupos.catalogo.length > 0) {
          if (results.length > 0) {
            results.push({
              id: '',
              text: '─────── Cuentas Personalizadas ───────',
              disabled: true
            });
          }
          results = results.concat(grupos.catalogo);
        }
        
        return { results: results };
      },
      cache: true
    },
    minimumInputLength: 0,
    language: {
      inputTooShort: function() {
        return 'Escribe para buscar en el PUC completo';
      },
      noResults: function() {
        return 'No se encontraron cuentas';
      },
      searching: function() {
        return 'Buscando...';
      }
    }
  });

  $('#selectTercero').select2({
    theme: 'bootstrap-5',
    placeholder: '-- Todos --',
    allowClear: true,
    width: '100%'
  });
  
  <?php if (!empty($cuenta_codigo)): ?>
  $.ajax({
    url: 'buscar_cuentas_balance.php',
    data: { id: '<?= htmlspecialchars($cuenta_codigo) ?>' },
    dataType: 'json'
  }).then(function(data) {
    if (data && data.length > 0) {
      var option = new Option(data[0].texto, data[0].valor, true, true);
      $('#selectCuenta').append(option).trigger('change');
    }
  });
  <?php endif; ?>
});

function exportarExcel() {
  const params = new URLSearchParams({
    periodo_fiscal: document.querySelector('input[name="periodo_fiscal"]').value,
    cuenta: document.querySelector('select[name="cuenta"]').value,
    tipo_cuenta: document.querySelector('select[name="tipo_cuenta"]').value,
    desde: document.querySelector('input[name="desde"]').value,
    hasta: document.querySelector('input[name="hasta"]').value,
    tercero: document.querySelector('select[name="tercero"]').value,
    mostrar_saldo_inicial: document.querySelector('#checkSaldoInicial').checked ? '1' : '0'
  });
  window.location.href = `exportar_balance_prueba_excel.php?${params}`;
}

function exportarPDF() {
  const params = new URLSearchParams({
    periodo_fiscal: document.querySelector('input[name="periodo_fiscal"]').value,
    cuenta: document.querySelector('select[name="cuenta"]').value,
    tipo_cuenta: document.querySelector('select[name="tipo_cuenta"]').value,
    desde: document.querySelector('input[name="desde"]').value,
    hasta: document.querySelector('input[name="hasta"]').value,
    tercero: document.querySelector('select[name="tercero"]').value,
    mostrar_saldo_inicial: document.querySelector('#checkSaldoInicial').checked ? '1' : '0'
  });
  window.open(`exportar_balance_prueba_pdf.php?${params}`, '_blank');
}

function limpiarFiltros() {
  window.location.href = window.location.pathname;
}
  </script>
</body>
</html>