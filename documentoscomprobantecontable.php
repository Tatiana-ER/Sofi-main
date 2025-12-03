<?php
include("connection.php");
include("LibroDiario.php");

$conn = new connection();
$pdo = $conn->connect();
$libroDiario = new LibroDiario($pdo);

// Obtener el siguiente consecutivo 
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(consecutivo) AS ultimo FROM doccomprobantecontable");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$fecha=(isset($_POST['fecha']))?$_POST['fecha']:"";
$consecutivo=(isset($_POST['consecutivo']))?$_POST['consecutivo']:"";
$observaciones=(isset($_POST['observaciones']))?$_POST['observaciones']:"";

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

if (isset($_POST['detalles'])) {
    $_POST['detalles'] = json_decode($_POST['detalles'], true);
}

switch($accion){
  case "btnAgregar":
      $sentencia=$pdo->prepare("INSERT INTO doccomprobantecontable(fecha,consecutivo,observaciones) 
      VALUES (:fecha,:consecutivo,:observaciones)");
      
      $sentencia->bindParam(':fecha',$fecha);
      $sentencia->bindParam(':consecutivo',$consecutivo);
      $sentencia->bindParam(':observaciones',$observaciones);
      $sentencia->execute();

      $idComprobante = $pdo->lastInsertId();

      if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
          $sqlDetalle = "INSERT INTO detallecomprobantecontable 
                        (comprobante_id, cuentaContable, descripcionCuenta, tercero, detalle, valorDebito, valorCredito)
                        VALUES (:comprobante_id, :cuentaContable, :descripcionCuenta, :tercero, :detalle, :valorDebito, :valorCredito)";
          $stmtDetalle = $pdo->prepare($sqlDetalle);

          foreach ($_POST['detalles'] as $detalle) {
              $terceroCompleto = trim(($detalle['terceroCedula'] ?? '') . ' - ' . ($detalle['terceroNombre'] ?? ''));
              if ($terceroCompleto == ' - ') {
                  $terceroCompleto = '';
              }

              // Para cuenta contable: solo guardar el código
              $cuentaContable = $detalle['cuentaContable'];
              
              // Si viene en formato "código - nombre", extraer solo el código
              if (strpos($cuentaContable, '-') !== false) {
                  $partes = explode('-', $cuentaContable, 2);
                  $cuentaContable = trim($partes[0]);
              }
              
              $stmtDetalle->execute([
                  ':comprobante_id' => $idComprobante,
                  ':cuentaContable' => $detalle['cuentaContable'],
                  ':descripcionCuenta' => $detalle['descripcionCuenta'],
                  ':tercero' => $terceroCompleto,
                  ':detalle' => $detalle['detalle'],
                  ':valorDebito' => $detalle['valorDebito'],
                  ':valorCredito' => $detalle['valorCredito']
              ]);
          }
      }

      $libroDiario->registrarComprobanteContable($idComprobante);

      header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
      exit;
  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE doccomprobantecontable 
                                  SET fecha = :fecha,
                                      consecutivo = :consecutivo,
                                      observaciones = :observaciones
                                  WHERE id = :id");

      $sentencia->bindParam(':fecha', $fecha);
      $sentencia->bindParam(':consecutivo', $consecutivo);
      $sentencia->bindParam(':observaciones', $observaciones);
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();
      
      $libroDiario->eliminarMovimientos('comprobante_contable', $txtId);

      $deleteDetalle = $pdo->prepare("DELETE FROM detallecomprobantecontable WHERE comprobante_id = :comprobante_id");
      $deleteDetalle->bindParam(':comprobante_id', $txtId);
      $deleteDetalle->execute();

      if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
          $sqlDetalle = "INSERT INTO detallecomprobantecontable 
                        (comprobante_id, cuentaContable, descripcionCuenta, tercero, detalle, valorDebito, valorCredito)
                        VALUES (:comprobante_id, :cuentaContable, :descripcionCuenta, :tercero, :detalle, :valorDebito, :valorCredito)";
          $stmtDetalle = $pdo->prepare($sqlDetalle);

          foreach ($_POST['detalles'] as $detalle) {
              $terceroCompleto = trim(($detalle['terceroCedula'] ?? '') . ' - ' . ($detalle['terceroNombre'] ?? ''));
              if ($terceroCompleto == ' - ') {
                  $terceroCompleto = '';
              }

              // Para cuenta contable: solo guardar el código
              $cuentaContable = $detalle['cuentaContable'];
              
              // Si viene en formato "código - nombre", extraer solo el código
              if (strpos($cuentaContable, '-') !== false) {
                  $partes = explode('-', $cuentaContable, 2);
                  $cuentaContable = trim($partes[0]);
              }
              
              $stmtDetalle->execute([
                  ':comprobante_id' => $txtId,
                  ':cuentaContable' => $detalle['cuentaContable'],
                  ':descripcionCuenta' => $detalle['descripcionCuenta'],
                  ':tercero' => $terceroCompleto,
                  ':detalle' => $detalle['detalle'],
                  ':valorDebito' => $detalle['valorDebito'],
                  ':valorCredito' => $detalle['valorCredito']
              ]);
          }
      }

      $libroDiario->registrarComprobanteContable($txtId);

      header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
      exit;
  break;

  case "btnEliminar":
      $libroDiario->eliminarMovimientos('comprobante_contable', $txtId);
      
      $sentenciaDetalle = $pdo->prepare("DELETE FROM detallecomprobantecontable WHERE comprobante_id = :id");
      $sentenciaDetalle->bindParam(':id', $txtId);
      $sentenciaDetalle->execute();

      $sentencia = $pdo->prepare("DELETE FROM doccomprobantecontable WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();

      header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
      exit;
  break;

  case "btnEditar":
      $sentencia = $pdo->prepare("SELECT * FROM doccomprobantecontable WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();
      $comprobante = $sentencia->fetch(PDO::FETCH_ASSOC);

      if ($comprobante) {
          $fecha = $comprobante['fecha'];
          $consecutivo = $comprobante['consecutivo'];
          $observaciones = $comprobante['observaciones'];
      }

      $stmtDetalle = $pdo->prepare("SELECT *, 
                                  TRIM(SUBSTRING_INDEX(tercero, '-', 1)) as terceroCedula,
                                  TRIM(SUBSTRING_INDEX(tercero, '-', -1)) as terceroNombre
                                  FROM detallecomprobantecontable WHERE comprobante_id = :comprobante_id");
      $stmtDetalle->bindParam(':comprobante_id', $txtId);
      $stmtDetalle->execute();
      $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
  break;
}

$sentencia= $pdo->prepare("SELECT * FROM `doccomprobantecontable` WHERE 1");
$sentencia->execute();
$lista=$sentencia->fetchALL(PDO::FETCH_ASSOC);

?>

<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  switch ("<?= $_GET['msg'] ?>") {
    case "agregado":
      Swal.fire({
        icon: 'success',
        title: 'Guardado exitosamente',
        text: 'El comprobante contable se ha agregado correctamente',
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
        text: 'El comprobante contable fue eliminado del registro',
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

  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

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
  
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style> 
    input[type="text"], input[type="number"] {
      width: 100%;
      box-sizing: border-box;
      padding: 5px;
    }

    .add-row-btn {
      cursor: pointer;
      background-color: #0d6efd;
      color: white;
      border: none;
      padding: 10px;
      font-size: 18px;
      margin-top: 20px;
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
      width: 160px;
      margin-left: 10px;
    }
    
    .select2-container {
      width: 100% !important;
    }
    .select2-container .select2-selection--single {
      height: 38px;
      padding: 6px 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 24px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 36px;
    }
  </style>

</head>

<body>

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

    <section id="services" class="services">
      <button class="btn-ir" onclick="window.location.href='menudocumentos.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>COMPROBANTE CONTABLE</h2>
          <p>Para crear un nuevo comprobante contable diligencie los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>
        
        <form id="formComprobanteContable" action="" method="post" class="container mt-3">

          <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

          <div class="row g-3">
            <div class="col-md-4">
              <label for="fecha" class="form-label fw-bold">Fecha del documento*</label>
              <input type="date" class="form-control" id="fecha" name="fecha"
                    value="<?php echo $fecha ? $fecha : date('Y-m-d'); ?>" required>
            </div>

            <div class="col-md-4">
              <label for="consecutivo" class="form-label fw-bold">Consecutivo*</label>
              <input type="text" class="form-control" id="consecutivo" name="consecutivo"
                    placeholder="Número consecutivo"
                    value="<?php echo $consecutivo; ?>" readonly>
            </div>
          </div>

          <div class="table-responsive mt-3">
            <table class="table-container">
              <thead class="table-primary text-center">
                <tr>
                  <th>Cuenta Contable</th>
                  <th>Descripción Cuenta</th>
                  <th style="width: 30%;">Cédula Tercero</th>
                  <th style="width: 50%;">Nombre Tercero</th>
                  <th style="width: 30%;">Detalle</th>
                  <th>Valor Débito</th>
                  <th>Valor Crédito</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="product-table">
                    <?php if (!empty($detalles)) : ?>
                      <?php foreach ($detalles as $detalle): ?>
                        <tr>
                          <td>
                            <select name="cuentaContable" class="form-control cuenta-select" style="width: 100%;">
                              <?php if (!empty($detalle['cuentaContable'])): ?>
                                <?php 
                                // Separar código y nombre si existe
                                $cuentaCompleta = $detalle['cuentaContable'];
                                $partes = explode('-', $cuentaCompleta, 2);
                                $codigo = $partes[0] ?? $cuentaCompleta;
                                $nombre = isset($partes[1]) ? trim($partes[1]) : '';
                                ?>
                                <option value="<?= htmlspecialchars($codigo) ?>" selected>
                                  <?= htmlspecialchars($codigo) ?>
                                </option>
                              <?php else: ?>
                                <option value="">Buscar cuenta...</option>
                              <?php endif; ?>
                            </select>
                          </td>
                          <td>
                            <input type="text" name="descripcionCuenta" class="form-control" 
                                  value="<?= htmlspecialchars($detalle['descripcionCuenta'] ?? $nombre) ?>">
                          </td>
                          <td>
                            <select name="terceroCedula" class="form-control tercero-select" style="width: 100%;">
                              <?php if (!empty($detalle['terceroCedula'])): ?>
                                <option value="<?= htmlspecialchars($detalle['terceroCedula']) ?>" selected>
                                  <?= htmlspecialchars($detalle['terceroCedula'] . ' - ' . ($detalle['terceroNombre'] ?? '')) ?>
                                </option>
                              <?php else: ?>
                                <option value="">Buscar por cédula...</option>
                              <?php endif; ?>
                            </select>
                          </td>
                          <td><input type="text" name="terceroNombre" class="form-control" value="<?= htmlspecialchars($detalle['terceroNombre'] ?? '') ?>"></td>
                          <td><input type="text" name="detalle" class="form-control" value="<?= htmlspecialchars($detalle['detalle']) ?>"></td>
                          <td><input type="text" step="0.01" name="valorDebito" class="form-control debito" value="<?= htmlspecialchars($detalle['valorDebito']) ?>"></td>
                          <td><input type="text" step="0.01" name="valorCredito" class="form-control credito" value="<?= htmlspecialchars($detalle['valorCredito']) ?>"></td>
                          <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                      </tr>
                  <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td>
                    <select name="cuentaContable" class="form-control cuenta-select" style="width: 100%;">
                      <option value="">Buscar cuenta...</option>
                    </select>
                  </td>
                  <td><input type="text" name="descripcionCuenta" class="form-control" value=""></td>
                  <td>
                    <select name="terceroCedula" class="form-control tercero-select" style="width: 100%;">
                      <option value="">Buscar por cédula...</option>
                    </select>
                  </td>
                  <td><input type="text" name="terceroNombre" class="form-control" placeholder="Nombre" value="" readonly></td>
                  <td><input type="text" name="detalle" class="form-control" value=""></td>
                  <td><input type="text" step="0.01" name="valorDebito" class="form-control debito" placeholder="0.00" value=""></td>
                  <td><input type="text" step="0.01" name="valorCredito" class="form-control credito" placeholder="0.00" value=""></td>
                  <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="col-md-6 ms-auto mt-3">
            <div class="row mb-2">
              <div class="col-6">
                <label for="sumaDebito" class="form-label fw-bold">Suma Débito</label>
                <input type="text" id="sumaDebito" name="sumaDebito" class="form-control text-end" value="0.00" readonly>
              </div>
              <div class="col-6">
                <label for="sumaCredito" class="form-label fw-bold">Suma Crédito</label>
                <input type="text" id="sumaCredito" name="sumaCredito" class="form-control text-end" value="0.00" readonly>
              </div>
            </div>
            <div class="mb-2">
              <label for="diferencia" class="form-label fw-bold">Diferencia</label>
              <input type="text" id="diferencia" name="diferencia" 
                    class="form-control text-end fw-bold border-2" 
                    value="0.00" readonly>
            </div>
          </div>

          <div class="mb-3">
            <label for="observaciones" class="form-label fw-bold">Observaciones</label>
            <input type="text" name="observaciones" value="<?php echo $observaciones;?>" class="form-control" id="observaciones" placeholder="">
          </div>

          <div class="mt-4">
            <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
            <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion">Modificar</button>
            <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion">Eliminar</button>
            <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
          </div>
        </form>

        <div class="row mt-4">
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Consecutivo</th>
                  <th>Observaciones</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody id="tabla-registros">
              <?php foreach($lista as $comprobante){ ?>
                <tr>
                  <td><?php echo $comprobante['fecha']; ?></td>
                  <td><?php echo $comprobante['consecutivo']; ?></td>
                  <td><?php echo $comprobante['observaciones']; ?></td>
                  <td>
                    <form action="" method="post">
                      <input type="hidden" name="txtId" value="<?php echo $comprobante['id']; ?>" >
                      <input type="hidden" name="fecha" value="<?php echo $comprobante['fecha']; ?>" >
                      <input type="hidden" name="consecutivo" value="<?php echo $comprobante['consecutivo']; ?>" >
                      <input type="hidden" name="observaciones" value="<?php echo $comprobante['observaciones']; ?>" >
                      
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
// ========== INICIALIZACIÓN DE SELECT2 CUENTAS ==========
function initCuentaSelect($select) {
  $select.select2({
    placeholder: "Buscar por código o nombre...",
    allowClear: true,
    width: '100%',
    ajax: {
      url: 'obtener_cuentas_comprobantecontable.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { search: params.term || '' };
      },
      processResults: function (data) {
        return {
          results: data.map(function (cuenta) {
            return { 
              id: cuenta.valor, 
              text: cuenta.texto,
              nombre: cuenta.nombre
            };
          })
        };
      },
      cache: true
    }
  });
  
  // Al seleccionar: mostrar solo el código
  $select.on('select2:select', function (e) {
    const data = e.params.data;
    const row = $(this).closest('tr');
    
    // Crear nueva opción solo con el código
    const newOption = new Option(data.id, data.id, true, true);
    $(this).empty().append(newOption).trigger('change');
    
    // Llenar la descripción con el nombre
    row.find('input[name="descripcionCuenta"]').val(data.nombre || '');
    
    // Feedback visual
    const descInput = row.find('input[name="descripcionCuenta"]');
    descInput.css('background-color', '#d4edda');
    setTimeout(() => descInput.css('background-color', ''), 1000);
  });

  $select.on('select2:clear', function (e) {
    $(this).closest('tr').find('input[name="descripcionCuenta"]').val('');
  });
}
// ========== INICIALIZACIÓN DE SELECT2 TERCEROS ==========
function initTerceroSelect($select) {
  $select.select2({
    placeholder: "Buscar por cédula o nombre...",
    allowClear: true,
    width: '100%',
    minimumInputLength: 1,
    language: {
      inputTooShort: function() { return "Por favor ingrese al menos 1 carácter"; },
      noResults: function() { return "No se encontraron resultados"; },
      searching: function() { return "Buscando..."; },
      errorLoading: function() { return "Error al cargar los resultados"; }
    },
    ajax: {
      url: 'buscar_terceros_comprobante.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { search: params.term || '' };
      },
      processResults: function (data) {
        if (!Array.isArray(data)) {
          console.error('Los datos no son un array:', data);
          return { results: [] };
        }
        
        return {
          results: data.map(function (tercero) {
            return { 
              id: tercero.cedula,
              text: tercero.cedula + ' - ' + tercero.nombre,
              cedula: tercero.cedula,
              nombre: tercero.nombre
            };
          })
        };
      },
      cache: true
    }
  });
  
  // Al seleccionar: mostrar solo la cédula
  $select.on('select2:select', function (e) {
    const data = e.params.data;
    const row = $(this).closest('tr');
    
    // Crear nueva opción solo con cédula
    const newOption = new Option(data.cedula, data.cedula, true, true);
    $(this).empty().append(newOption).trigger('change');
    
    // Llenar nombre
    row.find('input[name="terceroNombre"]').val(data.nombre || '');
    
    // Feedback visual
    const nombreInput = row.find('input[name="terceroNombre"]');
    nombreInput.css('background-color', '#d4edda');
    setTimeout(() => nombreInput.css('background-color', ''), 1000);
  });

  $select.on('select2:clear', function (e) {
    $(this).closest('tr').find('input[name="terceroNombre"]').val('');
  });
}

// ========== CALCULAR TOTALES ==========
function calcularTotales() {
  let sumaDebito = 0;
  let sumaCredito = 0;

  document.querySelectorAll("#product-table tr").forEach(row => {
    const debito = parseFloat(row.querySelector(".debito")?.value.replace(/\./g, '').replace(',', '.') || 0);
    const credito = parseFloat(row.querySelector(".credito")?.value.replace(/\./g, '').replace(',', '.') || 0);
    sumaDebito += debito;
    sumaCredito += credito;
  });

  const diferencia = sumaDebito - sumaCredito;
  document.querySelector("#sumaDebito").value = sumaDebito.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  document.querySelector("#sumaCredito").value = sumaCredito.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  document.querySelector("#diferencia").value = diferencia.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ========== AGREGAR FILA ==========
window.addRow = function() {
  const tableBody = document.getElementById("product-table");
  const newRow = document.createElement("tr");
  
  newRow.innerHTML = `
    <td>
      <select name="cuentaContable" class="form-control cuenta-select" style="width: 100%;">
        <option value="">Buscar cuenta...</option>
      </select>
    </td>
    <td><input type="text" name="descripcionCuenta" class="form-control" value=""></td>
    <td>
      <select name="terceroCedula" class="form-control tercero-select" style="width: 100%;">
        <option value="">Buscar por cédula...</option>
      </select>
    </td>
    <td><input type="text" name="terceroNombre" class="form-control" placeholder="Nombre" value="" readonly></td>
    <td><input type="text" name="detalle" class="form-control" value=""></td>
    <td><input type="text" step="0.01" name="valorDebito" class="form-control debito" placeholder="0.00" value=""></td>
    <td><input type="text" step="0.01" name="valorCredito" class="form-control credito" placeholder="0.00" value=""></td>
    <td>
      <button type="button" class="btn-add" onclick="addRow()">+</button>
      <button type="button" class="btn-remove" style="margin-left:10px; background-color:red; color:white; border:none; border-radius:4px; cursor:pointer;">-</button>
    </td>
  `;

  newRow.querySelector(".btn-remove").onclick = function() {
    const rows = tableBody.querySelectorAll("tr");
    if (rows.length > 1) {
      $(this.closest("tr")).find('.cuenta-select').select2('destroy');
      $(this.closest("tr")).find('.tercero-select').select2('destroy');
      this.closest("tr").remove();
      calcularTotales();
    } else {
      Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: 'Debe haber al menos una fila',
        confirmButtonColor: '#3085d6'
      });
    }
  };

  tableBody.appendChild(newRow);
  initCuentaSelect($(newRow).find('.cuenta-select'));
  initTerceroSelect($(newRow).find('.tercero-select'));
  calcularTotales();
};

// ========== REMOVER FILA SEGURO ==========
function removeRowSafe(btn) {
  const rows = document.querySelectorAll("#product-table tr");
  if (rows.length > 1) {
    $(btn.closest("tr")).find('.cuenta-select').select2('destroy');
    $(btn.closest("tr")).find('.tercero-select').select2('destroy');
    btn.closest("tr").remove();
    calcularTotales();
  } else {
    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: 'Debe haber al menos una fila',
      confirmButtonColor: '#3085d6'
    });
  }
}

// ========== MODO AGREGAR ==========
function modoAgregar() {
  document.getElementById("btnAgregar").style.display = "inline-block";
  document.getElementById("btnModificar").style.display = "none";
  document.getElementById("btnEliminar").style.display = "none";
  document.getElementById("btnCancelar").style.display = "none";

  document.getElementById("txtId").value = "";
  document.getElementById("fecha").value = new Date().toISOString().split('T')[0];
  document.getElementById("consecutivo").value = "";
  document.getElementById("observaciones").value = "";

  const tableBody = document.getElementById("product-table");
  tableBody.innerHTML = `
    <tr>
      <td>
        <select name="cuentaContable" class="form-control cuenta-select" style="width: 100%;">
          <option value="">Buscar cuenta...</option>
        </select>
      </td>
      <td><input type="text" name="descripcionCuenta" class="form-control" value=""></td>
      <td>
        <select name="terceroCedula" class="form-control tercero-select" style="width: 100%;">
          <option value="">Buscar por cédula...</option>
        </select>
      </td>
      <td><input type="text" name="terceroNombre" class="form-control" placeholder="Nombre" value="" readonly></td>
      <td><input type="text" name="detalle" class="form-control" value=""></td>
      <td><input type="text" step="0.01" name="valorDebito" class="form-control debito" placeholder="0.00" value=""></td>
      <td><input type="text" step="0.01" name="valorCredito" class="form-control credito" placeholder="0.00" value=""></td>
      <td>
        <button type="button" class="btn-add" onclick="addRow()">+</button>
        <button type="button" class="btn-remove" style="margin-left:10px; background-color:red; color:white; border:none; border-radius:4px; cursor:pointer;" onclick="removeRowSafe(this)">-</button>
      </td>
    </tr>
  `;
  
  initCuentaSelect($('.cuenta-select'));
  initTerceroSelect($('.tercero-select'));

  // OBTENER CONSECUTIVO
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

// ========== DOCUMENT READY - SOLO UNA VEZ ==========
$(document).ready(function() {
  // Inicializar selects existentes
  $('.cuenta-select').each(function() {
    initCuentaSelect($(this));
  });
  
  $('.tercero-select').each(function() {
    initTerceroSelect($(this));
  });

  // Configurar modos de edición
  const id = document.getElementById("txtId").value;
  if (id && id.trim() !== "") {
    document.getElementById("btnAgregar").style.display = "none";
    document.getElementById("btnModificar").style.display = "inline-block";
    document.getElementById("btnEliminar").style.display = "inline-block";
    document.getElementById("btnCancelar").style.display = "inline-block";
  } else {
    modoAgregar();
  }

  // Botón cancelar
  document.getElementById("btnCancelar").addEventListener("click", function(e) {
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

  // Agregar botón eliminar a primera fila si no existe
  const firstRow = document.querySelector("#product-table tr");
  if (firstRow && !firstRow.querySelector(".btn-remove")) {
    const btn = document.createElement("button");
    btn.textContent = "-";
    btn.className = "btn-remove";
    btn.style.cssText = "margin-left:10px; background-color:red; color:white; border:none; border-radius:4px; cursor:pointer;";
    btn.onclick = function() { removeRowSafe(this); };
    firstRow.lastElementChild.appendChild(btn);
  }

  // Event listener para calcular totales
  document.querySelector("#product-table").addEventListener("input", function (event) {
    if (event.target.classList.contains("debito") || event.target.classList.contains("credito")) {
      calcularTotales();
    }
  });

  // Confirmación para modificar/eliminar
  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", function (e) {
      const boton = e.submitter;
      const accion = boton?.value;

      if (accion === "btnModificar" || accion === "btnEliminar") {
        e.preventDefault();

        let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar registro?";
        let texto = accion === "btnModificar"
          ? "Se actualizarán los datos de este comprobante contable."
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

// ========== ENVÍO DEL FORMULARIO ==========
document.getElementById("formComprobanteContable").addEventListener("submit", function(e) {
  const rows = document.querySelectorAll("#product-table tr");
  let detalles = [];

  rows.forEach(row => {
    const $selectCuenta = $(row).find("[name='cuentaContable']");
    const cuenta = $selectCuenta.val() || "";
    
    const $selectTercero = $(row).find("[name='terceroCedula']");
    const terceroCedula = $selectTercero.val() || "";
    const terceroNombre = row.querySelector("[name='terceroNombre']")?.value || "";
    
    const descripcion = row.querySelector("[name='descripcionCuenta']")?.value || "";
    const detalle = row.querySelector("[name='detalle']")?.value || "";
    const debito = row.querySelector("[name='valorDebito']")?.value || "0";
    const credito = row.querySelector("[name='valorCredito']")?.value || "0";

    if (cuenta || descripcion || terceroCedula || terceroNombre || detalle) {
      detalles.push({
        cuentaContable: cuenta, 
        descripcionCuenta: descripcion, 
        terceroCedula: terceroCedula, 
        terceroNombre: terceroNombre, 
        detalle: detalle, 
        valorDebito: debito, 
        valorCredito: credito
      });
    }
  });

  const inputDetalles = document.createElement("input");
  inputDetalles.type = "hidden";
  inputDetalles.name = "detalles";
  inputDetalles.value = JSON.stringify(detalles);
  this.appendChild(inputDetalles);
});

// ========== ESTABLECER FECHA ACTUAL ==========
window.addEventListener('DOMContentLoaded', function() {
  const fechaInput = document.getElementById('fecha');
  const txtId = document.getElementById('txtId').value;
 
  if (!txtId || txtId.trim() === "") {
    const hoy = new Date();
    const year = hoy.getFullYear();
    const month = String(hoy.getMonth() + 1).padStart(2, '0');
    const day = String(hoy.getDate()).padStart(2, '0');
    const fechaLocal = `${year}-${month}-${day}`;
   
    fechaInput.value = fechaLocal;
  }
  
  // OBTENER CONSECUTIVO AL INICIO
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
    </section> <!-- End Services Section -->

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