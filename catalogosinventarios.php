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
$tipoTercero=(isset($_POST['tipoTercero']))?$_POST['tipoTercero']:"";
$facturacionCero=(isset($_POST['facturacionCero']))?$_POST['facturacionCero']:"";
$activo=(isset($_POST['activo']))?$_POST['activo']:"";


$accion=(isset($_POST['accion']))?$_POST['accion']:"";

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

  echo "Presionaste"; 
  break;

  case "btnAgregarProducto":

    $sentencia=$pdo->prepare("INSERT INTO productoinventarios(categoriaInventarios,codigoProducto,descripcionProducto,unidadMedida,cantidad,productoIva,tipoTercero,facturacionCero,activo) 
    VALUES (:categoriaInventarios,:codigoProducto,:descripcionProducto,:unidadMedida,:cantidad,:productoIva,:tipoTercero,:facturacionCero,:activo)");
    

    $sentencia->bindParam(':categoriaInventarios',$categoriaInventarios);
    $sentencia->bindParam(':codigoProducto',$codigoProducto);
    $sentencia->bindParam(':descripcionProducto',$descripcionProducto);
    $sentencia->bindParam(':unidadMedida',$unidadMedida);
    $sentencia->bindParam(':cantidad',$cantidad);
    $sentencia->bindParam(':productoIva',$productoIva);
    $sentencia->bindParam(':tipoTercero',$tipoTercero);
    $sentencia->bindParam(':facturacionCero',$facturacionCero);
    $sentencia->bindParam(':activo',$activo);

    $sentencia->execute();

  echo "Presionaste"; 
  break;
}

