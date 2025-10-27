<?php
include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$txtId = $_POST['txtId'] ?? "";
$codigoDocumento = $_POST['codigoDocumento'] ?? "";
$descripcionDocumento = $_POST['descripcionDocumento'] ?? "";
$resolucionDian = isset($_POST['resolucionDian']) ? 1 : 0;
$numeroResolucion = $_POST['numeroResolucion'] ?? "";
$fechaInicio = $_POST['fechaInicio'] ?? "";
$vigencia = $_POST['vigencia'] ?? "";
$fechaFinalizacion = $_POST['fechaFinalizacion'] ?? "";
$prefijo = $_POST['prefijo'] ?? "";
$consecutivoInicial = $_POST['consecutivoInicial'] ?? "";
$consecutivoFinal = $_POST['consecutivoFinal'] ?? "";
$retenciones = isset($_POST['retenciones']) ? 1 : 0;
$tipoRetencion = $_POST['tipoRetencion'] ?? "";
$autoRetenciones = isset($_POST['autoRetenciones']) ? 1 : 0;
$tipoAutoretencion = $_POST['tipoAutoretencion'] ?? "";
$activo = isset($_POST['activo']) ? 1 : 0;

$accion = $_POST['accion'] ?? "";

// Inicializa $lista para evitar el warning
$lista = [];

