<?php
require_once 'config/database.php';
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$pdo = Database::getConnection();

// Obtener usuario logueado con su rol
$username = $_SESSION['usuario'];
$sqlUser = "SELECT u.*, r.nombre as rol_nombre 
            FROM usuarios u 
            INNER JOIN roles r ON u.rol_id = r.id 
            WHERE u.username = :username";
$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute([':username' => $username]);
$usuario = $stmtUser->fetch();

if (!$usuario) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$esAdmin = ($usuario['rol_id'] == 1);

// Variables iniciales
$persona = $cedula = $digito = $nombres = $apellidos = $razon = "";
$departamento = $ciudad = $direccion = $email = $telefono = "";
$responsabilidadesInput = "";
$regimen = $actividad = "";
$tarifa = 0;
$aiu = 0;
$perfilExiste = false;
$modoEdicion = false;

// Verificar si ya existe un perfil
$stmtPerfil = $pdo->query("SELECT * FROM perfil LIMIT 1");
$perfil = $stmtPerfil->fetch();

if ($perfil) {
    $perfilExiste = true;
    $persona        = $perfil['persona'];
    $cedula         = $perfil['cedula'];
    $digito         = $perfil['digito'];
    $nombres        = $perfil['nombres'];
    $apellidos      = $perfil['apellidos'];
    $razon          = $perfil['razon'];
    $departamento   = $perfil['departamento'];
    $ciudad         = $perfil['ciudad'];
    $direccion      = $perfil['direccion'];
    $email          = $perfil['email'];
    $telefono       = $perfil['telefono'];
    $responsabilidadesInput = $perfil['responsabilidad'];
    $regimen        = $perfil['regimen'];
    $actividad      = $perfil['actividad'];
    $tarifa         = $perfil['tarifa'];
    $aiu            = $perfil['aiu'];
}

// Modo edición (solo admin)
if (isset($_GET['editar'])) {
    if ($esAdmin) {
        $modoEdicion = true;
    } else {
        header("Location: perfil.php");
        exit();
    }
}

$mostrarMensaje = isset($_GET['guardado']) && $_GET['guardado'] == 1 ? "guardar" : null;

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!$esAdmin && $perfilExiste) {
        $mostrarMensaje = "sinPermiso";
    } else {
        $persona      = $_POST['persona']      ?? '';
        $cedula       = $_POST['cedula']       ?? '';
        $digito       = $_POST['digito']       ?? '';
        $nombres      = $_POST['nombres']      ?? '';
        $apellidos    = $_POST['apellidos']    ?? '';
        $razon        = $_POST['razon']        ?? '';
        $departamento = $_POST['departamento'] ?? '';
        $ciudad       = $_POST['ciudad']       ?? '';
        $direccion    = $_POST['direccion']    ?? '';
        $email        = $_POST['email']        ?? '';
        $telefono     = $_POST['telefono']     ?? '';
        $regimen      = $_POST['regimen']      ?? '';
        $actividad    = $_POST['actividad']    ?? '';
        $tarifa       = !empty($_POST['tarifa']) ? floatval($_POST['tarifa']) : null;
        $aiu          = isset($_POST['aiu']) ? 1 : 0;

        $responsabilidadesSeleccionadas = $_POST['responsabilidades'] ?? [];
        $responsabilidadesInput = is_array($responsabilidadesSeleccionadas)
            ? implode(', ', $responsabilidadesSeleccionadas)
            : $responsabilidadesSeleccionadas;

        // Validar cédula
        if (strlen($cedula) > 10) {
            $cedula = substr($cedula, 0, 10);
        }

        if (!ctype_digit($cedula)) {
            $mostrarMensaje = "cedulaInvalida";
        } else {
            try {
                if ($perfilExiste) {
                    // ACTUALIZAR
                    $sql = "UPDATE perfil SET 
                                persona=:persona, cedula=:cedula, digito=:digito,
                                nombres=:nombres, apellidos=:apellidos, razon=:razon,
                                departamento=:departamento, ciudad=:ciudad, direccion=:direccion,
                                email=:email, regimen=:regimen, actividad=:actividad,
                                tarifa=:tarifa, aiu=:aiu, telefono=:telefono,
                                responsabilidad=:responsabilidad
                            WHERE id=:id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':persona'          => $persona,
                        ':cedula'           => $cedula,
                        ':digito'           => $digito,
                        ':nombres'          => $nombres,
                        ':apellidos'        => $apellidos,
                        ':razon'            => $razon,
                        ':departamento'     => $departamento,
                        ':ciudad'           => $ciudad,
                        ':direccion'        => $direccion,
                        ':email'            => $email,
                        ':regimen'          => $regimen,
                        ':actividad'        => $actividad,
                        ':tarifa'           => $tarifa,
                        ':aiu'              => $aiu,
                        ':telefono'         => $telefono,
                        ':responsabilidad'  => $responsabilidadesInput,
                        ':id'               => $perfil['id'],
                    ]);
                } else {
                    // INSERTAR
                    $sql = "INSERT INTO perfil 
                                (persona, cedula, digito, nombres, apellidos, razon,
                                 departamento, ciudad, direccion, email, regimen,
                                 actividad, tarifa, aiu, telefono, responsabilidad)
                            VALUES 
                                (:persona, :cedula, :digito, :nombres, :apellidos, :razon,
                                 :departamento, :ciudad, :direccion, :email, :regimen,
                                 :actividad, :tarifa, :aiu, :telefono, :responsabilidad)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':persona'          => $persona,
                        ':cedula'           => $cedula,
                        ':digito'           => $digito,
                        ':nombres'          => $nombres,
                        ':apellidos'        => $apellidos,
                        ':razon'            => $razon,
                        ':departamento'     => $departamento,
                        ':ciudad'           => $ciudad,
                        ':direccion'        => $direccion,
                        ':email'            => $email,
                        ':regimen'          => $regimen,
                        ':actividad'        => $actividad,
                        ':tarifa'           => $tarifa,
                        ':aiu'              => $aiu,
                        ':telefono'         => $telefono,
                        ':responsabilidad'  => $responsabilidadesInput,
                    ]);
                }

                $perfilExiste = true;
                header("Location: perfil.php?guardado=1");
                exit();

            } catch (PDOException $e) {
                error_log("Error en perfil.php: " . $e->getMessage());
                $mostrarMensaje = "error";
                $errorGuardar = "Error al guardar. Intenta de nuevo.";
            }
        }
    }
}
?>

