<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Obtener consecutivo automático
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(consecutivo) AS ultimo FROM docrecibodecaja");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

// Obtener facturas pendientes del cliente
if (isset($_GET['get_facturas']) && isset($_GET['identificacion'])) {
    $identificacion = $_GET['identificacion'];
    
    $stmt = $pdo->prepare("SELECT id, consecutivo, fecha, valorTotal, fechaVencimiento 
                          FROM facturav 
                          WHERE identificacion = :identificacion 
                          AND formaPago = 'Credito' 
                          ORDER BY fecha DESC");
    $stmt->bindParam(':identificacion', $identificacion);
    $stmt->execute();
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($facturas);
    exit;
}

// Buscar cliente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar_cliente'])) {
    $identificacion = $_POST['identificacion'];
    
    $stmt = $pdo->prepare("SELECT nombres, apellidos FROM catalogosterceros WHERE cedula = :cedula AND tipoTercero = 'cliente'");
    $stmt->bindParam(':cedula', $identificacion);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente) {
        echo json_encode([
            "nombre" => $cliente['nombres'] . " " . $cliente['apellidos']
        ]);
    } else {
        echo json_encode([
            "nombre" => "No encontrado o no es un cliente"
        ]);
    }
    exit;
}

// Variables iniciales
$txtId = $_POST['txtId'] ?? "";
$fecha = $_POST['fecha'] ?? "";
$consecutivo = $_POST['consecutivo'] ?? "";
$identificacion = $_POST['identificacion'] ?? "";
$nombre = $_POST['nombre'] ?? "";
$numeroFactura = $_POST['numeroFactura'] ?? "";
$fechaVencimiento = $_POST['fechaVencimiento'] ?? "";
$valor = $_POST['valor'] ?? "";
$valorTotal = $_POST['valorTotal'] ?? "";
$formaPago = $_POST['formaPago'] ?? "";
$observaciones = $_POST['observaciones'] ?? "";
$accion = $_POST['accion'] ?? "";

switch($accion) {
    case "btnAgregar":
        $sentencia = $pdo->prepare("INSERT INTO docrecibodecaja(
            fecha, consecutivo, identificacion, nombre, numeroFactura, 
            fechaVencimiento, valor, valorTotal, formaPago, observaciones
        ) VALUES (
            :fecha, :consecutivo, :identificacion, :nombre, :numeroFactura, 
            :fechaVencimiento, :valor, :valorTotal, :formaPago, :observaciones
        )");
        
        $sentencia->bindParam(':fecha', $fecha);
        $sentencia->bindParam(':consecutivo', $consecutivo);
        $sentencia->bindParam(':identificacion', $identificacion);
        $sentencia->bindParam(':nombre', $nombre);
        $sentencia->bindParam(':numeroFactura', $numeroFactura);
        $sentencia->bindParam(':fechaVencimiento', $fechaVencimiento);
        $sentencia->bindParam(':valor', $valor);
        $sentencia->bindParam(':valorTotal', $valorTotal);
        $sentencia->bindParam(':formaPago', $formaPago);
        $sentencia->bindParam(':observaciones', $observaciones);
        $sentencia->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=agregado");
        exit();
        break;

    case "btnModificar":
        $sentencia = $pdo->prepare("UPDATE docrecibodecaja SET
            fecha = :fecha,
            consecutivo = :consecutivo,
            identificacion = :identificacion,
            nombre = :nombre,
            numeroFactura = :numeroFactura,
            fechaVencimiento = :fechaVencimiento,
            valor = :valor,
            valorTotal = :valorTotal,
            formaPago = :formaPago,
            observaciones = :observaciones
            WHERE id = :id");
        
        $sentencia->bindParam(':fecha', $fecha);
        $sentencia->bindParam(':consecutivo', $consecutivo);
        $sentencia->bindParam(':identificacion', $identificacion);
        $sentencia->bindParam(':nombre', $nombre);
        $sentencia->bindParam(':numeroFactura', $numeroFactura);
        $sentencia->bindParam(':fechaVencimiento', $fechaVencimiento);
        $sentencia->bindParam(':valor', $valor);
        $sentencia->bindParam(':valorTotal', $valorTotal);
        $sentencia->bindParam(':formaPago', $formaPago);
        $sentencia->bindParam(':observaciones', $observaciones);
        $sentencia->bindParam(':id', $txtId);
        $sentencia->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=modificado");
        exit();
        break;

    case "btnEliminar":
        $sentencia = $pdo->prepare("DELETE FROM docrecibodecaja WHERE id = :id");
        $sentencia->bindParam(':id', $txtId);
        $sentencia->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=eliminado");
        exit();
        break;

    case "btnEditar":
        // Los datos ya vienen en $_POST desde los campos hidden
        break;
}

// Consulta para mostrar la tabla
$sentencia = $pdo->prepare("SELECT * FROM docrecibodecaja ORDER BY id DESC");
$sentencia->execute();
$lista = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Obtener medios de pago
$mediosPago = [];
$stmt = $pdo->query("SELECT metodoPago, cuentaContable FROM mediosdepago");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mediosPago[] = $row;
}
?>

