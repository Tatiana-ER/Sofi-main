<?php

include ("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$clase=(isset($_POST['clase']))?$_POST['clase']:"";
$grupo=(isset($_POST['grupo']))?$_POST['grupo']:"";
$cuenta=(isset($_POST['cuenta']))?$_POST['cuenta']:"";
$subcuenta=(isset($_POST['subcuenta']))?$_POST['subcuenta']:"";
$auxiliar=(isset($_POST['auxiliar']))?$_POST['auxiliar']:"";
$moduloInventarios=(isset($_POST['moduloInventarios']))?$_POST['moduloInventarios']:"";
$naturalezaContable=(isset($_POST['naturalezaContable']))?$_POST['naturalezaContable']:"";
$controlCartera=(isset($_POST['controlCartera']))?$_POST['controlCartera']:"";
$activa=(isset($_POST['activa']))?$_POST['activa']:"";

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

switch($accion){
  case "btnAgregar":

      $sentencia=$pdo->prepare("INSERT INTO catalogoscuentascontables(clase,grupo,cuenta,subcuenta,auxiliar,moduloInventarios,naturalezaContable,controlCartera,activa) 
      VALUES (:clase,:grupo,:cuenta,:subcuenta,:auxiliar,:moduloInventarios,:naturalezaContable,:controlCartera,:activa)");
      

      $sentencia->bindParam(':clase',$clase);
      $sentencia->bindParam(':grupo',$grupo);
      $sentencia->bindParam(':cuenta',$cuenta);
      $sentencia->bindParam(':subcuenta',$subcuenta);
      $sentencia->bindParam(':auxiliar',$auxiliar);
      $sentencia->bindParam(':moduloInventarios',$moduloInventarios);
      $sentencia->bindParam(':naturalezaContable',$naturalezaContable);
      $sentencia->bindParam(':controlCartera',$controlCartera);
      $sentencia->bindParam(':activa',$activa);
      $sentencia->execute();

  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE catalogoscuentascontables 
                                  SET clase = :clase,
                                      grupo = :grupo,
                                      cuenta = :cuenta,
                                      subcuenta = :subcuenta,
                                      auxiliar = :auxiliar,
                                      moduloInventarios = :moduloInventarios,
                                      naturalezaContable = :naturalezaContable,
                                      controlCartera = :controlCartera,
                                      activa = :activa
                                  WHERE id = :id");

      // Enlazamos los parámetros 

      $sentencia->bindParam(':clase', $clase);
      $sentencia->bindParam(':grupo', $grupo);
      $sentencia->bindParam(':cuenta', $cuenta);
      $sentencia->bindParam(':subcuenta', $subcuenta);
      $sentencia->bindParam(':auxiliar', $auxiliar);
      $sentencia->bindParam(':moduloInventarios', $moduloInventarios);
      $sentencia->bindParam(':naturalezaContable', $naturalezaContable);
      $sentencia->bindParam(':controlCartera', $controlCartera);
      $sentencia->bindParam(':activa', $activa);
      $sentencia->bindParam(':id', $txtId);

      // Ejecutamos la sentencia
      $sentencia->execute();

      // Opcional: Redirigir o mostrar mensaje de éxito
      echo "<script>alert('Datos actualizados correctamente');</script>";

  break;

  case "btnEliminar":

    $sentencia = $pdo->prepare("DELETE FROM catalogoscuentascontables WHERE id = :id");
    $sentencia->bindParam(':id', $txtId);
    $sentencia->execute();


  break;


}

$sentencia= $pdo->prepare("SELECT * FROM `catalogoscuentascontables` WHERE 1");
$sentencia->execute();
$lista=$sentencia->fetchALL(PDO::FETCH_ASSOC);


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
    .table-container {
      margin: 0 auto;
      padding: 20px;
      max-width: 95%;
      width: 100%;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
      background-color: #ffffff;
      border-radius: 5px;
      overflow-x: auto; /* Importante: activa el scroll horizontal */
    }

    table {
      min-width: 1000px; /* O el valor mínimo que quieras para permitir el scroll */
      width: max-content; /* Se adapta al contenido */
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid black;
      padding: 10px;
      text-align: center;
      white-space: nowrap; /* Evita que el texto se rompa en varias líneas */
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
          <h2>CATÁLOGO CUENTAS CONTABLES</h2>
          <p>Para crear nueva cuenta contable diligencia los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>

        <form action="" method="post">

          <div>
            <label for="id" class="form-label">ID:</label>
            <input type="text" class="form-control" value="<?php echo $txtId;?>" id="txtId" name="txtId" readonly>
          </div>
          <div class="mb-3">
            <div class="form-group">
                <label for="clase">Clase*</label>
                <input type="text" value="<?php echo $clase;?>" id="clase" name="clase" class="form-control" placeholder="Ingresa una clase..." required>
            </div>
            <div class="form-group">
                <label for="grupo">Grupo*</label>
                <select value="<?php echo $grupo;?>" id="grupo" name="grupo" class="form-control" disabled required>
                    <option value="">Selecciona un grupo...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="cuenta">Cuenta*</label>
                <select value="<?php echo $cuenta;?>" id="cuenta" name="cuenta" class="form-control" disabled required>
                    <option value="">Selecciona una cuenta...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="subcuenta">Subcuenta*</label>
                <select value="<?php echo $subcuenta;?>" id="subcuenta" name="subcuenta" class="form-control" disabled required>
                    <option value="">Selecciona una subcuenta...</option>
                </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="auxiliar" class="form-label">Auxiliar</label>
            <input type="text" class="form-control" value="<?php echo $auxiliar;?>" id="auxiliar" name="auxiliar" placeholder="">
          </div>

          
          <div class="mb-3">
            <label for="moduloInventarios" class="form-label">Asociada al módulo de inventarios</label>
            <input type="checkbox" class="" value="<?php echo $moduloInventarios;?>" id="moduloInventarios" name="moduloInventarios" placeholder="" required>
          </div>

          <div>
            <label for="label3" class="form-label">Naturaleza contable*</label>
            <br>
            <label>
              <input type="radio" value="<?php echo $naturalezaContable;?>" name="naturalezaContable" value="debito" onclick="toggleTipoTercero()">
              Debito
            </label>
            <label>
              <input type="radio" value="<?php echo $naturalezaContable;?>" name="naturalezaContable" value="credito" onclick="toggleTipoTercero()">
              Credito
            </label>
          </div>
          <br>

          <div class="mb-3">
            <label for="controlCartera" class="form-label">Control de cartera</label>
            <input type="checkbox"  class="" value="<?php echo $controlCartera;?>" id="controlCartera" name="controlCartera" placeholder="" required>
          </div>

          <div class="mb-3">
            <label for="activa" class="form-label">Activa</label>
            <input type="checkbox" class="" value="<?php echo $activa;?>" id="activa" name="activa" placeholder="" required>
          </div>

          <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Guardar</button>
          <button value="btnModificar" type="submit" class="btn btn-primary"  name="accion" >Modificar</button>
          <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>

        </form>

        <div class="row">
          <div class="table-container">

            <table>
              <thead>
                <tr>
                  <th>Clase</th>
                  <th>Grupo</th>
                  <th>Cuenta</th>
                  <th>Subcuenta</th>
                  <th>Auxiliar</th>
                  <th>Modulo Inventarios</th>
                  <th>Naturaleza Contable</th>
                  <th>Control Cartera</th>
                  <th>Activa</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <?php foreach($lista as $usuario){ ?>
                <tr>
                  <td><?php echo $usuario['clase']; ?></td>
                  <td><?php echo $usuario['grupo']; ?></td>
                  <td><?php echo $usuario['cuenta']; ?></td>
                  <td><?php echo $usuario['subCuenta']; ?></td>
                  <td><?php echo $usuario['auxiliar']; ?></td>
                  <td><?php echo $usuario['moduloInventarios']; ?></td>
                  <td><?php echo $usuario['naturalezaContable']; ?></td>
                  <td><?php echo $usuario['controlCartera']; ?></td>
                  <td><?php echo $usuario['activa']; ?></td>
                  <td>

                  <form action="" method="post">

                  <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
                  <input type="hidden" name="clase" value="<?php echo $usuario['clase']; ?>" >
                  <input type="hidden" name="grupo" value="<?php echo $usuario['grupo']; ?>" >
                  <input type="hidden" name="cuenta" value="<?php echo $usuario['cuenta']; ?>" >
                  <input type="hidden" name="subcuenta" value="<?php echo $usuario['subCuenta']; ?>" >
                  <input type="hidden" name="auxiliar" value="<?php echo $usuario['auxiliar']; ?>" >
                  <input type="hidden" name="moduloInventarios" value="<?php echo $usuario['moduloInventarios']; ?>" >
                  <input type="hidden" name="naturalezaContable" value="<?php echo $usuario['naturalezaContable']; ?>" >
                  <input type="hidden" name="controlCartera" value="<?php echo $usuario['controlCartera']; ?>" >
                  <input type="hidden" name="activa" value="<?php echo $usuario['activa']; ?>" >
                  <input type="submit" value="Editar" name="accion">
                  <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
                  </form>

                  </td>

                </tr>
              <?php } ?>
            </table>


          </div>


      </div>

      <script>
        document.addEventListener('DOMContentLoaded', function () {
            const datos = {
                '1-Activo': {
                    '11-Efectivo y equivalentes de efectivo': {
                        '1105-Caja': ['110505-Caja general', '110510-Cajas menores'],
                        '1110-Bancos': ['111005-Moneda nacional'],
                        '1120-Cuentas de ahorro': ['112005-Bancos'],
                        '1145-Inversiones en efectivo': ['114505-Fiducias'],
                    },
                    '12-Inversiones en asociadas': {
                        '1205-Acciones': ['120535-Comercio al por mayor y al por menor'],
                        '1295-Acciones o derechos en clubes deportivos': ['129515-Acciones o derechos en clubes deportivos', '129595-Otras inversiones'],
                    },
                    '13-Deudores comerciales y otras cuentas por cobrar': {
                        '1305-Clientes nacionales': ['130505-Clientes nacionales'],
                        '1325-Cuentas por cobrar a socios y accionistas': ['132510-A accionistas'],
                        '1330-Anticipos y avances': ['133005-A proveedores','133010-A contratistas','133015-A trabajadores','13301-Otros'],
                        '1355-Anticipo de impuestos y contribuciones': ['135510-Anticipo de impuestos de industria y comercio','135515-Anticipo Retención en la fuente','135517-Impuesto a las ventas retenido','135518-Impuesto de industria y comercio retenido'],
                        '1365-Cuentas por cobrar a trabajadores': ['136515-Educación','136525-Calamidad domestica'],
                        '1380-Deudores varios': ['138095-Otros'],
                        '1399-Provisiones': ['139905-Clientes'],
                    },
                    '14-Inventarios': {
                        '1435-Mercancías no fabricadas por la empresa': ['143501-Mercancías no fabricadas'],
                        '1498-Otros': ['149801-Otros'],
                    },
                    '15-Propiedad planta y equipo': {
                        '1504-Terrenos': ['150405-Urbanos'],
                        '1516-Construcciones y edificaciones': ['151605-Edificios'],
                        '1524-Equipo de oficina': ['152405-Muebles y enseres','152410-Equipos'],
                        '1528-Equipo de computación y comunicación': ['152805-Equipos de procesamiento de datos'],
                        '1540-Flota y equipo de transporte': ['154005-Vehículos en leasing'],
                        '1592-Depreciación acumulada': ['159205-Construcciones y edificaciones','159215-Equipo de oficina','159220-Equipo de computación y comunicación','159235-Flota y equipo de transporte'],
                    },
                    '16-Intangibles': {
                        '1635-Licencias': ['163501-Derecho de uso','163515-Marca adquirida'],
                    },
                    '17-Otros activos no financieros': {
                        '1720-Entidades controladas en forma conjunta': ['172020-Negocios conjuntos'],
                    },
                    '18-Impuesto a las ganancias': {
                        '1805-Impuesto corriente': ['180505-Renta y complementarios'],
                    },
                },
                '2-Pasivo': {
                    '21-Pasivos financieros': {
                        '2105-Bancos nacionales': ['210510-Pagares'],
                        '2110-Depósitos recibidos': ['211095-Otros'],
                    },
                    '22-Proveedores': {
                        '2205-Proveedores nacionales': ['210505-Proveedores nacionales'],
                        '2210-Proveedores del exterior': ['221005-Proveedores del exterior'],
                    },
                    '23-Acreedores comerciales y otras cuentas por pagar': {
                        '2305-Cuentas corrientes comerciales': ['230505-Cuentas corrientes comerciales'],
                        '2335-Costos y gastos por pagar': ['233525-Honorarios','233595-Otros'],
                        '2365-Retenciones en la fuente': ['236505-Salarios y pagos laborales',
                        '236510-Dividendos y/o participaciones','236515-Honorarios',
                        '236520-Comisiones','236525-Servicios','236530-Arrendamientos',
                        '236535-Rendimientos financieros','236540-Retención por compras',
                        '236570-Otras retenciones','236575-Autoretenciones'],
                        '2367-Impuesto a las ventas retenido': ['236701-Impuesto a las ventas retenido','236705-Retención de impuesto a las ventas Iva','236768-Impuesto a las ventas retenido'],
                        '2368-Impuesto de industria y comercio retenido': ['236805-Retención industria y comercio Ica'],
                        '2370-Aportes a empresas promotoras de salud eps': ['237005-Aportes a entidades promotoras de salud eps',
                        '237006-Aporte a administradoras de riesgos profesionales','237010-Aportes al icbf Sena y cajas de compensación',
                        '237015-Aportes arl','236525-Servicios','237025-Embargos judiciales',
                        '237030-Libranzas','237045-Fondos','237050-Ahorro afc'],
                        '2380-Acreedores varios': ['238030-Fondos de cesantías y/o pensiones','238095-Otros'],
                    },
                    '24-Pasivos por impuestos': {
                        '2404-Proveedores nacionales': ['240405-Vigencia fiscal corriente'],
                        '2408-Impuesto sobre las ventas por pagar': ['240805-Iva generado en ventas','240806-Iva generado','240810-Iva descontable por compras','240815-Descontable por servicios','240820-Descontable por devoluciones','240830-Descontable régimen simplificado'],
                        '2495-otros': ['249501-Impuesto al consumo nacional'],
                    },
                    '25-Beneficios a empleados': {
                        '2505-Salarios por pagar': ['250505-Salarios por pagar'],
                        '2510-Pasivo estimado para obligaciones laborales': ['251010-Pasivo estimado para obligaciones laborales'],
                        '2515-Intereses sobre cesantías': ['251501-Intereses sobre cesantías'],
                        '2520-Prima de servicios': ['252001-Prima de servicios'],
                        '2525-Vacaciones': ['252501-Vacaciones'],
                    },
                    '28-Pasivos no financieros': {
                        '2805-Anticipos y avances recibidos': ['280505-De clientes'],
                        '2815-Ingresos recibidos para terceros': ['281505-Valores recibidos para terceros']
                    },
                },
                '3-Patrimonio': {
                    '31-Capital social': {
                        '3105-Capital suscrito y pagado': ['310505-Capital suscrito y pagado', '310510-Capital por suscribir (db)'],
                    },
                    '32-Superávit de capital': {
                        '3205-Prima en colocación de acciones cuotas o partes': ['320505-Prima en colocación de acciones', '310520-Superávit por el método de participación'],
                    },
                    '33-Reservas': {
                        '3305-Reservas obligatorias': ['330505-Reservas obligatorias'],
                    },
                    '36-Resultado del ejercicio': {
                        '3605-Utilidad del ejercicio': ['360505-Utilidad del ejercicio', '360597-Utilidad del ejercicio fiscal'],
                        '3610-Perdida del ejercicio': ['361005-Perdida del ejercicio', '361097-Perdida del ejercicio fiscal'],
                    },
                    '37-Resultados de ejercicios anteriores': {
                        '3705-Resultados de ejercicios anteriores': ['370505-Resultados de ejercicios anteriores'],
                        '3710-Convergencia': ['371005-Convergencia'],
                    },
                    '39-Afectaciones fiscales de ingresos y gastos': {
                        '3905-Resultados fiscales de ventas en ganancia ocasional': ['390505-Resultados fiscales de ventas en ganancia ocasional'],
                    },
                },
                '4-Ingresos': {
                    '41-Ingresos de actividades ordinarias': {
                        '4135-Comercio al por mayor y al detal': ['413501-Comercio al por mayor y al detal'],
                        '4175-Devolución en ventas': ['417505-Devolución'],
                        '4180-Servicios': ['418001-Servicios'],
                    },
                    '42-Otros ingresos de actividades ordinarias': {
                        '4210-Financieros': ['421020-Diferencia en cambio', '421040-Descuentos comerciales condicionados'],
                        '4218-Ingresos método de participación': ['421805-De sociedades anónimas y/o asimiladas'],
                        '4295-Diversos': ['429505-Aprovechamientos', '429581-Ajuste al peso', '429595-Ingresos diversos POS'],
                    },
                    '43-Ganancias': {
                        '4305-Propiedad planta y equipo': ['430505-Revaluación','430510-Salvamento'],
                    },
                    '44-Ingresos fiscales': {
                        '4405-Ingresos por ganancia ocasional': ['440505-Ingresos por ganancia ocasional'],
                        '4410-Ingresos renta ordinaria': ['441005-Recuperación de deducciones fiscales'],
                    },
                },
                '5-Gastos': {
                    '51-Administrativos': {
                        '5105-Gastos de personal': ['510524-Incapacidades', '510527-Auxilio de transporte', '510530-Cesantías', '510533-Intereses sobre cesantías',
                        '510539-Vacaciones', '510545-Auxilios', '510548-Bonificaciones', '510551-Dotación y suministro a trabajadores', '510560-Indemnizaciones laborales', '510563-Capacitación al personal', 
                        '510566-Gastos deportivos y de recreación', '510568-Aportes a administradora de riesgos laborales', '510569-Aportes a entidades promotoras de salud eps', '510570-Aporte a fondos de pensión y/o cesantías',
                        '510572-Aportes cajas de compensación familiar', '510575-Aportes icbf','510578-Aportes Sena', '510584-Gastos médicos y drogas', '510595-Otros'     
                        ],
                        '5110-Honorarios': ['511010-Revisoría fiscal','511015-Auditoria externa','511020-Avalúos','511025-Asesoría jurídica','511035-Asesoría técnica'],
                        '5115-Impuestos': ['511505-Industria y comercio', '511515-A la propiedad raíz', '511540-De vehículos', '511570-Prorrateo de Iva', '511595-Otros impuestos'],
                        '5120-Arrendamientos': ['512010-Construcciones y edificaciones', '512020-Equipo de oficina', '512025-Equipo de computación', '512095-Bodegaje'],
                        '5125-Contribuciones y afiliaciones': ['512510-Afiliaciones y sostenimiento'],
                        '5130-Seguros': ['513010-Cumplimiento', '513015-Corriente débil', '513020-Vida colectiva', '513030-Terremoto', '513035-Sustracción y hurto', '513040-Flota y equipo de transporte', '513070-Rotura de maquinaria', '5130-Obligatorio accidente de transito'],
                        '5135-Servicios': ['513505-Aseo y vigilancia', '513520-Procesamiento electrónico de datos', '513525-Acueducto y alcantarillado', '513530-Energía eléctrica', '513535-Teléfono', '513540-Correo portes y telegramas', '513550-Transporte fletes y acarreos', '513555-Gas', '513595-Otros'],
                        '5140-Gastos legales': ['514005-Notariales', '514010-Registro mercantil', '514015-Tramites y licencias', '514095-Otros'],
                        '5145-Mantenimiento y reparaciones': ['514510-Construcciones y edificaciones', '514520-Equipo de oficina', '514525-Equipo de computación y comunicación', '514540-Flota y equipo de transporte'],
                        '5150-Adecuación e instalación': ['515005-Instalaciones eléctricas', '515010-Arreglos ornamentales', '515015-Reparaciones locativas', '515020-Adecuación de puestos de trabajo'],
                        '5155-Gastos de viaje': ['515505-Alojamiento y manutención', '515515-Pasajes aéreos', '515520-Pasajes terrestres', '515595-Otros gastos de viaje'],
                        '5160-Depreciaciones': ['516005-Construcciones y edificaciones', '516015-Equipo de oficina', '516020-Equipo de computación y comunicación', '516035-Flota y equipo de transporte'],
                        '5165-Amortizaciones': ['516510-Intangibles', '516515-Cargos diferidos'],
                        '5195-Diversos': ['519510-Libros suscripciones periódicos y revistas', '519520-Gastos de representación y relaciones publicas', '519525-Elementos de aseo y cafetería', '519530-Útiles papelería y fotocopias', '519535-Combustibles y lubricantes', '519545-Taxis y buses', '519560-Casino y restaurante', '519565-Parqueaderos', '519595-Otros'],
                        '5199-Otros gastos': ['519905-Inversiones', '519999-Otros gastos'],
                    },
                    '52-Ventas': {
                        '5205-Gastos de personal': ['520503-Salario integral', '520506-Sueldos', '520512-Apoyo sostenimiento aprendices', '520515-Horas extras y recargos', '520524-Incapacidades', '520527-Auxilio de transporte', '520530-Cesantías', '520533-Intereses sobre cesantías' ,
                        '520536-Prima de servicios' ,'520539-Vacaciones', '520545-Auxilios', '520548-Bonificaciones' ,'520551-Dotación y suministro a trabajadores', '520560-Indemnizaciones laborales', '520563-Capacitación al personal' ,'520566-Gastos deportivos y de recreación', '520568-Aportes a administradora de riesgos laborales',
                        '520569-Aportes a entidades promotoras de salud eps', '520570-Aporte a fondos de pensión y/o cesantías' ,'520572-Aportes cajas de compensación familiar', '520575-Aportes icbf', '520578-Aportes Sena', '520584-Gastos médicos y drogas' ,'520595-Otros'],
                        '5235-Servicios': ['523510-Temporales', '523535-Teléfono', '523540-Correo portes y telegramas', '523560-Publicidad propaganda y promoción'],
                        '5255-Gastos de viaje': ['525515-Pasajes aéreos comercial'],
                        '5295-Diversos': ['529505-Comisiones', '529545-Taxis y buses', '529595-Gastos diversos'],
                    },
                    '53-Otros gastos de actividades ordinarias': {
                        '5305-Financieros': ['530505-Gastos bancarios', '530515-Comisiones', '530520-Intereses', '530525-Diferencia en cambio', '530535-Descuentos comerciales condicionados'],
                        '5310-Perdida en venta y retiro de bienes': ['531030-Retiro de propiedades planta y equipo'],
                        '5315-Gastos extraordinarios': ['531515-Costos y gastos de ejercicios anteriores', '531520-Impuestos asumidos', '531525-Costos y gastos no deducibles'],
                        '5395-Gastos diversos': ['539520-Multas sanciones y litigios', '539525-Donaciones', '539581-Ajuste al peso', '539595-Otros'],
                    },
                    '54-Impuesto de renta y complementarios': {
                        '5405-Impuesto de renta y complementarios': ['540505-Impuesto de renta y complementarios', '540510-Cree', '540515-Impuesto a la riqueza'],
                    },
                },
                '6-Costos de venta': {
                    '61-Costo de ventas y de prestación de servicios': {
                        '6135-Comercio al por mayor y al por menor': ['613505-Comercio al por mayor y al por menor'],
                        '6180-Servicios': ['618001-Servicios'],
                    },
                },
                '7-Costos de producción': {
                    '71-Costos de producción o de operación': {
                        '7105-Costos de producción o de operación': ['710505-Costos de producción o de operación'],
                    },
                    '72-Mano de obra directa': {
                        '7205-Mano de obra directa': ['720503-Salario integral','720506-Sueldos','720512-Apoyo sostenimiento aprendices',
                        '720515-Horas extras y recargos', '720524-Incapacidades', '720527-Auxilio de transporte', '720530-Cesantías', 
                        '720533-Intereses sobre cesantías','720536-Prima de servicios', '720539-Vacaciones', '720545-Auxilios',
                        '720548-Bonificaciones', '720551-Dotación y suministro a trabajadores', '720560-Indemnizaciones laborales', '720563-Capacitación al personal',
                        '720566-Gastos deportivos y de recreación', '720568-Aportes a administradora de riesgos laborales', '720569-Aportes a entidades promotoras de salud eps',
                        '720570-Aporte a fondos de pensión y/o cesantías', '720572-Aportes cajas de compensación familiar', '720575-Aportes icbf', '720578-Aportes Sena',
                        '720584-Gastos médicos y drogas', '720595-Otros'
                        ],
                    },
                    '73-Costos indirectos': {
                        '7305-Costos indirectos': ['730505-Costos indirectos'],
                    },
                    '74-Contratos de servicios': {
                        '7405-Contratos de servicios': ['740505-Contratos de servicios'],
                    },
                },
            };

            const inputClase = document.getElementById('clase');
            const grupoSelect = document.getElementById('grupo');
            const cuentaSelect = document.getElementById('cuenta');
            const subcuentaSelect = document.getElementById('subcuenta');

            inputClase.addEventListener('input', function () {
                const searchTerm = inputClase.value.toLowerCase();
                const clasesFiltradas = Object.keys(datos).filter(clase =>
                    clase.toLowerCase().includes(searchTerm)
                );
                mostrarSugerencias(clasesFiltradas, 'clase');
            });

            function mostrarSugerencias(opcionesFiltradas, tipo) {
                const listaSugerencias = document.createElement('ul');
                listaSugerencias.classList.add('list-group');

                opcionesFiltradas.forEach(opcion => {
                    const item = document.createElement('li');
                    item.textContent = opcion;
                    item.classList.add('list-group-item');
                    item.addEventListener('click', function () {
                        if (tipo === 'clase') {
                            inputClase.value = opcion;
                            limpiarSugerencias();
                            activarGrupoSelect(opcion);
                        }
                    });
                    listaSugerencias.appendChild(item);
                });

                limpiarSugerencias();
                inputClase.parentNode.appendChild(listaSugerencias);
            }

            function limpiarSugerencias() {
                const listaAnterior = document.querySelector('.list-group');
                if (listaAnterior) {
                    listaAnterior.remove();
                }
            }

            function activarGrupoSelect(clase) {
                grupoSelect.innerHTML = '<option value="">Selecciona un grupo...</option>';
                grupoSelect.disabled = false;

                Object.keys(datos[clase]).forEach(grupo => {
                    const option = document.createElement('option');
                    option.value = grupo;
                    option.textContent = grupo;
                    grupoSelect.appendChild(option);
                });

                grupoSelect.addEventListener('change', function () {
                    activarCuentaSelect(clase, grupoSelect.value);
                });
            }

            function activarCuentaSelect(clase, grupo) {
                cuentaSelect.innerHTML = '<option value="">Selecciona una cuenta...</option>';
                cuentaSelect.disabled = false;

                Object.keys(datos[clase][grupo]).forEach(cuenta => {
                    const option = document.createElement('option');
                    option.value = cuenta;
                    option.textContent = cuenta;
                    cuentaSelect.appendChild(option);
                });

                cuentaSelect.addEventListener('change', function () {
                    activarSubcuentaSelect(clase, grupo, cuentaSelect.value);
                });
            }

            function activarSubcuentaSelect(clase, grupo, cuenta) {
                subcuentaSelect.innerHTML = '<option value="">Selecciona una subcuenta...</option>';
                subcuentaSelect.disabled = false;

                datos[clase][grupo][cuenta].forEach(subcuenta => {
                    const option = document.createElement('option');
                    option.value = subcuenta;
                    option.textContent = subcuenta;
                    subcuentaSelect.appendChild(option);
                });
            }
        });
      </script>
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