<?php if (isset($mostrarMensaje)): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    <?php if ($mostrarMensaje === "guardar"): ?>
        Swal.fire({ icon: 'success', title: '¡Datos guardados correctamente!', text: 'El perfil del negocio ha sido actualizado.', confirmButtonColor: '#3085d6' });
    <?php elseif ($mostrarMensaje === "error"): ?>
        Swal.fire({ icon: 'error', title: 'Error al guardar los datos', text: '<?php echo isset($errorGuardar) ? htmlspecialchars($errorGuardar) : "Por favor revisa la conexión o los campos ingresados."; ?>' });
    <?php elseif ($mostrarMensaje === "cedulaInvalida"): ?>
        Swal.fire({ icon: 'warning', title: 'Cédula inválida', text: 'Debe contener solo números.' });
    <?php elseif ($mostrarMensaje === "sinPermiso"): ?>
        Swal.fire({ icon: 'error', title: 'Sin permisos', text: 'Solo el administrador puede modificar el perfil del negocio.', confirmButtonColor: '#d33' });
    <?php endif; ?>
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('guardado');
        url.searchParams.delete('editar');
        window.history.replaceState({}, document.title, url);
    }
});
</script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SOFI - Mi Negocio</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Poppins:300,400,500,600,700" rel="stylesheet">
  <link href="assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link href="assets/css/improved-style.css" rel="stylesheet">