<!-- SweetAlert -->
<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  switch ("<?= $_GET['msg'] ?>") {
    case "agregado":
      Swal.fire({
        icon: 'success',
        title: 'Guardado exitosamente',
        text: 'El recibo de caja se ha agregado correctamente',
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
        text: 'El recibo de caja fue eliminado del registro',
        confirmButtonColor: '#3085d6'
      });
      break;
  }

  if (window.history.replaceState) {
    const url = new URL(window.location);
    url.searchParams.delete('msg');
    window.history.replaceState({}, document.title, url);
  }
});
</script>
<?php endif; ?>


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
    input[type="text"], input[type="number"], input[type="date"] {
      width: 100%;
      box-sizing: border-box;
      padding: 8px;
    }

    .btn-add, .btn-remove {
      padding: 5px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      color: white;
      font-weight: bold;
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
    
    .totals {
      margin-top: 20px;
      text-align: right;
    }
    
    .totals label {
      font-weight: bold;
    }
    
    .totals input {
      width: 200px;
      font-size: 18px;
      font-weight: bold;
      text-align: right;
    } 

    #product-table input[type="text"],
    #product-table input[type="number"] {
      padding: 5px;
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

  <!-- ======= Services Section ======= -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='menudocumentos.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    
    <div class="container" data-aos="fade-up">
      <div class="section-title">
        <h2>RECIBO DE CAJA</h2>
        <p>Para crear un nuevo recibo de caja diligencie los campos a continuación:</p>
        <p>(Los campos marcados con * son obligatorios)</p>
      </div>
      
      <form id="formReciboCaja" action="" method="post">
        <!-- ID oculto - MUY IMPORTANTE -->
        <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

        <!-- Fecha y Consecutivo -->
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="fecha" class="form-label fw-bold">Fecha del documento*</label>
            <input type="date" class="form-control" id="fecha" name="fecha"
                  value="<?php echo htmlspecialchars($fecha); ?>" required>
          </div>

          <div class="col-md-6">
            <label for="consecutivo" class="form-label fw-bold">Consecutivo*</label>
            <input type="text" class="form-control" id="consecutivo" name="consecutivo"
                  placeholder="Número consecutivo"
                  value="<?php echo htmlspecialchars($consecutivo); ?>" readonly required>
          </div>
        </div>

        <!-- Identificación y Nombre -->
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="identificacion" class="form-label fw-bold">Identificación del Cliente*</label>
            <input type="number" class="form-control" id="identificacion" name="identificacion"
                  placeholder="Ej: 123456789"
                  value="<?php echo htmlspecialchars($identificacion); ?>" required>
          </div>

          <div class="col-md-6">
            <label for="nombre" class="form-label fw-bold">Nombre del cliente*</label>
            <input type="text" class="form-control" id="nombre" name="nombre"
                  placeholder="Nombre del cliente"
                  value="<?php echo htmlspecialchars($nombre); ?>" readonly required>
          </div>
        </div>

        <!-- Tabla de productos/facturas -->
        <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Número de factura </th>
                  <th>Fecha de vencimiento</th>
                  <th>Valor</th>
                  <th>¿Seleccionar?</th>
                  <th></th>
                </tr>
              </thead>
            <tbody id="product-table">
              <tr>
                <td><input type="text" class="form-control factura-input" name="facturas[]" placeholder="Número de factura"></td>
                <td><input type="date" class="form-control fecha-input" name="fechas[]" placeholder="Fecha de vencimiento"></td>
                <td><input type="number" class="form-control valor-input" name="valores[]" step="0.01" placeholder="0.00"></td>
                <td style="text-align: center;">
                  <input type="checkbox" class="factura-checkbox" checked>
                </td>
                <td>
                  <button type="button" class="btn-add" onclick="addRow()">+</button>
                  <button type="button" class="btn-remove" onclick="removeRow(this)">-</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Campos hidden para consolidar los datos -->
        <input type="hidden" id="numeroFactura" name="numeroFactura" value="<?php echo htmlspecialchars($numeroFactura); ?>">
        <input type="hidden" id="fechaVencimiento" name="fechaVencimiento" value="<?php echo htmlspecialchars($fechaVencimiento); ?>">
        <input type="hidden" id="valor" name="valor" value="<?php echo htmlspecialchars($valor); ?>">

        <!-- Valor Total -->
        <div class="totals">
          <label for="valorTotal">VALOR TOTAL:</label>
          <input type="text" id="valorTotal" name="valorTotal" class="form-control d-inline-block" 
                 value="<?php echo htmlspecialchars($valorTotal); ?>" readonly>
        </div>

        <!-- Forma de Pago -->
        <div class="form-group mt-3">
          <label for="formaPago">FORMA DE PAGO*</label>
          <select id="formaPago" name="formaPago" class="form-control" required style="width: 400px; display: inline-block;">
            <option value="">Seleccione una opción</option>
            <?php foreach ($mediosPago as $medio): ?>
              <option value="<?= htmlspecialchars($medio['metodoPago']) ?>" 
                      <?php if($formaPago == $medio['metodoPago']) echo 'selected'; ?>>
                <?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Observaciones -->
        <div class="mb-3 mt-3">
          <label for="observaciones" class="form-label fw-bold">Observaciones</label>
          <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($observaciones); ?></textarea>
        </div>

        <!-- Botones -->
        <div class="mt-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion">Modificar</button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion">Eliminar</button>
          <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
        </div>
      </form>

        <div class="row">
          <div class="table-container">

            <table>
              <thead>
                <tr>
                <th>Consecutivo</th>
                <th>Fecha</th>
                <th>Identificación</th>
                <th>Nombre</th>
                <th>Núm. Factura</th>
                <th>Valor Total</th>  
                <th>Forma de Pago</th>
                <th>Observaciones</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody id="tabla-registros">
              <?php foreach($lista as $recibo){ ?>
                <tr>
                  <td><?php echo htmlspecialchars($recibo['consecutivo']); ?></td>
                  <td><?php echo htmlspecialchars($recibo['fecha']); ?></td>
                  <td><?php echo htmlspecialchars($recibo['identificacion']); ?></td>
                  <td><?php echo htmlspecialchars($recibo['nombre']); ?></td>
                  <td><?php echo htmlspecialchars($recibo['numeroFactura']); ?></td>
                  <td>$<?php echo number_format($recibo['valorTotal'], 2); ?></td>
                  <td><?php echo htmlspecialchars($recibo['formaPago']); ?></td>
                  <td><?php echo htmlspecialchars($recibo['observaciones']); ?></td>
                  <td>
                    <form action="" method="post" style="display:flex; gap:5px;">
                      <input type="hidden" name="txtId" value="<?php echo $recibo['id']; ?>">
                      <input type="hidden" name="fecha" value="<?php echo $recibo['fecha']; ?>">
                      <input type="hidden" name="consecutivo" value="<?php echo $recibo['consecutivo']; ?>">
                      <input type="hidden" name="identificacion" value="<?php echo $recibo['identificacion']; ?>">
                      <input type="hidden" name="nombre" value="<?php echo $recibo['nombre']; ?>">
                      <input type="hidden" name="numeroFactura" value="<?php echo $recibo['numeroFactura']; ?>">
                      <input type="hidden" name="fechaVencimiento" value="<?php echo $recibo['fechaVencimiento']; ?>">
                      <input type="hidden" name="valor" value="<?php echo $recibo['valor']; ?>">
                      <input type="hidden" name="valorTotal" value="<?php echo $recibo['valorTotal']; ?>">
                      <input type="hidden" name="formaPago" value="<?php echo $recibo['formaPago']; ?>">
                      <input type="hidden" name="observaciones" value="<?php echo $recibo['observaciones']; ?>">

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
        // Obtener consecutivo automático
        window.addEventListener('DOMContentLoaded', function() {
            const txtId = document.getElementById('txtId').value;
            
            if (!txtId || txtId.trim() === "") {
                fetch(window.location.pathname + "?get_consecutivo=1")
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('consecutivo').value = data.consecutivo;
                    })
                    .catch(error => console.error('Error al obtener consecutivo:', error));
            }
        });

        // Buscar cliente
        document.getElementById("identificacion").addEventListener("input", function() {
            let identificacion = this.value;

            if (identificacion.length > 0) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ 
                        buscar_cliente: "1",
                        identificacion: identificacion 
                    }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("nombre").value = data.nombre;
                })
                .catch(error => console.error("Error:", error));
            } else {
                document.getElementById("nombre").value = "";
            }
        });

        // Agregar nueva fila
        function addRow() {
            const tableBody = document.getElementById("product-table");
            const lastRow = tableBody.lastElementChild;
            const newRow = lastRow.cloneNode(true);
            
            // Limpiar valores
            newRow.querySelectorAll("input:not([type='checkbox'])").forEach(input => input.value = "");
            
            // Marcar el checkbox por defecto
            const checkbox = newRow.querySelector(".factura-checkbox");
            if (checkbox) checkbox.checked = true;
            
            tableBody.appendChild(newRow);
            
            // Re-calcular total cuando cambian los valores
            newRow.querySelector(".valor-input").addEventListener("input", calcularTotal);
            newRow.querySelector(".factura-checkbox").addEventListener("change", calcularTotal);
        }

        // Eliminar fila
        function removeRow(btn) {
            const rows = document.querySelectorAll("#product-table tr");
            if (rows.length > 1) {
                btn.closest("tr").remove();
                calcularTotal();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: 'Debe haber al menos una fila',
                    confirmButtonColor: '#3085d6'
                });
            }
        }

        // Calcular total automáticamente (solo facturas seleccionadas)
        function calcularTotal() {
            let total = 0;
            let facturas = [];
            let fechas = [];
            let valores = [];

            document.querySelectorAll("#product-table tr").forEach(row => {
                const valorInput = row.querySelector(".valor-input");
                const facturaInput = row.querySelector(".factura-input");
                const fechaInput = row.querySelector(".fecha-input");
                const checkbox = row.querySelector(".factura-checkbox");
                
                // Solo sumar si el checkbox está marcado
                if (checkbox && checkbox.checked && valorInput && valorInput.value) {
                    const valor = parseFloat(valorInput.value) || 0;
                    total += valor;
                    
                    if (facturaInput.value) facturas.push(facturaInput.value);
                    if (fechaInput.value) fechas.push(fechaInput.value);
                    if (valor > 0) valores.push(valor);
                }
            });

            document.getElementById("valorTotal").value = total.toFixed(2);
            document.getElementById("numeroFactura").value = facturas.join(", ");
            document.getElementById("fechaVencimiento").value = fechas.join(", ");
            document.getElementById("valor").value = valores.join(", ");
        }

        // Eventos para calcular total
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".valor-input").forEach(input => {
                input.addEventListener("input", calcularTotal);
            });
            
            document.querySelectorAll(".factura-checkbox").forEach(checkbox => {
                checkbox.addEventListener("change", calcularTotal);
            });
        });

        // Script para alternar botones
        document.addEventListener("DOMContentLoaded", function() {
          const id = document.getElementById("txtId").value;
          const btnAgregar = document.getElementById("btnAgregar");
          const btnModificar = document.getElementById("btnModificar");
          const btnEliminar = document.getElementById("btnEliminar");
          const btnCancelar = document.getElementById("btnCancelar");
          const form = document.getElementById("formReciboCaja");

          function modoAgregar() {
            btnAgregar.style.display = "inline-block";
            btnModificar.style.display = "none";
            btnEliminar.style.display = "none";
            btnCancelar.style.display = "none";

            form.querySelectorAll("input, select, textarea").forEach(el => {
              if (el.type === "checkbox") {
                el.checked = false;
              } else if (el.id !== "consecutivo" && el.type !== "hidden") {
                el.value = "";
              }
            });

            document.getElementById("txtId").value = "";
            
            // Limpiar tabla, dejar solo una fila
            const tbody = document.getElementById("product-table");
            tbody.innerHTML = `
              <tr>
                <td><input type="text" class="form-control factura-input" name="facturas[]" placeholder="Número de factura"></td>
                <td><input type="date" class="form-control fecha-input" name="fechas[]" placeholder="Fecha de vencimiento"></td>
                <td><input type="number" class="form-control valor-input" name="valores[]" step="0.01" placeholder="0.00"></td>
                <td style="text-align: center;">
                  <input type="checkbox" class="factura-checkbox" checked>
                </td>
                <td>
                  <button type="button" class="btn-add" onclick="addRow()">+</button>
                  <button type="button" class="btn-remove" onclick="removeRow(this)">-</button>
                </td>
              </tr>
            `;
            
            // Re-agregar eventos
            document.querySelector(".valor-input").addEventListener("input", calcularTotal);
            document.querySelector(".factura-checkbox").addEventListener("change", calcularTotal);
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
            window.location.href = window.location.pathname;
          });
        });

        // Confirmaciones con SweetAlert2
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
                  ? "Se actualizarán los datos de este recibo de caja."
                  : "Esta acción eliminará el recibo permanentemente.";

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
      </script>
    </div>
  </section>

  
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
