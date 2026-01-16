<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Sanitizar entrada
function limpiar($valor) {
  return htmlspecialchars(trim($valor));
}

// Recibir datos del formulario
$txtId = limpiar($_POST['txtId'] ?? "");
$tipoTerceroArray = $_POST['tipoTercero'] ?? [];

// Si es string (por ejemplo cuando viene del botón Editar), lo convertimos a array
if (!is_array($tipoTerceroArray)) {
    $tipoTerceroArray = explode(',', $tipoTerceroArray);
}

$tipoTercero = implode(',', $tipoTerceroArray);

$tipoPersona = limpiar($_POST['tipoPersona'] ?? "");
$cedula = limpiar($_POST['cedula'] ?? "");
$digito = limpiar($_POST['digito'] ?? "");
$nombres = limpiar($_POST['nombres'] ?? "");
$apellidos = limpiar($_POST['apellidos'] ?? "");
$razonSocial = limpiar($_POST['razonSocial'] ?? "");
$departamento = limpiar($_POST['departamento'] ?? "");
$ciudad = limpiar($_POST['ciudad'] ?? "");
$direccion = limpiar($_POST['direccion'] ?? "");
$telefono = limpiar($_POST['telefono'] ?? "");
$correo = limpiar($_POST['correo'] ?? "");
$tipoRegimen = limpiar($_POST['tipoRegimen'] ?? "");
$actividadEconomica = limpiar($_POST['actividadEconomica'] ?? "");
$activo = isset($_POST['activo']) ? 1 : 0; // checkbox
$accion = $_POST['accion'] ?? "";

// Validar teléfono
if ($telefono && !preg_match('/^[0-9]{7,10}$/', $telefono)) {
  header("Location: ".$_SERVER['PHP_SELF']."?msg=telefono_invalido");
  $accion = ""; // Detener cualquier acción
}

switch ($accion) {
  case "btnAgregar":
    // Validaciones únicas
    $verificar = $pdo->prepare("SELECT COUNT(*) FROM catalogosterceros WHERE cedula = :cedula OR correo = :correo OR telefono = :telefono");
    $verificar->bindParam(':cedula', $cedula);
    $verificar->bindParam(':correo', $correo);
    $verificar->bindParam(':telefono', $telefono);
    $verificar->execute();
    $existe = $verificar->fetchColumn();

    if ($existe > 0) {
      header("Location: ".$_SERVER['PHP_SELF']."?msg=duplicado");
      break;
    }

    $sentencia = $pdo->prepare("INSERT INTO catalogosterceros 
      (tipoTercero, tipoPersona, cedula, digito, nombres, apellidos, razonSocial, departamento, ciudad, direccion, telefono, correo, tipoRegimen, actividadEconomica, activo) 
      VALUES 
      (:tipoTercero, :tipoPersona, :cedula, :digito, :nombres, :apellidos, :razonSocial, :departamento, :ciudad, :direccion, :telefono, :correo, :tipoRegimen, :actividadEconomica, :activo)");

    $sentencia->bindParam(':tipoTercero', $tipoTercero);
    $sentencia->bindParam(':tipoPersona', $tipoPersona);
    $sentencia->bindParam(':cedula', $cedula);
    $sentencia->bindParam(':digito', $digito);
    $sentencia->bindParam(':nombres', $nombres);
    $sentencia->bindParam(':apellidos', $apellidos);
    $sentencia->bindParam(':razonSocial', $razonSocial);
    $sentencia->bindParam(':departamento', $departamento);
    $sentencia->bindParam(':ciudad', $ciudad);
    $sentencia->bindParam(':direccion', $direccion);
    $sentencia->bindParam(':telefono', $telefono);
    $sentencia->bindParam(':correo', $correo);
    $sentencia->bindParam(':tipoRegimen', $tipoRegimen);
    $sentencia->bindParam(':actividadEconomica', $actividadEconomica);
    $sentencia->bindParam(':activo', $activo);
    $sentencia->execute();

    header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
    exit; // Evita reenvío del formulario
  break;

  case "btnModificar":
    $sentencia = $pdo->prepare("UPDATE catalogosterceros SET 
      tipoTercero = :tipoTercero, tipoPersona = :tipoPersona, cedula = :cedula, digito = :digito, nombres = :nombres,
      apellidos = :apellidos, razonSocial = :razonSocial, departamento = :departamento, ciudad = :ciudad,
      direccion = :direccion, telefono = :telefono, correo = :correo, tipoRegimen = :tipoRegimen,
      actividadEconomica = :actividadEconomica, activo = :activo
      WHERE id = :id");

    $sentencia->bindParam(':tipoTercero', $tipoTercero);
    $sentencia->bindParam(':tipoPersona', $tipoPersona);
    $sentencia->bindParam(':cedula', $cedula);
    $sentencia->bindParam(':digito', $digito);
    $sentencia->bindParam(':nombres', $nombres);
    $sentencia->bindParam(':apellidos', $apellidos);
    $sentencia->bindParam(':razonSocial', $razonSocial);
    $sentencia->bindParam(':departamento', $departamento);
    $sentencia->bindParam(':ciudad', $ciudad);
    $sentencia->bindParam(':direccion', $direccion);
    $sentencia->bindParam(':telefono', $telefono);
    $sentencia->bindParam(':correo', $correo);
    $sentencia->bindParam(':tipoRegimen', $tipoRegimen);
    $sentencia->bindParam(':actividadEconomica', $actividadEconomica);
    $sentencia->bindParam(':activo', $activo);
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();

    // Redirigir y mostrar alerta
    header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
    exit;
  break;

  case "btnEliminar":
    $sentencia = $pdo->prepare("DELETE FROM catalogosterceros WHERE id = :id");
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();
    header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
    exit;
  break;
}

$sentencia = $pdo->prepare("SELECT * FROM catalogosterceros ORDER BY id DESC");
$sentencia->execute();
$lista = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_GET['msg'])): ?>

