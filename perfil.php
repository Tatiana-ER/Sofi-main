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

        <Form method="post" autocomplete="off">

          <div class="section-title">
            <br><br><br><br><br>
            <h2>Mi Negocio</h2>
            <p>Perfil</p>
            <p>(Los campos marcados con * son obligatorios)</p>
          </div>

          <div class="mb-3">
            <h1>DATOS DE USUARIO</h1>
            <br>
            <div>
              <label for="label3" class="form-label">Tipo de persona*</label>
              <br>
              <label>
                <input type="radio" id="personaNatural" name="tipoPersona" value="natural" onclick="toggleFields()">
                Persona Natural
              </label>
              <label>
                <input type="radio" id="personaJuridica" name="tipoPersona" value="juridica" onclick="toggleFields()">
                Persona Jurídica
              </label>
            </div>
            <br>
            <div>
              <label for="cedula" class="form-label">Cédula de Ciudadanía o NIT*</label>
              <input type="number" name="cedula" class="form-control" id="cedula" required>
            </div>
            <br>
            <div>
              <label for="digito" class="form-label">Digito de verificación</label>
              <input type="text" class="form-control" value="<?php echo $digito;?>" id="digito" name="digito" maxlength="1" pattern="[1-9]" title="Solo se permite un dígito entre 1 y 9" placeholder="Dígito entre 1 y 9">
            </div>
            <div>
              <label for="nombres" class="form-label">Nombres</label>
              <input type="text" name="nombres" class="form-control" id="nombres" disabled>
            </div>
            <div>
              <label for="apellidos" class="form-label">Apellidos</label>
              <input type="text" name="apellidos" class="form-control" id="apellidos" disabled>
            </div>
            <div>
              <label for="razonSocial" class="form-label">Razón Social</label>
              <input type="text" name="razonSocial" class="form-control" id="razonSocial" disabled>       
            </div>
            <br>
            <div class="form-group">
              <label for="departamento" class="form-label">Departamento*</label>
              <input type="text" name="departamento" id="departamento" class="form-control" placeholder="Buscar departamento..." autocomplete="off" required>
            </div>
            <div class="form-group mt-3">
              <label for="ciudad"class="form-label">Ciudad*</label>
              <select id="ciudad" name="ciudad" class="form-control" disabled required>
                  <option value="">Selecciona una ciudad...</option>
              </select>
            </div>
            <br>
            <label for="direccion" class="form-label">Dirección*</label>
            <input type="text" name="direccion" class="form-control" id="direccion" placeholder="ej: Cll 12 #52-16" required>
            <br>
            <label for="telefono" class="form-label">Telefono</label>
            <input type="number" name="telefono" class="form-control" id="telefono" placeholder="">
            <br>
              <label for="email" class="form-label">Correo Electronico*</label>
            <input type="email" name="email" class="form-control" id="email" placeholder="example@correo.com" required>
            <br>
          </div>

          <div class="mb-3">
            <h1>PERFIL TRIBUTARIO</h1>
            <br>
            <select class="form-select" name="tipoRegimen" aria-label="Default select example" required>
              <option selected>Tipo de regimen</soption>
              <option value="1">Responsable de iva</option>
              <option value="2">No responsable de iva</option>
              <option value="3">Régimen simple de tributación</option>
              <option value="3">Régimen especial</option>
            </select>
            <br>
            <label for="actividadEconomica" class="form-label">Codigo de actividad económica</label>
            <input type="text" name="actividadEconomica" class="form-control" id="actividadEconomica" placeholder="">
            <br>
            <label for="actividadEconomica" class="form-label">Actividad económica</label>
            <input type="text" name="actividadEconomica" class="form-control" id="actividadEconomica" placeholder="">
            <br>
            <label for="tarifaIca" class="form-label">Tarifa ICA</label>
            <input type="number" name="tarifaIca" class="form-control" id="tarifaIca" placeholder="ej:0,004">
            <br>
            <div class="mb-3">
              <label for="manejoAiu" class="form-label">Manejo de AIU</label>
              <input type="checkbox" name="manejoAiu" class="" id="manejoAiu" placeholder="">
            </div>
            <div class="mb-3">
              <label for="responsabilidadesTributarias" class="form-label">Responsabilidades Tributarias*</label>
              <input type="text" name="responsabilidadesTributarias" id="responsabilidadesTributarias" class="form-control" placeholder="Buscar responsabilidades tributarias..." autocomplete="off" required>
              <div id="seleccionadas" class="mt-2"></div>
            </div>
            <br>
          </div>

          <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Agregar</button>

          <ul id="resultList"></ul>

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
                document.getElementById("razonSocial").disabled = !personaJuridica;

                // Limpiar campos no habilitados
                if (personaNatural) {
                    document.getElementById("nit").value = "";
                } else if (personaJuridica) {
                    document.getElementById("cedula").value = "";
                }
            }
          </script>

          <!-- Script del campo responsabilidades tributaria -->
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
                    { numero: 11, nombre: 'Gran contribuyente' },
                    { numero: 12, nombre: 'Informante de exógena' },
                    { numero: 13, nombre: 'Autorretenedor' },
                    { numero: 14, nombre: 'Obligación facturar por ingresos bienes y/o servicios excluidos' },
                    { numero: 15, nombre: 'Profesionales de compra y venta de divisas' },
                    { numero: 16, nombre: 'Precios de transferencia' },
                    { numero: 17, nombre: 'Productor de bienes y/o servicios exentos' },
                    { numero: 18, nombre: 'Obtención NIT' },
                    { numero: 19, nombre: 'Declarar ingreso o salida del país de divisas o moneda' },
                    { numero: 20, nombre: 'Obligado a cumplir deberes formales a nombre de terceros' },
                    { numero: 21, nombre: 'Agente de retención en ventas' },
                    { numero: 22, nombre: 'Declaración consolidada precios de transferencia' },
                    { numero: 23, nombre: 'Declaración individual precios de transferencia' },
                    { numero: 24, nombre: 'Impuesto nacional a la gasolina y al ACPM' },
                    { numero: 25, nombre: 'Impuesto nacional al consumo' },
                    { numero: 26, nombre: 'Establecimiento Permanente' },
                    { numero: 27, nombre: 'Obligado a Facturar Electrónicamente' },
                    { numero: 28, nombre: 'Facturación Electrónica Voluntaria' },
                    { numero: 29, nombre: 'Proveedor de Servicios Tecnológicos PST' },
                    { numero: 30, nombre: 'Declaración anual de activos en el exterior e rendimientos financieros' },
                    { numero: 31, nombre: 'IVA Prestadores de Servicios desde el Exterior' },
                    { numero: 32, nombre: 'Régimen Simple de Tributación – SIMPLE' },
                    { numero: 33, nombre: 'Impuesto sobre las ventas – IVA' },
                    { numero: 34, nombre: 'No responsable de IVA' },
                    { numero: 35, nombre: 'Impuesto nacional a la gasolina' },
                    { numero: 36, nombre: 'No responsable de Consumo restaurantes y bares' },
                    { numero: 37, nombre: 'Agente retención impoconsumo de bienes inmuebles' },
                    { numero: 38, nombre: 'Facturador electrónico' },
                    { numero: 39, nombre: 'Persona Jurídica No Responsable de IVA' }
                ];
        
                const input = document.getElementById('responsabilidadesTributarias');
                const contenedorSeleccionadas = document.getElementById('seleccionadas');
                let seleccionadas = [];
        
                input.addEventListener('input', function () {
                    const searchTerm = input.value.toLowerCase();
                    const filteredResponsabilidades = responsabilidadesTributarias.filter(responsabilidad =>
                        responsabilidad.nombre.toLowerCase().includes(searchTerm) || responsabilidad.numero.toString().includes(searchTerm)
                    );
                    mostrarSugerencias(filteredResponsabilidades);
                });
        
                function mostrarSugerencias(responsabilidadesFiltradas) {
                    const listaSugerencias = document.createElement('ul');
                    listaSugerencias.classList.add('list-group');
        
                    responsabilidadesFiltradas.forEach(responsabilidad => {
                        const item = document.createElement('li');
                        item.textContent = `${responsabilidad.numero} - ${responsabilidad.nombre}`;
                        item.classList.add('list-group-item');
                        item.addEventListener('click', function () {
                            agregarSeleccion(responsabilidad);
                            limpiarSugerencias();
                        });
                        listaSugerencias.appendChild(item);
                    });
        
                    limpiarSugerencias();
                    input.parentNode.appendChild(listaSugerencias);
                }
        
                function agregarSeleccion(responsabilidad) {
                    if (!seleccionadas.find(r => r.numero === responsabilidad.numero)) {
                        seleccionadas.push(responsabilidad);
                        actualizarCampoSeleccionadas();
                    }
                    input.value = ''; // Limpia el campo para permitir nuevas selecciones
                }
        
                function actualizarCampoSeleccionadas() {
                    contenedorSeleccionadas.innerHTML = ''; // Limpiar las selecciones previas
        
                    seleccionadas.forEach(responsabilidad => {
                        const itemSeleccionado = document.createElement('span');
                        itemSeleccionado.textContent = `${responsabilidad.numero} - ${responsabilidad.nombre}`;
                        itemSeleccionado.classList.add('badge', 'bg-primary', 'm-1');
        
                        // Botón para eliminar responsabilidad seleccionada
                        const botonEliminar = document.createElement('button');
                        botonEliminar.textContent = 'x';
                        botonEliminar.classList.add('btn', 'btn-danger', 'btn-sm', 'ms-2');
                        botonEliminar.addEventListener('click', function () {
                            eliminarSeleccion(responsabilidad.numero);
                        });
        
                        itemSeleccionado.appendChild(botonEliminar);
                        contenedorSeleccionadas.appendChild(itemSeleccionado);
                    });
                }
        
                function eliminarSeleccion(numero) {
                    seleccionadas = seleccionadas.filter(r => r.numero !== numero);
                    actualizarCampoSeleccionadas();
                }
        
                function limpiarSugerencias() {
                    const listaAnterior = document.querySelector('.list-group');
                    if (listaAnterior) {
                        listaAnterior.remove();
                    }
                }
            });
          </script>
        </Form>

        <?php
            include("send.php");
        ?>

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