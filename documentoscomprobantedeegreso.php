
<?php
include("connection.php");
 
$conn = new connection();
$pdo = $conn->connect();

$txtId = (isset($_POST['txtId'])) ? $_POST['txtId'] : "";
$fecha = (isset($_POST['fecha'])) ? $_POST['fecha'] : "";
$consecutivo = (isset($_POST['consecutivo'])) ? $_POST['consecutivo'] : "";
$identificacion = (isset($_POST['identificacion'])) ? $_POST['identificacion'] : "";
$nombre = (isset($_POST['nombre'])) ? $_POST['nombre'] : "";
$formaPago = (isset($_POST['formaPago'])) ? $_POST['formaPago'] : "";
$valorTotal = (isset($_POST['valorTotal'])) ? $_POST['valorTotal'] : "";
$observaciones = (isset($_POST['observaciones'])) ? $_POST['observaciones'] : "";

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : "";

// Obtener el siguiente consecutivo 
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(consecutivo) AS ultimo FROM doccomprobanteegreso");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

// Si vienen detalles como JSON (desde el form)
if (isset($_POST['detalles'])) {
    $_POST['detalles'] = json_decode($_POST['detalles'], true);
}

switch ($accion) {
    case "btnAgregar":
        try {
            // Insertar encabezado
            $stmt = $pdo->prepare("INSERT INTO doccomprobanteegreso (consecutivo, fecha, identificacion, nombreProveedor, formaPago, valorTotal, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$consecutivo, $fecha, $identificacion, $nombre, $formaPago, $valorTotal, $observaciones]);
     
            $reciboId = $pdo->lastInsertId();
     
            // Detalles de las facturas
            if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
                foreach ($_POST['detalles'] as $detalle) {
                    if (!empty($detalle['numeroFactura'])) {
                        $stmt = $pdo->prepare("INSERT INTO detalle_doccomprobanteegreso (recibo_id, numeroFactura, fechaVencimiento, valor) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $reciboId, 
                            $detalle['numeroFactura'], 
                            $detalle['fechaVencimiento'] ?? null, 
                            $detalle['valor']
                        ]);
                    }
                }
            }
     
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=agregado");
            exit;
        } catch (PDOException $e) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
            exit;
        }
    break;

    case "btnModificar":
        try {
            $stmt = $pdo->prepare("UPDATE doccomprobanteegreso 
                                  SET consecutivo = ?, 
                                      fecha = ?, 
                                      identificacion = ?, 
                                      nombreProveedor = ?, 
                                      formaPago = ?, 
                                      valorTotal = ?, 
                                      observaciones = ? 
                                  WHERE id = ?");
            $stmt->execute([$consecutivo, $fecha, $identificacion, $nombre, $formaPago, $valorTotal, $observaciones, $txtId]);

            // Eliminar detalles antiguos
            $deleteDetalle = $pdo->prepare("DELETE FROM detalle_doccomprobanteegreso WHERE recibo_id = ?");
            $deleteDetalle->execute([$txtId]);

            // Insertar nuevos detalles
            if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
                foreach ($_POST['detalles'] as $detalle) {
                    if (!empty($detalle['numeroFactura'])) {
                        $stmt = $pdo->prepare("INSERT INTO detalle_doccomprobanteegreso (recibo_id, numeroFactura, fechaVencimiento, valor) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $txtId, 
                            $detalle['numeroFactura'], 
                            $detalle['fechaVencimiento'] ?? null, 
                            $detalle['valor']
                        ]);
                    }
                }
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=modificado");
            exit;
        } catch (PDOException $e) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
            exit;
        }
    break;

    case "btnEliminar":
        try {
            // Eliminar detalles relacionados
            $delDetalle = $pdo->prepare("DELETE FROM detalle_doccomprobanteegreso WHERE recibo_id = ?");
            $delDetalle->execute([$txtId]);

            // Eliminar comprobante
            $sentencia = $pdo->prepare("DELETE FROM doccomprobanteegreso WHERE id = ?");
            $sentencia->execute([$txtId]);

            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=eliminado");
            exit;
        } catch (PDOException $e) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
            exit;
        }
    break;

    case "btnEditar":
        // Cargar datos del comprobante
        $sentencia = $pdo->prepare("SELECT * FROM doccomprobanteegreso WHERE id = ?");
        $sentencia->execute([$txtId]);
        $comprobante = $sentencia->fetch(PDO::FETCH_ASSOC);

        if ($comprobante) {
            $fecha = $comprobante['fecha'];
            $consecutivo = $comprobante['consecutivo'];
            $identificacion = $comprobante['identificacion'];
            $nombre = $comprobante['nombreProveedor'];
            $formaPago = $comprobante['formaPago'];
            $valorTotal = $comprobante['valorTotal'];
            $observaciones = $comprobante['observaciones'];
        }

        // Cargar detalles asociados
        $stmtDetalle = $pdo->prepare("SELECT * FROM detalle_doccomprobanteegreso WHERE recibo_id = ?");
        $stmtDetalle->execute([$txtId]);
        $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
    break;
}

