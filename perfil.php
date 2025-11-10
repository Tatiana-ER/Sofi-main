<?php
$conexion = new mysqli("localhost", "root", "", "sofi");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Variables iniciales
$persona = $cedula = $digito = $nombres = $apellidos = $razon = "";
$departamento = $ciudad = $direccion = $email = $telefono = "";
$responsabilidadesInput = ""; 
$regimen = $actividad = "";
$tarifa = 0;
$aiu = 0;

$mostrarMensaje = isset($_GET['guardado']) && $_GET['guardado'] == 1 ? "guardar" : null;

// Procesar el formulario cuando se envíe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $persona = $_POST['persona'] ?? '';
    $cedula = $_POST['cedula'] ?? '';
    $digito = $_POST['digito'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $razon = $_POST['razon'] ?? '';
    $departamento = $_POST['departamento'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    $responsabilidadesSeleccionadas = $_POST['responsabilidades'] ?? [];
    $responsabilidadesInput = is_array($responsabilidadesSeleccionadas)
        ? implode(', ', $responsabilidadesSeleccionadas)
        : $responsabilidadesSeleccionadas;

    $regimen = $_POST['regimen'] ?? '';
    $actividad = $_POST['actividad'] ?? '';
    $tarifa = !empty($_POST['tarifa']) ? floatval($_POST['tarifa']) : NULL;
    $aiu = isset($_POST['aiu']) ? 1 : 0;

    // Validar cédula
    if (strlen($cedula) > 10) {
        $cedula = substr($cedula, 0, 10);
    }

    if (!ctype_digit($cedula)) {
        $mostrarMensaje = "cedulaInvalida";
    } else {
        $sql = "INSERT INTO perfil 
        (persona, cedula, digito, nombres, apellidos, razon, departamento, ciudad, direccion, email, regimen, actividad, tarifa, aiu, telefono, responsabilidad)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            die("Error al preparar la consulta: " . $conexion->error);
        }

        $stmt->bind_param(
            "ssisssssssssdiis",
            $persona,
            $cedula,
            $digito,
            $nombres,
            $apellidos,
            $razon,
            $departamento,
            $ciudad,
            $direccion,
            $email,
            $regimen,
            $actividad,
            $tarifa,
            $aiu,
            $telefono,
            $responsabilidadesInput
        );

        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?guardado=1");
            exit();
        } else {
            $mostrarMensaje = "error";
            $errorGuardar = $stmt->error;
        }

        $stmt->close();
    }
}

$conexion->close();
?>

