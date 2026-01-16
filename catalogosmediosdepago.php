<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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
      exit;

  break;

  case "Editar":
    $txtId = $_POST['txtId'];
    $metodoPago = $_POST['metodoPago'];
    $cuentaContable = $_POST['cuentaContable'];
  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE mediosdepago 
                                  SET metodoPago = :metodoPago,
                                      cuentaContable = :cuentaContable
                                  WHERE id = :id");

      $sentencia->bindParam(':metodoPago', $metodoPago);
      $sentencia->bindParam(':cuentaContable', $cuentaContable);
      $sentencia->bindParam(':id', $txtId);

      $sentencia->execute();

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

  $sentencia= $pdo->prepare("SELECT * FROM `mediosdepago` ORDER BY metodoPago, cuentaContable");
  $sentencia->execute();
  $lista=$sentencia->fetchALL(PDO::FETCH_ASSOC);

  // Agrupar por método de pago
  $metodosPorTipo = [];
  foreach($lista as $item) {
    $metodosPorTipo[$item['metodoPago']][] = $item;
  }

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
      });
      break;

    case "eliminado":
      Swal.fire({
        icon: 'success',
        title: 'Eliminado correctamente',
        text: 'El método de pago fue eliminado del registro',
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

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style> 
    input[type="text"] {
      width: 100%;
      box-sizing: border-box;
      padding: 5px;
    }

    /* Estilos para tabla desplegable */
    .metodo-header {
      color: black;
      cursor: pointer;
      font-weight: bold;
      padding: 12px 15px;
      transition: all 0.3s ease;
    }

    .metodo-header:hover {
      background: linear-gradient(135deg, #5f9fffff 0%, #71aaffff 100%);
    }

    .metodo-header i {
      margin-right: 10px;
      transition: transform 0.3s ease;
    }

    .metodo-header.collapsed i {
      transform: rotate(-90deg);
    }

    .metodo-content {
      display: none;
    }

    .metodo-content.show {
      display: table-row-group;
    }

    .cuenta-row {
      background-color: #f8f9fa;
      border-left: 4px solid #667eea;
    }

    .cuenta-row:hover {
      background-color: #e9ecef;
    }

    .cuenta-codigo {
      font-weight: 600;
      color: #667eea;
    }

    .cuenta-nombre {
      color: #495057;
    }

    .table-container table {
      border-collapse: separate;
      border-spacing: 0 5px;
    }
  </style>
</head>

<body>

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
      </nav>
    </div>
  </header>

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

      <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

      <div class="container">
        <label for="metodoPago" class="form-label fw-bold">Método de Pago*:</label>
        <select id="metodoPago" value="<?php echo $metodoPago;?>" name="metodoPago" class="form-control" required>
          <option value="">Selecciona un método de pago</option>
          <option value="Efectivo" <?php if($metodoPago=='Efectivo') echo 'selected'; ?>>Efectivo</option>
          <option value="Pago Electronico" <?php if($metodoPago=='Pago Electronico' || $metodoPago=='Transferencia') echo 'selected'; ?>>Pago Electrónico</option>
          <option value="Credito" <?php if($metodoPago=='Credito') echo 'selected'; ?>>Crédito</option>
        </select>
        <br>  
        <label for="cuentaContable" class="form-label fw-bold">Cuenta Contable*:</label>
        <select id="cuentaContable" name="cuentaContable" class="form-control" required>
            <option value="">Selecciona una cuenta contable</option>
        </select>

      </div>

      <div class="mt-4">
        <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
        <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion" style="display:none;">Modificar</button>
        <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion" style="display:none;">Eliminar</button>
        <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
      </div>
    </form>

    <!-- Tabla con desplegables -->
    <div class="row mt-5">
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Método de Pago</th>
              <th>Cuenta Contable</th>
              <th>Nombre Cuenta</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php 
          $metodosOrdenados = ['Efectivo', 'Pago Electronico', 'Credito'];
          foreach($metodosOrdenados as $metodo): 
            // Convertir "Transferencia" a "Pago Electronico" para mostrar
            $metodoMostrar = $metodo;
            $cuentasMetodo = [];
            
            // Buscar cuentas de este método (incluyendo "Transferencia" como "Pago Electronico")
            foreach($lista as $item) {
              $itemMetodo = $item['metodoPago'];
              if ($itemMetodo == 'Transferencia') $itemMetodo = 'Pago Electronico';
              
              if ($itemMetodo == $metodo) {
                $cuentasMetodo[] = $item;
              }
            }
            
            if (empty($cuentasMetodo)) continue;
          ?>
            <tr class="metodo-header" onclick="toggleMetodo('<?php echo $metodo; ?>')">
              <td colspan="4">
                <i class="fas fa-chevron-down"></i>
                <?php echo $metodoMostrar; ?>
              </td>
            </tr>
            <tbody class="metodo-content" id="content-<?php echo $metodo; ?>">
              <?php foreach($cuentasMetodo as $usuario): 
                // Separar código y nombre de la cuenta
                $cuentaCompleta = $usuario['cuentaContable'];
                $partes = explode('-', $cuentaCompleta, 2);
                $codigoCuenta = isset($partes[0]) ? trim($partes[0]) : $cuentaCompleta;
                $nombreCuenta = isset($partes[1]) ? trim($partes[1]) : '';
              ?>
              <tr class="cuenta-row">
                <td></td>
                <td class="cuenta-codigo"><?php echo $codigoCuenta; ?></td>
                <td class="cuenta-nombre"><?php echo $nombreCuenta; ?></td>
                <td>
                  <form action="" method="post" style="display: inline;">
                    <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                    <input type="hidden" name="metodoPago" value="<?php echo $usuario['metodoPago']; ?>">
                    <input type="hidden" name="cuentaContable" value="<?php echo $usuario['cuentaContable']; ?>">
                    <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info btn-editar-medio" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="submit" value="btnEliminar" name="accion" class="btn btn-sm btn-danger" title="Eliminar">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

<script>
// Función para expandir/contraer secciones
function toggleMetodo(metodo) {
  const content = document.getElementById('content-' + metodo);
  const header = event.currentTarget;
  
  if (content.classList.contains('show')) {
    content.classList.remove('show');
    header.classList.add('collapsed');
  } else {
    content.classList.add('show');
    header.classList.remove('collapsed');
  }
}

// Contraer todos por defecto al cargar
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.metodo-header').forEach(function(header) {
    header.classList.add('collapsed');
  });
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

    form.querySelectorAll("input, select, textarea").forEach(el => {
      if (el.type === "radio" || el.type === "checkbox") el.checked = false;
      else el.value = "";
    });
    document.getElementById("txtId").value = "";
    $('#cuentaContable').val(null).trigger('change');
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
    modoAgregar();
    
    if (window.history.replaceState) {
      const url = new URL(window.location);
      url.searchParams.forEach((value, key) => {
        if (key !== 'msg') {
          url.searchParams.delete(key);
        }
      });
      window.history.replaceState({}, document.title, url);
    }
  });
});

