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
$mostrar_saldo_inicial = isset($_GET['mostrar_saldo_inicial']) ? true : false;

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

// ================== FUNCIÓN PARA CALCULAR SALDOS POR CUENTA ==================
function calcularSaldoCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '', $calcular_saldo_inicial = false) {
    if ($calcular_saldo_inicial) {
        $ano_fiscal = date('Y', strtotime($fecha_desde));
        $fecha_inicio_saldo_inicial = $ano_fiscal . '-01-01';
        $fecha_fin_saldo_inicial = date('Y-m-d', strtotime($fecha_desde . ' -1 day'));
        
        $sql = "SELECT 
                    COALESCE(SUM(debito), 0) as total_debito,
                    COALESCE(SUM(credito), 0) as total_credito
                FROM libro_diario 
                WHERE codigo_cuenta = :cuenta 
                  AND fecha BETWEEN :desde AND :hasta";
        
        $params = [
            ':cuenta' => $codigo_cuenta, 
            ':desde' => $fecha_inicio_saldo_inicial, 
            ':hasta' => $fecha_fin_saldo_inicial
        ];
    } else {
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
    }
    
    if ($tercero != '') {
        // Para filtrar por tercero, necesitamos verificar tanto la identificación limpia como la con formato
        $sql .= " AND ( 
                    tercero_identificacion = :tercero 
                    OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                  )";
        $params[':tercero'] = $tercero;
        $params[':tercero_con_guion'] = $tercero . ' -';
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ================== OBTENER NOMBRES DE CUENTAS DESDE LA TABLA cuentas_contables ==================
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

// ================== OBTENER RESULTADO DEL EJERCICIO ==================
function obtenerResultadoEjercicio($pdo, $fecha_desde, $fecha_hasta, $tercero = '') {
    // Calcular ingresos (clase 4)
    $sql_ingresos = "SELECT COALESCE(SUM(credito - debito), 0) as saldo 
                     FROM libro_diario 
                     WHERE SUBSTRING(codigo_cuenta, 1, 1) = '4' 
                     AND fecha BETWEEN :desde AND :hasta";
    
    $params_ingresos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_ingresos .= " AND ( 
                           tercero_identificacion = :tercero 
                           OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                         )";
        $params_ingresos[':tercero'] = $tercero;
        $params_ingresos[':tercero_con_guion'] = $tercero . ' -';
    }
    
    $stmt = $pdo->prepare($sql_ingresos);
    $stmt->execute($params_ingresos);
    $ingresos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    // Calcular costos (clase 6)
    $sql_costos = "SELECT COALESCE(SUM(debito - credito), 0) as saldo 
                   FROM libro_diario 
                   WHERE SUBSTRING(codigo_cuenta, 1, 1) = '6' 
                   AND fecha BETWEEN :desde AND :hasta";
    
    $params_costos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_costos .= " AND ( 
                          tercero_identificacion = :tercero 
                          OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                        )";
        $params_costos[':tercero'] = $tercero;
        $params_costos[':tercero_con_guion'] = $tercero . ' -';
    }
    
    $stmt = $pdo->prepare($sql_costos);
    $stmt->execute($params_costos);
    $costos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    // Calcular gastos (clase 5)
    $sql_gastos = "SELECT COALESCE(SUM(debito - credito), 0) as saldo 
                   FROM libro_diario 
                   WHERE SUBSTRING(codigo_cuenta, 1, 1) = '5' 
                   AND fecha BETWEEN :desde AND :hasta";
    
    $params_gastos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_gastos .= " AND ( 
                          tercero_identificacion = :tercero 
                          OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                        )";
        $params_gastos[':tercero'] = $tercero;
        $params_gastos[':tercero_con_guion'] = $tercero . ' -';
    }
    
    $stmt = $pdo->prepare($sql_gastos);
    $stmt->execute($params_gastos);
    $gastos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    return $ingresos - $costos - $gastos;
}

// ================== OBTENER TODAS LAS CUENTAS DE ACTIVO (1), PASIVO (2) Y PATRIMONIO (3) ==================
$sql_cuentas = "SELECT DISTINCT 
                    codigo_cuenta, 
                    nombre_cuenta,
                    SUBSTRING(codigo_cuenta, 1, 1) as clase
                FROM libro_diario 
                WHERE fecha BETWEEN :desde AND :hasta
                  AND SUBSTRING(codigo_cuenta, 1, 1) IN ('1', '2', '3')";

