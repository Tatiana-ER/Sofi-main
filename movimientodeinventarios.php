<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Obtener categorías para el filtro
$sentenciaCategorias = $pdo->prepare("SELECT id, categoria FROM categoriainventarios ORDER BY categoria ASC");
$sentenciaCategorias->execute();
$categorias = $sentenciaCategorias->fetchAll(PDO::FETCH_ASSOC);

// Inicializar variables de filtro
$categoriaFiltro = isset($_POST['categoriaFiltro']) ? $_POST['categoriaFiltro'] : "";
$productoFiltro = isset($_POST['productoFiltro']) ? $_POST['productoFiltro'] : "";
$fechaDesde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : "";
$fechaHasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : "";
$tipoFiltro = isset($_POST['tipo']) ? $_POST['tipo'] : "";

// Construir consulta con filtros
$sql = "SELECT 
    m.id,
    m.comprobante,
    m.fecha_movimiento,
    m.cantidad_inicial,
    m.cantidad_entrada,
    m.cantidad_salida,
    m.saldo,
    p.codigoProducto,
    p.descripcionProducto,
    p.tipoItem,
    c.categoria
FROM movimientos_inventario m
INNER JOIN productoinventarios p ON m.id_producto = p.id
INNER JOIN categoriainventarios c ON p.categoriaInventarios = c.id
WHERE 1=1";

$params = [];

if (!empty($categoriaFiltro)) {
    $sql .= " AND c.id = :categoria";
    $params[':categoria'] = $categoriaFiltro;
}

if (!empty($productoFiltro)) {
    $sql .= " AND p.id = :producto";
    $params[':producto'] = $productoFiltro;
}

if (!empty($fechaDesde)) {
    $sql .= " AND m.fecha_movimiento >= :fechaDesde";
    $params[':fechaDesde'] = $fechaDesde;
}

if (!empty($fechaHasta)) {
    $sql .= " AND m.fecha_movimiento <= :fechaHasta";
    $params[':fechaHasta'] = $fechaHasta;
}

if (!empty($tipoFiltro)) {
    $sql .= " AND p.tipoItem = :tipo";
    $params[':tipo'] = $tipoFiltro;
}

$sql .= " ORDER BY m.fecha_movimiento DESC, m.id DESC";

