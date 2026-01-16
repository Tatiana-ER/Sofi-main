<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Variables iniciales - IMPORTANTE: Se toman de POST siempre
$txtId = $_POST['txtId'] ?? "";
$codigoDocumento = $_POST['codigoDocumento'] ?? "";
$descripcionDocumento = $_POST['descripcionDocumento'] ?? "";
$documentoSoporte = isset($_POST['documentoSoporte']) ? 1 : 0;
$prefijo = $_POST['prefijo'] ?? "";
$consecutivoInicial = $_POST['consecutivoInicial'] ?? "";
$consecutivoFinal = $_POST['consecutivoFinal'] ?? "";
$numeroFactura = $_POST['numeroFactura'] ?? "";
$retenciones = $_POST['retenciones'] ?? "";
$activo = isset($_POST['activo']) ? 1 : 0;
$accion = $_POST['accion'] ?? "";


switch ($accion) {
    case "btnAgregar":
        // Validación 1: evitar prefijos duplicados
        if (!empty($prefijo)) {
            $validarPrefijo = $pdo->prepare("
                SELECT COUNT(*) 
                FROM facturadecompra 
                WHERE prefijo = :prefijo
            ");
            $validarPrefijo->bindParam(':prefijo', $prefijo);
            $validarPrefijo->execute();

            if ($validarPrefijo->fetchColumn() > 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?msg=prefijo_duplicado");
                exit();
            }
        }

        // Validación 2: evitar números de factura duplicados con el mismo prefijo
        $validar = $pdo->prepare("
            SELECT COUNT(*) 
            FROM facturadecompra 
            WHERE prefijo = :prefijo 
            AND numeroFactura = :numeroFactura
        ");
        $validar->bindParam(':prefijo', $prefijo);
        $validar->bindParam(':numeroFactura', $numeroFactura);
        $validar->execute();

        if ($validar->fetchColumn() > 0) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=factura_duplicada");
            exit();
        }

        $sentencia = $pdo->prepare("INSERT INTO facturadecompra (
          codigoDocumento,
          descripcionDocumento,
          documentoSoporte,
          prefijo,
          numeroFactura,
          consecutivoInicial,
          consecutivoFinal,
          retenciones,
          activo
      ) VALUES (
          :codigoDocumento,
          :descripcionDocumento,
          :documentoSoporte,
          :prefijo,
          :numeroFactura,
          :consecutivoInicial,
          :consecutivoFinal,
          :retenciones,
          :activo
      )");

        $sentencia->bindParam(':codigoDocumento', $codigoDocumento);
        $sentencia->bindParam(':descripcionDocumento', $descripcionDocumento);
        $sentencia->bindParam(':documentoSoporte', $documentoSoporte);
        $sentencia->bindParam(':prefijo', $prefijo);
        $sentencia->bindParam(':consecutivoInicial', $consecutivoInicial);
        $sentencia->bindParam(':consecutivoFinal', $consecutivoFinal);
        $sentencia->bindParam(':numeroFactura', $numeroFactura);
        $sentencia->bindParam(':retenciones', $retenciones);
        $sentencia->bindParam(':activo', $activo);
        $sentencia->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=agregado");
        exit();
        break;

    case "btnModificar":
        // Validación 1: evitar prefijos duplicados (excluyendo el registro actual)
        if (!empty($prefijo)) {
            $validarPrefijo = $pdo->prepare("
                SELECT COUNT(*) 
                FROM facturadecompra 
                WHERE prefijo = :prefijo
                AND id != :id
            ");
            $validarPrefijo->bindParam(':prefijo', $prefijo);
            $validarPrefijo->bindParam(':id', $txtId);
            $validarPrefijo->execute();

            if ($validarPrefijo->fetchColumn() > 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?msg=prefijo_duplicado");
                exit();
            }
        }

        // Validación 2: evitar números de factura duplicados con el mismo prefijo (excluyendo el registro actual)
        $validar = $pdo->prepare("
            SELECT COUNT(*) 
            FROM facturadecompra 
            WHERE prefijo = :prefijo 
            AND numeroFactura = :numeroFactura
            AND id != :id
        ");
        $validar->bindParam(':prefijo', $prefijo);
        $validar->bindParam(':numeroFactura', $numeroFactura);
        $validar->bindParam(':id', $txtId);
        $validar->execute();

        if ($validar->fetchColumn() > 0) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=factura_duplicada");
            exit();
        }

        $sentencia = $pdo->prepare("UPDATE facturadecompra 
            SET codigoDocumento = :codigoDocumento,
                descripcionDocumento = :descripcionDocumento,
                documentoSoporte = :documentoSoporte,
                prefijo = :prefijo,
                numeroFactura = :numeroFactura,
                consecutivoInicial = :consecutivoInicial,
                consecutivoFinal = :consecutivoFinal,
                retenciones = :retenciones,
                activo = :activo
            WHERE id = :id");

        $sentencia->bindParam(':codigoDocumento', $codigoDocumento);
        $sentencia->bindParam(':descripcionDocumento', $descripcionDocumento);
        $sentencia->bindParam(':documentoSoporte', $documentoSoporte);
        $sentencia->bindParam(':prefijo', $prefijo);
        $sentencia->bindParam(':numeroFactura', $numeroFactura);
        $sentencia->bindParam(':consecutivoInicial', $consecutivoInicial);
        $sentencia->bindParam(':consecutivoFinal', $consecutivoFinal);
        $sentencia->bindParam(':retenciones', $retenciones);
        $sentencia->bindParam(':activo', $activo);
        $sentencia->bindParam(':id', $txtId);
        $sentencia->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=modificado");
        exit();
        break;

    case "btnEliminar":
        $sentencia = $pdo->prepare("DELETE FROM facturadecompra WHERE id = :id");
        $sentencia->bindParam(':id', $txtId);
        $sentencia->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=eliminado");
        exit();
        break;

    case "btnEditar":
        // NO hacemos validación aquí, solo cargamos los datos
        break;
}

// Consulta para mostrar la tabla
$sentencia = $pdo->prepare("SELECT * FROM facturadecompra ORDER BY id DESC");
$sentencia->execute();
$lista = $sentencia->fetchAll(PDO::FETCH_ASSOC);
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
        text: 'El parametro factura de compra se ha agregado correctamente',
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
        text: 'El parametro factura de compra fue eliminado del registro',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "factura_duplicada":
        Swal.fire({
          icon: 'error',
          title: 'Factura duplicada',
          text: 'Ya existe una factura con este prefijo y número',
          confirmButtonColor: '#d33'
        });
    break;

    case "prefijo_duplicado":
        Swal.fire({
          icon: 'error',
          title: 'Prefijo duplicado',
          text: 'Ya existe un documento con este prefijo. Por favor, utiliza uno diferente.',
          confirmButtonColor: '#d33'
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
        <h2>FACTURA DE COMPRA</h2>
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
                  value="<?php echo htmlspecialchars($codigoDocumento); ?>" required>
          </div>

          <div class="col-md-8">
            <label for="descripcionDocumento" class="form-label fw-bold">Descripción del documento*</label>
            <input type="text" class="form-control" id="descripcionDocumento" name="descripcionDocumento"
                  placeholder="Ej: Factura de compra, Nota crédito..."
                  value="<?php echo htmlspecialchars($descripcionDocumento); ?>" required>
          </div>
        </div>

        <!-- Documento soporte -->
        <div class="row g-3 mt-3">
          <div class="col-md-6 d-flex align-items-center">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="documentoSoporte" name="documentoSoporte"
                    <?php if ($documentoSoporte) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="documentoSoporte">Documento Soporte</label>
            </div>
          </div>
        </div>

        <!-- Prefijo, Consecutivo inicial y final -->
        <div class="row g-3 mt-2">
          <div class="col-md-3">
            <label for="prefijo" class="form-label fw-bold">Prefijo</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($prefijo);?>" 
                   id="prefijo" name="prefijo" placeholder="">
          </div>
          
          <div class="col-md-3">
            <label for="numeroFactura" class="form-label fw-bold">Nro. Factura</label>
            <input type="number" class="form-control"
                  id="numeroFactura"
                  name="numeroFactura"
                  value="<?php echo htmlspecialchars($numeroFactura); ?>"
                  required>
          </div>


          <div class="col-md-3">
            <label for="consecutivoInicial" class="form-label fw-bold">Consecutivo Inicial</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($consecutivoInicial);?>" 
                   id="consecutivoInicial" name="consecutivoInicial" placeholder="">
          </div>

          <div class="col-md-3">
            <label for="consecutivoFinal" class="form-label fw-bold">Consecutivo Final</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($consecutivoFinal);?>" 
                   id="consecutivoFinal" name="consecutivoFinal" placeholder="">
          </div>
        </div>


        <!-- Retenciones -->
        <div class="row g-3 mt-3">
          <div class="col-md-4">
            <label for="retenciones" class="form-label fw-bold">Retenciones</label>
            <select name="retenciones" id="retenciones" class="form-control">
              <option value="">Seleccione el tipo de retención</option>
              <option value="Retención a la Renta" <?php if($retenciones == 'Retención a la Renta') echo 'selected'; ?>>Retención a la Renta</option>
              <option value="Retención de IVA" <?php if($retenciones == 'Retención de IVA') echo 'selected'; ?>>Retención de IVA</option>
              <option value="Retención de ICA" <?php if($retenciones == 'Retención de ICA') echo 'selected'; ?>>Retención de ICA</option>
            </select>
          </div>
        </div>

        <!-- Activo -->
        <div class="row g-3 mt-3">
          <div class="col-md-6 d-flex align-items-center">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="activo" name="activo"
                    <?php if ($activo) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="activo">Activo</label>
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
    </div>

    <!-- TABLA -->
    <div class="row mt-4">
      <div class="table-container">
       <table class="table-container">
          <thead>
            <tr>
              <th>Código Documento</th>
              <th>Descripción Documento</th>
              <th>Documento Soporte</th>
              <th>Prefijo</th>
              <th>Número Factura</th>
              <th>Consecutivo Inicial</th>
              <th>Consecutivo Final</th>
              <th>Retenciones</th> 
              <th>Activo</th>
              <th>Acción</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach($lista as $usuario){ ?>
              <tr>
                <td><?php echo htmlspecialchars($usuario['codigoDocumento']); ?></td>
                <td><?php echo htmlspecialchars($usuario['descripcionDocumento']); ?></td>
                <td><?php echo $usuario['documentoSoporte'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
                <td><?php echo htmlspecialchars($usuario['prefijo']); ?></td>
                <td><?php echo htmlspecialchars($usuario['numeroFactura']); ?></td>
                <td><?php echo htmlspecialchars($usuario['consecutivoInicial']); ?></td>
                <td><?php echo htmlspecialchars($usuario['consecutivoFinal']); ?></td>
                <td><?php echo htmlspecialchars($usuario['retenciones']); ?></td>
                <td><?php echo $usuario['activo'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>

                <td>
                  <form action="" method="post" style="display:flex; gap:5px;">
                    <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                    <input type="hidden" name="codigoDocumento" value="<?php echo $usuario['codigoDocumento']; ?>">
                    <input type="hidden" name="descripcionDocumento" value="<?php echo $usuario['descripcionDocumento']; ?>">
                    <input type="hidden" name="documentoSoporte" value="<?php echo $usuario['documentoSoporte']; ?>">
                    <input type="hidden" name="prefijo" value="<?php echo $usuario['prefijo']; ?>">
                    <input type="hidden" name="numeroFactura" value="<?php echo $usuario['numeroFactura']; ?>">
                    <input type="hidden" name="consecutivoInicial" value="<?php echo $usuario['consecutivoInicial']; ?>">
                    <input type="hidden" name="consecutivoFinal" value="<?php echo $usuario['consecutivoFinal']; ?>">
                    <input type="hidden" name="retenciones" value="<?php echo $usuario['retenciones']; ?>">
                    <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>">

                    <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info btn-editar-pventa" title="Editar">
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
                ? "Se actualizarán los datos de este documento."
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
            const element = document.getElementById('pdfContent');
            const codigoDocumento = document.getElementById('codigoDocumento').value || 'Factura-Compra';
            
            const opt = {
              margin: [0.1, 0.5, 0.5, 0.5],
              filename: `${codigoDocumento}_FacturaDeCompra.pdf`,
              image: { type: 'jpeg', quality: 0.98 },
              html2canvas: {
                scale: 2,
                logging: false,
                dpi: 192,
                letterRendering: true,
                scrollY: 0,
                windowHeight: element.scrollHeight
              },
              jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
            
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