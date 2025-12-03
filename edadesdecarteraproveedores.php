<?php
// Procesar búsqueda de proveedor por identificación (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'buscarProveedor') {
        header('Content-Type: application/json');
        include("connection.php");

        try {
            $conn = new connection();
            $pdo = $conn->connect();

            $identificacion = $_POST['identificacion'] ?? '';

            $response = [
                'nombre' => '',
                'existe' => false
            ];

            if ($identificacion !== '') {
                // Buscar proveedor en facturas de compra
                $stmt = $pdo->prepare("SELECT DISTINCT nombre FROM facturac WHERE identificacion = ? LIMIT 1");
                $stmt->execute([$identificacion]);
                $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($proveedor) {
                    $response['nombre'] = $proveedor['nombre'];
                    $response['existe'] = true;
                }
            }

            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // Procesar generación del informe de edades de cartera
    if ($_POST['action'] === 'generarInforme') {
        header('Content-Type: application/json');
        include("connection.php");

        try {
            $conn = new connection();
            $pdo = $conn->connect();

            $identificacion = $_POST['identificacion'] ?? '';
            $rangoFechas = $_POST['rangoFechas'] ?? 'todos';
            $tipoFiltro = $_POST['tipoFiltro'] ?? 'proveedor'; // 'proveedor' o 'rango'

            $response = [
                'facturas' => [],
                'totales' => [
                    'sinVencer' => 0,
                    'vencido1_30' => 0,
                    'vencido31_60' => 0,
                    'mayor60' => 0
                ]
            ];

            // Consulta base para obtener facturas pendientes
            $sql = "
                SELECT 
                    f.identificacion,
                    f.nombre,
                    f.consecutivo as documento,
                    f.fecha_vencimiento,
                    CASE 
                        WHEN f.saldoReal IS NULL OR f.saldoReal = '' THEN f.valorTotal
                        ELSE f.saldoReal
                    END as saldo_pendiente
                FROM facturac f
                WHERE (f.saldoReal > 0 OR f.saldoReal IS NULL)
                AND f.formaPago LIKE '%Credito%'
                AND (CASE 
                        WHEN f.saldoReal IS NULL OR f.saldoReal = '' THEN f.valorTotal
                        ELSE f.saldoReal
                    END) > 0
            ";

            $params = [];

            // Aplicar filtro por proveedor si se especificó
            if ($tipoFiltro === 'proveedor' && $identificacion !== '') {
                $sql .= " AND f.identificacion = ?";
                $params[] = $identificacion;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Procesar cada factura
            foreach ($facturas as $factura) {
                $diasMora = calcularDiasMora($factura['fecha_vencimiento']);
                $saldos = clasificarSaldo($factura['saldo_pendiente'], $diasMora);

                // Aplicar filtro por rango de fechas
                $mostrarFactura = true;
                if ($rangoFechas !== 'todos') {
                    switch($rangoFechas) {
                        case '1-30':
                            $mostrarFactura = ($diasMora >= 1 && $diasMora <= 30);
                            break;
                        case '31-60':
                            $mostrarFactura = ($diasMora >= 31 && $diasMora <= 60);
                            break;
                        case '61+':
                            $mostrarFactura = ($diasMora > 60);
                            break;
                    }
                }

                if ($mostrarFactura) {
                    $facturaData = [
                        'identificacion' => $factura['identificacion'],
                        'nombre' => $factura['nombre'],
                        'documento' => $factura['documento'],
                        'fecha_vencimiento' => $factura['fecha_vencimiento'],
                        'dias_mora' => $diasMora,
                        'saldo_sin_vencer' => $saldos['sinVencer'],
                        'vencido_1_30' => $saldos['vencido1_30'],
                        'vencido_31_60' => $saldos['vencido31_60'],
                        'mayor_60' => $saldos['mayor60']
                    ];

                    $response['facturas'][] = $facturaData;

                    // Acumular totales
                    $response['totales']['sinVencer'] += $saldos['sinVencer'];
                    $response['totales']['vencido1_30'] += $saldos['vencido1_30'];
                    $response['totales']['vencido31_60'] += $saldos['vencido31_60'];
                    $response['totales']['mayor60'] += $saldos['mayor60'];
                }
            }

            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // Procesar agregar proveedor a la tabla
    if ($_POST['action'] === 'agregarProveedorTabla') {
        header('Content-Type: application/json');
        include("connection.php");

        try {
            $conn = new connection();
            $pdo = $conn->connect();

            $identificacion = $_POST['identificacion'] ?? '';
            $rangoFechas = $_POST['rangoFechas'] ?? 'todos';
            $tipoFiltro = $_POST['tipoFiltro'] ?? 'proveedor';

            $response = [
                'facturas' => [],
                'totales' => [
                    'sinVencer' => 0,
                    'vencido1_30' => 0,
                    'vencido31_60' => 0,
                    'mayor60' => 0
                ],
                'proveedor' => [
                    'identificacion' => $identificacion,
                    'nombre' => ''
                ]
            ];

            // Si es filtro por proveedor, obtener nombre
            if ($tipoFiltro === 'proveedor' && $identificacion !== '') {
                $stmtNombre = $pdo->prepare("SELECT DISTINCT nombre FROM facturac WHERE identificacion = ? LIMIT 1");
                $stmtNombre->execute([$identificacion]);
                $proveedor = $stmtNombre->fetch(PDO::FETCH_ASSOC);
                
                if ($proveedor) {
                    $response['proveedor']['nombre'] = $proveedor['nombre'];
                }
            }

            // Consulta para obtener facturas pendientes de proveedores
            $sql = "
              SELECT 
                  f.identificacion,
                  f.nombre,
                  f.consecutivo as documento,
                  f.fecha_vencimiento,
                  CASE 
                      WHEN f.saldoReal IS NULL OR f.saldoReal = '' THEN f.valorTotal
                      ELSE f.saldoReal
                  END as saldo_pendiente
              FROM facturac f
              WHERE (f.saldoReal > 0 OR f.saldoReal IS NULL)
              AND f.formaPago LIKE '%Credito%'
              AND (CASE 
                      WHEN f.saldoReal IS NULL OR f.saldoReal = '' THEN f.valorTotal
                      ELSE f.saldoReal
                  END) > 0
          ";

            $params = [];

            // Aplicar filtro por proveedor si es específico
            if ($tipoFiltro === 'proveedor' && $identificacion !== '') {
                $sql .= " AND f.identificacion = ?";
                $params[] = $identificacion;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Procesar cada factura
            foreach ($facturas as $factura) {
                $diasMora = calcularDiasMora($factura['fecha_vencimiento']);
                $saldos = clasificarSaldo($factura['saldo_pendiente'], $diasMora);

                // Aplicar filtro por rango de fechas
                $mostrarFactura = true;
                if ($rangoFechas !== 'todos') {
                    switch($rangoFechas) {
                        case '1-30':
                            $mostrarFactura = ($diasMora >= 1 && $diasMora <= 30);
                            break;
                        case '31-60':
                            $mostrarFactura = ($diasMora >= 31 && $diasMora <= 60);
                            break;
                        case '61+':
                            $mostrarFactura = ($diasMora > 60);
                            break;
                    }
                }

                if ($mostrarFactura) {
                    $facturaData = [
                        'identificacion' => $factura['identificacion'],
                        'nombre' => $factura['nombre'],
                        'documento' => $factura['documento'],
                        'fecha_vencimiento' => $factura['fecha_vencimiento'],
                        'dias_mora' => $diasMora,
                        'saldo_sin_vencer' => $saldos['sinVencer'],
                        'vencido_1_30' => $saldos['vencido1_30'],
                        'vencido_31_60' => $saldos['vencido31_60'],
                        'mayor_60' => $saldos['mayor60']
                    ];

                    $response['facturas'][] = $facturaData;

                    // Acumular totales
                    $response['totales']['sinVencer'] += $saldos['sinVencer'];
                    $response['totales']['vencido1_30'] += $saldos['vencido1_30'];
                    $response['totales']['vencido31_60'] += $saldos['vencido31_60'];
                    $response['totales']['mayor60'] += $saldos['mayor60'];
                }
            }

            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

// Función para calcular días de mora
function calcularDiasMora($fechaVencimiento) {
    if (!$fechaVencimiento || $fechaVencimiento == '0000-00-00') return 0;
    
    $hoy = new DateTime();
    $vencimiento = new DateTime($fechaVencimiento);
    
    // Si la fecha de vencimiento es futura, no hay mora
    if ($hoy < $vencimiento) {
        return 0;
    }
    
    $diferencia = $hoy->diff($vencimiento);
    return $diferencia->days;
}

// Función para clasificar saldo por rangos de mora
function clasificarSaldo($saldo, $diasMora) {
    // Asegurarse de que el saldo sea un número válido
    $saldo = floatval($saldo);
    if ($saldo <= 0) {
        return [
            'sinVencer' => 0,
            'vencido1_30' => 0,
            'vencido31_60' => 0,
            'mayor60' => 0
        ];
    }
    
    if ($diasMora === 0) {
        return [
            'sinVencer' => $saldo,
            'vencido1_30' => 0,
            'vencido31_60' => 0,
            'mayor60' => 0
        ];
    } elseif ($diasMora >= 1 && $diasMora <= 30) {
        return [
            'sinVencer' => 0,
            'vencido1_30' => $saldo,
            'vencido31_60' => 0,
            'mayor60' => 0
        ];
    } elseif ($diasMora >= 31 && $diasMora <= 60) {
        return [
            'sinVencer' => 0,
            'vencido1_30' => 0,
            'vencido31_60' => $saldo,
            'mayor60' => 0
        ];
    } else {
        return [
            'sinVencer' => 0,
            'vencido1_30' => 0,
            'vencido31_60' => 0,
            'mayor60' => $saldo
        ];
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>  

  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style>
    input[type="text"], select {
        width: 100%;
        box-sizing: border-box;
        padding: 5px;
    }
    .btn-generar {
      background-color: #28a745;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 20px;
    }
    .btn-generar:hover {
      background-color: #218838;
    }
    .btn-agregar {
      background-color: #007bff;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 20px;
      margin-left: 10px;
    }
    .btn-agregar:hover {
      background-color: #0069d9;
    }
    .btn-exportar {
      background-color: #17a2b8;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 20px;
      margin-left: 10px;
    }
    .btn-exportar:hover {
      background-color: #138496;
    }
    .btn-limpiar {
      background-color: #6c757d;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 20px;
      margin-left: 10px;
    }
    .btn-limpiar:hover {
      background-color: #5a6268;
    }
    .total-row {
      font-weight: bold;
      background-color: #e9ecef;
    }
    .loading {
      display: none;
      text-align: center;
      padding: 20px;
    }
    .proveedor-header {
      background-color: #e3f2fd;
      font-weight: bold;
    }
    .btn-eliminar {
      background-color: #dc3545;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
    }
    .btn-eliminar:hover {
      background-color: #c82333;
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
      <button class="btn-ir" onclick="window.location.href='informesproveedores.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
    <div class="container" data-aos="fade-up">

      <div class="section-title">
        <h2>EDADES DE CARTERA PROVEEDORES</h2>
      </div>

      <form id="formEdadesCartera" method="post">
        <div class="mb-3">
            <label class="form-label fw-bold">Tipo de Filtro:</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tipoFiltro" id="filtroProveedor" value="proveedor" checked>
                <label class="form-check-label" for="filtroProveedor">
                    Filtrar por Proveedor
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tipoFiltro" id="filtroRango" value="rango">
                <label class="form-check-label" for="filtroRango">
                    Filtrar por Rango de Edades
                </label>
            </div>
        </div>

        <div class="mb-3" id="proveedor-fields">
            <label for="identificacion_proveedor" class="form-label">Identificación proveedor</label>
            <input type="text" class="form-control" name="identificacion_proveedor" id="identificacion_proveedor">
        </div>

        <div class="mb-3" id="nombre-field">
            <label for="nombre_proveedor" class="form-label">Nombre proveedor</label>
            <input type="text" class="form-control" name="nombre_proveedor" id="nombre_proveedor" readonly>
        </div>

        <div class="mb-3">
            <label for="rango_fechas" class="form-label">Edades de cartera</label>
            <select class="form-control" name="rango_fechas" id="rango_fechas">
                <option value="todos">Todos los rangos</option>
                <option value="1-30">1-30 días</option>
                <option value="31-60">31-60 días</option>
                <option value="61+">Mayor de 60 días</option>
            </select>
        </div>

        <button type="button" class="btn-agregar" onclick="agregarDatosTabla()">
            <i class="fas fa-plus"></i> Agregar a Tabla
        </button>
        <button type="button" class="btn-limpiar" onclick="limpiarTabla()">
            <i class="fas fa-eraser"></i> Limpiar Tabla
        </button>
        <button type="button" class="btn-exportar" onclick="exportarPDF()">
            <i class="fas fa-file-pdf"></i> Exportar a PDF
        </button>
        <button type="button" class="btn-exportar" onclick="exportarExcel()">
            <i class="fas fa-file-excel"></i> Exportar a Excel
        </button>
      </form>

      <!-- Formularios ocultos para exportación -->
      <form id="formPdf" action="exportar_edades_proveedores_pdf.php" method="POST" target="_blank" style="display: none;">
          <input type="hidden" id="datosPdf" name="datos">
      </form>

      <form id="formExcel" action="exportar_edades_proveedores_excel.php" method="POST" target="_blank" style="display: none;">
          <input type="hidden" id="datosExcel" name="datos">
      </form>

      <div class="loading" id="loading">
        <i class="fas fa-spinner fa-spin"></i> Procesando...
      </div>

      <div class="section-subtitle">
        <h6>INFORME</h6>
      </div>  
      
      <div class="row">
        <div class="table-container">
          <table id="informe-table">
            <thead>
              <tr>
                <th>Identificación</th>
                <th>Nombre del proveedor</th>
                <th>Documento</th>
                <th>Fecha de vencimiento</th>
                <th>Días Mora</th>
                <th>Saldo sin vencer</th>
                <th>Vencido 1-30 días</th>
                <th>Vencido 31-60 días</th>
                <th>Mayor de 60 días</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="informe-body">
              <!-- Los datos se llenarán dinámicamente -->
            </tbody>
            <tfoot>
              <tr class="total-row">
                <td colspan="5">TOTAL</td>
                <td id="total-sin-vencer">0.00</td>
                <td id="total-1-30">0.00</td>
                <td id="total-31-60">0.00</td>
                <td id="total-mayor-60">0.00</td>
                <td></td>
              </tr>
            </tfoot>
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
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    // Array para almacenar los datos agregados (evitar duplicados)
    let datosAgregados = new Set();

    // Función para buscar proveedor por identificación
    function buscarProveedor() {
      const identificacion = document.getElementById('identificacion_proveedor').value.trim();
      
      if (identificacion === '') {
        document.getElementById('nombre_proveedor').value = '';
        return;
      }

      fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=buscarProveedor&identificacion=' + encodeURIComponent(identificacion)
      })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          console.error('Error:', data.error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al buscar el proveedor: ' + data.error
          });
          return;
        }

        if (data.existe) {
          document.getElementById('nombre_proveedor').value = data.nombre;
        } else {
          document.getElementById('nombre_proveedor').value = 'Proveedor no encontrado';
        }
      })
      .catch(error => {
        console.error('Error en fetch:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error al realizar la búsqueda'
        });
      });
    }

    // Función para manejar el cambio de tipo de filtro
    function manejarCambioFiltro() {
        const filtroProveedor = document.getElementById('filtroProveedor').checked;
        const proveedorFields = document.getElementById('proveedor-fields');
        const nombreField = document.getElementById('nombre-field');
        
        if (filtroProveedor) {
            proveedorFields.style.display = 'block';
            nombreField.style.display = 'block';
        } else {
            proveedorFields.style.display = 'none';
            nombreField.style.display = 'none';
            // Limpiar campos cuando se cambia a filtro por rango
            document.getElementById('identificacion_proveedor').value = '';
            document.getElementById('nombre_proveedor').value = '';
        }
    }

    // Función para agregar datos a la tabla
    function agregarDatosTabla() {
        const tipoFiltro = document.querySelector('input[name="tipoFiltro"]:checked').value;
        const identificacion = document.getElementById('identificacion_proveedor').value.trim();
        const rangoFechas = document.getElementById('rango_fechas').value;
        
        // Validaciones según el tipo de filtro
        if (tipoFiltro === 'proveedor' && identificacion === '') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe ingresar una identificación de proveedor'
            });
            return;
        }

        // Crear clave única para evitar duplicados
        const claveUnica = tipoFiltro === 'proveedor' 
            ? `proveedor_${identificacion}_${rangoFechas}`
            : `rango_${rangoFechas}`;

        // Verificar si ya está en la tabla
        if (datosAgregados.has(claveUnica)) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos duplicados',
                text: 'Estos datos ya están en la tabla'
            });
            return;
        }

        // Mostrar loading
        document.getElementById('loading').style.display = 'block';

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=agregarProveedorTabla&identificacion=' + encodeURIComponent(identificacion) + 
                  '&rangoFechas=' + encodeURIComponent(rangoFechas) +
                  '&tipoFiltro=' + encodeURIComponent(tipoFiltro)
        })
        .then(response => response.json())
        .then(data => {
            // Ocultar loading
            document.getElementById('loading').style.display = 'none';

            if (data.error) {
                console.error('Error:', data.error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al agregar los datos: ' + data.error
                });
                return;
            }

            // Agregar clave al set para evitar duplicados
            datosAgregados.add(claveUnica);

            // Agregar facturas a la tabla
            if (data.facturas.length > 0) {
                let totalProveedor = 0;
                
                data.facturas.forEach(factura => {
                    // Para filtro por rango, usar el nombre de la factura
                    const nombreMostrar = tipoFiltro === 'proveedor' ? data.proveedor.nombre : factura.nombre;
                    agregarFilaFactura(factura, nombreMostrar, claveUnica);
                    
                    // Calcular total del proveedor
                    const saldoSinVencer = parseFloat(factura.saldo_sin_vencer) || 0;
                    const vencido1_30 = parseFloat(factura.vencido_1_30) || 0;
                    const vencido31_60 = parseFloat(factura.vencido_31_60) || 0;
                    const mayor60 = parseFloat(factura.mayor_60) || 0;
                    totalProveedor += saldoSinVencer + vencido1_30 + vencido31_60 + mayor60;
                });

                // Si es filtro por proveedor, agregar fila de TOTAL PROVEEDOR
                if (tipoFiltro === 'proveedor') {
                    agregarFilaTotalProveedor(identificacion, data.proveedor.nombre, totalProveedor);
                }

                // Actualizar totales
                actualizarTotales();

                const mensaje = tipoFiltro === 'proveedor' 
                    ? `Se agregaron ${data.facturas.length} factura(s) del proveedor`
                    : `Se agregaron ${data.facturas.length} factura(s) del rango seleccionado`;

                Swal.fire({
                    icon: 'success',
                    title: 'Datos agregados',
                    text: mensaje,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin resultados',
                    text: 'No se encontraron facturas con los criterios seleccionados'
                });
            }

            // Limpiar campos si es filtro por proveedor
            if (tipoFiltro === 'proveedor') {
                document.getElementById('identificacion_proveedor').value = '';
                document.getElementById('nombre_proveedor').value = '';
            }
        })
        .catch(error => {
            document.getElementById('loading').style.display = 'none';
            console.error('Error en fetch:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al agregar los datos'
            });
        });
    }

    // Función para agregar una fila de TOTAL PROVEEDOR
    function agregarFilaTotalProveedor(identificacion, nombre, total) {
        const tbody = document.getElementById('informe-body');
        
        const totalRow = document.createElement('tr');
        totalRow.className = 'total-proveedor-row';
        totalRow.setAttribute('data-total-proveedor', identificacion);
        
        totalRow.innerHTML = `
            <td colspan="5" style="text-align: right; font-weight: bold;">
                TOTAL PROVEEDOR: ${identificacion} - ${nombre}
            </td>
            <td colspan="4" style="text-align: center; font-weight: bold;" class="total-general-proveedor">
                ${formatearMoneda(total)}
            </td>
            <td>
                <button type="button" class="btn-eliminar" onclick="eliminarTotalProveedor('${identificacion}')">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </td>
        `;
        
        tbody.appendChild(totalRow);
        
        // Agregar separador
        const separator = document.createElement('tr');
        separator.innerHTML = '<td colspan="10" class="proveedor-separator"></td>';
        tbody.appendChild(separator);
    }

    // Función para eliminar el total del proveedor
    function eliminarTotalProveedor(identificacion) {
        // Buscar y eliminar todas las facturas de este proveedor
        const filasProveedor = document.querySelectorAll(`tr[data-identificacion="${identificacion}"]`);
        const filaTotal = document.querySelector(`tr[data-total-proveedor="${identificacion}"]`);
        
        // Eliminar filas
        filasProveedor.forEach(fila => fila.remove());
        if (filaTotal) filaTotal.remove();
        
        // Eliminar separador (el siguiente hermano de la fila total)
        if (filaTotal && filaTotal.nextElementSibling) {
            filaTotal.nextElementSibling.remove();
        }
        
        // Eliminar del set de datos agregados
        // Buscar y eliminar todas las claves relacionadas con este proveedor
        const clavesAEliminar = [];
        datosAgregados.forEach(clave => {
            if (clave.startsWith(`proveedor_${identificacion}_`)) {
                clavesAEliminar.push(clave);
            }
        });
        
        clavesAEliminar.forEach(clave => {
            datosAgregados.delete(clave);
        });
        
        // Actualizar totales
        actualizarTotales();
    }

    // Función para agregar una fila de factura a la tabla
    function agregarFilaFactura(factura, nombreProveedor, claveUnica) {
        const tbody = document.getElementById('informe-body');
        const row = document.createElement('tr');
        row.setAttribute('data-identificacion', factura.identificacion);
        row.setAttribute('data-documento', factura.documento);
        row.setAttribute('data-clave-unica', claveUnica);
        
        const saldoSinVencer = parseFloat(factura.saldo_sin_vencer) || 0;
        const vencido1_30 = parseFloat(factura.vencido_1_30) || 0;
        const vencido31_60 = parseFloat(factura.vencido_31_60) || 0;
        const mayor60 = parseFloat(factura.mayor_60) || 0;
        
        row.innerHTML = `
            <td>${factura.identificacion}</td>
            <td>${nombreProveedor}</td>
            <td>${factura.documento}</td>
            <td>${formatearFecha(factura.fecha_vencimiento)}</td>
            <td>${factura.dias_mora}</td>
            <td class="saldo-sin-vencer" data-value="${saldoSinVencer}">${formatearMoneda(saldoSinVencer)}</td>
            <td class="vencido-1-30" data-value="${vencido1_30}">${formatearMoneda(vencido1_30)}</td>
            <td class="vencido-31-60" data-value="${vencido31_60}">${formatearMoneda(vencido31_60)}</td>
            <td class="mayor-60" data-value="${mayor60}">${formatearMoneda(mayor60)}</td>
            <td>
                <button type="button" class="btn-eliminar" onclick="eliminarFactura('${factura.identificacion}', '${factura.documento}', '${claveUnica}')">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    }

    // Función para eliminar factura
    function eliminarFactura(identificacion, documento, claveUnica) {
        Swal.fire({
            title: '¿Está seguro?',
            text: "¿Desea eliminar esta factura de la tabla?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Eliminar fila del DOM
                const fila = document.querySelector(`tr[data-identificacion="${identificacion}"][data-documento="${documento}"]`);
                if (fila) {
                    fila.remove();
                }

                // Eliminar del set de datos agregados
                datosAgregados.delete(claveUnica);

                // Actualizar totales
                actualizarTotales();

                Swal.fire(
                    'Eliminado',
                    'La factura ha sido eliminada de la tabla',
                    'success'
                );
            }
        });
    }

    // Función para limpiar toda la tabla
    function limpiarTabla() {
        // Verificar si hay datos antes de mostrar la alerta
        const tbody = document.getElementById('informe-body');
        if (!tbody || tbody.children.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Tabla vacía',
                text: 'No hay datos en la tabla para limpiar'
            });
            return;
        }

        Swal.fire({
            title: '¿Está seguro?',
            text: "Se eliminarán todos los datos de la tabla",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Limpiar datos
                datosAgregados.clear();
                
                // Limpiar cuerpo de la tabla
                document.getElementById('informe-body').innerHTML = '';
                
                // Resetear los totales a cero
                resetearTotales();
                
                Swal.fire(
                    'Limpiado',
                    'La tabla ha sido limpiada',
                    'success'
                );
            }
        });
    }

    // Función para resetear totales a cero
    function resetearTotales() {
        // Resetear valores mostrados
        document.getElementById('total-sin-vencer').textContent = '0.00';
        document.getElementById('total-1-30').textContent = '0.00';
        document.getElementById('total-31-60').textContent = '0.00';
        document.getElementById('total-mayor-60').textContent = '0.00';
        
        // Resetear data attributes
        document.getElementById('total-sin-vencer').setAttribute('data-value', '0');
        document.getElementById('total-1-30').setAttribute('data-value', '0');
        document.getElementById('total-31-60').setAttribute('data-value', '0');
        document.getElementById('total-mayor-60').setAttribute('data-value', '0');
    }

    // Función para calcular totales por proveedor
    function calcularTotalesPorProveedor() {
        const proveedores = {};
        
        const filas = document.querySelectorAll('#informe-body tr');
        
        filas.forEach(fila => {
            // Verificar si esta fila tiene las celdas necesarias
            const celdaIdentificacion = fila.cells[0];
            const celdaNombre = fila.cells[1];
            const celdaSaldoSinVencer = fila.querySelector('.saldo-sin-vencer');
            const celdaVencido1_30 = fila.querySelector('.vencido-1-30');
            const celdaVencido31_60 = fila.querySelector('.vencido-31-60');
            const celdaMayor60 = fila.querySelector('.mayor-60');
            
            // Si no tiene celdas necesarias, saltar esta fila
            if (!celdaIdentificacion || !celdaSaldoSinVencer) {
                return; // Saltar esta iteración
            }
            
            const identificacion = celdaIdentificacion.textContent;
            const nombre = celdaNombre ? celdaNombre.textContent : '';
            
            const sinVencer = parseFloat(celdaSaldoSinVencer.getAttribute('data-value')) || 0;
            const vencido1_30 = celdaVencido1_30 ? parseFloat(celdaVencido1_30.getAttribute('data-value')) || 0 : 0;
            const vencido31_60 = celdaVencido31_60 ? parseFloat(celdaVencido31_60.getAttribute('data-value')) || 0 : 0;
            const mayor60 = celdaMayor60 ? parseFloat(celdaMayor60.getAttribute('data-value')) || 0 : 0;
            
            const totalProveedor = sinVencer + vencido1_30 + vencido31_60 + mayor60;
            
            if (!proveedores[identificacion]) {
                proveedores[identificacion] = {
                    nombre: nombre,
                    total: 0,
                    sinVencer: 0,
                    vencido1_30: 0,
                    vencido31_60: 0,
                    mayor60: 0
                };
            }
            
            proveedores[identificacion].total += totalProveedor;
            proveedores[identificacion].sinVencer += sinVencer;
            proveedores[identificacion].vencido1_30 += vencido1_30;
            proveedores[identificacion].vencido31_60 += vencido31_60;
            proveedores[identificacion].mayor60 += mayor60;
        });
        
        return proveedores;
    }

    // Función para actualizar totales
    function actualizarTotales() {
        let totalSinVencer = 0;
        let total1_30 = 0;
        let total31_60 = 0;
        let totalMayor60 = 0;

        // Solo sumar filas que sean facturas (no totales de proveedor ni separadores)
        const filas = document.querySelectorAll('#informe-body tr:not(.total-proveedor-row):not(.proveedor-separator)');
        
        filas.forEach(fila => {
            // Verificar si la fila tiene las celdas necesarias
            const celdaSinVencer = fila.querySelector('.saldo-sin-vencer');
            if (celdaSinVencer) {
                const sinVencer = parseFloat(celdaSinVencer.getAttribute('data-value')) || 0;
                const vencido1_30 = fila.querySelector('.vencido-1-30') ? 
                                   parseFloat(fila.querySelector('.vencido-1-30').getAttribute('data-value')) || 0 : 0;
                const vencido31_60 = fila.querySelector('.vencido-31-60') ? 
                                    parseFloat(fila.querySelector('.vencido-31-60').getAttribute('data-value')) || 0 : 0;
                const mayor60 = fila.querySelector('.mayor-60') ? 
                               parseFloat(fila.querySelector('.mayor-60').getAttribute('data-value')) || 0 : 0;

                totalSinVencer += sinVencer;
                total1_30 += vencido1_30;
                total31_60 += vencido31_60;
                totalMayor60 += mayor60;
            }
        });

        // Actualizar las celdas de totales
        actualizarCeldasTotales(totalSinVencer, total1_30, total31_60, totalMayor60);
    }

    // Función auxiliar para actualizar celdas de totales
    function actualizarCeldasTotales(sinVencer, vencido1_30, vencido31_60, mayor60) {
        const celdaSinVencer = document.getElementById('total-sin-vencer');
        const celda1_30 = document.getElementById('total-1-30');
        const celda31_60 = document.getElementById('total-31-60');
        const celdaMayor60 = document.getElementById('total-mayor-60');
        
        if (celdaSinVencer) {
            celdaSinVencer.textContent = formatearMoneda(sinVencer);
            celdaSinVencer.setAttribute('data-value', sinVencer);
        }
        
        if (celda1_30) {
            celda1_30.textContent = formatearMoneda(vencido1_30);
            celda1_30.setAttribute('data-value', vencido1_30);
        }
        
        if (celda31_60) {
            celda31_60.textContent = formatearMoneda(vencido31_60);
            celda31_60.setAttribute('data-value', vencido31_60);
        }
        
        if (celdaMayor60) {
            celdaMayor60.textContent = formatearMoneda(mayor60);
            celdaMayor60.setAttribute('data-value', mayor60);
        }
    }

    // Función para formatear fecha
    function formatearFecha(fecha) {
        if (!fecha || fecha === '0000-00-00') return '';
        
        try {
            const date = new Date(fecha);
            if (isNaN(date.getTime())) {
                return fecha;
            }
            
            return date.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'numeric',
                year: 'numeric'
            });
        } catch (e) {
            return fecha;
        }
    }

    // Función para formatear moneda
    function formatearMoneda(valor) {
        if (isNaN(valor) || valor === null || valor === undefined) {
            return '0.00';
        }
        
        const numero = parseFloat(valor);
        return numero.toLocaleString('es-CO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
            useGrouping: true
        });
    }

    // Función para exportar a PDF
    function exportarPDF() {
        const tbody = document.getElementById('informe-body');
        if (tbody.children.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin datos',
                text: 'No hay datos para exportar. Agregue datos a la tabla primero.'
            });
            return;
        }

        // Preparar datos para exportación
        const datosExportar = prepararDatosExportacion();
        
        // Enviar datos al formulario PDF
        document.getElementById('datosPdf').value = JSON.stringify(datosExportar);
        document.getElementById('formPdf').submit();

        Swal.fire({
            icon: 'success',
            title: 'PDF Generado',
            text: 'El informe se está generando en formato PDF...',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Función para exportar a Excel
    function exportarExcel() {
        const tbody = document.getElementById('informe-body');
        if (tbody.children.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin datos',
                text: 'No hay datos para exportar. Agregue datos a la tabla primero.'
            });
            return;
        }

        // Preparar datos para exportación
        const datosExportar = prepararDatosExportacion();
        
        // Enviar datos al formulario Excel
        document.getElementById('datosExcel').value = JSON.stringify(datosExportar);
        document.getElementById('formExcel').submit();

        Swal.fire({
            icon: 'success',
            title: 'Excel Generado',
            text: 'El informe se está generando en formato Excel...',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Función para preparar datos para exportación
    function prepararDatosExportacion() {
        const datos = {
            facturas: [],
            totales: {
                sinVencer: parseFloat(document.getElementById('total-sin-vencer').getAttribute('data-value')) || 0,
                vencido1_30: parseFloat(document.getElementById('total-1-30').getAttribute('data-value')) || 0,
                vencido31_60: parseFloat(document.getElementById('total-31-60').getAttribute('data-value')) || 0,
                mayor60: parseFloat(document.getElementById('total-mayor-60').getAttribute('data-value')) || 0
            },
            totalesPorProveedor: {},
            filasEspeciales: [],
            fechaGeneracion: new Date().toLocaleDateString('es-ES'),
            titulo: 'Informe de Edades de Cartera - Proveedores'
        };

        // Recopilar todas las facturas y detectar filas especiales
        const filas = document.querySelectorAll('#informe-body tr');
        
        filas.forEach(fila => {
            // Verificar si es una fila de "TOTAL PROVEEDOR"
            if (fila.classList.contains('total-proveedor-row')) {
                const texto = fila.cells[0] ? fila.cells[0].textContent : '';
                const valor = fila.querySelector('.total-general-proveedor') ? 
                              fila.querySelector('.total-general-proveedor').textContent : '0.00';
                
                const filaEspecial = {
                    tipo: 'total_proveedor',
                    texto: texto,
                    valor: valor
                };
                datos.filasEspeciales.push(filaEspecial);
            }
            // Verificar si es un separador
            else if (fila.classList.contains('proveedor-separator')) {
                const filaEspecial = {
                    tipo: 'separador'
                };
                datos.filasEspeciales.push(filaEspecial);
            }
            // Es una factura normal - verificar que tenga celdas de saldo
            else {
                const celdaSinVencer = fila.querySelector('.saldo-sin-vencer');
                if (celdaSinVencer && fila.cells[0] && fila.cells[1]) {
                    const factura = {
                        identificacion: fila.cells[0].textContent || '',
                        nombre: fila.cells[1].textContent || '',
                        documento: fila.cells[2] ? fila.cells[2].textContent : '',
                        fecha_vencimiento: fila.cells[3] ? fila.cells[3].textContent : '',
                        dias_mora: fila.cells[4] ? fila.cells[4].textContent : '0',
                        saldo_sin_vencer: parseFloat(celdaSinVencer.getAttribute('data-value')) || 0,
                        vencido_1_30: fila.querySelector('.vencido-1-30') ? 
                                    parseFloat(fila.querySelector('.vencido-1-30').getAttribute('data-value')) || 0 : 0,
                        vencido_31_60: fila.querySelector('.vencido-31-60') ? 
                                      parseFloat(fila.querySelector('.vencido-31-60').getAttribute('data-value')) || 0 : 0,
                        mayor_60: fila.querySelector('.mayor-60') ? 
                                parseFloat(fila.querySelector('.mayor-60').getAttribute('data-value')) || 0 : 0
                    };
                    datos.facturas.push(factura);
                }
            }
        });

        // Calcular totales por proveedor
        datos.totalesPorProveedor = calcularTotalesPorProveedor();

        return datos;
    }

    // Event listeners al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        // Manejar cambio de filtro
        document.querySelectorAll('input[name="tipoFiltro"]').forEach(radio => {
            radio.addEventListener('change', manejarCambioFiltro);
        });
        
        // Inicializar estado
        manejarCambioFiltro();
        
        // Búsqueda automática de proveedor
        document.getElementById('identificacion_proveedor').addEventListener('input', buscarProveedor);
    });
    
  </script>

</body>

</html>