$params_cuentas = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($cuenta_codigo != '') {
    $sql_cuentas .= " AND codigo_cuenta = :cuenta";
    $params_cuentas[':cuenta'] = $cuenta_codigo;
}

if ($tercero != '') {
    $sql_cuentas .= " AND ( 
                        tercero_identificacion = :tercero 
                        OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
                      )";
    $params_cuentas[':tercero'] = $tercero;
    $params_cuentas[':tercero_con_guion'] = $tercero . ' -';
}

$sql_cuentas .= " ORDER BY clase, codigo_cuenta";

$stmt = $pdo->prepare($sql_cuentas);
$stmt->execute($params_cuentas);
$todas_cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== SEPARAR Y CALCULAR POR TIPO ==================
$activos = [];
$pasivos = [];
$patrimonios = [];
$totalActivos = 0;
$totalPasivos = 0;
$totalPatrimonios = 0;
$totalSaldoInicialActivos = 0;
$totalSaldoInicialPasivos = 0;
$totalSaldoInicialPatrimonios = 0;

$cuentas_procesadas = [];

foreach ($todas_cuentas as $cuenta) {
    $codigo = $cuenta['codigo_cuenta'];
    $clase = $cuenta['clase'];
    
    $movimientos = calcularSaldoCuenta($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero);
    $debito = floatval($movimientos['total_debito']);
    $credito = floatval($movimientos['total_credito']);
    
    $saldo_inicial = 0;
    if ($mostrar_saldo_inicial) {
        $movimientos_inicial = calcularSaldoCuenta($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero, true);
        $debito_inicial = floatval($movimientos_inicial['total_debito']);
        $credito_inicial = floatval($movimientos_inicial['total_credito']);
        
        if ($clase == '1') {
            $saldo_inicial = $debito_inicial - $credito_inicial;
        } else {
            $saldo_inicial = $credito_inicial - $debito_inicial;
        }
    }
    
    $saldo = 0;
    if ($clase == '1') {
        $saldo = $debito - $credito;
    } else {
        $saldo = $credito - $debito;
    }
    
    if ($saldo != 0 || $saldo_inicial != 0) {
        $nombre_cuenta = isset($nombres_cuentas[$codigo]) ? $nombres_cuentas[$codigo] : $cuenta['nombre_cuenta'];
        
        $item = [
            'codigo' => $codigo,
            'nombre' => $nombre_cuenta,
            'saldo_inicial' => $saldo_inicial,
            'saldo' => $saldo,
            'nivel' => strlen($codigo)
        ];
        
        if ($clase == '1') {
            $activos[] = $item;
            $totalActivos += $saldo;
            $totalSaldoInicialActivos += $saldo_inicial;
        } elseif ($clase == '2') {
            $pasivos[] = $item;
            $totalPasivos += $saldo;
            $totalSaldoInicialPasivos += $saldo_inicial;
        } elseif ($clase == '3') {
            $patrimonios[] = $item;
            $totalPatrimonios += $saldo;
            $totalSaldoInicialPatrimonios += $saldo_inicial;
        }
        
        $cuentas_procesadas[] = $codigo;
    }
}

