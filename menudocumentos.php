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
            <button class="btn-ir" onclick="window.location.href='dashboard.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>DOCUMENTOS</h2>
          <p>A continuación puede ingresar a los documentos configurados para su usuario en el sistema.</p>
        </div>

        <div class="row">
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="icon-box">
              <i class="bi bi-card-checklist"></i>
              <h4><a href="documentosfacturadeventa.php">FACTURA DE VENTA</a></h4>
              <p>Documento en el que se registra y respalda una transacción de venta de bienes o servicios. Es obligatorio y tiene valor legal, cumpliendo la función de soportar la transacción para fines contables, tributarios y legales, tanto para la empresa como para el Estado.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="icon-box">
              <i class="bi bi-bar-chart"></i>
              <h4><a href="documentosfacturadecompra.php">FACTURA DE COMPRA</a></h4>
              <p>Documento que se recibe de un comprador como comprobante de la adquisición del bien o servicio. Este documento lo emite el vendedor, pero para el comprador tiene un valor contable importante, pues este permite registrar el gasto o costo en la contabilidad, justificando la adquisición de producto o servicio y, en algunos casos, aplicar deducciones fiscales.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="icon-box">
              <i class="bi bi-binoculars"></i>
              <h4><a href="documentosrecibodecaja.php">RECIBO DE CAJA</a></h4>
              <p>Es el documento en el que se registra la recepción de dinero por parte de la empresa. Este recibo se emite por quien recibe el dinero y se entrega al pagador como comprobante del pago realizado.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="400">
            <div class="icon-box">
              <i class="bi bi-brightness-high"></i>
              <h4><a href="documentoscomprobantedeegreso.php">COMPROBANTE DE EGRESO</a></h4>
              <p>Documento contable utilizado para registrar la salida de dinero la empresa. Este comprobante respalda la transacción y permite llevar un control adecuado de los gastos realizados.</p>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="500">
            <div class="icon-box">
              <i class="bi bi-calendar4-week"></i>
              <h4><a href="documentoscomprobantecontable.php">COMPROBANTE CONTABLE</a></h4>
              <p>Documento que se utiliza en la contabilidad para registrar y respaldar cualquier transacción financiera dentro de la empresa. Su objetivo es dejar constancia formal y detallada de las operaciones realizadas, ya sea ingresos, egresos, ajustes, transferencias u otros movimientos que afecten las cuentas de la empresa.</p>
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