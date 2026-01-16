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
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

    <!-- ======= Services Section ======= -->
    <section id="services" class="services">
            <button class="btn-ir" onclick="window.location.href='dashboard.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>LIBROS</h2>
          <p>A continuación puede ingresar a los libros configurados para su usuario en el sistema.</p>
        </div>

        <div class="row">
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="icon-box">
              <i class="bi bi-card-checklist"></i>
              <h4><a href="librosestadodesituacionfinanciera.php">Estado de situación financiera</a></h4>
              <p>Es el registro que presenta la posición financiera de una empresa en un momento determinado, este informe muestra los activos, pasivos y patrimonio de la empresa, proporcionando una visión clara de su situación financiera y su capacidad para cumplir con sus obligaciones.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="icon-box">
              <i class="bi bi-bar-chart"></i>
              <h4><a href="librosestadoderesultados.php">Estado de resultados</a></h4>
              <p>Este reporte que muestra los ingresos, costos y gastos de una empresa durante un período específico (como un mes, trimestre o año), este informe contable le permite conocer la rentabilidad de la empresa, detallando cuánto ha ganado o perdido en el período evaluado.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="icon-box">
              <i class="bi bi-binoculars"></i>
              <h4><a href="librosbalancedeprueba.php">Balance de prueba</a></h4>
              <p>Es un reporte en el que se muestra un resumen de todas las cuentas del libro mayor de una empresa, indicando sus saldos deudores y acreedores en un período específico con el objetivo de verificar que el total de los débitos sea igual al total de los créditos, asegurando que las cuentas estén equilibradas y que no haya errores en los registros contables.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="400">
            <div class="icon-box">
              <i class="bi bi-brightness-high"></i>
              <h4><a href="libroslibroauxiliar.php">Libro auxiliar</a></h4>
              <p>Es un registro detallado y específico que desglosa y complementa las cuentas del libro mayor, estos se utilizan para llevar un seguimiento más detallado de las transacciones y saldos de cuentas específicas, proporcionando información más precisa sobre cada tipo de cuenta.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="500">
            <div class="icon-box">
              <i class="bi bi-briefcase"></i>
              <h4><a href="libroslibrodeventas.php">Libro de ventas</a></h4>
              <p>Registro detallado de todas las transacciones de ventas realizadas por la empresa en un período determinado. Este libro contiene información sobre las ventas de productos o servicios, incluyendo los detalles de cada operación, los montos involucrados y los impuestos aplicables (como el IVA).</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="600">
            <div class="icon-box">
              <i class="bi bi-briefcase"></i>
              <h4><a href="libroslibrodecompras.php">Libro de compras</a></h4>
              <p>Es el registro detallado de todas las transacciones de compras realizadas la empresa en un período determinado. Este libro tiene como objetivo llevar un control de las adquisiciones de bienes y servicios, registrar los impuestos pagados (como el IVA) y garantizar que las compras estén correctamente reflejadas en los estados financieros de la empresa.</p>
            </div>
          </div>          
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="700">
            <div class="icon-box">
              <i class="bi bi-calendar4-week"></i>
              <h4><a href="librosmovimientodecaja.php">Movimiento de caja</a></h4>
              <p>Es un reporte detallado de todas las transacciones que afectan el efectivo disponible de la empresa. Este libro refleja tanto las entradas (ingresos) como las salidas (egresos) de dinero en efectivo, permitiendo un control preciso sobre el flujo de caja de la empresa en un período específico.</p>
            </div>
          </div>
        </div>

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