// Confirmaciones SweetAlert2
document.addEventListener("DOMContentLoaded", () => {
  const forms = document.querySelectorAll("form");

  forms.forEach(form => {
    form.addEventListener("submit", function(e) {
      const boton = e.submitter;
      const accion = boton?.value;

      if (accion === "btnModificar" || accion === "btnEliminar") {
        e.preventDefault();

        let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar registro?";
        let texto = accion === "btnModificar"
          ? "Se actualizarán los datos del método de pago."
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

$(document).ready(function() {
  const $metodoPago = $('#metodoPago');
  const $cuentaSelect = $('#cuentaContable');
  const cuentaContableGuardada = "<?php echo $cuentaContable; ?>";

  $cuentaSelect.select2({
    placeholder: "Buscar o seleccionar una cuenta contable",
    allowClear: true,
    width: '100%',
    ajax: {
      url: 'obtener_cuentas.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          metodo: $metodoPago.val(),
          search: params.term || ''
        };
      },
      processResults: function (data) {
        return {
          results: data.map(function (cuenta) {
            return { id: cuenta.valor, text: cuenta.texto };
          })
        };
      },
      cache: true
    }
  });

  $metodoPago.on('change', function() {
    $cuentaSelect.val(null).trigger('change');
  });

  // Si hay una cuenta guardada desde PHP, agrégala manualmente
  if (cuentaContableGuardada && cuentaContableGuardada.trim() !== "") {
    const cuentaTexto = "<?php echo addslashes($cuentaContable); ?>"; // muestra código + nombre
    const option = new Option(cuentaTexto, cuentaContableGuardada, true, true);
    $cuentaSelect.append(option).trigger('change');
  }

});
</script>
  </div>
</section>

<footer id="footer" class="footer-minimalista">
  <p>Universidad de Santander - Ingeniería de Software</p>
  <p>Todos los derechos reservados © 2025</p>
  <p>Creado por iniciativa del programa de Contaduría Pública</p>
</footer>

<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>