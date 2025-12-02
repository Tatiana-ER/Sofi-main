<!DOCTYPE html>
<html lang="es">

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

  <!-- Template Main CSS File -->
  <link href="assets/css/improved-style.css" rel="stylesheet">
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Estilos del nuevo logo -->
  <style>
    .logo {
      font-size: 20px;
      font-weight: bold;
    }

    .logo a {
      text-decoration: none;
      color: inherit;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logo-icon {
      width: 70px;
      height: auto;
    }

    /* ===== Footer ===== */
    #footer {
      background: #1c0e0e40; /* azul oscuro institucional */
      color: #000000ff;
      text-align: center;
    }

    #footer a {
      color: #000000ff;
      text-decoration: none;
    }

    #footer a:hover {
      color: #000000ff;
      text-decoration: underline;
    }

    #footer .copyright {
      font-weight: 500;
      margin-bottom: 5px;
    }
  </style>

</head>

<body>
<!-- Mostrar mensajes de error si existen -->
<?php if (isset($_GET['error'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php
    $error = $_GET['error'];
    $mensaje = '';
    $titulo = 'Error';
    
    switch($error) {
        case 'campos_vacios':
            $mensaje = 'Por favor, completa todos los campos.';
            break;
        case 'credenciales_invalidas':
            $mensaje = 'Usuario o contraseña incorrectos.';
            break;
        case 'error_servidor':
            $mensaje = 'Error en el servidor. Intenta nuevamente.';
            break;
        default:
            $mensaje = 'Ha ocurrido un error desconocido.';
    }
    ?>
    
    Swal.fire({
        icon: 'error',
        title: '<?php echo $titulo; ?>',
        text: '<?php echo $mensaje; ?>',
        confirmButtonColor: '#d33'
    }).then(() => {
        // Limpiar el parámetro de error de la URL
        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url);
        }
    });
});
</script>
<?php endif; ?>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">

      <!-- Nuevo logo -->
      <h1 class="logo">
        <a href="index.php">
          <img src="./Img/sofilogo5pequeño.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>

      <!-- Navbar -->
      <nav id="navbar" class="navbar">
        <ul>
          <li>
            <a class="nav-link scrollto active" href="login.php" style="color: darkblue;">Iniciar Sesión</a>
          </li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->

    </div>
  </header><!-- End Header -->

  <!-- ======= Hero Section (Carrusel) ======= -->
  <section id="hero" class="d-flex justify-content-center align-items-center">
    <div id="heroCarousel" data-bs-interval="5000" class="container carousel carousel-fade" data-bs-ride="carousel">

      <!-- Slide 1 -->
      <div class="carousel-item active">
        <div class="carousel-container text-center">
          <h2 class="animate__animated animate__fadeInDown">
            <span>Universidad de Santander</span> <br> #1
          </h2>
          <p class="animate__animated animate__fadeInUp">
            En capacidad para generar producción científica de excelencia.<br>
            Con mayor producción en colaboración internacional.<br>
            Con publicaciones en revistas científicas más influyentes del mundo.
          </p>
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="carousel-item">
        <div class="carousel-container text-center">
          <h2 class="animate__animated animate__fadeInDown">
            <span>Universidad de Santander</span> <br> #17 en Colombia
          </h2>
          <p class="animate__animated animate__fadeInUp">
            Según el Scimago Institutions Rankings 2021, la Universidad de Santander se sitúa en el puesto 17 entre las mejores universidades de Colombia en investigación.
          </p>
        </div>
      </div>

      <!-- Slide 3 -->
      <div class="carousel-item">
        <div class="carousel-container text-center">
          <h2 class="animate__animated animate__fadeInDown">
            <span>Universidad de Santander</span> <br> En cifras
          </h2>
          <p class="animate__animated animate__fadeInUp">
            110 - Investigadores categorizados por Minciencias (2021) <br>
            179 - Artículos científicos publicados en Scopus (2021) <br>
            26 - Grupos de investigación categorizados (Convocatoria 894-21) <br>
            124 - Semilleros de investigación activos en 2022A
          </p>
        </div>
      </div>

      <!-- Controles del carrusel -->
      <a class="carousel-control-prev" href="#heroCarousel" role="button" data-bs-slide="prev">
        <span class="carousel-control-prev-icon bx bx-chevron-left" aria-hidden="true"></span>
      </a>

      <a class="carousel-control-next" href="#heroCarousel" role="button" data-bs-slide="next">
        <span class="carousel-control-next-icon bx bx-chevron-right" aria-hidden="true"></span>
      </a>
    </div>
  </section><!-- End Hero -->

  <!-- ======= Footer Minimalista ======= -->
  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer>

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