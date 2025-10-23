<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$metodoPago=(isset($_POST['metodoPago']))?$_POST['metodoPago']:"";
$cuentaContable=(isset($_POST['cuentaContable']))?$_POST['cuentaContable']:"";

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

switch($accion){
  case "btnAgregar":

      $sentencia=$pdo->prepare("INSERT INTO mediosdepago(metodoPago,cuentaContable) 
      VALUES (:metodoPago,:cuentaContable)");

      $sentencia->bindParam(':metodoPago',$metodoPago);
      $sentencia->bindParam(':cuentaContable',$cuentaContable);

      $sentencia->execute();
      
      header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
      exit; // Evita reenvío del formulario

  break;

  case "Editar":
    // Rellenar los campos con los valores seleccionados
    $txtId = $_POST['txtId'];
    $metodoPago = $_POST['metodoPago'];
    $cuentaContable = $_POST['cuentaContable'];
  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE mediosdepago 
                                  SET metodoPago = :metodoPago,
                                      cuentaContable = :cuentaContable
                                  WHERE id = :id");

      // Enlazamos los parámetros 

      $sentencia->bindParam(':metodoPago', $metodoPago);
      $sentencia->bindParam(':cuentaContable', $cuentaContable);
      $sentencia->bindParam(':id', $txtId);

      // Ejecutamos la sentencia
      $sentencia->execute();

    // Redirigir y mostrar alerta
    header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
    exit;

  break;

  case "btnEliminar":

    $sentencia = $pdo->prepare("DELETE FROM mediosdepago WHERE id = :id");
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();
    header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
    exit;
  break;

}

  $sentencia= $pdo->prepare("SELECT * FROM `mediosdepago` WHERE 1");
  $sentencia->execute();
  $lista=$sentencia->fetchALL(PDO::FETCH_ASSOC);

?>

<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const msg = "<?= $_GET['msg'] ?>";

  switch (msg) {
    case "agregado":
      Swal.fire({
        icon: 'success',
        title: 'Guardado exitosamente',
        text: 'El método de pago se ha agregado correctamente',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "modificado":
      Swal.fire({
        icon: 'success',
        title: 'Modificado correctamente',
        text: 'Los datos del método de pago se actualizaron con éxito',
        confirmButtonColor: '#3085d6'
      }).then(() => {
        // 🔹 Restablecer el modo agregar
        const form = document.getElementById("formMetodos");
        if (form) {
          form.reset(); // limpia los campos
          document.getElementById("txtId").value = "";
        }

        // Mostrar solo el botón de agregar
        document.getElementById("btnAgregar").style.display = "inline-block";
        document.getElementById("btnModificar").style.display = "none";
        document.getElementById("btnEliminar").style.display = "none";
        document.getElementById("btnCancelar").style.display = "none";
      });
      break;

    case "eliminado":
      Swal.fire({
        icon: 'success',
        title: 'Eliminado correctamente',
        text: 'El método de pago fue eliminado del registro',
        confirmButtonColor: '#3085d6'
      }).then(() => {
        const form = document.getElementById("formMetodos");
        if (form) form.reset();
        document.getElementById("btnAgregar").style.display = "inline-block";
        document.getElementById("btnModificar").style.display = "none";
        document.getElementById("btnEliminar").style.display = "none";
        document.getElementById("btnCancelar").style.display = "none";
      });
      break;
  }

  // Quitar el parámetro ?msg=... de la URL
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
  <!-- CSS de Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- JS: jQuery y Select2 -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

    <!-- ======= Services Section ======= -->

<section id="services" class="services">
  <button class="btn-ir" onclick="window.location.href='menucatalogos.php'">
    <i class="fa-solid fa-arrow-left"></i> Regresar
  </button>
  <div class="container" data-aos="fade-up">

    <div class="section-title">
      <h2>CATÁLOGO MÉTODOS DE PAGO</h2>
      <p>Para crear una nueva forma de pago diligencie los campos a continuación:</p>
      <p>(Los campos marcados con * son obligatorios)</p>
    </div>

    <form id="formMetodos" action="" method="post" class="container mt-3">

      <!-- ID oculto -->
      <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

      <div class="container">
        <label for="metodoPago" class="form-label">Método de Pago:</label>
        <select id="metodoPago" value="<?php echo $metodoPago;?>" name="metodoPago" onchange="mostrarCuentaContable()" class="form-control">
          <option value="">Selecciona un método de pago</option>
          <option value="efectivo" <?php if($metodoPago=='efectivo') echo 'selected'; ?>>Efectivo</option>
          <option value="transferencia" <?php if($metodoPago=='transferencia') echo 'selected'; ?>>Transferencia</option>
          <option value="credito" <?php if($metodoPago=='credito') echo 'selected'; ?>>Crédito</option>
        </select>
        <br>  
        <label for="cuentaContable" class="form-label">Cuenta Contable:</label>
        <select id="cuentaContable" name="cuentaContable" class="form-control" required>
          <option value="">Selecciona una cuenta contable</option>
          <!-- aquí tus opciones generadas en PHP -->
        </select>

      </div>

      <!-- Botones -->
      <div class="mt-4">
        <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
        <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion" style="display:none;">Modificar</button>
        <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion" style="display:none;">Eliminar</button>
        <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
      </div>
    </form>

    <!-- Tabla -->
    <div class="row">
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Método de Pago</th>
              <th>Cuenta Contable</th>
              <th>Acción</th>
            </tr>
          </thead>
          <?php foreach($lista as $usuario){ ?>
          <tr>
            <td><?php echo $usuario['metodoPago']; ?></td>
            <td><?php echo $usuario['cuentaContable']; ?></td>
            <td>
              <form action="" method="post">
                <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                <input type="hidden" name="metodoPago" value="<?php echo $usuario['metodoPago']; ?>">
                <input type="hidden" name="cuentaContable" value="<?php echo $usuario['cuentaContable']; ?>">
                <button value="btnEditar" type="submit" class="btn-editar" name="accion">Editar</button>
                <button value="btnEliminar" type="submit" class="btn-eliminar" name="accion">Eliminar</button>
              </form>
            </td>
          </tr>
          <?php } ?>
        </table>
      </div>
    </div>

<!-- Scripts -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const metodoPago = document.getElementById('metodoPago');
    const cuentaSelect = document.getElementById('cuentaContable');

    // Cargar JSON una sola vez
    fetch("cuentas_contables.json")
      .then(response => {
        if (!response.ok) throw new Error("No se pudo cargar el archivo JSON");
        return response.json();
      })
      .then(datos => {
        inicializarSelector(datos);
      })
      .catch(error => console.error("Error al cargar las cuentas contables:", error));

    function inicializarSelector(datos) {
      metodoPago.addEventListener('change', function () {
        const metodo = metodoPago.value;
        cuentaSelect.innerHTML = '<option value="">Selecciona una cuenta contable</option>';

        // Filtrar las cuentas según el método de pago
        let clases = [];

        if (metodo === "efectivo") {
          clases = ["1105-Caja"];
        } else if (metodo === "transferencia") {
          clases = ["1110-Bancos", "111005-Moneda nacional", "1120-Cuentas de ahorro"];
        } else if (metodo === "credito") {
          clases = ["2205-Nacionales", "2335-Costos y gastos por pagar", "1305-Clientes"];
        }

        // Recorrer todo el JSON (clase → grupo → cuenta → subcuenta)
        for (const clase in datos) {
          for (const grupo in datos[clase]) {
            for (const cuenta in datos[clase][grupo]) {
              const contenido = datos[clase][grupo][cuenta];

              // Si la cuenta coincide con las que buscamos (nivel 3)
              if (clases.includes(cuenta)) {
                if (Array.isArray(contenido)) {
                  contenido.forEach(sub => {
                    const option = document.createElement("option");
                    option.value = sub;
                    option.textContent = `${cuenta} → ${sub}`;
                    cuentaSelect.appendChild(option);
                  });
                } else if (typeof contenido === "object") {
                  // Caso especial: hay un nivel más (como 111005-Moneda nacional)
                  for (const subcuenta in contenido) {
                    const subcontenido = contenido[subcuenta];
                    if (Array.isArray(subcontenido)) {
                      subcontenido.forEach(item => {
                        const option = document.createElement("option");
                        option.value = item;
                        option.textContent = `${cuenta} → ${subcuenta} → ${item}`;
                        cuentaSelect.appendChild(option);
                      });
                    }
                  }
                }
              }

              // También validar si hay coincidencias en un nivel más profundo (como 111005-Moneda nacional)
              if (typeof contenido === "object" && !Array.isArray(contenido)) {
                for (const subcuenta in contenido) {
                  if (clases.includes(subcuenta)) {
                    const subcontenido = contenido[subcuenta];
                    if (Array.isArray(subcontenido)) {
                      subcontenido.forEach(item => {
                        const option = document.createElement("option");
                        option.value = item;
                        option.textContent = `${subcuenta} → ${item}`;
                        cuentaSelect.appendChild(option);
                      });
                    }
                  }
                }
              }
            }
          }
        }
      });
    }
  });

  // Control de botones
  document.addEventListener("DOMContentLoaded", function() {
    const id = document.getElementById("txtId").value;
    const btnAgregar = document.getElementById("btnAgregar");
    const btnModificar = document.getElementById("btnModificar");
    const btnEliminar = document.getElementById("btnEliminar");
    const btnCancelar = document.getElementById("btnCancelar");
    const form = document.getElementById("formMetodos");

    function modoAgregar() {
      btnAgregar.style.display = "inline-block";
      btnModificar.style.display = "none";
      btnEliminar.style.display = "none";
      btnCancelar.style.display = "none";

      // Limpiar campos
      form.querySelectorAll("input, select, textarea").forEach(el => {
        if (el.type === "radio" || el.type === "checkbox") el.checked = false;
        else el.value = "";
      });
      document.getElementById("txtId").value = "";
    }

    if (id && id.trim() !== "") {
      btnAgregar.style.display = "none";
      btnModificar.style.display = "inline-block";
      btnEliminar.style.display = "inline-block";
      btnCancelar.style.display = "inline-block";
    } else {
      modoAgregar();
    }

    btnCancelar.addEventListener("click", e => {
      e.preventDefault();
      modoAgregar();
      form.reset();
      document.getElementById("txtId").value = "";
    });
  });

  // Confirmaciones SweetAlert2
  document.addEventListener("DOMContentLoaded", () => {
    const forms = document.querySelectorAll("form");

    forms.forEach(form => {
      form.addEventListener("submit", function(e) {
        const boton = e.submitter;
        const accion = boton?.value;

        if (accion === "btnAgregar" || accion === "btnModificar" || accion === "btnEliminar") {
          e.preventDefault();

          let titulo = "";
          let texto = "";
          let icono = "warning";
          let color = "#3085d6";

          if (accion === "btnAgregar") {
            titulo = "¿Desea agregar este método de pago?";
            texto = "El nuevo método será registrado en el sistema.";
            icono = "question";
            color = "#198754";
          } else if (accion === "btnModificar") {
            titulo = "¿Guardar cambios?";
            texto = "Se actualizarán los datos del método de pago.";
          } else if (accion === "btnEliminar") {
            titulo = "¿Eliminar registro?";
            texto = "Esta acción eliminará el registro permanentemente.";
            color = "#d33";
          }

          Swal.fire({
            title: titulo,
            text: texto,
            icon: icono,
            showCancelButton: true,
            confirmButtonText: "Sí, continuar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: color,
            cancelButtonColor: "#6c757d",
          }).then((result) => {
            if (result.isConfirmed) {
              let inputAccion = form.querySelector("input[name='accion']");
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

    // --- Mostrar mensaje de éxito al regresar de PHP ---
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get("success");

    if (success === "agregado") {
      Swal.fire({
        title: "¡Registro guardado!",
        text: "El método de pago se agregó correctamente.",
        icon: "success",
        confirmButtonColor: "#198754"
      });
    } else if (success === "modificado") {
      Swal.fire({
        title: "¡Cambios guardados!",
        text: "El método de pago se actualizó correctamente.",
        icon: "success",
        confirmButtonColor: "#3085d6"
      });
    } else if (success === "eliminado") {
      Swal.fire({
        title: "¡Registro eliminado!",
        text: "El método de pago se eliminó correctamente.",
        icon: "success",
        confirmButtonColor: "#d33"
      });
    }
  });

  $(document).ready(function() {
    $('#cuentaContable').select2({
      placeholder: "Buscar o seleccionar una cuenta contable",
      allowClear: true,
      width: '100%'
    });
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
<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</a>

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
