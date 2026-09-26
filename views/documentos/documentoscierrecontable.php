<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/auth_check.php'; ?>
<?php
require_once '../../config/database.php';
include('../../classes/LibroDiario.php');

$pdo = Database::getConnection();
$libroDiario = new LibroDiario($pdo);

$accion = isset($_POST['accion']) ? $_POST['accion'] : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'cerrar') {
    $anoFiscal = intval($_POST['anoFiscal'] ?? 0);

    try {
        if ($anoFiscal <= 0) {
            throw new Exception("Selecciona un año fiscal válido.");
        }
        $resultado = $libroDiario->ejecutarCierreContable($anoFiscal);
        $utilidad = number_format($resultado['utilidad_ejercicio'], 2, ',', '.');
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=cerrado&anio=" . $anoFiscal . "&utilidad=" . urlencode($utilidad));
        exit;
    } catch (Exception $e) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'revertir') {
    $anoFiscal = intval($_POST['anoFiscal'] ?? 0);

    try {
        $libroDiario->reversarCierreContable($anoFiscal);
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=revertido&anio=" . $anoFiscal);
        exit;
    } catch (Exception $e) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
        exit;
    }
}

// ================== AÑOS DISPONIBLES (los que tienen movimientos en libro_diario) ==================
$stmtAnios = $pdo->query("SELECT DISTINCT YEAR(fecha) as anio FROM libro_diario ORDER BY anio DESC");
$aniosDisponibles = $stmtAnios->fetchAll(PDO::FETCH_COLUMN);

// ================== AÑOS YA CERRADOS (activos) PARA DESHABILITARLOS EN EL SELECT ==================
$stmtCerrados = $pdo->query("SELECT ano_fiscal FROM cierres_contables WHERE estado = 'activo'");
$aniosCerrados = $stmtCerrados->fetchAll(PDO::FETCH_COLUMN);

