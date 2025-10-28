<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Inicialización de variables para Categoría
$idcategoria=(isset($_POST['idcategoria']))?$_POST['idcategoria']:""; // Añadido para edición/eliminación
$categoria=(isset($_POST['categoria']))?$_POST['categoria']:"";
$codigoCuentaVentas=(isset($_POST['codigoCuentaVentas']))?$_POST['codigoCuentaVentas']:"";
$cuentaVentas=(isset($_POST['cuentaVentas']))?$_POST['cuentaVentas']:"";
$codigoCuentaInventarios=(isset($_POST['codigoCuentaInventarios']))?$_POST['codigoCuentaInventarios']:"";
$cuentaInventarios=(isset($_POST['cuentaInventarios']))?$_POST['cuentaInventarios']:"";
$codigoCuentaCostos=(isset($_POST['codigoCuentaCostos']))?$_POST['codigoCuentaCostos']:"";
$cuentaCostos=(isset($_POST['cuentaCostos']))?$_POST['cuentaCostos']:"";
$codigoCuentaDevoluciones=(isset($_POST['codigoCuentaDevoluciones']))?$_POST['codigoCuentaDevoluciones']:"";
$cuentaDevoluciones=(isset($_POST['cuentaDevoluciones']))?$_POST['cuentaDevoluciones']:"";

// Inicialización de variables para Producto
$idproducto=(isset($_POST['idproducto']))?$_POST['idproducto']:""; // Añadido para edición/eliminación
$categoriaInventarios=(isset($_POST['categoriaInventarios']))?$_POST['categoriaInventarios']:"";
$codigoProducto=(isset($_POST['codigoProducto']))?$_POST['codigoProducto']:"";
$descripcionProducto=(isset($_POST['descripcionProducto']))?$_POST['descripcionProducto']:"";
$unidadMedida=(isset($_POST['unidadMedida']))?$_POST['unidadMedida']:"";
$cantidad=(isset($_POST['cantidad']))?$_POST['cantidad']:"";

$tipoItem=(isset($_POST['tipoItem']))?$_POST['tipoItem']:"";
$facturacionCero_post=(isset($_POST['facturacionCero']))?$_POST['facturacionCero']:"";
$activo_post=(isset($_POST['activo']))?$_POST['activo']:"";
$productoIva_post=(isset($_POST['productoIva']))?$_POST['productoIva']:""; // Variable temporal para el checkbox

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

// Convertir checkboxes a valores binarios (usamos las variables temporales para evitar conflictos)
$productoIva = isset($_POST['productoIva']) ? 1 : 0;
$facturacionCero = isset($_POST['facturacionCero']) ? 1 : 0;
$activo = isset($_POST['activo']) ? 1 : 0;

