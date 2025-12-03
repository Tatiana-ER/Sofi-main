<?php
include("connection.php");
include("LibroDiario.php");

$conn = new connection();
$pdo = $conn->connect();
$libroDiario = new LibroDiario($pdo);

// Obtener consecutivo automático
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM docrecibodecaja");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

// Obtener facturas a crédito del cliente con saldo pendiente - MODIFICADO
if (isset($_GET['get_facturas']) && isset($_GET['identificacion'])) {
    $identificacion = $_GET['identificacion'];
    
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            numero_factura,  -- Cambiado de consecutivo a numero_factura
            fecha, 
            fecha_vencimiento,  -- Agregado fecha_vencimiento
            CAST(valorTotal AS DECIMAL(10,2)) as valorTotal,
            COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal
        FROM facturav 
        WHERE identificacion = :identificacion 
        AND formaPago LIKE '%Credito%'
        AND COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) > 0
        ORDER BY fecha ASC
    ");
    $stmt->bindParam(':identificacion', $identificacion);
    $stmt->execute();
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($facturas);
    exit;
}

// Obtener detalles de un recibo para editar
if (isset($_GET['get_detalles']) && isset($_GET['idRecibo'])) {
    $idRecibo = $_GET['idRecibo'];
    
    $stmt = $pdo->prepare("
        SELECT 
            consecutivoFactura,
            valorAplicado,
            fechaVencimiento
        FROM detalle_recibo_caja
        WHERE idRecibo = :idRecibo
        ORDER BY id
    ");
    $stmt->bindParam(':idRecibo', $idRecibo);
    $stmt->execute();
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($detalles);
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
            "nombre" => trim($cliente['nombres'] . " " . $cliente['apellidos'])
        ]);
    } else {
        echo json_encode([
            "nombre" => "No encontrado o no es un cliente"
        ]);
    }
    exit;
}

// Variables iniciales - MODIFICADO: fecha actual por defecto
$txtId = $_POST['txtId'] ?? "";
// CORRECCIÓN: Usar $_POST si existe, si no usar fecha actual
$fecha = isset($_POST['fecha']) && !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');
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

// Datos de facturas (JSON)
$facturasData = $_POST['facturasData'] ?? "";