// ================== AGREGAR AGRUPACIONES SUPERIORES ==================
function agregarAgrupaciones(&$array_cuentas, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial = false) {
    $agrupaciones = [];
    $niveles_validos = [1, 2, 4, 6, 8, 10];
    
    $cuentas_con_saldo = [];
    foreach ($array_cuentas as $cuenta) {
        $cuentas_con_saldo[$cuenta['codigo']] = $cuenta;
    }
    
    foreach ($array_cuentas as $cuenta) {
        $codigo = $cuenta['codigo'];
        $longitud_actual = strlen($codigo);
        
        foreach ($niveles_validos as $longitud) {
            if ($longitud < $longitud_actual) {
                $grupo = substr($codigo, 0, $longitud);
                
                if (!isset($cuentas_con_saldo[$grupo]) && !in_array($grupo, $cuentas_procesadas)) {
                    $nombre = isset($nombres_cuentas[$grupo]) ? $nombres_cuentas[$grupo] : 'Grupo ' . $grupo;
                    
                    if (!isset($agrupaciones[$grupo])) {
                        $agrupaciones[$grupo] = [
                            'codigo' => $grupo,
                            'nombre' => $nombre,
                            'saldo_inicial' => 0,
                            'saldo' => 0,
                            'nivel' => strlen($grupo),
                            'es_grupo' => true
                        ];
                    }
                }
            }
        }
    }
    
    foreach ($niveles_validos as $nivel) {
        foreach (array_reverse($niveles_validos) as $nivel_hijo) {
            if ($nivel_hijo > $nivel) {
                foreach (array_merge($array_cuentas, array_values($agrupaciones)) as $item) {
                    if (strlen($item['codigo']) == $nivel_hijo) {
                        $codigo_padre = substr($item['codigo'], 0, $nivel);
                        
                        if (isset($agrupaciones[$codigo_padre])) {
                            // Siempre suma algebraicamente (respeta signos positivos y negativos)
                            $agrupaciones[$codigo_padre]['saldo'] += $item['saldo'];
                            if ($mostrar_saldo_inicial) {
                                $agrupaciones[$codigo_padre]['saldo_inicial'] += $item['saldo_inicial'];
                            }
                        }
                    }
                }
            }
        }
    }
    
    foreach ($agrupaciones as $codigo => $grupo) {
        $cuentas_procesadas[] = $codigo;
    }
    
    $resultado = array_merge(array_values($agrupaciones), $array_cuentas);
    
    usort($resultado, function($a, $b) {
        return strcmp($a['codigo'], $b['codigo']);
    });
    
    return $resultado;
}

$activos = agregarAgrupaciones($activos, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);
$pasivos = agregarAgrupaciones($pasivos, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);

// ================== AGREGAR RESULTADO DEL EJERCICIO AL PATRIMONIO (ANTES DE AGREGAR AGRUPACIONES) ==================
$resultado_ejercicio = obtenerResultadoEjercicio($pdo, $fecha_desde, $fecha_hasta, $tercero);

if ($resultado_ejercicio != 0) {
    $cuenta_resultado = [
        'codigo' => ($resultado_ejercicio >= 0) ? '360501' : '361001',
        'nombre' => ($resultado_ejercicio >= 0) ? 'Utilidad del ejercicio' : 'Pérdida del ejercicio',
        'saldo_inicial' => 0,
        'saldo' => $resultado_ejercicio, // Valor con su signo (negativo si es pérdida)
        'nivel' => 6,
        'es_resultado' => true
    ];
    
    $patrimonios[] = $cuenta_resultado;
    $totalPatrimonios += $resultado_ejercicio;
    $cuentas_procesadas[] = $cuenta_resultado['codigo'];
}

// Ahora sí agregamos las agrupaciones del patrimonio (que ya incluye el resultado)
$patrimonios = agregarAgrupaciones($patrimonios, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);

// ================== LISTA DE CUENTAS PARA EL SELECT ==================
$sql_codigos = "SELECT DISTINCT codigo_cuenta FROM libro_diario 
                WHERE SUBSTRING(codigo_cuenta, 1, 1) IN ('1', '2', '3')
                ORDER BY codigo_cuenta";
$stmt_codigos = $pdo->query($sql_codigos);
$codigos_unicos = $stmt_codigos->fetchAll(PDO::FETCH_COLUMN);

$lista_cuentas = [];
foreach ($codigos_unicos as $codigo) {
    $nombre = isset($nombres_cuentas[$codigo]) ? $nombres_cuentas[$codigo] : 'Cuenta ' . $codigo;
    $lista_cuentas[] = [
        'codigo_cuenta' => $codigo,
        'nombre_cuenta' => $nombre
    ];
}

// ================== LISTA DE TERCEROS PARA EL SELECT (UNIFICADA) ==================
$sql_terceros = "SELECT 
                    DISTINCT ld.tercero_identificacion,
                    ld.tercero_nombre
                 FROM libro_diario ld
                 WHERE ld.tercero_identificacion IS NOT NULL 
                   AND ld.tercero_identificacion != ''
                   AND SUBSTRING(ld.codigo_cuenta, 1, 1) IN ('1', '2', '3')
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
    
    // Buscar el nombre completo (puede venir de tercero_nombre o del mismo tercero_identificacion)
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