<?php if (isset($mostrarMensaje)): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    <?php if ($mostrarMensaje === "guardar"): ?>
        Swal.fire({
            icon: 'success',
            title: 'Datos guardados correctamente',
            confirmButtonColor: '#3085d6'
        });
    <?php elseif ($mostrarMensaje === "error"): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error al guardar los datos',
            text: 'Por favor revisa la conexión o los campos ingresados.'
        });
    <?php elseif ($mostrarMensaje === "cedulaInvalida"): ?>
        Swal.fire({
            icon: 'warning',
            title: 'Cédula inválida',
            text: 'Debe contener solo números.'
        });
    <?php endif; ?>

    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('guardado');
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
 <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>   
   

  <link href="assets/css/improved-style.css" rel="stylesheet">

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
            <button class="btn-ir" onclick="window.location.href='dashboard.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
      <div class="container" data-aos="fade-up">

        <Form method="post" autocomplete="off">

          <div class="section-title">
            <h2>MI NEGOCIO</h2>
            <p>(Los campos marcados con * son obligatorios)</p>
          </div>

          <div class="mb-3">
            <h2>DATOS DE USUARIO</h2>
            <br>
            <div class="row mb-3">
                  <!-- Tipo de persona -->
                  <div class="col-md-4">
                    <label class="form-label">Tipo de persona*</label><br>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" id="personaNatural" name="persona" value="natural" onclick="toggleFields()">
                      <label class="form-check-label" for="personaNatural">Persona Natural</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" id="personaJuridica" name="persona" value="juridica" onclick="toggleFields()">
                      <label class="form-check-label" for="personaJuridica">Persona Jurídica</label>
                    </div>
                  </div>

                    <!-- Cédula o NIT -->
                    <div class="col-md-4">
                      <label for="cedula" class="form-label">Cédula o NIT*</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        id="cedula" 
                        name="cedula" 
                        placeholder="Ej: 1234567890"
                        maxlength="10"
                        oninput="limitLength(this, 10)"
                      >
                    </div>

                    <script>
                      function limitLength(input, maxLength) {
                        if (input.value.length > maxLength) {
                          input.value = input.value.slice(0, maxLength);
                        }
                      }
                    </script>


              <!-- Dígito de verificación -->
              <div class="col-md-4">
                <label for="digito" class="form-label">Dígito de verificación</label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="digito" 
                  name="digito" 
                  maxlength="1" 
                  pattern="[0-9]" 
                  title="Solo se permite un dígito entre 0 y 9"
                  placeholder="Dígito entre 0 y 9"
                  value="<?php echo htmlspecialchars($digito); ?>">
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
                  <label for="razon" class="form-label">Razón Social</label>
                  <input type="text" class="form-control" id="razon" name="razon"
                        value="<?php echo $razon ?>" disabled>
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
                    <label for="email" class="form-label">Correo electrónico*</label>
                    <input type="email" class="form-control" id="email" name="email"
                          placeholder="example@correo.com"
                          value="<?php echo $email; ?>" required>
                  </div>
                </div>
                  </div>
            <br>
          <div class="mb-3">
            <h2>PERFIL TRIBUTARIO</h2>
            <br>
            <!-- Fila 1: Tipo de régimen, Código actividad, Actividad económica -->
            <div class="row g-3 mt-2">
              <div class="col-md-4">
                <label for="regimen" class="form-label">Tipo de régimen*</label>
                <select class="form-select" name="regimen" id="regimen" required>
                  <option selected disabled>Seleccione un régimen...</option>
                  <option value="Responsable de IVA">Responsable de IVA</option>
                  <option value="No responsable de IVA">No responsable de IVA</option>
                  <option value="Régimen simple de tributación">Régimen simple de tributación</option>
                  <option value="Régimen especial">Régimen especial</option>
                </select>
              </div>

              <div class="col-md-4">
                <label for="actividadEconomica" class="form-label">Código de actividad económica</label>
                <input type="text" name="actividadEconomica" class="form-control" id="actividadEconomica" placeholder="Ej: 6201">
              </div>

              <div class="col-md-4">
                <label for="actividad" class="form-label">Actividad económica</label>
                <input type="text" name="actividad" class="form-control" id="actividad" placeholder="Ej: Comercio al por menor de alimentos">
              </div>
            </div>
              <!-- Fila 2: Tarifa ICA, AIU, Responsabilidades Tributarias -->
              <div class="row g-3 mt-2">
                <div class="col-md-4">
                  <label for="tarifa" class="form-label">Tarifa ICA</label>
                  <input type="number" name="tarifa" class="form-control" id="tarifa" step="0.0001" placeholder="Ej: 0.004">
                </div>

                <div class="col-md-4 d-flex align-items-center">
                  <div>
                    <label for="aiu" class="form-label d-block">Manejo de AIU</label>
                    <input type="checkbox" name="aiu" id="aiu" style="transform: scale(1.3); margin-top: 6px;">
                  </div>
                </div>

                <!-- Campo en el formulario --> 
                <label for="responsabilidadesTributarias">Responsabilidades Tributarias*</label>
                <select id="responsabilidadesTributarias" name="responsabilidades[]" class="form-select" multiple="multiple" required> 
                    <!-- Opciones se llenan por JS --> 
                </select> 
                 <div id="seleccionadas" class="mt-2"></div> 
                 <!-- Campo oculto que sí se envía --> 
                <input type="hidden" id="responsabilidadesInput" name="responsabilidadesSeleccionadas">

                  <!-- Botón -->
                <div class="mt-4">
                <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>


          <!-- Script de los campos departamentos y ciudades-->
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

          <!-- Script de los checklist Persona natural y Persona juridica -->
          <script>
            function toggleFields() {
                const personaNatural = document.getElementById("personaNatural").checked;
                const personaJuridica = document.getElementById("personaJuridica").checked;

                // Habilitar campos
                document.getElementById("nombres").disabled = !personaNatural;
                document.getElementById("apellidos").disabled = !personaNatural;
                document.getElementById("razon").disabled = !personaJuridica;

                // Desactivar campo "Dígito de verificación" si es persona natural
                digito.disabled = personaNatural;
                if (personaNatural) {
                    digito.value = ""; // limpiar el valor si estaba habilitado antes
                }

                // Limpiar campos no habilitados
                if (personaNatural) {
                    document.getElementById("nit").value = "";
                } else if (personaJuridica) {
                    document.getElementById("cedula").value = "";
                }
            }
          </script>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
              const responsabilidadesTributarias = [
                { numero: 1, nombre: 'Aporte especial para la administración de justicia' },
                { numero: 2, nombre: 'Gravamen a los movimientos financieros' },
                { numero: 3, nombre: 'Impuesto al patrimonio' },
                { numero: 4, nombre: 'Impuesto renta y complementario régimen especial' },
                { numero: 5, nombre: 'Impuesto renta y complementario régimen ordinario' },
                { numero: 6, nombre: 'Ingresos y patrimonio' },
                { numero: 7, nombre: 'Retención en la fuente a título de renta' },
                { numero: 8, nombre: 'Retención timbre nacional' },
                { numero: 9, nombre: 'Retención en la fuente en el impuesto sobre las ventas' },
                { numero: 10, nombre: 'Obligado aduanero' },
                { numero: 13, nombre: 'Gran contribuyente' },
                { numero: 14, nombre: 'Informante de exógena' },
                { numero: 15, nombre: 'Autorretenedor' },
                { numero: 16, nombre: 'Obligación facturar por ingresos bienes y/o servicios excluidos' },
                { numero: 17, nombre: 'Profesionales de compra y venta de divisas' },
                { numero: 18, nombre: 'Precios de transferencia' },
                { numero: 19, nombre: 'Productor de bienes y/o servicios exentos' },
                { numero: 20, nombre: 'Obtención NIT' },
                { numero: 21, nombre: 'Declarar ingreso o salida del país de divisas o moneda' },
                { numero: 22, nombre: 'Obligado a cumplir deberes formales a nombre de terceros' },
                { numero: 23, nombre: 'Agente de retención en ventas' },
                { numero: 24, nombre: 'Declaración consolidada precios de transferencia' },
                { numero: 26, nombre: 'Declaración individual precios de transferencia' },
                { numero: 32, nombre: 'Impuesto nacional a la gasolina y al ACPM' },
                { numero: 33, nombre: 'Impuesto nacional al consumo' },
                { numero: 36, nombre: 'Establecimiento Permanente' },
                { numero: 37, nombre: 'Obligado a Facturar Electrónicamente' },
                { numero: 38, nombre: 'Facturación Electrónica Voluntaria' },
                { numero: 39, nombre: 'Proveedor de Servicios Tecnológicos PST' },
                { numero: 41, nombre: 'Declaración anual de activos en el exterior e rendimientos financieros' },
                { numero: 46, nombre: 'IVA Prestadores de Servicios desde el Exterior' },
                { numero: 47, nombre: 'Régimen Simple de Tributación-SIMPLE' },
                { numero: 48, nombre: 'Impuesto sobre las ventas-IVA' },
                { numero: 49, nombre: 'No responsable de IVA' },
                { numero: 50, nombre: 'No responsable de Consumo restaurantes y bares' },
                { numero: 51, nombre: 'Agente retención impoconsumo de bienes inmuebles' },
                { numero: 52, nombre: 'Facturador electrónico' },
                { numero: 53, nombre: 'Persona Jurídica No Responsable de IVA' }
              ];

              const $select = $('#responsabilidadesTributarias');

              // Poblar <select> con opciones
              responsabilidadesTributarias.forEach(r => {
                const text = `${r.numero} - ${r.nombre}`;
                const option = new Option(text, r.numero + ' - ' + r.nombre, false, false);
                $select.append(option);
              });

              // Inicializar Select2
              $select.select2({
                placeholder: 'Seleccione una o varias responsabilidades',
                width: '100%',
                dropdownParent: $('body'),        
                maximumSelectionLength: 10,      
                closeOnSelect: false,             
                dropdownCssClass: 'custom-select2-dropdown'
              });
              const style = document.createElement('style');
              style.innerHTML = `
                .select2-container--open { z-index: 99999 !important; }
                .custom-select2-dropdown .select2-results { max-height: 240px; overflow-y: auto; }
              `;
              document.head.appendChild(style);
            });
            </script>

        </Form>
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