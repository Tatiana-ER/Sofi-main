<?php
// Solo procesa si viene POST con 'fetchProveedor'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetchProveedor') {
    header('Content-Type: application/json');
    include("connection.php");

    try {
        $conn = new connection();
        $pdo = $conn->connect();

        $cedula = $_POST['cedula'] ?? '';
        $fechaCorte = $_POST['fecha'] ?? date('Y-m-d');

        $response = [
            'nombre' => '',
            'totalAdeudado' => 0,
            'valorPagos' => 0,
            'valorAnticipos' => 0,
            'saldoPagar' => 0,
            'fechaCorte' => $fechaCorte
        ];

        if ($cedula !== '') {
            // 1. Obtener nombre y TOTAL ADEUDADO (facturas a crédito hasta la fecha de corte)
            $stmtCartera = $pdo->prepare("
                SELECT 
                    nombre,
                    COALESCE(SUM(CASE 
                        WHEN (formaPago LIKE '%Credito%' OR formaPago LIKE '%credito%' OR formaPago LIKE '%CREDITO%')
                        AND fecha <= :fechaCorte
                        THEN valorTotal
                        ELSE 0 
                    END), 0) AS totalAdeudado
                FROM facturac 
                WHERE identificacion = :cedula
                GROUP BY nombre
            ");
            $stmtCartera->execute([
                ':cedula' => $cedula,
                ':fechaCorte' => $fechaCorte
            ]);
            
            $rowCartera = $stmtCartera->fetch(PDO::FETCH_ASSOC);

            if ($rowCartera) {
                $response['nombre'] = $rowCartera['nombre'];
                $response['totalAdeudado'] = floatval($rowCartera['totalAdeudado'] ?? 0);
            } else {
                // Si no hay resultados, intentar obtener solo el nombre
                $stmtNombre = $pdo->prepare("SELECT nombre FROM facturac WHERE identificacion = :cedula LIMIT 1");
                $stmtNombre->execute([':cedula' => $cedula]);
                $rowNombre = $stmtNombre->fetch(PDO::FETCH_ASSOC);
                if ($rowNombre) {
                    $response['nombre'] = $rowNombre['nombre'];
                }
            }

            // 2. VALOR PAGOS: Suma de pagos de comprobantes de egreso hasta la fecha de corte
            // Solo se cuentan pagos que NO sean a crédito
            try {
                $stmtPagos = $pdo->prepare("
                    SELECT COALESCE(SUM(valorTotal), 0) AS valorPagos
                    FROM doccomprobanteegreso 
                    WHERE identificacion = :cedula
                    AND (formaPago NOT LIKE '%Credito%' AND formaPago NOT LIKE '%credito%' AND formaPago NOT LIKE '%CREDITO%')
                    AND fecha <= :fechaCorte
                ");
                $stmtPagos->execute([
                    ':cedula' => $cedula,
                    ':fechaCorte' => $fechaCorte
                ]);
                
                $rowPagos = $stmtPagos->fetch(PDO::FETCH_ASSOC);
                $response['valorPagos'] = floatval($rowPagos['valorPagos'] ?? 0);

            } catch (Exception $e) {
                $response['valorPagos'] = 0;
                error_log("ERROR en pagos: " . $e->getMessage());
            }

            // 3. VALOR ANTICIPOS: Suma de débitos en cuenta 1330 (Anticipos y avances) hasta la fecha de corte
            try {
                $stmtAnticipos = $pdo->prepare("
                    SELECT COALESCE(SUM(d.valorDebito), 0) AS valorAnticipos
                    FROM detallecomprobantecontable d
                    INNER JOIN doccomprobantecontable dc ON d.comprobante_id = dc.id
                    WHERE d.cuentaContable LIKE '1330%'
                    AND (d.tercero LIKE CONCAT('%', :cedula, '%') OR d.tercero = :cedula2)
                    AND dc.fecha <= :fechaCorte
                ");
                $stmtAnticipos->execute([
                    ':cedula' => $cedula,
                    ':cedula2' => $cedula,
                    ':fechaCorte' => $fechaCorte
                ]);
                $rowAnticipos = $stmtAnticipos->fetch(PDO::FETCH_ASSOC);
                $response['valorAnticipos'] = floatval($rowAnticipos['valorAnticipos'] ?? 0);
            } catch (Exception $e) {
                $response['valorAnticipos'] = 0;
                error_log("ERROR en anticipos: " . $e->getMessage());
            }

            // 4. SALDO POR PAGAR = Total Adeudado - Valor Pagos - Valor Anticipos
            $response['saldoPagar'] = $response['totalAdeudado'] - $response['valorPagos'] - $response['valorAnticipos'];
            
            // DEBUG: Mostrar todos los valores con fecha
            error_log("DEBUG - Resumen para cédula: $cedula | Fecha corte: $fechaCorte");
            error_log("  - Nombre: {$response['nombre']}");
            error_log("  - Total Adeudado: {$response['totalAdeudado']}");
            error_log("  - Pagos Realizados: {$response['valorPagos']}");
            error_log("  - Anticipos: {$response['valorAnticipos']}");
            error_log("  - Saldo por Pagar: {$response['saldoPagar']}");
        }

        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
    }
    exit;
}

// Búsqueda de proveedor por identificación o nombre (AJAX)
if (isset($_POST['es_ajax']) && $_POST['es_ajax'] == 'proveedor') {
    header('Content-Type: application/json');
    include("connection.php");

    try {
        $conn = new connection();
        $pdo = $conn->connect();

        $ident = $_POST['identificacion'] ?? '';
        $nombreProveedor = $_POST['nombreProveedor'] ?? '';
        $proveedor = null;

        if (!empty($ident)) {
            $stmt = $pdo->prepare("SELECT identificacion, nombre FROM facturac WHERE identificacion = :cedula LIMIT 1");
            $stmt->bindParam(':cedula', $ident, PDO::PARAM_STR);
            $stmt->execute();
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif (!empty($nombreProveedor)) {
            $likeNombre = "%$nombreProveedor%";
            $stmt = $pdo->prepare("SELECT identificacion, nombre FROM facturac WHERE nombre LIKE :nombre LIMIT 1");
            $stmt->bindParam(':nombre', $likeNombre, PDO::PARAM_STR);
            $stmt->execute();
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($proveedor) {
            echo json_encode([
                "nombre" => $proveedor['nombre'],
                "identificacion" => $proveedor['identificacion']
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

  <title>SOFI - Cuánto Debo</title>
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
    .btn-exportar {
      background-color: #17a2b8;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
      cursor: pointer;
      margin-left: 10px;
    }
    .btn-exportar:hover {
      background-color: #138496;
    }
  </style>
</head>

<body>

  <!-- Header -->
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

  <!-- Services Section -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='informesproveedores.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">

      <div class="section-title">
        <h2>CUÁNTO DEBO</h2>
        <p>Consulte el estado de cuentas por pagar a un proveedor específico</p>
      </div>
      
      <div class="row g-3 mt-2">
        <div class="col-md-4">
            <label for="cedula" class="form-label fw-bold">Identificación del Proveedor (NIT o CC)</label>
            <input type="text" class="form-control" id="cedula" name="cedula" placeholder="Ej: 123456789">
        </div>

        <div class="col-md-8">
          <label for="nombre" class="form-label fw-bold">Nombre del Proveedor</label>
          <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del proveedor" readonly>
        </div>
      </div>

      <div class="row g-3 mt-2">
        <div class="col-md-4">
          <label for="fecha" class="form-label fw-bold">Fecha de Corte</label>
          <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>">
        </div>
      </div>

      <form id="formPdf" action="generar_pdf_proveedores.php" method="POST" target="_blank" style="display: none;">
          <input type="hidden" id="datosProveedoresPdf" name="datosProveedores">
          <input type="hidden" id="fechaPdf" name="fecha">
      </form>

      <form id="formExcel" action="exportar_cuanto_debo_excel.php" method="POST" target="_blank" style="display: none;">
          <input type="hidden" id="datosProveedoresExcel" name="datosProveedores">
          <input type="hidden" id="fechaExcel" name="fecha">
      </form>

        <div class="section-title mt-5">
          <h4>ESTADO DE CUENTAS POR PAGAR</h4>
        </div>

        <div class="row">
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Identificación</th>
                  <th>Nombre del Proveedor</th>
                  <th>Total Cartera</th>
                  <th>Pagos Realizados</th>
                  <th>Valor Anticipos</th>
                  <th>Saldo por Pagar</th>
                  <th>Acciones</th>
                </tr> 
              </thead>
              <tbody id="tablaProveedores">
                <!-- Las filas se agregarán dinámicamente aquí -->
              </tbody>
              <tfoot>
                <tr class="total-row">
                  <th colspan="2">TOTAL</th>
                  <th id="totalAdeudadoSum">0.00</th>
                  <th id="totalPagosSum">0.00</th>
                  <th id="totalAnticiposSum">0.00</th>
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
          <button type="button" class="btn btn-primary" onclick="generarPDF()">
            <i class="fas fa-file-pdf"></i> Generar PDF
          </button>
          <button type="button" class="btn btn-success ms-2" onclick="exportarExcel()" style="background-color: #17a2b8; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-left: 10px;">
            <i class="fas fa-file-excel"></i> Generar Excel
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
    // Array para almacenar los proveedores agregados
    let proveedoresAgregados = [];

    // Búsqueda bidireccional de proveedor
    const inputCedula = document.getElementById("cedula");
    const inputNombre = document.getElementById("nombre");

    // Búsqueda por identificación
    inputCedula.addEventListener("input", function () {
      const valor = this.value.trim();
      
      if (valor.length === 0) {
        inputNombre.value = '';
        return;
      }

      if (valor.length > 0) {
        fetch("", {
          method: "POST",
          body: new URLSearchParams({ identificacion: valor, es_ajax: 'proveedor' }),
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
          body: new URLSearchParams({ nombreProveedor: valor, es_ajax: 'proveedor' }),
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

    // Función para obtener datos de cartera y agregar a la tabla
    function obtenerDatosCartera(cedula) {
        // Verificar si el proveedor ya está en la tabla
        if (proveedoresAgregados.includes(cedula)) {
            Swal.fire({
                icon: 'warning',
                title: 'Proveedor duplicado',
                text: 'Este proveedor ya está en la tabla',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        // Obtener la fecha de corte del campo fecha
        const fechaCorte = document.getElementById('fecha').value;
        
        // Validar que la fecha esté seleccionada
        if (!fechaCorte) {
            Swal.fire({
                icon: 'error',
                title: 'Fecha requerida',
                text: 'Por favor seleccione una fecha de corte'
            });
            return;
        }

        // Crear objeto FormData para enviar los datos
        const formData = new URLSearchParams();
        formData.append('action', 'fetchProveedor');
        formData.append('cedula', cedula);
        formData.append('fecha', fechaCorte);

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Datos recibidos:', data);
            console.log('Fecha de corte utilizada:', fechaCorte);
            
            if (data.error) {
                console.error('Error del servidor:', data.error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al consultar los datos: ' + data.error
                });
                return;
            }

            if (!data.nombre) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Proveedor no encontrado',
                    text: 'No se encontraron datos para este proveedor hasta la fecha ' + fechaCorte
                });
                return;
            }

            // Validar que los datos sean números válidos
            const totalAdeudado = parseFloat(data.totalAdeudado) || 0;
            const valorPagos = parseFloat(data.valorPagos) || 0;
            const valorAnticipos = parseFloat(data.valorAnticipos) || 0;
            const saldoPagar = parseFloat(data.saldoPagar) || 0;

            // Agregar proveedor al array
            proveedoresAgregados.push(cedula);

            // Agregar fila a la tabla
            agregarFilaProveedor(cedula, data.nombre, totalAdeudado, valorPagos, valorAnticipos, saldoPagar);

            // Actualizar totales
            actualizarTotales();

            // Limpiar campos de búsqueda
            inputCedula.value = '';
            inputNombre.value = '';

            // Mostrar mensaje de éxito con fecha
            Swal.fire({
                icon: 'success',
                title: 'Proveedor agregado',
                text: `Proveedor agregado correctamente. Datos hasta: ${fechaCorte}`,
                timer: 2000,
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
    function agregarFilaProveedor(cedula, nombre, totalAdeudado, valorPagos, valorAnticipos, saldoPagar) {
      const tbody = document.getElementById('tablaProveedores');
      const fila = document.createElement('tr');
      fila.setAttribute('data-cedula', cedula);
      
      fila.innerHTML = `
        <td>${cedula}</td>
        <td>${nombre}</td>
        <td class="total-adeudado">${formatearMoneda(totalAdeudado)}</td>
        <td class="valor-pagos">${formatearMoneda(valorPagos)}</td>
        <td class="valor-anticipos">${formatearMoneda(valorAnticipos)}</td>
        <td class="saldo-pagar">${formatearMoneda(saldoPagar)}</td>
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
        text: "¿Desea eliminar este proveedor de la tabla?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          // Eliminar del array
          const index = proveedoresAgregados.indexOf(cedula);
          if (index > -1) {
            proveedoresAgregados.splice(index, 1);
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
            'El proveedor ha sido eliminado de la tabla',
            'success'
          );
        }
      });
    }

    // Función para limpiar toda la tabla
    function limpiarTabla() {
      if (proveedoresAgregados.length === 0) {
        Swal.fire({
          icon: 'info',
          title: 'Tabla vacía',
          text: 'No hay proveedores en la tabla para limpiar'
        });
        return;
      }

      Swal.fire({
        title: '¿Está seguro?',
        text: "Se eliminarán todos los proveedores de la tabla",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          proveedoresAgregados = [];
          document.getElementById('tablaProveedores').innerHTML = '';
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
      let totalAdeudado = 0;
      let totalPagos = 0;
      let totalAnticipos = 0;
      let totalSaldo = 0;

      const filas = document.querySelectorAll('#tablaProveedores tr');
      filas.forEach(fila => {
        const adeudado = parseFloat(fila.querySelector('.total-adeudado').textContent.replace(/,/g, '')) || 0;
        const pagos = parseFloat(fila.querySelector('.valor-pagos').textContent.replace(/,/g, '')) || 0;
        const anticipos = parseFloat(fila.querySelector('.valor-anticipos').textContent.replace(/,/g, '')) || 0;
        const saldo = parseFloat(fila.querySelector('.saldo-pagar').textContent.replace(/,/g, '')) || 0;

        totalAdeudado += adeudado;
        totalPagos += pagos;
        totalAnticipos += anticipos;
        totalSaldo += saldo;
      });

      document.getElementById('totalAdeudadoSum').textContent = formatearMoneda(totalAdeudado);
      document.getElementById('totalPagosSum').textContent = formatearMoneda(totalPagos);
      document.getElementById('totalAnticiposSum').textContent = formatearMoneda(totalAnticipos);
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
      // Verificar que haya proveedores en la tabla
      if (proveedoresAgregados.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Tabla vacía',
          text: 'No hay proveedores en la tabla para generar el PDF'
        });
        return;
      }

      // Obtener fecha de corte
      const fechaCorte = document.getElementById('fecha').value;
      
      // Preparar datos para el PDF
      const datosPDF = {
        proveedores: [],
        totales: {
          totalAdeudado: 0,
          totalPagos: 0,
          totalAnticipos: 0,
          totalSaldo: 0
        }
      };

      // Recorrer todas las filas de la tabla
      const filas = document.querySelectorAll('#tablaProveedores tr');
      filas.forEach(fila => {
        const cedula = fila.querySelector('td:nth-child(1)').textContent;
        const nombre = fila.querySelector('td:nth-child(2)').textContent;
        const totalAdeudado = parseFloat(fila.querySelector('.total-adeudado').textContent.replace(/,/g, '')) || 0;
        const valorPagos = parseFloat(fila.querySelector('.valor-pagos').textContent.replace(/,/g, '')) || 0;
        const valorAnticipos = parseFloat(fila.querySelector('.valor-anticipos').textContent.replace(/,/g, '')) || 0;
        const saldoPagar = parseFloat(fila.querySelector('.saldo-pagar').textContent.replace(/,/g, '')) || 0;

        datosPDF.proveedores.push({
          identificacion: cedula,
          nombre: nombre,
          totalAdeudado: totalAdeudado,
          valorPagos: valorPagos,
          valorAnticipos: valorAnticipos,
          saldoPagar: saldoPagar
        });

        // Acumular totales
        datosPDF.totales.totalAdeudado += totalAdeudado;
        datosPDF.totales.totalPagos += valorPagos;
        datosPDF.totales.totalAnticipos += valorAnticipos;
        datosPDF.totales.totalSaldo += saldoPagar;
      });

      // Formatear totales para evitar decimales largos
      datosPDF.totales.totalAdeudado = parseFloat(datosPDF.totales.totalAdeudado.toFixed(2));
      datosPDF.totales.totalPagos = parseFloat(datosPDF.totales.totalPagos.toFixed(2));
      datosPDF.totales.totalAnticipos = parseFloat(datosPDF.totales.totalAnticipos.toFixed(2));
      datosPDF.totales.totalSaldo = parseFloat(datosPDF.totales.totalSaldo.toFixed(2));

      console.log('Datos enviados al PDF:', datosPDF);

      // Enviar datos al formulario oculto
      document.getElementById('datosProveedoresPdf').value = JSON.stringify(datosPDF);
      document.getElementById('fechaPdf').value = fechaCorte;

      // Enviar formulario
      document.getElementById('formPdf').submit();

      // Mostrar mensaje de éxito
      Swal.fire({
        icon: 'success',
        title: 'PDF generado',
        text: 'El PDF se está generando...',
        timer: 2000,
        showConfirmButton: false
      });
    }

    // Función para exportar a Excel
    function exportarExcel() {
      // Verificar que haya proveedores en la tabla
      if (proveedoresAgregados.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Tabla vacía',
          text: 'No hay proveedores en la tabla para exportar a Excel'
        });
        return;
      }

      // Obtener fecha de corte
      const fechaCorte = document.getElementById('fecha').value;
      
      // Preparar datos para Excel (misma estructura que PDF)
      const datosExcel = {
        proveedores: [],
        totales: {
          totalAdeudado: 0,
          totalPagos: 0,
          totalAnticipos: 0,
          totalSaldo: 0
        }
      };

      // Recorrer todas las filas de la tabla
      const filas = document.querySelectorAll('#tablaProveedores tr');
      filas.forEach(fila => {
        const cedula = fila.querySelector('td:nth-child(1)').textContent;
        const nombre = fila.querySelector('td:nth-child(2)').textContent;
        const totalAdeudado = parseFloat(fila.querySelector('.total-adeudado').textContent.replace(/,/g, '')) || 0;
        const valorPagos = parseFloat(fila.querySelector('.valor-pagos').textContent.replace(/,/g, '')) || 0;
        const valorAnticipos = parseFloat(fila.querySelector('.valor-anticipos').textContent.replace(/,/g, '')) || 0;
        const saldoPagar = parseFloat(fila.querySelector('.saldo-pagar').textContent.replace(/,/g, '')) || 0;

        datosExcel.proveedores.push({
          identificacion: cedula,
          nombre: nombre,
          totalAdeudado: totalAdeudado,
          valorPagos: valorPagos,
          valorAnticipos: valorAnticipos,
          saldoPagar: saldoPagar
        });

        // Acumular totales
        datosExcel.totales.totalAdeudado += totalAdeudado;
        datosExcel.totales.totalPagos += valorPagos;
        datosExcel.totales.totalAnticipos += valorAnticipos;
        datosExcel.totales.totalSaldo += saldoPagar;
      });

      // Formatear totales
      datosExcel.totales.totalAdeudado = parseFloat(datosExcel.totales.totalAdeudado.toFixed(2));
      datosExcel.totales.totalPagos = parseFloat(datosExcel.totales.totalPagos.toFixed(2));
      datosExcel.totales.totalAnticipos = parseFloat(datosExcel.totales.totalAnticipos.toFixed(2));
      datosExcel.totales.totalSaldo = parseFloat(datosExcel.totales.totalSaldo.toFixed(2));

      console.log('Datos enviados a Excel:', datosExcel);

      // Enviar datos al formulario oculto de Excel
      document.getElementById('datosProveedoresExcel').value = JSON.stringify(datosExcel);
      document.getElementById('fechaExcel').value = fechaCorte;

      // Enviar formulario
      document.getElementById('formExcel').submit();

      // Mostrar mensaje de éxito
      Swal.fire({
        icon: 'success',
        title: 'Excel generado',
        text: 'El archivo Excel se está generando...',
        timer: 2000,
        showConfirmButton: false
      });
    }
  </script>

</body>
</html>