// ================== HISTORIAL DE CIERRES ==================
$stmtHistorial = $pdo->query("SELECT * FROM cierres_contables ORDER BY ano_fiscal DESC, fecha_ejecucion DESC");
$historialCierres = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cierre Contable</title>

   <!-- Favicons -->
  <link href="../../assets/img/favicon.png" rel="icon">
  <link href="../../assets/img/apple-touch-icon.png" rel="apple-touch-icon">


  <!-- Vendor CSS Files -->
  <link href="../../assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="../../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link href="../../assets/css/improved-style.css" rel="stylesheet">

  <style>
    .cierre-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 20px;
    }

    .cierre-form-box {
      background-color: #fafbfc;
      border: 1px solid #dfe3e8;
      border-radius: 8px;
      padding: 25px;
      margin-bottom: 30px;
    }

    .cierre-form-box h5 {
      color: #2c3e50;
      font-weight: bold;
      margin-bottom: 15px;
    }

    .aviso-cierre {
      background-color: #fff8e1;
      border-left: 4px solid #f0ad4e;
      padding: 12px 15px;
      font-size: 13px;
      color: #6b5a1e;
      margin-bottom: 15px;
      border-radius: 4px;
    }

    .badge-activo {
      background-color: #2f6f4e;
      color: white;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
    }

    .badge-reversado {
      background-color: #a94442;
      color: white;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
    }

    .utilidad-positiva { color: #2f6f4e; font-weight: bold; }
    .utilidad-negativa { color: #a94442; font-weight: bold; }

    .table-container {
      overflow-x: auto;
      overflow-y: visible;
    }

    .table-historial th {
      background-color: #2c3e50;
      color: white;
      padding: 10px;
      font-size: 13px;
    }

    .table-historial td {
      padding: 10px;
      font-size: 13px;
      border-bottom: 1px solid #eee;
      vertical-align: middle;
    }
  </style>
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="../../dashboard.php">
          <img src="../../Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li>
            <a class="nav-link scrollto active" href="../../dashboard.php" style="color: darkblue;">Inicio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="../../perfil.php" style="color: darkblue;">Mi Negocio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="../../index.php" style="color: darkblue;">Cerrar Sesión</a>
          </li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

  <!-- ======= Services Section ======= -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='../menus/menudocumentos.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>

    <div class="container" data-aos="fade-up">

      <div class="section-title">
        <h2>CIERRE CONTABLE</h2>
        <p>Cierra el año fiscal para trasladar la utilidad o pérdida del ejercicio a patrimonio y dejar las cuentas de ingresos, costos y gastos listas para el año siguiente.</p>
      </div>

      <div class="cierre-container">

        <div class="cierre-form-box">
          <h5><i class="fas fa-lock me-2"></i>Cerrar un año fiscal</h5>

          <div class="aviso-cierre">
            <i class="fas fa-circle-info me-1"></i>
            Al cerrar un año se genera un asiento contable el 31 de diciembre que deja en cero las cuentas de ingresos, costos y gastos, y traslada el resultado a la cuenta de patrimonio "Utilidad del ejercicio". Esta acción se puede revertir después si es necesario.
          </div>

          <form action="" method="post" id="formCierre" class="d-flex gap-3 align-items-end flex-wrap">
            <input type="hidden" name="accion" value="cerrar">
            <div>
              <label class="form-label fw-bold">Año Fiscal</label>
              <select name="anoFiscal" id="anoFiscal" class="form-select" required>
                <option value="">Seleccione un año</option>
                <?php foreach ($aniosDisponibles as $anio): ?>
                  <?php $yaEstaCerrado = in_array($anio, $aniosCerrados); ?>
                  <option value="<?= htmlspecialchars($anio) ?>" <?= $yaEstaCerrado ? 'disabled' : '' ?>>
                    <?= htmlspecialchars($anio) ?><?= $yaEstaCerrado ? ' (ya cerrado)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <button type="submit" name="accion" value="cerrar" class="btn btn-primary">
                <i class="fas fa-lock me-1"></i> Cerrar Año Fiscal
              </button>
            </div>
          </form>
        </div>

        <h5 class="fw-bold mb-3">Historial de cierres</h5>

        <div class="table-container">
          <table class="table-historial w-100">
            <thead>
              <tr>
                <th>Año Fiscal</th>
                <th>Fecha de Cierre</th>
                <th class="text-end">Utilidad / Pérdida del Ejercicio</th>
                <th>Estado</th>
                <th>Fecha de Ejecución</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($historialCierres)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-3">Todavía no se ha ejecutado ningún cierre contable.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($historialCierres as $cierre): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($cierre['ano_fiscal']) ?></strong></td>
                    <td><?= date('d/m/Y', strtotime($cierre['fecha_cierre'])) ?></td>
                    <td class="text-end <?= $cierre['utilidad_ejercicio'] >= 0 ? 'utilidad-positiva' : 'utilidad-negativa' ?>">
                      $<?= number_format($cierre['utilidad_ejercicio'], 2) ?>
                      <?= $cierre['utilidad_ejercicio'] < 0 ? ' (pérdida)' : '' ?>
                    </td>
                    <td>
                      <?php if ($cierre['estado'] === 'activo'): ?>
                        <span class="badge-activo">Activo</span>
                      <?php else: ?>
                        <span class="badge-reversado">Reversado</span>
                      <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($cierre['fecha_ejecucion'])) ?></td>
                    <td>
                    <?php if ($cierre['estado'] === 'activo'): ?>
                      <form action="" method="post" class="form-revertir" data-anio="<?= htmlspecialchars($cierre['ano_fiscal']) ?>">
                        <input type="hidden" name="anoFiscal" value="<?= htmlspecialchars($cierre['ano_fiscal']) ?>">
                        <input type="hidden" name="accion" value="revertir">
                        <button type="submit" name="accion" value="revertir" class="btn btn-sm btn-outline-danger">
                          <i class="fas fa-rotate-left me-1"></i> Revertir
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </section><!-- End Services Section -->

  <?php if (isset($_GET['msg'])): ?>
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    switch ("<?= $_GET['msg'] ?>") {
      case "cerrado":
        Swal.fire({
          icon: 'success',
          title: 'Año fiscal cerrado',
          text: 'El año <?= htmlspecialchars($_GET['anio'] ?? '') ?> se cerró correctamente. Utilidad del ejercicio: $<?= htmlspecialchars($_GET['utilidad'] ?? '0') ?>',
          confirmButtonColor: '#103669'
        });
        break;

      case "revertido":
        Swal.fire({
          icon: 'success',
          title: 'Cierre revertido',
          text: 'El cierre del año <?= htmlspecialchars($_GET['anio'] ?? '') ?> fue revertido correctamente.',
          confirmButtonColor: '#103669'
        });
        break;

      case "error":
        const detalle = new URLSearchParams(window.location.search).get('detalle');
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: detalle || 'Ocurrió un error al procesar la operación',
          confirmButtonColor: '#eb0404'
        });
        break;
    }
  });
  </script>
  <?php endif; ?>

  <script>
    document.getElementById('formCierre').addEventListener('submit', function (e) {
      const anio = document.getElementById('anoFiscal').value;
      if (!anio) return;

      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: '¿Cerrar el año ' + anio + '?',
        text: 'Se generará el asiento de cierre y las cuentas de ingresos, costos y gastos quedarán en cero para el año siguiente. Podrás revertirlo después si es necesario.',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar año',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#103669'
      }).then((result) => {
        if (result.isConfirmed) {
          this.submit();
        }
      });
    });

    document.querySelectorAll('.form-revertir').forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const anio = this.getAttribute('data-anio');
        Swal.fire({
          icon: 'warning',
          title: '¿Revertir el cierre del año ' + anio + '?',
          text: 'Se eliminará el asiento de cierre y las cuentas de ingresos, costos y gastos volverán a mostrar su saldo real.',
          showCancelButton: true,
          confirmButtonText: 'Sí, revertir',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#eb0404'
        }).then((result) => {
          if (result.isConfirmed) {
            this.submit();
          }
        });
      });
    });
  </script>

<!-- Vendor JS Files -->
  <script src="../../assets/vendor/aos/aos.js"></script>
  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <script>
    AOS.init();
  </script>

</body>
</html>