<script>
    document.addEventListener("DOMContentLoaded", () => {
      switch ("<?= $_GET['msg'] ?>") {
        case "agregado":
          Swal.fire({
            icon: 'success',
            title: 'Guardado exitosamente',
            text: 'La cuenta contable se ha agregado correctamente',
            confirmButtonColor: '#3085d6'
          });
          break;

        case "modificado":
          Swal.fire({
            icon: 'success',
            title: 'Modificado correctamente',
            text: 'Los datos se actualizaron con éxito',
            confirmButtonColor: '#3085d6'
          });
          break;

        case "eliminado":
          Swal.fire({
            icon: 'success',
            title: 'Eliminado correctamente',
            text: 'La cuenta contable fue eliminada del registro',
            confirmButtonColor: '#3085d6'
          });
          break;
        case "duplicado":
          Swal.fire({
            icon: 'error',
            title: 'Error al guardar',
            text: 'Ya existe un tercero con la misma cédula, correo o teléfono.',
            confirmButtonColor: '#3085d6'
          });
          break;
        case "telefono_invalido":
          Swal.fire({
            icon: 'error',
            title: 'Teléfono no válido',
            text: 'El número de teléfono debe tener entre 7 y 10 dígitos.',
            confirmButtonColor: '#3085d6'
          });
          break;
      }

      // Quita el parámetro ?msg=... de la URL sin recargar
      if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('msg');
        window.history.replaceState({}, document.title, url);
      }
    });
</script>
<?php endif; ?>

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
  </style>

