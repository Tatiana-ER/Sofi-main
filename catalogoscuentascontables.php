<?php
include ("connection.php");
$conn = new connection();
$pdo = $conn->connect();

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$clase=(isset($_POST['clase']))?$_POST['clase']:"";
$grupo=(isset($_POST['grupo']))?$_POST['grupo']:"";
$cuenta=(isset($_POST['cuenta']))?$_POST['cuenta']:"";
$subcuenta=(isset($_POST['subcuenta']))?$_POST['subcuenta']:"";
$auxiliar=(isset($_POST['auxiliar']))?$_POST['auxiliar']:"";
$moduloInventarios = isset($_POST['moduloInventarios']) ? 1 : 0;
$naturalezaContable=(isset($_POST['naturalezaContable']))?$_POST['naturalezaContable']:"";
$controlCartera= isset($_POST['controlCartera']) ? 1 : 0;
$activa= isset($_POST['activa']) ? 1 : 0;

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

switch($accion){
  case "btnAgregar":

      $sentencia=$pdo->prepare("INSERT INTO catalogoscuentascontables(clase,grupo,cuenta,subcuenta,auxiliar,moduloInventarios,naturalezaContable,controlCartera,activa) 
      VALUES (:clase,:grupo,:cuenta,:subcuenta,:auxiliar,:moduloInventarios,:naturalezaContable,:controlCartera,:activa)");
      
      $sentencia->bindParam(':clase',$clase);
      $sentencia->bindParam(':grupo',$grupo);
      $sentencia->bindParam(':cuenta',$cuenta);
      $sentencia->bindParam(':subcuenta',$subcuenta);
      $sentencia->bindParam(':auxiliar',$auxiliar);
      $sentencia->bindParam(':moduloInventarios',$moduloInventarios);
      $sentencia->bindParam(':naturalezaContable',$naturalezaContable);
      $sentencia->bindParam(':controlCartera',$controlCartera);
      $sentencia->bindParam(':activa',$activa);
      $sentencia->execute();

      header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
      exit; // Evita reenvío del formulario
    break;

 case "btnModificar":
    $sentencia = $pdo->prepare("UPDATE catalogoscuentascontables 
        SET clase = :clase,
            grupo = :grupo,
            cuenta = :cuenta,
            subcuenta = :subcuenta,
            auxiliar = :auxiliar,
            moduloInventarios = :moduloInventarios,
            naturalezaContable = :naturalezaContable,
            controlCartera = :controlCartera,
            activa = :activa
        WHERE id = :id");

    $sentencia->bindParam(':clase', $clase);
    $sentencia->bindParam(':grupo', $grupo);
    $sentencia->bindParam(':cuenta', $cuenta);
    $sentencia->bindParam(':subcuenta', $subcuenta);
    $sentencia->bindParam(':auxiliar', $auxiliar);
    $sentencia->bindParam(':moduloInventarios', $moduloInventarios);
    $sentencia->bindParam(':naturalezaContable', $naturalezaContable);
    $sentencia->bindParam(':controlCartera', $controlCartera);
    $sentencia->bindParam(':activa', $activa);
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();

    // Redirigir y mostrar alerta
    header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
    exit;

  break;

  case "btnEliminar":

    $sentencia = $pdo->prepare("DELETE FROM catalogoscuentascontables WHERE id = :id");
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();
    
    header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
    exit;

  break;

}

$sentencia= $pdo->prepare("SELECT * FROM `catalogoscuentascontables` WHERE 1");
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
        text: 'La cuenta contable se ha agregado correctamente',
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
        text: 'La cuenta contable fue eliminada del registro',
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
      <button class="btn-ir" onclick="window.location.href='menucatalogos.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>CATÁLOGO CUENTAS CONTABLES</h2>
          <p>Para crear nueva cuenta contable diligencia los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>

        <form id="formCuentas" action="" method="post" class="container mt-3">

        <!-- ID oculto -->
        <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

        <!-- Clase, Grupo, Cuenta, Subcuenta -->
        <div class="row g-3">
          <div class="col-md-3">
            <label for="clase" class="form-label fw-bold">Clase*</label>
            <input type="text" class="form-control" id="clase" name="clase"
                  placeholder="Ingresa una clase..."
                  value="<?php echo $clase; ?>" required>
          </div>

          <div class="col-md-3">
            <label for="grupo" class="form-label fw-bold">Grupo*</label>
            <select id="grupo" name="grupo" class="form-select" disabled required>
              <option value="">Selecciona un grupo...</option>
            </select>
          </div>

          <div class="col-md-3">
            <label for="cuenta" class="form-label fw-bold">Cuenta*</label>
            <select id="cuenta" name="cuenta" class="form-select" disabled required>
              <option value="">Selecciona una cuenta...</option>
            </select>
          </div>

          <div class="col-md-3">
            <label for="subcuenta" class="form-label fw-bold">Subcuenta*</label>
            <select id="subcuenta" name="subcuenta" class="form-select" disabled required>
              <option value="">Selecciona una subcuenta...</option>
            </select>
          </div>
        </div>

        <!-- Auxiliar -->
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="auxiliar" class="form-label fw-bold">Auxiliar</label>
            <input type="text" class="form-control" id="auxiliar" name="auxiliar"
                  placeholder="Ingresa el auxiliar (si aplica)"
                  value="<?php echo $auxiliar; ?>">
          </div>
        </div>

        <!-- Módulo inventarios -->
        <div class="row g-3 mt-2">
          <div class="col-md-6 d-flex align-items-center">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="moduloInventarios" name="moduloInventarios"
                    <?php if ($moduloInventarios) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="moduloInventarios">
                Asociada al módulo de inventarios
              </label>
            </div>
          </div>
        </div>

        <!-- Naturaleza contable -->
        <div class="row g-2 mt-2">
          <div class="col-md-3 ">
            <label class="form-label fw-bold">Naturaleza contable*</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" id="debito" name="naturalezaContable" value="debito"
                    <?php if ($naturalezaContable == 'debito') echo 'checked'; ?>>
              <label class="form-check-label" for="debito">Débito</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" id="credito" name="naturalezaContable" value="credito"
                    <?php if ($naturalezaContable == 'credito') echo 'checked'; ?>>
              <label class="form-check-label" for="credito">Crédito</label>
            </div>
          </div>

          <!-- Control cartera y activa -->
          <div class="col-md-3">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="controlCartera" name="controlCartera"
                    <?php if ($controlCartera) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="controlCartera">Control de cartera</label>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="activa" name="activa"
                    <?php if ($activa) echo 'checked'; ?>>
              <label class="form-check-label fw-bold" for="activa">Activa</label>
            </div>
          </div>
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
                  <th>Clase</th>
                  <th>Grupo</th>
                  <th>Cuenta</th>
                  <th>Subcuenta</th>
                  <th>Auxiliar</th>
                  <th>Modulo Inventarios</th>
                  <th>Naturaleza Contable</th>
                  <th>Control Cartera</th>
                  <th>Activa</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <?php foreach($lista as $usuario){ ?>
                <tr>
                  <td><?php echo $usuario['clase']; ?></td>
                  <td><?php echo $usuario['grupo']; ?></td>
                  <td><?php echo $usuario['cuenta']; ?></td>
                  <td><?php echo $usuario['subCuenta']; ?></td>
                  <td><?php echo $usuario['auxiliar']; ?></td>
                  <td><?php echo $usuario['moduloInventarios']? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
                  <td><?php echo $usuario['naturalezaContable']; ?></td>
                  <td><?php echo $usuario['controlCartera']? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
                  <td><?php echo $usuario['activa'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
                  <td>

                  <form action="" method="post">

                  <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
                  <input type="hidden" name="clase" value="<?php echo $usuario['clase']; ?>" >
                  <input type="hidden" name="grupo" value="<?php echo $usuario['grupo']; ?>" >
                  <input type="hidden" name="cuenta" value="<?php echo $usuario['cuenta']; ?>" >
                  <input type="hidden" name="subcuenta" value="<?php echo $usuario['subCuenta']; ?>" >
                  <input type="hidden" name="auxiliar" value="<?php echo $usuario['auxiliar']; ?>" >
                  <input type="hidden" name="moduloInventarios" value="<?php echo $usuario['moduloInventarios']; ?>" >
                  <input type="hidden" name="naturalezaContable" value="<?php echo $usuario['naturalezaContable']; ?>" >
                  <input type="hidden" name="controlCartera" value="<?php echo $usuario['controlCartera']; ?>" >
                  <input type="hidden" name="activa" value="<?php echo $usuario['activa']; ?>" >
                  
                  <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info btn-editar-cuenta" title="Editar">
                      <i class="fas fa-edit"></i>
                  </button>
                  <button type="submit" value="btnEliminar" name="accion" class="btn btn-sm btn-danger" title="Eliminar">
                      <i class="fas fa-trash-alt"></i>
                  </button>
                            
                  </form>
                  </td>

                </tr>
              <?php } ?>
            </table>


          </div>


      </div>
    </section><!-- End Services Section -->

    <!-- Cuentas Contables Existentes -->
      <script>
        document.addEventListener('DOMContentLoaded', function () {

        // Cargar datos desde el archivo JSON
        fetch("cuentas_contables.json")
            .then(response => {
                if (!response.ok) {
                    throw new Error("No se pudo cargar el archivo JSON");
                }
                return response.json();
            })
            .then(datos => {
                // Una vez cargado el JSON, activa toda la lógica
                inicializarCatalogo(datos);
            })
            .catch(error => console.error("Error al cargar las cuentas contables:", error));

        function inicializarCatalogo(datos) {
            const inputClase = document.getElementById('clase');
            const grupoSelect = document.getElementById('grupo');
            const cuentaSelect = document.getElementById('cuenta');
            const subcuentaSelect = document.getElementById('subcuenta');

            inputClase.addEventListener('input', function () {
                const searchTerm = inputClase.value.toLowerCase();
                const clasesFiltradas = Object.keys(datos).filter(clase =>
                    clase.toLowerCase().includes(searchTerm)
                );
                mostrarSugerencias(clasesFiltradas, 'clase');
            });

            function mostrarSugerencias(opcionesFiltradas, tipo) {
                const listaSugerencias = document.createElement('ul');
                listaSugerencias.classList.add('list-group');

                opcionesFiltradas.forEach(opcion => {
                    const item = document.createElement('li');
                    item.textContent = opcion;
                    item.classList.add('list-group-item');
                    item.addEventListener('click', function () {
                        if (tipo === 'clase') {
                            inputClase.value = opcion;
                            limpiarSugerencias();
                            activarGrupoSelect(opcion);
                        }
                    });
                    listaSugerencias.appendChild(item);
                });

                limpiarSugerencias();
                inputClase.parentNode.appendChild(listaSugerencias);
            }

            function limpiarSugerencias() {
                const listaAnterior = document.querySelector('.list-group');
                if (listaAnterior) {
                    listaAnterior.remove();
                }
            }

            function activarGrupoSelect(clase) {
                grupoSelect.innerHTML = '<option value="">Selecciona un grupo...</option>';
                grupoSelect.disabled = false;

                Object.keys(datos[clase]).forEach(grupo => {
                    const option = document.createElement('option');
                    option.value = grupo;
                    option.textContent = grupo;
                    grupoSelect.appendChild(option);
                });

                grupoSelect.addEventListener('change', function () {
                    activarCuentaSelect(clase, grupoSelect.value);
                });
            }

            function activarCuentaSelect(clase, grupo) {
                cuentaSelect.innerHTML = '<option value="">Selecciona una cuenta...</option>';
                cuentaSelect.disabled = false;

                Object.keys(datos[clase][grupo]).forEach(cuenta => {
                    const option = document.createElement('option');
                    option.value = cuenta;
                    option.textContent = cuenta;
                    cuentaSelect.appendChild(option);
                });

                cuentaSelect.addEventListener('change', function () {
                    activarSubcuentaSelect(clase, grupo, cuentaSelect.value);
                });
            }

            function activarSubcuentaSelect(clase, grupo, cuenta) {
                subcuentaSelect.innerHTML = '<option value="">Selecciona una subcuenta...</option>';
                subcuentaSelect.disabled = false;

                datos[clase][grupo][cuenta].forEach(subcuenta => {
                    const option = document.createElement('option');
                    option.value = subcuenta;
                    option.textContent = subcuenta;
                    subcuentaSelect.appendChild(option);
                });
            }
        }
    });
      // Script para alternar botones
      document.addEventListener("DOMContentLoaded", function() {
        const id = document.getElementById("txtId").value;
        const btnAgregar = document.getElementById("btnAgregar");
        const btnModificar = document.getElementById("btnModificar");
        const btnEliminar = document.getElementById("btnEliminar");
        const btnCancelar = document.getElementById("btnCancelar");
        const form = document.getElementById("formCuentas");

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
    </section><!-- End Services Section -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer><!-- End Footer -->

  <!-- Vendor JS Files -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

    <!-- Script para el menú móvil -->
  <script>
  document.addEventListener("DOMContentLoaded", function() {
    const toggle = document.querySelector(".mobile-nav-toggle");
    const navMenu = document.querySelector(".navbar ul");

    toggle.addEventListener("click", () => {
      navMenu.classList.toggle("show");
    });
  });
  </script>

</body>

</html>