// Búsqueda proveedor y facturas a crédito (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['identificacion']) && !isset($_POST['accion'])) {
    $identificacion = $_POST['identificacion'];
 
    // Consultar nombre del proveedor
    $stmt = $pdo->prepare("SELECT nombres, apellidos FROM catalogosterceros WHERE cedula = :cedula AND tipoTercero = 'proveedor'");
    $stmt->bindParam(':cedula', $identificacion, PDO::PARAM_INT);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
 
    $nombreCompleto = $cliente ? $cliente['nombres'] . " " . $cliente['apellidos'] : "No encontrado o no es un proveedor";
 
    // Obtener TODAS las facturas a crédito del proveedor que contengan "Credito" en formaPago
    $stmt = $pdo->prepare("
        SELECT 
            numeroFactura, 
            valorTotal, 
            fecha, 
            consecutivo
        FROM facturac 
        WHERE identificacion = :identificacion 
        AND formaPago LIKE '%Credito%'
        ORDER BY fecha DESC
    ");
    $stmt->bindParam(':identificacion', $identificacion, PDO::PARAM_INT);
    $stmt->execute();
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    echo json_encode([
        "nombre" => $nombreCompleto,
        "facturas" => $facturas
    ]);
    exit;
}
 
// Visualizar métodos de pago
$mediosPago = [];
$stmt = $pdo->query("SELECT metodoPago, cuentaContable FROM mediosdepago");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mediosPago[] = $row;
}

// Lista de comprobantes de egreso
$sentencia = $pdo->prepare("SELECT * FROM doccomprobanteegreso WHERE 1");
$sentencia->execute();
$lista = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Mantener los valores cargados en edición
if (isset($comprobante) && !empty($comprobante)) {
    $txtId = $comprobante['id'];
}
?>

