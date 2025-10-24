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
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid black;
        padding: 10px;
        text-align: center;
    }
    th {
        background-color: #f2f2f2;
    }
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
      <button class="btn-ir" onclick="window.location.href='informesinventarios.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
    <div class="container" data-aos="fade-up">

      <div class="section-title">
        <h2>MOVIMIENTO DE INVENTARIOS</h2>
      </div>

      <div class="mb-2">
        <form action="" method="post">
          <label for="campo" class="form-label">Categoría de inventario</label>
          <input type="text" class="form-control" name="campo" id="campo">
        </form>
      </div>

      <div class="mb-2">
        <form action="" method="post">
          <label for="campo" class="form-label">Producto</label>
          <input type="text" class="form-control" name="campo" id="campo">
        </form>
      </div>

      <div class="mb-2">
        <form action="" method="post">
          <label for="campo" class="form-label">Fecha de elaboración</label>
          <input type="text" class="form-control" name="campo" id="campo">
        </form>
      </div>

      <div class="mb-2">
        <form action="" method="post">
          <label for="campo" class="form-label">Tipo</label>
          <input type="text" class="form-control" name="campo" id="campo">
        </form>
      </div>

      <div class="section-subtitle">
        <h6>INFORME</h6>
      </div>  

      <div>
        <table id="informe-table">
          <tr>
              <th>Código de producto</th>
              <th>Nombre del producto</th>
              <th>Comprobante</th>
              <th>Fecha de elaboración</th>
              <th>Cantidad inicial</th>
              <th>Cantidad entrada</th>
              <th>Cantidad salida</th>
              <th>Saldo</th>
          </tr>
          <tr>
              <td><input type="text" name="codigo"></td>
              <td><input type="text" name="nombre"></td>
              <td><input type="text" name="comprobante"></td>
              <td><input type="text" name="fecha"></td>
              <td><input type="text" name="cantidad_inicial"></td>
              <td><input type="text" name="cantidad_entrada"></td>
              <td><input type="text" name="cantidad_salida"></td>
              <td><input type="text" name="saldo"></td>
          </tr>
          <tr>
              <th colspan="4">TOTAL</th>
              <td><input type="text" name="total_9"></td> 
              <td><input type="text" name="total_10"></td> 
              <td><input type="text" name="total_11"></td> 
              <td><input type="text" name="total_12"></td> 
          </tr>
        </table>
        <button type="button" class="add-row-btn" onclick="addRow()">+ Añadir fila</button>
      </div>


      <script>
        function addRow() {
            // Obtener la tabla
            var table = document.getElementById("informe-table");

            // Crear una nueva fila
            var row = table.insertRow(table.rows.length - 1);  // Inserta antes de la última fila "TOTAL"

            // Añadir celdas a la fila
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);
            var cell4 = row.insertCell(3);
            var cell5 = row.insertCell(4);
            var cell6= row.insertCell(5);
            var cell7 = row.insertCell(6);
            var cell8 = row.insertCell(7);

            // Agregar los inputs a las celdas
            cell1.innerHTML = '<input type="text" name="codigo">';
            cell2.innerHTML = '<input type="text" name="nombre">';
            cell3.innerHTML = '<input type="text" name="comprobante">';
            cell4.innerHTML = '<input type="text" name="fecha">';
            cell5.innerHTML = '<input type="text" name="cantidad_inicial">';
            cell6.innerHTML = '<input type="text" name="cantidad_entrada">';
            cell7.innerHTML = '<input type="text" name="cantidad_final">';
            cell8.innerHTML = '<input type="text" name="saldo">';

        }
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

</body>

</html>