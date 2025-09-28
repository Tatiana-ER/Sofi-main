<?php
include("connection.php");

$cedula = '';
$nombreCompleto = '';

if (isset($_GET['cedula'])) {
    $cedula = $_GET['cedula'];

    $conn = new connection();
    $pdo = $conn->connect();

    $stmt = $pdo->prepare("SELECT nombre, valorTotal FROM facturac WHERE identificacion = ? LIMIT 1");
    $stmt->execute([$cedula]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'nombre' => $resultado['nombre'],
            'valorTotal' => $resultado['valorTotal'],
            'valorAnticipos' => 0 
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
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
        <h2>CUANTO DEBO</h2>
      </div>

      <form action="generar_pdf.php" method="POST" target="_blank">
        <div class="mb-3">
            <label for="cedula" class="form-label">Identificación proveedor</label>
            <input type="text" class="form-control" name="cedula" id="cedula" value="<?php echo htmlspecialchars($cedula); ?>" oninput="consultarNombre()">
        </div>

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre proveedor</label>
            <input type="text" class="form-control" name="nombre" id="nombre" readonly>
        </div>

        <div class="mb-3">
            <label for="fecha" class="form-label">Fecha de corte</label>
            <input type="date" class="form-control" name="fecha" id="fecha">
        </div>

        <div class="section-subtitle">
          <br><br><br>
          <h6>INFORME</h6>
        </div>  

        <div>
          <table id="informe">
            <tr>
                <th>Identificación</th>
                <th>Nombre del cliente</th>
                <th>Total Cartera</th>
                <th>Valor Anticipos</th>
                <th>Saldo por pagar</th>
            </tr>
            <tr>
                <td><input type="text" name="identificacion" readonly></td>
                <td><input type="text" name="nombreCliente" readonly></td>
                <td><input type="text" name="totalCartera"></td>
                <td><input type="text" name="valorAnticipos"></td>
                <td><input type="text" name="saldoPagar"></td>
            </tr>
            <tr>
                <th colspan="2">TOTAL</th>
                <td><input type="text" name="total_9"></td>
                <td><input type="text" name="total_10"></td>
                <td><input type="text" name="total_11"></td>
            </tr>
          </table>
          <button type="button" class="add-row-btn" onclick="addRow()">+ Añadir fila</button>
          <br><br><br>
          <button type="submit" class="btn btn-primary" onclick="prepararPDF()">Descargar PDF</button>
        </form>
      </div>


      <script>
        function addRow() {
            // Obtener la tabla
            var table = document.getElementById("informe");

            // Crear una nueva fila
            var row = table.insertRow(table.rows.length - 1);  // Inserta antes de la última fila "TOTAL"

            // Añadir celdas a la fila
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);
            var cell4 = row.insertCell(3);
            var cell5 = row.insertCell(4);

            // Agregar los inputs a las celdas
            cell1.innerHTML = '<input type="text" name="identificacion">';
            cell2.innerHTML = '<input type="text" name="nombreCliente">';
            cell3.innerHTML = '<input type="text" name="totalCartera">';
            cell4.innerHTML = '<input type="text" name="valorAnticipos">';
            cell5.innerHTML = '<input type="text" name="saldoPagar">';
        }
        function consultarNombre() {
            const cedula = document.getElementById('cedula').value;

            if (cedula.length > 0) {
                fetch('?cedula=' + cedula)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('nombre').value = data.nombre;
                            // llenar la tabla
                            document.getElementsByName('identificacion')[0].value = cedula;
                            document.getElementsByName('nombreCliente')[0].value = data.nombre;
                            document.getElementsByName('totalCartera')[0].value = data.valorTotal;
                            document.getElementsByName('saldoPagar')[0].value = data.valorTotal;
                            document.getElementsByName('valorAnticipos')[0].value = data.valorAnticipos;
                        } else {
                            document.getElementById('nombre').value = '';
                            document.getElementsByName('identificacion')[0].value = '';
                            document.getElementsByName('nombreCliente')[0].value = '';
                            document.getElementsByName('totalCartera')[0].value = '';
                            document.getElementsByName('saldoPagar')[0].value = '';
                        }
                    });
            }
        }
        function prepararPDF() {
            document.getElementById('pdf_cedula').value = document.getElementById('cedula').value;
            document.getElementById('pdf_nombre').value = document.getElementById('nombre').value;
            document.getElementById('pdf_fecha').value = document.getElementById('fecha').value;
            document.getElementById('pdf_identificacion').value = document.getElementsByName('identificacion')[0].value;
            document.getElementById('pdf_nombreCliente').value = document.getElementsByName('nombreCliente')[0].value;
            document.getElementById('pdf_totalCartera').value = document.getElementsByName('totalCartera')[0].value;
            document.getElementById('pdf_valorAnticipos').value = document.getElementsByName('valorAnticipos')[0].value;
            document.getElementById('pdf_saldoPagar').value = document.getElementsByName('saldoPagar')[0].value;
        }
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