$sentenciaMovimientos = $pdo->prepare($sql);
$sentenciaMovimientos->execute($params);
$movimientos = $sentenciaMovimientos->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SOFI - Movimiento de Inventarios</title>
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
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- Estilo principal -->
  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style>
    /* Estilos base consistentes con Existencias */
    input[type="text"], 
    input[type="date"], 
    input[type="number"],
    select.form-control,
    select.form-select {
      width: 100%;
      box-sizing: border-box;
      padding: 8px 10px;
      border: 1px solid #ced4da;
      border-radius: 4px;
    }
    
    .form-group { 
      margin-bottom: 15px; 
    }

    /* Estilos de la tabla - mismo que Existencias */
    .table-container { 
      margin-top: 20px; 
    }
    
    .table-container table {
      width: 100%;
      border-collapse: collapse;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .table-container th, 
    .table-container td {
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

    /* Fila de totales */
    .table-container tfoot th {
      background-color: #6c757d;
    }
    
    .table-container tfoot td {
      background-color: #f8f9fa;
      font-weight: 600;
    }

    /* Sin datos */
    .no-data {
      text-align: center;
      padding: 40px;
      color: #6c757d;
      font-style: italic;
    }

    /* Select2 personalizado */
    .select2-container--default .select2-selection--single {
      border: 1px solid #ced4da;
      border-radius: 4px;
      height: 38px;
      padding: 4px 10px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 28px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 36px;
    }
  </style>
</head>

<body class="p-4">

  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="dashboard.php">
          <img src="./Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
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
        <h2>MOVIMIENTO DE INVENTARIOS</h2>
        <p>Consulte el movimiento de inventario de productos.</p>
      </div>

      <!-- FORMULARIO DE FILTROS -->
      <form action="" method="POST" id="formFiltros">

        <!-- Filtros -->
        <div class="row g-3">
          <div class="col-md-3">
            <label class="fw-bold">Categoría de Inventario</label>
            <select class="form-control" name="categoriaFiltro" id="categoriaFiltro">
              <option value="">Todas las categorías</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo ($categoriaFiltro == $cat['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cat['categoria']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="fw-bold">Producto</label>
            <select class="form-control" name="productoFiltro" id="productoFiltro">
              <option value="">Todos los productos</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="fw-bold">Fecha Desde</label>
            <input type="date" class="form-control" name="fecha_desde" id="fecha_desde" value="<?php echo $fechaDesde; ?>">
          </div>

          <div class="col-md-2">
            <label class="fw-bold">Fecha Hasta</label>
            <input type="date" class="form-control" name="fecha_hasta" id="fecha_hasta" value="<?php echo $fechaHasta; ?>">
          </div>

          <div class="col-md-2">
            <label class="fw-bold">Tipo</label>
            <select class="form-control" name="tipo" id="tipo">
              <option value="">Todos</option>
              <option value="producto" <?php echo ($tipoFiltro == 'producto') ? 'selected' : ''; ?>>Producto</option>
              <option value="servicio" <?php echo ($tipoFiltro == 'servicio') ? 'selected' : ''; ?>>Servicio</option>
            </select>
          </div>
        </div>

        <!-- Botón de Búsqueda -->
        <div class="mt-4 text-center">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Buscar
          </button>
        </div>
      </form>

      <!-- Tabla de Movimientos -->
      <div class="table-container mt-5">
        <table id="informe-table">
          <thead>
            <tr>
              <th>Código de Producto</th>
              <th>Nombre del Producto</th>
              <th>Comprobante</th>
              <th>Fecha de Elaboración</th>
              <th>Cantidad Inicial</th>
              <th>Cantidad Entrada</th>
              <th>Cantidad Salida</th>
              <th>Saldo</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($movimientos) > 0): ?>
              <?php 
              $totalInicial = 0;
              $totalEntrada = 0;
              $totalSalida = 0;
              $totalSaldo = 0;
              
              foreach ($movimientos as $mov): 
                $totalInicial += $mov['cantidad_inicial'];
                $totalEntrada += $mov['cantidad_entrada'];
                $totalSalida += $mov['cantidad_salida'];
                $totalSaldo += $mov['saldo'];
              ?>
              <tr>
                <td><?php echo htmlspecialchars($mov['codigoProducto']); ?></td>
                <td><?php echo htmlspecialchars($mov['descripcionProducto']); ?></td>
                <td><?php echo htmlspecialchars($mov['comprobante']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($mov['fecha_movimiento'])); ?></td>
                <td><?php echo number_format($mov['cantidad_inicial'], 0); ?></td>
                <td><?php echo number_format($mov['cantidad_entrada'], 0); ?></td>
                <td><?php echo number_format($mov['cantidad_salida'], 0); ?></td>
                <td><?php echo number_format($mov['saldo'], 0); ?></td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="no-data">No se encontraron movimientos con los filtros seleccionados</td>
              </tr>
            <?php endif; ?>
          </tbody>
          <?php if (count($movimientos) > 0): ?>
          <tfoot>
            <tr>
              <th colspan="4">TOTAL</th>
              <td><?php echo number_format($totalInicial, 0); ?></td>
              <td><?php echo number_format($totalEntrada, 0); ?></td>
              <td><?php echo number_format($totalSalida, 0); ?></td>
              <td><?php echo number_format($totalSaldo, 0); ?></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>

      <!-- Botón de Descarga -->
      <?php if (count($movimientos) > 0): ?>
      <div class="mt-4 text-center">
        <button type="button" class="btn btn-primary" onclick="generarPDF()">
          <i class="fas fa-file-pdf"></i> Descargar PDF
        </button>
        <button type="button" class="btn btn-success ms-2" onclick="exportarExcel()">
          <i class="fas fa-file-excel"></i> Descargar Excel
        </button>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- Formulario oculto para enviar datos al PDF -->
  <form id="formPdf" action="movimientoinventarios_pdf.php" method="POST" target="_blank" style="display: none;">
    <input type="hidden" name="datosMovimientos" id="datosMovimientosPdf">
    <input type="hidden" name="filtros" id="filtrosPdf">
  </form>
<br>
  <!-- Formulario oculto para enviar datos a Excel -->
  <form id="formExcel" action="movimientoinventarios_excel.php" method="POST" style="display: none;">
    <input type="hidden" name="datosMovimientos" id="datosMovimientosExcel">
    <input type="hidden" name="filtros" id="filtrosExcel">
  </form>
<br>
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

  <script>
    $(document).ready(function() {
      // Inicializar Select2 en el select de productos
      $('#productoFiltro').select2({
        placeholder: "Seleccione un producto",
        allowClear: true,
        width: '100%'
      });

      // Cargar productos cuando se selecciona una categoría
      $('#categoriaFiltro').on('change', function() {
        const categoriaId = $(this).val();
        const productoSelect = $('#productoFiltro');
        
        productoSelect.empty();
        productoSelect.append(new Option("Todos los productos", "", false, false));
        
        if (categoriaId) {
          $.ajax({
            url: 'obtener_productos_categoria.php',
            type: 'POST',
            data: { categoriaId: categoriaId },
            dataType: 'json',
            success: function(data) {
              data.forEach(function(producto) {
                const option = new Option(
                  producto.codigoProducto + ' - ' + producto.descripcionProducto,
                  producto.id,
                  false,
                  false
                );
                productoSelect.append(option);
              });
              productoSelect.trigger('change');
            },
            error: function(xhr, status, error) {
              console.error("Error al cargar productos:", error);
            }
          });
        } else {
          productoSelect.trigger('change');
        }
      });

      // Disparar el evento change si hay una categoría seleccionada al cargar
      <?php if (!empty($categoriaFiltro)): ?>
        $('#categoriaFiltro').trigger('change');
        <?php if (!empty($productoFiltro)): ?>
          setTimeout(function() {
            $('#productoFiltro').val('<?php echo $productoFiltro; ?>').trigger('change');
          }, 500);
        <?php endif; ?>
      <?php endif; ?>
    });

    function descargarPDF() {
      // Obtener los valores de los filtros
      const categoria = document.getElementById('categoriaFiltro').value;
      const producto = document.getElementById('productoFiltro').value;
      const desde = document.getElementById('fecha_desde').value;
      const hasta = document.getElementById('fecha_hasta').value;
      const tipo = document.getElementById('tipo').value;
      
      // Construir la URL con los parámetros
      let url = 'movimientoinventarios_pdf.php?';
      const params = [];
      
      if (categoria) params.push('categoria=' + encodeURIComponent(categoria));
      if (producto) params.push('producto=' + encodeURIComponent(producto));
      if (desde) params.push('desde=' + encodeURIComponent(desde));
      if (hasta) params.push('hasta=' + encodeURIComponent(hasta));
      if (tipo) params.push('tipo=' + encodeURIComponent(tipo));
      
      url += params.join('&');
      
      // Abrir el PDF en una nueva ventana
      window.open(url, '_blank');
    }

    // Función para generar PDF
    function generarPDF() {
      // Verificar que haya movimientos en la tabla
      const filas = document.querySelectorAll('#informe-table tbody tr');
      if (filas.length === 0 || filas[0].querySelector('.no-data')) {
        Swal.fire({
          icon: 'warning',
          title: 'Tabla vacía',
          text: 'No hay movimientos en la tabla para generar el PDF'
        });
        return;
      }

      // Preparar datos para el PDF
      const datosPDF = {
        movimientos: [],
        totales: {
          totalInicial: 0,
          totalEntrada: 0,
          totalSalida: 0,
          totalSaldo: 0
        }
      };

      // Recorrer todas las filas de la tabla
      filas.forEach(fila => {
        const codigoProducto = fila.querySelector('td:nth-child(1)').textContent;
        const nombreProducto = fila.querySelector('td:nth-child(2)').textContent;
        const comprobante = fila.querySelector('td:nth-child(3)').textContent;
        const fecha = fila.querySelector('td:nth-child(4)').textContent;
        const cantidadInicial = parseFloat(fila.querySelector('td:nth-child(5)').textContent.replace(/,/g, '')) || 0;
        const cantidadEntrada = parseFloat(fila.querySelector('td:nth-child(6)').textContent.replace(/,/g, '')) || 0;
        const cantidadSalida = parseFloat(fila.querySelector('td:nth-child(7)').textContent.replace(/,/g, '')) || 0;
        const saldo = parseFloat(fila.querySelector('td:nth-child(8)').textContent.replace(/,/g, '')) || 0;

        datosPDF.movimientos.push({
          codigoProducto: codigoProducto,
          nombreProducto: nombreProducto,
          comprobante: comprobante,
          fecha: fecha,
          cantidadInicial: cantidadInicial,
          cantidadEntrada: cantidadEntrada,
          cantidadSalida: cantidadSalida,
          saldo: saldo
        });

        // Acumular totales
        datosPDF.totales.totalInicial += cantidadInicial;
        datosPDF.totales.totalEntrada += cantidadEntrada;
        datosPDF.totales.totalSalida += cantidadSalida;
        datosPDF.totales.totalSaldo += saldo;
      });

      // Formatear totales para evitar decimales largos
      datosPDF.totales.totalInicial = parseFloat(datosPDF.totales.totalInicial.toFixed(2));
      datosPDF.totales.totalEntrada = parseFloat(datosPDF.totales.totalEntrada.toFixed(2));
      datosPDF.totales.totalSalida = parseFloat(datosPDF.totales.totalSalida.toFixed(2));
      datosPDF.totales.totalSaldo = parseFloat(datosPDF.totales.totalSaldo.toFixed(2));

      // Preparar información de filtros
      const filtrosAplicados = {
        categoria: document.getElementById('categoriaFiltro').options[document.getElementById('categoriaFiltro').selectedIndex].text,
        producto: document.getElementById('productoFiltro').options[document.getElementById('productoFiltro').selectedIndex].text,
        fechaDesde: document.getElementById('fecha_desde').value,
        fechaHasta: document.getElementById('fecha_hasta').value,
        tipo: document.getElementById('tipo').options[document.getElementById('tipo').selectedIndex].text
      };

      console.log('Datos enviados al PDF:', datosPDF);
      console.log('Filtros:', filtrosAplicados);

      // Enviar datos al formulario oculto
      document.getElementById('datosMovimientosPdf').value = JSON.stringify(datosPDF);
      document.getElementById('filtrosPdf').value = JSON.stringify(filtrosAplicados);

      // Enviar formulario
      document.getElementById('formPdf').submit();

      // Mostrar mensaje de éxito
      Swal.fire({
        icon: 'success',
        title: 'PDF generado',
        text: 'El PDF se está generando...',
        timer: 2000,
        showConfirmButton: false
      });
    }

    // Función para exportar a Excel
    function exportarExcel() {
      // Verificar que haya movimientos en la tabla
      const filas = document.querySelectorAll('#informe-table tbody tr');
      if (filas.length === 0 || filas[0].querySelector('.no-data')) {
        Swal.fire({
          icon: 'warning',
          title: 'Tabla vacía',
          text: 'No hay movimientos en la tabla para exportar a Excel'
        });
        return;
      }

      // Preparar datos para Excel (misma estructura que PDF)
      const datosExcel = {
        movimientos: [],
        totales: {
          totalInicial: 0,
          totalEntrada: 0,
          totalSalida: 0,
          totalSaldo: 0
        }
      };

      // Recorrer todas las filas de la tabla
      filas.forEach(fila => {
        const codigoProducto = fila.querySelector('td:nth-child(1)').textContent;
        const nombreProducto = fila.querySelector('td:nth-child(2)').textContent;
        const comprobante = fila.querySelector('td:nth-child(3)').textContent;
        const fecha = fila.querySelector('td:nth-child(4)').textContent;
        const cantidadInicial = parseFloat(fila.querySelector('td:nth-child(5)').textContent.replace(/,/g, '')) || 0;
        const cantidadEntrada = parseFloat(fila.querySelector('td:nth-child(6)').textContent.replace(/,/g, '')) || 0;
        const cantidadSalida = parseFloat(fila.querySelector('td:nth-child(7)').textContent.replace(/,/g, '')) || 0;
        const saldo = parseFloat(fila.querySelector('td:nth-child(8)').textContent.replace(/,/g, '')) || 0;

        datosExcel.movimientos.push({
          codigoProducto: codigoProducto,
          nombreProducto: nombreProducto,
          comprobante: comprobante,
          fecha: fecha,
          cantidadInicial: cantidadInicial,
          cantidadEntrada: cantidadEntrada,
          cantidadSalida: cantidadSalida,
          saldo: saldo
        });

        // Acumular totales
        datosExcel.totales.totalInicial += cantidadInicial;
        datosExcel.totales.totalEntrada += cantidadEntrada;
        datosExcel.totales.totalSalida += cantidadSalida;
        datosExcel.totales.totalSaldo += saldo;
      });

      // Formatear totales
      datosExcel.totales.totalInicial = parseFloat(datosExcel.totales.totalInicial.toFixed(2));
      datosExcel.totales.totalEntrada = parseFloat(datosExcel.totales.totalEntrada.toFixed(2));
      datosExcel.totales.totalSalida = parseFloat(datosExcel.totales.totalSalida.toFixed(2));
      datosExcel.totales.totalSaldo = parseFloat(datosExcel.totales.totalSaldo.toFixed(2));

      // Preparar información de filtros
      const filtrosAplicados = {
        categoria: document.getElementById('categoriaFiltro').options[document.getElementById('categoriaFiltro').selectedIndex].text,
        producto: document.getElementById('productoFiltro').options[document.getElementById('productoFiltro').selectedIndex].text,
        fechaDesde: document.getElementById('fecha_desde').value,
        fechaHasta: document.getElementById('fecha_hasta').value,
        tipo: document.getElementById('tipo').options[document.getElementById('tipo').selectedIndex].text
      };

      console.log('Datos enviados a Excel:', datosExcel);

      // Enviar datos al formulario oculto de Excel
      document.getElementById('datosMovimientosExcel').value = JSON.stringify(datosExcel);
      document.getElementById('filtrosExcel').value = JSON.stringify(filtrosAplicados);

      // Enviar formulario
      document.getElementById('formExcel').submit();

      // Mostrar mensaje de éxito
      Swal.fire({
        icon: 'success',
        title: 'Excel generado',
        text: 'El archivo Excel se está generando...',
        timer: 2000,
        showConfirmButton: false
      });
    }
  </script>

</body>
</html>