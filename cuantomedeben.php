<?php
// Solo procesa si viene POST con 'fetchCliente'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetchCliente') {
    header('Content-Type: application/json');
    include("connection.php");

    try {
        $conn = new connection();
        $pdo = $conn->connect();

        $cedula = $_POST['cedula'] ?? '';

        $response = [
            'nombre' => '',
            'totalCartera' => 0,
            'valorAnticipos' => 0,
            'saldoCobrar' => 0
        ];

        if ($cedula !== '') {
            // 1. TOTAL CARTERA: Suma de facturas de venta a crédito
            $stmtCartera = $pdo->prepare("
                SELECT 
                    nombre,
                    SUM(CASE WHEN formaPago LIKE '%Credito%' THEN valorTotal ELSE 0 END) AS totalCartera
                FROM facturav 
                WHERE identificacion = ?
                GROUP BY identificacion
            ");
            $stmtCartera->execute([$cedula]);
            $rowCartera = $stmtCartera->fetch(PDO::FETCH_ASSOC);

            if ($rowCartera) {
                $response['nombre'] = $rowCartera['nombre'];
                $response['totalCartera'] = floatval($rowCartera['totalCartera']);
            }

            // 2. VALOR ANTICIPOS: Suma de recibos de caja con concepto "Anticipo"
            $stmtAnticipos = $pdo->prepare("
                SELECT SUM(valor) AS valorTotal
                FROM recibodecaja
                WHERE identificacion = ? AND concepto LIKE '%Anticipo%'
            ");
            $stmtAnticipos->execute([$cedula]);
            $rowAnticipos = $stmtAnticipos->fetch(PDO::FETCH_ASSOC);

            if ($rowAnticipos && $rowAnticipos['totalAnticipos'] !== null) {
                $response['valorAnticipos'] = floatval($rowAnticipos['totalAnticipos']);
            }

            // 3. PAGOS REALIZADOS: Suma de recibos de caja (excepto anticipos)
            $stmtPagos = $pdo->prepare("
                SELECT SUM(valor) AS totalPagos
                FROM recibodecaja
                WHERE identificacion = ? AND (concepto NOT LIKE '%Anticipo%' OR concepto IS NULL)
            ");
            $stmtPagos->execute([$cedula]);
            $rowPagos = $stmtPagos->fetch(PDO::FETCH_ASSOC);

            $totalPagos = ($rowPagos && $rowPagos['totalPagos'] !== null) ? floatval($rowPagos['totalPagos']) : 0;

            // 4. SALDO POR COBRAR: Cartera - Anticipos - Pagos
            $response['saldoCobrar'] = $response['totalCartera'] - $response['valorAnticipos'] - $totalPagos;
        }

        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Búsqueda de cliente por identificación o nombre (AJAX)
if (isset($_POST['es_ajax']) && $_POST['es_ajax'] == 'cliente') {
    header('Content-Type: application/json');
    include("connection.php");

    try {
        $conn = new connection();
        $pdo = $conn->connect();

        $ident = $_POST['identificacion'] ?? '';
        $nombreCliente = $_POST['nombreCliente'] ?? '';
        $cliente = null;

        if (!empty($ident)) {
            $stmt = $pdo->prepare("SELECT identificacion, nombre FROM facturav WHERE identificacion = :cedula LIMIT 1");
            $stmt->bindParam(':cedula', $ident, PDO::PARAM_STR);
            $stmt->execute();
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif (!empty($nombreCliente)) {
            $likeNombre = "%$nombreCliente%";
            $stmt = $pdo->prepare("SELECT identificacion, nombre FROM facturav WHERE nombre LIKE :nombre LIMIT 1");
            $stmt->bindParam(':nombre', $likeNombre, PDO::PARAM_STR);
            $stmt->execute();
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($cliente) {
            echo json_encode([
                "nombre" => $cliente['nombre'],
                "identificacion" => $cliente['identificacion']
            ]);
        } else {
            echo json_encode(["nombre" => "No encontrado"]);
        }
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SOFI - Cuánto Me Deben</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style>
    input[type="text"], input[type="date"], input[type="number"] {
      width: 100%;
      box-sizing: border-box;
      padding: 5px;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .table-container table {
      width: 100%;
      border-collapse: collapse;
    }
    .table-container th, .table-container td {
      padding: 8px;
      border: 1px solid #ddd;
      text-align: left;
    }
    .table-container th {
      background-color: #0d6efd;
      color: white;
      font-weight: bold;
    }
    .total-row {
      background-color: #f8f9fa;
      font-weight: bold;
    }
  </style>
</head>

<body>

  <!-- Header -->
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

  <!-- Services Section -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='informesclientes.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">

      <div class="section-title">
        <h2>CUÁNTO ME DEBEN</h2>
        <p>Consulte el estado de cartera de un cliente específico</p>
      </div>

      <form action="generarPdfCliente.php" method="POST" target="_blank">
        
        <div class="row g-3">
          <div class="col-md-4">
            <label for="cedula" class="form-label fw-bold">Identificación del Cliente (NIT o CC)*</label>
            <input type="text" class="form-control" id="cedula" name="cedula" placeholder="Ej: 123456789" required>
          </div>

          <div class="col-md-8">
            <label for="nombre" class="form-label fw-bold">Nombre del Cliente</label>
            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del cliente" readonly>
          </div>
        </div>

        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="fecha" class="form-label fw-bold">Fecha de Corte</label>
            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>">
          </div>
        </div>

        <div class="section-title mt-5">
          <h4>ESTADO DE CUENTA</h4>
        </div>

        <div class="row">
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Identificación</th>
                  <th>Nombre del Cliente</th>
                  <th>Total Cartera</th>
                  <th>Valor Anticipos</th>
                  <th>Saldo por Cobrar</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><input type="text" id="identificacion" name="identificacion" readonly></td>
                  <td><input type="text" id="nombreCliente" name="nombreCliente" readonly></td>
                  <td><input type="text" id="totalCartera" name="totalCartera" readonly></td>
                  <td><input type="text" id="valorAnticipos" name="valorAnticipos" readonly></td>
                  <td><input type="text" id="saldoCobrar" name="saldoCobrar" readonly></td>
                </tr>
                <tr class="total-row">
                  <th colspan="2">TOTAL</th>
                  <td><input type="text" id="totalCarteraSum" name="totalCarteraSum" readonly></td>
                  <td><input type="text" id="totalAnticiposSum" name="totalAnticiposSum" readonly></td>
                  <td><input type="text" id="totalSaldoSum" name="totalSaldoSum" readonly></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-file-pdf"></i> Descargar PDF
          </button>
        </div>

      </form>

    </div>
  </section>

  <!-- Footer -->
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

  <script src="assets/js/main.js"></script>

  <script>
    // Búsqueda bidireccional de cliente (similar a factura de compra)
    const inputCedula = document.getElementById("cedula");
    const inputNombre = document.getElementById("nombre");

    // Búsqueda por identificación
    inputCedula.addEventListener("input", function () {
      const valor = this.value.trim();
      
      if (valor.length === 0) {
        limpiarCampos();
        return;
      }

      if (valor.length > 0) {
        fetch("", {
          method: "POST",
          body: new URLSearchParams({ identificacion: valor, es_ajax: 'cliente' }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        })
        .then(res => res.json())
        .then(data => {
          inputNombre.value = data.nombre || "No encontrado";
          if (data.identificacion) {
            obtenerDatosCartera(data.identificacion);
          }
        })
        .catch(console.error);
      }
    });

    // Búsqueda por nombre
    inputNombre.addEventListener("input", function () {
      const valor = this.value.trim();
      
      if (valor.length >= 3) {
        fetch("", {
          method: "POST",
          body: new URLSearchParams({ nombreCliente: valor, es_ajax: 'cliente' }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        })
        .then(res => res.json())
        .then(data => {
          if (data.identificacion) {
            inputCedula.value = data.identificacion;
            obtenerDatosCartera(data.identificacion);
          }
        })
        .catch(console.error);
      }
    });

    // Función para obtener datos de cartera
    function obtenerDatosCartera(cedula) {
      fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=fetchCliente&cedula=' + encodeURIComponent(cedula)
      })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          console.error('Error del servidor:', data.error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al consultar los datos: ' + data.error
          });
          return;
        }

        // Llenar campos de la tabla
        document.getElementById('identificacion').value = cedula;
        document.getElementById('nombreCliente').value = data.nombre;
        document.getElementById('totalCartera').value = formatearMoneda(data.totalCartera);
        document.getElementById('valorAnticipos').value = formatearMoneda(data.valorAnticipos);
        document.getElementById('saldoCobrar').value = formatearMoneda(data.saldoCobrar);

        // Llenar totales
        document.getElementById('totalCarteraSum').value = formatearMoneda(data.totalCartera);
        document.getElementById('totalAnticiposSum').value = formatearMoneda(data.valorAnticipos);
        document.getElementById('totalSaldoSum').value = formatearMoneda(data.saldoCobrar);
      })
      .catch(error => {
        console.error('Error en fetch:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error al realizar la consulta'
        });
      });
    }

    // Función para formatear valores monetarios
    function formatearMoneda(valor) {
      return parseFloat(valor).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Función para limpiar campos
    function limpiarCampos() {
      document.getElementById('nombre').value = '';
      document.getElementById('nombreCliente').value = '';
      document.getElementById('totalCartera').value = '';
      document.getElementById('valorAnticipos').value = '';
      document.getElementById('saldoCobrar').value = '';
      document.getElementById('identificacion').value = '';
      document.getElementById('totalCarteraSum').value = '';
      document.getElementById('totalAnticiposSum').value = '';
      document.getElementById('totalSaldoSum').value = '';
    }
  </script>

</body>
</html>