</head>
<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="dashboard.php">
          <img src="./Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
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
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

    <!-- ======= Services Section ======= -->
    <section id="services" class="services">
      <button class="btn-ir" onclick="window.location.href='menucatalogos.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>

      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>CATÁLOGO DE TERCEROS </h2>
          <p>A continuación puede ingresar a los catálogos configurados para su usuario en el sistema.</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>

        <div class="section-subtitle fw-bold">
          <i class="fas fa-plus-circle"></i> NUEVA TERCERO
        </div>

        <form id="formularioTercero" action="" method="post" class="container mt-3">
        <!-- ID oculto -->
        <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

        <div class="row g-3">
          <!-- Tipo de tercero -->
          <div class="col-md-4">
            <label class="form-label fw-bold">Tipo de tercero*</label><br>
            <div class="form-check form-check-inline">
              <input type="checkbox" class="form-check-input" name="tipoTercero[]" value="Cliente"
                    <?php if (strpos($tipoTercero, 'Cliente') !== false) echo 'checked'; ?>>
              <label class="form-check-label">Cliente</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="checkbox" class="form-check-input" name="tipoTercero[]" value="Proveedor"
                    <?php if (strpos($tipoTercero, 'Proveedor') !== false) echo 'checked'; ?>>
              <label class="form-check-label">Proveedor</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="checkbox" class="form-check-input" name="tipoTercero[]" value="Otro"
                    <?php if (strpos($tipoTercero, 'Otro') !== false) echo 'checked'; ?>>
              <label class="form-check-label">Otro</label>
            </div>
          </div>

          <!-- Tipo de persona -->
          <div class="col-md-4">
            <label class="form-label fw-bold">Tipo de persona*</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" id="personaNatural" name="tipoPersona" value="Natural"
                    <?php if ($tipoPersona == 'Natural') echo 'checked'; ?> onclick="toggleFields()">
              <label class="form-check-label">Persona Natural</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" id="personaJuridica" name="tipoPersona" value="Juridica"
                    <?php if ($tipoPersona == 'Juridica') echo 'checked'; ?> onclick="toggleFields()">
              <label class="form-check-label">Persona Jurídica</label>
            </div>
          </div>
        </div>

        <!-- Cédula y dígito -->
        <div class="row g-3 mt-2">
          <div class="col-md-8">
            <label for="cedula" class="form-label">Cédula o NIT*</label>
            <input type="text" class="form-control" id="cedula" name="cedula"
                  value="<?php echo $cedula; ?>" 
                  maxlength="10" 
                  pattern="[0-9]{1,10}"
                  title="La cédula o NIT debe tener máximo 10 dígitos numéricos"
                  required>
            <small class="form-text text-muted">Máximo 10 dígitos</small>
          </div>
          <div class="col-md-4">
            <label for="digito" class="form-label">Dígito de verificación</label>
            <input type="text" class="form-control" id="digito" name="digito" maxlength="1"
                  pattern="[1-9]" title="Solo se permite un dígito entre 1 y 9"
                  value="<?php echo $digula; ?>" placeholder="1-9">
          </div>
        </div>

        <!-- Nombres, apellidos y razón social -->
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="nombres" class="form-label">Nombres</label>
            <input type="text" class="form-control" id="nombres" name="nombres"
                  value="<?php echo $nombres; ?>" disabled>
          </div>
          <div class="col-md-4">
            <label for="apellidos" class="form-label">Apellidos</label>
            <input type="text" class="form-control" id="apellidos" name="apellidos"
                  value="<?php echo $apellidos; ?>" disabled>
          </div>
          <div class="col-md-4">
            <label for="razonSocial" class="form-label">Razón Social</label>
            <input type="text" class="form-control" id="razonSocial" name="razonSocial"
                  value="<?php echo $razonSocial; ?>" disabled>
          </div>
        </div>

        <!-- Departamento y ciudad -->
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="departamento" class="form-label">Departamento*</label>
            <input type="text" class="form-control" id="departamento" name="departamento"
                  placeholder="Buscar departamento..." autocomplete="off"
                  value="<?php echo $departamento; ?>" required>
          </div>
          <div class="col-md-6">
            <label for="ciudad" class="form-label">Ciudad*</label>
            <select id="ciudad" name="ciudad" class="form-control" disabled>
              <option value="">Selecciona una ciudad...</option>
            </select>
          </div>
        </div>

        <!-- Dirección, teléfono y correo -->
        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="direccion" class="form-label">Dirección*</label>
            <input type="text" class="form-control" id="direccion" name="direccion"
                  placeholder="ej: Cll 12 #52-16"
                  value="<?php echo $direccion; ?>" required>
          </div>
          <div class="col-md-4">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="number" class="form-control" id="telefono" name="telefono"
                  value="<?php echo $telefono; ?>">
          </div>
          <div class="col-md-4">
            <label for="correo" class="form-label">Correo electrónico*</label>
            <input type="email" class="form-control" id="correo" name="correo"
                  placeholder="example@correo.com"
                  value="<?php echo $correo; ?>" required>
          </div>
        </div>

        <!-- Régimen y actividad -->
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="tipoRegimen" class="form-label">Tipo de régimen*</label>
            <select class="form-select" id="tipoRegimen" name="tipoRegimen" required>
              <option selected>Seleccione un tipo de régimen</option>
              <option value="Responsable de IVA">Responsable de IVA</option>
              <option value="No responsable de IVA">No responsable de IVA</option>
              <option value="Regimen simple de tributación">Régimen simple de tributación</option>
              <option value="Regimen especial">Régimen especial</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="actividadEconomica" class="form-label">Actividad económica</label>
            <input type="text" class="form-control" id="actividadEconomica" name="actividadEconomica"
                  value="<?php echo $actividadEconomica; ?>" disabled>
          </div>
        </div>

        <!-- Activo -->
        <div class="form-check mt-3">
          <input type="checkbox" class="form-check-input" id="activo" name="activo"
                <?php if ($activo) echo 'checked'; ?>>
          <label class="form-check-label" for="activo">Activo</label>
        </div>

        <!-- Botones -->
        <div class="mt-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion" style="display:none;">Modificar</button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion" style="display:none;">Eliminar</button>
          <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
        </div>
      </form>

      <div class="section-title">
          <h3>TERCEROS REGISTRADOS</h3>
      </div>

        <div class="row">
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Tipo tercero</th>
                  <th>Tipo persona</th>
                  <th>Cedula o NIT</th>
                  <th>Digito</th>
                  <th>Nombres</th>
                  <th>Apellidos</th>
                  <th>Razon social</th>
                  <th>Departamento</th>
                  <th>Ciudad</th>
                  <th>Dirección</th>
                  <th>Telefono</th>
                  <th>Correo</th>
                  <th>Tipo de regimen</th>
                  <th>Actividad economica</th>
                  <th>Activo</th>
                  <th>Acción</th>
                </tr>
              </thead>

              <?php foreach($lista as $usuario){ ?>
              <tr>
                <td><?php echo $usuario['tipoTercero']; ?></td>
                <td><?php echo $usuario['tipoPersona']; ?></td>
                <td><?php echo $usuario['cedula']; ?></td>
                <td><?php echo $usuario['digito']; ?></td>
                <td><?php echo $usuario['nombres']; ?></td>
                <td><?php echo $usuario['apellidos']; ?></td>
                <td><?php echo $usuario['razonSocial']; ?></td>
                <td><?php echo $usuario['departamento']; ?></td>
                <td><?php echo $usuario['ciudad']; ?></td>
                <td><?php echo $usuario['direccion']; ?></td>
                <td><?php echo $usuario['telefono']; ?></td>
                <td><?php echo $usuario['correo']; ?></td>
                <td><?php echo $usuario['tipoRegimen']; ?></td>
                <td><?php echo $usuario['actividadEconomica']; ?></td>
                <td><?php echo $usuario['activo'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></td>
                <td>
                  <form action="" method="post" style="display: flex; justify-content: center; gap: 6px;">
                    <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                    <input type="hidden" name="tipoTercero" value="<?php echo $usuario['tipoTercero']; ?>">
                    <input type="hidden" name="tipoPersona" value="<?php echo $usuario['tipoPersona']; ?>">
                    <input type="hidden" name="cedula" value="<?php echo $usuario['cedula']; ?>">
                    <input type="hidden" name="digito" value="<?php echo $usuario['digito']; ?>">
                    <input type="hidden" name="nombres" value="<?php echo $usuario['nombres']; ?>">
                    <input type="hidden" name="apellidos" value="<?php echo $usuario['apellidos']; ?>">
                    <input type="hidden" name="razonSocial" value="<?php echo $usuario['razonSocial']; ?>">
                    <input type="hidden" name="departamento" value="<?php echo $usuario['departamento']; ?>">
                    <input type="hidden" name="ciudad" value="<?php echo $usuario['ciudad']; ?>">
                    <input type="hidden" name="direccion" value="<?php echo $usuario['direccion']; ?>">
                    <input type="hidden" name="telefono" value="<?php echo $usuario['telefono']; ?>">
                    <input type="hidden" name="correo" value="<?php echo $usuario['correo']; ?>">
                    <input type="hidden" name="tipoRegimen" value="<?php echo $usuario['tipoRegimen']; ?>">
                    <input type="hidden" name="actividadEconomica" value="<?php echo $usuario['actividadEconomica']; ?>">
                    <input type="hidden" name="activo" value="<?php echo $usuario['activo']; ?>">

                    <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info btn-editar-teceros" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="submit" value="btnEliminar" name="accion" class="btn btn-sm btn-danger" title="Eliminar">
                        <i class="fas fa-trash-alt"></i>
                    </button>

                  </form>
                </td>
              </tr>
            <?php } ?>
            </table>     
          </div>         
        </div>
      <script>

        document.addEventListener('DOMContentLoaded', function () {
            const departamentos = {
              'Amazonas': [
                          'Leticia', 'El Encanto', 'La Chorrera', 'La Pedrera', 
                          'La Victoria', 'Mirití - Paraná', 'Puerto Alegría', 
                          'Puerto Arica', 'Puerto Nariño', 'Puerto Santander', 
                          'Tarapacá'
                    ],
                    'Antioquia': [
                        'Medellín', 'Abejorral', 'Abriaqui', 'Alejandria', 'Amaga', 'Amalfi', 'Andes', 
                        'Angelopolis', 'Angostura', 'Anori', 'Santafe de Antioquia', 'Anza', 'Apartado', 
                        'Arboletes', 'Argelia', 'Armenia', 'Barbosa', 'Belmira', 'Bello', 'Betania', 'Betulia', 
                        'Ciudad Bolivar', 'Briceño', 'Buritica', 'Caceres', 'Caicedo', 'Caldas', 'Campamento', 
                        'Cañasgordas', 'Caracoli', 'Caramanta', 'Carepa', 'El Carmen de Viboral', 'Carolina', 
                        'Caucasia', 'Chigorodo', 'Cisneros', 'Cocorna', 'Concepcion', 'Concordia', 'Copacabana', 
                        'Dabeiba', 'Don Matias', 'Ebejico', 'El Bagre', 'Entrerrios', 'Envigado', 'Fredonia', 
                        'Frontino', 'Giraldo', 'Girardota', 'Gomez Plata', 'Granada', 'Guadalupe', 'Guarne', 
                        'Guatape', 'Heliconia', 'Hispania', 'Itagui', 'Ituango', 'Jardin', 'Jerico', 'La Ceja', 
                        'La Estrella', 'La Pintada', 'La Union', 'Liborina', 'Maceo', 'Marinilla', 'Montebello', 
                        'Murindo', 'Mutata', 'Nariño', 'Necocli', 'Nechi', 'Olaya', 'Peñol', 'Peque', 'Pueblorrico', 
                        'Puerto Berrio', 'Puerto Nare', 'Puerto Triunfo', 'Remedios', 'Retiro', 'Rionegro', 
                        'Sabanalarga', 'Sabaneta', 'Salgar', 'San Andres de Cuerquia', 'San Carlos', 'San Francisco', 
                        'San Jeronimo', 'San Jose de la Montaña', 'San Juan de Uraba', 'San Luis', 'San Pedro', 
                        'San Pedro de Uraba', 'San Rafael', 'San Roque', 'San Vicente', 'Santa Barbara', 
                        'Santa Rosa de Osos', 'Santo Domingo', 'El Santuario', 'Segovia', 'Sonson', 'Sopetran', 
                        'Tamesis', 'Taraza', 'Tarso', 'Titiribi', 'Toledo', 'Turbo', 'Uramita', 'Urrao', 'Valdivia', 
                        'Valparaiso', 'Vegachi', 'Venecia', 'Vigia del Fuerte', 'Yali', 'Yarumal', 'Yolombo', 'Yondo', 
                        'Zaragoza' 
                    ],
                    'Arauca': [
                          'Arauca', 'Arauquita', 'Cravo Norte', 'Fortul', 
                          'Puerto Rondon', 'Saravena', 'Tame'
                    ],
                    'Atlántico': [
                        'Barranquilla', 'Baranoa', 'Campo de la Cruz', 
                        'Candelaria', 'Galapa', 'Juan de Acosta', 
                        'Luruaco', 'Malambo', 'Manatí', 
                        'Palmar de Varela', 'Piojo', 'Polonuevo', 
                        'Ponedera', 'Puerto Colombia', 'Repelón', 
                        'Sabanagrande', 'Sabanalarga', 'Santa Lucía', 
                        'Santo Tomás', 'Soledad', 'Suan', 
                        'Tubará', 'Usiacurí'
                    ],
                    'Bolivar': [
                        'Cartagena', 'Achi', 'Altos del Rosario', 'Arenal', 'Arjona', 'Arroyohondo', 'Barranco de Loba', 
                        'Calamar', 'Cantagallo', 'Cicuco', 'Cordoba', 'Clemencia', 'El Carmen de Bolivar', 'El Guamo', 
                        'El Peñon', 'Hatillo de Loba', 'Magangue', 'Mahates', 'Margarita', 'Maria la Baja', 
                        'Montecristo', 'Mompos', 'Norosi', 'Morales', 'Pinillos', 'Regidor', 'Rio Viejo', 
                        'San Cristobal', 'San Estanislao', 'San Fernando', 'San Jacinto', 'San Jacinto del Cauca', 
                        'San Juan Nepomuceno', 'San Martin de Loba', 'San Pablo', 'Santa Catalina', 'Santa Rosa', 
                        'Santa Rosa del Sur', 'Simiti', 'Soplaviento', 'Talaigua Nuevo', 'Tiquisio', 'Turbaco', 
                        'Turbana', 'Villanueva', 'Zambrano'
                    ],
                    'Boyaca': [
                          'Tunja', 'Almeida', 'Aquitania', 'Arcabuco', 'Belen', 'Berbeo', 'Beteitiva', 
                          'Boavita', 'Boyaca', 'Briceño', 'Buenavista', 'Busbanza', 'Caldas', 'Campohermoso', 
                          'Cerinza', 'Chinavita', 'Chiquinquira', 'Chiscas', 'Chita', 'Chitaraque', 
                          'Chivata', 'Cienega', 'Combita', 'Coper', 'Corrales', 'Covarachia', 'Cubara', 
                          'Cucaita', 'Cuitiva', 'Chiquiza', 'Chivor', 'Duitama', 'El Cocuy', 'El Espino', 
                          'Firavitoba', 'Floresta', 'Gachantiva', 'Gameza', 'Garagoa', 'Guacamayas', 
                          'Guateque', 'Guayata', 'Gsican', 'Iza', 'Jenesano', 'Jerico', 'Labranzagrande', 
                          'La Capilla', 'La Victoria', 'La Uvita', 'Villa de Leyva', 'Macanal', 'Maripi', 
                          'Miraflores', 'Mongua', 'Mongui', 'Moniquira', 'Motavita', 'Muzo', 'Nobsa', 
                          'Nuevo Colon', 'Oicata', 'Otanche', 'Pachavita', 'Paez', 'Paipa', 'Pajarito', 
                          'Panqueba', 'Pauna', 'Paya', 'Paz de Rio', 'Pesca', 'Pisba', 'Puerto Boyaca', 
                          'Quipama', 'Ramiriqui', 'Raquira', 'Rondon', 'Saboya', 'Sachica', 'Samaca', 
                          'San Eduardo', 'San Jose de Pare', 'San Luis de Gaceno', 'San Mateo', 
                          'San Miguel de Sema', 'San Pablo de Borbur', 'Santana', 'Santa Maria', 
                          'Santa Rosa de Viterbo', 'Santa Sofia', 'Sativanorte', 'Sativasur', 
                          'Siachoque', 'Soata', 'Socota', 'Socha', 'Sogamoso', 'Somondoco', 'Sora', 
                          'Sotaquira', 'Soraca', 'Susacon', 'Sutamarchan', 'Sutatenza', 'Tasco', 
                          'Tenza', 'Tibana', 'Tibasosa', 'Tinjaca', 'Tipacoque', 'Toca', 'TogsI', 
                          'Topaga', 'Tota', 'Tunungua', 'Turmeque', 'Tuta', 'Tutaza', 'Umbita', 
                          'Ventaquemada', 'Viracacha', 'Zetaquira'
                        ],
                      'Caldas': [
                          'Manizales', 'Aguadas', 'Anserma', 'Aranzazu', 'Belalcazar', 'Chinchina', 
                          'Filadelfia', 'La Dorada', 'La Merced', 'Manzanares', 'Marmato', 
                          'Marquetalia', 'Marulanda', 'Neira', 'Norcasia', 'Pacora', 'Palestina', 
                          'Pensilvania', 'Riosucio', 'Risaralda', 'Salamina', 'Samana', 'San Jose', 
                          'Supia', 'Victoria', 'Villamaria', 'Viterbo'
                      ],
                      'Caquetá': [
                          'Florencia', 'Albania', 'Belen de los Andaquies', 'Cartagena del Chaira', 
                          'Curillo', 'El Doncello', 'El Paujil', 'La Montañita', 'Milan', 
                          'Morelia', 'Puerto Rico', 'San Jose del Fragua', 'San Vicente del Caguán', 
                          'Solano', 'Solita', 'Valparaiso'
                      ],
                      'Casanare': [
                          'Yopal', 'Aguazul', 'Chameza', 'Hato Corozal', 
                          'La Salina', 'Maní', 'Monterrey', 'Nunchía', 
                          'Orocue', 'Paz de Ariporo', 'Pore', 'Recetor', 
                          'Sabanalarga', 'Sacama', 'San Luis de Palenque', 
                          'Tamara', 'Tauramena', 'Trinidad', 'Villanueva'
                      ],    
                      'Cauca': [
                          'Popayán', 'Almaguer', 'Argelia', 'Balboa', 'Bolívar', 'Buenos Aires', 
                          'Cajibio', 'Caldono', 'Caloto', 'Corinto', 'El Tambo', 'Florencia', 
                          'Guachené', 'Guapi', 'Inza', 'Jambaló', 'La Sierra', 'La Vega', 'López', 
                          'Mercaderes', 'Miranda', 'Morales', 'Padilla', 'Páez', 'Patía', 
                          'Piamonte', 'Piendamo', 'Puerto Tejada', 'Puracé', 'Rosas', 'San Sebastián', 
                          'Santander de Quilichao', 'Santa Rosa', 'Silvia', 'Sotará', 'Suárez', 
                          'Sucre', 'Timbío', 'Timbiquí', 'Toribio', 'Totoro', 'Villa Rica'
                      ],
                      'Cesar': [
                          'Valledupar', 'Aguachica', 'Agustín Codazzi', 'Astrea', 'Becerril', 
                          'Bosconia', 'Chimichagua', 'Chiriguana', 'Curumaní', 'El Copey', 
                          'El Paso', 'Gamarra', 'Gonzalez', 'La Gloria', 'La Jagua de Ibirico', 
                          'Manaure', 'Pailitas', 'Pelaya', 'Pueblo Bello', 'Rio de Oro', 
                          'La Paz', 'San Alberto', 'San Diego', 'San Martin', 'Tamalameque'
                      ],
                      'Córdoba': [
                          'Montería', 'Ayapel', 'Buenavista', 'Canalete', 'Cereté', 'Chima', 
                          'Chinú', 'Ciénaga de Oro', 'Cotorra', 'La Apartada', 'Lorica', 
                          'Los Cordobas', 'Momil', 'Montelíbano', 'Moñitos', 'Planeta Rica', 
                          'Pueblo Nuevo', 'Puerto Escondido', 'Puerto Libertador', 'Purísima', 
                          'Sahagún', 'San Andrés Sotavento', 'San Antero', 'San Bernardo del Viento', 
                          'San Carlos', 'San Pelayo', 'Tierralta', 'Valencia'
                      ],
                      'Cundinamarca': [
                          'Agua de Dios', 'Alban', 'Anapoima', 'Anolaima', 'Arbeláez', 'Beltrán', 
                          'Bituima', 'Bojacá', 'Cabrera', 'Cachipay', 'Cajicá', 'Caparrapí', 
                          'Caqueza', 'Carmen de Carupa', 'Chaguani', 'Chía', 'Chipaque', 
                          'Choachí', 'Chocontá', 'Cogua', 'Cota', 'Cucunubá', 'El Colegio', 
                          'El Peñón', 'El Rosal', 'Facatativá', 'Fomeque', 'Fosca', 'Funza', 
                          'Fuquene', 'Fusagasugá', 'Gachala', 'Gachancipá', 'Gacheta', 'Gama', 
                          'Girardot', 'Granada', 'Guacheta', 'Guaduas', 'Guasca', 'Guataquí', 
                          'Guatavita', 'Guayabal de Siquima', 'Guayabetal', 'Gutiérrez', 
                          'Jerusalén', 'Junín', 'La Calera', 'La Mesa', 'La Palma', 'La Peña', 
                          'La Vega', 'Lenguazaque', 'Macheta', 'Madrid', 'Manta', 'Medina', 
                          'Mosquera', 'Nariño', 'Nemocón', 'Nilo', 'Nimaima', 'Nocaima', 
                          'Venecia', 'Pacho', 'Paime', 'Pandi', 'Paratebueno', 'Pasca', 
                          'Puerto Salgar', 'Puli', 'Quebradanegra', 'Quetame', 'Quipile', 
                          'Apulo', 'Ricaurte', 'San Antonio del Tequendama', 'San Bernardo', 
                          'San Cayetano', 'San Francisco', 'San Juan de Río Seco', 'Sasaima', 
                          'Sesquilé', 'Sibaté', 'Silvania', 'Simijaca', 'Soacha', 'Sopo', 
                          'Subachoque', 'Suesca', 'Supatá', 'Susa', 'Sutatausa', 'Tabio', 
                          'Tausa', 'Tena', 'Tenjo', 'Tibacuy', 'Tibirita', 'Tocaima', 
                          'Tocancipá', 'Topaipí', 'Ubala', 'Ubaque', 'Villa de San Diego de Ubaté', 
                          'Une', 'Utica', 'Vergara', 'Viani', 'Villagómez', 'Villapinzón', 
                          'Villeta', 'Viotá', 'Yacopí', 'Zipacón', 'Zipaquirá'
                      ],
                      'Chocó': [
                          'Quibdó', 'Acandí', 'Alto Baudó', 'Atrato', 'Bagadó', 'Bahía Solano', 
                          'Bajo Baudó', 'Bojayá', 'El Cantón del San Pablo', 'Carmen del Darién', 
                          'Certeguí', 'Condoto', 'El Carmen de Atrato', 'El Litoral del San Juan', 
                          'Istmina', 'Jurado', 'Lloró', 'Medio Atrato', 'Medio Baudó', 
                          'Medio San Juan', 'Novita', 'Nuquí', 'Río Iro', 'Río Quito', 
                          'Riosucio', 'San José del Palmar', 'Sipi', 'Tadó', 'Ungía', 
                          'Unión Panamericana'
                      ],
                      'Guainía': [
                          'Inírida', 'Barranco Minas', 'Mapiripana', 'San Felipe', 
                          'Puerto Colombia', 'La Guadalupe', 'Cacahual', 
                          'Pana Pana', 'Morichal'
                      ],
                      'Guaviare': [
                          'San José del Guaviare', 'Calamar', 'El Retorno', 
                          'Miraflores'
                      ],    
                      'Huila': [
                          'Neiva', 'Acevedo', 'Agrado', 'Aipe', 'Algeciras', 'Altamira', 
                          'Baraya', 'Campoalegre', 'Colombia', 'Elías', 'Garzón', 'Gigante', 
                          'Guadalupe', 'Hobo', 'Iquira', 'Isnos', 'La Argentina', 'La Plata', 
                          'Nataga', 'Oporapa', 'Paicol', 'Palermo', 'Palestina', 'Pital', 
                          'Pitalito', 'Rivera', 'Saladoblanco', 'San Agustín', 'Santa María', 
                          'Suaza', 'Tarqui', 'Tesalia', 'Tello', 'Teruel', 'Timaná', 
                          'Villavieja', 'Yaguará'
                      ],
                      'La Guajira': [
                          'Riohacha', 'Albania', 'Barrancas', 'Dibulla', 'Distracción', 
                          'El Molino', 'Fonseca', 'Hatonuevo', 'La Jagua del Pilar', 
                          'Maicao', 'Manaure', 'San Juan del Cesar', 'Uribia', 
                          'Urumita', 'Villanueva'
                      ],
                      'Magdalena': [
                          'Santa Marta', 'Algarrobo', 'Aracataca', 'Ariguani', 
                          'Cerro San Antonio', 'Chibolo', 'Ciénaga', 'Concordia', 
                          'El Banco', 'El Piñón', 'El Retén', 'Fundación', 
                          'Guamal', 'Nueva Granada', 'Pedraza', 'Pijiño del Carmen', 
                          'Pivijay', 'Plato', 'Pueblo Viejo', 'Remolino', 
                          'Sabanas de San Ángel', 'Salamina', 'San Sebastián de Buenavís', 
                          'San Zenón', 'Santa Ana', 'Santa Bárbara de Pinto', 
                          'Sitionuevo', 'Tenerife', 'Zapayán', 'Zona Bananera'
                      ],
                      'Meta': [
                          'Villavicencio', 'Acacías', 'Barranca de Upía', 'Cabuyaro', 
                          'Castilla la Nueva', 'Cubarral', 'Cumaral', 'El Calvario', 
                          'El Castillo', 'El Dorado', 'Fuente de Oro', 'Granada', 
                          'Guamal', 'Mapiripán', 'Mesetas', 'La Macarena', 
                          'Uribe', 'Lejanías', 'Puerto Concordia', 'Puerto Gaitán', 
                          'Puerto López', 'Puerto Lleras', 'Puerto Rico', 'Restrepo', 
                          'San Carlos de Guaroa', 'San Juan de Arama', 'San Juanito', 
                          'San Martín', 'Vistahermosa'
                      ],
                      'Nariño': [
                          'Pasto', 'Alban', 'Aldana', 'Ancuya', 'Arboleda', 
                          'Barbacoas', 'Belen', 'Buesaco', 'Colon', 'Consaca', 
                          'Contadero', 'Cordoba', 'Cuaspud', 'Cumbal', 'Cumbitara', 
                          'Chachagüí', 'El Charco', 'El Peñol', 'El Rosario', 
                          'El Tablón de Gómez', 'El Tambo', 'Funes', 'Guachucal', 
                          'Guitarrilla', 'Gualmatán', 'Iles', 'Imues', 'Ipiales', 
                          'La Cruz', 'La Florida', 'La Llanada', 'La Tola', 
                          'La Union', 'Leiva', 'Linares', 'Los Andes', 'Magüí', 
                          'Mallama', 'Mosquera', 'Nariño', 'Olaya Herrera', 
                          'Ospina', 'Francisco Pizarro', 'Policarpa', 'Potosí', 
                          'Providencia', 'Puerres', 'Pupiales', 'Ricaurte', 
                          'Roberto Payán', 'Samaniego', 'Sandona', 'San Bernardo', 
                          'San Lorenzo', 'San Pablo', 'San Pedro de Cartago', 
                          'Santa Barbara', 'Santacruz', 'Sapuyes', 'Taminango', 
                          'Tangua', 'San Andres de Tumaco', 'Tuquerres', 
                          'Yacuanquer'
                      ],
                      'Norte de Santander': [
                          'Cúcuta', 'Abrego', 'Arboledas', 'Bochalema', 
                          'Bucarasica', 'Cacota', 'Cachira', 'Chinacota', 
                          'Chitagá', 'Convención', 'Cucutilla', 'Durania', 
                          'El Carmen', 'El Tarra', 'El Zulia', 'Gramalote', 
                          'Hacarí', 'Herrán', 'Labateca', 'La Esperanza', 
                          'La Playa', 'Los Patios', 'Lourdes', 'Mutiscua', 
                          'Ocaña', 'Pamplona', 'Pamplonita', 'Puerto Santander', 
                          'Ragonvalia', 'Salazar', 'San Calixto', 'San Cayetano', 
                          'Santiago', 'Sardinata', 'Silos', 'Teorama', 'Tibú', 
                          'Toledo', 'Villa Caro', 'Villa del Rosario'
                      ],
                      'Quindío': [
                          'Armenia', 'Buenavista', 'Calarcá', 'Circasia', 
                          'Córdoba', 'Filandia', 'Génova', 'La Tebaida', 
                          'Montenegro', 'Pijao', 'Quimbaya', 'Salento'
                      ],
                      'Risaralda': [
                          'Pereira', 'Apía', 'Balboa', 'Belen de Umbria', 
                          'Dosquebradás', 'Guática', 'La Celia', 'La Virginia', 
                          'Marsella', 'Mistrató', 'Pueblo Rico', 'Quinchía', 
                          'Santa Rosa de Cabal', 'Santuario'
                      ],
                      'San Andrés, Providencia y Santa Catalina': [
                          'San Andrés', 'Providencia'
                      ],
                      'Santander': [
                          'Bucaramanga', 'Aguada', 'Albania', 'Aratoca', 
                          'Barbosa', 'Barichara', 'Barrancabermeja', 'Betulia', 
                          'Bolívar', 'Cabrera', 'California', 'Capitanejo', 
                          'Carcasi', 'Cepita', 'Cerrito', 'Charalá', 
                          'Charta', 'Chima', 'Chipatá', 'Cimitarrá', 
                          'Concepción', 'Confines', 'Contratación', 'Coromoro', 
                          'Curití', 'El Carmen de Chucurí', 'El Guacamayo', 
                          'El Peñón', 'El Playón', 'Encino', 'Enciso', 
                          'Florian', 'Floridablanca', 'Galán', 'Gambita', 
                          'Girón', 'Guaca', 'Guadalupe', 'Guapota', 
                          'Guavata', 'G$EPSA', 'Hato', 'Jesús María', 
                          'Jordán', 'La Belleza', 'Landazuri', 'La Paz', 
                          'Lebrija', 'Los Santos', 'Macaravita', 'Málaga', 
                          'Matanza', 'Mogotes', 'Molagavita', 'Ocamonte', 
                          'Oiba', 'Onzaga', 'Palmar', 'Palmas del Socorro', 
                          'Páramo', 'Piedecuesta', 'Pinchote', 'Puente Nacional', 
                          'Puerto Parra', 'Puerto Wilches', 'Rionegro', 
                          'Sabana de Torres', 'San Andrés', 'San Benito', 
                          'San Gil', 'San Joaquín', 'San José de Miranda', 
                          'San Miguel', 'San Vicente de Chucurí', 'Santa Bárbara', 
                          'Santa Helena del Opon', 'Simacota', 'Socorro', 
                          'Suita', 'Sucre', 'Surata', 'Tona', 
                          'Valle de San José', 'Vélez', 'Vetas', 
                          'Villanueva', 'Zapatoca'
                      ],
                      'Sucre': [
                          'Sincelejo', 'Buenavista', 'Caimito', 'Coloso', 
                          'Corozal', 'Coveñas', 'Chalán', 'El Roble', 
                          'Galeras', 'Guaranda', 'La Unión', 'Los Palmitos', 
                          'Majagual', 'Morroa', 'Ovejas', 'Palmito', 
                          'Sampués', 'San Benito Abad', 'San Juan de Betulia', 
                          'San Marcos', 'San Onofre', 'San Pedro', 'San Luis de Since', 
                          'Sucre', 'Santiago de Tolu', 'Tolu Viejo'
                      ],
                      'Tolima': [
                          'Ibagué', 'Alpujarra', 'Alvarado', 'Ambalema', 
                          'Anzoátegui', 'Armero', 'Ataco', 'Cajamarca', 
                          'Carmen de Apicalá', 'Casabianca', 'Chaparral', 
                          'Coello', 'Coyaima', 'Cunday', 'Dolores', 
                          'Espinal', 'Falan', 'Flandes', 'Fresno', 
                          'Guamo', 'Herveo', 'Honda', 'Icononzo', 
                          'Lérida', 'Líbano', 'Mariquita', 'Melgar', 
                          'Murillo', 'Natagaima', 'Ortega', 'Palocabildo', 
                          'Piedras', 'Planadas', 'Prado', 'Purificación', 
                          'Rioblanco', 'Roncesvalles', 'Rovira', 'Saldaña', 
                          'San Antonio', 'San Luis', 'Santa Isabel', 
                          'Suárez', 'Valle de San Juan', 'Venadillo', 
                          'Villahermosa', 'Villarrica'
                      ],
                      'Valle del Cauca': [
                          'Cali', 'Alcalá', 'Andalucía', 'Ansermanuevo', 
                          'Argelia', 'Bolívar', 'Buenaventura', 'Guadalajara de Buga', 
                          'Bugalagrande', 'Caicedonia', 'Calima', 'Candelaria', 
                          'Cartago', 'Dagua', 'El Águila', 'El Cairo', 
                          'El Cerrito', 'El Dovio', 'Florida', 'Ginebra', 
                          'Guacarí', 'Jamundí', 'La Cumbre', 'La Unión', 
                          'La Victoria', 'Obando', 'Palmira', 'Pradera', 
                          'Restrepo', 'Riofrío', 'Roldanillo', 'San Pedro', 
                          'Sevilla', 'Toro', 'Trujillo', 'Tuluá', 
                          'Ulloa', 'Versalles', 'Vijes', 'Yotoco', 
                          'Yumbo', 'Zarzal'
                      ],
                      'Putumayo': [
                          'Mocoa', 'Colón', 'Orito', 'Puerto Asís', 
                          'Puerto Caicedo', 'Puerto Guzmán', 'Leguízamo', 
                          'Sibundoy', 'San Francisco', 'San Miguel', 
                          'Santiago', 'Valle del Guamuez', 'Villagarzón'
                      ],
                      'Vaupés': [
                          'Mitú', 'Carurú', 'Pacoa', 'Taraíra', 
                          'Papunaúa', 'Yavarate'
                      ],
                      'Vichada': [
                          'Puerto Carreño', 'La Primavera', 'Santa Rosalía', 
                          'Cumaribo'
                      ]     
            };
    
            const input = document.getElementById('departamento');
            const ciudadSelect = document.getElementById('ciudad');
    
            input.addEventListener('input', function () {
                const searchTerm = input.value.toLowerCase();
                const filteredDepartamentos = Object.keys(departamentos).filter(dpto =>
                    dpto.toLowerCase().includes(searchTerm)
                );
                mostrarSugerencias(filteredDepartamentos);
            });
    
            function mostrarSugerencias(departamentosFiltrados) {
                const listaSugerencias = document.createElement('ul');
                listaSugerencias.classList.add('list-group');
                
                departamentosFiltrados.forEach(departamento => {
                    const item = document.createElement('li');
                    item.textContent = departamento;
                    item.classList.add('list-group-item');
                    item.addEventListener('click', function () {
                        input.value = departamento;
                        limpiarSugerencias();
                        activarCiudadSelect(departamento);
                    });
                    listaSugerencias.appendChild(item);
                });
    
                limpiarSugerencias();
                input.parentNode.appendChild(listaSugerencias);
            }
    
            function limpiarSugerencias() {
                const listaAnterior = document.querySelector('.list-group');
                if (listaAnterior) {
                    listaAnterior.remove();
                }
            }
    
            function activarCiudadSelect(departamento) {
                // Limpia las opciones anteriores
                ciudadSelect.innerHTML = '<option value="">Selecciona una ciudad...</option>';
                ciudadSelect.disabled = false; // Activa el campo
    
                // Rellena las opciones según el departamento seleccionado
                departamentos[departamento].forEach(ciudad => {
                    const option = document.createElement('option');
                    option.value = ciudad;
                    option.textContent = ciudad;
                    ciudadSelect.appendChild(option);
                });
            }
        });
    </script>

    <script>
      function toggleFields() {
        const personaNatural = document.getElementById("personaNatural").checked;
        const personaJuridica = document.getElementById("personaJuridica").checked;
        const tipoTerceros = document.querySelectorAll('input[name="tipoTercero[]"]:checked');

        // --- Habilitar campos según tipo de persona ---
        document.getElementById("nombres").disabled = !personaNatural;
        document.getElementById("apellidos").disabled = !personaNatural;
        document.getElementById("razonSocial").disabled = !personaJuridica;

        // --- Limpiar campos no habilitados ---
        if (personaNatural) {
          document.getElementById("nit").value = "";
        } else if (personaJuridica) {
          document.getElementById("cedula").value = "";
        }

        // --- Habilitar campo de actividad económica si se marca Cliente o Proveedor ---
        let habilitarActividad = false;
        tipoTerceros.forEach(tipo => {
          if (tipo.value === "Cliente" || tipo.value === "Proveedor") {
            habilitarActividad = true;
          }
        });

        document.getElementById("actividadEconomica").disabled = !habilitarActividad;
      }

      // Llamar cada vez que cambie algo
      document.addEventListener("DOMContentLoaded", function() {
        // Detectar cambios en persona natural / jurídica
        document.getElementById("personaNatural").addEventListener("change", toggleFields);
        document.getElementById("personaJuridica").addEventListener("change", toggleFields);

        // Detectar cambios en tipo de tercero (Cliente / Proveedor / Otro)
        document.querySelectorAll('input[name="tipoTercero[]"]').forEach(input => {
          input.addEventListener("change", toggleFields);
        });

        // Llamar una vez al inicio
        toggleFields();
      });
      
      // Script para alternar botones
      document.addEventListener("DOMContentLoaded", function() {
        const id = document.getElementById("txtId").value;
        const btnAgregar = document.getElementById("btnAgregar");
        const btnModificar = document.getElementById("btnModificar");
        const btnEliminar = document.getElementById("btnEliminar");
        const btnCancelar = document.getElementById("btnCancelar");
        const form = document.getElementById("formularioTercero");

        function modoAgregar() {
          // Ocultar/mostrar botones
          btnAgregar.style.display = "inline-block";
          btnModificar.style.display = "none";
          btnEliminar.style.display = "none";
          btnCancelar.style.display = "none";

          // Limpiar todos los campos manualmente
          form.querySelectorAll("input, select, textarea").forEach(el => {
            if (el.type === "radio" || el.type === "checkbox") {
              el.checked = false;
            } else {
              el.value = "";
            }
          });

          // Si tienes checkbox "Activo", lo marcamos por defecto
          const chkActivo = document.querySelector('input[name="activo"]');
          if (chkActivo) chkActivo.checked = true;

          // Asegurar que el ID quede vacío
          const txtId = document.getElementById("txtId");
          if (txtId) txtId.value = "";
        }

        // Estado inicial (modo modificar o agregar)
        if (id && id.trim() !== "") {
          btnAgregar.style.display = "none";
          btnModificar.style.display = "inline-block";
          btnEliminar.style.display = "inline-block";
          btnCancelar.style.display = "inline-block";
        } else {
          modoAgregar();
        }

        // Evento cancelar
        btnCancelar.addEventListener("click", function(e) {
            e.preventDefault();
            modoAgregar();
            
            // AJUSTE ADICIONAL: Limpiar los parámetros de edición de la URL
            if (window.history.replaceState) {
                const url = new URL(window.location);
                // Elimina todos los parámetros POST que se cargan al editar
                url.searchParams.forEach((value, key) => {
                    if (key !== 'msg') { // Dejamos 'msg' por si acaso
                        url.searchParams.delete(key);
                    }
                });
                window.history.replaceState({}, document.title, url);
            }
           });
      });

      document.addEventListener('DOMContentLoaded', function () {
        const departamentos = {
          'Amazonas': ['Leticia', 'El Encanto', 'La Chorrera', 'La Pedrera', 'La Victoria', 'Mirití - Paraná', 'Puerto Alegría', 'Puerto Arica', 'Puerto Nariño', 'Puerto Santander', 'Tarapacá'],
          'Antioquia': ['Medellín', 'Abejorral', 'Abriaqui', 'Alejandria', 'Amaga', 'Amalfi'],
          'Arauca': ['Arauca', 'Arauquita', 'Cravo Norte', 'Fortul', 'Puerto Rondon', 'Saravena', 'Tame']
        };

        const inputDepto = document.getElementById('departamento');
        const selectCiudad = document.getElementById('ciudad');
        const ciudadActual = "<?php echo $ciudad; ?>";

        // --- Si el formulario está cargado con un departamento (modo edición) ---
        if (inputDepto.value && departamentos[inputDepto.value]) {
          selectCiudad.innerHTML = '<option value="">Selecciona una ciudad...</option>';
          departamentos[inputDepto.value].forEach(ciudad => {
            const option = document.createElement('option');
            option.value = ciudad;
            option.textContent = ciudad;
            if (ciudad === ciudadActual) option.selected = true;
            selectCiudad.appendChild(option);
          });
          selectCiudad.disabled = false;
        }

        // --- Activar campos según tipoPersona en modo edición ---
        toggleFields();
      });
      </script>

      <script>
      function toggleFields() {
        const personaNatural = document.getElementById("personaNatural").checked;
        const personaJuridica = document.getElementById("personaJuridica").checked;

        const nombres = document.getElementById("nombres");
        const apellidos = document.getElementById("apellidos");
        const razonSocial = document.getElementById("razonSocial");
        const actividad = document.getElementById("actividadEconomica");

        if (personaNatural) {
          nombres.disabled = false;
          apellidos.disabled = false;
          razonSocial.disabled = true;
          actividad.disabled = false;
        } else if (personaJuridica) {
          nombres.disabled = true;
          apellidos.disabled = true;
          razonSocial.disabled = false;
          actividad.disabled = false;
        } else {
          nombres.disabled = true;
          apellidos.disabled = true;
          razonSocial.disabled = true;
          actividad.disabled = true;
        }
      }
      
      // Funciones de confirmación con SweetAlert2
      document.addEventListener("DOMContentLoaded", () => {
        // Selecciona TODOS los formularios de la página
        const forms = document.querySelectorAll("form");

        forms.forEach((form) => {
          form.addEventListener("submit", function (e) {
            const boton = e.submitter; // botón que disparó el envío
            const accion = boton?.value;

            // Solo mostrar confirmación para modificar o eliminar
            if (accion === "btnModificar" || accion === "btnEliminar") {
              e.preventDefault(); // detener envío temporalmente

              let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar registro?";
              let texto = accion === "btnModificar"
                ? "Se actualizarán los datos de esta cuenta contable."
                : "Esta acción eliminará el registro permanentemente.";

              Swal.fire({
                title: titulo,
                text: texto,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, continuar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: accion === "btnModificar" ? "#3085d6" : "#d33",
                cancelButtonColor: "#6c757d",
              }).then((result) => {
                if (result.isConfirmed) {
                  //  Crear (si no existe) un campo oculto con la acción seleccionada
                  let inputAccion = form.querySelector("input[name='accionOculta']");
                  if (!inputAccion) {
                    inputAccion = document.createElement("input");
                    inputAccion.type = "hidden";
                    inputAccion.name = "accion";
                    form.appendChild(inputAccion);
                  }
                  inputAccion.value = accion;

                  form.submit(); // Enviar el formulario correspondiente
                }
              });
            }
          });
        });
      });
    </script>

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