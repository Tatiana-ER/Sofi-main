<?php
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

// Obtener consecutivo automático por AJAX
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(consecutivo) AS ultimo FROM doccomprobantecontable ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nuevoConsecutivo = ($row['ultimo'] ?? 0) + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

// Registrar comprobante contable
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'btnAgregar') {
    $fecha = $_POST['fecha'];
    $consecutivo = $_POST['consecutivo'];
    $observaciones = $_POST['observaciones'];

    // Guardar en comprobantecontable
    $stmt = $pdo->prepare("INSERT INTO doccomprobantecontable  (fecha, consecutivo, observaciones) VALUES (?, ?, ?)");
    $stmt->execute([$fecha, $consecutivo, $observaciones]);
    $comprobante_id = $pdo->lastInsertId();

    // Guardar detalles (asumiendo que llegan en arrays)
    if (isset($_POST['cuentaContable'])) {
        $cuentas = $_POST['cuentaContable'];
        $descripciones = $_POST['descripcionCuenta'];
        $terceros = $_POST['tercero'];
        $detalles = $_POST['detalle'];
        $debitos = $_POST['valorDebito'];
        $creditos = $_POST['valorCredito'];

        $stmt = $pdo->prepare("INSERT INTO detallecomprobantecontable 
            (comprobante_id, cuentaContable, descripcionCuenta, tercero, detalle, valorDebito, valorCredito) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        for ($i = 0; $i < count($cuentas); $i++) {
            $stmt->execute([
                $comprobante_id,
                $cuentas[$i],
                $descripciones[$i],
                $terceros[$i],
                $detalles[$i],
                $debitos[$i],
                $creditos[$i]
            ]);
        }
    }

    echo "<script>alert('Comprobante registrado correctamente'); window.location.href='tu_pagina_de_listado.php';</script>";
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
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      font-weight: bold;
      display: inline-block;
      width: 150px;
    }
    .totals {
      margin-top: 20px;
      text-align: right;
    }
    .totals label {
      font-weight: bold;
    }
    .totals input {
      width: 160px;
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
            <button class="btn-ir" onclick="window.location.href='menudocumentos.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>COMPROBANTE CONTABLE</h2>
          <p>Para crear un nuevo comprobante contable diligencie los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>
        
        <form method="POST" action="">
          <div class="mb-3">
            <label for="fecha" class="form-label">Fecha de documento</label>
            <input type="date" name="fecha" class="form-control" id="fecha" placeholder="" required>
          </div>
          <div class="mb-3">
            <label for="consecutivo" class="form-label">Consecutivo</label>
            <input type="text" name="consecutivo" class="form-control" id="consecutivo" placeholder="">
          </div>
      
          <div>
            <table>
              <thead>
                <tr>
                  <th>Cuenta contable</th>
                  <th>Descripción cuenta</th>
                  <th>Tercero</th>
                  <th>Detalle</th>
                  <th>Valor Debito</th>
                  <th>Valor Credito</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="product-table">
                <tr>
                  <td><input type="text" placeholder="Cuenta contable"></td>
                  <td><input type="text" placeholder="Descripción cuenta contable"></td>
                  <td><input type="text" placeholder="Tercero"></td>
                  <td><input type="text" placeholder="Detalle"></td>
                  <td><input type="number" class="quantity" placeholder="Valor Debito"></td>
                  <td><input type="number" class="quantity" placeholder="Valor Credito"></td>
                  <td><button class="add-row" onclick="addRow()">+</button></td>
                </tr>
              </tbody>
            </table>
          </div><br>

          <div class="totals">
            <div class="form-group">
                <label for="valor-total">SUMA TOTAL</label>
                <input type="text" id="valor-total" class="valor-total" placeholder="Suma Debito" readonly>
                <input type="text" id="valor-total" class="valor-total" placeholder="Suma credito"readonly>
            </div>
          </div>

          <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones</label>
            <input type="text" name="observaciones" class="form-control" id="observaciones" placeholder="">
          </div>

          <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Agregar</button>
        </form>

        <script>
        window.addEventListener('DOMContentLoaded', function() {
            fetch(window.location.pathname + "?get_consecutivo=1")
                .then(response => response.json())
                .then(data => {
                    document.getElementById('consecutivo').value = data.consecutivo;
                })
                .catch(error => console.error('Error al obtener consecutivo:', error));
        });
        function addRow() {
          // Obtiene el cuerpo de la tabla
          const tableBody = document.getElementById("product-table");
      
          // Obtiene la última fila de la tabla para duplicarla
          const lastRow = tableBody.lastElementChild;
      
          // Clona la última fila
          const newRow = lastRow.cloneNode(true);
      
          // Limpia los valores de entrada en la nueva fila
          const inputs = newRow.getElementsByTagName("input");
          for (let input of inputs) {
            input.value = "";
          }
      
          // Añade la nueva fila al final del cuerpo de la tabla
          tableBody.appendChild(newRow);
        }
        </script>
        <br>
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