?>

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

  <link href="assets/css/style.css" rel="stylesheet">

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
      <h1 class="logo"><a href="dashboard.php"> S O F I </a>  = >  Software Financiero </h1>
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
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <br><br><br><br><br>
          <h2>CATÁLOGO DE INVENTARIOS</h2>
        </div>

        <div>

          <div class="section-title"> 
            <p>Para crear una nueva categoría de inventarios diligencie los campos a continuación:</p>
            <p>(Los campos marcados con * son obligatorios)</p>
          </div>
          
          <form action="" method="post">

            <div class="mb-3">
              <label for="categoria" class="form-label">Categoría*</label>
              <input type="text" class="form-control" id="idcategoria" name="idcategoria" placeholder="Ingresa el codigo de la categoría" required>
              <input type="text" class="form-control" id="categoria" name="categoria" placeholder="Ingresa el nombre de la categoría" required>
            </div>

            <!-- Campo Ventas -->
            <div class="form-group position-relative mb-4">
              <label for="ventas">Código Ventas:</label>
              <input type="text" class="form-control" id="ventas" placeholder="Ingresa código ventas" autocomplete="off">
              <input type="text" class="form-control mt-1" id="nombre_ventas" placeholder="Nombre cuenta ventas" readonly>
            </div>

            <!-- Campo Inventarios -->
            <div class="form-group position-relative mb-4">
              <label for="inventarios">Código Inventarios:</label>
              <input type="text" class="form-control" id="inventarios" placeholder="Ingresa código inventarios" autocomplete="off">
              <input type="text" class="form-control mt-1" id="nombre_inventarios" placeholder="Nombre cuenta inventarios" readonly>
            </div>

            <!-- Campo Costos -->
            <div class="form-group position-relative mb-4">
              <label for="costos">Código Costos:</label>
              <input type="text" class="form-control" id="costos" placeholder="Ingresa código costos" autocomplete="off">
              <input type="text" class="form-control mt-1" id="nombre_costos" placeholder="Nombre cuenta costos" readonly>
            </div>

            <!-- Campo Devoluciones -->
            <div class="form-group position-relative mb-4">
              <label for="devoluciones">Código Devoluciones:</label>
              <input type="text" class="form-control" id="devoluciones" placeholder="Ingresa código devoluciones" autocomplete="off">
              <input type="text" class="form-control mt-1" id="nombre_devoluciones" placeholder="Nombre cuenta devoluciones" readonly>
            </div>
            <button value="btnAgregarCategoria" type="submit" class="btn btn-primary"  name="accion" >Guardar Categoría</button>

          </form>

        </div>  

        <div>

          <div class="section-title">
            <br><br>
            <p>Para crear un nuevo producto diligencie los campos a continuación:</p>
            <p>(Los campos marcados con * son obligatorios)</p>
          </div>

          
          <form action="" method="post">
            <!-- Campo para mostrar las categorías guardadas -->
            <div class="mb-3">
              <label for="categoriaInventarios" class="form-label">Categoría de inventarios</label>
              <select id="categoriaInventarios" name="categoriaInventarios" class="form-control">
                <option value="">Seleccione una categoría</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="producto" class="form-label">Código y descripción del producto/servicio</label>
              <input type="text" class="form-control" id="codigoProducto" name="codigoProducto" placeholder="Ingresa el codigo">
              <input type="text" class="form-control" id="descripcionProducto" name="descripcionProducto" placeholder="Ingresa el nombre del producto o servicio">
            </div>

            <div class="mb-3">
              <label for="unidadMedida" class="form-label">Unidad de medida</label>
              <input type="text" class="form-control" id="unidadMedida" name="unidadMedida" placeholder="">
            </div>

            <div class="mb-3">
              <label for="cantidad" class="form-label">Cantidad</label>
              <input type="number" class="form-control" id="cantidad" name="cantidad" placeholder="">
            </div>

            <div class="mb-3">
              <label for="productoIva" class="form-label">Producto con IVA?</label>
              <input type="checkbox" class="" id="productoIva" name="productoIva" placeholder="">
            </div>
            <div>
              <label>
                <input type="radio" name="tipoTercero" value="producto" onclick="toggleTipoTercero()">
                Producto
              </label>
              <label>
                <input type="radio" name="tipoTercero" value="servicio" onclick="toggleTipoTercero()">
                Servicio
              </label>
            </div>
            <br>
            <div class="mb-3">
              <label for="facturacionCero" class="form-label">Permite facturación con existencias en cero?</label>
              <input type="checkbox" class="" id="facturacionCero" name="facturacionCero" placeholder="">
            </div>
            <div class="mb-3">
              <label for="activo" class="form-label">Activo*</label>
              <input type="checkbox" class="" id="activo" name="activo" placeholder="" required>
            </div>
            <button value="btnAgregarProducto" type="submit" class="btn btn-primary"  name="accion" >Guardar Producto/Servicio</button>
          </form>
        
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
          const cuentasContables = {
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

          const keys = ['ventas', 'inventarios', 'costos', 'devoluciones'];

          keys.forEach(key => {
            const input = document.getElementById(key);
            const inputNombre = document.getElementById('nombre_' + key);

            input.addEventListener('input', function () {
              const searchTerm = input.value.toLowerCase();
              const filtered = cuentasContables[key].filter(c =>
                c.codigo.toLowerCase().includes(searchTerm) || c.nombre.toLowerCase().includes(searchTerm)
              );
              mostrarSugerencias(filtered, input, inputNombre);
            });
          });

          function mostrarSugerencias(cuentas, input, inputNombre) {
            limpiarSugerencias(input);
            if (cuentas.length === 0) return;

            const ul = document.createElement('ul');
            ul.classList.add('sugerencias');

            cuentas.forEach(c => {
              const li = document.createElement('li');
              li.textContent = `${c.codigo} - ${c.nombre}`;
              li.classList.add('list-group-item');
              li.addEventListener('click', () => {
                input.value = c.codigo;
                inputNombre.value = c.nombre;
                limpiarSugerencias(input);
              });
              ul.appendChild(li);
            });

            input.parentNode.appendChild(ul);
          }

          function limpiarSugerencias(input) {
            const prev = input.parentNode.querySelector('.sugerencias');
            if (prev) prev.remove();
          }
        });
        </script>

      </div>
    </section><!-- End Services Section -->

  <!-- ======= Footer ======= -->
  <footer id="footer">
    <div class="footer-top">
      <div class="container">
        <div class="row">

          <div class="col-lg-3 col-md-6 footer-links">
            <h4>Useful Links</h4>
            <ul>
              <li><i class="bx bx-chevron-right"></i> <a target="_blank" href="https://udes.edu.co">UDES</a></li>
              <li><i class="bx bx-chevron-right"></i> <a target="_blank" href="https://bucaramanga.udes.edu.co/estudia/pregrados/contaduria-publica">CONTADURIA PUBLICA</a></li>
            </ul>
          </div>

          <div class="col-lg-3 col-md-6 footer-links">
            <h4>Ubicación</h4>
            <p>
              Calle 70 N° 55-210, <br>
              Bucaramanga, <br>
              Santander <br><br>
            </p>
          </div>

          <div class="col-lg-3 col-md-6 footer-contact">
            <h4>Contactenos</h4>
            <p>
              <strong>Teléfono:</strong> (607) 6516500 <br>
              <strong>Email:</strong> notificacionesudes@udes.edu.co <br>
            </p>
          </div>

          <div class="col-lg-3 col-md-6 footer-info">
            <h3>Redes Sociales</h3>
            <p>A través de los siguientes link´s puedes seguirnos.</p>
            <div class="social-links mt-3">
              <a href="#" class="twitter"><i class="bx bxl-twitter"></i></a>
              <a href="#" class="facebook"><i class="bx bxl-facebook"></i></a>
              <a href="#" class="instagram"><i class="bx bxl-instagram"></i></a>
              <a href="#" class="google-plus"><i class="bx bxl-skype"></i></a>
              <a href="#" class="linkedin"><i class="bx bxl-linkedin"></i></a>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="container">
      <div class="copyright">
        &copy; Copyright 2023 <strong><span> UNIVERSIDAD DE SANTANDER </span></strong>. All Rights Reserved
      </div>
      <div class="credits">
        Creado por iniciativa del programa de <a href="https://bucaramanga.udes.edu.co/estudia/pregrados/contaduria-publica">Contaduría Pública</a>
      </div>
    </div>
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