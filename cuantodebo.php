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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>  

  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style>
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
          <button class="btn-ir" onclick="window.location.href='informesproveedores.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
    <div class="container" data-aos="fade-up">  

      <div class="section-title">
      <h2>CUANTO DEBO</h2>
      </div>

      <form action="generar_pdf.php" method="POST" target="_blank">
      <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="cedula" class="form-label">Identificación proveedor</label>
            <input type="text" class="form-control" name="cedula" id="cedula" value="<?php echo htmlspecialchars($cedula); ?>" oninput="consultarNombre()">
        </div>

         <div class="col-md-4">
            <label for="nombre" class="form-label">Nombre proveedor</label>
            <input type="text" class="form-control" name="nombre" id="nombre" readonly>
        </div>

         <div class="col-md-4">
            <label for="fecha" class="form-label">Fecha de corte</label>
            <input type="date" class="form-control" name="fecha" id="fecha">
        </div>
      </div>
      <br>
        <div class="section-subtitle">
          <h6>INFORME</h6>
        </div>  
        <div class="row">
          <div class="table-container">
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
          </div>
        </div>
          
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