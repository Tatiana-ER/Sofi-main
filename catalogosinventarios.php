<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$categoria=(isset($_POST['idcategoria']))?$_POST['idcategoria']:"";
$categoria=(isset($_POST['categoria']))?$_POST['categoria']:"";
$codigoCuentaVentas=(isset($_POST['codigoCuentaVentas']))?$_POST['codigoCuentaVentas']:"";
$cuentaVentas=(isset($_POST['cuentaVentas']))?$_POST['cuentaVentas']:"";
$codigoCuentaInventarios=(isset($_POST['codigoCuentaInventarios']))?$_POST['codigoCuentaInventarios']:"";
$cuentaInventarios=(isset($_POST['cuentaInventarios']))?$_POST['cuentaInventarios']:"";
$codigoCuentaCostos=(isset($_POST['codigoCuentaCostos']))?$_POST['codigoCuentaCostos']:"";
$cuentaCostos=(isset($_POST['cuentaCostos']))?$_POST['cuentaCostos']:"";
$codigoCuentaDevoluciones=(isset($_POST['codigoCuentaDevoluciones']))?$_POST['codigoCuentaDevoluciones']:"";
$cuentaDevoluciones=(isset($_POST['cuentaDevoluciones']))?$_POST['cuentaDevoluciones']:"";

$categoriaInventarios=(isset($_POST['categoriaInventarios']))?$_POST['categoriaInventarios']:"";
$codigoProducto=(isset($_POST['codigoProducto']))?$_POST['codigoProducto']:"";
$descripcionProducto=(isset($_POST['descripcionProducto']))?$_POST['descripcionProducto']:"";
$unidadMedida=(isset($_POST['unidadMedida']))?$_POST['unidadMedida']:"";
$cantidad=(isset($_POST['cantidad']))?$_POST['cantidad']:"";
$productoIva=(isset($_POST['productoIva']))?$_POST['productoIva']:"";
$tipoItem=(isset($_POST['tipoItem']))?$_POST['tipoItem']:"";
$facturacionCero=(isset($_POST['facturacionCero']))?$_POST['facturacionCero']:"";
$activo=(isset($_POST['activo']))?$_POST['activo']:"";


$accion=(isset($_POST['accion']))?$_POST['accion']:"";

// Obtener todas las categorías registradas
$sentenciaCategorias = $pdo->prepare("SELECT id, categoria FROM categoriainventarios ORDER BY categoria ASC");
$sentenciaCategorias->execute();
$categorias = $sentenciaCategorias->fetchAll(PDO::FETCH_ASSOC);

// Convertir checkboxes a valores binarios
$productoIva = isset($_POST['productoIva']) ? 1 : 0;
$facturacionCero = isset($_POST['facturacionCero']) ? 1 : 0;
$activo = isset($_POST['activo']) ? 1 : 0;

