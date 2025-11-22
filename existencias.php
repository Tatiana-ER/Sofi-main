<?php
/**
 * =================================================================
 * SECCIÓN 1: LÓGICA DE SERVIDOR (PHP)
 *
 * Esta parte maneja la conexión a la base de datos y procesa
 * las peticiones AJAX para la búsqueda de productos.
 * =================================================================
 */
include("connection.php");

// 1. CONEXIÓN A LA BASE DE DATOS
$conn = new connection();
$pdo = $conn->connect();

// 2. MANEJO DE PETICIONES AJAX
// Detecta si la petición es una llamada AJAX por la variable 'action'.
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Configura el encabezado para indicar que la respuesta es JSON
    header('Content-Type: application/json');

    switch ($action) {
        case 'buscarCodigo':
            // 2.1 Búsqueda por código
            if (isset($_POST['codigo'])) {
                $codigo = $_POST['codigo'];

                $stmt = $pdo->prepare("SELECT descripcionProducto, cantidad FROM productoinventarios WHERE codigoProducto = ?");
                $stmt->execute([$codigo]);
                $producto = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($producto) {
                    echo json_encode([
                        'existe' => true,
                        'codigo' => $codigo,
                        'descripcion' => $producto['descripcionProducto'],
                        // Se sigue enviando la cantidad como float para que JS la procese
                        'cantidad' => (float)$producto['cantidad']
                    ]);
                } else {
                    echo json_encode(['existe' => false]);
                }
            }
            break;

        case 'buscarNombre':
            // 2.2 Búsqueda por nombre (autocompletado)
            if (isset($_POST['buscarNombre'])) {
                $nombre = $_POST['buscarNombre'];

                $stmt = $pdo->prepare("SELECT codigoProducto, descripcionProducto, cantidad FROM productoinventarios WHERE descripcionProducto LIKE ? LIMIT 10");
                $stmt->execute(['%' . $nombre . '%']);
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode($productos);
            }
            break;
    }
    // IMPORTANTE: Detener la ejecución si fue una petición AJAX
    exit;
}