</head>
<body>

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
          <li><a class="nav-link scrollto active" href="logout.php" style="color: darkblue;">Cerrar Sesión</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>

  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='dashboard.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    <div class="container" data-aos="fade-up">
      <form method="post" autocomplete="off">
        <div class="section-title">
          <h2>MI NEGOCIO</h2>
          <p>(Los campos marcados con * son obligatorios)</p>

          <div class="mt-3" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['username']); ?>
                <span class="badge bg-<?php echo $esAdmin ? 'danger' : 'secondary'; ?>">
                  <?php echo $esAdmin ? 'Administrador' : 'Usuario'; ?>
                </span>
              </div>
              <?php if ($perfilExiste && $esAdmin && !$modoEdicion): ?>
                <a href="?editar=1" class="btn btn-warning btn-sm">
                  <i class="bi bi-pencil-square"></i> Editar Perfil
                </a>
              <?php elseif ($modoEdicion && $esAdmin): ?>
                <div>
                  <span class="badge bg-warning text-dark me-2"><i class="bi bi-exclamation-triangle"></i> Modo Edición</span>
                  <a href="perfil.php" class="btn btn-secondary btn-sm"><i class="bi bi-x-circle"></i> Cancelar</a>
                </div>
              <?php endif; ?>
            </div>
            <?php if ($perfilExiste && !$esAdmin): ?>
              <div class="alert alert-info mt-2 mb-0" style="font-size: 0.9em;">
                <i class="bi bi-info-circle"></i> El perfil está bloqueado. Solo el administrador puede realizar cambios.
              </div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <h2>DATOS DE USUARIO</h2><br>
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Tipo de persona*</label><br>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" id="personaNatural" name="persona" value="natural"
                    onclick="toggleFields()" <?php echo ($persona=='natural')?'checked':''; ?>
                    <?php echo ($perfilExiste&&!$modoEdicion)?'disabled':''; ?>>
                  <label class="form-check-label" for="personaNatural">Persona Natural</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" id="personaJuridica" name="persona" value="juridica"
                    onclick="toggleFields()" <?php echo ($persona=='juridica')?'checked':''; ?>
                    <?php echo ($perfilExiste&&!$modoEdicion)?'disabled':''; ?>>
                  <label class="form-check-label" for="personaJuridica">Persona Jurídica</label>
                </div>
              </div>
              <div class="col-md-4">
                <label for="cedula" class="form-label">Cédula o NIT*</label>
                <input type="text" class="form-control" id="cedula" name="cedula"
                  placeholder="Ej: 1234567890" maxlength="10"
                  value="<?php echo htmlspecialchars($cedula); ?>"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':'required'; ?>>
              </div>
              <div class="col-md-4">
                <label for="digito" class="form-label">Dígito de verificación</label>
                <input type="text" class="form-control" id="digito" name="digito"
                  maxlength="1" pattern="[0-9]" placeholder="Dígito entre 0 y 9"
                  value="<?php echo htmlspecialchars($digito); ?>"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':''; ?>>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-4">
                <label for="nombres" class="form-label">Nombres</label>
                <input type="text" class="form-control" id="nombres" name="nombres"
                  value="<?php echo htmlspecialchars($nombres); ?>"
                  <?php echo ($persona!='natural'||($perfilExiste&&!$modoEdicion))?'disabled':''; ?>>
              </div>
              <div class="col-md-4">
                <label for="apellidos" class="form-label">Apellidos</label>
                <input type="text" class="form-control" id="apellidos" name="apellidos"
                  value="<?php echo htmlspecialchars($apellidos); ?>"
                  <?php echo ($persona!='natural'||($perfilExiste&&!$modoEdicion))?'disabled':''; ?>>
              </div>
              <div class="col-md-4">
                <label for="razon" class="form-label">Razón Social</label>
                <input type="text" class="form-control" id="razon" name="razon"
                  value="<?php echo htmlspecialchars($razon); ?>"
                  <?php echo ($persona!='juridica'||($perfilExiste&&!$modoEdicion))?'disabled':''; ?>>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label for="departamento" class="form-label">Departamento*</label>
                <input type="text" class="form-control" id="departamento" name="departamento"
                  placeholder="Buscar departamento..." autocomplete="off"
                  value="<?php echo htmlspecialchars($departamento); ?>"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':'required'; ?>>
              </div>
              <div class="col-md-6">
                <label for="ciudad" class="form-label">Ciudad*</label>
                <select id="ciudad" name="ciudad" class="form-control"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'disabled':''; ?>>
                  <option value="">Selecciona una ciudad...</option>
                  <?php if ($ciudad): ?>
                    <option value="<?php echo htmlspecialchars($ciudad); ?>" selected>
                      <?php echo htmlspecialchars($ciudad); ?>
                    </option>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-4">
                <label for="direccion" class="form-label">Dirección*</label>
                <input type="text" class="form-control" id="direccion" name="direccion"
                  placeholder="ej: Cll 12 #52-16"
                  value="<?php echo htmlspecialchars($direccion); ?>"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':'required'; ?>>
              </div>
              <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="number" class="form-control" id="telefono" name="telefono"
                  value="<?php echo htmlspecialchars($telefono); ?>"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':''; ?>>
              </div>
              <div class="col-md-4">
                <label for="email" class="form-label">Correo electrónico*</label>
                <input type="email" class="form-control" id="email" name="email"
                  placeholder="example@correo.com"
                  value="<?php echo htmlspecialchars($email); ?>"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':'required'; ?>>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <h2>PERFIL TRIBUTARIO</h2><br>
            <div class="row g-3 mt-2">
              <div class="col-md-4">
                <label for="regimen" class="form-label">Tipo de régimen*</label>
                <select class="form-select" name="regimen" id="regimen"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'disabled':'required'; ?>>
                  <option value="" <?php echo empty($regimen)?'selected':''; ?>>Seleccione un régimen...</option>
                  <?php foreach(['Responsable de IVA','No responsable de IVA','Régimen simple de tributación','Régimen especial'] as $op): ?>
                    <option value="<?php echo $op; ?>" <?php echo ($regimen==$op)?'selected':''; ?>><?php echo $op; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label for="actividadEconomica" class="form-label">Código de actividad económica</label>
                <input type="text" name="actividadEconomica" class="form-control" id="actividadEconomica"
                  placeholder="Ej: 6201"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':''; ?>>
              </div>
              <div class="col-md-4">
                <label for="actividad" class="form-label">Actividad económica</label>
                <input type="text" name="actividad" class="form-control" id="actividad"
                  placeholder="Ej: Comercio al por menor de alimentos"
                  value="<?php echo htmlspecialchars($actividad); ?>"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':''; ?>>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-4">
                <label for="tarifa" class="form-label">Tarifa ICA</label>
                <input type="number" name="tarifa" class="form-control" id="tarifa"
                  step="0.0001" placeholder="Ej: 0.004"
                  value="<?php echo $tarifa; ?>"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'readonly':''; ?>>
              </div>
              <div class="col-md-4 d-flex align-items-center">
                <div>
                  <label for="aiu" class="form-label d-block">Manejo de AIU</label>
                  <input type="checkbox" name="aiu" id="aiu" style="transform: scale(1.3); margin-top: 6px;"
                    <?php echo ($aiu==1)?'checked':''; ?>
                    <?php echo ($perfilExiste&&!$modoEdicion)?'disabled':''; ?>>
                </div>
              </div>
              <div class="col-md-12">
                <label for="responsabilidadesTributarias">Responsabilidades Tributarias*</label>
                <select id="responsabilidadesTributarias" name="responsabilidades[]"
                  class="form-select" multiple="multiple"
                  <?php echo ($perfilExiste&&!$modoEdicion)?'disabled':'required'; ?>>
                </select>
              </div>
            </div>

            <div class="mt-4">
              <?php if (!$perfilExiste || $modoEdicion): ?>
                <button type="submit" class="btn btn-primary" name="accion" value="btnAgregar">
                  <?php echo $perfilExiste ? 'Actualizar' : 'Guardar'; ?>
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>

  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer>

  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>

  <script>
  // Departamentos y ciudades
  document.addEventListener('DOMContentLoaded', function () {
    const departamentos = {"Amazonas":["Leticia","El Encanto","La Chorrera","La Pedrera","La Victoria","Mirití - Paraná","Puerto Alegría","Puerto Arica","Puerto Nariño","Puerto Santander","Tarapacá"],"Antioquia":["Medellín","Abejorral","Abriaqui","Alejandria","Amaga","Amalfi","Andes","Angelopolis","Angostura","Anori","Santafe de Antioquia","Anza","Apartado","Arboletes","Argelia","Armenia","Barbosa","Belmira","Bello","Betania","Betulia","Ciudad Bolivar","Briceño","Buritica","Caceres","Caicedo","Caldas","Campamento","Cañasgordas","Caracoli","Caramanta","Carepa","El Carmen de Viboral","Carolina","Caucasia","Chigorodo","Cisneros","Cocorna","Concepcion","Concordia","Copacabana","Dabeiba","Don Matias","Ebejico","El Bagre","Entrerrios","Envigado","Fredonia","Frontino","Giraldo","Girardota","Gomez Plata","Granada","Guadalupe","Guarne","Guatape","Heliconia","Hispania","Itagui","Ituango","Jardin","Jerico","La Ceja","La Estrella","La Pintada","La Union","Liborina","Maceo","Marinilla","Montebello","Murindo","Mutata","Nariño","Necocli","Nechi","Olaya","Peñol","Peque","Pueblorrico","Puerto Berrio","Puerto Nare","Puerto Triunfo","Remedios","Retiro","Rionegro","Sabanalarga","Sabaneta","Salgar","San Andres de Cuerquia","San Carlos","San Francisco","San Jeronimo","San Jose de la Montaña","San Juan de Uraba","San Luis","San Pedro","San Pedro de Uraba","San Rafael","San Roque","San Vicente","Santa Barbara","Santa Rosa de Osos","Santo Domingo","El Santuario","Segovia","Sonson","Sopetran","Tamesis","Taraza","Tarso","Titiribi","Toledo","Turbo","Uramita","Urrao","Valdivia","Valparaiso","Vegachi","Venecia","Vigia del Fuerte","Yali","Yarumal","Yolombo","Yondo","Zaragoza"],"Santander":["Bucaramanga","Aguada","Albania","Aratoca","Barbosa","Barichara","Barrancabermeja","Betulia","Bolívar","Cabrera","California","Capitanejo","Carcasi","Cepita","Cerrito","Charalá","Charta","Chima","Chipatá","Cimitarrá","Concepción","Confines","Contratación","Coromoro","Curití","El Carmen de Chucurí","El Guacamayo","El Peñón","El Playón","Encino","Enciso","Florian","Floridablanca","Galán","Gambita","Girón","Guaca","Guadalupe","Guapota","Guavata","Hato","Jesús María","Jordán","La Belleza","Landazuri","La Paz","Lebrija","Los Santos","Macaravita","Málaga","Matanza","Mogotes","Molagavita","Ocamonte","Oiba","Onzaga","Palmar","Palmas del Socorro","Páramo","Piedecuesta","Pinchote","Puente Nacional","Puerto Parra","Puerto Wilches","Rionegro","Sabana de Torres","San Andrés","San Benito","San Gil","San Joaquín","San José de Miranda","San Miguel","San Vicente de Chucurí","Santa Bárbara","Santa Helena del Opon","Simacota","Socorro","Suita","Sucre","Surata","Tona","Valle de San José","Vélez","Vetas","Villanueva","Zapatoca"]};

    const input = document.getElementById('departamento');
    const ciudadSelect = document.getElementById('ciudad');

    input.addEventListener('input', function () {
      const term = input.value.toLowerCase();
      const filtrados = Object.keys(departamentos).filter(d => d.toLowerCase().includes(term));
      mostrarSugerencias(filtrados);
    });

    function mostrarSugerencias(lista) {
      limpiarSugerencias();
      if (!lista.length) return;
      const ul = document.createElement('ul');
      ul.classList.add('list-group');
      lista.forEach(d => {
        const li = document.createElement('li');
        li.textContent = d;
        li.classList.add('list-group-item');
        li.style.cursor = 'pointer';
        li.addEventListener('click', () => {
          input.value = d;
          limpiarSugerencias();
          activarCiudades(d);
        });
        ul.appendChild(li);
      });
      input.parentNode.appendChild(ul);
    }

    function limpiarSugerencias() {
      const prev = document.querySelector('.list-group');
      if (prev) prev.remove();
    }

    function activarCiudades(dpto) {
      ciudadSelect.innerHTML = '<option value="">Selecciona una ciudad...</option>';
      ciudadSelect.disabled = false;
      (departamentos[dpto] || []).forEach(c => {
        const opt = document.createElement('option');
        opt.value = c; opt.textContent = c;
        ciudadSelect.appendChild(opt);
      });
    }
  });
  </script>

  <script>
  function toggleFields() {
    const natural  = document.getElementById("personaNatural").checked;
    const juridica = document.getElementById("personaJuridica").checked;
    const bloqueado = <?php echo ($perfilExiste && !$modoEdicion) ? 'true' : 'false'; ?>;
    document.getElementById("nombres").disabled   = !natural  || bloqueado;
    document.getElementById("apellidos").disabled = !natural  || bloqueado;
    document.getElementById("razon").disabled     = !juridica || bloqueado;
    const digito = document.getElementById("digito");
    digito.disabled = natural || bloqueado;
    if (natural && !bloqueado) digito.value = "";
  }
  document.addEventListener('DOMContentLoaded', toggleFields);
  </script>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const responsabilidades = [
      {n:1,nombre:'Aporte especial para la administración de justicia'},
      {n:2,nombre:'Gravamen a los movimientos financieros'},
      {n:5,nombre:'Impuesto renta y complementario régimen ordinario'},
      {n:7,nombre:'Retención en la fuente a título de renta'},
      {n:14,nombre:'Informante de exógena'},
      {n:15,nombre:'Autorretenedor'},
      {n:37,nombre:'Obligado a Facturar Electrónicamente'},
      {n:47,nombre:'Régimen Simple de Tributación-SIMPLE'},
      {n:48,nombre:'Impuesto sobre las ventas-IVA'},
      {n:49,nombre:'No responsable de IVA'},
    ];

    const $select = $('#responsabilidadesTributarias');
    responsabilidades.forEach(r => {
      $select.append(new Option(`${r.n} - ${r.nombre}`, `${r.n} - ${r.nombre}`));
    });

    <?php if (!empty($responsabilidadesInput)): ?>
    $select.val(<?php echo json_encode(explode(', ', $responsabilidadesInput)); ?>);
    <?php endif; ?>

    $select.select2({
      placeholder: 'Seleccione una o varias responsabilidades',
      width: '100%',
      closeOnSelect: false,
      disabled: <?php echo ($perfilExiste && !$modoEdicion) ? 'true' : 'false'; ?>
    });
  });
  </script>

</body>
</html>