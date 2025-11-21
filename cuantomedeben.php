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
            'totalFacturado' => 0,
            'valorAnticipos' => 0,
            'abonosRealizados' => 0,
            'saldoCobrar' => 0
        ];

        if ($cedula !== '') {
            // 1. Obtener nombre y TOTAL FACTURADO (valorTotal de facturas a crédito)
            $stmtCartera = $pdo->prepare("
                SELECT 
                    nombre,
                    SUM(CASE 
                        WHEN formaPago LIKE '%Credito%' 
                        THEN CAST(REPLACE(valorTotal, ',', '') AS DECIMAL(10,2))
                        ELSE 0 
                    END) AS totalFacturado
                FROM facturav 
                WHERE identificacion = ?
                GROUP BY nombre
            ");
            $stmtCartera->execute([$cedula]);
            $rowCartera = $stmtCartera->fetch(PDO::FETCH_ASSOC);

            if ($rowCartera) {
                $response['nombre'] = $rowCartera['nombre'];
                $response['totalFacturado'] = floatval($rowCartera['totalFacturado']);
            }

            // 2. VALOR ANTICIPOS: Solo valorCredito de la cuenta 2805 (anticipos recibidos)
            $stmtAnticipos2805 = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(valorCredito), 0) AS valorAnticipos
                FROM detallecomprobantecontable
                WHERE cuentaContable LIKE '2805%'
                AND tercero LIKE CONCAT(?, ' -%')
            ");
            $stmtAnticipos2805->execute([$cedula]);
            $rowAnticipos2805 = $stmtAnticipos2805->fetch(PDO::FETCH_ASSOC);

            if ($rowAnticipos2805) {
                $response['valorAnticipos'] = floatval($rowAnticipos2805['valorAnticipos']);
            }

            // 3. ABONOS REALIZADOS: Suma de pagos aplicados (de detalle_recibo_caja)
              $stmtAbonos = $pdo->prepare("
                  SELECT COALESCE(SUM(det.valorAplicado), 0) AS abonosRealizados
                  FROM detalle_recibo_caja det
                  INNER JOIN docrecibodecaja drc ON det.idRecibo = drc.id
                  WHERE CAST(drc.identificacion AS CHAR) = ?
              ");
              $stmtAbonos->execute([$cedula]);
            $rowAbonos = $stmtAbonos->fetch(PDO::FETCH_ASSOC);

            if ($rowAbonos) {
                $response['abonosRealizados'] = floatval($rowAbonos['abonosRealizados']);
            }

            // 4. SALDO POR COBRAR = Total Facturado - Valor Anticipos - Abonos Realizados
            $response['saldoCobrar'] = $response['totalFacturado'] - $response['valorAnticipos'] - $response['abonosRealizados'];
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
            echo json_encode([
                "nombre" => "",
                "identificacion" => ""
            ]);
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
    .btn-limpiar {
      background-color: #6c757d;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
      cursor: pointer;
      margin-right: 10px;
    }
    .btn-limpiar:hover {
      background-color: #5a6268;
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
      <div class="row g-3 mt-2">
        <div class="col-md-4">
            <label for="cedula" class="form-label fw-bold">Identificación del Cliente (NIT o CC)</label>
            <input type="text" class="form-control" id="cedula" name="cedula" placeholder="Ej: 123456789">
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

      <form id="formPdf" action="generar_pdf.php" method="POST" target="_blank" style="display: none;">
          <!-- Este se llenará dinámicamente con JavaScript -->
      </form>

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
                  <th>Total Facturado</th>
                  <th>Valor Anticipos</th>
                  <th>Abonos Realizados</th>
                  <th>Saldo por Cobrar</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tablaClientes">
                <!-- Las filas se agregarán dinámicamente aquí -->
              </tbody>
              <tfoot>
                <tr class="total-row">
                  <th colspan="2">TOTAL</th>
                  <th id="totalFacturadoSum">0.00</th>
                  <th id="totalAnticiposSum">0.00</th>
                  <th id="totalAbonosSum">0.00</th>
                  <th id="totalSaldoSum">0.00</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="mt-4">
          <button type="button" class="btn-limpiar" onclick="limpiarTabla()">
            <i class="fas fa-eraser"></i> Limpiar Tabla
          </button>
          <button type="button" class="btn btn-success" onclick="generarPDF()">
            <i class="fas fa-file-pdf"></i> Generar PDF
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
    // Array para almacenar los clientes agregados
    let clientesAgregados = [];

    // Búsqueda bidireccional de cliente
    const inputCedula = document.getElementById("cedula");
    const inputNombre = document.getElementById("nombre");

    // Búsqueda por identificación

// Variable para el timeout del debounce
let timeoutBusqueda = null;

// Búsqueda por identificación con debounce
inputCedula.addEventListener("input", function () {
  const valor = this.value.trim();
  
  // Limpiar el timeout anterior
  clearTimeout(timeoutBusqueda);
  
  if (valor.length === 0) {
    inputNombre.value = '';
    return;
  }

  // Solo buscar si tiene al menos 6 dígitos (ajusta según tus necesidades)
  if (valor.length >= 4) {
    // Esperar 800ms después de que el usuario deje de escribir
    timeoutBusqueda = setTimeout(() => {
      fetch("", {
        method: "POST",
        body: new URLSearchParams({ identificacion: valor, es_ajax: 'cliente' }),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      })
      .then(res => res.json())
      .then(data => {
        // Si encuentra el cliente, muestra el nombre
        if (data.nombre && data.nombre !== "No encontrado" && data.nombre !== "") {
          inputNombre.value = data.nombre;
          if (data.identificacion) {
            obtenerDatosCartera(data.identificacion);
          }
        } else {
          // Si no encuentra, deja el campo vacío y llama a obtenerDatosCartera
          inputNombre.value = '';
          obtenerDatosCartera(valor);
        }
      })
      .catch(console.error);
    }, 200); // Espera 800 milisegundos
  }
});

// Búsqueda por nombre con debounce
inputNombre.addEventListener("input", function () {
  const valor = this.value.trim();
  
  // Limpiar el timeout anterior
  clearTimeout(timeoutBusqueda);
  
  if (valor.length >= 4) {
    // Esperar 800ms después de que el usuario deje de escribir
    timeoutBusqueda = setTimeout(() => {
      fetch("", {
        method: "POST",
        body: new URLSearchParams({ nombreCliente: valor, es_ajax: 'cliente' }),
        headers: { "Content-Type": "application/x-www-form-urlencoded" }
      })
      .then(res => res.json())
      .then(data => {
        if (data.identificacion && data.nombre && data.nombre !== "No encontrado" && data.nombre !== "") {
          inputCedula.value = data.identificacion;
          obtenerDatosCartera(data.identificacion);
        }
        // Si no encuentra, no hacer nada (no mostrar alerta aún)
      })
      .catch(console.error);
    }, 200); // Espera 800 milisegundos
  }
});
 // Función para obtener datos de cartera y agregar a la tabla

function obtenerDatosCartera(cedula) {
  // Verificar si el cliente ya está en la tabla
  if (clientesAgregados.includes(cedula)) {
    Swal.fire({
      icon: 'warning',
      title: 'Cliente duplicado',
      text: 'Este cliente ya está en la tabla',
      timer: 2000,
      showConfirmButton: false
    });
    return;
  }

  fetch(window.location.href, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=fetchCliente&cedula=' + encodeURIComponent(cedula)
  })
  .then(response => response.json())
  .then(data => {
    console.log('Datos recibidos:', data);
    
    if (data.error) {
      console.error('Error del servidor:', data.error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al consultar los datos: ' + data.error
      });
      return;
    }

    // Validar que los datos sean números válidos
    const totalFacturado = parseFloat(data.totalFacturado) || 0;
    const valorAnticipos = parseFloat(data.valorAnticipos) || 0;
    const abonosRealizados = parseFloat(data.abonosRealizados) || 0;
    const saldoCobrar = parseFloat(data.saldoCobrar) || 0;

    // *** VALIDACIÓN 1: Cliente no encontrado en la base de datos ***
    if (!data.nombre || data.nombre === '') {
      Swal.fire({
        icon: 'warning',
        title: 'Cliente no encontrado',
        text: 'No se encontraron datos para este cliente en el sistema',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3085d6'
      });
      inputCedula.value = '';
      inputNombre.value = '';
      return;
    }

    // *** VALIDACIÓN 2: Cliente existe pero no tiene facturas de venta ***
    if (totalFacturado === 0 && valorAnticipos === 0 && abonosRealizados === 0) {
      Swal.fire({
        icon: 'info',
        title: 'Sin facturas registradas',
        html: `
          <p>El cliente <strong>${data.nombre}</strong> no tiene facturas de venta registradas.</p>
          <p>Por favor, registre una factura de venta para este cliente antes de continuar.</p>
        `,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3085d6',
        width: '500px'
      });
      
      inputCedula.value = '';
      inputNombre.value = '';
      return;
    }

    // Si pasa todas las validaciones, agregar el cliente
    clientesAgregados.push(cedula);
    agregarFilaCliente(cedula, data.nombre, totalFacturado, valorAnticipos, abonosRealizados, saldoCobrar);
    actualizarTotales();

    // Limpiar campos de búsqueda
    inputCedula.value = '';
    inputNombre.value = '';

    // Mostrar mensaje de éxito
    Swal.fire({
      icon: 'success',
      title: 'Cliente agregado',
      text: 'Cliente agregado correctamente a la tabla',
      timer: 1500,
      showConfirmButton: false
    });
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

// Función para agregar una fila a la tabla
function agregarFilaCliente(cedula, nombre, totalFacturado, valorAnticipos, abonosRealizados, saldoCobrar) {
  const tbody = document.getElementById('tablaClientes');
  const fila = document.createElement('tr');
  fila.setAttribute('data-cedula', cedula);
  
  fila.innerHTML = `
    <td>${cedula}</td>
    <td>${nombre}</td>
    <td class="total-facturado">${formatearMoneda(totalFacturado)}</td>
    <td class="valor-anticipos">${formatearMoneda(valorAnticipos)}</td>
    <td class="abonos-realizados">${formatearMoneda(abonosRealizados)}</td>
    <td class="saldo-cobrar">${formatearMoneda(saldoCobrar)}</td>
    <td>
      <button type="button" class="btn-eliminar" onclick="eliminarFila('${cedula}')">
        <i class="fas fa-trash"></i> Eliminar
      </button>
    </td>
  `;
  
  tbody.appendChild(fila);
}

// Función para eliminar una fila
function eliminarFila(cedula) {
  Swal.fire({
    title: '¿Está seguro?',
    text: "¿Desea eliminar este cliente de la tabla?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      // Eliminar del array
      const index = clientesAgregados.indexOf(cedula);
      if (index > -1) {
        clientesAgregados.splice(index, 1);
      }

      // Eliminar fila del DOM
      const fila = document.querySelector(`tr[data-cedula="${cedula}"]`);
      if (fila) {
        fila.remove();
      }

      // Actualizar totales
      actualizarTotales();

      Swal.fire(
        'Eliminado',
        'El cliente ha sido eliminado de la tabla',
        'success'
      );
    }
  });
}

// Función para limpiar toda la tabla
function limpiarTabla() {
  if (clientesAgregados.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'Tabla vacía',
      text: 'No hay clientes en la tabla para limpiar'
    });
    return;
  }

  Swal.fire({
    title: '¿Está seguro?',
    text: "Se eliminarán todos los clientes de la tabla",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, limpiar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      clientesAgregados = [];
      document.getElementById('tablaClientes').innerHTML = '';
      actualizarTotales();
      
      Swal.fire(
        'Limpiado',
        'La tabla ha sido limpiada',
        'success'
      );
    }
  });
}