// =========================================================================
// 1. Lógica CRUD (Create, Read, Update, Delete)
// =========================================================================

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
        exit;

    break;

    case "btnModificarCategoria":
        // Asegúrate de incluir el campo oculto idcategoria en tu formulario de edición
        $sentencia=$pdo->prepare("UPDATE categoriainventarios SET categoria=:categoria, codigoCuentaVentas=:codigoCuentaVentas, cuentaVentas=:cuentaVentas, codigoCuentaInventarios=:codigoCuentaInventarios, cuentaInventarios=:cuentaInventarios, codigoCuentaCostos=:codigoCuentaCostos, cuentaCostos=:cuentaCostos, codigoCuentaDevoluciones=:codigoCuentaDevoluciones, cuentaDevoluciones=:cuentaDevoluciones WHERE id=:idcategoria");

        $sentencia->bindParam(':idcategoria',$idcategoria);
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

        header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
        exit; 

    break;
    
    case "btnEliminarCategoria":
        $sentencia=$pdo->prepare("DELETE FROM categoriainventarios WHERE id=:idcategoria");
        $sentencia->bindParam(':idcategoria',$idcategoria);
        $sentencia->execute();

        header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
        exit; 

    break;

    case "btnAgregarProducto":
        $sentencia=$pdo->prepare("INSERT INTO productoinventarios(categoriaInventarios,codigoProducto,descripcionProducto,unidadMedida,cantidad,productoIva,tipoItem,facturacionCero,activo) 
        VALUES (:categoriaInventarios,:codigoProducto,:descripcionProducto,:unidadMedida,:cantidad,:productoIva,:tipoItem,:facturacionCero,:activo)");
        
        $sentencia->bindParam(':categoriaInventarios',$categoriaInventarios);
        $sentencia->bindParam(':codigoProducto',$codigoProducto);
        $sentencia->bindParam(':descripcionProducto',$descripcionProducto);
        $sentencia->bindParam(':unidadMedida',$unidadMedida);
        $sentencia->bindParam(':cantidad',$cantidad);
        $sentencia->bindParam(':productoIva',$productoIva); // Ya es 0 o 1
        $sentencia->bindParam(':tipoItem',$tipoItem);
        $sentencia->bindParam(':facturacionCero',$facturacionCero); // Ya es 0 o 1
        $sentencia->bindParam(':activo',$activo); // Ya es 0 o 1

        $sentencia->execute();

        header("Location: ".$_SERVER['PHP_SELF']."?msg=agregadoProducto");
        exit;

    break;

    case "btnModificarProducto":
        $sentencia=$pdo->prepare("UPDATE productoinventarios SET categoriaInventarios=:categoriaInventarios, codigoProducto=:codigoProducto, descripcionProducto=:descripcionProducto, unidadMedida=:unidadMedida, cantidad=:cantidad, productoIva=:productoIva, tipoItem=:tipoItem, facturacionCero=:facturacionCero, activo=:activo WHERE id=:idproducto");
        
        $sentencia->bindParam(':idproducto',$idproducto);
        $sentencia->bindParam(':categoriaInventarios',$categoriaInventarios);
        $sentencia->bindParam(':codigoProducto',$codigoProducto);
        $sentencia->bindParam(':descripcionProducto',$descripcionProducto);
        $sentencia->bindParam(':unidadMedida',$unidadMedida);
        $sentencia->bindParam(':cantidad',$cantidad);
        $sentencia->bindParam(':productoIva',$productoIva); // Ya es 0 o 1
        $sentencia->bindParam(':tipoItem',$tipoItem);
        $sentencia->bindParam(':facturacionCero',$facturacionCero); // Ya es 0 o 1
        $sentencia->bindParam(':activo',$activo); // Ya es 0 o 1

        $sentencia->execute();

        header("Location: ".$_SERVER['PHP_SELF']."?msg=modificadoProducto");
        exit;

    break;

    case "btnEliminarProducto":
        $sentencia=$pdo->prepare("DELETE FROM productoinventarios WHERE id=:idproducto");
        $sentencia->bindParam(':idproducto',$idproducto);
        $sentencia->execute();

        header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminadoProducto");
        exit;

    break;
}

// =========================================================================
// 2. Obtener datos para visualización (Lectura)
// =========================================================================

// Obtener todas las categorías registradas
$sentenciaCategorias = $pdo->prepare("SELECT id, categoria, codigoCuentaVentas, cuentaVentas, codigoCuentaInventarios, cuentaInventarios, codigoCuentaCostos, cuentaCostos, codigoCuentaDevoluciones, cuentaDevoluciones FROM categoriainventarios ORDER BY categoria ASC");
$sentenciaCategorias->execute();
$categorias = $sentenciaCategorias->fetchAll(PDO::FETCH_ASSOC);