// Función para actualizar saldos de facturas - MODIFICADO
function actualizarSaldosFacturas($pdo, $facturasData) {
    if (!empty($facturasData)) {
        $dataArray = json_decode($facturasData, true);
        
        foreach ($dataArray as $facturaData) {
            // Obtener información actual de la factura usando numero_factura
            $stmt = $pdo->prepare("
                SELECT 
                    CAST(valorTotal AS DECIMAL(10,2)) as valorTotal,
                    COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal
                FROM facturav 
                WHERE numero_factura = :numero_factura
            ");
            $stmt->execute([':numero_factura' => $facturaData['numero_factura']]);
            $factura = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($factura) {
                // Calcular nuevo saldo
                $nuevoSaldo = $factura['saldoReal'] - floatval($facturaData['valor']);
                
                // Actualizar saldo en la factura
                $stmtUpdate = $pdo->prepare("
                    UPDATE facturav 
                    SET saldoReal = :nuevoSaldo 
                    WHERE numero_factura = :numero_factura
                ");
                $stmtUpdate->execute([
                    ':nuevoSaldo' => $nuevoSaldo,
                    ':numero_factura' => $facturaData['numero_factura']
                ]);
            }
        }
    }
}

// Función para restaurar saldos cuando se elimina o modifica un recibo - MODIFICADO
function restaurarSaldosFacturas($pdo, $idRecibo) {
    // Obtener los detalles del recibo que se va a eliminar/modificar
    $stmt = $pdo->prepare("
        SELECT consecutivoFactura, valorAplicado 
        FROM detalle_recibo_caja 
        WHERE idRecibo = :idRecibo
    ");
    $stmt->execute([':idRecibo' => $idRecibo]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Restaurar saldos usando numero_factura
    foreach ($detalles as $detalle) {
        $stmtUpdate = $pdo->prepare("
            UPDATE facturav 
            SET saldoReal = COALESCE(saldoReal, CAST(valorTotal AS DECIMAL(10,2))) + :valor 
            WHERE numero_factura = :numero_factura
        ");
        $stmtUpdate->execute([
            ':valor' => $detalle['valorAplicado'],
            ':numero_factura' => $detalle['consecutivoFactura']
        ]);
    }
}

switch($accion) {
    case "btnAgregar":
        try {
            $pdo->beginTransaction();
            
            // Validar saldos disponibles antes de procesar - MODIFICADO
            if (!empty($facturasData)) {
                $dataArray = json_decode($facturasData, true);
                
                foreach ($dataArray as $facturaData) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal
                        FROM facturav 
                        WHERE numero_factura = :numero_factura
                    ");
                    $stmt->execute([':numero_factura' => $facturaData['numero_factura']]);
                    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$factura || floatval($facturaData['valor']) > $factura['saldoReal']) {
                        throw new Exception("El valor aplicado a la factura {$facturaData['numero_factura']} excede el saldo disponible");
                    }
                }
            }
            
            // Insertar recibo principal
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
            
            $idRecibo = $pdo->lastInsertId();
            
            // Insertar detalles de facturas - MODIFICADO
            if (!empty($facturasData)) {
                $dataArray = json_decode($facturasData, true);
                
                foreach ($dataArray as $facturaData) {
                    $stmtDetalle = $pdo->prepare("
                        INSERT INTO detalle_recibo_caja 
                        (idRecibo, consecutivoFactura, valorAplicado, fechaVencimiento)
                        VALUES (:idRecibo, :numero_factura, :valor, :fechaVenc)
                    ");
                    $stmtDetalle->execute([
                        ':idRecibo' => $idRecibo,
                        ':numero_factura' => $facturaData['numero_factura'],
                        ':valor' => $facturaData['valor'],
                        ':fechaVenc' => $facturaData['fechaVencimiento'] ?: null
                    ]);
                }
            }
            
            // Actualizar saldos de las facturas
            actualizarSaldosFacturas($pdo, $facturasData);

            // ✨ NUEVO: Registrar en Libro Diario
            $libroDiario->registrarReciboCaja($idRecibo);

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=agregado");
            exit();       
            
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
            exit();
        }
        break;

    case "btnModificar":
        try {
            $pdo->beginTransaction();
            
            // Restaurar saldos de las facturas del recibo original
            restaurarSaldosFacturas($pdo, $txtId);
            
            // Validar nuevos saldos - MODIFICADO
            if (!empty($facturasData)) {
                $dataArray = json_decode($facturasData, true);
                
                foreach ($dataArray as $facturaData) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal
                        FROM facturav 
                        WHERE numero_factura = :numero_factura
                    ");
                    $stmt->execute([':numero_factura' => $facturaData['numero_factura']]);
                    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$factura || floatval($facturaData['valor']) > $factura['saldoReal']) {
                        throw new Exception("El valor aplicado a la factura {$facturaData['numero_factura']} excede el saldo disponible");
                    }
                }
            }
            
            // Actualizar recibo principal
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
            
            // Eliminar detalles antiguos
            $stmtDelete = $pdo->prepare("DELETE FROM detalle_recibo_caja WHERE idRecibo = :idRecibo");
            $stmtDelete->execute([':idRecibo' => $txtId]);

            // ✨ NUEVO: Eliminar asientos contables antiguos
            $libroDiario->eliminarMovimientos('recibo_caja', $txtId);

            // Restaurar saldos de las facturas del recibo original
            restaurarSaldosFacturas($pdo, $txtId);
            
            // Insertar nuevos detalles - MODIFICADO
            if (!empty($facturasData)) {
                $dataArray = json_decode($facturasData, true);
                
                foreach ($dataArray as $facturaData) {
                    $stmtDetalle = $pdo->prepare("
                        INSERT INTO detalle_recibo_caja 
                        (idRecibo, consecutivoFactura, valorAplicado, fechaVencimiento)
                        VALUES (:idRecibo, :numero_factura, :valor, :fechaVenc)
                    ");
                    $stmtDetalle->execute([
                        ':idRecibo' => $txtId,
                        ':numero_factura' => $facturaData['numero_factura'],
                        ':valor' => $facturaData['valor'],
                        ':fechaVenc' => $facturaData['fechaVencimiento'] ?: null
                    ]);
                }
            }
            
            // Actualizar nuevos saldos
            actualizarSaldosFacturas($pdo, $facturasData);

            // ✨ NUEVO: Registrar nuevos asientos contables
            $libroDiario->registrarReciboCaja($txtId);

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=modificado");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
            exit();
        }
        break;

    case "btnEliminar":
      try {
          $pdo->beginTransaction();
          
          // ✨ NUEVO: Eliminar asientos contables
          $libroDiario->eliminarMovimientos('recibo_caja', $txtId);
          
          // Restaurar saldos antes de eliminar
          restaurarSaldosFacturas($pdo, $txtId);
          
          // Eliminar recibo (cascade eliminará los detalles)
          $sentencia = $pdo->prepare("DELETE FROM docrecibodecaja WHERE id = :id");
          $sentencia->bindParam(':id', $txtId);
          $sentencia->execute();
          
          $pdo->commit();
          header("Location: " . $_SERVER['PHP_SELF'] . "?msg=eliminado");
          exit();
          
      } catch (Exception $e) {
          $pdo->rollBack();
          header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
          exit();
      }
  break;

    case "btnEditar":
        // Los datos ya vienen en $_POST desde los campos hidden
        break;
}

// Consulta para mostrar la tabla con información de detalles
$sentencia = $pdo->prepare("
    SELECT 
        r.*,
        (SELECT COUNT(*) FROM detalle_recibo_caja WHERE idRecibo = r.id) as numFacturas
    FROM docrecibodecaja r
    ORDER BY CAST(r.consecutivo AS UNSIGNED) DESC
");
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
  const msg = "<?= $_GET['msg'] ?>";
  const detalle = "<?= $_GET['detalle'] ?? '' ?>";
  
  switch (msg) {
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
      
    case "error":
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: detalle || 'Ocurrió un error al procesar la solicitud',
        confirmButtonColor: '#d33'
      });
      break;
  }

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
  <title>Recibo de Caja - SOFI</title>
  
  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Raleway:300,400,500,600,700|Poppins:300,400,500,600,700" rel="stylesheet">
  
  <!-- Vendor CSS -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style>
    .table-container {
      overflow-x: auto;
      margin-top: 20px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    table th {
      background: #0d6efd;
      color: white;
      padding: 12px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
    }
    
    table td {
      padding: 10px;
      border-bottom: 1px solid #dee2e6;
      font-size: 14px;
    }
    
    table tbody tr:hover {
      background: #f8f9fa;
    }
    
    .factura-row {
      background: #f8f9fa;
      transition: all 0.2s;
    }
    
    .factura-row:hover {
      background: #e9ecef;
    }
    
    .btn-cargar-facturas {
      margin-top: 15px;
      margin-bottom: 10px;
      background: #198754;
      color: white;
      border: none;
      padding: 10px 25px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s;
    }
    
    .btn-cargar-facturas:hover {
      background: #146c43;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .totals {
      margin-top: 20px;
      text-align: right;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 5px;
      border: 2px solid #0d6efd;
    }
    
    .totals label {
      font-weight: bold;
      font-size: 18px;
      color: #0d6efd;
      margin-right: 10px;
    }
    
    .totals input {
      width: 200px;
      font-size: 20px;
      font-weight: bold;
      text-align: right;
      display: inline-block;
      border: 2px solid #0d6efd;
      color: #0d6efd;
    }
    
    .badge {
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .badge-info {
      background: #0dcaf0;
      color: #000;
    }
    
    .badge-warning {
      background: #ffc107;
      color: #000;
    }
    
    .badge-success {
      background: #198754;
      color: #fff;
    }
    
    .info-box {
      background: #e7f3ff;
      border-left: 4px solid #0d6efd;
      padding: 12px;
      margin: 15px 0;
      border-radius: 4px;
    }
    
    .info-box i {
      color: #0d6efd;
      margin-right: 8px;
    }
    
    .form-control:focus {
      border-color: #0d6efd;
      box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .saldo-pendiente {
      font-weight: bold;
      color: #dc3545;
    }
    
    .saldo-info {
      font-size: 12px;
      color: #6c757d;
      font-style: italic;
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
          <li><a class="nav-link" href="dashboard.php">Inicio</a></li>
          <li><a class="nav-link" href="perfil.php">Mi Negocio</a></li>
          <li><a class="nav-link" href="index.php">Cerrar Sesión</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>

  <!-- Main Section -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='menudocumentos.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    
    <div class="container" data-aos="fade-up">
      <div class="section-title">
        <h2>RECIBO DE CAJA</h2>
        <p>Registre los pagos recibidos de sus clientes</p>
        <p>(Los campos marcados con * son obligatorios)</p>
      </div>
      
      <form id="formReciboCaja" action="" method="post">
        <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">
        <input type="hidden" id="numeroFactura" name="numeroFactura">
        <input type="hidden" id="fechaVencimiento" name="fechaVencimiento">
        <input type="hidden" id="valor" name="valor">
        <input type="hidden" id="facturasData" name="facturasData">

        <!-- Fecha y Consecutivo -->
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="fecha" class="form-label fw-bold">Fecha del Recibo*</label>
            <input type="date" class="form-control" id="fecha" name="fecha"
              value="<?php echo htmlspecialchars($fecha); ?>" required>
          </div>
          <div class="col-md-6">
            <label for="consecutivo" class="form-label fw-bold">Consecutivo*</label>
            <input type="text" class="form-control" id="consecutivo" name="consecutivo"
                   value="<?php echo htmlspecialchars($consecutivo); ?>" readonly required>
          </div>
        </div>

        <!-- Cliente -->
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

        <button type="button" class="btn-cargar-facturas" id="btnCargarFacturas">
          <i class="fas fa-file-invoice-dollar"></i> Cargar Facturas Pendientes
        </button>

        <!-- Tabla de facturas - MODIFICADO: Encabezados cambiados -->
        <div class="table-container">
          <table id="tablaFacturas">
            <thead>
              <tr>
                <th style="width: 12%;">Número Factura</th> <!-- Cambiado de Consecutivo -->
                <th style="width: 12%;">Fecha Factura</th>
                <th style="width: 13%;">Valor Total</th>
                <th style="width: 13%;">Saldo Pendiente</th>
                <th style="width: 13%;">Fecha Vencimiento*</th> <!-- Ahora viene de la BD -->
                <th style="width: 17%;">Valor a Aplicar*</th>
                <th style="width: 10%; text-align: center;">Seleccionar</th>
              </tr>
            </thead>
            <tbody id="facturasBody">
              <tr>
                <td colspan="7" class="text-center" style="padding: 30px;">
                  <i class="fas fa-search" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></i>
                  Ingrese una identificación y cargue las facturas pendientes
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Total -->
        <div class="totals">
          <label for="valorTotal"><i class="fas fa-dollar-sign"></i> VALOR TOTAL:</label>
          <input type="text" id="valorTotal" name="valorTotal" class="form-control" 
                 value="<?php echo htmlspecialchars($valorTotal); ?>" readonly>
        </div>

        <!-- Forma de Pago -->
        <div class="row g-3 mt-3">
          <div class="col-md-6">
            <label for="formaPago" class="form-label fw-bold">Forma de Pago*</label>
            <select id="formaPago" name="formaPago" class="form-control" required>
              <option value="">Seleccione una opción</option>
              <?php foreach ($mediosPago as $medio): ?>
                <option value="<?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>" 
                        <?php if($formaPago == $medio['metodoPago']) echo 'selected'; ?>>
                  <?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Observaciones -->
        <div class="mb-3 mt-3">
          <label for="observaciones" class="form-label fw-bold">Observaciones</label>
          <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                    placeholder="Ingrese observaciones adicionales (opcional)"><?php echo htmlspecialchars($observaciones); ?></textarea>
        </div>

        <!-- Botones -->
        <div class="mt-4 mb-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">
            <i class="fas fa-save"></i> Guardar Recibo
          </button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion" style="display:none;">
            <i class="fas fa-edit"></i> Modificar
          </button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion" style="display:none;">
            <i class="fas fa-trash"></i> Eliminar
          </button>
          <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </form>

      <!-- Tabla de registros -->
      <div class="section-title mt-5">
        <h3>Recibos de Caja Registrados</h3>
      </div>
      
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Consecutivo</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Facturas Aplicadas</th>
              <th>Valor Total</th>
              <th>Forma de Pago</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if(count($lista) > 0): ?>
              <?php foreach($lista as $recibo): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($recibo['consecutivo']); ?></strong></td>
                  <td><?php echo date('d/m/Y', strtotime($recibo['fecha'])); ?></td>
                  <td>
                    <strong><?php echo htmlspecialchars($recibo['nombre']); ?></strong><br>
                    <small class="text-muted">CC: <?php echo htmlspecialchars($recibo['identificacion']); ?></small>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($recibo['numeroFactura']); ?>
                    <br><span class="badge badge-info"><?php echo $recibo['numFacturas']; ?> factura(s)</span>
                  </td>
                  <td><strong style="color: #198754;">$<?php echo number_format($recibo['valorTotal'], 2); ?></strong></td>
                  <td><?php echo htmlspecialchars($recibo['formaPago']); ?></td>
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
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center" style="padding: 30px;">
                  No hay recibos de caja registrados
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
  </footer>

  <script>
    // Variable global para modo edición
    let modoEdicion = false;

    // Obtener consecutivo
    window.addEventListener('DOMContentLoaded', function() {
      const txtId = document.getElementById('txtId').value;
      
      if (!txtId || txtId.trim() === "") {
        fetch(window.location.pathname + "?get_consecutivo=1")
          .then(r => r.json())
          .then(data => {
            document.getElementById('consecutivo').value = data.consecutivo;
          })
          .catch(err => console.error('Error:', err));
      } else {
        modoEdicion = true;
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
        .then(r => r.json())
        .then(data => {
          document.getElementById("nombre").value = data.nombre;
        })
        .catch(err => console.error("Error:", err));
      } else {
        document.getElementById("nombre").value = "";
        if (!modoEdicion) {
          document.getElementById("facturasBody").innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 30px;"><i class="fas fa-search" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></i>Ingrese una identificación</td></tr>';
        }
      }
    });

    // Cargar facturas pendientes
    document.getElementById("btnCargarFacturas").addEventListener("click", function() {
      const identificacion = document.getElementById("identificacion").value;
      const txtId = document.getElementById("txtId").value;
      
      if (!identificacion) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Debe ingresar una identificación primero',
          confirmButtonColor: '#3085d6'
        });
        return;
      }
      
      const tbody = document.getElementById("facturasBody");
      tbody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando facturas...</td></tr>';
      
      fetch(`?get_facturas=1&identificacion=${identificacion}`)
        .then(r => r.json())
        .then(facturas => {
          if (facturas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 30px;"><i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; display: block; margin-bottom: 10px;"></i><strong>¡Excelente!</strong><br>Este cliente no tiene facturas pendientes por pagar</td></tr>';
            return;
          }
          
          tbody.innerHTML = "";
          
          // Si estamos editando, cargar los detalles previos
          if (txtId && txtId.trim() !== "") {
            fetch(`?get_detalles=1&idRecibo=${txtId}`)
              .then(r => r.json())
              .then(detalles => {
                renderFacturas(facturas, detalles);
              })
              .catch(err => {
                console.error(err);
                renderFacturas(facturas, []);
              });
          } else {
            renderFacturas(facturas, []);
          }
        })
        .catch(err => {
          console.error(err);
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar las facturas</td></tr>';
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar las facturas',
            confirmButtonColor: '#d33'
          });
        });
    });

    // Renderizar facturas con o sin detalles previos - MODIFICADO
    function renderFacturas(facturas, detalles) {
      const tbody = document.getElementById("facturasBody");
      tbody.innerHTML = "";
      
      facturas.forEach(f => {
        const tr = document.createElement("tr");
        tr.className = "factura-row";
        tr.dataset.numero_factura = f.numero_factura; // Cambiado de consecutivo
        tr.dataset.valorTotal = f.valorTotal;
        tr.dataset.saldoReal = f.saldoReal;
        
        // Buscar si esta factura estaba previamente seleccionada
        const detalleExistente = detalles.find(d => d.consecutivoFactura === f.numero_factura); // Cambiado
        
        // Calcular porcentaje de saldo
        const porcentajeSaldo = (parseFloat(f.saldoReal) / parseFloat(f.valorTotal)) * 100;
        let badgeClass = 'badge-success';
        if (porcentajeSaldo > 75) badgeClass = 'badge-warning';
        if (porcentajeSaldo === 100) badgeClass = 'badge-info';
        
        tr.innerHTML = `
          <td><strong>${f.numero_factura || f.consecutivo}</strong></td> <!-- Cambiado -->
          <td>${formatDate(f.fecha)}</td>
          <td><strong>${parseFloat(f.valorTotal).toFixed(2)}</strong></td>
          <td>
            <span class="saldo-pendiente">${parseFloat(f.saldoReal).toFixed(2)}</span>
            <br>
            <span class="badge ${badgeClass}">${porcentajeSaldo.toFixed(0)}% pendiente</span>
          </td>
          <td>
            <input type="date" 
                   class="form-control fecha-venc" 
                   value="${f.fecha_vencimiento || (detalleExistente ? detalleExistente.fechaVencimiento : '')}"
                   placeholder="Fecha vencimiento">
          </td>
          <td>
            <input type="number" 
                   class="form-control valor-aplicar" 
                   step="0.01" 
                   min="0" 
                   max="${parseFloat(f.saldoReal).toFixed(2)}"
                   value="${detalleExistente ? parseFloat(detalleExistente.valorAplicado).toFixed(2) : ''}"
                   placeholder="Máx: ${parseFloat(f.saldoReal).toFixed(2)}">
            <small class="saldo-info">Máximo: ${parseFloat(f.saldoReal).toFixed(2)}</small>
          </td>
          <td style="text-align: center;">
            <input type="checkbox" 
                   class="factura-checkbox form-check-input" 
                   style="width: 20px; height: 20px;"
                   ${detalleExistente ? 'checked' : ''}>
          </td>
        `;
        
        tbody.appendChild(tr);
      });
      
      // Agregar eventos
      document.querySelectorAll(".valor-aplicar").forEach(input => {
        input.addEventListener("input", function() {
          const row = this.closest('.factura-row');
          const saldoReal = parseFloat(row.dataset.saldoReal);
          const valor = parseFloat(this.value) || 0;
          
          if (valor > saldoReal) {
            Swal.fire({
              icon: 'warning',
              title: 'Valor excedido',
              text: `El valor no puede ser mayor al saldo pendiente (${saldoReal.toFixed(2)})`,
              confirmButtonColor: '#3085d6'
            });
            this.value = saldoReal.toFixed(2);
          }
          
          calcularTotal();
        });
      });
      
      // NUEVO: Evento para selección automática del valor - REQUERIMIENTO 06
      document.querySelectorAll(".factura-checkbox").forEach(checkbox => {
        checkbox.addEventListener("change", function() {
          const row = this.closest('.factura-row');
          const valorInput = row.querySelector('.valor-aplicar');
          const saldoReal = parseFloat(row.dataset.saldoReal);
          
          if (this.checked && valorInput) {
            // REQUERIMIENTO 06: Seleccionar automáticamente el valor pendiente
            valorInput.value = saldoReal.toFixed(2);
          } else if (valorInput) {
            valorInput.value = '';
          }
          
          calcularTotal();
        });
      });
      
      // Calcular total inicial si hay detalles
      if (detalles.length > 0) {
        calcularTotal();
      }
    }

    // Formatear fecha
    function formatDate(dateStr) {
      if (!dateStr) return 'N/A';
      const d = new Date(dateStr + 'T00:00:00');
      return d.toLocaleDateString('es-CO');
    }

    // Calcular total - MODIFICADO
    function calcularTotal() {
      let total = 0;
      let facturasSeleccionadas = [];
      let fechasVencimiento = [];
      let valoresAplicados = [];
      let facturasData = [];
      
      document.querySelectorAll(".factura-row").forEach(row => {
        const checkbox = row.querySelector(".factura-checkbox");
        const valorInput = row.querySelector(".valor-aplicar");
        const fechaVencInput = row.querySelector(".fecha-venc");
        
        if (checkbox && checkbox.checked && valorInput && valorInput.value) {
          const valor = parseFloat(valorInput.value) || 0;
          
          if (valor > 0) {
            total += valor;
            facturasSeleccionadas.push(row.dataset.numero_factura); // Cambiado
            valoresAplicados.push(valor.toFixed(2));
            
            const fechaVenc = fechaVencInput && fechaVencInput.value ? fechaVencInput.value : '';
            if (fechaVenc) {
              fechasVencimiento.push(fechaVenc);
            }
            
            // Agregar a array de objetos para JSON - MODIFICADO
            facturasData.push({
              numero_factura: row.dataset.numero_factura, // Cambiado
              valor: valor.toFixed(2),
              fechaVencimiento: fechaVenc
            });
          }
        }
      });
      
      document.getElementById("valorTotal").value = total.toFixed(2);
      document.getElementById("numeroFactura").value = facturasSeleccionadas.join(', ');
      document.getElementById("valor").value = valoresAplicados.join(', ');
      document.getElementById("fechaVencimiento").value = fechasVencimiento.join(', ');
      document.getElementById("facturasData").value = JSON.stringify(facturasData);
    }

    // Modo agregar/editar
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
        document.getElementById("facturasBody").innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 30px;"><i class="fas fa-search" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></i>Seleccione un cliente y cargue las facturas pendientes</td></tr>';
        modoEdicion = false;
      }

      if (id && id.trim() !== "") {
        btnAgregar.style.display = "none";
        btnModificar.style.display = "inline-block";
        btnEliminar.style.display = "inline-block";
        btnCancelar.style.display = "inline-block";
        modoEdicion = true;
        
        // Auto-cargar facturas si hay identificación
        const identificacion = document.getElementById("identificacion").value;
        if (identificacion) {
          setTimeout(() => {
            document.getElementById("btnCargarFacturas").click();
          }, 500);
        }
      } else {
        modoAgregar();
      }

      btnCancelar.addEventListener("click", function(e) {
        e.preventDefault();
        window.location.href = window.location.pathname;
      });
    });

    // Validación antes de enviar
    document.getElementById("formReciboCaja").addEventListener("submit", function(e) {
      const accion = e.submitter?.value;
      
      if (accion === "btnAgregar" || accion === "btnModificar") {
        const facturasData = document.getElementById("facturasData").value;
        const valorTotal = document.getElementById("valorTotal").value;
        
        if (!facturasData || facturasData === "[]") {
          e.preventDefault();
          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar al menos una factura y asignar un valor',
            confirmButtonColor: '#3085d6'
          });
          return false;
        }
        
        if (!valorTotal || parseFloat(valorTotal) <= 0) {
          e.preventDefault();
          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'El valor total debe ser mayor a cero',
            confirmButtonColor: '#3085d6'
          });
          return false;
        }
      }
    });

    // Confirmaciones
    document.addEventListener("DOMContentLoaded", () => {
      const forms = document.querySelectorAll("form");

      forms.forEach((form) => {
        form.addEventListener("submit", function (e) {
          const boton = e.submitter;
          const accion = boton?.value;

          if (accion === "btnModificar" || accion === "btnEliminar") {
            e.preventDefault();

            let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar recibo?";
            let texto = accion === "btnModificar"
              ? "Se actualizarán los datos de este recibo de caja y los saldos de las facturas."
              : "Esta acción eliminará el recibo y restaurará los saldos de las facturas afectadas.";

            Swal.fire({
              title: titulo,
              text: texto,
              icon: "warning",
              showCancelButton: true,
              confirmButtonText: "Sí, continuar",
              cancelButtonText: "Cancelar",
              confirmButtonColor: accion === "btnModificar" ? "#ffc107" : "#d33",
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

    // Establecer fecha actual al cargar la página si está vacía
    window.addEventListener('DOMContentLoaded', function() {
      const fechaInput = document.getElementById('fecha');
      const txtId = document.getElementById('txtId').value;
      
      // Solo establecer fecha actual si NO estamos editando
      if (!txtId || txtId.trim() === "") {
        // CORREGIDO: Obtener fecha local de Colombia (GMT-5)
        const hoy = new Date();
        const year = hoy.getFullYear();
        const month = String(hoy.getMonth() + 1).padStart(2, '0');
        const day = String(hoy.getDate()).padStart(2, '0');
        const fechaLocal = `${year}-${month}-${day}`;
        
        fechaInput.value = fechaLocal;
      }
    });
  </script>

  <!-- Vendor JS -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/js/main.js"></script>

</body>
</html>