<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  switch ("<?= $_GET['msg'] ?>") {
    case "agregado":
      Swal.fire({
        icon: 'success',
        title: 'Guardado exitosamente',
        text: 'El comprobante de egreso se ha agregado correctamente',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "modificado":
      Swal.fire({
        icon: 'success',
        title: 'Modificado correctamente',
        text: 'Los datos se actualizaron con éxito',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "eliminado":
      Swal.fire({
        icon: 'success',
        title: 'Eliminado correctamente',
        text: 'El comprobante de egreso fue eliminado del registro',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "error":
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Ocurrió un error al procesar la solicitud',
        confirmButtonColor: '#d33'
      });
      break;
  }

  // Quita el parámetro ?msg=... de la URL sin recargar
  if (window.history.replaceState) {
    const url = new URL(window.location);
    url.searchParams.delete('msg');
    url.searchParams.delete('detalle');
    window.history.replaceState({}, document.title, url);
  }
});
</script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="es">
 
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
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i%22 rel="stylesheet">
 
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
    input[type="text"], input[type="number"], input[type="date"] {
      width: 100%;
      box-sizing: border-box;
      padding: 5px;
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
      background-color: #f2f2f2;
      font-weight: bold;
    }
 
    .btn-add, .btn-remove {
      cursor: pointer;
      color: white;
      border: none;
      padding: 5px 10px;
      font-size: 16px;
      border-radius: 4px;
    }

    .btn-add {
      background-color: #0d6efd;
    }

    .btn-remove {
      background-color: #dc3545;
      margin-left: 5px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      font-weight: bold;
      display: inline-block;
      width: 150px;
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
    <button class="btn-ir" onclick="window.location.href='menudocumentos.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">
 
      <div class="section-title">
        <h2>COMPROBANTE DE EGRESO</h2>
        <p>Para crear un nuevo comprobante de egreso diligencie los campos a continuación:</p>
        <p>(Los campos marcados con * son obligatorios)</p>
      </div>
       
      <form id="formComprobanteEgreso" method="POST" action="">
        <input type="hidden" id="txtId" name="txtId" value="<?php echo htmlspecialchars($txtId); ?>">

        <div class="row g-3">
          <div class="col-md-4">
            <label for="fecha" class="form-label fw-bold">Fecha de documento*</label>
            <input type="date" name="fecha" class="form-control" id="fecha" value="<?php echo htmlspecialchars($fecha); ?>" required>
          </div>

          <div class="col-md-4">
            <label for="consecutivo" class="form-label fw-bold">Consecutivo</label>
            <input type="text" name="consecutivo" class="form-control" id="consecutivo" value="<?php echo htmlspecialchars($consecutivo); ?>" readonly>
          </div>
        </div>

        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="identificacion" class="form-label fw-bold">Identificación del proveedor (NIT o CC)*</label>
            <input type="number" name="identificacion" class="form-control" id="identificacion" value="<?php echo htmlspecialchars($identificacion); ?>" required>
          </div>

          <div class="col-md-8">
            <label for="nombre" class="form-label fw-bold">Nombre del proveedor</label>
            <input type="text" name="nombre" class="form-control" id="nombre" value="<?php echo htmlspecialchars($nombre); ?>" readonly>
          </div>
        </div>

        <div class="mt-3">
          <h5>Facturas a Crédito del Proveedor</h5>
          <div class="table-responsive table-container">
            <table>
              <thead class="table-primary text-center">
                <tr>
                  <th>Número de factura</th>
                  <th>Fecha de vencimiento</th>
                  <th>Valor</th>
                  <th>Seleccionar</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody id="product-table">
                <?php if (!empty($detalles)) : ?>
                  <?php foreach ($detalles as $index => $detalle): ?>
                    <tr>
                      <td><input type="text" name="numeroFactura" value="<?= htmlspecialchars($detalle['numeroFactura']) ?>"></td>
                      <td><input type="date" name="fechaVencimiento" value="<?= htmlspecialchars($detalle['fechaVencimiento']) ?>"></td>
                      <td><input type="number" name="valor" class="valor-factura" value="<?= htmlspecialchars($detalle['valor']) ?>" step="0.01" oninput="calcularTotal()"></td>
                      <td><input type="checkbox" name="seleccionado" class="checkbox-factura" checked onchange="calcularTotal()"></td>
                      <td>
                        <?php if ($index === 0): ?>
                          <button type="button" class="btn-add" onclick="addRow()">+</button>
                        <?php else: ?>
                          <button type="button" class="btn-remove" onclick="deleteRow(this)">-</button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td><input type="text" name="numeroFactura" placeholder="Número de factura"></td>
                    <td><input type="date" name="fechaVencimiento"></td>
                    <td><input type="number" name="valor" class="valor-factura" placeholder="Valor" step="0.01" oninput="calcularTotal()"></td>
                    <td><input type="checkbox" name="seleccionado" class="checkbox-factura" onchange="calcularTotal()"></td>
                    <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="valorTotal" class="form-label fw-bold">VALOR TOTAL</label>
            <input type="text" id="valorTotal" name="valorTotal" class="form-control text-end fw-bold" value="<?php echo htmlspecialchars($valorTotal); ?>" readonly>
          </div>

          <div class="col-md-6">
            <label for="formaPago" class="form-label fw-bold">FORMA DE PAGO*</label>
            <select type="text" id="formaPago" name="formaPago" class="form-select" required>
              <option value="">Seleccione una opción</option>
              <?php foreach ($mediosPago as $medio): ?>
                <option value="<?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>"
                  <?= ($formaPago == $medio['metodoPago'] . ' - ' . $medio['cuentaContable']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-3 mt-3">
          <label for="observaciones" class="form-label fw-bold">Observaciones</label>
          <input type="text" name="observaciones" class="form-control" id="observaciones" value="<?php echo htmlspecialchars($observaciones); ?>">
        </div>

        <div class="mt-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion">Modificar</button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion">Eliminar</button>
          <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
        </div>
      </form>

      <!-- Lista de comprobantes -->
      <div class="row mt-4">
        <div class="table-container">
          <h4>Comprobantes de Egreso Registrados</h4>
          <table>
            <thead>
              <tr>
                <th>Consecutivo</th>
                <th>Fecha</th>
                <th>Identificación</th>
                <th>Proveedor</th>
                <th>Forma Pago</th>
                <th>Valor Total</th>
                <th>Observaciones</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody id="tabla-registros">
            <?php foreach($lista as $comprobante){ ?>
              <tr>
                <td><?php echo htmlspecialchars($comprobante['consecutivo']); ?></td>
                <td><?php echo htmlspecialchars($comprobante['fecha']); ?></td>
                <td><?php echo htmlspecialchars($comprobante['identificacion']); ?></td>
                <td><?php echo htmlspecialchars($comprobante['nombreProveedor']); ?></td>
                <td><?php echo htmlspecialchars($comprobante['formaPago']); ?></td>
                <td><?php echo htmlspecialchars($comprobante['valorTotal']); ?></td>
                <td><?php echo htmlspecialchars($comprobante['observaciones']); ?></td>
                <td>
                  <form action="" method="post" class="form-tabla-accion" style="display: inline;">
                    <input type="hidden" name="txtId" value="<?php echo $comprobante['id']; ?>" >
                    <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button type="submit" name="accion" value="btnEliminar" class="btn btn-sm btn-danger" title="Eliminar">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>

      <script>
        // Obtener consecutivo al cargar la página SOLO si no hay ID (modo agregar)
        window.addEventListener('DOMContentLoaded', function() {
            const txtId = document.getElementById("txtId").value;
            
            if (!txtId || txtId.trim() === "") {
                fetch(window.location.pathname + "?get_consecutivo=1")
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('consecutivo').value = data.consecutivo;
                    })
                    .catch(error => console.error('Error al obtener consecutivo:', error));
            }
        });
      </script>
      <br>
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
 
</body>
 
</html>

        // Buscar proveedor y sus facturas a crédito
        document.getElementById("identificacion").addEventListener("input", function () {
            let identificacion = this.value;
 
            if (identificacion.length > 0) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ identificacion: identificacion }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("nombre").value = data.nombre;
 
                    const tableBody = document.getElementById("product-table");
                    tableBody.innerHTML = "";
 
                    if (data.facturas && data.facturas.length > 0) {
                        data.facturas.forEach((factura, index) => {
                            const newRow = document.createElement("tr");
                            newRow.innerHTML = `
                                <td><input type="text" name="numeroFactura" value="${factura.numeroFactura || ''}" readonly></td>
                                <td><input type="date" name="fechaVencimiento" value="${factura.fecha || ''}"></td>
                                <td><input type="number" name="valor" class="valor-factura" value="${factura.valorTotal || ''}" step="0.01" readonly oninput="calcularTotal()"></td>
                                <td><input type="checkbox" name="seleccionado" class="checkbox-factura" onchange="calcularTotal()"></td>
                                <td>
                                    ${index === 0 ? '<button type="button" class="btn-add" onclick="addRow()">+</button>' : '<button type="button" class="btn-remove" onclick="deleteRow(this)">-</button>'}
                                </td>
                            `;
                            tableBody.appendChild(newRow);
                        });
                    } else {
                        const emptyRow = document.createElement("tr");
                        emptyRow.innerHTML = `
                            <td><input type="text" name="numeroFactura" placeholder="Número de factura"></td>
                            <td><input type="date" name="fechaVencimiento"></td>
                            <td><input type="number" name="valor" class="valor-factura" placeholder="Valor" step="0.01" oninput="calcularTotal()"></td>
                            <td><input type="checkbox" name="seleccionado" class="checkbox-factura" onchange="calcularTotal()"></td>
                            <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                        `;
                        tableBody.appendChild(emptyRow);
                    }

                    calcularTotal();
                })
                .catch(error => {
                    console.error("Error en la consulta:", error);
                    document.getElementById("nombre").value = "Error al buscar proveedor";
                });
            } else {
                document.getElementById("nombre").value = "";
                document.getElementById("valorTotal").value = "";
                
                const tableBody = document.getElementById("product-table");
                tableBody.innerHTML = `
                    <tr>
                        <td><input type="text" name="numeroFactura" placeholder="Número de factura"></td>
                        <td><input type="date" name="fechaVencimiento"></td>
                        <td><input type="number" name="valor" class="valor-factura" placeholder="Valor" step="0.01" oninput="calcularTotal()"></td>
                        <td><input type="checkbox" name="seleccionado" class="checkbox-factura" onchange="calcularTotal()"></td>
                        <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                    </tr>
                `;
            }
        });

        // Calcular el total de las facturas seleccionadas
        function calcularTotal() {
            const checkboxes = document.querySelectorAll('.checkbox-factura');
            let total = 0;

            checkboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    const valorInput = document.querySelectorAll('.valor-factura')[index];
                    const valor = parseFloat(valorInput.value) || 0;
                    total += valor;
                }
            });

            document.getElementById('valorTotal').value = total.toFixed(2);
        }
 
        // Función para agregar una nueva fila vacía
        function addRow() {
            const tableBody = document.getElementById("product-table");
            const newRow = document.createElement("tr");
            
            newRow.innerHTML = `
                <td><input type="text" name="numeroFactura" placeholder="Número de factura"></td>
                <td><input type="date" name="fechaVencimiento"></td>
                <td><input type="number" name="valor" class="valor-factura" placeholder="Valor" step="0.01" oninput="calcularTotal()"></td>
                <td><input type="checkbox" name="seleccionado" class="checkbox-factura" onchange="calcularTotal()"></td>
                <td><button type="button" class="btn-remove" onclick="deleteRow(this)">-</button></td>
            `;
            
            tableBody.appendChild(newRow);
        }

        // Función para eliminar una fila
        function deleteRow(button) {
            const row = button.closest('tr');
            const rows = document.querySelectorAll("#product-table tr");
            if (rows.length > 1) {
                row.remove();
                calcularTotal();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe haber al menos una fila',
                    confirmButtonColor: '#3085d6'
                });
            }
        }

        // Alternar botones y comportamiento cancelar
        document.addEventListener("DOMContentLoaded", function() {
            const id = document.getElementById("txtId").value;
            const btnAgregar = document.getElementById("btnAgregar");
            const btnModificar = document.getElementById("btnModificar");
            const btnEliminar = document.getElementById("btnEliminar");
            const btnCancelar = document.getElementById("btnCancelar");
            const form = document.getElementById("formComprobanteEgreso");

            function modoAgregar() {
                btnAgregar.style.display = "inline-block";
                btnModificar.style.display = "none";
                btnEliminar.style.display = "none";
                btnCancelar.style.display = "none";

                document.getElementById("txtId").value = "";
                document.getElementById("identificacion").value = "";
                document.getElementById("nombre").value = "";
                document.getElementById("fecha").value = "";
                document.getElementById("formaPago").value = "";
                document.getElementById("valorTotal").value = "";
                document.getElementById("observaciones").value = "";

                const tableBody = document.getElementById("product-table");
                tableBody.innerHTML = `
                    <tr>
                        <td><input type="text" name="numeroFactura" placeholder="Número de factura"></td>
                        <td><input type="date" name="fechaVencimiento"></td>
                        <td><input type="number" name="valor" class="valor-factura" placeholder="Valor" step="0.01" oninput="calcularTotal()"></td>
                        <td><input type="checkbox" name="seleccionado" class="checkbox-factura" onchange="calcularTotal()"></td>
                        <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                    </tr>
                `;

                fetch(window.location.pathname + "?get_consecutivo=1")
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('consecutivo').value = data.consecutivo;
                    })
                    .catch(error => console.error('Error al obtener consecutivo:', error));

                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.search = '';
                    window.history.replaceState({}, document.title, url);
                }
            }

            if (id && id.trim() !== "") {
                btnAgregar.style.display = "none";
                btnModificar.style.display = "inline-block";
                btnEliminar.style.display = "inline-block";
                btnCancelar.style.display = "inline-block";
            } else {
                modoAgregar();
            }

            btnCancelar.addEventListener("click", function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: '¿Cancelar edición?',
                    text: "Se perderán los cambios no guardados",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'No',
                    confirmButtonColor: '#6c757d',
                    cancelButtonColor: '#3085d6'
                }).then((result) => {
                    if (result.isConfirmed) {
                        modoAgregar();
                    }
                });
            });
        });

        // Funciones de confirmación con SweetAlert2
        document.addEventListener("DOMContentLoaded", () => {
            const forms = document.querySelectorAll("form");

            forms.forEach((form) => {
                form.addEventListener("submit", function (e) {
                    const boton = e.submitter;
                    const accion = boton?.value;

                    if (accion === "btnModificar" || accion === "btnEliminar") {
                        e.preventDefault();

                        let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar registro?";
                        let texto = accion === "btnModificar"
                            ? "Se actualizarán los datos de este comprobante de egreso."
                            : "Esta acción eliminará el registro permanentemente.";

                        Swal.fire({
                            title: titulo,
                            text: texto,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Sí, continuar",
                            cancelButtonText: "Cancelar",
                            confirmButtonColor: accion === "btnModificar" ? "#3085d6" : "#d33",
                            cancelButtonColor: "#6c757d",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                let inputAccion = form.querySelector("input[name='accionOculta']");
                                if (!inputAccion) {
                                    inputAccion = document.createElement("input");
                                    inputAccion.type = "hidden";
                                    inputAccion.name = "accion";
                                    form.appendChild(inputAccion);
                                }
                                inputAccion.value = accion;

                                form.submit();
                            }
                        });
                    }
                });
            });
        });

        // Empaquetar detalles antes de enviar el formulario
        document.getElementById("formComprobanteEgreso").addEventListener("submit", function(e) {
            const rows = document.querySelectorAll("#product-table tr");
            let detalles = [];

            rows.forEach(row => {
                const numeroFactura = row.querySelector("[name='numeroFactura']")?.value || "";
                const fechaVencimiento = row.querySelector("[name='fechaVencimiento']")?.value || "";
                const valor = row.querySelector("[name='valor']")?.value || "";
                const seleccionado = row.querySelector("[name='seleccionado']")?.checked || false;

                if (numeroFactura && valor && seleccionado) {
                    detalles.push({
                        numeroFactura: numeroFactura,
                        fechaVencimiento: fechaVencimiento,
                        valor: valor
                    });
                }
            });

            const inputDetalles = document.createElement("input");
            inputDetalles.type = "hidden";
            inputDetalles.name = "detalles";
            inputDetalles.value = JSON.stringify(detalles);

            this.appendChild(inputDetalles);
        });

        // Empaquetar detalles antes de enviar el formulario
        document.getElementById("formComprobanteEgreso").addEventListener("submit", function(e) {
            const rows = document.querySelectorAll("#product-table tr");
            let detalles = [];

            rows.forEach(row => {
                const numeroFactura = row.querySelector("[name='numeroFactura']")?.value || "";
                const fechaVencimiento = row.querySelector("[name='fechaVencimiento']")?.value || "";
                const valor = row.querySelector("[name='valor']")?.value || "";
                const seleccionado = row.querySelector("[name='seleccionado']")?.checked || false;

                if (numeroFactura && valor && seleccionado) {
                    detalles.push({
                        numeroFactura: numeroFactura,
                        fechaVencimiento: fechaVencimiento,
                        valor: valor
                    });
                }
            });

            const inputDetalles = document.createElement("input");
            inputDetalles.type = "hidden";
            inputDetalles.name = "detalles";
            inputDetalles.value = JSON.stringify(detalles);

            this.appendChild(inputDetalles);
        });
        </script>
        <br>
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
 
</body>
 
</html>