/**
 * =================================================================
 * SECCIÓN 2: RENDERIZADO HTML
 *
 * El código a continuación se ejecuta SÓLO si se carga la página
 * directamente en el navegador.
 * =================================================================
 */
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SOFI - Existencias</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Asegúrate de incluir tu estilo principal -->
  <link href="assets/css/improved-style.css" rel="stylesheet">

  <!-- ================================== -->
  <!-- SECCIÓN 3: ESTILOS CSS PERSONALIZADOS -->
  <!-- ================================== -->
  <style>
    /* Estilos base */
    input[type="text"], input[type="date"], input[type="number"] {
      width: 100%;
      box-sizing: border-box;
      padding: 8px 10px;
      border: 1px solid #ced4da;
      border-radius: 4px;
    }
    .form-group { margin-bottom: 15px; }

    /* Estilos de la tabla */
    .table-container { margin-top: 20px; }
    .table-container table {
      width: 100%;
      border-collapse: collapse;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .table-container th, .table-container td {
      padding: 12px 8px;
      border: 1px solid #e9ecef;
      text-align: center;
      vertical-align: middle;
    }
    .table-container th {
      background-color: #0d6efd;
      color: white;
      font-weight: 600;
    }
    /* Estilo para los inputs dentro de la tabla */
    #tableBody input {
        border: none; 
        background: transparent; 
        text-align: center;
        padding: 0;
    }
    #tableBody td:nth-child(3) input { /* Tercera columna para cantidad */
        /* Alineamos a la derecha para que se vea como número */
        text-align: right; 
    }

    /* Estilos de Sugerencias (Autocompletado) */
    .suggestions-box {
      position: absolute;
      background: #ffffff;
      border: 1px solid #0d6efd;
      border-top: none;
      max-height: 250px;
      overflow-y: auto;
      width: calc(100% - 2px); 
      z-index: 1000;
      box-shadow: 0 6px 10px rgba(0,0,0,0.15);
      padding: 0;
      margin: 0;
    }
    .suggestion-item {
      padding: 10px;
      cursor: pointer;
      border-bottom: 1px solid #f1f1f1;
      transition: background-color 0.2s;
    }
    .suggestion-item:last-child { border-bottom: none; }
    .suggestion-item:hover { background: #e9f5ff; }
    .position-relative { position: relative; }
    
    /* Ajuste específico para el input del total */
    #total { text-align: right !important; }

    /* Botones de exportación */
    .btn-exportar {
      background-color: #17a2b8;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 20px;
      margin-left: 10px;
    }
    .btn-exportar:hover {
      background-color: #138496;
    }
    .btn-pdf {
      background-color: #dc3545;
    }
    .btn-pdf:hover {
      background-color: #c82333;
    }
    .btn-excel {
      background-color: #28a745;
    }
    .btn-excel:hover {
      background-color: #218838;
    }

  </style>
</head>

<body class="p-4">

  <!-- Header (Menú de Navegación) -->
  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="dashboard.php">
          <img src="./Img/sofilogo5pequeño.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto active" href="dashboard.php" style="color: darkblue;">Inicio</a></li>
          <li><a class="nav-link scrollto active" href="perfil.php" style="color: darkblue;">Mi Negocio</a></li>
          <li><a class="nav-link scrollto active" href="index.php" style="color: darkblue;">Cerrar Sesión</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>

  <div class="container" style="margin-top: 80px;">

    <!-- Botón de Regresar -->
    <button class="btn-ir" onclick="window.location.href='informesinventarios.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">

      <div class="section-title">
        <h2>EXISTENCIAS</h2>
        <p>Consulte el inventario disponible de productos.</p>
      </div>

      <!-- FORMULARIO PRINCIPAL: Envía todos los datos, incluyendo la tabla, a generarPdfExistencias.php -->
      <form id="formPdf" action="generarPdfExistencias.php" method="POST" target="_blank" style="display: none;">
          <input type="hidden" id="datosPdf" name="datos">
      </form>

      <form id="formExcel" action="generarExcelExistencias.php" method="POST" target="_blank" style="display: none;">
          <input type="hidden" id="datosExcel" name="datos">
      </form>

      <!-- Buscadores -->
      <div class="row g-3">
        <div class="col-md-4">
          <label class="fw-bold">Código de Producto</label>
          <input type="text" class="form-control" id="codigoBuscar" placeholder="Ej: PROD001">
        </div>

        <div class="col-md-8 position-relative">
          <label class="fw-bold">Nombre del Producto</label>
          <input type="text" class="form-control" id="nombreBuscar" placeholder="Escriba para buscar..." autocomplete="off">
          <div id="suggestions" class="suggestions-box" style="display:none;"></div>
        </div>
      </div>

      <div class="row g-3 mt-2">
        <div class="col-md-12">
          <label class="fw-bold">Producto Seleccionado</label>
          <input type="text" class="form-control" id="nombreProducto" readonly placeholder="Seleccione un producto...">
        </div>
      </div>

      <!-- Fechas (Campos de Filtro) -->
      <div class="row g-3 mt-3">
        <div class="col-md-6">
          <label class="fw-bold">Fecha Desde</label>
          <input type="date" class="form-control" id="fechaDesde">
        </div>
        <div class="col-md-6">
          <label class="fw-bold">Fecha Hasta</label>
          <input type="date" class="form-control" id="fechaHasta">
        </div>
      </div>

      <!-- Tabla de Resultados -->
      <div class="table-container mt-5">
        <table id="informe">
          <thead>
            <tr>
              <th>Código de Producto</th>
              <th>Nombre Producto</th>
              <th>Saldo Cantidades</th>
            </tr>
          </thead>
          <!-- La tablaBody DEBE estar dentro del form para enviar sus inputs -->
          <tbody id="tableBody"></tbody>
        </table>
      </div>

      <!-- Total de Cantidades -->
      <div class="mt-3 text-end d-flex justify-content-end align-items-center">
        <label class="fw-bold me-2">Total de Cantidades:</label>
        <!-- Se deja como number para consistencia, y se envía como 'total' -->
        <input type="number" id="total" name="total" readonly class="form-control" style="width:150px;text-align:right;">
      </div>

      <!-- Botones de Acción -->
      <div class="mt-4 text-center">
        <button type="button" class="btn-exportar btn-pdf" onclick="exportarPDF()">
          <i class="fas fa-file-pdf"></i> Descargar PDF
        </button>
        <button type="button" class="btn-exportar btn-excel" onclick="exportarExcel()">
          <i class="fas fa-file-excel"></i> Descargar Excel
        </button>
        <br><br>
        <br>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer>

  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <script src="assets/js/main.js"></script>

  <!-- ================================== -->
  <!-- SECCIÓN 4: LÓGICA DEL CLIENTE (JAVASCRIPT) -->
  <!-- MODIFICADO: FORMATO DE CANTIDADES A ENTEROS -->
  <!-- ================================== -->
  <script>
    // NOTA: La variable 'action' se enviará en las peticiones AJAX para que el PHP sepa qué hacer.
    const API_URL = ""; // Dejar vacío o poner el nombre del archivo actual si el PHP está arriba
    const inputCodigo = document.getElementById("codigoBuscar");
    const inputNombreBuscar = document.getElementById("nombreBuscar");
    const inputNombreProducto = document.getElementById("nombreProducto");
    const suggestionsBox = document.getElementById("suggestions");
    let timeoutCodigo = null,
      timeoutNombre = null;

    // 🔹 Buscar por código con retardo
    inputCodigo.addEventListener("input", function() {
      clearTimeout(timeoutCodigo);
      const codigo = this.value.trim();
      if (codigo === "") return;

      timeoutCodigo = setTimeout(() => {
        buscarPorCodigo(codigo);
      }, 500);
    });

    function buscarPorCodigo(codigo) {
      let xhr = new XMLHttpRequest();
      // Apunta a este mismo archivo y añade 'action=buscarCodigo'
      xhr.open("POST", API_URL, true); 
      xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhr.onload = function() {
        if (this.status == 200) {
          try {
            let datos = JSON.parse(this.responseText);
            if (datos.existe) {
              inputNombreBuscar.value = datos.descripcion;
              inputNombreProducto.value = datos.descripcion;
              agregarProductoTabla(datos.codigo, datos.descripcion, datos.cantidad);
            } else {
              Swal.fire({
                icon: 'warning',
                title: 'No encontrado',
                text: 'No se encontró ningún producto con ese código'
              });
            }
          } catch (e) {
            console.error("Error al parsear JSON para búsqueda por código:", e);
          }
        }
      };
      xhr.send("action=buscarCodigo&codigo=" + encodeURIComponent(codigo));
    }

    // 🔹 Autocompletado por nombre
    inputNombreBuscar.addEventListener("input", function() {
      clearTimeout(timeoutNombre);
      const nombre = this.value.trim();
      if (nombre.length < 2) {
        suggestionsBox.style.display = "none";
        return;
      }
      timeoutNombre = setTimeout(() => {
        let xhr = new XMLHttpRequest();
        // Apunta a este mismo archivo y añade 'action=buscarNombre'
        xhr.open("POST", API_URL, true); 
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
          if (this.status == 200) {
            try {
              let productos = JSON.parse(this.responseText);
              mostrarSugerencias(productos);
            } catch (e) {
              console.error("Error al parsear JSON para autocompletado:", e);
              suggestionsBox.style.display = "none";
            }
          }
        };
        xhr.send("action=buscarNombre&buscarNombre=" + encodeURIComponent(nombre));
      }, 300);
    });

    function mostrarSugerencias(productos) {
      if (productos.length === 0) {
        suggestionsBox.style.display = "none";
        return;
      }
      suggestionsBox.innerHTML = "";
      productos.forEach(p => {
        let div = document.createElement("div");
        div.className = "suggestion-item";
        div.innerHTML = `<strong>${p.codigoProducto}</strong> - ${p.descripcionProducto}`;
        div.onclick = function() {
          inputCodigo.value = p.codigoProducto;
          inputNombreBuscar.value = p.descripcionProducto;
          inputNombreProducto.value = p.descripcionProducto;
          agregarProductoTabla(p.codigoProducto, p.descripcionProducto, p.cantidad);
          suggestionsBox.style.display = "none";
        };
        suggestionsBox.appendChild(div);
      });
      suggestionsBox.style.display = "block";
    }

    document.addEventListener("click", (e) => {
      if (!e.target.closest("#nombreBuscar") && !e.target.closest("#suggestions")) {
        suggestionsBox.style.display = "none";
      }
    });

    // 🔹 Agregar producto a tabla
    function agregarProductoTabla(codigo, nombre, cantidad) {
      let tableBody = document.getElementById("tableBody");

      // Evita duplicados
      for (let row of tableBody.rows) {
        // Busca si el código ya existe en alguna fila
        const existingInput = row.cells[0].querySelector("input");
        if (existingInput && existingInput.value === codigo) {
          Swal.fire({
            icon: 'info',
            title: 'Ya agregado',
            text: 'Este producto ya está en la lista.'
          });
          return;
        }
      }

      // *** CAMBIO CLAVE AQUÍ: REDONDEAR A ENTERO PARA QUITAR DECIMALES ***
      // Math.round() asegura que si la DB envía 30.00 o 30.1, se muestre 30
      const cantidadEntera = Math.round(parseFloat(cantidad)); 

      let row = tableBody.insertRow();
      row.innerHTML = `
        <td><input type="text" name="codigos[]" value="${codigo}" readonly></td>
        <td><input type="text" name="nombres[]" value="${nombre}" readonly></td>
        <td><input type="number" name="saldos[]" value="${cantidadEntera}" readonly></td>
      `;
      // La clave para el PDF es que estos inputs con names[] existen dentro del <form>
      
      calcularTotal();
    }

    // 🔹 Calcular total
    function calcularTotal() {
      let total = 0;
      document.querySelectorAll('input[name="saldos[]"]').forEach(input => {
        // *** CAMBIO CLAVE AQUÍ: USAR parseInt() PARA SUMAR SIN DECIMALES ***
        total += parseInt(input.value) || 0;
      });
      // El total se muestra sin decimales.
      document.getElementById("total").value = total;
    }

    // 🔹 Función para exportar a PDF
    function exportarPDF() {
      const tbody = document.getElementById("tableBody");
      if (tbody.children.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Sin datos',
          text: 'No hay datos para exportar. Agregue productos a la tabla primero.'
        });
        return;
      }

      // Preparar datos para exportación
      const datosExportar = prepararDatosExportacion();
      
      // Enviar datos al formulario PDF
      document.getElementById('datosPdf').value = JSON.stringify(datosExportar);
      document.getElementById('formPdf').submit();

      Swal.fire({
        icon: 'success',
        title: 'PDF Generado',
        text: 'El informe se está generando en formato PDF...',
        timer: 2000,
        showConfirmButton: false
      });
    }

    // 🔹 Función para exportar a Excel
    function exportarExcel() {
      const tbody = document.getElementById("tableBody");
      if (tbody.children.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Sin datos',
          text: 'No hay datos para exportar. Agregue productos a la tabla primero.'
        });
        return;
      }

      // Preparar datos para exportación
      const datosExportar = prepararDatosExportacion();
      
      // Enviar datos al formulario Excel
      document.getElementById('datosExcel').value = JSON.stringify(datosExportar);
      document.getElementById('formExcel').submit();

      Swal.fire({
        icon: 'success',
        title: 'Excel Generado',
        text: 'El informe se está generando en formato Excel...',
        timer: 2000,
        showConfirmButton: false
      });
    }

    // 🔹 Función para preparar datos para exportación
    function prepararDatosExportacion() {
      const datos = {
        productos: [],
        total: parseInt(document.getElementById('total').value) || 0,
        fechaDesde: document.getElementById('fechaDesde').value,
        fechaHasta: document.getElementById('fechaHasta').value,
        fechaGeneracion: new Date().toLocaleDateString('es-ES'),
        titulo: 'Informe de Existencias de Inventario'
      };

      // Recopilar todos los productos de la tabla
      const filas = document.querySelectorAll('#tableBody tr');
      filas.forEach(fila => {
        const producto = {
          codigo: fila.cells[0].querySelector('input').value,
          nombre: fila.cells[1].querySelector('input').value,
          cantidad: parseInt(fila.cells[2].querySelector('input').value) || 0
        };
        datos.productos.push(producto);
      });

      return datos;
    }
  </script>
</body>

</html>