<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SOFI - Sistema de Información Financiero</title>
  <meta content="Sistema de información financiero para gestionar documentos, libros contables e informes de tu negocio." name="description">
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

  <!-- Estilos del logo, landing y footer -->
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
      gap: 9px;
    }

    .logo-icon {
      width: 40px;
      height: auto;
    }

    /* ===== Hero de producto ===== */
    #landing-hero {
      padding: 170px 0 100px;
      background: linear-gradient(135deg, #0b1e4d 0%, #123a8f 55%, #2563eb 100%);
      color: #ffffff;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    #landing-hero::after {
      content: "";
      position: absolute;
      inset: 0;
      background: url("assets/img/hero-bg.jpg") no-repeat center center/cover;
      opacity: 0.12;
      z-index: 0;
    }

    #landing-hero .hero-inner {
      position: relative;
      z-index: 1;
      max-width: 780px;
      margin: 0 auto;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.25);
      padding: 8px 18px;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 500;
      margin-bottom: 24px;
    }

    #landing-hero h1 {
      font-family: "Poppins", sans-serif;
      font-size: 2.8rem;
      font-weight: 600;
      line-height: 1.25;
      margin-bottom: 20px;
      text-shadow: 1px 1px 6px rgba(0,0,0,0.25);
    }

    #landing-hero p.hero-subtitle {
      font-size: 1.15rem;
      color: #dbe4ff;
      line-height: 1.7;
      margin-bottom: 36px;
    }

    .hero-actions {
      display: flex;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .btn-hero-primary,
    .btn-hero-secondary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 14px 28px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 1rem;
    }

    .btn-hero-primary {
      background: var(--secondary-color, #f59e0b);
      color: #1f2937;
    }

    .btn-hero-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.25);
      color: #1f2937;
    }

    .btn-hero-secondary {
      background: transparent;
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .btn-hero-secondary:hover {
      background: rgba(255, 255, 255, 0.12);
      color: #ffffff;
      transform: translateY(-3px);
    }

    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 50px;
      margin-top: 55px;
      flex-wrap: wrap;
      position: relative;
      z-index: 1;
    }

    .hero-stats div {
      text-align: center;
    }

    .hero-stats strong {
      display: block;
      font-family: "Poppins", sans-serif;
      font-size: 1.8rem;
      color: #ffffff;
    }

    .hero-stats span {
      font-size: 0.85rem;
      color: #b9c8f5;
    }

    /* ===== Funcionalidades (grid propio, sin Bootstrap .row/.col) ===== */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 28px;
      max-width: 1180px;
      margin: 0 auto;
    }

    .feature-card {
      background: var(--background-white, #ffffff);
      border-radius: var(--border-radius-lg, 16px);
      padding: 38px 32px;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border-color, #e5e7eb);
      transition: all 0.35s ease;
      text-align: left;
      height: 100%;
    }

    .feature-card:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-xl);
      border-color: var(--primary-color);
    }

    .feature-icon {
      width: 58px;
      height: 58px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 22px;
    }

    .feature-icon i {
      font-size: 1.6rem;
      color: #ffffff;
    }

    .feature-card h4 {
      font-family: "Poppins", sans-serif;
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 12px;
    }

    .feature-card p {
      color: var(--text-secondary);
      font-size: 0.95rem;
      line-height: 1.7;
      margin: 0;
    }

    @media (max-width: 991px) {
      .features-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 576px) {
      .features-grid {
        grid-template-columns: 1fr;
      }
    }

    /* ===== Cómo funciona ===== */
    #como-funciona {
      padding: 90px 0;
      background: var(--background-white, #ffffff);
    }

    .pasos-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 30px;
      max-width: 1150px;
      margin: 0 auto;
    }

    .paso-item {
      text-align: center;
      padding: 10px;
      position: relative;
    }

    .paso-numero {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: "Poppins", sans-serif;
      font-weight: 600;
      font-size: 1.2rem;
      margin: 0 auto 18px;
    }

    .paso-item h5 {
      font-family: "Poppins", sans-serif;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-primary);
    }

    .paso-item p {
      color: var(--text-secondary);
      font-size: 0.92rem;
      line-height: 1.6;
    }

    /* ===== CTA final ===== */
    #cta-final {
      padding: 80px 0;
      background: linear-gradient(135deg, #0b1e4d, #2563eb);
      color: #fff;
      text-align: center;
    }

    #cta-final h3 {
      font-family: "Poppins", sans-serif;
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 16px;
    }

    #cta-final p {
      color: #dbe4ff;
      margin-bottom: 30px;
      max-width: 550px;
      margin-left: auto;
      margin-right: auto;
    }

    @media (max-width: 991px) {
      .pasos-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 576px) {
      #landing-hero h1 {
        font-size: 2rem;
      }
      .pasos-grid {
        grid-template-columns: 1fr;
      }
    }

    /* ===== Footer ===== */
    #footer {
      background: linear-gradient(135deg, #0b1e4d, #123a8f);
      color: #e6ecff;
      text-align: center;
      padding: 30px 0;
    }

    #footer p {
      margin-bottom: 6px;
      font-size: 0.9rem;
    }

    #footer a {
      color: #ffffff;
      text-decoration: none;
    }

    #footer a:hover {
      color: #ffffff;
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

      <h1 class="logo">
        <a href="index.php">
          <img src="./Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>

      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto active" href="#funcionalidades" style="color: darkblue;">Funcionalidades</a></li>
          <li><a class="nav-link scrollto active" href="#como-funciona" style="color: darkblue;">Cómo funciona</a></li>
          <li><a class="nav-link scrollto active" href="login.php" style="color: darkblue;">Iniciar Sesión</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->

    </div>
  </header><!-- End Header -->

  <!-- ======= Hero de producto ======= -->
  <section id="landing-hero">
    <div class="container hero-inner" data-aos="fade-up">
      <span class="hero-badge"><i class="bi bi-shield-check"></i> Software contable para tu negocio</span>
      <h1>Lleva la contabilidad de tu negocio sin complicaciones</h1>
      <p class="hero-subtitle">
        SOFI centraliza tus documentos, libros contables e informes financieros
        en un solo sistema, fácil de usar y pensado para pequeños y medianos negocios.
      </p>
      <div class="hero-actions">
        <a href="login.php" class="btn-hero-primary">
          Iniciar sesión <i class="bi bi-arrow-right"></i>
        </a>
        <a href="#funcionalidades" class="btn-hero-secondary">
          Ver qué incluye
        </a>
      </div>

      <div class="hero-stats">
        <div><strong>100%</strong><span>Basado en la web</span></div>
        <div><strong>4</strong><span>Módulos integrados</span></div>
        <div><strong>Roles</strong><span>Control de acceso por usuario</span></div>
      </div>
    </div>
  </section><!-- End Hero -->

  <!-- ======= Funcionalidades ======= -->
  <section id="services" class="services" data-aos="fade-up">
    <div class="container">

      <div class="section-title" id="funcionalidades">
        <h2>Todo lo que necesitas en un solo lugar</h2>
        <p>SOFI reúne los módulos clave para el día a día contable de tu negocio.</p>
      </div>

      <div class="features-grid">

        <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
          <div class="feature-icon"><i class="bi bi-file-earmark-text"></i></div>
          <h4>Documentos</h4>
          <p>Genera facturas de venta y compra, recibos de caja, comprobantes de egreso y comprobantes contables, todo desde un mismo lugar.</p>
        </div>

        <div class="feature-card" data-aos="fade-up" data-aos-delay="150">
          <div class="feature-icon"><i class="bi bi-journal-bookmark"></i></div>
          <h4>Libros contables</h4>
          <p>Consulta libro diario, libro de compras y ventas, movimientos de caja, balance de prueba y estados financieros generados automáticamente.</p>
        </div>

        <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
          <div class="feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <h4>Informes</h4>
          <p>Visualiza el estado de clientes, proveedores e inventarios para tomar mejores decisiones sobre tu negocio.</p>
        </div>

        <div class="feature-card" data-aos="fade-up" data-aos-delay="250">
          <div class="feature-icon"><i class="bi bi-folder2-open"></i></div>
          <h4>Catálogos</h4>
          <p>Administra terceros, cuentas contables, inventarios y medios de pago para mantener tu información siempre organizada.</p>
        </div>

        <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
          <div class="feature-icon"><i class="bi bi-lock"></i></div>
          <h4>Cierre contable</h4>
          <p>Cierra tus periodos contables de forma ordenada, con la seguridad de que la información queda consistente.</p>
        </div>

        <div class="feature-card" data-aos="fade-up" data-aos-delay="350">
          <div class="feature-icon"><i class="bi bi-people"></i></div>
          <h4>Roles y permisos</h4>
          <p>Define qué puede ver y editar cada usuario de tu equipo, con roles personalizados por módulo.</p>
        </div>

      </div>
    </div>
  </section><!-- End Funcionalidades -->

  <!-- ======= Cómo funciona ======= -->
  <section id="como-funciona" data-aos="fade-up">
    <div class="container">
      <div class="section-title">
        <h2>Cómo funciona</h2>
        <p>Empieza a llevar tu contabilidad en pocos pasos.</p>
      </div>

      <div class="pasos-grid">
        <div class="paso-item" data-aos="fade-up" data-aos-delay="100">
          <div class="paso-numero">1</div>
          <h5>Inicia sesión</h5>
          <p>Ingresa con tu usuario y contraseña asignados por tu administrador.</p>
        </div>
        <div class="paso-item" data-aos="fade-up" data-aos-delay="150">
          <div class="paso-numero">2</div>
          <h5>Registra tu información</h5>
          <p>Configura tus catálogos de terceros, cuentas e inventarios.</p>
        </div>
        <div class="paso-item" data-aos="fade-up" data-aos-delay="200">
          <div class="paso-numero">3</div>
          <h5>Genera documentos</h5>
          <p>Crea facturas, recibos y comprobantes desde el módulo de documentos.</p>
        </div>
        <div class="paso-item" data-aos="fade-up" data-aos-delay="250">
          <div class="paso-numero">4</div>
          <h5>Consulta tus reportes</h5>
          <p>Revisa libros e informes actualizados en tiempo real.</p>
        </div>
      </div>
    </div>
  </section><!-- End Cómo funciona -->

  <!-- ======= CTA final ======= -->
  <section id="cta-final" data-aos="fade-up">
    <div class="container">
      <h3>¿Listo para ordenar la contabilidad de tu negocio?</h3>
      <p>Ingresa con tu usuario para empezar a usar SOFI.</p>
      <a href="login.php" class="btn-hero-primary">
        Iniciar sesión <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </section><!-- End CTA -->

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

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      if (typeof AOS !== "undefined") AOS.init();
    });
  </script>

</body>
</html>