// Función para actualizar totales
function actualizarTotales() {
  let totalFacturado = 0;
  let totalAnticipos = 0;
  let totalAbonos = 0;
  let totalSaldo = 0;

  const filas = document.querySelectorAll('#tablaClientes tr');
  filas.forEach(fila => {
    const facturado = parseFloat(fila.querySelector('.total-facturado').textContent.replace(/,/g, '')) || 0;
    const anticipos = parseFloat(fila.querySelector('.valor-anticipos').textContent.replace(/,/g, '')) || 0;
    const abonos = parseFloat(fila.querySelector('.abonos-realizados').textContent.replace(/,/g, '')) || 0;
    const saldo = parseFloat(fila.querySelector('.saldo-cobrar').textContent.replace(/,/g, '')) || 0;

    totalFacturado += facturado;
    totalAnticipos += anticipos;
    totalAbonos += abonos;
    totalSaldo += saldo;
  });

  document.getElementById('totalFacturadoSum').textContent = formatearMoneda(totalFacturado);
  document.getElementById('totalAnticiposSum').textContent = formatearMoneda(totalAnticipos);
  document.getElementById('totalAbonosSum').textContent = formatearMoneda(totalAbonos);
  document.getElementById('totalSaldoSum').textContent = formatearMoneda(totalSaldo);
}

    // Función para formatear valores monetarios
    function formatearMoneda(valor) {
      if (isNaN(valor) || valor === null || valor === undefined) {
        return '0.00';
      }
      return parseFloat(valor).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Función para generar PDF
  function generarPDF() {
    if (clientesAgregados.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Tabla vacía',
        text: 'No hay clientes en la tabla para generar el PDF'
      });
      return;
    }

    const fechaCorte = document.getElementById('fecha').value;
    
    const identificaciones = [];
    const nombres = [];
    const totalesFacturado = [];
    const valoresAnticipos = [];
    const abonosRealizados = [];
    const saldosCobrar = [];
    
    let totalFacturado = 0;
    let totalAnticipos = 0;
    let totalAbonos = 0;
    let totalSaldo = 0;

    const filas = document.querySelectorAll('#tablaClientes tr');
    filas.forEach(fila => {
      const cedula = fila.querySelector('td:nth-child(1)').textContent;
      const nombre = fila.querySelector('td:nth-child(2)').textContent;
      const facturado = fila.querySelector('.total-facturado').textContent;
      const anticipos = fila.querySelector('.valor-anticipos').textContent;
      const abonos = fila.querySelector('.abonos-realizados').textContent;
      const saldo = fila.querySelector('.saldo-cobrar').textContent;

      identificaciones.push(cedula);
      nombres.push(nombre);
      totalesFacturado.push(facturado);
      valoresAnticipos.push(anticipos);
      abonosRealizados.push(abonos);
      saldosCobrar.push(saldo);

      totalFacturado += parseFloat(facturado.replace(/,/g, '')) || 0;
      totalAnticipos += parseFloat(anticipos.replace(/,/g, '')) || 0;
      totalAbonos += parseFloat(abonos.replace(/,/g, '')) || 0;
      totalSaldo += parseFloat(saldo.replace(/,/g, '')) || 0;
    });

    const form = document.getElementById('formPdf');
    form.innerHTML = '';

    identificaciones.forEach((id, index) => {
      const inputId = document.createElement('input');
      inputId.type = 'hidden';
      inputId.name = 'identificaciones[]';
      inputId.value = identificaciones[index];
      form.appendChild(inputId);

      const inputNombre = document.createElement('input');
      inputNombre.type = 'hidden';
      inputNombre.name = 'nombres[]';
      inputNombre.value = nombres[index];
      form.appendChild(inputNombre);

      const inputFacturado = document.createElement('input');
      inputFacturado.type = 'hidden';
      inputFacturado.name = 'totalFacturado[]';
      inputFacturado.value = totalesFacturado[index];
      form.appendChild(inputFacturado);

      const inputAnticipos = document.createElement('input');
      inputAnticipos.type = 'hidden';
      inputAnticipos.name = 'valorAnticipos[]';
      inputAnticipos.value = valoresAnticipos[index];
      form.appendChild(inputAnticipos);

      const inputAbonos = document.createElement('input');
      inputAbonos.type = 'hidden';
      inputAbonos.name = 'abonosRealizados[]';
      inputAbonos.value = abonosRealizados[index];
      form.appendChild(inputAbonos);

      const inputSaldo = document.createElement('input');
      inputSaldo.type = 'hidden';
      inputSaldo.name = 'saldoCobrar[]';
      inputSaldo.value = saldosCobrar[index];
      form.appendChild(inputSaldo);
    });

    // Agregar totales
    const inputTotalFacturado = document.createElement('input');
    inputTotalFacturado.type = 'hidden';
    inputTotalFacturado.name = 'totalGeneralFacturado';
    inputTotalFacturado.value = formatearMoneda(totalFacturado);
    form.appendChild(inputTotalFacturado);

    const inputTotalAnticipos = document.createElement('input');
    inputTotalAnticipos.type = 'hidden';
    inputTotalAnticipos.name = 'totalGeneralAnticipos';
    inputTotalAnticipos.value = formatearMoneda(totalAnticipos);
    form.appendChild(inputTotalAnticipos);

    const inputTotalAbonos = document.createElement('input');
    inputTotalAbonos.type = 'hidden';
    inputTotalAbonos.name = 'totalGeneralAbonos';
    inputTotalAbonos.value = formatearMoneda(totalAbonos);
    form.appendChild(inputTotalAbonos);

    const inputTotalSaldo = document.createElement('input');
    inputTotalSaldo.type = 'hidden';
    inputTotalSaldo.name = 'totalGeneralSaldo';
    inputTotalSaldo.value = formatearMoneda(totalSaldo);
    form.appendChild(inputTotalSaldo);

    const inputFecha = document.createElement('input');
    inputFecha.type = 'hidden';
    inputFecha.name = 'fechaCorte';
    inputFecha.value = fechaCorte;
    form.appendChild(inputFecha);

    form.submit();

    Swal.fire({
      icon: 'success',
      title: 'PDF generado',
      text: 'El PDF se está generando...',
      timer: 2000,
      showConfirmButton: false
    });
  }

  </script>

</body>
</html>