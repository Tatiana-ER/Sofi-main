<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

if (isset($_POST['codigo'])) {
    $codigo = $_POST['codigo'];

    $stmt = $pdo->prepare("SELECT descripcionProducto, cantidad FROM productoinventarios WHERE codigoProducto = ?");
    $stmt->execute([$codigo]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($producto) {
        echo json_encode([
            'existe' => true,
            'descripcion' => $producto['descripcionProducto'],
            'cantidad' => (float)$producto['cantidad'] // Forzamos a número decimal
        ]);
    } else {
        echo json_encode([
            'existe' => false,
            'descripcion' => '',
            'cantidad' => 0
        ]);
    }
    exit; // Detener el script después de devolver JSON
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
      <form action="generarPdfExistencias.php" method="POST" target="_blank">
        <div class="section-title">
          <h2>EXISTENCIAS</h2>
        </div>

        <div class="mb-3">
          <label for="codigoBuscar" class="form-label">Código de producto</label>
          <input type="text" class="form-control" name="codigoBuscar" id="codigoBuscar" onkeyup="buscarProducto()">
        </div>

        <div class="mb-3">
          <label for="nombreProducto" class="form-label">Nombre producto</label>
          <input type="text" class="form-control" id="nombreProducto" readonly>
        </div>

        <div class="mb-3">
          <label for="campo" class="form-label">Fecha de corte</label>
          <input type="date" class="form-control" name="campo" id="campo" placeholder="Desde"><br>
          <input type="date" class="form-control" name="campo" id="campo" placeholder="Hasta">
        </div>

        <div class="section-subtitle">
          <h6>INFORME</h6>
        </div>  

        <div>
          <table id="informe">
            <tr>
                <th>Código de producto</th>
                <th>Nombre producto</th>
                <th>Saldo cantidades</th>
            </tr>
            <tr>
                <td><input type="text" id="codigo" name="codigo" readonly></td>
                <td><input type="text" id="nombre" name="nombre" readonly></td>
                <td><input type="text" id="saldo" name="saldo "readonly></td>
            </tr>
            <tr>
                <th colspan="2">TOTAL</th>
                <td><input type="text" name="total"></td> 
            </tr>
          </table>
          <button type="button" class="add-row-btn" onclick="addRow()">+ Añadir fila</button>
          <br><br>
          <button type="submit" class="btn btn-primary">Descargar PDF</button>
        </div>
      </form> 


      <script>
        // Script para crear nueva fila en la tabla
        function addRow() {
            // Obtener la tabla
            var table = document.getElementById("informe");

            // Crear una nueva fila
            var row = table.insertRow(table.rows.length - 1);  // Inserta antes de la última fila "TOTAL"

            // Añadir celdas a la fila
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);

            // Agregar los inputs a las celdas
            cell1.innerHTML = '<input type="text" name="codigo">';
            cell2.innerHTML = '<input type="text" name="nombre">';
            cell3.innerHTML = '<input type="text" name="saldo">';

        }

        // Script para hacer consulta de existencias con el codigo del producto
        function buscarProducto() {
          let codigo = document.getElementById('codigoBuscar').value;

          if (codigo.trim() === "") {
              document.getElementById('nombreProducto').value = "";
              document.getElementById('codigo').value = "";
              document.getElementById('nombre').value = "";
              document.getElementById('saldo').value = "";
              document.querySelector('input[name="total"]').value = "";
              return;
          }

          let xhr = new XMLHttpRequest();
          xhr.open('POST', '', true); // Llama al mismo archivo
          xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

          xhr.onload = function () {
              if (this.status == 200) {
                  let datos = JSON.parse(this.responseText);
                  let totalField = document.querySelector('input[name="total"]');

                  if (datos.existe) {
                      document.getElementById('nombreProducto').value = datos.descripcion;
                      document.getElementById('codigo').value = codigo;
                      document.getElementById('nombre').value = datos.descripcion;
                      document.getElementById('saldo').value = datos.cantidad;
                      totalField.value = datos.cantidad;
                  } else {
                      document.getElementById('nombreProducto').value = "No encontrado";
                      document.getElementById('codigo').value = codigo;
                      document.getElementById('nombre').value = "Producto desconocido";
                      document.getElementById('saldo').value = 0;
                      totalField.value = 0;
                  }
              }
          }

          xhr.send('codigo=' + encodeURIComponent(codigo));
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