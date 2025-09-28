<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Ingreso | SOFI UDES</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="style.css"> <!-- Este es tu estilo personalizado -->
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Estilos generales del sitio -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <div class="form">
        <div class="leftside">
            <img src="./Img/welcome.png" alt="Bienvenido">
            <h1>SOFI UDES</h1>
        </div>

        <div class="rightside">
            <h2>INICIO</h2>
            <div class="input-container">

                <!-- FORMULARIO DE LOGIN -->
                <form action="inicioSesion/inicioSesion.php" method="POST">
                    <div class="input">
                        <i class="fa fa-user"></i>
                        <input type="text" name="username" required placeholder="Usuario">
                    </div>
                    <div class="input">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" required placeholder="Contraseña">
                    </div>
                    <button type="submit" class="login">INICIO</button>
                </form>

                <!-- SOCIAL MEDIA ICONS -->
                <p>Otros medios</p>
                <div class="social-items">
                    <a href="#"><i class="fa fa-facebook"></i></a>
                    <a href="#"><i class="fa fa-google"></i></a>
                    <a href="#"><i class="fa fa-twitter"></i></a>
                </div>

                <!-- FORMULARIO DE REGISTRO -->
                <p>¿No tienes cuenta?</p>
                <form action="registrarse.php" method="post">
                    <input type="submit" class="login" value="REGISTRATE">
                </form>
            </div>
        </div>
    </div>

    <script src="login.js"></script> <!-- Este script debería manejar interacciones del login si tienes -->
</body>
</html>