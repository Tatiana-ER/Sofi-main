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

  <link href="assets/css/improved-style.css" rel="stylesheet">

  <!--Estilos del nuevo logo, incluido en el nav por la palabra SOFI, creado por Tatiana y Catalina -->
  <style>
    .logo {
        font-size: 20px;
        font-weight: bold;
      }

      .logo a {
        text-decoration: none;
        color: inherit; /* conserva el color del texto */
        display: flex;
        align-items: center;
        gap: 5px; /* espacio entre el texto y la imagen */
      }

      .logo-icon {
        width: 75px;   /* tamaño del logo */
        height: auto;
      }

  </style>

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">

    <!--Nuevo diseño del nav con el logo, creado por Tatiana y Catalina -->
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
      <div class="container" data-aos="fade-up">
        <div class="row">
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="icon-box">
              <i class="bi bi-card-checklist"></i>
              <h4><a href="menucatalogos.php">CATÁLOGOS</a></h4>
              <p> Registro sistemático y organizado de elementos contables como cuentas, terceros, inventarios, medios de pago e impuestos.Sirve como una base de datos que permite consultar, crear y modificar la información de manera eficiente.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="icon-box">
              <i class="bi bi-bar-chart"></i>
              <h4><a href="menudocumentos.php">DOCUMENTOS</a></h4>
              <p>Soportes contables como facturas de venta y compra, recibos de caja, y comprobantes de egreso que sirven como evidencia de las transacciones económicas y permiten generar los registros contables necesarios para elaborar los estados financieros.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="icon-box">
              <i class="bi bi-binoculars"></i>
              <h4><a href="menuinformes.php">INFORMES</a></h4>
              <p>Documentos que presentan de manera estructurada y concisa la información recopilada y procesada sobre clientes, proveedores, inventarios y otros elementos relevantes para la empresa. Permiten analizar datos, realizar consultas específicas y validar información.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="400">
            <div class="icon-box">
              <i class="bi bi-briefcase"></i>
              <h4><a href="menulibros.php">LIBROS</a></h4>
              <p>Registros ordenados y sistemáticos que documentan todas las operaciones económicas de una empresa, sintetizados para facilitar la toma de decisiones. Incluyen estados financieros como el balance general, el estado de resultados, el balance de comprobación y libros auxiliares que detallan las transacciones individuales.</p>
            </div>
          </div>
        </div>

      </div>
    </section><!-- End Services Section -->

 <!-- Footer Minimalista -->
<footer id="footer" class="footer-minimalista">
  <p>Universidad de Santander - Ingeniería de Sotfware</p>
  <p>Todos los derechos reservados © 2025</p>
  <p>Creado por iniciativa del programa de Contaduría Pública</p>
</footer>
<!-- End Footer -->

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