// Obtener todos los productos/servicios registrados (usando JOIN para mostrar el nombre de la categoría)
$sentenciaProductos = $pdo->prepare("SELECT 
    p.id, 
    p.categoriaInventarios AS idCategoria,
    c.categoria,
    p.codigoProducto,
    p.descripcionProducto,
    p.unidadMedida,
    p.cantidad,
    p.productoIva,
    p.tipoItem,
    p.facturacionCero,
    p.activo 
    FROM productoinventarios p
    JOIN categoriainventarios c ON p.categoriaInventarios = c.id
    ORDER BY p.descripcionProducto ASC");
$sentenciaProductos->execute();
$productos = $sentenciaProductos->fetchAll(PDO::FETCH_ASSOC);

?>

<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    switch ("<?= $_GET['msg'] ?>") {
        case "agregado":
        case "agregadoProducto":
            Swal.fire({
                icon: 'success',
                title: 'Guardado exitosamente',
                text: 'El registro se ha agregado correctamente',
                confirmButtonColor: '#3085d6'
            });
            break;
        
        case "modificado":
        case "modificadoProducto":
            Swal.fire({
                icon: 'success',
                title: 'Modificado correctamente',
                text: 'Los datos se actualizaron con éxito',
                confirmButtonColor: '#3085d6'
            });
            break;

        case "eliminado":
        case "eliminadoProducto":
            Swal.fire({
                icon: 'success',
                title: 'Eliminado correctamente',
                text: 'El registro fue eliminado',
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
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 

  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
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

          <input type="hidden" name="idcategoria" id="idcategoria" value="">
          <div class="row g-3"></div>

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
                <select class="form-control select-cuenta" id="codigoCuentaVentas" name="codigoCuentaVentas">
                  <option value="">Selecciona una cuenta de ventas</option>
                </select>
                <input type="text" class="form-control mt-1" id="cuentaVentas" name="cuentaVentas" placeholder="Nombre cuenta ventas" readonly>
              </div>

              <div class="col-md-6">
                <label for="inventarios" class="form-label fw-bold">Código Inventarios</label>
                <select class="form-control select-cuenta" id="codigoCuentaInventarios" name="codigoCuentaInventarios">
                  <option value="">Selecciona una cuenta de inventarios</option>
                </select>
                <input type="text" class="form-control mt-1" id="cuentaInventarios" name="cuentaInventarios" placeholder="Nombre cuenta inventarios" readonly>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label for="costos" class="form-label fw-bold">Código Costos</label>
                <select class="form-control select-cuenta" id="codigoCuentaCostos" name="codigoCuentaCostos">
                  <option value="">Selecciona una cuenta de costos</option>
                </select>
                <input type="text" class="form-control mt-1" id="cuentaCostos" name="cuentaCostos" placeholder="Nombre cuenta costos" readonly>
              </div>

              <div class="col-md-6">
                <label for="devoluciones" class="form-label fw-bold">Código Devoluciones</label>
                <select class="form-control select-cuenta" id="codigoCuentaDevoluciones" name="codigoCuentaDevoluciones">
                  <option value="">Selecciona una cuenta de devoluciones</option>
                </select>
                <input type="text" class="form-control mt-1" id="cuentaDevoluciones" name="cuentaDevoluciones" placeholder="Nombre cuenta devoluciones" readonly>
              </div>
            </div>

            <!-- Botón -->
            <div class="mt-4">
                  <button id="btnGuardarCategoria" value="btnAgregarCategoria" type="submit" class="btn btn-primary" name="accion">
                      Guardar Categoría
                  </button>
                  <button id="btnModificarCategoria" value="btnModificarCategoria" type="submit" class="btn btn-success d-none" name="accion">
                      Modificar Categoría
                  </button>
                  <button id="btnCancelarCategoria" type="button" class="btn btn-secondary d-none">
                      Cancelar Edición
                  </button>
              </div>
          </form>
        </div>

        <!--TABLA LISTA DE CATEGORÍAS -->
        <div class="mt-5">
            <div class="section-title">
                <h3>Categorías Registradas</h3>
            </div>
            <div class="table-responsive">
                <table class="table-container">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Categoría</th>
                            <th scope="col">Cód. Ventas</th>
                            <th scope="col">Cód. Inventarios</th>
                            <th scope="col">Cód. Costos</th>
                            <th scope="col">Cód. Dev.</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cat['id']); ?></td>
                            <td><?php echo htmlspecialchars($cat['categoria']); ?></td>
                            <td><?php echo htmlspecialchars($cat['codigoCuentaVentas']); ?></td>
                            <td><?php echo htmlspecialchars($cat['codigoCuentaInventarios']); ?></td>
                            <td><?php echo htmlspecialchars($cat['codigoCuentaCostos']); ?></td>
                            <td><?php echo htmlspecialchars($cat['codigoCuentaDevoluciones']); ?></td>
                            <td>
                                <form action="" method="post" class="d-inline form-accion-categoria">
                                    <input type="hidden" name="idcategoria" value="<?php echo $cat['id']; ?>">
                                    <input type="hidden" data-campo="idcategoria" value="<?php echo htmlspecialchars($cat['id']); ?>">
                                    <input type="hidden" data-campo="categoria" value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                                    <input type="hidden" data-campo="codigoCuentaVentas" value="<?php echo htmlspecialchars($cat['codigoCuentaVentas']); ?>">
                                    <input type="hidden" data-campo="cuentaVentas" value="<?php echo htmlspecialchars($cat['cuentaVentas']); ?>">
                                    <input type="hidden" data-campo="codigoCuentaInventarios" value="<?php echo htmlspecialchars($cat['codigoCuentaInventarios']); ?>">
                                    <input type="hidden" data-campo="cuentaInventarios" value="<?php echo htmlspecialchars($cat['cuentaInventarios']); ?>">
                                    <input type="hidden" data-campo="codigoCuentaCostos" value="<?php echo htmlspecialchars($cat['codigoCuentaCostos']); ?>">
                                    <input type="hidden" data-campo="cuentaCostos" value="<?php echo htmlspecialchars($cat['cuentaCostos']); ?>">
                                    <input type="hidden" data-campo="codigoCuentaDevoluciones" value="<?php echo htmlspecialchars($cat['codigoCuentaDevoluciones']); ?>">
                                    <input type="hidden" data-campo="cuentaDevoluciones" value="<?php echo htmlspecialchars($cat['cuentaDevoluciones']); ?>">
                                    
                                    <button type="button" class="btn btn-sm btn-info btn-editar-categoria" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="submit" value="btnEliminarCategoria" name="accion" class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PRODUCTOS -->
        <div class="mt-5">
          <div  class="section-title">
            <p>Para crear un nuevo producto diligencie los campos a continuación:</p>
            <p class="text-muted">(Los campos marcados con * son obligatorios)</p>
          </div>

          <form action="" method="post" id="formProductos">
            <input type="hidden" name="idproducto" id="idproducto" value="">

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
                <button value="btnAgregarProducto" type="submit" class="btn btn-primary" name="accion" id="btnGuardarProducto">
                    Guardar Producto/Servicio
                </button>
                <button id="btnModificarProducto" value="btnModificarProducto" type="submit" class="btn btn-success d-none" name="accion">
                    Modificar Producto/Servicio
                </button>
                <button id="btnCancelarProducto" type="button" class="btn btn-secondary d-none">
                    Cancelar Edición
                </button>
            </div>
          </form>
        </div>

        <!--TABLA LISTA DE PRODUCTOS -->
        <div class="mt-5">
            <div class="section-title">
                <h3>Productos/Servicios Registrados</h3>
            </div>
            <div class="table-responsive">
                <table class="table-container">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Categoría</th>
                            <th scope="col">Código</th>
                            <th scope="col">Descripción</th>
                            <th scope="col">Unidad</th>
                            <th scope="col">Cant.</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">IVA</th>
                            <th scope="col">Activo</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $prod): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($prod['id']); ?></td>
                            <td><?php echo htmlspecialchars($prod['categoria']); ?></td>
                            <td><?php echo htmlspecialchars($prod['codigoProducto']); ?></td>
                            <td><?php echo htmlspecialchars($prod['descripcionProducto']); ?></td>
                            <td><?php echo htmlspecialchars($prod['unidadMedida']); ?></td>
                            <td><?php echo htmlspecialchars($prod['cantidad']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($prod['tipoItem'])); ?></td>
                            <td><?php echo $prod['productoIva'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
                            <td><?php echo $prod['activo'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
                            <td>
                                <form action="" method="post" class="d-inline form-accion-producto">
                                    <input type="hidden" name="idproducto" value="<?php echo $prod['id']; ?>">
                                    <input type="hidden" data-campo="idproducto" value="<?php echo htmlspecialchars($prod['id']); ?>">
                                    <input type="hidden" data-campo="categoriaInventarios" value="<?php echo htmlspecialchars($prod['idCategoria']); ?>">
                                    <input type="hidden" data-campo="codigoProducto" value="<?php echo htmlspecialchars($prod['codigoProducto']); ?>">
                                    <input type="hidden" data-campo="descripcionProducto" value="<?php echo htmlspecialchars($prod['descripcionProducto']); ?>">
                                    <input type="hidden" data-campo="unidadMedida" value="<?php echo htmlspecialchars($prod['unidadMedida']); ?>">
                                    <input type="hidden" data-campo="cantidad" value="<?php echo htmlspecialchars($prod['cantidad']); ?>">
                                    <input type="hidden" data-campo="productoIva" value="<?php echo htmlspecialchars($prod['productoIva']); ?>">
                                    <input type="hidden" data-campo="facturacionCero" value="<?php echo htmlspecialchars($prod['facturacionCero']); ?>">
                                    <input type="hidden" data-campo="activo" value="<?php echo htmlspecialchars($prod['activo']); ?>">
                                    <input type="hidden" data-campo="tipoItem" value="<?php echo htmlspecialchars($prod['tipoItem']); ?>">

                                    <button type="button" class="btn btn-sm btn-info btn-editar-producto" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="submit" value="btnEliminarProducto" name="accion" class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
$(document).ready(function() {
    
    // Función para inicializar Select2 y cargar las cuentas vía AJAX
    function inicializarSelectCuenta(selector, tipoCuenta, placeholderText) {
        
        // Inicializa Select2 en el elemento
        $(selector).select2({
            placeholder: placeholderText,
            allowClear: true,
            // Permite que la busqueda se haga sobre la jerarquía completa
            matcher: function(params, data) {
                // Si no hay término de búsqueda, muestra todas las opciones
                if ($.trim(params.term) === '') {
                    return data;
                }
                // Si la cadena de la opción contiene el término de búsqueda, la incluye
                if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
                    return data;
                }
                return null;
            }
        });

        // Cargar las opciones vía AJAX
        $.ajax({
            url: 'obtener_cuentas_inventarios.php', // Nombre de tu archivo PHP
            type: 'GET',
            data: { tipo: tipoCuenta }, // Envía el tipo de cuenta (ventas, inventarios, etc.)
            dataType: 'json',
            success: function(data) {
                const selectElement = $(selector);
                selectElement.empty(); // Limpia opciones existentes

                // Agrega la opción por defecto (placeholder)
                selectElement.append(new Option(placeholderText, '', false, true));

                // Recorre el array de cuentas y crea las opciones
                data.forEach(function(cuenta) {
                    // El 'valor' es el código, el 'texto' es la descripción con jerarquía para Select2
                    var newOption = new Option(cuenta.texto, cuenta.valor, false, false);
                    
                    // 🔑 CAMBIO 1: Adjuntar el nombre_puro al elemento OPTION
                    // Esto permite recuperar el nombre limpio después
                    $(newOption).attr('data-nombre-puro', cuenta.nombre_puro);
                    
                    selectElement.append(newOption);
                });

                // Actualiza Select2
                selectElement.trigger('change');
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar cuentas de " + tipoCuenta + ":", status, error);
                // Si falla, al menos deja la opción por defecto
                $(selector).append(new Option("Error al cargar cuentas", "", false, true));
            }
        });
    }
    // ----------------------------------------------------
    // 2. Ejecutar la función para cada SELECT
    // ----------------------------------------------------

    inicializarSelectCuenta(
        '#codigoCuentaVentas', 
        'ventas', 
        'Selecciona una cuenta de ventas (Clase 4)'
    );

    inicializarSelectCuenta(
        '#codigoCuentaInventarios', 
        'inventarios', 
        'Selecciona una cuenta de inventarios (Clase 14)'
    );

    inicializarSelectCuenta(
        '#codigoCuentaCostos', 
        'costos', 
        'Selecciona una cuenta de costos (Clase 6)'
    );

    inicializarSelectCuenta(
        '#codigoCuentaDevoluciones', 
        'devoluciones', 
        'Selecciona una cuenta de devoluciones (Cta 4175)'
    );
    
    // ----------------------------------------------------
    // 3. Lógica para actualizar los campos de texto
    // ----------------------------------------------------
    
    // Función para manejar el cambio en el select y actualizar el input de texto
    function actualizarNombreCuenta(selectId, inputId) {
        $(selectId).on('change', function() {
            const selectedOption = $(this).find('option:selected');
            
            // 🔑 CAMBIO 2: Leer el atributo data-nombre-puro para obtener el nombre limpio
            const nombrePuro = selectedOption.data('nombre-puro');

            // Establece el valor del input de texto (será vacío si no se selecciona nada)
            $(inputId).val(nombrePuro || ""); 
        });
    }
    
    actualizarNombreCuenta('#codigoCuentaVentas', '#cuentaVentas');
    actualizarNombreCuenta('#codigoCuentaInventarios', '#cuentaInventarios');
    actualizarNombreCuenta('#codigoCuentaCostos', '#cuentaCostos');
    actualizarNombreCuenta('#codigoCuentaDevoluciones', '#cuentaDevoluciones');


    // ----------------------------------------------------
    // 4. Lógica para cargar datos en modo Edición (si aplica)
    // ----------------------------------------------------
    
    // Función para manejar el clic en el botón de edición
    $(document).on('click', '.btn-editar-categoria', function() {
        const row = $(this).closest('form');
        
        // Obtener los datos actuales de la fila
        const datos = {
            idcategoria: row.find('[data-campo="idcategoria"]').val(),
            categoria: row.find('[data-campo="categoria"]').val(),
            codigoCuentaVentas: row.find('[data-campo="codigoCuentaVentas"]').val(),
            // No necesitamos cuentaVentas aquí, se actualizará al hacer .val().trigger('change')
            codigoCuentaInventarios: row.find('[data-campo="codigoCuentaInventarios"]').val(),
            // ...
            codigoCuentaCostos: row.find('[data-campo="codigoCuentaCostos"]').val(),
            // ...
            codigoCuentaDevoluciones: row.find('[data-campo="codigoCuentaDevoluciones"]').val(),
            // ...
        };

        // Rellenar los campos del formulario de edición de Categoría
        $('#idcategoria').val(datos.idcategoria);
        $('#categoria').val(datos.categoria);
        
        // Seleccionar los valores en los Select2
        // El trigger('change') disparará la función actualizarNombreCuenta, llenando los inputs de texto
        $('#codigoCuentaVentas').val(datos.codigoCuentaVentas).trigger('change');
        $('#codigoCuentaInventarios').val(datos.codigoCuentaInventarios).trigger('change');
        $('#codigoCuentaCostos').val(datos.codigoCuentaCostos).trigger('change');
        $('#codigoCuentaDevoluciones').val(datos.codigoCuentaDevoluciones).trigger('change');
        
        // Si el valor del nombre de la cuenta en la tabla no coincide (por la extracción del nombre puro),
        // puedes cargarlo explícitamente desde la tabla aquí, aunque el trigger debería bastar.
        // Si no quieres esperar el trigger (y ya lo tienes en el hidden de la fila):
        $('#cuentaVentas').val(row.find('[data-campo="cuentaVentas"]').val());
        $('#cuentaInventarios').val(row.find('[data-campo="cuentaInventarios"]').val());
        $('#cuentaCostos').val(row.find('[data-campo="cuentaCostos"]').val());
        $('#cuentaDevoluciones').val(row.find('[data-campo="cuentaDevoluciones"]').val());


        // Mostrar botones de edición
        $('#btnGuardarCategoria').addClass('d-none');
        $('#btnModificarCategoria').removeClass('d-none');
        $('#btnCancelarCategoria').removeClass('d-none');
        
        // Mover el foco
        $('html, body').animate({
            scrollTop: $("#formCategorias").offset().top - 100
        }, 500);
    });

    // Lógica del botón Cancelar Edición
    $('#btnCancelarCategoria').on('click', function() {
        // Limpiar el formulario y resetear a modo 'Agregar'
        $('#formCategorias')[0].reset();
        $('#idcategoria').val('');
        
        // Asegurar que los select2 se reseteen visualmente
        $('#codigoCuentaVentas').val('').trigger('change');
        $('#codigoCuentaInventarios').val('').trigger('change');
        $('#codigoCuentaCostos').val('').trigger('change');
        $('#codigoCuentaDevoluciones').val('').trigger('change');
        
        // Mostrar botones de agregar
        $('#btnGuardarCategoria').removeClass('d-none');
        $('#btnModificarCategoria').addClass('d-none');
        $('#btnCancelarCategoria').addClass('d-none');
    });

});

        // Funciones de Edición de Categorías y Productos
        document.addEventListener("DOMContentLoaded", () => {
            // Edición de Categorías
            const formCategorias = document.getElementById('formCategorias');
            const btnGuardarCategoria = document.getElementById('btnGuardarCategoria');
            const btnModificarCategoria = document.getElementById('btnModificarCategoria');
            const btnCancelarCategoria = document.getElementById('btnCancelarCategoria');

            document.querySelectorAll('.btn-editar-categoria').forEach(button => {
                button.addEventListener('click', function() {
                    const form = this.closest('.form-accion-categoria');
                    
                    // Llenar el formulario principal con los datos ocultos de la fila
                    form.querySelectorAll('input[type="hidden"][data-campo]').forEach(input => {
                        const targetId = input.getAttribute('data-campo');
                        const targetInput = formCategorias.querySelector(`#${targetId}`);
                        if (targetInput) {
                            targetInput.value = input.value;
                        }
                    });

                    // Cambiar la visibilidad de los botones
                    btnGuardarCategoria.classList.add('d-none');
                    btnModificarCategoria.classList.remove('d-none');
                    btnCancelarCategoria.classList.remove('d-none');
                });
            });

            btnCancelarCategoria.addEventListener('click', function() {
                // Limpiar formulario y restablecer botones
                formCategorias.reset();
                document.getElementById('idcategoria').value = "";
                btnGuardarCategoria.classList.remove('d-none');
                btnModificarCategoria.classList.add('d-none');
                btnCancelarCategoria.classList.add('d-none');
            });

            // Edición de Productos/Servicios
            const formProductos = document.getElementById('formProductos');
            const btnGuardarProducto = document.getElementById('btnGuardarProducto');
            const btnModificarProducto = document.getElementById('btnModificarProducto');
            const btnCancelarProducto = document.getElementById('btnCancelarProducto');

            document.querySelectorAll('.btn-editar-producto').forEach(button => {
                button.addEventListener('click', function() {
                    const form = this.closest('.form-accion-producto');
                    
                    // Llenar el formulario principal con los datos ocultos de la fila
                    form.querySelectorAll('input[type="hidden"][data-campo]').forEach(input => {
                        const targetId = input.getAttribute('data-campo');
                        const targetInput = formProductos.querySelector(`#${targetId}`);

                        if (targetInput) {
                            const value = input.value;
                            if (targetInput.type === 'checkbox') {
                                // Para checkboxes (productoIva, facturacionCero, activo)
                                targetInput.checked = (value == 1);
                            } else if (targetInput.type === 'radio') {
                                // Para radio buttons (tipoItem)
                                formProductos.querySelector(`input[name="${targetId}"][value="${value}"]`).checked = true;
                            } else {
                                // Para campos de texto/select
                                targetInput.value = value;
                            }
                        }
                    });

                    // Cambiar la visibilidad de los botones
                    btnGuardarProducto.classList.add('d-none');
                    btnModificarProducto.classList.remove('d-none');
                    btnCancelarProducto.classList.remove('d-none');
                });
            });

            btnCancelarProducto.addEventListener('click', function() {
                // Limpiar formulario y restablecer botones
                formProductos.reset();
                document.getElementById('idproducto').value = "";
                btnGuardarProducto.classList.remove('d-none');
                btnModificarProducto.classList.add('d-none');
                btnCancelarProducto.classList.add('d-none');
                // Asegurar que el checkbox 'Activo' esté marcado por defecto al cancelar
                document.getElementById('activo').checked = true; 
            });

            // Asegurar que el checkbox 'Activo' esté marcado por defecto al cargar la página
            if (document.getElementById('activo')) {
                document.getElementById('activo').checked = true;
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
                let isDelete = false;
                let isModify = false;

                // Verificar acciones de modificar y eliminar
                if (accion === "btnModificarCategoria" || accion === "btnModificarProducto") {
                    isModify = true;
                } else if (accion === "btnEliminarCategoria" || accion === "btnEliminarProducto") {
                    isDelete = true;
                }

                // Solo mostrar confirmación para modificar o eliminar
                if (isModify || isDelete) {
                    e.preventDefault(); // detener envío temporalmente

                    let titulo = isModify ? "¿Guardar cambios?" : "¿Eliminar registro?";
                    let texto = isModify
                        ? "Se actualizarán los datos."
                        : "Esta acción eliminará el registro permanentemente.";

                    Swal.fire({
                        title: titulo,
                        text: texto,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Sí, continuar",
                        cancelButtonText: "Cancelar",
                        confirmButtonColor: isModify ? "#3085d6" : "#d33",
                        cancelButtonColor: "#6c757d",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Si la acción es de la tabla (Eliminar), necesitamos el campo de acción
                            // Si viene del formulario de edición/guardar, el botón 'name="accion"' ya está ahí.
                            if (form.classList.contains('form-accion-categoria') || form.classList.contains('form-accion-producto')) {
                                // Crear (si no existe) un campo oculto con la acción seleccionada
                                let inputAccion = form.querySelector("input[name='accion']");
                                if (!inputAccion) {
                                    inputAccion = document.createElement("input");
                                    inputAccion.type = "hidden";
                                    inputAccion.name = "accion";
                                    form.appendChild(inputAccion);
                                }
                                inputAccion.value = accion; // Asignar la acción correcta (Eliminar)

                            } else if(isModify) {
                                // Si es modificar desde el formulario principal, solo actualizamos el campo oculto
                                let inputAccion = form.querySelector("input[name='accion']");
                                if (!inputAccion) {
                                    inputAccion = document.createElement("input");
                                    inputAccion.type = "hidden";
                                    inputAccion.name = "accion";
                                    form.appendChild(inputAccion);
                                }
                                inputAccion.value = accion;
                            }
                            
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

  <!--  Footer  -->
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