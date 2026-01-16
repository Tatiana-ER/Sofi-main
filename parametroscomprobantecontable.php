<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Variables del formulario
$txtId = $_POST['txtId'] ?? "";
$codigoDocumento = $_POST['codigoDocumento'] ?? "";
$descripcionDocumento = $_POST['descripcionDocumento'] ?? "";
$consecutivo = $_POST['consecutivo'] ?? "";
$activo = isset($_POST['activo']) ? 1 : 0;
$accion = $_POST['accion'] ?? "";

switch ($accion) {
  case "btnAgregar":
      // CONSECUTIVO FIJO: Siempre 1 para todos los tipos de documento
      $consecutivo = 1;

      $sentencia = $pdo->prepare("INSERT INTO comprobantecontable (codigoDocumento, descripcionDocumento, consecutivo, activo) 
                                  VALUES (:codigoDocumento, :descripcionDocumento, :consecutivo, :activo)");
      $sentencia->bindParam(':codigoDocumento', $codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento', $descripcionDocumento);
      $sentencia->bindParam(':consecutivo', $consecutivo);
      $sentencia->bindParam(':activo', $activo);
      $sentencia->execute();

      header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
      exit;
  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE comprobantecontable 
                                  SET codigoDocumento = :codigoDocumento,
                                      descripcionDocumento = :descripcionDocumento,
                                      activo = :activo
                                  WHERE id = :id");
      $sentencia->bindParam(':codigoDocumento', $codigoDocumento);
      $sentencia->bindParam(':descripcionDocumento', $descripcionDocumento);
      $sentencia->bindParam(':activo', $activo);
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();

      header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
      exit;
  break;

  case "btnEliminar":
      $sentencia = $pdo->prepare("DELETE FROM comprobantecontable WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();

      header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
      exit;
  break;

  case "btnEditar":
      // Solo cargar los datos del registro a editar
  break;
}

// Cargar los registros existentes
$sentencia = $pdo->prepare("SELECT * FROM comprobantecontable ORDER BY id ASC");
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
        text: 'El parametro comprobante contable se ha agregado correctamente',
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
        text: 'El parametro comprobante contable fue eliminado del registro',
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
          <img src="./Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
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
        <h2>COMPROBANTE CONTABLE</h2>
        <p>Para crear un nuevo tipo de documento diligencie los campos a continuación:</p>
        <p>(Los campos marcados con * son obligatorios)</p>
      </div>

      <form id="formComprobanteContable" action="" method="post">
        <div>
          <input type="hidden" class="form-control" value="<?php echo $txtId;?>" id="txtId" name="txtId" readonly>
        </div>

         <!-- Código y Descripción -->
        <div class="row g-3">
          <div class="col-md-4">
            <label for="codigoDocumento" class="form-label fw-bold">Codigo de documento*</label>
            <input type="text" class="form-control" value="<?php echo $codigoDocumento;?>" id="codigoDocumento" name="codigoDocumento" placeholder="" required>
          </div>
          <div class="col-md-8">
            <label for="descripcionDocumento" class="form-label fw-bold">Descripción documento*</label>
            <input type="text" class="form-control" value="<?php echo $descripcionDocumento;?>" id="descripcionDocumento" name="descripcionDocumento" placeholder="" required>
          </div>
        </div>

        <!-- Consecutivo y Activo -->
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="consecutivo" class="form-label fw-bold">Consecutivo</label>
            <input type="text" class="form-control" 
                  id="consecutivo" 
                  name="consecutivo" 
                  value="1" 
                  readonly>
          </div>  
        </div>

        <div class="row g-3 mt-2">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="activo" name="activo" <?php echo ($activo == 1) ? 'checked' : ''; ?>>
            <label for="activo" class="form-label fw-bold">Activo*</label>
          </div>
        </div>

        <div class="mt-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Guardar</button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-primary"  name="accion" >Modificar</button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
          <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
        </div>

      </form>

<div class="row">
  <div class="table-responsive">
    <table class="table-container">
      <thead>
        <tr>
          <th>Código Documento</th>
          <th>Descripción Documento</th>
          <th>Consecutivo</th>
          <th>Activo</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($lista as $usuario){ ?>
          <tr>
            <td><?php echo $usuario['codigoDocumento']; ?></td>
            <td><?php echo $usuario['descripcionDocumento']; ?></td>
            <td><?php echo $usuario['consecutivo']; ?></td>
            <td><?php echo $usuario['activo']? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
            <td>
              <form action="" method="post" style="display:inline-block;">
                <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                <input type="hidden" name="codigoDocumento" value="<?php echo $usuario['codigoDocumento']; ?>">
                <input type="hidden" name="descripcionDocumento" value="<?php echo $usuario['descripcionDocumento']; ?>">
                <input type="hidden" name="consecutivo" value="1"> <!-- CAMBIO AQUÍ: Siempre 1 -->
                <input type="hidden" name="activo" value="<?php echo $usuario['activo'] ?>">
                <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info btn-editar-cuenta" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
              </form>
              <form action="" method="post" style="display:inline-block;">
                <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
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
 
    </div>
  </section><!-- End Services Section -->
  <script>
    // Script para alternar botones
// Script para alternar botones
document.addEventListener("DOMContentLoaded", function() {
  const id = document.getElementById("txtId").value;
  const btnAgregar = document.getElementById("btnAgregar");
  const btnModificar = document.getElementById("btnModificar");
  const btnEliminar = document.getElementById("btnEliminar");
  const btnCancelar = document.getElementById("btnCancelar");
  const form = document.getElementById("formComprobanteContable");
  const consecutivoInput = document.getElementById("consecutivo");

  function modoAgregar() {
    // Ocultar/mostrar botones
    btnAgregar.style.display = "inline-block";
    btnModificar.style.display = "none";
    btnEliminar.style.display = "none";
    btnCancelar.style.display = "none";

    // Limpiar todos los campos manualmente
    form.querySelectorAll("input, select, textarea").forEach(el => {
      if (el.type === "radio" || el.type === "checkbox") {
        el.checked = false;
      } else if (el.id !== "consecutivo" && el.id !== "txtId") {
        el.value = "";
      }
    });

    // Establecer consecutivo siempre a 1
    if (consecutivoInput) {
      consecutivoInput.value = "1";
    }

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
    
    // Asegurar que el consecutivo sea 1 incluso en modo edición
    if (consecutivoInput) {
      consecutivoInput.value = "1";
    }
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
                  // 🔹 Crear (si no existe) un campo oculto con la acción seleccionada
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
  </script>

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