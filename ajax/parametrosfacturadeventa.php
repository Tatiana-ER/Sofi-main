<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/auth_check.php'; ?>
<?php
require_once '../config/database.php';
 

$pdo = Database::getConnection();
 
$search = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
 
if (isset($_GET['search'])) {
    error_log(" Buscando: " . $_GET['search']);
}
 
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
$cuentaRetenciones = $_POST['cuentaRetenciones'] ?? ""; // ✅ Nuevo campo
$activo = isset($_POST['activo']) ? 1 : 0;
 
$accion = $_POST['accion'] ?? "";
 
// Inicializa $lista para evitar el warning
$lista = [];
 
switch ($accion) {
  case "btnAgregar":
      $sentencia = $pdo->prepare("INSERT INTO facturadeventa(
        codigoDocumento, descripcionDocumento, resolucionDian, numeroResolucion,
        fechaInicio, vigencia, fechaFinalizacion, prefijo,
        consecutivoInicial, consecutivoFinal, retenciones, tipoRetencion,
        autoRetenciones, tipoAutoretencion, cuentaRetenciones, activo
      ) VALUES (
        :codigoDocumento, :descripcionDocumento, :resolucionDian, :numeroResolucion,
        :fechaInicio, :vigencia, :fechaFinalizacion, :prefijo,
        :consecutivoInicial, :consecutivoFinal, :retenciones, :tipoRetencion,
        :autoRetenciones, :tipoAutoretencion, :cuentaRetenciones, :activo
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
      $sentencia->bindParam(':cuentaRetenciones', $cuentaRetenciones);
      $sentencia->bindParam(':activo', $activo);
      $sentencia->execute();
 
      header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
      exit;
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
          cuentaRetenciones = :cuentaRetenciones, -- Nuevo campo
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
      $sentencia->bindParam(':cuentaRetenciones', $cuentaRetenciones);
      $sentencia->bindParam(':activo', $activo);
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();
 
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
 
 
// Consulta para llenar la tabla (faltaba)
if (!empty($search)) {
    $sentencia = $pdo->prepare("SELECT * FROM facturadeventa
                                WHERE codigoDocumento LIKE :search
                                   OR descripcionDocumento LIKE :search
                                   OR tipoRetencion LIKE :search
                                   OR cuentaRetenciones LIKE :search
                                ORDER BY id DESC");
    $sentencia->bindValue(':search', "%$search%");
} else {
    $sentencia = $pdo->prepare("SELECT * FROM facturadeventa ORDER BY id DESC");
}
 
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
        confirmButtonColor: '#103669'
      });
      break;
 
    case "modificado":
      Swal.fire({
        icon: 'success',
        title: 'Modificado correctamente',
        text: 'Los datos se actualizaron con éxito',
        confirmButtonColor: '#103669'
      });
      break;
 
    case "eliminado":
      Swal.fire({
        icon: 'success',
        title: 'Eliminado correctamente',
        text: 'El parametro factura de venta fue eliminado del registro',
        confirmButtonColor: '#103669'
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
  <link href="../assets/img/favicon.png" rel="icon">
  <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">
 
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i%22 rel="stylesheet">
 
  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
 
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
 
  <link href="../assets/css/improved-style.css" rel="stylesheet">

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

    .tabla-documentos {
        width: 100%;
    }

    .tabla-documentos th,
    .tabla-documentos td {
        padding: 8px 10px;
        vertical-align: middle;
    }

    .tabla-documentos .info-principal {
        font-weight: 600;
        color: #333;
        line-height: 1.25;
    }

    .tabla-documentos .info-secundaria {
        font-size: 0.82rem;
        color: #6c757d;
        margin-top: 2px;
        line-height: 1.25;
    }

    .tabla-documentos .info-terciaria {
        font-size: 0.78rem;
        color: #6c757d;
        margin-top: 3px;
        line-height: 1.25;
    }

    .tabla-documentos .estado-activo {
        color: #198754;
        font-weight: 600;
        white-space: nowrap;
    }

    .tabla-documentos .estado-inactivo {
        color: #dc3545;
        font-weight: 600;
        white-space: nowrap;
    }
  </style>
 
 
</head>
 
<body>
 
  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="../dashboard.php">
          <img src="../Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li>
            <a class="nav-link scrollto active" href="../dashboard.php" style="color: darkblue;">Inicio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="../perfil.php" style="color: darkblue;">Mi Negocio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="../index.php" style="color: darkblue;">Cerrar Sesión</a>
          </li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->
 
   <!-- ======= Services Section ======= -->
   <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='../views/catalogos/catalogosparametrosdedocumentos.php'">
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
                placeholder="Ej: Factura de venta, Nota crédito..."
                value="<?php echo $descripcionDocumento; ?>" required>
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
              <option value="Retención a la Renta" <?php if ($tipoRetencion == "Retención a la Renta") echo 'selected'; ?>>Retención a la Renta</option>
              <option value="Retención de IVA" <?php if ($tipoRetencion == "Retención de IVA") echo 'selected'; ?>>Retención de IVA</option>
              <option value="Retención de ICA" <?php if ($tipoRetencion == "Retención de ICA") echo 'selected'; ?>>Retención de ICA</option>
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
              <option value="Autorretención a la Renta" <?php if ($tipoAutoretencion == "Autorretención a la Renta") echo 'selected'; ?>>Autorretención a la Renta</option>
              <option value="Autorretención de IVA" <?php if ($tipoAutoretencion == "Autorretención de IVA") echo 'selected'; ?>>Autorretención de IVA</option>
              <option value="Autorretención de ICA" <?php if ($tipoAutoretencion == "Autorretención de ICA") echo 'selected'; ?>>Autorretención de ICA</option>
            </select>
          </div>
        </div>
 
        <!-- NUEVO CAMPO: Cuentas contables de retención -->
        <div class="row g-3 mt-3">
          <div class="col-md-6">
            <label for="cuentaRetenciones" class="form-label fw-bold">
              Cuentas contables de retención
            </label>
            <select id="cuentaRetenciones" class="form-select" name="cuentaRetenciones">
              <option value="">Seleccione una cuenta...</option>
            </select>
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
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn-agregar" name="accion">Agregar</button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn-modificar" name="accion">Modificar</button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn-eliminar-item" name="accion">Eliminar</button>
          <button id="btnCancelar" type="button" class="btn-cancelar" style="display:none;">Cancelar</button>
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

            <table class="tabla-documentos">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Resolución DIAN</th>
                        <th>Vigencia</th>
                        <th>Numeración</th>
                        <th>Retenciones</th>
                        <th>Autoretenciones</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($lista as $usuario){ ?>

                    <tr>

                        <!-- DOCUMENTO -->
                        <td>
                            <div class="info-principal">
                                <?php echo htmlspecialchars($usuario['codigoDocumento']); ?>
                            </div>

                            <div class="info-secundaria">
                                <?php echo htmlspecialchars($usuario['descripcionDocumento']); ?>
                            </div>
                        </td>


                        <!-- RESOLUCIÓN DIAN -->
                        <td>
                            <?php if(!empty($usuario['numeroResolucion'])){ ?>
                                <div class="info-primaria">
                                    N.º <?php echo htmlspecialchars($usuario['numeroResolucion']); ?>
                                </div>
                            <?php } ?>
                        </td>


                        <!-- VIGENCIA -->
                        <td>
                            <div class="info-principal">
                                <?php echo htmlspecialchars($usuario['fechaInicio']); ?>
                            </div>

                            <div class="info-secundaria">
                                Vigencia: <?php echo htmlspecialchars($usuario['vigencia']); ?>
                            </div>

                            <?php if(!empty($usuario['fechaFinalizacion'])){ ?>
                                <div class="info-terciaria">
                                    Hasta: <?php echo htmlspecialchars($usuario['fechaFinalizacion']); ?>
                                </div>
                            <?php } ?>
                        </td>


                        <!-- NUMERACIÓN -->
                        <td>
                            <div class="info-principal">
                                Prefijo: <?php echo htmlspecialchars($usuario['prefijo']); ?>
                            </div>

                            <div class="info-secundaria">
                                <?php echo htmlspecialchars($usuario['consecutivoInicial']); ?>
                                -
                                <?php echo htmlspecialchars($usuario['consecutivoFinal']); ?>
                            </div>
                        </td>


                        <!-- RETENCIONES -->
                        <td>
                            <div class="info-principal">
                                <?php if($usuario['retenciones']){ ?>
                                    <i class="fas fa-check-circle text-success"></i> Sí
                                <?php } else { ?>
                                    <i class="fas fa-times-circle text-danger"></i> No
                                <?php } ?>
                            </div>

                            <?php if(!empty($usuario['tipoRetencion'])){ ?>
                                <div class="info-secundaria">
                                    <?php echo htmlspecialchars($usuario['tipoRetencion']); ?>
                                </div>
                            <?php } ?>

                            <?php if(!empty($usuario['cuentaRetenciones'])){ ?>
                                <div class="info-terciaria">
                                    Cuenta: <?php echo htmlspecialchars($usuario['cuentaRetenciones']); ?>
                                </div>
                            <?php } ?>
                        </td>


                        <!-- AUTORETENCIONES -->
                        <td>
                            <div class="info-principal">
                                <?php if($usuario['autoRetenciones']){ ?>
                                    <i class="fas fa-check-circle text-success"></i> Sí
                                <?php } else { ?>
                                    <i class="fas fa-times-circle text-danger"></i> No
                                <?php } ?>
                            </div>

                            <?php if(!empty($usuario['tipoAutoretencion'])){ ?>
                                <div class="info-secundaria">
                                    <?php echo htmlspecialchars($usuario['tipoAutoretencion']); ?>
                                </div>
                            <?php } ?>
                        </td>


                        <!-- ESTADO -->
                        <td class="text-center">
                            <?php if($usuario['activo']){ ?>
                                <span class="estado-activo">
                                    <i class="fas fa-check-circle text-success"></i>
                                    Activo
                                </span>
                            <?php } else { ?>
                                <span class="estado-inactivo">
                                    <i class="fas fa-times-circle text-danger"></i>
                                    Inactivo
                                </span>
                            <?php } ?>
                        </td>


                        <!-- ACCIONES -->
                        <td class="text-center">
                            <div class="dropdown">

                                <button class="btn btn-sm btn-outline-secondary"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-display="static"
                                        aria-expanded="false"
                                        title="Opciones">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">

                                    <!-- EDITAR -->
                                    <li>
                                        <form action="" method="post" class="d-inline">

                                            <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                                            <input type="hidden" name="codigoDocumento" value="<?php echo $usuario['codigoDocumento']; ?>">
                                            <input type="hidden" name="descripcionDocumento" value="<?php echo $usuario['descripcionDocumento']; ?>">
                                            <input type="hidden" name="numeroResolucion" value="<?php echo $usuario['numeroResolucion']; ?>">
                                            <input type="hidden" name="fechaInicio" value="<?php echo $usuario['fechaInicio']; ?>">
                                            <input type="hidden" name="vigencia" value="<?php echo $usuario['vigencia']; ?>">
                                            <input type="hidden" name="fechaFinalizacion" value="<?php echo $usuario['fechaFinalizacion']; ?>">
                                            <input type="hidden" name="prefijo" value="<?php echo $usuario['prefijo']; ?>">
                                            <input type="hidden" name="consecutivoInicial" value="<?php echo $usuario['consecutivoInicial']; ?>">
                                            <input type="hidden" name="consecutivoFinal" value="<?php echo $usuario['consecutivoFinal']; ?>">
                                            <input type="hidden" name="retenciones" value="<?php echo $usuario['retenciones']; ?>">
                                            <input type="hidden" name="tipoRetencion" value="<?php echo $usuario['tipoRetencion']; ?>">
                                            <input type="hidden" name="cuentaRetenciones" value="<?php echo $usuario['cuentaRetenciones']; ?>">
                                            <input type="hidden" name="autoRetenciones" value="<?php echo $usuario['autoRetenciones']; ?>">
                                            <input type="hidden" name="tipoAutoretencion" value="<?php echo $usuario['tipoAutoretencion']; ?>">
                                            <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>">

                                            <button type="submit"
                                                    name="accion"
                                                    value="btnEditar"
                                                    class="dropdown-item">
                                                <i class="fas fa-edit me-2"></i>
                                                Editar
                                            </button>

                                        </form>
                                    </li>

                                    <!-- ELIMINAR -->
                                    <li>
                                        <form action="" method="post" class="d-inline">

                                            <input type="hidden"
                                                  name="txtId"
                                                  value="<?php echo $usuario['id']; ?>">

                                            <button type="submit"
                                                    name="accion"
                                                    value="btnEliminar"
                                                    class="dropdown-item text-danger">
                                                <i class="fas fa-trash-alt me-2"></i>
                                                Eliminar
                                            </button>

                                        </form>
                                    </li>

                                </ul>
                            </div>
                        </td>

                    </tr>

                    <?php } ?>
                </tbody>
            </table>

        </div>
    </div>
 
      <script>
        // Cuentas contables de retención con Select2 y búsqueda AJAX cuentaRetenciones
        $(document).ready(function () {
          // Inicializar Select2 para cuentas de retención
          $('#cuentaRetenciones').select2({
            placeholder: 'Seleccione o busque una cuenta de retención',
            allowClear: true,
            ajax: {
              url: 'obtener_cuentas_retencion.php',
              dataType: 'json',
              delay: 250,
              data: function (params) {
                return { search: params.term || '' }; // Si escribe algo, se busca globalmente
              },
              processResults: function (data) {
                return {
                  results: data.map(function (item) {
                    return { id: item.valor, text: item.texto };
                  }),
                };
              },
              cache: true,
            },
            language: {
              noResults: function () {
                return "No se encontraron cuentas";
              },
              searching: function () {
                return "Buscando...";
              },
              inputTooShort: function () {
                return "Escriba para buscar otras cuentas";
              },
            },
            minimumInputLength: 0
          });
 
        });
 
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
                  confirmButtonColor: accion === "btnModificar" ? "#103669" : "#eb0404",
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
                        confirmButtonColor: '#103669'
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

        // Solución: mover el menú desplegable a <body> para que no lo recorte el scroll de la tabla
    document.addEventListener('show.bs.dropdown', function (e) {
        const button = e.target;
        const menu = button.nextElementSibling; // el <ul class="dropdown-menu">

        if (!menu || !menu.classList.contains('dropdown-menu')) return;

        // Guarda dónde estaba originalmente para devolverlo después
        menu._originalParent = menu.parentNode;
        menu._originalNextSibling = menu.nextSibling;

        document.body.appendChild(menu);
        menu.style.position = 'fixed';
        menu.style.zIndex = '3000';
        menu.style.display = 'block';
        menu.style.width = '220px'; // ancho fijo: evita que se estire al reposicionar

        const posicionar = () => {
            const rect = button.getBoundingClientRect();
            const menuAncho = menu.offsetWidth;

            // Alinea el borde derecho del menú con el borde derecho del botón (como dropdown-menu-end)
            let left = rect.right - menuAncho;
            if (left < 8) left = 8; // evita que se salga por la izquierda

            let top = rect.bottom + 4;
            // Si no cabe abajo, lo abre hacia arriba
            if (top + menu.offsetHeight > window.innerHeight) {
                top = rect.top - menu.offsetHeight - 4;
            }

            menu.style.top = `${top}px`;
            menu.style.left = `${left}px`;
        };

        posicionar();
        // Reposiciona si se hace scroll o resize mientras el menú está abierto
        window.addEventListener('scroll', posicionar, true);
        window.addEventListener('resize', posicionar);
        menu._posicionar = posicionar;
    });

    document.addEventListener('hide.bs.dropdown', function (e) {
        const button = e.target;
        const menu = button.nextElementSibling?.classList.contains('dropdown-menu')
            ? button.nextElementSibling
            : document.body.querySelector('.dropdown-menu[style*="position: fixed"]');

        if (!menu || !menu._originalParent) return;

        window.removeEventListener('scroll', menu._posicionar, true);
        window.removeEventListener('resize', menu._posicionar);

        // Lo regresa a su lugar original en la fila de la tabla
        if (menu._originalNextSibling) {
            menu._originalParent.insertBefore(menu, menu._originalNextSibling);
        } else {
            menu._originalParent.appendChild(menu);
        }
        menu.style.position = '';
        menu.style.zIndex = '';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.display = '';
        menu.style.width = '';
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
  <script src="../assets/vendor/aos/aos.js"></script>
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="../assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="../assets/vendor/php-email-form/validate.js"></script>
 
  <!-- Template Main JS File -->
  <script src="../assets/js/main.js"></script>

  <?php include $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/assets/asistente/asistente-widget.php'; ?>
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/assets/notificaciones/notificaciones-widget.php'; ?>


</body>
 
</html>