// ================== VERIFICAR EQUILIBRIO CONTABLE ==================
$total_pasivo_patrimonio = $totalPasivos + $totalPatrimonios;
$diferencia = $totalActivos - $total_pasivo_patrimonio;
$esta_equilibrado = abs($diferencia) < 0.01;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Estado de Situación Financiera - SOFI</title>
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
    .nivel-1 {
      background-color: #e3f2fd;
      font-weight: bold;
      font-size: 1.05rem;
    }
    
    .nivel-2 {
      background-color: #f3f9ff;
      font-weight: bold;
    }
    
    .nivel-4 {
      padding-left: 20px !important;
      font-weight: 600;
    }
    
    .nivel-6 {
      padding-left: 40px !important;
    }
    
    .nivel-8 {
      padding-left: 60px !important;
    }
    
    .nivel-10 {
      padding-left: 80px !important;
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
    
    .equilibrio-correcto {
      background-color: #d4edda;
      color: #155724;
      font-weight: bold;
    }
    
    .equilibrio-incorrecto {
      background-color: #f8d7da;
      color: #721c24;
      font-weight: bold;
    }
    
    @media print {
      .btn-ir, form, .btn-primary, .btn-secondary, .btn-success, .btn-limpiar { display: none; }
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

  <!-- ======= Estado de Situación Financiera Section ======= -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='menulibros.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    
    <div class="container" data-aos="fade-up">
      <div class="section-title">
        <h2><i class="fa-solid fa-balance-scale"></i> ESTADO DE SITUACIÓN FINANCIERA</h2>

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
                      <option value="<?= htmlspecialchars($t['identificacion_limpia']) ?>" 
                              <?= $t['identificacion_limpia']==$tercero?'selected':'' ?>>
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
          <!-- Checkbox para mostrar saldo inicial -->
          <div class="col-md-12 mt-2">
              <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="mostrar_saldo_inicial" id="mostrar_saldo_inicial" 
                        <?= $mostrar_saldo_inicial ? 'checked' : '' ?>>
                  <label class="form-check-label" for="mostrar_saldo_inicial">
                      Mostrar saldo inicial
                  </label>
              </div>
          </div>
          <!-- Botón para limpiar filtros -->
          <div class="col-md-12 mt-3">
              <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">
                </i> Limpiar Filtros
              </button>
          </div>
      </form>

      <!-- Botones de exportación -->
      <?php if (count($activos) > 0 || count($pasivos) > 0 || count($patrimonios) > 0): ?>
      <div class="mb-3 text-end">
        <button onclick="exportarPDF()" class="btn btn-secondary">
          <i class="fa-solid fa-file-pdf"></i> Exportar PDF
        </button>
        <button onclick="exportarExcel()" class="btn btn-success">
          <i class="fa-solid fa-file-excel"></i> Exportar Excel
        </button>
      </div>
      <?php endif; ?>

      <!-- ACTIVOS -->
      <h3 class="mt-4 mb-3" style="color: #054a85;">
          ACTIVOS
      </h3>
      <div class="table-container">
          <table>
              <thead>
                  <tr>
                      <th style="width: 15%">Código</th>
                      <th style="width: 45%">Nombre de la cuenta</th>
                      <?php if ($mostrar_saldo_inicial): ?>
                      <th style="width: 20%" class="text-end">Saldo Inicial</th>
                      <th style="width: 20%" class="text-end">Movimientos</th>
                      <?php else: ?>
                      <th style="width: 40%" class="text-end">Saldo</th>
                      <?php endif; ?>
                  </tr>
              </thead>
              <tbody>
                  <?php if (count($activos) > 0): ?>
                      <?php foreach($activos as $fila): ?>
                          <tr class="nivel-<?= $fila['nivel'] ?>">
                              <td><?= htmlspecialchars($fila['codigo']) ?></td>
                              <td><?= htmlspecialchars($fila['nombre']) ?></td>
                              <?php if ($mostrar_saldo_inicial): ?>
                              <td class="text-end"><?= number_format($fila['saldo_inicial'], 2, ',', '.') ?></td>
                              <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                              <?php else: ?>
                              <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                              <?php endif; ?>
                          </tr>
                      <?php endforeach; ?>
                      <tr class="total-seccion">
                          <td colspan="<?= $mostrar_saldo_inicial ? '2' : '2' ?>">TOTAL ACTIVOS</td>
                          <?php if ($mostrar_saldo_inicial): ?>
                          <td class="text-end"><?= number_format($totalSaldoInicialActivos, 2, ',', '.') ?></td>
                          <td class="text-end"><?= number_format($totalActivos, 2, ',', '.') ?></td>
                          <?php else: ?>
                          <td class="text-end"><?= number_format($totalActivos, 2, ',', '.') ?></td>
                          <?php endif; ?>
                      </tr>
                  <?php else: ?>
                      <tr>
                          <td colspan="<?= $mostrar_saldo_inicial ? '4' : '3' ?>" class="text-center text-muted">No hay activos en el período seleccionado</td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>

      <!-- PASIVOS -->
      <h3 class="mt-4 mb-3" style="color: #054a85;">
          PASIVOS
      </h3>
      <div class="table-container">
          <table>
              <thead>
                  <tr>
                      <th style="width: 15%">Código</th>
                      <th style="width: 45%">Nombre de la cuenta</th>
                      <?php if ($mostrar_saldo_inicial): ?>
                      <th style="width: 20%" class="text-end">Saldo Inicial</th>
                      <th style="width: 20%" class="text-end">Movimientos</th>
                      <?php else: ?>
                      <th style="width: 40%" class="text-end">Saldo</th>
                      <?php endif; ?>
                  </tr>
              </thead>
              <tbody>
                  <?php if (count($pasivos) > 0): ?>
                      <?php foreach($pasivos as $fila): ?>
                          <tr class="nivel-<?= $fila['nivel'] ?>">
                              <td><?= htmlspecialchars($fila['codigo']) ?></td>
                              <td><?= htmlspecialchars($fila['nombre']) ?></td>
                              <?php if ($mostrar_saldo_inicial): ?>
                              <td class="text-end"><?= number_format($fila['saldo_inicial'], 2, ',', '.') ?></td>
                              <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                              <?php else: ?>
                              <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                              <?php endif; ?>
                          </tr>
                      <?php endforeach; ?>
                      <tr class="total-seccion">
                          <td colspan="<?= $mostrar_saldo_inicial ? '2' : '2' ?>">TOTAL PASIVOS</td>
                          <?php if ($mostrar_saldo_inicial): ?>
                          <td class="text-end"><?= number_format($totalSaldoInicialPasivos, 2, ',', '.') ?></td>
                          <td class="text-end"><?= number_format($totalPasivos, 2, ',', '.') ?></td>
                          <?php else: ?>
                          <td class="text-end"><?= number_format($totalPasivos, 2, ',', '.') ?></td>
                          <?php endif; ?>
                      </tr>
                  <?php else: ?>
                      <tr>
                          <td colspan="<?= $mostrar_saldo_inicial ? '4' : '3' ?>" class="text-center text-muted">No hay pasivos en el período seleccionado</td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>

      <!-- PATRIMONIO -->
      <h3 class="mt-4 mb-3" style="color: #054a85;">
          PATRIMONIO
      </h3>
      <div class="table-container">
          <table>
              <thead>
                  <tr>
                      <th style="width: 15%">Código</th>
                      <th style="width: 45%">Nombre de la cuenta</th>
                      <?php if ($mostrar_saldo_inicial): ?>
                      <th style="width: 20%" class="text-end">Saldo Inicial</th>
                      <th style="width: 20%" class="text-end">Movimientos</th>
                      <?php else: ?>
                      <th style="width: 40%" class="text-end">Saldo</th>
                      <?php endif; ?>
                  </tr>
              </thead>
              <tbody>
                  <?php if (count($patrimonios) > 0): ?>
                      <?php foreach($patrimonios as $fila): ?>
                          <tr class="nivel-<?= $fila['nivel'] ?>">
                              <td><?= htmlspecialchars($fila['codigo']) ?></td>
                              <td><?= htmlspecialchars($fila['nombre']) ?></td>
                              <?php if ($mostrar_saldo_inicial): ?>
                              <td class="text-end"><?= number_format($fila['saldo_inicial'], 2, ',', '.') ?></td>
                              <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                              <?php else: ?>
                              <td class="text-end"><?= number_format($fila['saldo'], 2, ',', '.') ?></td>
                              <?php endif; ?>
                          </tr>
                      <?php endforeach; ?>
                      <tr class="total-seccion">
                          <td colspan="<?= $mostrar_saldo_inicial ? '2' : '2' ?>">TOTAL PATRIMONIO</td>
                          <?php if ($mostrar_saldo_inicial): ?>
                          <td class="text-end"><?= number_format($totalSaldoInicialPatrimonios, 2, ',', '.') ?></td>
                          <td class="text-end"><?= number_format($totalPatrimonios, 2, ',', '.') ?></td>
                          <?php else: ?>
                          <td class="text-end"><?= number_format($totalPatrimonios, 2, ',', '.') ?></td>
                          <?php endif; ?>
                      </tr>
                  <?php else: ?>
                      <tr>
                          <td colspan="<?= $mostrar_saldo_inicial ? '4' : '3' ?>" class="text-center text-muted">No hay patrimonio en el período seleccionado</td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>

      <!-- EQUILIBRIO CONTABLE -->
      <h3 class="mt-4 mb-3" style="color: #054a85;">
          EQUILIBRIO CONTABLE
      </h3>
      <div class="table-container">
          <table>
              <tbody>
                  <tr class="<?= $esta_equilibrado ? 'equilibrio-correcto' : 'equilibrio-incorrecto' ?>">
                      <td style="width: 75%">
                          <?php if ($esta_equilibrado): ?>
                              <i class="fa-solid fa-check-circle"></i> ACTIVOS = PASIVOS + PATRIMONIO
                          <?php else: ?>
                              <i class="fa-solid fa-exclamation-triangle"></i> DESEQUILIBRIO CONTABLE
                          <?php endif; ?>
                      </td>
                      <?php if ($mostrar_saldo_inicial): ?>
                      <td class="text-end">
                          <?= number_format($totalSaldoInicialActivos, 2, ',', '.') ?> = 
                          <?= number_format($totalSaldoInicialPasivos + $totalSaldoInicialPatrimonios, 2, ',', '.') ?>
                      </td>
                      <td class="text-end">
                          <?= number_format($totalActivos, 2, ',', '.') ?> = 
                          <?= number_format($total_pasivo_patrimonio, 2, ',', '.') ?>
                      </td>
                      <?php else: ?>
                      <td class="text-end">
                          <?= number_format($totalActivos, 2, ',', '.') ?> = 
                          <?= number_format($total_pasivo_patrimonio, 2, ',', '.') ?>
                      </td>
                      <?php endif; ?>
                  </tr>
                  <?php if (!$esta_equilibrado): ?>
                  <tr class="equilibrio-incorrecto">
                      <td colspan="<?= $mostrar_saldo_inicial ? '3' : '2' ?>" class="text-center">
                          Diferencia: <?= number_format($diferencia, 2, ',', '.') ?>
                      </td>
                  </tr>
                  <?php endif; ?>
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
        const mostrar_saldo_inicial = document.querySelector('input[name="mostrar_saldo_inicial"]').checked ? '1' : '0';

        const url = `exportar_estado_situacion_excel.php?periodo_fiscal=${encodeURIComponent(periodo_fiscal)}&cuenta=${encodeURIComponent(cuenta)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&tercero=${encodeURIComponent(tercero)}&mostrar_saldo_inicial=${mostrar_saldo_inicial}`;
        window.location.href = url;
    }

    function exportarPDF() {
        const periodo_fiscal = document.querySelector('input[name="periodo_fiscal"]').value;
        const cuenta = document.querySelector('select[name="cuenta"]').value;
        const desde = document.querySelector('input[name="desde"]').value;
        const hasta = document.querySelector('input[name="hasta"]').value;
        const tercero = document.querySelector('select[name="tercero"]').value;
        const mostrar_saldo_inicial = document.querySelector('input[name="mostrar_saldo_inicial"]').checked ? '1' : '0';

        const url = `exportar_estado_situacion_pdf.php?periodo_fiscal=${encodeURIComponent(periodo_fiscal)}&cuenta=${encodeURIComponent(cuenta)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&tercero=${encodeURIComponent(tercero)}&mostrar_saldo_inicial=${mostrar_saldo_inicial}`;
        window.open(url, '_blank');
    }

    // Función para limpiar filtros
    function limpiarFiltros() {
        // Redirigir a la misma página sin parámetros
        window.location.href = window.location.pathname;
    }
  </script>
</body>
</html>