switch($accion){
  case "btnAgregar":
      $sentencia = $pdo->prepare("INSERT INTO facturadeventa(
        codigoDocumento, descripcionDocumento, resolucionDian, numeroResolucion,
        fechaInicio, vigencia, fechaFinalizacion, prefijo,
        consecutivoInicial, consecutivoFinal, retenciones, tipoRetencion,
        autoRetenciones, tipoAutoretencion, activo
      ) VALUES (
        :codigoDocumento, :descripcionDocumento, :resolucionDian, :numeroResolucion,
        :fechaInicio, :vigencia, :fechaFinalizacion, :prefijo,
        :consecutivoInicial, :consecutivoFinal, :retenciones, :tipoRetencion,
        :autoRetenciones, :tipoAutoretencion, :activo
      )");

      $sentencia->bindParam(':codigoDocumento', $codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento', $descripcionDocumento);
      $sentencia->bindParam(':resolucionDian', $resolucionDian);
      $sentencia->bindParam(':numeroResolucion', $numeroResolucion);
      $sentencia->bindParam(':fechaInicio', $fechaInicio);
      $sentencia->bindParam(':vigencia', $vigencia);
      $sentencia->bindParam(':fechaFinalizacion', $fechaFinalizacion);
      $sentencia->bindParam(':prefijo', $prefijo);
      $sentencia->bindParam(':consecutivoInicial', $consecutivoInicial);
      $sentencia->bindParam(':consecutivoFinal', $consecutivoFinal);
      $sentencia->bindParam(':retenciones', $retenciones);
      $sentencia->bindParam(':tipoRetencion', $tipoRetencion);
      $sentencia->bindParam(':autoRetenciones', $autoRetenciones);
      $sentencia->bindParam(':tipoAutoretencion', $tipoAutoretencion);
      $sentencia->bindParam(':activo', $activo);
      $sentencia->execute();

      // Mostrar alerta con SweetAlert2
      header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
      exit; // Evita reenvío del formulario
  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE facturadeventa SET 
          codigoDocumento = :codigoDocumento,
          descripcionDocumento = :descripcionDocumento,
          resolucionDian = :resolucionDian,
          numeroResolucion = :numeroResolucion,
          fechaInicio = :fechaInicio,
          vigencia = :vigencia,
          fechaFinalizacion = :fechaFinalizacion,
          prefijo = :prefijo,
          consecutivoInicial = :consecutivoInicial,
          consecutivoFinal = :consecutivoFinal,
          retenciones = :retenciones,
          tipoRetencion = :tipoRetencion,
          autoRetenciones = :autoRetenciones,
          tipoAutoretencion = :tipoAutoretencion,
          activo = :activo
        WHERE id = :id");

      $sentencia->bindParam(':codigoDocumento', $codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento', $descripcionDocumento);
      $sentencia->bindParam(':resolucionDian', $resolucionDian);
      $sentencia->bindParam(':numeroResolucion', $numeroResolucion);
      $sentencia->bindParam(':fechaInicio', $fechaInicio);
      $sentencia->bindParam(':vigencia', $vigencia);
      $sentencia->bindParam(':fechaFinalizacion', $fechaFinalizacion);
      $sentencia->bindParam(':prefijo', $prefijo);
      $sentencia->bindParam(':consecutivoInicial', $consecutivoInicial);
      $sentencia->bindParam(':consecutivoFinal', $consecutivoFinal);
      $sentencia->bindParam(':retenciones', $retenciones);
      $sentencia->bindParam(':tipoRetencion', $tipoRetencion);
      $sentencia->bindParam(':autoRetenciones', $autoRetenciones);
      $sentencia->bindParam(':tipoAutoretencion', $tipoAutoretencion);
      $sentencia->bindParam(':activo', $activo);
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();

      // Redirigir y mostrar alerta
    header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
    exit;
  break;

  case "btnEliminar":
      $sentencia = $pdo->prepare("DELETE FROM facturadeventa WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();

      header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
      exit;
  break;
}

// Consulta para llenar la tabla y evitar el warning
$sentencia = $pdo->prepare("SELECT * FROM facturadeventa");
$sentencia->execute();
$lista = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  switch ("<?= $_GET['msg'] ?>") {
    case "agregado":
      Swal.fire({
        icon: 'success',
        title: 'Guardado exitosamente',
        text: 'El parametro factura de venta se ha agregado correctamente',
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
        text: 'El parametro factura de venta fue eliminado del registro',
        confirmButtonColor: '#3085d6'
      });
      break;
  }

  // Quita el parámetro ?msg=... de la URL sin recargar
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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style> 
    input[type="text"] {
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
    <button class="btn-ir" onclick="window.location.href='catalogosparametrosdedocumentos.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
    <div class="container" data-aos="fade-up">

      
      <div class="section-title">
        <h2>FACTURA DE VENTA</h2>
        <p>Para crear un nuevo tipo de documento diligencie los campos a continuación:</p>
        <p>(Los campos marcados con * son obligatorios)</p>
      </div>
    <div id="pdfContent">
      <form id="formDocumentos" action="" method="post" class="container mt-3">

        <!-- ID oculto -->
        <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

        <!-- Código y Descripción -->
        <div class="row g-3">
          <div class="col-md-4">
            <label for="codigoDocumento" class="form-label fw-bold">Código de documento*</label>
            <input type="number" class="form-control" id="codigoDocumento" name="codigoDocumento"
                  placeholder="Ingresa el código..."
                  value="<?php echo $codigoDocumento; ?>" required>
          </div>

          <div class="col-md-8">
            <label for="descripcionDocumento" class="form-label fw-bold">Descripción del documento*</label>
            <input type="text" class="form-control" id="descripcionDocumento" name="descripcionDocumento"
                  placeholder="Ingresa la descripción..."
                  value="<?php echo $descripcionDocumento; ?>" required>
          </div>
        </div>

        <!-- Resolución DIAN -->
        <div class="row g-3 mt-2">
          <div class="col-md-6 d-flex align-items-center">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="resolucionDian" name="resolucionDian"
                    <?php if ($resolucionDian) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="resolucionDian">
                Documento con resolución DIAN
              </label>
            </div>
          </div>
        </div>

        <!-- Datos de resolución -->
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="numeroResolucion" class="form-label fw-bold">Número de resolución</label>
            <input type="text" class="form-control" id="numeroResolucion" name="numeroResolucion"
                  placeholder="Ej: 12345"
                  value="<?php echo $numeroResolucion; ?>">
          </div>

          <div class="col-md-4">
            <label for="fechaInicio" class="form-label fw-bold">Fecha de inicio</label>
            <input type="date" class="form-control" id="fechaInicio" name="fechaInicio"
                  value="<?php echo $fechaInicio; ?>">
          </div>

          <div class="col-md-4">
            <label for="vigencia" class="form-label fw-bold">Vigencia (meses)</label>
            <input type="number" class="form-control" id="vigencia" name="vigencia"
                  placeholder="Ej: 12"
                  value="<?php echo $vigencia; ?>">
          </div>
        </div>

        <!-- Fecha finalización -->
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="fechaFinalizacion" class="form-label fw-bold">Fecha de finalización</label>
            <input type="date" class="form-control" id="fechaFinalizacion" name="fechaFinalizacion"
                  value="<?php echo $fechaFinalizacion; ?>" readonly>
          </div>
        </div>

        <!-- Prefijo y consecutivos -->
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="prefijo" class="form-label fw-bold">Prefijo</label>
            <input type="text" class="form-control" id="prefijo" name="prefijo"
                  placeholder="Ej: FAC"
                  value="<?php echo $prefijo; ?>">
          </div>

          <div class="col-md-4">
            <label for="consecutivoInicial" class="form-label fw-bold">Consecutivo inicial</label>
            <input type="number" class="form-control" id="consecutivoInicial" name="consecutivoInicial"
                  placeholder="Ej: 1"
                  value="<?php echo $consecutivoInicial; ?>">
          </div>

          <div class="col-md-4">
            <label for="consecutivoFinal" class="form-label fw-bold">Consecutivo final</label>
            <input type="number" class="form-control" id="consecutivoFinal" name="consecutivoFinal"
                  placeholder="Ej: 1000"
                  value="<?php echo $consecutivoFinal; ?>">
          </div>
        </div>

        <!-- Retenciones -->
        <div class="row g-3 mt-3">
          <div class="col-md-6">
            <div class="form-check mb-2">
              <input type="checkbox" class="form-check-input" id="retenciones" name="retenciones"
                    <?php if ($retenciones) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="retenciones">Aplica retenciones</label>
            </div>
            <select class="form-select" id="tipoRetencion" name="tipoRetencion">
              <option value="">Seleccione el tipo de retención</option>
              <option value="1" <?php if ($tipoRetencion == 1) echo 'selected'; ?>>Retención a la Renta</option>
              <option value="2" <?php if ($tipoRetencion == 2) echo 'selected'; ?>>Retención de IVA</option>
              <option value="3" <?php if ($tipoRetencion == 3) echo 'selected'; ?>>Retención de ICA</option>
            </select>
          </div>

          <div class="col-md-6">
            <div class="form-check mb-2">
              <input type="checkbox" class="form-check-input" id="autoRetenciones" name="autoRetenciones"
                    <?php if ($autoRetenciones) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="autoRetenciones">Aplica autoretenciones</label>
            </div>
            <select class="form-select" id="tipoAutoretencion" name="tipoAutoretencion">
              <option value="">Seleccione el tipo de autoretención</option>
              <option value="1" <?php if ($tipoAutoretencion == 1) echo 'selected'; ?>>Autorretención a la Renta</option>
              <option value="2" <?php if ($tipoAutoretencion == 2) echo 'selected'; ?>>Autorretención de IVA</option>
              <option value="3" <?php if ($tipoAutoretencion == 3) echo 'selected'; ?>>Autorretención de ICA</option>
            </select>
          </div>
        </div>

        <!-- NUEVO CAMPO: Cuentas contables de retención -->
        <div class="row g-3 mt-3">
          <div class="col-md-6">
            <label for="cuentasContablesRetencion" class="form-label fw-bold">
              Cuentas contables de retención
            </label>
            <input type="text" class="form-control" id="cuentasContablesRetencion" name="cuentasContablesRetencion"
                  placeholder="Ingrese los códigos contables manualmente..."
                  value="">
            <small class="text-muted">Ejemplo: 236540, 236570</small>
          </div>
        </div>

        <!-- Activo -->
        <div class="row g-3 mt-3">
          <div class="col-md-6 d-flex align-items-center">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="activo" name="activo"
                    <?php if ($activo) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="activo">Documento activo</label>
            </div>
          </div>
        </div>

      </div>

        <!-- Botones -->
        <div class="mt-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion">Modificar</button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion">Eliminar</button>
          <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
          <button type="button" id="btnDescargar" class="btn btn-success">
            💾 Guardar (en PC)
          </button>
          
          <button type="button" id="btnImprimir" class="btn btn-primary">
             🖨️ Imprimir 
          </button>
        </div>

        </form> 

      <div class="row">
     <div class="table-container">

     <table class="table-container">
      <thead>
        <tr>
          <th>Codigo Documento</th>
          <th>Descripción Documento</th>
          <th>Resolucion Dian</th>
          <th>Numero de Resolución</th>
          <th>Fecha Inicio</th>
          <th>Vigencia</th>
          <th>Fecha Finalización</th>
          <th>Prefijo</th>
          <th>Consecutivo Inicial</th>
          <th>Consecutivo Final</th>
          <th>Retenciones</th>
          <th>Tipo Retención</th>
          <th>Autoretenciones</th>
          <th>Tipo de regimen</th>
          <th>Activo</th>
          <th>Acción</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach($lista as $usuario){ ?>
          <tr>
            <td><?php echo htmlspecialchars($usuario['codigoDocumento']); ?></td>
            <td><?php echo htmlspecialchars($usuario['descripcionDocumento']); ?></td>
            <td><?php echo htmlspecialchars($usuario['resolucionDian'])? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
            <td><?php echo htmlspecialchars($usuario['numeroResolucion']); ?></td>
            <td><?php echo htmlspecialchars($usuario['fechaInicio']); ?></td>
            <td><?php echo htmlspecialchars($usuario['vigencia']); ?></td>
            <td><?php echo htmlspecialchars($usuario['fechaFinalizacion']); ?></td>
            <td><?php echo htmlspecialchars($usuario['prefijo']); ?></td>
            <td><?php echo htmlspecialchars($usuario['consecutivoInicial']); ?></td>
            <td><?php echo htmlspecialchars($usuario['consecutivoFinal']); ?></td>
            <td><?php echo htmlspecialchars($usuario['retenciones'])? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
            <td><?php echo htmlspecialchars($usuario['tipoRetencion']); ?></td>
            <td><?php echo htmlspecialchars($usuario['autoRetenciones'])? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
            <td><?php echo htmlspecialchars($usuario['tipoAutoretencion']); ?></td>
            <td><?php echo htmlspecialchars($usuario['activo'])? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>

            <td>
              <form action="" method="post" style="display:flex; gap:5px;">
                <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                <input type="hidden" name="codigoDocumento" value="<?php echo $usuario['codigoDocumento']; ?>">
                <input type="hidden" name="descripcionDocumento" value="<?php echo $usuario['descripcionDocumento']; ?>">
                <input type="hidden" name="resolucionDian" value="<?php echo $usuario['resolucionDian']; ?>">
                <input type="hidden" name="numeroResolucion" value="<?php echo $usuario['numeroResolucion']; ?>">
                <input type="hidden" name="fechaInicio" value="<?php echo $usuario['fechaInicio']; ?>">
                <input type="hidden" name="vigencia" value="<?php echo $usuario['vigencia']; ?>">
                <input type="hidden" name="fechaFinalizacion" value="<?php echo $usuario['fechaFinalizacion']; ?>">
                <input type="hidden" name="prefijo" value="<?php echo $usuario['prefijo']; ?>">
                <input type="hidden" name="consecutivoInicial" value="<?php echo $usuario['consecutivoInicial']; ?>">
                <input type="hidden" name="consecutivoFinal" value="<?php echo $usuario['consecutivoFinal']; ?>">
                <input type="hidden" name="retenciones" value="<?php echo $usuario['retenciones']; ?>">
                <input type="hidden" name="tipoRetencion" value="<?php echo $usuario['tipoRetencion']; ?>">
                <input type="hidden" name="autoRetenciones" value="<?php echo $usuario['autoRetenciones']; ?>">
                <input type="hidden" name="tipoAutoretencion" value="<?php echo $usuario['tipoAutoretencion']; ?>">
                <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>">

                <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info btn-editar-pventa" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="submit" value="btnEliminar" name="accion" class="btn btn-sm btn-danger" title="Eliminar">
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
        /* El campo fechaFinalizacion se calcula automáticamente a partir de fechaInicio y vigencia */
        document.addEventListener('DOMContentLoaded', function () {
          const fechaInicioInput = document.getElementById('fechaInicio');
          const vigenciaInput = document.getElementById('vigencia');
          const fechaFinalInput = document.getElementById('fechaFinalizacion');

          function calcularFechaFinalizacion() {
            const fechaInicio = new Date(fechaInicioInput.value);
            const vigencia = parseInt(vigenciaInput.value);

            if (!isNaN(fechaInicio.getTime()) && !isNaN(vigencia)) {
              const nuevaFecha = new Date(fechaInicio);
              nuevaFecha.setMonth(nuevaFecha.getMonth() + vigencia);
              const yyyy = nuevaFecha.getFullYear();
              const mm = String(nuevaFecha.getMonth() + 1).padStart(2, '0');
              const dd = String(nuevaFecha.getDate()).padStart(2, '0');
              fechaFinalInput.value = `${yyyy}-${mm}-${dd}`;
            } else {
              fechaFinalInput.value = '';
            }
          }

          fechaInicioInput.addEventListener('change', calcularFechaFinalizacion);
          vigenciaInput.addEventListener('input', calcularFechaFinalizacion);
        });

        // Script para alternar botones
        document.addEventListener("DOMContentLoaded", function() {
          const id = document.getElementById("txtId").value;
          const btnAgregar = document.getElementById("btnAgregar");
          const btnModificar = document.getElementById("btnModificar");
          const btnEliminar = document.getElementById("btnEliminar");
          const btnCancelar = document.getElementById("btnCancelar");
          const btnDescargar = document.getElementById("btnDescargar");
          const btnImprimir = document.getElementById("btnImprimir");
          const form = document.getElementById("formDocumentos");

          function modoAgregar() {
            // Ocultar/mostrar botones
            btnAgregar.style.display = "inline-block";
            btnModificar.style.display = "none";
            btnEliminar.style.display = "none";
            btnCancelar.style.display = "none";
            btnDescargar.style.display = "none";
            btnImprimir.style.display = "none";

            // Limpiar todos los campos manualmente
            form.querySelectorAll("input, select, textarea").forEach(el => {
              if (el.type === "radio" || el.type === "checkbox") {
                el.checked = false;
              } else {
                el.value = "";
              }
            });

            // Si tienes checkbox "Activo", lo marcamos por defecto
            const chkActivo = document.querySelector('input[name="activo"]');
            if (chkActivo) chkActivo.checked = true;

            // Asegurar que el ID quede vacío
            const txtId = document.getElementById("txtId");
            if (txtId) txtId.value = "";
          }

          // Estado inicial (modo modificar o agregar)
          if (id && id.trim() !== "") {
            btnAgregar.style.display = "none";
            btnModificar.style.display = "inline-block";
            btnEliminar.style.display = "inline-block";
            btnCancelar.style.display = "inline-block";
            btnDescargar.style.display = "inline-block";
            btnImprimir.style.display = "inline-block";
          } else {
            modoAgregar();
          }

          // Evento cancelar
          btnCancelar.addEventListener("click", function(e) {
            e.preventDefault();
            modoAgregar();
            
            // AJUSTE ADICIONAL: Limpiar los parámetros de edición de la URL
            if (window.history.replaceState) {
                const url = new URL(window.location);
                // Elimina todos los parámetros POST que se cargan al editar
                url.searchParams.forEach((value, key) => {
                    if (key !== 'msg') { // Dejamos 'msg' por si acaso
                        url.searchParams.delete(key);
                    }
                });
                window.history.replaceState({}, document.title, url);
            }
           });
        });

        // Funciones de confirmación con SweetAlert2
          document.addEventListener("DOMContentLoaded", () => {
          // Selecciona TODOS los formularios de la página
          const forms = document.querySelectorAll("form");

          forms.forEach((form) => {
            form.addEventListener("submit", function (e) {
              const boton = e.submitter; // botón que disparó el envío
              const accion = boton?.value;

              // Solo mostrar confirmación para modificar o eliminar
              if (accion === "btnModificar" || accion === "btnEliminar") {
                e.preventDefault(); // detener envío temporalmente

                let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar registro?";
                let texto = accion === "btnModificar"
                  ? "Se actualizarán los datos de esta cuenta contable."
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
                    // Crear (si no existe) un campo oculto con la acción seleccionada
                    let inputAccion = form.querySelector("input[name='accionOculta']");
                    if (!inputAccion) {
                      inputAccion = document.createElement("input");
                      inputAccion.type = "hidden";
                      inputAccion.name = "accion";
                      form.appendChild(inputAccion);
                    }
                    inputAccion.value = accion;

                    form.submit(); // Enviar el formulario correspondiente
                  }
                });
              }
            });
          });
        });

        // --- FUNCIONALIDAD DE GUARDAR (en PC) A PDF ---
        document.addEventListener("DOMContentLoaded", function() {
            const btnDescargar = document.getElementById("btnDescargar");

            if (btnDescargar) {
                btnDescargar.addEventListener("click", function() {
                    // Elemento HTML que queremos convertir a PDF
                    const element = document.getElementById('pdfContent');
                    
                    // Opcional: Obtener el código del documento para el nombre del archivo
                    const codigoDocumento = document.getElementById('codigoDocumento').value || 'Factura-Venta';
                    
                    // 1. Configuración de html2pdf
                    const opt = {
                    // Márgenes muy reducidos o cero en la parte superior
                    margin: [0.1, 0.5, 0.5, 0.5], // [arriba, derecha, abajo, izquierda]
                    filename: `${codigoDocumento}_FacturaDeVenta.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { 
                             scale: 2, 
                             logging: false, 
                             dpi: 192, 
                             letterRendering: true,
                             scrollY: 0,
                             windowHeight: element.scrollHeight // Asegura que se capture todo el alto
                        },
                        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
                    };

                    // 2. Ejecutar la conversión
                    html2pdf().set(opt).from(element).save();
                    
                    // Mensaje de SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'PDF Generado',
                        text: 'El formulario se ha guardado como PDF en su PC.',
                        confirmButtonColor: '#3085d6'
                    });
                });
            }
        });
        // --- FUNCIONALIDAD DE IMPRIMIR ---
        document.addEventListener("DOMContentLoaded", function() {
            const btnImprimir = document.getElementById("btnImprimir");

            if (btnImprimir) {
                btnImprimir.addEventListener("click", function() {
                    window.print(); 
                });
            }
        });
      </script>    
    </div>
  </section><!-- End Services Section -->

    <!-- Footer -->
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