switch($accion){
  case "btnAgregarCategoria":

      $sentencia=$pdo->prepare("INSERT INTO categoriainventarios(categoria,codigoCuentaVentas,cuentaVentas,codigoCuentaInventarios,cuentaInventarios,codigoCuentaCostos,cuentaCostos,codigoCuentaDevoluciones,cuentaDevoluciones) 
      VALUES (:categoria,:codigoCuentaVentas,:cuentaVentas,:codigoCuentaInventarios,:cuentaInventarios,:codigoCuentaCostos,:cuentaCostos,:codigoCuentaDevoluciones,:cuentaDevoluciones)");

      $sentencia->bindParam(':categoria',$categoria);
      $sentencia->bindParam(':codigoCuentaVentas',$codigoCuentaVentas);
      $sentencia->bindParam(':cuentaVentas',$cuentaVentas);
      $sentencia->bindParam(':codigoCuentaInventarios',$codigoCuentaInventarios);
      $sentencia->bindParam(':cuentaInventarios',$cuentaInventarios);
      $sentencia->bindParam(':codigoCuentaCostos',$codigoCuentaCostos);
      $sentencia->bindParam(':cuentaCostos',$cuentaCostos);
      $sentencia->bindParam(':codigoCuentaDevoluciones',$codigoCuentaDevoluciones);
      $sentencia->bindParam(':cuentaDevoluciones',$cuentaDevoluciones);
      $sentencia->execute();

      header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
      exit; // Evita reenvío del formulario

  break;

  case "btnAgregarProducto":

    $sentencia=$pdo->prepare("INSERT INTO productoinventarios(categoriaInventarios,codigoProducto,descripcionProducto,unidadMedida,cantidad,productoIva,tipoItem,facturacionCero,activo) 
    VALUES (:categoriaInventarios,:codigoProducto,:descripcionProducto,:unidadMedida,:cantidad,:productoIva,:tipoItem,:facturacionCero,:activo)");
    

    $sentencia->bindParam(':categoriaInventarios',$categoriaInventarios);
    $sentencia->bindParam(':codigoProducto',$codigoProducto);
    $sentencia->bindParam(':descripcionProducto',$descripcionProducto);
    $sentencia->bindParam(':unidadMedida',$unidadMedida);
    $sentencia->bindParam(':cantidad',$cantidad);
    $sentencia->bindParam(':productoIva',$productoIva);
    $sentencia->bindParam(':tipoItem',$tipoItem);
    $sentencia->bindParam(':facturacionCero',$facturacionCero);
    $sentencia->bindParam(':activo',$activo);

    $sentencia->execute();

    header("Location: ".$_SERVER['PHP_SELF']."?msg=agregadoProducto");
    exit; // Evita reenvío del formulario

  break;
}
?>

<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  switch ("<?= $_GET['msg'] ?>") {
    case "agregado":
      Swal.fire({
        icon: 'success',
        title: 'Guardada exitosamente',
        text: 'La categoria se ha agregado correctamente',
        confirmButtonColor: '#3085d6'
      });
      break;
    
    case "agregadoProducto":
      Swal.fire({
        icon: 'success',
        title: 'Guardado exitosamente',
        text: 'El producto se ha agregado correctamente',
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
        title: 'Eliminada correctamente',
        text: 'La categoria fue eliminada del registro',
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
    .sugerencias {
      position: absolute;
      z-index: 1000;
      background-color: white;
      border: 1px solid #ccc;
      width: 100%;
      max-height: 150px;
      overflow-y: auto;
      margin-top: 2px;
      padding-left: 0;
      list-style: none;
    }

    .sugerencias li {
      padding: 8px 12px;
      cursor: pointer;
    }

    .sugerencias li:hover {
      background-color: #f0f0f0;
    }

    .form-group {
      position: relative;
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
          <h2>CATÁLOGO DE INVENTARIOS</h2>
          <p>Para crear una nueva categoría de inventarios diligencie los campos a continuación:</p>
          <p class="text-muted">(Los campos marcados con * son obligatorios)</p>
        </div>

        <!-- CATEGORÍAS -->
        <div class="mt-4">

          <form action="" method="post" id="formCategorias">
            <div class="row g-3">
              <div class="col-md-5">
                <label for="categoria" class="form-label fw-bold">Nombre de la Categoría*</label>
                <input type="text" class="form-control" id="categoria" name="categoria"
                      placeholder="Ej: Electrodomésticos" required>
              </div>
            </div>

            <!-- Códigos contables asociados -->
            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label for="ventas" class="form-label fw-bold">Código Ventas</label>
                <input type="text" class="form-control" id="codigoCuentaVentas" name="codigoCuentaVentas" placeholder="Ingresa código ventas">
                <input type="text" class="form-control mt-1" id="cuentaVentas" name="cuentaVentas" placeholder="Nombre cuenta ventas" readonly>
              </div>

              <div class="col-md-6">
                <label for="inventarios" class="form-label fw-bold">Código Inventarios</label>
                <input type="text" class="form-control" id="codigoCuentaInventarios" name="codigoCuentaInventarios" placeholder="Ingresa código inventarios">
                <input type="text" class="form-control mt-1" id="cuentaInventarios" name="cuentaInventarios" placeholder="Nombre cuenta inventarios" readonly>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label for="costos" class="form-label fw-bold">Código Costos</label>
                <input type="text" class="form-control" id="codigoCuentaCostos" name="codigoCuentaCostos" placeholder="Ingresa código costos">
                <input type="text" class="form-control mt-1" id="cuentaCostos" name="cuentaCostos" placeholder="Nombre cuenta costos" readonly>
              </div>

              <div class="col-md-6">
                <label for="devoluciones" class="form-label fw-bold">Código Devoluciones</label>
                <input type="text" class="form-control" id="codigoCuentaDevoluciones" name="codigoCuentaDevoluciones" placeholder="Ingresa código devoluciones">
                <input type="text" class="form-control mt-1" id="cuentaDevoluciones" name="cuentaDevoluciones" placeholder="Nombre cuenta devoluciones" readonly>
              </div>
            </div>

            <!-- Botón -->
            <div class="mt-4">
              <button id="btnAgregarCategoria" value="btnAgregarCategoria" type="submit" class="btn btn-primary" name="accion">
                Guardar Categoría
              </button>
            </div>
          </form>
        </div>

        <!-- PRODUCTOS -->
        <div class="mt-5">
          <div  class="section-title">
            <p>Para crear un nuevo producto diligencie los campos a continuación:</p>
            <p class="text-muted">(Los campos marcados con * son obligatorios)</p>
          </div>

          <form action="" method="post" id="formProductos">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="categoriaInventarios" class="form-label fw-bold">Categoría de inventarios*</label>
                <select id="categoriaInventarios" name="categoriaInventarios" class="form-select" required>
                  <option value="">Seleccione una categoría...</option>
                  <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['categoria']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-3">
                <label for="codigoProducto" class="form-label fw-bold">Código del producto*</label>
                <input type="text" class="form-control" id="codigoProducto" name="codigoProducto"
                      placeholder="Ej: P-001" required>
              </div>

              <div class="col-md-5">
                <label for="descripcionProducto" class="form-label fw-bold">Descripción*</label>
                <input type="text" class="form-control" id="descripcionProducto" name="descripcionProducto"
                      placeholder="Nombre del producto o servicio" required>
              </div>

              <div class="col-md-4">
                <label for="unidadMedida" class="form-label fw-bold">Unidad de medida</label>
                <input type="text" class="form-control" id="unidadMedida" name="unidadMedida"
                      placeholder="Ej: Unidad, Caja, Litro">
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-3">
                <label for="cantidad" class="form-label fw-bold">Cantidad</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" min="0">
              </div>

              <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="productoIva" name="productoIva">
                  <label class="form-check-label fw-bold" for="productoIva">Producto con IVA</label>
                </div>
              </div>

              <div class="col-md-4 d-flex align-items-center">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="facturacionCero" name="facturacionCero">
                  <label class="form-check-label fw-bold" for="facturacionCero">
                    Permite facturación con existencias en cero
                  </label>
                </div>
              </div>

              <div class="col-md-2 d-flex align-items-center">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="activo" name="activo" required>
                  <label class="form-check-label fw-bold" for="activo">Activo*</label>
                </div>
              </div>
            </div>

            <!-- Radio Buttons -->
            <div class="row g-3 mt-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">Tipo de ítem*</label><br>
                <div class="form-check form-check-inline">
                  <input type="radio" class="form-check-input" id="tipoProducto" name="tipoItem" value="producto" required>
                  <label class="form-check-label" for="tipoProducto">Producto</label>
                </div>
                <div class="form-check form-check-inline">
                  <input type="radio" class="form-check-input" id="tipoServicio" name="tipoItem" value="servicio">
                  <label class="form-check-label" for="tipoServicio">Servicio</label>
                </div>
              </div>
            </div>

            <!-- Botón -->
            <div class="mt-4">
              <button value="btnAgregarProducto" type="submit" class="btn btn-primary" name="accion">
                Guardar Producto/Servicio
              </button>
            </div>
          </form>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
          // Datos de ejemplo (mantén o carga desde donde quieras)
          const cuentasContables = {
            'codigoCuentaVentas': [
              {codigo: '413501', nombre: 'Comercio al por mayor y al detal'},
              {codigo: '417505', nombre: 'Devolución'},
              {codigo: '418001', nombre: 'Servicios'}
            ],
            'codigoCuentaInventarios': [
              {codigo: '143501', nombre: 'Mercancías no fabricadas'},
              {codigo: '149801', nombre: 'Otros'}
            ],
            'codigoCuentaCostos': [
              {codigo: '613505', nombre: 'Comercio al por mayor y al por menor'},
              {codigo: '618001', nombre: 'Servicios'}
            ],
            'codigoCuentaDevoluciones': [
              {codigo: '417505', nombre: 'Devolución'}
            ],

            // Soporte para claves antiguas (por si volvieron a usar los ids viejos)
            'ventas': [
              {codigo: '413501', nombre: 'Comercio al por mayor y al detal'},
              {codigo: '417505', nombre: 'Devolución'},
              {codigo: '418001', nombre: 'Servicios'}
            ],
            'inventarios': [
              {codigo: '143501', nombre: 'Mercancías no fabricadas'},
              {codigo: '149801', nombre: 'Otros'}
            ],
            'costos': [
              {codigo: '613505', nombre: 'Comercio al por mayor y al por menor'},
              {codigo: '618001', nombre: 'Servicios'}
            ],
            'devoluciones': [
              {codigo: '417505', nombre: 'Devolución'}
            ]
          };

          // Mapeo explícito: id del input de código -> id del input del nombre
          const mapping = {
            // nuevos nombres (recomendados según tu PHP)
            'codigoCuentaVentas': 'cuentaVentas',
            'codigoCuentaInventarios': 'cuentaInventarios',
            'codigoCuentaCostos': 'cuentaCostos',
            'codigoCuentaDevoluciones': 'cuentaDevoluciones',

            // antiguos (por compatibilidad)
            'ventas': 'nombre_ventas',
            'inventarios': 'nombre_inventarios',
            'costos': 'nombre_costos',
            'devoluciones': 'nombre_devoluciones'
          };

          // Recorremos las claves del mapping (asegura que chequea inputs viejos y nuevos)
          Object.keys(mapping).forEach(key => {
            const inputCodigo = document.getElementById(key);
            const inputNombre = document.getElementById(mapping[key]);

            // Si no existen ambos campos para esa clave, saltamos
            if (!inputCodigo || !inputNombre) return;

            // Escuchar 'input' para mostrar sugerencias
            inputCodigo.addEventListener('input', function () {
              const searchTerm = inputCodigo.value.trim().toLowerCase();
              const sourceArray = cuentasContables[key] || [];
              const filtered = sourceArray.filter(c =>
                c.codigo.toLowerCase().includes(searchTerm) || c.nombre.toLowerCase().includes(searchTerm)
              );
              mostrarSugerencias(filtered, inputCodigo, inputNombre);
            });

            // También manejar tecla abajo/arriba + Enter (opcional)
            inputCodigo.addEventListener('keydown', function(e) {
              const ul = inputCodigo.parentNode.querySelector('.sugerencias');
              if (!ul) return;
              const items = Array.from(ul.querySelectorAll('li'));
              const active = ul.querySelector('li.active');
              let index = items.indexOf(active);

              if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (index < items.length - 1) index++;
                else index = 0;
                setActive(items, index);
              } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (index > 0) index--;
                else index = items.length - 1;
                setActive(items, index);
              } else if (e.key === 'Enter') {
                e.preventDefault();
                if (items[index]) {
                  items[index].dispatchEvent(new Event('selectFromList'));
                }
              }
            });
          });

          function setActive(items, idx) {
            items.forEach(i => i.classList.remove('active'));
            if (items[idx]) items[idx].classList.add('active');
          }

          function mostrarSugerencias(cuentas, inputCodigo, inputNombre) {
            limpiarSugerencias(inputCodigo);
            if (!cuentas || cuentas.length === 0) return;

            const ul = document.createElement('ul');
            ul.classList.add('sugerencias', 'list-group');
            ul.style.position = 'absolute';
            ul.style.zIndex = '1000';
            ul.style.width = '100%';
            ul.style.maxHeight = '220px';
            ul.style.overflowY = 'auto';
            ul.style.marginTop = '6px';

            cuentas.forEach(c => {
              const li = document.createElement('li');
              li.textContent = `${c.codigo} - ${c.nombre}`;
              li.classList.add('list-group-item', 'list-group-item-action');
              // Usamos pointerdown para que se ejecute ANTES del blur del input
              li.addEventListener('pointerdown', function (ev) {
                // Hacemos la asignación directamente
                inputCodigo.value = c.codigo;
                inputNombre.value = c.nombre;
                // Evitamos que otro manejador borre por blur antes
                ev.preventDefault();
                limpiarSugerencias(inputCodigo);
              });

              // También permitimos seleccionar por teclado
              li.addEventListener('selectFromList', function () {
                inputCodigo.value = c.codigo;
                inputNombre.value = c.nombre;
                limpiarSugerencias(inputCodigo);
              });

              // Hover visual
              li.addEventListener('mouseenter', () => {
                li.classList.add('active');
              });
              li.addEventListener('mouseleave', () => {
                li.classList.remove('active');
              });

              ul.appendChild(li);
            });

            // Posicionar el contenedor relativo si es necesario
            // Esto asume que el padre tiene position: relative; si no, lo colocamos
            const parent = inputCodigo.parentNode;
            const computed = window.getComputedStyle(parent);
            if (computed.position === 'static') {
              parent.style.position = 'relative';
            }

            parent.appendChild(ul);

            // Si el usuario hace click en otro lado, cerramos (con un pequeño delay si se quiere)
            document.addEventListener('click', function onDocClick(e) {
              if (!parent.contains(e.target)) {
                limpiarSugerencias(inputCodigo);
                document.removeEventListener('click', onDocClick);
              }
            });
          }

          function limpiarSugerencias(input) {
            const prev = input.parentNode.querySelector('.sugerencias');
            if (prev) prev.remove();
          }
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